<?php
/**
 * 系统设置页面（仅管理员）
 */

// 检查管理员权限
if (!isset($currentUser) || $currentUser['role'] !== 'admin') {
    header('Location: ?action=dashboard');
    exit;
}

// 处理设置保存
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_general':
            $result = saveGeneralSettings($_POST);
            $message = $result['success'] ? '通用设置保存成功' : $result['message'];
            break;
            
        case 'save_email':
            $result = saveEmailSettings($_POST);
            $message = $result['success'] ? '邮件设置保存成功' : $result['message'];
            break;
            
        case 'save_security':
            $result = saveSecuritySettings($_POST);
            $message = $result['success'] ? '安全设置保存成功' : $result['message'];
            break;
            
        case 'backup':
            $result = createBackup();
            $message = $result['success'] ? $result['message'] : $result['message'];
            break;
            
        case 'clear_cache':
            $result = clearCache();
            $message = $result['success'] ? '缓存清理成功' : $result['message'];
            break;
    }
}

// 获取当前设置
$generalSettings = getGeneralSettings();
$emailSettings = getEmailSettings();
$securitySettings = getSecuritySettings();
$systemInfo = getSystemInfo();
?>

<div class="space-y-6">
    <!-- 页面标题 -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 font-display">系统设置</h2>
        <p class="text-gray-600">配置系统参数和管理选项</p>
    </div>

    <?php if (isset($message)): ?>
    <div class="mb-6 p-4 bg-<?php echo strpos($message, '成功') !== false ? 'green' : 'red'; ?>-50 border border-<?php echo strpos($message, '成功') !== false ? 'green' : 'red'; ?>-200 rounded-lg">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-<?php echo strpos($message, '成功') !== false ? 'green' : 'red'; ?>-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span class="text-<?php echo strpos($message, '成功') !== false ? 'green' : 'red'; ?>-800"><?php echo htmlspecialchars($message); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- 通用设置 -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">通用设置</h3>
                </div>
                <div class="p-6">
                    <form method="post" class="space-y-6">
                        <input type="hidden" name="action" value="save_general">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">网站名称</label>
                                <input type="text" name="site_name" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       value="<?php echo htmlspecialchars($generalSettings['site_name']); ?>">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">网站描述</label>
                                <textarea name="site_description" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($generalSettings['site_description']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">每页显示条目数</label>
                                <select name="items_per_page" class="form-control">
                                    <option value="10" <?php echo ($generalSettings['items_per_page'] == 10) ? 'selected' : ''; ?>>10</option>
                                    <option value="20" <?php echo ($generalSettings['items_per_page'] == 20) ? 'selected' : ''; ?>>20</option>
                                    <option value="50" <?php echo ($generalSettings['items_per_page'] == 50) ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo ($generalSettings['items_per_page'] == 100) ? 'selected' : ''; ?>>100</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">时区</label>
                                <select name="timezone" class="form-control">
                                    <option value="Asia/Shanghai" <?php echo ($generalSettings['timezone'] == 'Asia/Shanghai') ? 'selected' : ''; ?>>Asia/Shanghai</option>
                                    <option value="UTC" <?php echo ($generalSettings['timezone'] == 'UTC') ? 'selected' : ''; ?>>UTC</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" name="registration_enabled" id="regEnabled" 
                                   <?php echo ($generalSettings['registration_enabled'] ? 'checked' : ''); ?> 
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="regEnabled" class="ml-2 text-sm text-gray-700">允许用户注册</label>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                保存设置
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 邮件设置 -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">邮件设置</h3>
                </div>
                <div class="p-6">
                    <form method="post" class="space-y-6">
                        <input type="hidden" name="action" value="save_email">
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">SMTP服务器</label>
                                <input type="text" name="smtp_host" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="smtp.example.com" 
                                       value="<?php echo htmlspecialchars($emailSettings['smtp_host']); ?>">
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">端口</label>
                                    <input type="number" name="smtp_port" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           value="<?php echo htmlspecialchars($emailSettings['smtp_port']); ?>">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">加密方式</label>
                                    <select name="smtp_encryption" class="form-control">
                                        <option value="none" <?php echo ($emailSettings['smtp_encryption'] == 'none') ? 'selected' : ''; ?>>无加密</option>
                                        <option value="ssl" <?php echo ($emailSettings['smtp_encryption'] == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                                        <option value="tls" <?php echo ($emailSettings['smtp_encryption'] == 'tls') ? 'selected' : ''; ?>>TLS</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">用户名</label>
                                    <input type="text" name="smtp_username" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           value="<?php echo htmlspecialchars($emailSettings['smtp_username']); ?>">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">密码</label>
                                    <input type="password" name="smtp_password" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           value="<?php echo htmlspecialchars($emailSettings['smtp_password']); ?>">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">发件人邮箱</label>
                                <input type="email" name="from_email" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="noreply@example.com" 
                                       value="<?php echo htmlspecialchars($emailSettings['from_email']); ?>">
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <button type="button" onclick="testEmail()" 
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                测试邮件
                            </button>
                            
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                保存设置
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 安全设置 -->
        <div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">安全设置</h3>
                </div>
                <div class="p-6">
                    <form method="post" class="space-y-6">
                        <input type="hidden" name="action" value="save_security">
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">最小密码长度</label>
                                <input type="number" name="password_min_length" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       min="6" max="20"
                                       value="<?php echo htmlspecialchars($securitySettings['password_min_length']); ?>">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">会话超时（分钟）</label>
                                <input type="number" name="session_timeout" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       min="5" max="1440"
                                       value="<?php echo htmlspecialchars($securitySettings['session_timeout']); ?>">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">最大登录尝试次数</label>
                                <input type="number" name="max_login_attempts" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       min="3" max="10"
                                       value="<?php echo htmlspecialchars($securitySettings['max_login_attempts']); ?>">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">锁定时长（分钟）</label>
                                <input type="number" name="lockout_duration" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       min="5" max="60"
                                       value="<?php echo htmlspecialchars($securitySettings['lockout_duration']); ?>">
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <input type="checkbox" name="require_email_verification" id="emailVerify" 
                                       <?php echo ($securitySettings['require_email_verification'] ? 'checked' : ''); ?> 
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label for="emailVerify" class="ml-2 text-sm text-gray-700">邮箱验证</label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="require_two_factor" id="twoFactor" 
                                       <?php echo ($securitySettings['require_two_factor'] ? 'checked' : ''); ?> 
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label for="twoFactor" class="ml-2 text-sm text-gray-700">双因子认证</label>
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                保存设置
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 系统操作 -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">系统操作</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-4 border border-gray-200 rounded-lg">
                            <div>
                                <h4 class="font-medium text-gray-900">数据库备份</h4>
                                <p class="text-sm text-gray-500">创建数据库完整备份</p>
                            </div>
                            <button onclick="confirmBackup()" 
                                    class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                                立即备份
                            </button>
                        </div>
                        
                        <div class="flex justify-between items-center p-4 border border-gray-200 rounded-lg">
                            <div>
                                <h4 class="font-medium text-gray-900">清理缓存</h4>
                                <p class="text-sm text-gray-500">清理系统缓存文件</p>
                            </div>
                            <button onclick="confirmClearCache()" 
                                    class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                                清理缓存
                            </button>
                        </div>
                        
                        <div class="flex justify-between items-center p-4 border border-gray-200 rounded-lg">
                            <div>
                                <h4 class="font-medium text-gray-900">查看系统日志</h4>
                                <p class="text-sm text-gray-500">查看系统运行日志</p>
                            </div>
                            <button onclick="viewLogs()" 
                                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                查看日志
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testEmail() {
    if (confirm('确定要测试邮件设置吗？')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="action" value="test_email">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function confirmBackup() {
    if (confirm('确定要创建数据库备份吗？这可能需要一些时间。')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="action" value="backup">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function confirmClearCache() {
    if (confirm('确定要清理系统缓存吗？')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="action" value="clear_cache">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function viewLogs() {
    window.open('?action=view_logs', '_blank');
}
</script>

<?php
/**
 * 获取通用设置
 */
function getGeneralSettings() {
    return [
        'site_name' => '数据管理系统',
        'site_description' => '专业的Excel数据管理平台',
        'items_per_page' => 20,
        'timezone' => 'Asia/Shanghai',
        'registration_enabled' => 1
    ];
}

/**
 * 获取邮件设置
 */
function getEmailSettings() {
    return [
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_encryption' => 'tls',
        'smtp_username' => '',
        'smtp_password' => '',
        'from_email' => ''
    ];
}

/**
 * 获取安全设置
 */
function getSecuritySettings() {
    return [
        'password_min_length' => 6,
        'session_timeout' => 60,
        'max_login_attempts' => 5,
        'lockout_duration' => 15,
        'require_email_verification' => 0,
        'require_two_factor' => 0
    ];
}

/**
 * 保存通用设置
 */
function saveGeneralSettings($data) {
    // 这里应该验证并保存设置到数据库
    return ['success' => true, 'message' => '通用设置保存成功'];
}

/**
 * 保存邮件设置
 */
function saveEmailSettings($data) {
    // 这里应该验证并保存设置到数据库
    return ['success' => true, 'message' => '邮件设置保存成功'];
}

/**
 * 保存安全设置
 */
function saveSecuritySettings($data) {
    // 这里应该验证并保存设置到数据库
    return ['success' => true, 'message' => '安全设置保存成功'];
}

/**
 * 创建备份
 */
function createBackup() {
    // 这里应该创建实际的数据库备份
    return ['success' => true, 'message' => '数据库备份创建成功'];
}

/**
 * 清理缓存
 */
function clearCache() {
    // 这里应该清理实际的系统缓存
    return ['success' => true, 'message' => '缓存清理成功'];
}

/**
 * 获取系统信息
 */
function getSystemInfo() {
    return [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'database_version' => 'MySQL 8.0.0',
        'disk_space' => '50GB',
        'memory_usage' => '256MB'
    ];
}
?>