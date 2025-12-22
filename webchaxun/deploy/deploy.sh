#!/bin/bash

# Web查询系统 - 云服务器自动部署脚本
# 适用于 Ubuntu 20.04/22.04, CentOS 7/8, Debian 10/11

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 配置变量
PROJECT_NAME="webchaxun"
REPO_URL="https://github.com/qdmz/webchaxun.git"
DOMAIN="your-domain.com"  # 请修改为您的域名
EMAIL="admin@example.com"  # 请修改为您的邮箱
INSTALL_DIR="/var/www/$PROJECT_NAME"
PYTHON_VERSION="3.8"

# 日志函数
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
    exit 1
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
}

# 检查是否为root用户
check_root() {
    if [[ $EUID -eq 0 ]]; then
        error "请不要使用root用户运行此脚本，请使用普通用户运行"
    fi
}

# 检测操作系统
detect_os() {
    if [[ -f /etc/os-release ]]; then
        . /etc/os-release
        OS=$NAME
        VER=$VERSION_ID
    else
        error "无法检测操作系统版本"
    fi
    log "检测到操作系统: $OS $VER"
}

# 更新系统
update_system() {
    log "更新系统软件包..."
    if command -v apt-get &> /dev/null; then
        sudo apt-get update && sudo apt-get upgrade -y
    elif command -v yum &> /dev/null; then
        sudo yum update -y
    elif command -v dnf &> /dev/null; then
        sudo dnf update -y
    else
        error "不支持的包管理器"
    fi
}

# 安装系统依赖
install_system_dependencies() {
    log "安装系统依赖..."
    
    if command -v apt-get &> /dev/null; then
        # Ubuntu/Debian
        sudo apt-get install -y \
            python3 \
            python3-pip \
            python3-venv \
            git \
            nginx \
            curl \
            wget \
            htop \
            supervisor \
            certbot \
            python3-certbot-nginx
    elif command -v yum &> /dev/null; then
        # CentOS 7
        sudo yum install -y \
            python3 \
            python3-pip \
            git \
            nginx \
            curl \
            wget \
            htop \
            supervisor
        # 安装EPEL仓库用于certbot
        sudo yum install -y epel-release
        sudo yum install -y certbot python3-certbot-nginx
    elif command -v dnf &> /dev/null; then
        # CentOS 8/Fedora
        sudo dnf install -y \
            python3 \
            python3-pip \
            git \
            nginx \
            curl \
            wget \
            htop \
            supervisor \
            certbot \
            python3-certbot-nginx
    fi
}

# 创建项目目录
create_project_directory() {
    log "创建项目目录: $INSTALL_DIR"
    sudo mkdir -p $INSTALL_DIR
    sudo chown $USER:$USER $INSTALL_DIR
}

# 克隆代码
clone_repository() {
    log "克隆项目代码..."
    if [[ -d "$INSTALL_DIR/.git" ]]; then
        log "项目已存在，更新代码..."
        cd $INSTALL_DIR
        git pull origin main
    else
        log "首次克隆项目..."
        git clone $REPO_URL $INSTALL_DIR
        cd $INSTALL_DIR
    fi
}

# 创建Python虚拟环境
create_venv() {
    log "创建Python虚拟环境..."
    cd $INSTALL_DIR
    python3 -m venv venv
    source venv/bin/activate
    
    # 升级pip
    pip install --upgrade pip
    
    # 安装依赖
    pip install -r requirements.txt
}

# 配置环境变量
configure_environment() {
    log "配置环境变量..."
    cd $INSTALL_DIR
    
    # 复制环境配置文件
    if [[ ! -f .env ]]; then
        cp .env.example .env
        
        # 生成随机密钥
        SECRET_KEY=$(python3 -c 'import secrets; print(secrets.token_hex(32))')
        
        # 更新环境配置
        sed -i "s/SECRET_KEY=your-secret-key-here/SECRET_KEY=$SECRET_KEY/" .env
        sed -i "s/DEBUG=True/DEBUG=False/" .env
        sed -i "s/DOMAIN=.*/DOMAIN=$DOMAIN/" .env
        
        log "已创建并配置.env文件，请根据需要修改其他配置"
    fi
}

# 初始化应用
initialize_application() {
    log "初始化应用..."
    cd $INSTALL_DIR
    source venv/bin/activate
    
    # 运行安装脚本
    python install.py
}

# 配置Nginx
configure_nginx() {
    log "配置Nginx..."
    
    sudo tee /etc/nginx/sites-available/$PROJECT_NAME > /dev/null <<EOF
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;
    
    # 重定向到HTTPS
    return 301 https://\$server_name\$request_uri;
}

server {
    listen 443 ssl http2;
    server_name $DOMAIN www.$DOMAIN;
    
    # SSL证书配置
    ssl_certificate /etc/letsencrypt/live/$DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN/privkey.pem;
    
    # SSL安全配置
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # 安全头
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # 静态文件
    location /static {
        alias $INSTALL_DIR/static;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # 上传文件
    location /uploads {
        alias $INSTALL_DIR/uploads;
        expires 1y;
        add_header Cache-Control "public";
    }
    
    # 应用代理
    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_redirect off;
        
        # 超时设置
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
    
    # 日志
    access_log /var/log/nginx/$PROJECT_NAME.access.log;
    error_log /var/log/nginx/$PROJECT_NAME.error.log;
}
EOF

    # 启用站点
    sudo ln -sf /etc/nginx/sites-available/$PROJECT_NAME /etc/nginx/sites-enabled/
    sudo rm -f /etc/nginx/sites-enabled/default
    
    # 测试配置
    sudo nginx -t
    if [[ $? -eq 0 ]]; then
        log "Nginx配置正确"
    else
        error "Nginx配置有误"
    fi
}

# 配置systemd服务
configure_systemd() {
    log "配置systemd服务..."
    
    sudo tee /etc/systemd/system/$PROJECT_NAME.service > /dev/null <<EOF
[Unit]
Description=Web查询系统
After=network.target

[Service]
Type=exec
User=$USER
Group=$USER
WorkingDirectory=$INSTALL_DIR
Environment=PATH=$INSTALL_DIR/venv/bin
Environment=FLASK_ENV=production
ExecStart=$INSTALL_DIR/venv/bin/gunicorn -w 4 -b 127.0.0.1:8000 --timeout 120 run:app
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

    sudo systemctl daemon-reload
    sudo systemctl enable $PROJECT_NAME
}

# 获取SSL证书
obtain_ssl_certificate() {
    log "获取SSL证书..."
    
    # 先启动Nginx
    sudo systemctl start nginx
    
    # 获取证书
    sudo certbot --nginx -d $DOMAIN -d www.$DOMAIN --non-interactive --agree-tos --email $EMAIL
    
    # 设置自动续期
    sudo crontab -l | grep -q "certbot renew" || (sudo crontab -l; echo "0 12 * * * /usr/bin/certbot renew --quiet") | sudo crontab -
}

# 设置文件权限
set_permissions() {
    log "设置文件权限..."
    
    sudo chown -R $USER:$USER $INSTALL_DIR
    sudo chmod -R 755 $INSTALL_DIR
    sudo chmod -R 777 $INSTALL_DIR/uploads
    sudo chmod -R 777 $INSTALL_DIR/logs
}

# 启动服务
start_services() {
    log "启动服务..."
    
    # 启动应用服务
    sudo systemctl start $PROJECT_NAME
    sudo systemctl status $PROJECT_NAME --no-pager
    
    # 重启Nginx
    sudo systemctl restart nginx
    sudo systemctl status nginx --no-pager
    
    # 检查服务状态
    if sudo systemctl is-active --quiet $PROJECT_NAME; then
        log "✅ 应用服务启动成功"
    else
        error "❌ 应用服务启动失败"
    fi
    
    if sudo systemctl is-active --quiet nginx; then
        log "✅ Nginx服务启动成功"
    else
        error "❌ Nginx服务启动失败"
    fi
}

# 设置防火墙
configure_firewall() {
    log "配置防火墙..."
    
    if command -v ufw &> /dev/null; then
        # Ubuntu
        sudo ufw allow 22/tcp
        sudo ufw allow 80/tcp
        sudo ufw allow 443/tcp
        sudo ufw --force enable
    elif command -v firewall-cmd &> /dev/null; then
        # CentOS
        sudo firewall-cmd --permanent --add-service=ssh
        sudo firewall-cmd --permanent --add-service=http
        sudo firewall-cmd --permanent --add-service=https
        sudo firewall-cmd --reload
    fi
}

# 创建备份脚本
create_backup_script() {
    log "创建备份脚本..."
    
    sudo tee /usr/local/bin/backup-$PROJECT_NAME.sh > /dev/null <<EOF
#!/bin/bash

# 备份脚本
BACKUP_DIR="/var/backups/$PROJECT_NAME"
DATE=\$(date +%Y%m%d_%H%M%S)

# 创建备份目录
sudo mkdir -p \$BACKUP_DIR

# 备份数据库
cp $INSTALL_DIR/webchaxun.db \$BACKUP_DIR/webchaxun_\$DATE.db

# 备份上传文件
sudo tar -czf \$BACKUP_DIR/uploads_\$DATE.tar.gz -C $INSTALL_DIR uploads

# 删除7天前的备份
find \$BACKUP_DIR -name "*.db" -mtime +7 -delete
find \$BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "备份完成: \$DATE"
EOF

    sudo chmod +x /usr/local/bin/backup-$PROJECT_NAME.sh
    
    # 设置每日备份
    sudo crontab -l | grep "backup-$PROJECT_NAME.sh" || (sudo crontab -l; echo "0 2 * * * /usr/local/bin/backup-$PROJECT_NAME.sh") | sudo crontab -
}

# 显示安装结果
show_result() {
    log "🎉 部署完成！"
    echo ""
    echo "================================"
    echo "Web查询系统已成功部署！"
    echo "================================"
    echo "访问地址: https://$DOMAIN"
    echo "管理员账户: admin"
    echo "管理员密码: admin123"
    echo ""
    echo "重要提示:"
    echo "1. 请立即登录并修改管理员密码"
    echo "2. 请根据需要修改.env文件中的配置"
    echo "3. 备份脚本已设置为每日凌晨2点执行"
    echo "4. SSL证书已配置自动续期"
    echo ""
    echo "服务管理命令:"
    echo "启动服务: sudo systemctl start $PROJECT_NAME"
    echo "停止服务: sudo systemctl stop $PROJECT_NAME"
    echo "重启服务: sudo systemctl restart $PROJECT_NAME"
    echo "查看状态: sudo systemctl status $PROJECT_NAME"
    echo "查看日志: sudo journalctl -u $PROJECT_NAME -f"
    echo ""
    echo "配置文件位置:"
    echo "Nginx配置: /etc/nginx/sites-available/$PROJECT_NAME"
    echo "服务配置: /etc/systemd/system/$PROJECT_NAME.service"
    echo "应用配置: $INSTALL_DIR/.env"
    echo "================================"
}

# 主函数
main() {
    log "开始部署Web查询系统..."
    
    # 检查参数
    if [[ $# -eq 2 ]]; then
        DOMAIN=$1
        EMAIL=$2
        log "使用域名: $DOMAIN, 邮箱: $EMAIL"
    else
        error "用法: $0 <域名> <邮箱>"
    fi
    
    # 执行部署步骤
    check_root
    detect_os
    update_system
    install_system_dependencies
    create_project_directory
    clone_repository
    create_venv
    configure_environment
    initialize_application
    configure_nginx
    configure_systemd
    obtain_ssl_certificate
    set_permissions
    configure_firewall
    start_services
    create_backup_script
    show_result
}

# 运行主函数
main "$@"