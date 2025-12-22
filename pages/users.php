<?php
/**
 * 用户管理页面（仅管理员）
 */

// 检查管理员权限
if (!isset($currentUser) || $currentUser['role'] !== 'admin') {
    header('Location: ?action=dashboard');
    exit;
}

// 处理用户操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['user_action'] ?? '';
    
    switch ($action) {
        case 'add':
            $result = addUser($_POST);
            $message = $result['success'] ? '用户添加成功' : $result['message'];
            break;
            
        case 'edit':
            $result = editUser($_POST);
            $message = $result['success'] ? '用户信息更新成功' : $result['message'];
            break;
            
        case 'delete':
            $result = deleteUser($_POST['user_id']);
            $message = $result['success'] ? '用户删除成功' : $result['message'];
            break;
            
        case 'toggle_status':
            $result = toggleUserStatus($_POST['user_id']);
            $message = $result['success'] ? '用户状态更新成功' : $result['message'];
            break;
    }
}

// 获取用户列表
$page = max(1, $_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';

$users = getUsers($limit, $offset, $search);
$totalUsers = getTotalUsers($search);
$totalPages = ceil($totalUsers / $limit);
?>

<div class="space-y-6">
    <!-- 页面标题和操作栏 -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 font-display">用户管理</h2>
            <p class="text-gray-600">管理系统用户和权限设置</p>
        </div>
        
        <div class="flex space-x-3">
            <button onclick="showAddUserModal()" 
                    class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H8m8 0l-8-8-8 8z"></path>
                </svg>
                添加用户
            </button>
        </div>
    </div>

    <!-- 搜索和筛选 -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <form method="get">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="搜索用户名或邮箱...">
                </form>
            </div>
            
            <div class="flex space-x-3">
                <select class="form-control" onchange="location.href='?action=users&role='+this.value">
                    <option value="">所有角色</option>
                    <option value="admin" <?php echo (isset($_GET['role']) && $_GET['role'] == 'admin') ? 'selected' : ''; ?>>管理员</option>
                    <option value="user" <?php echo (isset($_GET['role']) && $_GET['role'] == 'user') ? 'selected' : ''; ?>>普通用户</option>
                </select>
                
                <select class="form-control" onchange="location.href='?action=users&status='+this.value">
                    <option value="">所有状态</option>
                    <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'selected' : ''; ?>>激活</option>
                    <option value="inactive" <?php echo (isset($_GET['status']) && $_GET['status'] == 'inactive') ? 'selected' : ''; ?>>禁用</option>
                </select>
            </div>
        </div>
    </div>

    <!-- 用户列表 -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">用户名</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">邮箱</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">角色</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">注册时间</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">最后登录</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                                <p class="text-gray-500">没有找到用户</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-xs font-medium text-blue-600"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></div>
                                        <?php if ($user['id'] == $currentUser['id']): ?>
                                        <span class="text-xs text-blue-600">当前用户</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $user['role'] == 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'; ?>">
                                    <?php echo $user['role'] == 'admin' ? '管理员' : '普通用户'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $user['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $user['status'] == 'active' ? '激活' : '禁用'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('Y-m-d', strtotime($user['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : '从未登录'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button onclick="showEditUserModal(<?php echo $user['id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-800" title="编辑">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-1.414a2 2 0 112.828 0l2.829-2.828a2 2 0 012.828 0l2.414 2.414a2 2 0 010-2.828z"></path>
                                        </svg>
                                    </button>
                                    
                                    <button onclick="toggleUserStatus(<?php echo $user['id']; ?>)" 
                                            class="text-<?php echo $user['status'] == 'active' ? 'yellow' : 'green'; ?>-600 hover:text-<?php echo $user['status'] == 'active' ? 'yellow' : 'green'; ?>-800" 
                                            title="<?php echo $user['status'] == 'active' ? '禁用' : '激活'; ?>">
                                        <?php if ($user['status'] == 'active'): ?>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 0118.364 5.636m-9 9l12.728-12.728"></path>
                                            </svg>
                                        <?php else: ?>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        <?php endif; ?>
                                    </button>
                                    
                                    <?php if ($user['id'] != $currentUser['id']): ?>
                                    <button onclick="confirmDeleteUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['username']); ?>')" 
                                            class="text-red-600 hover:text-red-800" title="删除">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 分页 -->
        <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    显示 <?php echo $offset + 1; ?> 到 <?php echo min($offset + $limit, $totalUsers); ?> 共 <?php echo $totalUsers; ?> 条记录
                </div>
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?action=users&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" 
                           class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50">上一页</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?action=users&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                           class="px-3 py-1 text-sm border <?php echo $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50'; ?> rounded-md">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?action=users&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" 
                           class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50">下一页</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 添加用户模态框 -->
<div id="addUserModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">添加用户</h3>
            <button onclick="hideAddUserModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form method="post" id="addUserForm">
            <input type="hidden" name="user_action" value="add">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">用户名</label>
                    <input type="text" name="username" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="请输入用户名" minlength="3" maxlength="50">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">邮箱</label>
                    <input type="email" name="email" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="请输入邮箱地址">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">密码</label>
                    <input type="password" name="password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="请输入密码" minlength="6">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">角色</label>
                    <select name="role" class="form-control">
                        <option value="user">普通用户</option>
                        <option value="admin">管理员</option>
                    </select>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="hideAddUserModal()" 
                        class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">
                    取消
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    添加
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 编辑用户模态框 -->
<div id="editUserModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">编辑用户</h3>
            <button onclick="hideEditUserModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form method="post" id="editUserForm">
            <input type="hidden" name="user_action" value="edit">
            <input type="hidden" name="user_id" id="editUserId">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">用户名</label>
                    <input type="text" name="username" id="editUsername" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">邮箱</label>
                    <input type="email" name="email" id="editEmail" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">角色</label>
                    <select name="role" id="editRole" class="form-control">
                        <option value="user">普通用户</option>
                        <option value="admin">管理员</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">状态</label>
                    <select name="status" id="editStatus" class="form-control">
                        <option value="active">激活</option>
                        <option value="inactive">禁用</option>
                    </select>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="hideEditUserModal()" 
                        class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">
                    取消
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    保存
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddUserModal() {
    document.getElementById('addUserModal').classList.remove('hidden');
}

function hideAddUserModal() {
    document.getElementById('addUserModal').classList.add('hidden');
    document.getElementById('addUserForm').reset();
}

function showEditUserModal(userId) {
    // 这里应该通过AJAX获取用户信息
    // 为了演示，我们设置默认值
    document.getElementById('editUserId').value = userId;
    document.getElementById('editUsername').value = '';
    document.getElementById('editEmail').value = '';
    document.getElementById('editRole').value = 'user';
    document.getElementById('editStatus').value = 'active';
    
    document.getElementById('editUserModal').classList.remove('hidden');
}

function hideEditUserModal() {
    document.getElementById('editUserModal').classList.add('hidden');
}

function toggleUserStatus(userId) {
    if (confirm('确定要切换用户状态吗？')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="user_action" value="toggle_status">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function confirmDeleteUser(userId, username) {
    if (confirm(`确定要删除用户 "${username}" 吗？此操作不可撤销。`)) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="user_action" value="delete">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// 点击模态框背景关闭
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        hideAddUserModal();
        hideEditUserModal();
    }
});
</script>

<?php
/**
 * 获取用户列表
 */
function getUsers($limit, $offset, $search = '') {
    // 这里应该从数据库获取实际用户数据
    return [
        [
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'status' => 'active',
            'created_at' => '2024-01-01 10:00:00',
            'last_login' => '2024-12-18 10:30:00'
        ]
    ];
}

/**
 * 获取用户总数
 */
function getTotalUsers($search = '') {
    // 这里应该从数据库统计实际用户数量
    return 1;
}

/**
 * 添加用户
 */
function addUser($data) {
    // 这里应该验证数据并插入数据库
    return ['success' => true, 'message' => '用户添加成功'];
}

/**
 * 编辑用户
 */
function editUser($data) {
    // 这里应该验证数据并更新数据库
    return ['success' => true, 'message' => '用户信息更新成功'];
}

/**
 * 删除用户
 */
function deleteUser($userId) {
    // 这里应该从数据库删除用户
    return ['success' => true, 'message' => '用户删除成功'];
}

/**
 * 切换用户状态
 */
function toggleUserStatus($userId) {
    // 这里应该更新用户状态
    return ['success' => true, 'message' => '用户状态更新成功'];
}
?>