<?php
/**
 * 用户管理页面
 * 用户列表、编辑、权限管理
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

// 处理用户操作
$action = $_GET['action'] ?? 'list';

if ($action === 'delete' && isset($_GET['id'])) {
    deleteUser($_GET['id']);
    header('Location: users.php?message=用户已删除');
    exit;
}

if ($action === 'toggle_status' && isset($_GET['id'])) {
    toggleUserStatus($_GET['id']);
    header('Location: users.php?message=用户状态已更新');
    exit;
}

// 获取用户列表
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';

$users = getUsers($search, $role, $status, $limit, $offset);
$totalUsers = countUsers($search, $role, $status);
$totalPages = ceil($totalUsers / $limit);

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - 数据管理系统</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .users-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 24px;
            margin: 20px;
        }
        
        .filters-section {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        
        .table-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
            border-radius: 6px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-edit {
            background: #3b82f6;
            color: white;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        
        .btn-toggle {
            background: #10b981;
            color: white;
        }
        
        .btn-toggle.disabled {
            background: #6b7280;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .role-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .role-admin {
            background: #fef3c7;
            color: #d97706;
        }
        
        .role-user {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 24px;
        }
        
        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            color: #374151;
        }
        
        .pagination a:hover {
            background: #f3f4f6;
        }
        
        .pagination .current {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .users-container {
                margin: 10px;
                padding: 16px;
            }
            
            .table-container {
                overflow-x: auto;
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
                <li><a href="index.php">📊 仪表板</a></li>
                <li><a href="users.php" class="active">👥 用户管理</a></li>
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
            <div class="users-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <div>
                        <h1 style="margin: 0; color: #374151;">👥 用户管理</h1>
                        <p style="margin: 5px 0 0; color: #6b7280;">
                            共 <?php echo number_format($totalUsers); ?> 位用户
                        </p>
                    </div>
                    <button class="btn btn-primary" onclick="showAddUserModal()">
                        ➕ 添加用户
                    </button>
                </div>

                <!-- 搜索和筛选 -->
                <div class="filters-section">
                    <form method="GET" class="filters-grid">
                        <div>
                            <label class="form-label">搜索用户</label>
                            <input type="text" name="search" placeholder="用户名、邮箱..." 
                                   class="form-input" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div>
                            <label class="form-label">用户角色</label>
                            <select name="role" class="form-input">
                                <option value="">全部角色</option>
                                <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>管理员</option>
                                <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>普通用户</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">账户状态</label>
                            <select name="status" class="form-input">
                                <option value="">全部状态</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>正常</option>
                                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>禁用</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary">🔍 搜索</button>
                            <a href="users.php" class="btn btn-secondary">重置</a>
                        </div>
                    </form>
                </div>

                <!-- 用户列表表格 -->
                <div class="table-container">
                    <?php if (empty($users)): ?>
                        <div style="text-align: center; padding: 60px 20px; color: #6b7280;">
                            <div style="font-size: 48px; margin-bottom: 16px;">👥</div>
                            <h3>没有找到用户</h3>
                            <p>尝试调整搜索条件或添加新用户</p>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>用户名</th>
                                    <th>邮箱</th>
                                    <th>角色</th>
                                    <th>状态</th>
                                    <th>注册时间</th>
                                    <th>最后登录</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>#<?php echo $user['id']; ?></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                                </div>
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                                <?php echo $user['role'] === 'admin' ? '管理员' : '普通用户'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $user['status']; ?>">
                                                <?php echo $user['status'] === 'active' ? '正常' : '禁用'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($user['created_at']); ?></td>
                                        <td><?php echo $user['last_login'] ? formatDate($user['last_login']) : '从未登录'; ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="btn-sm btn-edit" onclick="editUser(<?php echo $user['id']; ?>)">
                                                    编辑
                                                </button>
                                                <button class="btn-sm btn-toggle <?php echo $user['status'] === 'inactive' ? 'disabled' : ''; ?>" 
                                                        onclick="toggleUserStatus(<?php echo $user['id']; ?>, '<?php echo $user['status']; ?>')">
                                                    <?php echo $user['status'] === 'active' ? '禁用' : '启用'; ?>
                                                </button>
                                                <button class="btn-sm btn-delete" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                    删除
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- 分页 -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>">上一页</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>">下一页</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        function showAddUserModal() {
            const html = `
                <form id="addUserForm">
                    <div style="margin-bottom: 16px;">
                        <label class="form-label">用户名</label>
                        <input type="text" name="username" class="form-input" required>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label class="form-label">邮箱</label>
                        <input type="email" name="email" class="form-input" required>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label class="form-label">密码</label>
                        <input type="password" name="password" class="form-input" required>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label class="form-label">用户角色</label>
                        <select name="role" class="form-input">
                            <option value="user">普通用户</option>
                            <option value="admin">管理员</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" onclick="App.closeModal(document.querySelector('.modal'))" class="btn btn-secondary">取消</button>
                        <button type="submit" class="btn btn-primary">添加用户</button>
                    </div>
                </form>
            `;
            
            App.openModal(html, '添加新用户');
            
            document.getElementById('addUserForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch('api/add_user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        App.showNotification('用户添加成功！', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        App.showNotification('添加失败：' + data.message, 'error');
                    }
                })
                .catch(error => {
                    App.showNotification('添加失败：' + error.message, 'error');
                });
            });
        }

        function editUser(userId) {
            App.showNotification('编辑功能开发中...', 'info');
        }

        function toggleUserStatus(userId, currentStatus) {
            const action = currentStatus === 'active' ? '禁用' : '启用';
            
            if (confirm(`确定要${action}该用户吗？`)) {
                fetch(`users.php?action=toggle_status&id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        App.showNotification(`用户${action}成功！`, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        App.showNotification('操作失败：' + data.message, 'error');
                    }
                })
                .catch(error => {
                    App.showNotification('操作失败：' + error.message, 'error');
                });
            }
        }

        function deleteUser(userId) {
            if (confirm('确定要删除该用户吗？此操作不可恢复！')) {
                fetch(`users.php?action=delete&id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        App.showNotification('用户删除成功！', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        App.showNotification('删除失败：' + data.message, 'error');
                    }
                })
                .catch(error => {
                    App.showNotification('删除失败：' + error.message, 'error');
                });
            }
        }

        // 处理URL参数中的消息
        const urlParams = new URLSearchParams(window.location.search);
        const message = urlParams.get('message');
        if (message) {
            App.showNotification(message, 'success');
            // 清除URL参数
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>