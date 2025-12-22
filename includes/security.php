<?php
/**
 * 安全配置和防护类
 * 包含XSS防护、SQL注入防护、CSRF防护等安全措施
 */

class Security {
    
    /**
     * 防止XSS攻击
     */
    public static function sanitizeOutput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeOutput'], $data);
        }
        
        // 移除脚本标签和事件处理器
        $data = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $data);
        $data = preg_replace('/on\w+\s*=\s*(["\']).*?\1/is', '', $data);
        
        // HTML转义
        $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $data;
    }
    
    /**
     * 防止SQL注入
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        // 去除多余空格
        $input = trim($input);
        
        // 去除反斜杠（如果启用了magic_quotes）
        if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
            $input = stripslashes($input);
        }
        
        // 防止SQL注入（使用参数化查询时这个不是必需的，但作为额外保护）
        $input = str_replace(["'", '"', ';', '--'], ['', '', '', ''], $input);
        
        return $input;
    }
    
    /**
     * 验证文件上传
     */
    public static function validateUploadedFile($file) {
        $errors = [];
        
        // 检查上传错误
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = '文件上传失败：' . self::getUploadError($file['error']);
        }
        
        // 检查文件大小
        if ($file['size'] > UPLOAD_MAX_SIZE) {
            $errors[] = '文件大小超过限制（最大 ' . formatFileSize(UPLOAD_MAX_SIZE) . '）';
        }
        
        // 检查文件类型
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ALLOWED_FILE_TYPES)) {
            $errors[] = '不支持的文件类型，仅支持：' . implode(', ', ALLOWED_FILE_TYPES);
        }
        
        // 检查MIME类型
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimeTypes = [
            'xls' => ['application/vnd.ms-excel', 'application/excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'csv' => ['text/csv', 'text/plain', 'application/vnd.ms-excel']
        ];
        
        if (!isset($allowedMimeTypes[$fileExtension]) || 
            !in_array($mimeType, $allowedMimeTypes[$fileExtension])) {
            $errors[] = '文件内容与类型不匹配';
        }
        
        return $errors;
    }
    
    /**
     * 获取上传错误信息
     */
    private static function getUploadError($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => '文件大小超过服务器限制',
            UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
            UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败',
            UPLOAD_ERR_EXTENSION => 'PHP扩展阻止了文件上传'
        ];
        
        return $errors[$errorCode] ?? '未知上传错误';
    }
    
    /**
     * 生成安全的文件名
     */
    public static function generateSafeFilename($originalName) {
        // 移除路径信息
        $filename = basename($originalName);
        
        // 移除特殊字符
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // 添加时间戳和随机字符串
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        
        $safeName = substr($nameWithoutExt, 0, 50) . '_' . 
                   time() . '_' . 
                   bin2hex(random_bytes(4)) . 
                   ($extension ? '.' . $extension : '');
        
        return $safeName;
    }
    
    /**
     * 验证CSRF令牌
     */
    public static function validateCsrfToken($token) {
        if (!isset($_SESSION['csrf_tokens'][$token])) {
            return false;
        }
        
        if ($_SESSION['csrf_tokens'][$token] < time()) {
            unset($_SESSION['csrf_tokens'][$token]);
            return false;
        }
        
        unset($_SESSION['csrf_tokens'][$token]);
        return true;
    }
    
    /**
     * 生成CSRF令牌
     */
    public static function generateCsrfToken() {
        if (!isset($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_tokens'][$token] = time() + CSRF_TOKEN_EXPIRY;
        
        // 清理过期的令牌
        self::cleanExpiredCsrfTokens();
        
        return $token;
    }
    
    /**
     * 清理过期的CSRF令牌
     */
    private static function cleanExpiredCsrfTokens() {
        if (!isset($_SESSION['csrf_tokens'])) {
            return;
        }
        
        foreach ($_SESSION['csrf_tokens'] as $token => $expiry) {
            if ($expiry < time()) {
                unset($_SESSION['csrf_tokens'][$token]);
            }
        }
    }
    
    /**
     * 验证请求频率限制
     */
    public static function checkRateLimit($key, $maxRequests = 60, $timeWindow = 60) {
        $cacheKey = "rate_limit_{$key}";
        $currentTime = time();
        
        $requests = getCache($cacheKey, []);
        
        // 移除过期的请求记录
        $requests = array_filter($requests, function($timestamp) use ($currentTime, $timeWindow) {
            return ($currentTime - $timestamp) < $timeWindow;
        });
        
        // 检查是否超过限制
        if (count($requests) >= $maxRequests) {
            return false;
        }
        
        // 记录当前请求
        $requests[] = $currentTime;
        setCache($cacheKey, $requests, $timeWindow);
        
        return true;
    }
    
    /**
     * 设置安全头信息
     */
    public static function setSecurityHeaders() {
        // 防止点击劫持
        header('X-Frame-Options: SAMEORIGIN');
        
        // 防止MIME类型嗅探
        header('X-Content-Type-Options: nosniff');
        
        // 防止XSS攻击
        header('X-XSS-Protection: 1; mode=block');
        
        // 防止内容盗链
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // 安全传输策略
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        
        // 内容安全策略
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com",
            "img-src 'self' data: https:",
            "font-src 'self' https://cdnjs.cloudflare.com",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'"
        ];
        
        header("Content-Security-Policy: " . implode('; ', $csp));
    }
    
    /**
     * 验证电子邮件格式
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * 验证密码强度
     */
    public static function isStrongPassword($password) {
        if (strlen($password) < 8) {
            return false;
        }
        
        // 检查是否包含大小写字母和数字
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasLower = preg_match('/[a-z]/', $password);
        $hasDigit = preg_match('/[0-9]/', $password);
        
        return $hasUpper && $hasLower && $hasDigit;
    }
    
    /**
     * 加密敏感数据
     */
    public static function encrypt($data, $key) {
        $method = 'AES-256-CBC';
        $ivLength = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * 解密敏感数据
     */
    public static function decrypt($data, $key) {
        $method = 'AES-256-CBC';
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length($method);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        return openssl_decrypt($encrypted, $method, $key, 0, $iv);
    }
    
    /**
     * 记录安全事件
     */
    public static function logSecurityEvent($event, $level = 'INFO', $details = '') {
        $logFile = BASE_PATH . '/logs/security.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = getClientIP();
        
        $logMessage = sprintf(
            "[%s] [%s] [IP:%s] %s - %s\n",
            $timestamp,
            $level,
            $ip,
            $event,
            $details
        );
        
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// 自动设置安全头
Security::setSecurityHeaders();

?>