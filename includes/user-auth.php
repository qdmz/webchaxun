<?php
/**
 * 用户认证和权限管理类
 * 优化版本 - 包含会话管理、权限验证、安全防护
 */

class UserAuth {
    private $db;
    private $sessionTimeout = SESSION_TIMEOUT;
    
    public function __construct() {
        $this->db = getDatabase();
        $this->initSession();
    }
    
    /**
     * 初始化会话
     */
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => $this->sessionTimeout,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'] ?? '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            
            session_name('EXCEL_SYSTEM_SESSION');
            session_start();
        }
        
        // 检查会话超时
        $this->checkSessionTimeout();
        
        // 防止会话固定攻击
        if (empty($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
            $_SESSION['ip_address'] = getClientIP();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }
        
        // 验证会话安全性
        $this->validateSessionSecurity();
    }
    
    /**
     * 检查会话超时
     */
    private function checkSessionTimeout() {
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity']) > $this->sessionTimeout) {
            $this->logout();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * 验证会话安全性
     */
    private function validateSessionSecurity() {
        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== getClientIP()) {
            $this->logout();
            throw new Exception('会话安全验证失败');
        }
        
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            $this->logout();
            throw new Exception('会话安全验证失败');
        }
    }
    
    /**
     * 用户登录
     */
    public function login($username, $password, $rememberMe = false) {
        // 防止暴力破解
        $this->checkLoginAttempts($username);
        
        // 验证输入
        if (empty($username) || empty($password)) {
            $this->logFailedAttempt($username, '空用户名或密码');
            return ['success' => false, 'error' => '用户名和密码不能为空'];
        }
        
        // 查询用户
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE username = ? AND status = 'active'",
            [$username]
        );
        
        if (!$user) {
            $this->logFailedAttempt($username, '用户不存在');
            return ['success' => false, 'error' => '用户名或密码错误'];
        }
        
        // 验证密码
        if (!password_verify($password, $user['password'])) {
            $this->logFailedAttempt($username, '密码错误');
            return ['success' => false, 'error' => '用户名或密码错误'];
        }
        
        // 检查是否需要更新密码哈希
        if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_BCRYPT_COST])) {
            $this->updatePasswordHash($user['id'], $password);
        }
        
        // 设置会话
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // 记录登录成功
        logUserAction($user['id'], '用户登录', "IP: " . getClientIP());
        
        // 清除失败尝试记录
        $this->clearFailedAttempts($username);
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * 用户退出
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            logUserAction($_SESSION['user_id'], '用户退出');
        }
        
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * 检查是否已登录
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && $this->checkSessionTimeout();
    }
    
    /**
     * 获取当前用户信息
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $user = $this->db->fetchOne(
            "SELECT id, username, real_name, role, created_at FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );
        
        return $user ?: null;
    }
    
    /**
     * 检查权限
     */
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $role = $_SESSION['role'];
        
        // 权限映射表
        $permissions = [
            'admin' => ['manage_users', 'upload_files', 'view_files', 'search_data', 'delete_files'],
            'user' => ['view_files', 'search_data']
        ];
        
        return isset($permissions[$role]) && in_array($permission, $permissions[$role]);
    }
    
    /**
     * 检查登录尝试次数
     */
    private function checkLoginAttempts($username) {
        $cacheKey = "login_attempts_{$username}";
        $attempts = getCache($cacheKey, 0);
        
        if ($attempts >= 5) {
            throw new Exception('登录尝试次数过多，请稍后再试');
        }
    }
    
    /**
     * 记录登录失败尝试
     */
    private function logFailedAttempt($username, $reason) {
        $cacheKey = "login_attempts_{$username}";
        $attempts = getCache($cacheKey, 0);
        $attempts++;
        
        // 设置15分钟过期时间
        setCache($cacheKey, $attempts, 900);
        
        logSystemEvent('登录失败', 'WARNING', "用户名: {$username}, 原因: {$reason}, IP: " . getClientIP());
    }
    
    /**
     * 清除失败尝试记录
     */
    private function clearFailedAttempts($username) {
        $cacheKey = "login_attempts_{$username}";
        deleteCache($cacheKey);
    }
    
    /**
     * 更新密码哈希
     */
    private function updatePasswordHash($userId, $password) {
        $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_BCRYPT_COST]);
        
        $this->db->update(
            'users',
            ['password' => $newHash, 'updated_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$userId]
        );
        
        logUserAction($userId, '密码哈希更新');
    }
    
    /**
     * 修改密码
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        if (!$this->isLoggedIn() || $_SESSION['user_id'] != $userId) {
            return ['success' => false, 'error' => '无权限操作'];
        }
        
        // 验证当前密码
        $user = $this->db->fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'error' => '当前密码错误'];
        }
        
        // 验证新密码强度
        if (!isStrongPassword($newPassword)) {
            return ['success' => false, 'error' => '密码强度不足，必须包含大小写字母和数字，长度至少8位'];
        }
        
        // 更新密码
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => PASSWORD_BCRYPT_COST]);
        
        $this->db->update(
            'users',
            ['password' => $newHash, 'updated_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$userId]
        );
        
        logUserAction($userId, '修改密码');
        
        return ['success' => true];
    }
    
    /**
     * 用户注册（仅管理员可用）
     */
    public function registerUser($username, $password, $realName, $role = 'user') {
        if (!$this->hasPermission('manage_users')) {
            return ['success' => false, 'error' => '无权限创建用户'];
        }
        
        // 验证输入
        if (empty($username) || empty($password) || empty($realName)) {
            return ['success' => false, 'error' => '所有字段都必须填写'];
        }
        
        if (!isStrongPassword($password)) {
            return ['success' => false, 'error' => '密码强度不足'];
        }
        
        // 检查用户名是否已存在
        $existingUser = $this->db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
        if ($existingUser) {
            return ['success' => false, 'error' => '用户名已存在'];
        }
        
        // 创建用户
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_BCRYPT_COST]);
        
        $userId = $this->db->insert('users', [
            'username' => $username,
            'password' => $passwordHash,
            'real_name' => $realName,
            'role' => $role,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        logUserAction($_SESSION['user_id'], '创建用户', "用户名: {$username}, 角色: {$role}");
        
        return ['success' => true, 'user_id' => $userId];
    }
    
    /**
     * 获取用户列表（仅管理员可用）
     */
    public function getUserList($page = 1, $limit = 20) {
        if (!$this->hasPermission('manage_users')) {
            return ['success' => false, 'error' => '无权限查看用户列表'];
        }
        
        $offset = ($page - 1) * $limit;
        
        $users = $this->db->fetchAll(
            "SELECT id, username, real_name, role, status, created_at FROM users ORDER BY id DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
        
        $total = $this->db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
        
        return [
            'success' => true,
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ];
    }
}

?>