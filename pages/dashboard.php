<?php
/**
 * 仪表板页面
 */

// 获取统计数据
$stats = getDashboardStats();
$recentFiles = getRecentFiles();
$systemInfo = getSystemInfo();
?>

<div class="space-y-6">
    <!-- 欢迎区域 -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-8 text-white shadow-xl">
        <div class="max-w-3xl">
            <h2 class="text-4xl font-bold mb-4 font-display">
                欢迎回来，<?php echo htmlspecialchars($currentUser['username']); ?>！
            </h2>
            <p class="text-blue-100 text-lg mb-6">
                欢迎使用数据管理系统，这里是您的系统概览和数据分析中心。
            </p>
            <div class="flex items-center space-x-4">
                <div class="text-sm">
                    <span class="text-blue-200">上次登录：</span>
                    <span class="font-medium"><?php echo $currentUser['last_login'] ? date('Y-m-d H:i', strtotime($currentUser['last_login'])) : '首次登录'; ?></span>
                </div>
                <div class="text-sm">
                    <span class="text-blue-200">登录次数：</span>
                    <span class="font-medium"><?php echo $currentUser['login_count']; ?> 次</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 统计数据 -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">文件总数</p>
                    <p class="text-2xl font-bold text-gray-900 font-display"><?php echo $stats['total_files']; ?></p>
                    <p class="text-xs text-green-600 mt-1">+12% 较上月</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">用户数量</p>
                    <p class="text-2xl font-bold text-gray-900 font-display"><?php echo $stats['total_users']; ?></p>
                    <p class="text-xs text-green-600 mt-1">+5% 较上月</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">数据记录</p>
                    <p class="text-2xl font-bold text-gray-900 font-display"><?php echo number_format($stats['total_records']); ?></p>
                    <p class="text-xs text-green-600 mt-1">+23% 较上月</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">系统状态</p>
                    <p class="text-2xl font-bold text-green-600 font-display">正常</p>
                    <p class="text-xs text-green-600 mt-1">99.9% 可用性</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 最近文件 -->
        <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 font-display">最近文件</h3>
                <a href="?action=files" class="text-sm text-blue-600 hover:text-blue-500 font-medium">查看全部</a>
            </div>
            
            <div class="space-y-3">
                <?php if (empty($recentFiles)): ?>
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-gray-500">暂无文件</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentFiles as $file): ?>
                        <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition-colors">
                            <div class="flex items-center flex-1 min-w-0">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($file['original_name']); ?></p>
                                    <p class="text-xs text-gray-500">
                                        <?php echo date('m-d H:i', strtotime($file['upload_time'])); ?> · 
                                        <?php echo formatFileSize($file['file_size']); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 ml-4">
                                <button class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                </button>
                                <button class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 系统信息 -->
        <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 font-display mb-4">系统信息</h3>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">PHP版本</span>
                    <span class="text-sm font-medium text-gray-900"><?php echo $systemInfo['php_version']; ?></span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">MySQL版本</span>
                    <span class="text-sm font-medium text-gray-900"><?php echo $systemInfo['mysql_version']; ?></span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">服务器软件</span>
                    <span class="text-sm font-medium text-gray-900"><?php echo $systemInfo['server_software']; ?></span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">系统版本</span>
                    <span class="text-sm font-medium text-gray-900">v1.0.0</span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">安装时间</span>
                    <span class="text-sm font-medium text-gray-900"><?php echo $systemInfo['install_time']; ?></span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">上传目录</span>
                    <span class="text-sm font-medium <?php echo $systemInfo['upload_writable'] ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo $systemInfo['upload_writable'] ? '可写' : '不可写'; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 快速操作 -->
    <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900 font-display mb-6">快速操作</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="?action=files" class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-all group">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900">上传文件</h4>
                        <p class="text-sm text-gray-500">支持Excel、CSV格式</p>
                    </div>
                </div>
                <span class="text-xs text-blue-600 bg-blue-100 px-2 py-1 rounded">推荐</span>
            </a>
            
            <a href="?action=query" class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-green-300 hover:bg-green-50 transition-all group">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900">数据查询</h4>
                        <p class="text-sm text-gray-500">搜索和筛选数据</p>
                    </div>
                </div>
                <span class="text-xs text-green-600 bg-green-100 px-2 py-1 rounded">实时</span>
            </a>
            
            <?php if ($currentUser['role'] === 'admin'): ?>
            <a href="?action=users" class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-purple-300 hover:bg-purple-50 transition-all group">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900">用户管理</h4>
                        <p class="text-sm text-gray-500">管理用户权限</p>
                    </div>
                </div>
                <span class="text-xs text-purple-600 bg-purple-100 px-2 py-1 rounded">管理</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
/**
 * 获取仪表板统计数据
 */
function getDashboardStats() {
    $conn = getDatabaseConnection();
    
    $stats = [
        'total_files' => 0,
        'total_users' => 0,
        'total_records' => 0
    ];
    
    // 获取文件总数
    $result = $conn->query("SELECT COUNT(*) as count FROM files WHERE status = 'active'");
    if ($row = $result->fetch_assoc()) {
        $stats['total_files'] = $row['count'];
    }
    
    // 获取用户总数
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
    if ($row = $result->fetch_assoc()) {
        $stats['total_users'] = $row['count'];
    }
    
    // 获取数据记录总数
    $result = $conn->query("SELECT COUNT(*) as count FROM data_records");
    if ($row = $result->fetch_assoc()) {
        $stats['total_records'] = $row['count'];
    }
    
    $conn->close();
    return $stats;
}

/**
 * 获取最近文件
 */
function getRecentFiles($limit = 5) {
    $conn = getDatabaseConnection();
    
    $files = [];
    $stmt = $conn->prepare("
        SELECT f.*, u.username 
        FROM files f 
        LEFT JOIN users u ON f.uploader_id = u.id 
        WHERE f.status = 'active' 
        ORDER BY f.upload_time DESC 
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $files[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    return $files;
}

/**
 * 获取系统信息
 */
function getSystemInfo() {
    $installTime = '未知';
    if (file_exists('../config/install.lock')) {
        $installData = json_decode(file_get_contents('../config/install.lock'), true);
        $installTime = $installData['installed_at'] ?? '未知';
    }
    
    // 获取MySQL版本
    $mysqlVersion = '未知';
    $conn = getDatabaseConnection();
    $result = $conn->query("SELECT VERSION() as version");
    if ($row = $result->fetch_assoc()) {
        $mysqlVersion = $row['version'];
    }
    $conn->close();
    
    return [
        'php_version' => PHP_VERSION,
        'mysql_version' => $mysqlVersion,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'install_time' => $installTime,
        'upload_writable' => is_writable('../uploads')
    ];
}

/**
 * 格式化文件大小
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>