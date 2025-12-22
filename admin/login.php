<?php
/**
 * 管理员登录页面
 */

session_start();

// 如果已经登录，重定向到管理后台
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // 验证管理员凭据
    if (authenticateAdmin($username, $password)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['login_time'] = time();
        
        // 记录登录日志
        logAdminAction('login', "管理员 {$username} 登录系统");
        
        header('Location: index.php');
        exit;
    } else {
        $error = '用户名或密码错误';
        logAdminAction('login_failed', "管理员登录失败: {$username}");
    }
}

// 模拟认证函数（实际项目中应该连接数据库验证）
function authenticateAdmin($username, $password) {
    // 默认管理员账户
    $adminUsers = [
        'admin' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
        'superadmin' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
    ];
    
    return isset($adminUsers[$username]) && password_verify($password, $adminUsers[$username]);
}

function logAdminAction($action, $details) {
    // 这里应该将日志写入数据库或文件
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    // 简单的文件日志记录
    file_put_contents('../logs/admin_' . date('Y-m-d') . '.log', json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - 数据管理系统</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }
        
        .admin-login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 
                0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .login-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .login-title {
            font-size: 24px;
            font-weight: 700;
            color: #374151;
            margin: 0 0 8px 0;
        }
        
        .login-subtitle {
            color: #6b7280;
            font-size: 14px;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }
        
        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(102, 126, 234, 0.5);
        }
        
        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid rgba(239, 68, 68, 0.2);
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .security-notice {
            background: rgba(59, 130, 246, 0.1);
            color: #2563eb;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid rgba(59, 130, 246, 0.2);
            margin-top: 24px;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .default-credentials {
            background: rgba(251, 146, 60, 0.1);
            color: #ea580c;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid rgba(251, 146, 60, 0.2);
            margin-top: 20px;
            font-size: 13px;
        }
        
        .back-link {
            text-align: center;
            margin-top: 24px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: #764ba2;
        }
        
        @media (max-width: 480px) {
            .login-card {
                margin: 20px;
                padding: 30px 20px;
            }
            
            .login-title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">🎛️</div>
                <h1 class="login-title">管理员登录</h1>
                <p class="login-subtitle">数据管理系统后台</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <span>⚠️</span>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="username" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">用户名</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input" 
                        placeholder="请输入管理员用户名"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">密码</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="请输入密码"
                        required
                    >
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    登录管理系统
                </button>
            </form>

            <div class="security-notice">
                <strong>🔒 安全提示：</strong><br>
                • 此为管理员登录界面<br>
                • 请使用授权的账户登录<br>
                • 登录失败将被记录<br>
                • 请勿在公共设备上登录
            </div>

            <div class="default-credentials">
                <strong>📝 默认账户：</strong><br>
                用户名：<code>admin</code><br>
                密码：<code>password</code><br>
                <small>⚠️ 首次登录后请立即修改密码</small>
            </div>

            <div class="back-link">
                <a href="../index.php">← 返回前台</a>
            </div>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('loginBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading"></span> 登录中...';
            
            // 添加一些延迟以显示加载状态
            setTimeout(() => {
                // 表单会自然提交
            }, 500);
        });

        // 添加输入框焦点效果
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // 检查登录失败次数
        let loginAttempts = parseInt(localStorage.getItem('admin_login_attempts') || '0');
        const maxAttempts = 5;
        
        if (loginAttempts >= maxAttempts) {
            const lockoutTime = parseInt(localStorage.getItem('admin_lockout_time') || '0');
            const currentTime = Date.now();
            const lockoutDuration = 15 * 60 * 1000; // 15分钟
            
            if (currentTime - lockoutTime < lockoutDuration) {
                const remainingTime = Math.ceil((lockoutDuration - (currentTime - lockoutTime)) / 60000);
                document.getElementById('loginForm').innerHTML = `
                    <div class="error-message">
                        <span>🔒</span>
                        登录尝试次数过多，请 ${remainingTime} 分钟后再试
                    </div>
                `;
            } else {
                localStorage.removeItem('admin_login_attempts');
                localStorage.removeItem('admin_lockout_time');
            }
        }

        <?php if (isset($error)): ?>
            // 登录失败时增加尝试次数
            loginAttempts++;
            localStorage.setItem('admin_login_attempts', loginAttempts.toString());
            
            if (loginAttempts >= maxAttempts) {
                localStorage.setItem('admin_lockout_time', Date.now().toString());
            }
        <?php endif; ?>

        // 自动填充演示账户（仅开发环境）
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            document.getElementById('username').value = 'admin';
            document.getElementById('password').value = 'password';
        }
    </script>
</body>
</html>