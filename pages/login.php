<?php
/**
 * 登录页面
 */

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        $result = login($username, $password);
        
        if ($result['success']) {
            header('Location: ?action=dashboard');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>

<div class="bg-white rounded-lg shadow-lg p-8">
    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 font-display mb-2">数据管理系统</h2>
        <p class="text-gray-600">请登录您的账户</p>
    </div>
    
    <?php if (isset($error)): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <span class="text-red-800"><?php echo htmlspecialchars($error); ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <form method="post" class="space-y-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">用户名</label>
            <input type="text" name="username" 
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   placeholder="请输入用户名" required
                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">密码</label>
            <input type="password" name="password" 
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   placeholder="请输入密码" required>
        </div>
        
        <div class="flex items-center justify-between">
            <label class="flex items-center">
                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm text-gray-600">记住我</span>
            </label>
            <a href="#" class="text-sm text-blue-600 hover:text-blue-500">忘记密码？</a>
        </div>
        
        <button type="submit" 
                class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3 rounded-lg font-semibold hover:from-blue-700 hover:to-purple-700 transition-all transform hover:scale-105">
            登录
        </button>
    </form>
    
    <div class="mt-6 text-center">
        <p class="text-sm text-gray-600">
            还没有账户？
            <a href="#" class="text-blue-600 hover:text-blue-500 font-medium">立即注册</a>
        </p>
    </div>
    
    <div class="mt-8 pt-6 border-t border-gray-200">
        <div class="text-center text-xs text-gray-500">
            <p>数据管理系统 v1.0.0</p>
            <p>基于 PHP + MySQL 构建</p>
        </div>
    </div>
</div>