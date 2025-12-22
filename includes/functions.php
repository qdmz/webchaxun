<?php
/**
 * 通用函数库
 * 包含错误处理、安全验证、工具函数等
 */

/**
 * 错误处理函数
 */
function handleError($errno, $errstr, $errfile, $errline) {
    // 忽略@抑制的错误
    if (error_reporting() === 0) {
        return;
    }
    
    $errorTypes = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE_ERROR',
        E_CORE_WARNING => 'CORE_WARNING',
        E_COMPILE_ERROR => 'COMPILE_ERROR',
        E_COMPILE_WARNING => 'COMPILE_WARNING',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE',
        E_STRICT => 'STRICT',
        E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER_DEPRECATED'
    ];
    
    $errorType = isset($errorTypes[$errno]) ? $errorTypes[$errno] : 'UNKNOWN';
    
    $logMessage = sprintf(
        "[%s] [%s] %s in %s on line %d\n",
        date('Y-m-d H:i:s'),
        $errorType,
        $errstr,
        $errfile,
        $errline
    );
    
    // 写入错误日志
    error_log($logMessage, 3, BASE_PATH . '/logs/application_errors.log');
    
    // 开发环境显示详细错误
    if (APP_ENV === 'development') {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<strong>{$errorType}:</strong> {$errstr}<br>";
        echo "<small>File: {$errfile} (Line: {$errline})</small>";
        echo "</div>";
    }
    
    // 严重错误时停止执行
    if ($errno === E_ERROR || $errno === E_USER_ERROR) {
        if (APP_ENV === 'production') {
            // 生产环境显示用户友好错误页面
            http_response_code(500);
            include BASE_PATH . '/templates/error_500.html';
        }
        exit(1);
    }
    
    return true;
}

/**
 * 异常处理函数
 */
function handleException($exception) {
    $logMessage = sprintf(
        "[%s] [EXCEPTION] %s in %s on line %d\nStack trace:\n%s\n",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    
    error_log($logMessage, 3, BASE_PATH . '/logs/exceptions.log');
    
    if (APP_ENV === 'development') {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<strong>EXCEPTION:</strong> {$exception->getMessage()}<br>";
        echo "<small>File: {$exception->getFile()} (Line: {$exception->getLine()})</small><br>";
        echo "<pre>{$exception->getTraceAsString()}</pre>";
        echo "</div>";
    } else {
        http_response_code(500);
        include BASE_PATH . '/templates/error_500.html';
    }
    
    exit(1);
}

/**
 * 安全相关函数
 */

// HTML转义
function escape($data) {
    if (is_array($data)) {
        return array_map('escape', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// 过滤输入数据
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    
    // 去除多余空格
    $input = trim($input);
    
    // 防止SQL注入
    $input = stripslashes($input);
    
    // 防止XSS攻击
    $input = escape($input);
    
    return $input;
}

// 验证邮箱格式
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// 验证密码强度
function isStrongPassword($password) {
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

// 生成CSRF令牌
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }
    
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_tokens'][$token] = time() + CSRF_TOKEN_EXPIRY;
    
    // 清理过期的令牌
    foreach ($_SESSION['csrf_tokens'] as $storedToken => $expiry) {
        if ($expiry < time()) {
            unset($_SESSION['csrf_tokens'][$storedToken]);
        }
    }
    
    return $token;
}

// 验证CSRF令牌
function validateCsrfToken($token) {
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
 * 文件处理函数
 */

// 安全的文件上传
function safeUploadFile($file, $allowedTypes = ALLOWED_FILE_TYPES, $maxSize = UPLOAD_MAX_SIZE) {
    $errors = [];
    
    // 检查上传错误
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => '文件大小超过服务器限制',
            UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
            UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败',
            UPLOAD_ERR_EXTENSION => 'PHP扩展阻止了文件上传'
        ];
        
        return [
            'success' => false,
            'error' => $uploadErrors[$file['error']] ?? '未知上传错误'
        ];
    }
    
    // 检查文件大小
    if ($file['size'] > $maxSize) {
        return [
            'success' => false,
            'error' => '文件大小超过限制'
        ];
    }
    
    // 检查文件类型
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedTypes)) {
        return [
            'success' => false,
            'error' => '不支持的文件类型'
        ];
    }
    
    // 验证文件内容（防止伪装文件）
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimeTypes = [
        'xls' => ['application/vnd.ms-excel', 'application/excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'csv' => ['text/csv', 'text/plain']
    ];
    
    if (!isset($allowedMimeTypes[$fileExtension]) || 
        !in_array($mimeType, $allowedMimeTypes[$fileExtension])) {
        return [
            'success' => false,
            'error' => '文件内容与类型不匹配'
        ];
    }
    
    // 生成安全的文件名
    $safeFilename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
    $destination = UPLOAD_DIR . $safeFilename;
    
    // 移动文件
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => false,
            'error' => '文件移动失败'
        ];
    }
    
    return [
        'success' => true,
        'filename' => $safeFilename,
        'original_name' => $file['name'],
        'file_path' => $destination,
        'file_size' => $file['size']
    ];
}

/**
 * 日志记录函数
 */

// 记录用户操作日志
function logUserAction($userId, $action, $details = '') {
    $logFile = BASE_PATH . '/logs/user_actions.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $logMessage = sprintf(
        "[%s] [User:%d] [IP:%s] %s - %s\n",
        $timestamp,
        $userId,
        $ip,
        $action,
        $details
    );
    
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// 记录系统事件
function logSystemEvent($event, $level = 'INFO', $details = '') {
    $logFile = BASE_PATH . '/logs/system_events.log';
    $timestamp = date('Y-m-d H:i:s');
    
    $logMessage = sprintf(
        "[%s] [%s] %s - %s\n",
        $timestamp,
        $level,
        $event,
        $details
    );
    
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * 缓存函数
 */

// 获取缓存
function getCache($key, $default = null) {
    if (!CACHE_ENABLED) {
        return $default;
    }
    
    $cacheFile = CACHE_DIR . md5($key) . '.cache';
    
    if (!file_exists($cacheFile)) {
        return $default;
    }
    
    $data = file_get_contents($cacheFile);
    $cache = unserialize($data);
    
    if ($cache['expiry'] < time()) {
        unlink($cacheFile);
        return $default;
    }
    
    return $cache['data'];
}

// 设置缓存
function setCache($key, $data, $expiry = CACHE_EXPIRY) {
    if (!CACHE_ENABLED) {
        return false;
    }
    
    $cacheFile = CACHE_DIR . md5($key) . '.cache';
    $cache = [
        'data' => $data,
        'expiry' => time() + $expiry,
        'created' => time()
    ];
    
    if (!is_dir(dirname($cacheFile))) {
        mkdir(dirname($cacheFile), 0755, true);
    }
    
    return file_put_contents($cacheFile, serialize($cache), LOCK_EX) !== false;
}

// 删除缓存
function deleteCache($key) {
    $cacheFile = CACHE_DIR . md5($key) . '.cache';
    if (file_exists($cacheFile)) {
        return unlink($cacheFile);
    }
    return true;
}

/**
 * 工具函数
 */

// 格式化文件大小
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

// 获取客户端IP
function getClientIP() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

// 重定向函数
function redirect($url, $permanent = false) {
    if ($permanent) {
        header('HTTP/1.1 301 Moved Permanently');
    }
    header('Location: ' . $url);
    exit;
}

// 设置错误和异常处理器
set_error_handler('handleError');
set_exception_handler('handleException');

?>