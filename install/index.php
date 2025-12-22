<?php
/**
 * 数据管理系统 - 在线安装程序
 * 版本: 1.0.0
 * 作者: CloudBase AI ToolKit
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 安装步骤
define('INSTALL_STEPS', [
    1 => '环境检查',
    2 => '数据库配置',
    3 => '管理员设置',
    4 => '安装进度',
    5 => '安装完成'
]);

// 获取当前安装步骤
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step < 1) $step = 1;
if ($step > 5) $step = 5;

// 安装锁文件
$lockFile = '../config/install.lock';
$configFile = '../config/config.php';

// 检查是否已安装
if (file_exists($lockFile) && $step < 5) {
    $installedInfo = json_decode(file_get_contents($lockFile), true);
    $step = 5;
}

/**
 * 页面头部
 */
function renderHeader($title = '数据管理系统安装向导') {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title; ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Inter', sans-serif; }
            .step-active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
            .step-completed { background: #10b981; }
            .step-pending { background: #e5e7eb; color: #6b7280; }
        </style>
    </head>
    <body class="bg-gray-50 min-h-screen">
        <div class="min-h-screen flex flex-col">
            <!-- 头部 -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-16">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h1 class="text-xl font-bold text-gray-900">数据管理系统安装向导</h1>
                        </div>
                        <div class="text-sm text-gray-500">
                            版本 1.0.0
                        </div>
                    </div>
                </div>
            </header>

            <!-- 步骤导航 -->
            <div class="bg-white shadow-sm">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div class="flex items-center justify-between">
                        <?php
                        foreach (INSTALL_STEPS as $stepNum => $stepName) {
                            $isActive = $stepNum == $step;
                            $isCompleted = $stepNum < $step;
                            $statusClass = $isActive ? 'step-active' : ($isCompleted ? 'step-completed' : 'step-pending');
                            ?>
                            <div class="flex items-center">
                                <div class="<?php echo $statusClass; ?> text-white rounded-full w-10 h-10 flex items-center justify-center text-sm font-medium">
                                    <?php if ($isCompleted): ?>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <?php echo $stepNum; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $stepName; ?></div>
                                </div>
                                <?php if ($stepNum < 5): ?>
                                    <div class="w-12 h-0.5 bg-gray-300 ml-6"></div>
                                <?php endif; ?>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- 主要内容 -->
            <main class="flex-grow">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?php
}

/**
 * 页面底部
 */
function renderFooter() {
    ?>
                </div>
            </main>

            <!-- 页脚 -->
            <footer class="bg-white border-t border-gray-200">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div class="text-center text-sm text-gray-500">
                        <p>数据管理系统 © 2024 - 基于 PHP + MySQL 构建</p>
                        <p class="mt-1">由 CloudBase AI ToolKit 提供</p>
                    </div>
                </div>
            </footer>
        </div>
    </body>
    </html>
    <?php
}

/**
 * 检查PHP环境
 */
function checkEnvironment() {
    $requirements = [
        'php_version' => [
            'name' => 'PHP版本',
            'required' => '>= 7.4',
            'current' => PHP_VERSION,
            'status' => version_compare(PHP_VERSION, '7.4.0', '>=')
        ],
        'mysql' => [
            'name' => 'MySQL支持',
            'required' => '已安装',
            'current' => extension_loaded('mysqli') ? '已安装' : '未安装',
            'status' => extension_loaded('mysqli')
        ],
        'pdo' => [
            'name' => 'PDO支持',
            'required' => '已安装',
            'current' => extension_loaded('pdo_mysql') ? '已安装' : '未安装',
            'status' => extension_loaded('pdo_mysql')
        ],
        'fileinfo' => [
            'name' => 'Fileinfo支持',
            'required' => '已安装',
            'current' => extension_loaded('fileinfo') ? '已安装' : '未安装',
            'status' => extension_loaded('fileinfo')
        ],
        'curl' => [
            'name' => 'cURL支持',
            'required' => '已安装',
            'current' => extension_loaded('curl') ? '已安装' : '未安装',
            'status' => extension_loaded('curl')
        ],
        'session' => [
            'name' => 'Session支持',
            'required' => '已启用',
            'current' => function_exists('session_start') ? '已启用' : '未启用',
            'status' => function_exists('session_start')
        ],
        'writable_config' => [
            'name' => 'config目录可写',
            'required' => '可写',
            'current' => is_writable('../config') ? '可写' : '不可写',
            'status' => is_writable('../config')
        ],
        'writable_uploads' => [
            'name' => 'uploads目录可写',
            'required' => '可写',
            'current' => is_writable('../uploads') ? '可写' : '不可写',
            'status' => is_writable('../uploads')
        ]
    ];

    return $requirements;
}

/**
 * 测试数据库连接
 */
function testDatabaseConnection($host, $username, $password, $database, $port = 3306) {
    try {
        $conn = new mysqli($host, $username, $password, $database, $port);
        if ($conn->connect_error) {
            return false;
        }
        
        // 测试查询
        $result = $conn->query("SELECT VERSION() as version");
        $row = $result->fetch_assoc();
        $conn->close();
        
        return [
            'success' => true,
            'version' => $row['version']
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * 创建数据库表
 */
function createTables($conn) {
    $sql = "
        CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `email` varchar(100) NOT NULL,
            `password` varchar(255) NOT NULL,
            `role` enum('admin','user') DEFAULT 'user',
            `status` enum('active','inactive') DEFAULT 'active',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `files` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `filename` varchar(255) NOT NULL,
            `original_name` varchar(255) NOT NULL,
            `file_type` varchar(50) NOT NULL,
            `file_size` int(11) NOT NULL,
            `file_path` varchar(255) NOT NULL,
            `uploader_id` int(11) NOT NULL,
            `upload_time` timestamp DEFAULT CURRENT_TIMESTAMP,
            `status` enum('active','deleted') DEFAULT 'active',
            PRIMARY KEY (`id`),
            KEY `uploader_id` (`uploader_id`),
            FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `data_records` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `file_id` int(11) NOT NULL,
            `sheet_name` varchar(255) NOT NULL,
            `row_number` int(11) NOT NULL,
            `data` json NOT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `file_id` (`file_id`),
            FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `system_settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `setting_key` varchar(100) NOT NULL,
            `setting_value` text,
            `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
            `description` varchar(255),
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `setting_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    // 执行SQL语句
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            if (!$conn->query($statement)) {
                throw new Exception("创建表失败: " . $conn->error);
            }
        }
    }

    return true;
}

/**
 * 插入默认系统设置
 */
function insertDefaultSettings($conn) {
    $settings = [
        'site_name' => '数据管理系统',
        'site_description' => '专业的Excel数据管理平台',
        'max_file_size' => '10485760', // 10MB
        'allowed_file_types' => '["xlsx","xls","csv"]',
        'registration_enabled' => '1',
        'admin_email' => '',
        'upload_path' => '../uploads/',
        'session_timeout' => '3600',
        'items_per_page' => '20'
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
    
    foreach ($settings as $key => $value) {
        $type = gettype($value);
        if ($type == 'boolean') $type = 'boolean';
        elseif ($type == 'integer') $type = 'number';
        else $type = 'string';
        
        $description = getDescriptionForKey($key);
        $stmt->bind_param("ssss", $key, $value, $type, $description);
        $stmt->execute();
    }

    return true;
}

/**
 * 获取设置项描述
 */
function getDescriptionForKey($key) {
    $descriptions = [
        'site_name' => '网站名称',
        'site_description' => '网站描述',
        'max_file_size' => '最大文件大小（字节）',
        'allowed_file_types' => '允许的文件类型（JSON格式）',
        'registration_enabled' => '是否允许用户注册',
        'admin_email' => '管理员邮箱',
        'upload_path' => '文件上传路径',
        'session_timeout' => '会话超时时间（秒）',
        'items_per_page' => '每页显示条目数'
    ];
    
    return $descriptions[$key] ?? '';
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 2) {
        // 数据库配置
        $_SESSION['db_config'] = $_POST;
        header('Location: index.php?step=3');
        exit;
    } elseif ($step == 3) {
        // 管理员设置
        $_SESSION['admin_config'] = $_POST;
        header('Location: index.php?step=4');
        exit;
    } elseif ($step == 4) {
        // 执行安装
        $dbConfig = $_SESSION['db_config'];
        $adminConfig = $_SESSION['admin_config'];
        
        try {
            // 连接数据库
            $conn = new mysqli(
                $dbConfig['db_host'], 
                $dbConfig['db_username'], 
                $dbConfig['db_password'], 
                $dbConfig['db_name'], 
                $dbConfig['db_port']
            );
            
            if ($conn->connect_error) {
                throw new Exception("数据库连接失败: " . $conn->connect_error);
            }
            
            // 创建表
            createTables($conn);
            
            // 插入默认设置
            insertDefaultSettings($conn);
            
            // 创建管理员账户
            $hashedPassword = password_hash($adminConfig['admin_password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'admin', 'active')");
            $stmt->bind_param("sss", $adminConfig['admin_username'], $adminConfig['admin_email'], $hashedPassword);
            $stmt->execute();
            
            // 创建配置文件
            $configContent = "<?php\n";
            $configContent .= "// 数据库配置\n";
            $configContent .= "\$config['database']['host'] = '" . $dbConfig['db_host'] . "';\n";
            $configContent .= "\$config['database']['port'] = '" . $dbConfig['db_port'] . "';\n";
            $configContent .= "\$config['database']['username'] = '" . $dbConfig['db_username'] . "';\n";
            $configContent .= "\$config['database']['password'] = '" . $dbConfig['db_password'] . "';\n";
            $configContent .= "\$config['database']['name'] = '" . $dbConfig['db_name'] . "';\n";
            $configContent .= "\$config['database']['charset'] = 'utf8mb4';\n\n";
            $configContent .= "// 系统配置\n";
            $configContent .= "\$config['system']['timezone'] = 'Asia/Shanghai';\n";
            $configContent .= "\$config['system']['session_name'] = 'data_management_session';\n";
            $configContent .= "\$config['system']['upload_path'] = '../uploads/';\n";
            $configContent .= "\$config['system']['max_file_size'] = 10485760;\n";
            $configContent .= "\$config['system']['allowed_extensions'] = ['xlsx', 'xls', 'csv'];\n";
            
            if (!file_put_contents($configFile, $configContent)) {
                throw new Exception("无法创建配置文件");
            }
            
            // 创建安装锁文件
            $installInfo = [
                'installed_at' => date('Y-m-d H:i:s'),
                'version' => '1.0.0',
                'admin_username' => $adminConfig['admin_username']
            ];
            file_put_contents($lockFile, json_encode($installInfo));
            
            $conn->close();
            
            header('Location: index.php?step=5');
            exit;
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// 渲染页面
renderHeader();

switch ($step) {
    case 1:
        // 环境检查
        $requirements = checkEnvironment();
        $allPassed = true;
        foreach ($requirements as $req) {
            if (!$req['status']) {
                $allPassed = false;
                break;
            }
        }
        ?>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">环境检查</h2>
            
            <div class="space-y-4">
                <?php foreach ($requirements as $key => $req): ?>
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div>
                        <div class="font-medium text-gray-900"><?php echo $req['name']; ?></div>
                        <div class="text-sm text-gray-500">要求: <?php echo $req['required']; ?></div>
                    </div>
                    <div class="flex items-center">
                        <div class="text-sm mr-3">当前: <?php echo $req['current']; ?></div>
                        <?php if ($req['status']): ?>
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        <?php else: ?>
                            <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (!$allPassed): ?>
            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.728-.833-2.498 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <span class="text-yellow-800">部分环境要求未满足，请先解决这些问题再继续安装。</span>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="mt-6 flex justify-end">
                <?php if ($allPassed): ?>
                    <a href="index.php?step=2" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        下一步
                    </a>
                <?php else: ?>
                    <button disabled class="bg-gray-300 text-gray-500 px-6 py-2 rounded-lg cursor-not-allowed">
                        环境检查未通过
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
        break;
        
    case 2:
        // 数据库配置
        $dbConfig = $_SESSION['db_config'] ?? [];
        ?>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">数据库配置</h2>
            
            <form method="post" action="index.php?step=2" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">数据库主机</label>
                        <input type="text" name="db_host" value="<?php echo $dbConfig['db_host'] ?? 'localhost'; ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="localhost" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">端口</label>
                        <input type="number" name="db_port" value="<?php echo $dbConfig['db_port'] ?? '3306'; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="3306" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">数据库名</label>
                        <input type="text" name="db_name" value="<?php echo $dbConfig['db_name'] ?? ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="data_management" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">用户名</label>
                        <input type="text" name="db_username" value="<?php echo $dbConfig['db_username'] ?? ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="root" required>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">密码</label>
                    <input type="password" name="db_password" value="<?php echo $dbConfig['db_password'] ?? ''; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="数据库密码">
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="text-blue-800">
                            <p class="font-medium">数据库要求：</p>
                            <ul class="list-disc list-inside text-sm mt-2 space-y-1">
                                <li>MySQL 5.7+ 或 MariaDB 10.2+</li>
                                <li>数据库字符集必须是 utf8mb4</li>
                                <li>数据库用户需要CREATE、INSERT、UPDATE、DELETE、SELECT权限</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-between">
                    <a href="index.php?step=1" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                        上一步
                    </a>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        下一步
                    </button>
                </div>
            </form>
        </div>
        <?php
        break;
        
    case 3:
        // 管理员设置
        $adminConfig = $_SESSION['admin_config'] ?? [];
        ?>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">管理员账户设置</h2>
            
            <form method="post" action="index.php?step=3" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">管理员用户名</label>
                        <input type="text" name="admin_username" value="<?php echo $adminConfig['admin_username'] ?? ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="admin" required minlength="3" maxlength="50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">邮箱地址</label>
                        <input type="email" name="admin_email" value="<?php echo $adminConfig['admin_email'] ?? ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="admin@example.com" required>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">密码</label>
                        <input type="password" name="admin_password" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="请输入密码" required minlength="6" id="password">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">确认密码</label>
                        <input type="password" name="admin_password_confirm" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="请再次输入密码" required minlength="6" id="password_confirm">
                    </div>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-yellow-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.728-.833-2.498 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <div class="text-yellow-800">
                            <p class="font-medium">安全提示：</p>
                            <ul class="list-disc list-inside text-sm mt-2 space-y-1">
                                <li>请使用强密码，包含大小写字母、数字和特殊字符</li>
                                <li>管理员账户拥有系统最高权限</li>
                                <li>请妥善保管管理员密码</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-between">
                    <a href="index.php?step=2" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                        上一步
                    </a>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors" 
                            onclick="return validatePasswords()">
                        开始安装
                    </button>
                </div>
            </form>
        </div>
        
        <script>
        function validatePasswords() {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirm').value;
            
            if (password !== confirm) {
                alert('两次输入的密码不一致！');
                return false;
            }
            
            if (password.length < 6) {
                alert('密码长度不能少于6位！');
                return false;
            }
            
            return true;
        }
        </script>
        <?php
        break;
        
    case 4:
        // 安装进度
        ?>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">正在安装...</h2>
            
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg" id="step1">
                    <div>
                        <div class="font-medium text-gray-900">连接数据库</div>
                        <div class="text-sm text-gray-500">测试数据库连接</div>
                    </div>
                    <div id="step1-icon" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                        <div class="w-4 h-4 bg-gray-300 rounded-full animate-pulse"></div>
                    </div>
                </div>
                
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg opacity-50" id="step2">
                    <div>
                        <div class="font-medium text-gray-900">创建数据表</div>
                        <div class="text-sm text-gray-500">初始化数据库结构</div>
                    </div>
                    <div id="step2-icon" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                        <div class="w-4 h-4 bg-gray-300 rounded-full"></div>
                    </div>
                </div>
                
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg opacity-50" id="step3">
                    <div>
                        <div class="font-medium text-gray-900">插入基础数据</div>
                        <div class="text-sm text-gray-500">创建默认设置</div>
                    </div>
                    <div id="step3-icon" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                        <div class="w-4 h-4 bg-gray-300 rounded-full"></div>
                    </div>
                </div>
                
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg opacity-50" id="step4">
                    <div>
                        <div class="font-medium text-gray-900">创建管理员账户</div>
                        <div class="text-sm text-gray-500">设置系统管理员</div>
                    </div>
                    <div id="step4-icon" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                        <div class="w-4 h-4 bg-gray-300 rounded-full"></div>
                    </div>
                </div>
                
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg opacity-50" id="step5">
                    <div>
                        <div class="font-medium text-gray-900">生成配置文件</div>
                        <div class="text-sm text-gray-500">创建系统配置</div>
                    </div>
                    <div id="step5-icon" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                        <div class="w-4 h-4 bg-gray-300 rounded-full"></div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
            <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <span class="text-red-800">安装失败: <?php echo $error; ?></span>
                </div>
            </div>
            <div class="mt-4">
                <a href="index.php?step=1" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    重新安装
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <script>
        // 模拟安装进度
        let currentStep = 1;
        
        function updateStep(step, status) {
            const stepDiv = document.getElementById('step' + step);
            const iconDiv = document.getElementById('step' + step + '-icon');
            
            if (status === 'loading') {
                iconDiv.innerHTML = '<div class="w-4 h-4 bg-blue-300 rounded-full animate-pulse"></div>';
                stepDiv.classList.remove('opacity-50');
            } else if (status === 'success') {
                iconDiv.innerHTML = '<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
            } else if (status === 'error') {
                iconDiv.innerHTML = '<svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
            }
        }
        
        // 自动执行安装
        setTimeout(() => {
            document.querySelector('form').submit();
        }, 1000);
        </script>
        
        <form method="post" action="index.php?step=4"></form>
        <?php
        break;
        
    case 5:
        // 安装完成
        $installedInfo = [];
        if (file_exists($lockFile)) {
            $installedInfo = json_decode(file_get_contents($lockFile), true);
        }
        ?>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                
                <h2 class="text-3xl font-bold text-gray-900 mb-4">安装完成！</h2>
                <p class="text-gray-600 mb-8">数据管理系统已成功安装并配置完成。</p>
                
                <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-8 text-left">
                    <h3 class="font-semibold text-green-900 mb-4">安装信息</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">安装版本:</span>
                            <span class="font-medium"><?php echo $installedInfo['version'] ?? '1.0.0'; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">安装时间:</span>
                            <span class="font-medium"><?php echo $installedInfo['installed_at'] ?? date('Y-m-d H:i:s'); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">管理员账户:</span>
                            <span class="font-medium"><?php echo $installedInfo['admin_username'] ?? 'admin'; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8 text-left">
                    <h3 class="font-semibold text-blue-900 mb-4">安全建议</h3>
                    <ul class="list-disc list-inside text-sm text-blue-800 space-y-2">
                        <li>请删除 install 目录以确保系统安全</li>
                        <li>定期备份数据库和上传文件</li>
                        <li>及时更新系统到最新版本</li>
                        <li>定期检查系统日志</li>
                    </ul>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="../index.php" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        进入系统
                    </a>
                    <button onclick="deleteInstallDir()" class="bg-red-600 text-white px-8 py-3 rounded-lg hover:bg-red-700 transition-colors font-medium">
                        删除安装目录
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        function deleteInstallDir() {
            if (confirm('确定要删除安装目录吗？这是推荐的安全操作。')) {
                // 这里无法直接删除目录，需要手动操作
                alert('请手动删除 install 目录以确保系统安全。');
            }
        }
        </script>
        <?php
        break;
}

renderFooter();
?>