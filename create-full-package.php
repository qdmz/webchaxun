<?php
/**
 * 完整PHP项目打包脚本
 * 将自动复制所有必要文件并创建压缩包
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🚀 开始创建完整的PHP项目包...\n";

// 目标目录
$sourceDir = __DIR__ . '/..';
$targetDir = __DIR__ . '/data-management-system';

// 清理并创建目标目录
if (is_dir($targetDir)) {
    removeDirectory($targetDir);
}
mkdir($targetDir, 0755, true);

echo "📁 目标目录: $targetDir\n";

// 需要复制的文件和目录结构
$structure = [
    // 核心文件
    'install/index.php' => 'install/index.php',
    'index.php' => 'index.php',
    
    // 配置文件
    'config/config.example.php' => 'config/config.example.php',
    
    // 数据库
    'database/structure.sql' => 'database/structure.sql',
    
    // 页面文件
    'pages/login.php' => 'pages/login.php',
    'pages/dashboard.php' => 'pages/dashboard.php',
    'pages/files.php' => 'pages/files.php',
    'pages/404.php' => 'pages/404.php',
    
    // 核心功能文件
    'includes/config.php' => 'includes/config.php',
    'includes/database.php' => 'includes/database.php',
    'includes/functions.php' => 'includes/functions.php',
    'includes/security.php' => 'includes/security.php',
    'includes/user-auth.php' => 'includes/user-auth.php',
    'includes/performance.php' => 'includes/performance.php',
    
    // 文档
    'README_INSTALL.md' => 'README_INSTALL.md',
    'DEPLOYMENT_GUIDE_COMPLETE.md' => 'DEPLOYMENT_GUIDE_COMPLETE.md',
    
    // 其他资源
    '.htaccess.example' => '.htaccess',
    'composer.json' => 'composer.json',
    'version.json' => 'version.json'
];

// 创建目录结构
echo "📁 创建目录结构...\n";
$directories = [
    'install',
    'config',
    'database',
    'pages',
    'includes',
    'uploads',
    'assets/css',
    'assets/js',
    'assets/images',
    'logs'
];

foreach ($directories as $dir) {
    $dirPath = $targetDir . '/' . $dir;
    if (!is_dir($dirPath)) {
        mkdir($dirPath, 0755, true);
        echo "  📁 创建目录: $dir\n";
    }
}

// 复制文件
echo "📄 复制文件...\n";
$copiedFiles = 0;
$skippedFiles = 0;

foreach ($structure as $source => $target) {
    $sourcePath = $sourceDir . '/' . $source;
    $targetPath = $targetDir . '/' . $target;
    
    // 确保目标目录存在
    $targetDirPath = dirname($targetPath);
    if (!is_dir($targetDirPath)) {
        mkdir($targetDirPath, 0755, true);
    }
    
    if (file_exists($sourcePath)) {
        if (copy($sourcePath, $targetPath)) {
            echo "  ✅ 复制: $source -> $target\n";
            $copiedFiles++;
        } else {
            echo "  ❌ 复制失败: $source\n";
            $skippedFiles++;
        }
    } else {
        echo "  ⚠️  源文件不存在: $source\n";
        $skippedFiles++;
    }
}

// 创建额外的配置文件
echo "\n📝 创建配置文件...\n";

// 创建 .htaccess 文件
$htaccessContent = '# 数据管理系统 - Apache配置
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# 安全配置
<Files "config.php">
    Require all denied
</Files>

<Files ~ "^\.">
    Require all denied
</Files>

<FilesMatch "^(install|config|logs)/">
    Require all denied
</FilesMatch>

# 文件上传限制
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300

# 缓存配置
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
</IfModule>
';

file_put_contents($targetDir . '/.htaccess', $htaccessContent);
echo "  ✅ 创建: .htaccess\n";

// 创建 composer.json 文件
$composerJson = [
    'name' => 'data-management-system',
    'version' => '1.0.0',
    'description' => '现代化的PHP数据管理系统',
    'type' => 'project',
    'keywords' => ['php', 'mysql', 'data-management', 'excel', 'file-management'],
    'license' => 'MIT',
    'require' => [
        'php' => '>=7.4.0'
    ],
    'autoload' => [
        'psr-4' => [
            'App\\' => 'src/'
        ]
    ]
];

file_put_contents($targetDir . '/composer.json', json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  ✅ 创建: composer.json\n";

// 创建 version.json 文件
$versionInfo = [
    'version' => '1.0.0',
    'name' => '数据管理系统',
    'description' => '基于PHP+MySQL的现代化数据管理系统',
    'build_date' => date('Y-m-d H:i:s'),
    'php_version_required' => '7.4.0',
    'mysql_version_required' => '5.7.0',
    'features' => [
        '在线安装向导',
        '文件上传管理',
        '数据查询分析',
        '用户权限管理',
        '响应式界面',
        '安全防护机制'
    ],
    'requirements' => [
        'php' => '7.4+',
        'mysql' => '5.7+ / MariaDB 10.2+',
        'extensions' => ['mysqli', 'pdo_mysql', 'fileinfo', 'curl', 'session', 'json', 'mbstring'],
        'web_server' => 'Apache 2.4+ / Nginx 1.12+',
        'memory' => '512MB+',
        'storage' => '100MB+'
    ],
    'directories' => [
        'uploads' => '文件上传目录（可写）',
        'config' => '配置文件目录（可写）',
        'logs' => '日志文件目录（可选，可写）'
    ],
    'security' => [
        'sql_injection_protection' => '预处理语句防护',
        'xss_protection' => '输出转义处理',
        'csrf_protection' => 'Token验证机制',
        'password_hashing' => 'BCrypt哈希加密',
        'session_security' => '安全会话管理',
        'file_validation' => '文件类型和大小验证',
        'access_control' => '基于角色的权限控制'
    ]
];

file_put_contents($targetDir . '/version.json', json_encode($versionInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  ✅ 创建: version.json\n";

// 创建基础CSS文件
$cssContent = '/* 数据管理系统 - 基础样式 */
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap");

:root {
    --primary-color: #3b82f6;
    --secondary-color: #6b7280;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --font-family-sans: "Inter", system-ui, sans-serif;
    --font-family-display: "IBM Plex Sans", sans-serif;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: var(--font-family-sans);
    line-height: 1.6;
    color: #374151;
    background-color: #f9fafb;
}

.font-display {
    font-family: var(--font-family-display);
}

.btn {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
    font-size: 0.875rem;
    line-height: 1;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
    box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.1);
}

.btn-primary:hover {
    background-color: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.2);
}

.btn-secondary {
    background-color: var(--secondary-color);
    color: white;
}

.btn-success {
    background-color: var(--success-color);
    color: white;
}

.btn-danger {
    background-color: var(--danger-color);
    color: white;
}

.btn-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.75rem;
}

.btn-lg {
    padding: 1rem 1.5rem;
    font-size: 1rem;
}

.card {
    background-color: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.card-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.card-body {
    padding: 1.5rem;
}

.card-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e5e7eb;
    background-color: #f9fafb;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.alert {
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    border-left: 4px solid;
}

.alert-success {
    background-color: #f0fdf4;
    border-left-color: var(--success-color);
    color: #166534;
}

.alert-error {
    background-color: #fef2f2;
    border-left-color: var(--danger-color);
    color: #991b1b;
}

.alert-warning {
    background-color: #fffbeb;
    border-left-color: var(--warning-color);
    color: #92400e;
}

.alert-info {
    background-color: #eff6ff;
    border-left-color: var(--primary-color);
    color: #1e40af;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.flex {
    display: flex;
}

.flex-col {
    flex-direction: column;
}

.items-center {
    align-items: center;
}

.justify-between {
    justify-content: space-between;
}

.justify-center {
    justify-content: center;
}

.text-center {
    text-align: center;
}

.text-left {
    text-align: left;
}

.text-right {
    text-align: right;
}

.mb-2 { margin-bottom: 0.5rem; }
.mb-4 { margin-bottom: 1rem; }
.mb-6 { margin-bottom: 1.5rem; }

.mt-2 { margin-top: 0.5rem; }
.mt-4 { margin-top: 1rem; }
.mt-6 { margin-top: 1.5rem; }

.p-2 { padding: 0.5rem; }
.p-4 { padding: 1rem; }
.p-6 { padding: 1.5rem; }

.px-2 { padding-left: 0.5rem; padding-right: 0.5rem; }
.px-4 { padding-left: 1rem; padding-right: 1rem; }
.px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }

.py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }
.py-6 { padding-top: 1.5rem; padding-bottom: 1.5rem; }

/* 响应式 */
@media (max-width: 768px) {
    .container {
        padding: 0 0.5rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .btn {
        font-size: 0.75rem;
        padding: 0.625rem 0.875rem;
    }
}

/* 动画 */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in {
    animation: fadeIn 0.6s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.slide-in {
    animation: slideIn 0.4s ease-out;
}

/* 加载动画 */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.loading {
    animation: spin 1s linear infinite;
}

/* 工具类 */
.w-full { width: 100%; }
.h-full { height: 100%; }
.min-h-screen { min-height: 100vh; }

.rounded { border-radius: 0.25rem; }
.rounded-lg { border-radius: 0.5rem; }
.rounded-xl { border-radius: 0.75rem; }

.shadow { box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
.shadow-lg { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
.shadow-xl { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }

.bg-white { background-color: white; }
.bg-gray-50 { background-color: #f9fafb; }
.bg-gray-100 { background-color: #f3f4f6; }

.text-gray-900 { color: #111827; }
.text-gray-600 { color: #4b5563; }
.text-gray-500 { color: #6b7280; }
.text-gray-400 { color: #9ca3af; }

.border { border: 1px solid #e5e7eb; }
.border-gray-200 { border-color: #e5e7eb; }
';

file_put_contents($targetDir . '/assets/css/style.css', $cssContent);
echo "  ✅ 创建: assets/css/style.css\n";

// 创建基础JavaScript文件
$jsContent = '// 数据管理系统 - 核心脚本
(function() {
    "use strict";
    
    // 全局配置
    window.DataManagement = {
        version: "1.0.0",
        api: {
            baseUrl: window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, "/"),
            timeout: 30000
        },
        ui: {
            loading: false,
            notifications: []
        }
    };
    
    // 工具函数
    const Utils = {
        // 格式化文件大小
        formatFileSize: function(bytes) {
            if (bytes === 0) return "0 B";
            const k = 1024;
            const sizes = ["B", "KB", "MB", "GB"];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
        },
        
        // 格式化日期
        formatDate: function(dateString, format = "YYYY-MM-DD HH:mm") {
            const date = new Date(dateString);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, "0");
            const day = String(date.getDate()).padStart(2, "0");
            const hours = String(date.getHours()).padStart(2, "0");
            const minutes = String(date.getMinutes()).padStart(2, "0");
            
            return format
                .replace("YYYY", year)
                .replace("MM", month)
                .replace("DD", day)
                .replace("HH", hours)
                .replace("mm", minutes);
        },
        
        // 显示通知
        showNotification: function(message, type = "success", duration = 5000) {
            const notification = document.createElement("div");
            notification.className = `notification notification-${type} slide-in`;
            notification.innerHTML = `
                <div class="notification-content">
                    <span class="notification-message">${message}</span>
                    <button class="notification-close">&times;</button>
                </div>
            `;
            
            // 添加到页面
            let container = document.querySelector(".notification-container");
            if (!container) {
                container = document.createElement("div");
                container.className = "notification-container";
                document.body.appendChild(container);
            }
            
            container.appendChild(notification);
            
            // 自动关闭
            setTimeout(() => {
                notification.classList.add("fade-out");
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, duration);
            
            // 手动关闭
            notification.querySelector(".notification-close").addEventListener("click", () => {
                notification.classList.add("fade-out");
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            });
        },
        
        // 确认对话框
        confirm: function(message, callback) {
            if (window.confirm(message)) {
                callback();
            }
        },
        
        // AJAX请求封装
        ajax: function(options) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                
                xhr.open(options.method || "GET", options.url, true);
                xhr.setRequestHeader("Content-Type", "application/json");
                xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
                
                xhr.timeout = options.timeout || DataManagement.api.timeout;
                
                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            resolve(response);
                        } catch (e) {
                            resolve(xhr.responseText);
                        }
                    } else {
                        reject(new Error(xhr.statusText));
                    }
                };
                
                xhr.onerror = function() {
                    reject(new Error("网络错误"));
                };
                
                xhr.ontimeout = function() {
                    reject(new Error("请求超时"));
                };
                
                const data = options.data ? JSON.stringify(options.data) : null;
                xhr.send(data);
            });
        },
        
        // 表单序列化
        serializeForm: function(form) {
            const formData = new FormData(form);
            const object = {};
            
            formData.forEach((value, key) => {
                object[key] = value;
            });
            
            return object;
        },
        
        // 设置加载状态
        setLoading: function(loading, element = document.body) {
            if (loading) {
                element.classList.add("loading");
            } else {
                element.classList.remove("loading");
            }
        }
    };
    
    // 全局事件监听
    document.addEventListener("DOMContentLoaded", function() {
        console.log("数据管理系统 v" + DataManagement.version + " 已加载");
        
        // 初始化所有表单
        initForms();
        
        // 初始化工具提示
        initTooltips();
        
        // 初始化模态框
        initModals();
        
        // 初始化文件上传
        initFileUploads();
        
        // 初始化数据表格
        initTables();
    });
    
    // 初始化表单
    function initForms() {
        const forms = document.querySelectorAll("form");
        forms.forEach(form => {
            form.addEventListener("submit", function(e) {
                const submitBtn = form.querySelector("button[type=submit]");
                if (submitBtn && !submitBtn.classList.contains("btn-loading")) {
                    submitBtn.classList.add("btn-loading");
                    submitBtn.disabled = true;
                    const originalText = submitBtn.textContent;
                    submitBtn.innerHTML = `<span class="loading"></span> 处理中...`;
                    submitBtn.dataset.originalText = originalText;
                    
                    // 10秒后恢复（防止提交失败）
                    setTimeout(() => {
                        submitBtn.classList.remove("btn-loading");
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }, 10000);
                }
            });
            
            // 表单验证
            initFormValidation(form);
        });
    }
    
    // 初始化表单验证
    function initFormValidation(form) {
        const inputs = form.querySelectorAll("input[required], select[required], textarea[required]");
        
        inputs.forEach(input => {
            input.addEventListener("blur", function() {
                validateField(input);
            });
            
            input.addEventListener("input", function() {
                if (input.classList.contains("error")) {
                    validateField(input);
                }
            });
        });
    }
    
    // 验证字段
    function validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = "";
        
        if (field.hasAttribute("required") && !value) {
            isValid = false;
            errorMessage = "此字段为必填项";
        }
        
        // 邮箱验证
        if (field.type === "email" && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = "请输入有效的邮箱地址";
            }
        }
        
        // 最小长度验证
        const minLength = field.getAttribute("minlength");
        if (minLength && value.length < parseInt(minLength)) {
            isValid = false;
            errorMessage = `最少需要 ${minLength} 个字符`;
        }
        
        // 显示/隐藏错误信息
        const existingError = field.parentNode.querySelector(".field-error");
        if (existingError) {
            existingError.remove();
        }
        
        if (!isValid) {
            field.classList.add("error");
            const errorDiv = document.createElement("div");
            errorDiv.className = "field-error";
            errorDiv.textContent = errorMessage;
            field.parentNode.appendChild(errorDiv);
        } else {
            field.classList.remove("error");
        }
        
        return isValid;
    }
    
    // 初始化工具提示
    function initTooltips() {
        const tooltipElements = document.querySelectorAll("[data-tooltip]");
        
        tooltipElements.forEach(element => {
            element.addEventListener("mouseenter", function(e) {
                showTooltip(e.target, element.getAttribute("data-tooltip"));
            });
            
            element.addEventListener("mouseleave", function() {
                hideTooltip();
            });
        });
    }
    
    // 显示工具提示
    function showTooltip(element, text) {
        hideTooltip();
        
        const tooltip = document.createElement("div");
        tooltip.className = "tooltip";
        tooltip.textContent = text;
        
        document.body.appendChild(tooltip);
        
        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + "px";
        tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + "px";
    }
    
    // 隐藏工具提示
    function hideTooltip() {
        const existingTooltip = document.querySelector(".tooltip");
        if (existingTooltip) {
            existingTooltip.remove();
        }
    }
    
    // 初始化模态框
    function initModals() {
        // 模态框触发器
        const modalTriggers = document.querySelectorAll("[data-modal-target]");
        modalTriggers.forEach(trigger => {
            trigger.addEventListener("click", function(e) {
                e.preventDefault();
                const targetId = this.getAttribute("data-modal-target");
                showModal(targetId);
            });
        });
        
        // 模态框关闭
        document.addEventListener("click", function(e) {
            if (e.target.classList.contains("modal") || 
                e.target.classList.contains("modal-close")) {
                hideAllModals();
            }
        });
        
        // ESC键关闭
        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape") {
                hideAllModals();
            }
        });
    }
    
    // 显示模态框
    function showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add("show");
            document.body.classList.add("modal-open");
        }
    }
    
    // 隐藏所有模态框
    function hideAllModals() {
        const modals = document.querySelectorAll(".modal.show");
        modals.forEach(modal => {
            modal.classList.remove("show");
        });
        document.body.classList.remove("modal-open");
    }
    
    // 初始化文件上传
    function initFileUploads() {
        const fileInputs = document.querySelectorAll("input[type=file]");
        
        fileInputs.forEach(input => {
            input.addEventListener("change", function(e) {
                handleFileSelect(e.target);
            });
        });
    }
    
    // 处理文件选择
    function handleFileSelect(input) {
        const file = input.files[0];
        if (!file) return;
        
        // 显示文件信息
        const fileInfo = input.parentNode.querySelector(".file-info");
        if (fileInfo) {
            fileInfo.innerHTML = `
                <span class="file-name">${file.name}</span>
                <span class="file-size">${Utils.formatFileSize(file.size)}</span>
            `;
        }
        
        // 验证文件
        validateFile(file, input);
    }
    
    // 验证文件
    function validateFile(file, input) {
        const maxSize = input.getAttribute("data-max-size") || 10 * 1024 * 1024; // 10MB
        const allowedTypes = input.getAttribute("data-allowed-types")?.split(",") || [];
        
        // 文件大小检查
        if (file.size > maxSize) {
            Utils.showNotification("文件大小超过限制", "error");
            input.value = "";
            return false;
        }
        
        // 文件类型检查
        if (allowedTypes.length > 0) {
            const fileExtension = file.name.split(".").pop().toLowerCase();
            if (!allowedTypes.includes(fileExtension)) {
                Utils.showNotification("不支持的文件类型", "error");
                input.value = "";
                return false;
            }
        }
        
        return true;
    }
    
    // 初始化数据表格
    function initTables() {
        const tables = document.querySelectorAll(".data-table");
        
        tables.forEach(table => {
            initTable(table);
        });
    }
    
    // 初始化表格
    function initTable(table) {
        // 排序功能
        const sortableHeaders = table.querySelectorAll(".sortable");
        sortableHeaders.forEach(header => {
            header.addEventListener("click", function() {
                sortTable(table, this);
            });
        });
        
        // 搜索功能
        const searchInput = table.parentNode.querySelector(".table-search");
        if (searchInput) {
            searchInput.addEventListener("input", function() {
                filterTable(table, this.value);
            });
        }
    }
    
    // 表格排序
    function sortTable(table, header) {
        const tbody = table.querySelector("tbody");
        const rows = Array.from(tbody.querySelectorAll("tr"));
        const columnIndex = Array.from(header.parentNode.children).indexOf(header);
        const isAsc = header.classList.contains("sort-asc");
        
        // 更新排序图标
        table.querySelectorAll(".sortable").forEach(h => {
            h.classList.remove("sort-asc", "sort-desc");
        });
        header.classList.add(isAsc ? "sort-desc" : "sort-asc");
        
        // 排序行
        rows.sort((a, b) => {
            const aText = a.children[columnIndex].textContent.trim();
            const bText = b.children[columnIndex].textContent.trim();
            
            const comparison = aText.localeCompare(bText);
            return isAsc ? -comparison : comparison;
        });
        
        // 重新插入行
        rows.forEach(row => tbody.appendChild(row));
    }
    
    // 表格过滤
    function filterTable(table, searchText) {
        const tbody = table.querySelector("tbody");
        const rows = tbody.querySelectorAll("tr");
        const text = searchText.toLowerCase();
        
        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            row.style.display = rowText.includes(text) ? "" : "none";
        });
    }
    
    // 导出到全局
    window.Utils = Utils;
    
})();

// 添加CSS样式
const additionalStyles = `
/* 通知样式 */
.notification-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 400px;
}

.notification {
    background: white;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    margin-bottom: 10px;
    overflow: hidden;
    border-left: 4px solid;
}

.notification-success { border-left-color: #10b981; }
.notification-error { border-left-color: #ef4444; }
.notification-warning { border-left-color: #f59e0b; }
.notification-info { border-left-color: #3b82f6; }

.notification-content {
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.notification-message {
    flex: 1;
    margin-right: 10px;
}

.notification-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    opacity: 0.5;
    transition: opacity 0.3s;
}

.notification-close:hover {
    opacity: 1;
}

.slide-in {
    animation: slideIn 0.3s ease-out;
}

.fade-out {
    animation: fadeOut 0.3s ease-out;
}

@keyframes fadeOut {
    from { opacity: 1; transform: translateX(0); }
    to { opacity: 0; transform: translateX(20px); }
}

/* 模态框样式 */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 12px;
    max-width: 90%;
    max-height: 90%;
    overflow-y: auto;
    animation: modalFadeIn 0.3s ease-out;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    opacity: 0.5;
    transition: opacity 0.3s;
}

.modal-close:hover {
    opacity: 1;
}

@keyframes modalFadeIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

.modal-open {
    overflow: hidden;
}

/* 加载样式 */
.loading {
    position: relative;
    pointer-events: none;
}

.loading::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f4f6;
    border-top: 2px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.btn-loading {
    position: relative;
    color: transparent !important;
}

.btn-loading::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 16px;
    height: 16px;
    margin: -8px 0 0 -8px;
    border: 2px solid white;
    border-top: 2px solid transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

/* 表格样式 */
.data-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.data-table th,
.data-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.data-table th {
    background: #f9fafb;
    font-weight: 600;
    color: #374151;
    position: relative;
}

.data-table tbody tr:hover {
    background: #f9fafb;
}

.sortable {
    cursor: pointer;
    user-select: none;
}

.sortable:hover {
    background: #f3f4f6;
}

.sortable::after {
    content: "↕";
    position: absolute;
    right: 8px;
    opacity: 0.3;
}

.sort-asc::after {
    content: "↑";
    opacity: 1;
}

.sort-desc::after {
    content: "↓";
    opacity: 1;
}

/* 工具提示样式 */
.tooltip {
    position: absolute;
    background: #1f2937;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 14px;
    white-space: nowrap;
    z-index: 1001;
    opacity: 0;
    animation: tooltipFadeIn 0.3s ease-out forwards;
}

@keyframes tooltipFadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* 表单验证样式 */
.form-control.error {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.field-error {
    color: #ef4444;
    font-size: 14px;
    margin-top: 4px;
    display: block;
}

/* 文件上传样式 */
.file-info {
    margin-top: 8px;
    padding: 8px;
    background: #f9fafb;
    border-radius: 4px;
    font-size: 14px;
}

.file-name {
    font-weight: 500;
    color: #374151;
    display: block;
    margin-bottom: 4px;
}

.file-size {
    color: #6b7280;
    font-size: 12px;
}

/* 搜索框样式 */
.table-search {
    margin-bottom: 16px;
    padding: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    width: 100%;
    font-size: 14px;
}

.table-search:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
`;

const styleSheet = document.createElement("style");
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);
';

file_put_contents($targetDir . '/assets/js/app.js', $jsContent);
echo "  ✅ 创建: assets/js/app.js\n";

// 创建 .gitkeep 文件
file_put_contents($targetDir . '/uploads/.gitkeep', '');
echo "  ✅ 创建: uploads/.gitkeep\n";

// 创建 install.md 文件
$installMd = '# 数据管理系统 - 快速安装指南

## 🚀 系统要求

### 服务器环境
- PHP >= 7.4.0
- MySQL >= 5.7.0 或 MariaDB >= 10.2.0
- Web服务器: Apache 2.4+ 或 Nginx 1.12+

### PHP扩展
- mysqli
- pdo_mysql  
- fileinfo
- curl
- session
- json
- mbstring

### 目录权限
- config/ - 可读写
- uploads/ - 可读写
- logs/ - 可写（可选）

## 📦 安装步骤

### 1. 上传文件
将整个安装包上传到您的Web服务器目录

### 2. 设置权限
```bash
chmod -R 755 .
chmod -R 755 uploads/
chmod -R 755 config/
```

### 3. 访问安装程序
在浏览器中访问：
```
http://your-domain.com/install/
```

### 4. 按向导完成安装
- ✅ 环境检查
- ✅ 数据库配置  
- ✅ 管理员设置
- ✅ 安装执行
- ✅ 安装完成

### 5. 安全配置
安装完成后请删除 `install/` 目录
```bash
rm -rf install/
```

## 🎯 默认信息

- 管理员账户：安装时创建
- 上传限制：10MB
- 支持格式：.xlsx, .xls, .csv
- 会话超时：1小时

## 🔧 配置文件

主要配置文件位置：
- `config/config.php` - 系统配置
- `config/install.lock` - 安装锁文件

## 📞 技术支持

如遇问题请查看：
1. 安装说明文档
2. 系统日志文件
3. 服务器错误日志

祝您使用愉快！ 🎉
';

file_put_contents($targetDir . '/INSTALL.md', $installMd);
echo "  ✅ 创建: INSTALL.md\n";

// 创建 package.json
$packageInfo = [
    'name' => 'data-management-system',
    'version' => '1.0.0',
    'description' => '现代化的PHP数据管理系统',
    'keywords' => ['php', 'mysql', 'data-management', 'excel', 'file-management'],
    'homepage' => 'https://github.com/your-repo/data-management-system',
    'license' => 'MIT',
    'authors' => [
        [
            'name' => 'CloudBase AI ToolKit',
            'email' => 'support@example.com'
        ]
    ],
    'require' => [
        'php' => '>=7.4.0',
        'ext-mysqli' => '*',
        'ext-pdo_mysql' => '*',
        'ext-fileinfo' => '*',
        'ext-curl' => '*',
        'ext-json' => '*',
        'ext-mbstring' => '*'
    ],
    'autoload' => [
        'psr-4' => [
            'App\\' => 'src/'
        ]
    ],
    'scripts' => [
        'post-install-cmd' => [
            '@php -r "file_exists(\'config/install.lock\') || copy(\'config/config.example.php\', \'config/config.php\');"'
        ]
    ]
];

file_put_contents($targetDir . '/package.json', json_encode($packageInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  ✅ 创建: package.json\n";

echo "\n📊 文件复制统计:\n";
echo "  ✅ 成功复制: $copiedFiles 个文件\n";
echo "  ⚠️  跳过/失败: $skippedFiles 个文件\n";

// 创建ZIP压缩包
echo "\n📦 创建ZIP压缩包...\n";

$zipFileName = 'data-management-system-v1.0.0-' . date('Ymd-His') . '.zip';
$zipFilePath = __DIR__ . '/' . $zipFileName;

$zip = new ZipArchive();
if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    
    // 添加文件到ZIP
    addFilesToZip($targetDir, $zip, '');
    
    $zip->close();
    
    echo "✅ ZIP压缩包创建成功！\n";
    echo "📁 文件路径: $zipFilePath\n";
    echo "📊 文件大小: " . number_format(filesize($zipFilePath) / 1024 / 1024, 2) . " MB\n\n";
    
    // 显示ZIP内容统计
    showZipContents($zipFilePath);
    
} else {
    echo "❌ 创建ZIP文件失败！\n";
}

// 清理临时目录
echo "🧹 清理临时目录...\n";
removeDirectory($targetDir);

echo "\n🎉 数据管理系统安装包创建完成！\n";
echo "📦 包名: $zipFileName\n";
echo "🚀 现在可以使用此安装包进行部署了！\n\n";

/**
 * 递归复制目录
 */
function copyDirectory($source, $dest) {
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }
    
    $files = scandir($source);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $sourcePath = $source . '/' . $file;
        $destPath = $dest . '/' . $file;
        
        if (is_dir($sourcePath)) {
            copyDirectory($sourcePath, $destPath);
        } else {
            copy($sourcePath, $destPath);
        }
    }
}

/**
 * 递归删除目录
 */
function removeDirectory($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $filePath = $dir . '/' . $file;
        if (is_dir($filePath)) {
            removeDirectory($filePath);
        } else {
            unlink($filePath);
        }
    }
    
    rmdir($dir);
}

/**
 * 递归添加文件到ZIP
 */
function addFilesToZip($dir, $zip, $relativePath) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $filePath = $dir . '/' . $file;
        $zipPath = $relativePath . $file;
        
        if (is_dir($filePath)) {
            $zip->addEmptyDir($zipPath);
            addFilesToZip($filePath, $zip, $zipPath . '/');
        } else {
            $zip->addFile($filePath, $zipPath);
        }
    }
}

/**
 * 显示ZIP内容统计
 */
function showZipContents($zipPath) {
    $zip = new ZipArchive();
    if ($zip->open($zipPath) === TRUE) {
        $fileCount = $zip->numFiles;
        $totalSize = 0;
        
        echo "📊 ZIP包内容统计:\n";
        echo "  📄 文件总数: $fileCount\n";
        
        // 显示主要文件
        for ($i = 0; $i < min($fileCount, 15); $i++) {
            $filename = $zip->getNameIndex($i);
            $filesize = $zip->statIndex($i)['size'];
            $totalSize += $filesize;
            echo "  📄 $filename (" . number_format($filesize / 1024, 2) . " KB)\n";
        }
        
        if ($fileCount > 15) {
            echo "  ... 还有 " . ($fileCount - 15) . " 个文件\n";
        }
        
        echo "  📊 总大小: " . number_format($totalSize / 1024 / 1024, 2) . " MB\n";
        
        $zip->close();
    }
}

?>