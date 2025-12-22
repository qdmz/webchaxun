<?php
/**
 * PHP应用配置文件
 * 优化版本 - 包含数据库、安全、错误处理等配置
 */

// 应用基本信息
define('APP_NAME', 'Excel数据查询系统');
define('APP_VERSION', '2.0.0');
define('APP_ENV', 'production'); // production, development, testing

// 错误报告设置
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 0);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'excel_query_system');
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT', 3306);

// 文件上传配置
define('UPLOAD_MAX_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_FILE_TYPES', ['xls', 'xlsx', 'csv']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// 安全配置
define('SESSION_TIMEOUT', 3600); // 1小时
define('CSRF_TOKEN_EXPIRY', 1800); // 30分钟
define('PASSWORD_BCRYPT_COST', 12);

// 性能优化配置
define('CACHE_ENABLED', true);
define('CACHE_DIR', __DIR__ . '/../cache/');
define('CACHE_EXPIRY', 3600); // 1小时

// Excel处理配置
define('EXCEL_MAX_ROWS', 10000);
define('EXCEL_MAX_COLS', 50);
define('EXCEL_MEMORY_LIMIT', '512M');

// 搜索配置
define('SEARCH_TIMEOUT', 300); // 5分钟
define('MAX_SEARCH_RESULTS', 1000);

// 路径配置
define('BASE_PATH', dirname(__DIR__));
define('INCLUDE_PATH', __DIR__);
define('LIB_PATH', BASE_PATH . '/lib/');

// 自动创建必要的目录
$requiredDirs = [
    UPLOAD_DIR,
    CACHE_DIR,
    dirname(ini_get('error_log'))
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// 设置默认时区
date_default_timezone_set('Asia/Shanghai');

// 设置内存限制
ini_set('memory_limit', EXCEL_MEMORY_LIMIT);
ini_set('max_execution_time', SEARCH_TIMEOUT);

// 会话安全配置
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

// 防止XSS攻击
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// 应用初始化函数
function initApplication() {
    // 检查必要扩展
    $requiredExtensions = ['pdo_mysql', 'mbstring', 'gd', 'zip', 'xml'];
    $missingExtensions = [];
    
    foreach ($requiredExtensions as $ext) {
        if (!extension_loaded($ext)) {
            $missingExtensions[] = $ext;
        }
    }
    
    if (!empty($missingExtensions)) {
        throw new Exception('缺少必要的PHP扩展: ' . implode(', ', $missingExtensions));
    }
    
    // 检查目录权限
    $writableDirs = [UPLOAD_DIR, CACHE_DIR];
    foreach ($writableDirs as $dir) {
        if (!is_writable($dir)) {
            throw new Exception("目录不可写: $dir");
        }
    }
}

// 应用关闭函数
function shutdownApplication() {
    // 清理临时文件
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
}

// 注册关闭函数
register_shutdown_function('shutdownApplication');

?>