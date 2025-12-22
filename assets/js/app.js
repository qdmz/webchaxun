/*!
 * 数据管理系统 - 主JavaScript文件
 * 版本: 1.0.0
 * 现代化的交互功能和用户体验
 */

// 全局变量
const App = {
    config: {
        apiBase: '/api',
        csrfToken: null,
        theme: localStorage.getItem('theme') || 'light'
    },
    
    // 初始化应用
    init() {
        this.initTheme();
        this.initEventListeners();
        this.initAnimations();
        this.initTooltips();
        this.initFormValidation();
        this.initNotifications();
        console.log('数据管理系统已初始化');
    },
    
    // 主题管理
    initTheme() {
        document.body.setAttribute('data-theme', this.config.theme);
        
        // 创建主题切换按钮
        const themeToggle = document.createElement('button');
        themeToggle.className = 'theme-toggle';
        themeToggle.innerHTML = this.config.theme === 'light' ? '🌙' : '☀️';
        themeToggle.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            background: rgba(255, 255, 255, 0.9);
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            font-size: 20px;
            z-index: 1000;
            transition: all 0.3s ease;
        `;
        
        themeToggle.addEventListener('click', () => {
            this.toggleTheme();
            themeToggle.innerHTML = this.config.theme === 'light' ? '🌙' : '☀️';
        });
        
        document.body.appendChild(themeToggle);
    },
    
    toggleTheme() {
        this.config.theme = this.config.theme === 'light' ? 'dark' : 'light';
        document.body.setAttribute('data-theme', this.config.theme);
        localStorage.setItem('theme', this.config.theme);
        
        // 添加切换动画
        document.body.style.transition = 'all 0.3s ease';
        setTimeout(() => {
            document.body.style.transition = '';
        }, 300);
    },
    
    // 事件监听器初始化
    initEventListeners() {
        // 平滑滚动
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(anchor.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // 表单提交处理
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                    return false;
                }
                this.handleFormSubmit(form, e);
            });
        });
        
        // 文件上传处理
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', (e) => {
                this.handleFileUpload(e);
            });
        });
        
        // 导航高亮
        this.updateNavigationHighlight();
        
        // 监听滚动事件
        window.addEventListener('scroll', this.handleScroll.bind(this));
        
        // 键盘快捷键
        document.addEventListener('keydown', this.handleKeyboardShortcuts.bind(this));
    },
    
    // 动画效果初始化
    initAnimations() {
        // 淡入动画
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        // 观察所有卡片和元素
        document.querySelectorAll('.card, .stat-card, .btn').forEach(el => {
            observer.observe(el);
        });
        
        // 数字递增动画
        this.initCounterAnimation();
    },
    
    // 工具提示初始化
    initTooltips() {
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            element.classList.add('tooltip');
        });
    },
    
    // 表单验证初始化
    initFormValidation() {
        // 实时验证
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('blur', () => {
                this.validateField(input);
            });
            
            input.addEventListener('input', () => {
                if (input.classList.contains('error')) {
                    this.validateField(input);
                }
            });
        });
    },
    
    // 通知系统初始化
    initNotifications() {
        this.createNotificationContainer();
    },
    
    createNotificationContainer() {
        const container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            max-width: 500px;
        `;
        document.body.appendChild(container);
    },
    
    // 表单验证
    validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('.form-input[required]');
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    },
    
    validateField(input) {
        const value = input.value.trim();
        const type = input.type;
        const required = input.hasAttribute('required');
        
        // 清除之前的错误状态
        input.classList.remove('error');
        this.removeError(input);
        
        // 必填验证
        if (required && !value) {
            this.showError(input, '此字段为必填项');
            return false;
        }
        
        // 类型验证
        switch (type) {
            case 'email':
                if (value && !this.isValidEmail(value)) {
                    this.showError(input, '请输入有效的邮箱地址');
                    return false;
                }
                break;
            case 'tel':
                if (value && !this.isValidPhone(value)) {
                    this.showError(input, '请输入有效的手机号码');
                    return false;
                }
                break;
            case 'password':
                if (value.length < 6) {
                    this.showError(input, '密码至少需要6个字符');
                    return false;
                }
                break;
        }
        
        return true;
    },
    
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },
    
    isValidPhone(phone) {
        const phoneRegex = /^1[3-9]\d{9}$/;
        return phoneRegex.test(phone.replace(/\D/g, ''));
    },
    
    showError(input, message) {
        input.classList.add('error');
        input.style.borderColor = '#ef4444';
        
        const errorElement = document.createElement('div');
        errorElement.className = 'error-message';
        errorElement.textContent = message;
        errorElement.style.cssText = `
            color: #ef4444;
            font-size: 12px;
            margin-top: 4px;
            display: block;
        `;
        
        input.parentNode.appendChild(errorElement);
    },
    
    removeError(input) {
        const errorElement = input.parentNode.querySelector('.error-message');
        if (errorElement) {
            errorElement.remove();
        }
        input.style.borderColor = '';
    },
    
    // 表单提交处理
    handleFormSubmit(form, event) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading"></span> 提交中...';
        }
        
        // 显示通知
        this.showNotification('正在处理...', 'info');
        
        // 模拟异步提交
        setTimeout(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '提交';
            }
            
            this.showNotification('提交成功！', 'success');
            
            // 如果是登录表单，重定向
            if (form.classList.contains('login-form')) {
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 1500);
            }
        }, 2000);
    },
    
    // 文件上传处理
    handleFileUpload(event) {
        const input = event.target;
        const file = input.files[0];
        
        if (!file) return;
        
        // 文件大小验证
        const maxSize = 10 * 1024 * 1024; // 10MB
        if (file.size > maxSize) {
            this.showNotification('文件大小不能超过10MB', 'error');
            input.value = '';
            return;
        }
        
        // 文件类型验证
        const allowedTypes = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'];
        if (!allowedTypes.includes(file.type)) {
            this.showNotification('请上传Excel或CSV文件', 'error');
            input.value = '';
            return;
        }
        
        // 显示文件信息
        const fileInfo = document.createElement('div');
        fileInfo.className = 'file-info';
        fileInfo.innerHTML = `
            <div style="margin-top: 10px; padding: 10px; background: rgba(102, 126, 234, 0.1); border-radius: 8px;">
                <strong>文件名:</strong> ${file.name}<br>
                <strong>大小:</strong> ${this.formatFileSize(file.size)}<br>
                <strong>类型:</strong> ${file.type}
            </div>
        `;
        
        // 移除之前的文件信息
        const existingInfo = input.parentNode.querySelector('.file-info');
        if (existingInfo) {
            existingInfo.remove();
        }
        
        input.parentNode.appendChild(fileInfo);
    },
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    // 滚动处理
    handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // 添加滚动阴影效果
        if (scrollTop > 10) {
            document.body.classList.add('scrolled');
        } else {
            document.body.classList.remove('scrolled');
        }
    },
    
    // 键盘快捷键
    handleKeyboardShortcuts(event) {
        // Ctrl/Cmd + K: 搜索
        if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
            event.preventDefault();
            const searchInput = document.querySelector('input[type="search"], input[placeholder*="搜索"]');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Escape: 关闭模态框
        if (event.key === 'Escape') {
            const modals = document.querySelectorAll('.modal.active');
            modals.forEach(modal => {
                this.closeModal(modal);
            });
        }
    },
    
    // 导航高亮
    updateNavigationHighlight() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPath || 
                (href !== '/' && currentPath.startsWith(href))) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    },
    
    // 数字递增动画
    initCounterAnimation() {
        const counters = document.querySelectorAll('.stat-value');
        
        counters.forEach(counter => {
            const target = parseInt(counter.textContent) || 0;
            const increment = target / 100;
            let current = 0;
            
            const updateCounter = () => {
                current += increment;
                if (current < target) {
                    counter.textContent = Math.ceil(current);
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = target.toLocaleString();
                }
            };
            
            // 使用IntersectionObserver触发动画
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        updateCounter();
                        observer.unobserve(entry.target);
                    }
                });
            });
            
            observer.observe(counter);
        });
    },
    
    // 通知系统
    showNotification(message, type = 'info', duration = 3000) {
        const container = document.getElementById('notification-container');
        if (!container) return;
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 10px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            border-left: 4px solid ${this.getNotificationColor(type)};
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s ease;
        `;
        
        notification.innerHTML = `
            <span style="font-size: 20px;">${this.getNotificationIcon(type)}</span>
            <span style="flex: 1;">${message}</span>
            <button onclick="this.parentNode.remove()" style="background: none; border: none; cursor: pointer; font-size: 16px;">×</button>
        `;
        
        container.appendChild(notification);
        
        // 触发动画
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
            notification.style.opacity = '1';
        }, 10);
        
        // 自动移除
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, duration);
    },
    
    getNotificationColor(type) {
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };
        return colors[type] || colors.info;
    },
    
    getNotificationIcon(type) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        return icons[type] || icons.info;
    },
    
    // 模态框管理
    openModal(content, title = '') {
        // 移除现有模态框
        this.closeAllModals();
        
        const modal = document.createElement('div');
        modal.className = 'modal active';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9998;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;
        
        modal.innerHTML = `
            <div style="background: white; border-radius: 16px; padding: 24px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; transform: scale(0.9); transition: transform 0.3s ease;">
                ${title ? `<h3 style="margin-top: 0; margin-bottom: 16px;">${title}</h3>` : ''}
                <div>${content}</div>
                <button onclick="App.closeModal(this.closest('.modal'))" style="margin-top: 16px; padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer;">关闭</button>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // 触发动画
        setTimeout(() => {
            modal.style.opacity = '1';
            modal.querySelector('div').style.transform = 'scale(1)';
        }, 10);
    },
    
    closeModal(modal) {
        if (modal) {
            modal.style.opacity = '0';
            modal.querySelector('div').style.transform = 'scale(0.9)';
            setTimeout(() => {
                modal.remove();
            }, 300);
        }
    },
    
    closeAllModals() {
        const modals = document.querySelectorAll('.modal.active');
        modals.forEach(modal => {
            this.closeModal(modal);
        });
    },
    
    // API调用辅助函数
    async apiCall(endpoint, options = {}) {
        const url = `${this.config.apiBase}${endpoint}`;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            }
        };
        
        if (this.config.csrfToken) {
            defaultOptions.headers['X-CSRF-Token'] = this.config.csrfToken;
        }
        
        const finalOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };
        
        try {
            const response = await fetch(url, finalOptions);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || '请求失败');
            }
            
            return data;
        } catch (error) {
            this.showNotification(error.message, 'error');
            throw error;
        }
    },
    
    // 数据格式化
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('zh-CN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    formatCurrency(amount) {
        return new Intl.NumberFormat('zh-CN', {
            style: 'currency',
            currency: 'CNY'
        }).format(amount);
    }
};

// DOM加载完成后初始化
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});

// 导出到全局
window.App = App;