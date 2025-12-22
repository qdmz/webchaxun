<?php
/**
 * 性能优化配置
 * 包含缓存、压缩、数据库优化等性能提升措施
 */

class Performance {
    
    /**
     * 启用输出压缩
     */
    public static function enableOutputCompression() {
        if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
            ob_start('ob_gzhandler');
        }
    }
    
    /**
     * 设置浏览器缓存头
     */
    public static function setBrowserCacheHeaders($maxAge = 3600) {
        if (APP_ENV === 'production') {
            header('Cache-Control: public, max-age=' . $maxAge);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
            header('Pragma: cache');
        } else {
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }
    
    /**
     * 数据库查询优化
     */
    public static function optimizeDatabaseQueries() {
        // 设置数据库连接参数
        if (isset($GLOBALS['db'])) {
            $GLOBALS['db']->query("SET SESSION query_cache_type = 1");
            $GLOBALS['db']->query("SET SESSION query_cache_size = 16777216"); // 16MB
            $GLOBALS['db']->query("SET SESSION sort_buffer_size = 2097152"); // 2MB
            $GLOBALS['db']->query("SET SESSION read_buffer_size = 1048576"); // 1MB
        }
    }
    
    /**
     * 启用OPcache（如果可用）
     */
    public static function enableOpcache() {
        if (extension_loaded('Zend OPcache') && ini_get('opcache.enable')) {
            // OPcache已启用，无需额外配置
            return true;
        }
        
        // 建议在php.ini中启用OPcache
        return false;
    }
    
    /**
     * 静态资源优化
     */
    public static function optimizeStaticResources() {
        // CSS/JS压缩（在生产环境中）
        if (APP_ENV === 'production') {
            // 可以集成minify等工具
            // 这里只是示例，实际项目中需要具体实现
        }
    }
    
    /**
     * 图片优化
     */
    public static function optimizeImages() {
        // 可以集成图片压缩工具
        // 这里只是示例
    }
    
    /**
     * 监控性能指标
     */
    public static function monitorPerformance() {
        static $startTime = null;
        
        if ($startTime === null) {
            $startTime = microtime(true);
            return;
        }
        
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2); // 毫秒
        
        $memoryUsage = memory_get_peak_usage(true);
        $memoryUsageMB = round($memoryUsage / 1024 / 1024, 2);
        
        // 记录性能日志
        if ($executionTime > 1000) { // 超过1秒记录慢请求
            $logFile = BASE_PATH . '/logs/performance.log';
            $timestamp = date('Y-m-d H:i:s');
            $url = $_SERVER['REQUEST_URI'] ?? 'unknown';
            
            $logMessage = sprintf(
                "[%s] [SLOW] URL: %s, Time: %sms, Memory: %sMB\n",
                $timestamp,
                $url,
                $executionTime,
                $memoryUsageMB
            );
            
            if (!is_dir(dirname($logFile))) {
                mkdir(dirname($logFile), 0755, true);
            }
            
            file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        }
        
        // 开发环境下显示性能信息
        if (APP_ENV === 'development') {
            echo "<!-- Performance: {$executionTime}ms, Memory: {$memoryUsageMB}MB -->";
        }
    }
    
    /**
     * 数据库连接池优化
     */
    public static function optimizeDatabasePool() {
        // 设置持久连接参数
        if (isset($GLOBALS['db'])) {
            // 这里可以配置连接池参数
            // 实际项目中可能需要使用专门的连接池工具
        }
    }
    
    /**
     * 缓存策略优化
     */
    public static function optimizeCacheStrategy() {
        // 设置缓存策略
        if (CACHE_ENABLED) {
            // 可以根据业务需求设置不同的缓存时间
            // 例如：频繁访问的数据缓存时间较短，不常变化的数据缓存时间较长
        }
    }
    
    /**
     * 会话存储优化
     */
    public static function optimizeSessionStorage() {
        // 设置会话存储方式
        if (APP_ENV === 'production') {
            // 生产环境建议使用Redis或Memcached存储会话
            // ini_set('session.save_handler', 'redis');
            // ini_set('session.save_path', 'tcp://127.0.0.1:6379');
        }
    }
    
    /**
     * 文件系统优化
     */
    public static function optimizeFileSystem() {
        // 确保上传目录和缓存目录有适当的权限
        $dirs = [UPLOAD_DIR, CACHE_DIR, BASE_PATH . '/logs/'];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // 设置目录权限
            chmod($dir, 0755);
        }
    }
    
    /**
     * 网络优化
     */
    public static function optimizeNetwork() {
        // 设置HTTP连接参数
        if (function_exists('curl_version')) {
            // 可以设置cURL参数优化网络请求
        }
    }
    
    /**
     * 应用启动优化
     */
    public static function optimizeApplicationStartup() {
        // 预加载常用类
        if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
            // PHP 7.4+ 支持预加载
            // opcache_compile_file('path/to/class.php');
        }
    }
    
    /**
     * 获取性能统计信息
     */
    public static function getPerformanceStats() {
        $stats = [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)),
            'included_files' => count(get_included_files()),
            'database_queries' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0
        ];
        
        // 获取数据库统计信息
        if (isset($GLOBALS['db']) && method_exists($GLOBALS['db'], 'getStats')) {
            $dbStats = $GLOBALS['db']->getStats();
            $stats['database_queries'] = $dbStats['query_count'] ?? 0;
            $stats['database_time'] = $dbStats['total_time'] ?? 0;
        }
        
        return $stats;
    }
    
    /**
     * 生成性能报告
     */
    public static function generatePerformanceReport() {
        $stats = self::getPerformanceStats();
        
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'memory_usage_mb' => round($stats['memory_usage'] / 1024 / 1024, 2),
            'memory_peak_mb' => round($stats['memory_peak'] / 1024 / 1024, 2),
            'execution_time_ms' => round($stats['execution_time'] * 1000, 2),
            'included_files' => $stats['included_files'],
            'database_queries' => $stats['database_queries'],
            'database_time_ms' => round(($stats['database_time'] ?? 0) * 1000, 2)
        ];
        
        return $report;
    }
}

// 自动启用性能优化
Performance::enableOutputCompression();
Performance::setBrowserCacheHeaders();
Performance::optimizeFileSystem();

// 注册性能监控
register_shutdown_function([Performance::class, 'monitorPerformance']);

?>