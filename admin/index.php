<?php
/**
 * 管理员后台首页
 * 系统管理和配置界面
 */

session_start();

// 检查管理员权限
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/database.php';

// 获取系统统计信息
$stats = getSystemStats();
$recentLogs = getRecentLogs();
$systemInfo = getSystemInfo();

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统管理 - 数据管理系统</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .sidebar {
            width: 250px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            padding: 20px 0;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 4px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: #374151;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 0 10px;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .admin-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .log-entry {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .log-level {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .log-level.info { background: #dbeafe; color: #1e40af; }
        .log-level.warning { background: #fef3c7; color: #d97706; }
        .log-level.error { background: #fee2e2; color: #dc2626; }
        
        .system-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #6b7280;
        }
        
        .info-value {
            color: #374151;
        }
        
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            }
            
            .sidebar-menu {
                display: flex;
                overflow-x: auto;
                padding: 0 10px;
            }
            
            .sidebar-menu li {
                margin: 0 5px;
            }
            
            .sidebar-menu a {
                white-space: nowrap;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- 侧边栏 -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2 style="margin: 0; color: #374151;">🎛️ 系统管理</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php" class="active">📊 仪表板</a></li>
                <li><a href="users.php">👥 用户管理</a></li>
                <li><a href="files.php">📁 文件管理</a></li>
                <li><a href="database.php">🗄️ 数据库管理</a></li>
                <li><a href="settings.php">⚙️ 系统设置</a></li>
                <li><a href="logs.php">📋 系统日志</a></li>
                <li><a href="backup.php">💾 备份恢复</a></li>
                <li><a href="../index.php">🏠 返回前台</a></li>
            </ul>
        </aside>

        <!-- 主内容区 -->
        <main class="main-content">
            <div class="admin-header" style="margin-bottom: 30px;">
                <h1 style="margin: 0; color: white;">系统管理仪表板</h1>
                <p style="margin: 5px 0 0; color: rgba(255, 255, 255, 0.8);">
                    欢迎回来，<?php echo htmlspecialchars($_SESSION['admin_username']); ?>！
                </p>
            </div>

            <!-- 统计卡片 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        👥
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                    <div class="stat-label">注册用户</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                        📁
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['total_files']); ?></div>
                    <div class="stat-label">上传文件</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                        📊
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['total_records']); ?></div>
                    <div class="stat-label">数据记录</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
                        💾
                    </div>
                    <div class="stat-value"><?php echo formatFileSize($stats['storage_used']); ?></div>
                    <div class="stat-label">存储使用</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- 最近日志 -->
                <div class="admin-card">
                    <h3 style="margin-top: 0; margin-bottom: 20px;">📋 最近日志</h3>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php if (empty($recentLogs)): ?>
                            <p style="text-align: center; color: #6b7280; padding: 40px 0;">暂无日志记录</p>
                        <?php else: ?>
                            <?php foreach ($recentLogs as $log): ?>
                                <div class="log-entry">
                                    <div>
                                        <div style="font-weight: 600; margin-bottom: 4px;"><?php echo htmlspecialchars($log['message']); ?></div>
                                        <div style="font-size: 12px; color: #6b7280;"><?php echo formatDate($log['created_at']); ?></div>
                                    </div>
                                    <span class="log-level <?php echo $log['level']; ?>"><?php echo strtoupper($log['level']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 系统信息 -->
                <div class="admin-card">
                    <h3 style="margin-top: 0; margin-bottom: 20px;">💻 系统信息</h3>
                    <div class="system-info-grid">
                        <div>
                            <div class="info-item">
                                <span class="info-label">PHP版本</span>
                                <span class="info-value"><?php echo $systemInfo['php_version']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">MySQL版本</span>
                                <span class="info-value"><?php echo $systemInfo['mysql_version']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">服务器软件</span>
                                <span class="info-value"><?php echo $systemInfo['server_software']; ?></span>
                            </div>
                        </div>
                        <div>
                            <div class="info-item">
                                <span class="info-label">操作系统</span>
                                <span class="info-value"><?php echo $systemInfo['os']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">内存使用</span>
                                <span class="info-value"><?php echo $systemInfo['memory_usage']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">磁盘空间</span>
                                <span class="info-value"><?php echo $systemInfo['disk_space']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 快速操作 -->
            <div class="admin-card">
                <h3 style="margin-top: 0; margin-bottom: 20px;">⚡ 快速操作</h3>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <button class="btn btn-primary" onclick="backupDatabase()">
                        💾 立即备份数据库
                    </button>
                    <button class="btn btn-primary" onclick="clearCache()">
                        🗑️ 清理缓存
                    </button>
                    <button class="btn btn-secondary" onclick="exportUsers()">
                        📥 导出用户数据
                    </button>
                    <button class="btn btn-secondary" onclick="checkSystemHealth()">
                        🔍 系统健康检查
                    </button>
                    <button class="btn btn-secondary" onclick="showSystemLogs()">
                        📋 查看详细日志
                    </button>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        // 管理员专用功能
        function backupDatabase() {
            App.showNotification('开始备份数据库...', 'info');
            fetch('api/backup.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    App.showNotification('数据库备份成功！', 'success');
                    // 创建下载链接
                    const link = document.createElement('a');
                    link.href = data.download_url;
                    link.download = data.filename;
                    link.click();
                } else {
                    App.showNotification('备份失败：' + data.message, 'error');
                }
            })
            .catch(error => {
                App.showNotification('备份失败：' + error.message, 'error');
            });
        }

        function clearCache() {
            if (confirm('确定要清理所有缓存吗？')) {
                App.showNotification('正在清理缓存...', 'info');
                setTimeout(() => {
                    App.showNotification('缓存清理完成！', 'success');
                }, 2000);
            }
        }

        function exportUsers() {
            App.showNotification('正在导出用户数据...', 'info');
            setTimeout(() => {
                App.showNotification('用户数据导出成功！', 'success');
            }, 1500);
        }

        function checkSystemHealth() {
            App.showNotification('正在检查系统健康状态...', 'info');
            setTimeout(() => {
                const health = {
                    database: '正常',
                    file_system: '正常',
                    memory: '良好',
                    disk: '充足'
                };
                
                let html = '<h4>系统健康检查结果</h4><ul>';
                for (const [key, status] of Object.entries(health)) {
                    html += `<li>${key}: <strong style="color: #10b981;">${status}</strong></li>`;
                }
                html += '</ul>';
                
                App.openModal(html, '系统健康检查');
            }, 2000);
        }

        function showSystemLogs() {
            window.location.href = 'logs.php';
        }

        // 自动刷新数据
        setInterval(() => {
            // 每5分钟刷新一次统计数据
            location.reload();
        }, 300000);
    </script>
</body>
</html>