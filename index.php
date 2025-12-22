<?php
/**
 * 数据管理系统 - 主页
 * 版本: 1.0.0
 */

// 检查是否已安装
if (!file_exists('config/config.php')) {
    header('Location: install/index.php');
    exit;
}

// 加载配置
require_once 'config/config.php';

// 检查安装锁文件
if (!file_exists('config/install.lock')) {
    header('Location: install/index.php');
    exit;
}

// 加载核心文件
require_once 'includes/functions.php';
require_once 'includes/database.php';
require_once 'includes/security.php';
require_once 'includes/user-auth.php';

// 初始化会话
initSession();

// 获取当前用户
$currentUser = getCurrentUser();

// 如果用户未登录且不是访问登录页面，重定向到登录页
if (!$currentUser && !isset($_GET['action']) || $_GET['action'] !== 'login') {
    header('Location: ?action=login');
    exit;
}

// 处理路由
$action = $_GET['action'] ?? 'dashboard';
$page = $_GET['page'] ?? 'home';

// 设置页面标题和描述
$pageInfo = [
    'dashboard' => ['title' => '系统概览', 'description' => '系统运行状态和数据统计'],
    'files' => ['title' => '文件管理', 'description' => '上传、管理和分析Excel文件'],
    'query' => ['title' => '数据查询', 'description' => '搜索和筛选数据记录'],
    'users' => ['title' => '用户管理', 'description' => '管理系统用户和权限'],
    'settings' => ['title' => '系统设置', 'description' => '配置系统参数和选项'],
    'login' => ['title' => '用户登录', 'description' => '登录数据管理系统']
];

$title = $pageInfo[$action]['title'] ?? '数据管理系统';
$description = $pageInfo[$action]['description'] ?? '';

/**
 * 渲染页面头部
 */
function renderHeader($title, $user = null) {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?> - 数据管理系统</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Inter', sans-serif; }
            .font-display { font-family: 'IBM Plex Sans', sans-serif; }
            .sidebar-link:hover { transform: translateX(4px); }
            .sidebar-link { transition: all 0.3s ease; }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .fade-in { animation: fadeIn 0.6s ease-out; }
        </style>
    </head>
    <body class="bg-gray-50">
        <div class="min-h-screen flex flex-col">
    <?php
}

/**
 * 渲染导航栏
 */
function renderNavbar($user) {
    ?>
    <!-- 导航栏 -->
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900 font-display">数据管理系统</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if ($user): ?>
                        <span class="text-sm text-gray-500">欢迎, <?php echo htmlspecialchars($user['username']); ?></span>
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full flex items-center justify-center">
                            <span class="text-white text-sm font-medium"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></span>
                        </div>
                        <a href="?action=logout" class="text-gray-500 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-100 transition-colors" title="退出">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <?php
}

/**
 * 渲染侧边栏
 */
function renderSidebar($user) {
    if (!$user) return;
    
    $currentAction = $_GET['action'] ?? 'dashboard';
    
    $menuItems = [
        'dashboard' => [
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            'label' => '系统概览',
            'color' => 'blue'
        ],
        'files' => [
            'icon' => 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12',
            'label' => '文件管理',
            'color' => 'green'
        ],
        'query' => [
            'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
            'label' => '数据查询',
            'color' => 'purple'
        ]
    ];
    
    // 如果是管理员，添加管理菜单
    if ($user['role'] === 'admin') {
        $menuItems['users'] = [
            'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z',
            'label' => '用户管理',
            'color' => 'orange'
        ];
        $menuItems['settings'] = [
            'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
            'label' => '系统设置',
            'color' => 'gray'
        ];
    }
    
    ?>
    <!-- 侧边栏 -->
    <aside class="w-64 bg-white shadow-md border-r border-gray-200 min-h-screen">
        <div class="p-6">
            <nav class="space-y-2">
                <?php foreach ($menuItems as $action => $item): ?>
                    <a href="?action=<?php echo $action; ?>" 
                       class="sidebar-link flex items-center p-3 rounded-lg <?php echo $currentAction === $action ? 'bg-' . $item['color'] . '-100 text-' . $item['color'] . '-700' : 'text-gray-600 hover:bg-gray-100'; ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $item['icon']; ?>"></path>
                        </svg>
                        <span class="font-medium"><?php echo $item['label']; ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </aside>
    <?php
}

/**
 * 渲染页面内容
 */
function renderContent($action, $user) {
    switch ($action) {
        case 'dashboard':
            require 'pages/dashboard.php';
            break;
        case 'files':
            require 'pages/files.php';
            break;
        case 'query':
            require 'pages/query.php';
            break;
        case 'users':
            if ($user && $user['role'] === 'admin') {
                require 'pages/users.php';
            } else {
                echo '<div class="flex items-center justify-center h-64"><p class="text-gray-500">权限不足</p></div>';
            }
            break;
        case 'settings':
            if ($user && $user['role'] === 'admin') {
                require 'pages/settings.php';
            } else {
                echo '<div class="flex items-center justify-center h-64"><p class="text-gray-500">权限不足</p></div>';
            }
            break;
        case 'login':
            require 'pages/login.php';
            break;
        case 'logout':
            logout();
            header('Location: ?action=login');
            exit;
        default:
            require 'pages/404.php';
            break;
    }
}

/**
 * 渲染页面底部
 */
function renderFooter() {
    ?>
        </div>
        
        <!-- 页脚 -->
        <footer class="bg-white border-t border-gray-200 mt-auto">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-center md:text-left text-gray-600 text-sm mb-4 md:mb-0">
                        <p>数据管理系统 © 2024 - 基于 PHP + MySQL 构建</p>
                        <p class="mt-1">专业的Excel数据管理解决方案</p>
                    </div>
                    <div class="flex space-x-6">
                        <a href="#" class="text-gray-400 hover:text-gray-600">帮助文档</a>
                        <a href="#" class="text-gray-400 hover:text-gray-600">技术支持</a>
                        <a href="#" class="text-gray-400 hover:text-gray-600">关于我们</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    </body>
    </html>
    <?php
}

// 主要渲染逻辑
if ($action === 'login') {
    // 登录页面特殊布局
    renderHeader($title, null);
    ?>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-purple-50">
        <div class="w-full max-w-md">
            <?php renderContent($action, null); ?>
        </div>
    </div>
    <?php
    renderFooter();
} else {
    // 正常布局
    renderHeader($title, $currentUser);
    renderNavbar($currentUser);
    ?>
    <div class="flex flex-1">
        <?php renderSidebar($currentUser); ?>
        <main class="flex-1 p-6 ml-64">
            <div class="fade-in">
                <?php renderContent($action, $currentUser); ?>
            </div>
        </main>
    </div>
    <?php
    renderFooter();
}
?>