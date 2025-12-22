#!/bin/bash

# SSL证书配置脚本
# 支持Let's Encrypt和自签名证书

# 配置变量
DOMAIN=""
EMAIL=""
SSL_TYPE="letsencrypt"  # letsencrypt 或 self-signed
CERT_DIR="/etc/letsencrypt/live"
SSL_DIR="/etc/ssl"

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

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

# 检查参数
check_params() {
    if [[ -z "$DOMAIN" || -z "$EMAIL" ]]; then
        error "用法: $0 <域名> <邮箱> [证书类型]"
    fi
    
    if [[ -n "$3" ]]; then
        SSL_TYPE="$3"
    fi
    
    log "域名: $DOMAIN"
    log "邮箱: $EMAIL"
    log "证书类型: $SSL_TYPE"
}

# 检测系统
detect_system() {
    if command -v apt-get &> /dev/null; then
        PKG_MANAGER="apt-get"
    elif command -v yum &> /dev/null; then
        PKG_MANAGER="yum"
    elif command -v dnf &> /dev/null; then
        PKG_MANAGER="dnf"
    else
        error "不支持的包管理器"
    fi
    
    log "包管理器: $PKG_MANAGER"
}

# 安装必要软件
install_dependencies() {
    log "安装必要软件..."
    
    case $PKG_MANAGER in
        apt-get)
            sudo apt-get update
            sudo apt-get install -y certbot python3-certbot-nginx openssl
            ;;
        yum)
            sudo yum install -y epel-release
            sudo yum install -y certbot python3-certbot-nginx openssl
            ;;
        dnf)
            sudo dnf install -y certbot python3-certbot-nginx openssl
            ;;
    esac
}

# 获取Let's Encrypt证书
get_letsencrypt_cert() {
    log "获取Let's Encrypt SSL证书..."
    
    # 停止nginx以释放80端口
    sudo systemctl stop nginx
    
    # 获取证书
    if sudo certbot certonly --standalone -d "$DOMAIN" -d "www.$DOMAIN" --email "$EMAIL" --agree-tos --non-interactive; then
        log "✅ Let's Encrypt证书获取成功"
        
        # 设置自动续期
        (sudo crontab -l 2>/dev/null; echo "0 12 * * * /usr/bin/certbot renew --quiet --post-hook 'systemctl reload nginx'") | sudo crontab -
        log "✅ 自动续期已设置"
    else
        error "Let's Encrypt证书获取失败"
    fi
}

# 生成自签名证书
generate_self_signed_cert() {
    log "生成自签名SSL证书..."
    
    # 创建SSL目录
    sudo mkdir -p "$SSL_DIR/certs"
    sudo mkdir -p "$SSL_DIR/private"
    
    # 生成私钥
    sudo openssl genrsa -out "$SSL_DIR/private/$DOMAIN.key" 2048
    
    # 生成证书
    sudo openssl req -new -x509 -key "$SSL_DIR/private/$DOMAIN.key" \
        -out "$SSL_DIR/certs/$DOMAIN.crt" \
        -days 365 \
        -subj "/C=CN/ST=State/L=City/O=Organization/OU=Organizational Unit/CN=$DOMAIN"
    
    # 设置权限
    sudo chmod 600 "$SSL_DIR/private/$DOMAIN.key"
    sudo chmod 644 "$SSL_DIR/certs/$DOMAIN.crt"
    
    log "✅ 自签名证书生成完成"
    warning "⚠️  自签名证书会在浏览器中显示安全警告，仅用于测试环境"
}

# 配置Nginx SSL
configure_nginx_ssl() {
    log "配置Nginx SSL..."
    
    local cert_file
    local key_file
    
    if [[ "$SSL_TYPE" == "letsencrypt" ]]; then
        cert_file="$CERT_DIR/$DOMAIN/fullchain.pem"
        key_file="$CERT_DIR/$DOMAIN/privkey.pem"
    else
        cert_file="$SSL_DIR/certs/$DOMAIN.crt"
        key_file="$SSL_DIR/private/$DOMAIN.key"
    fi
    
    # 检查证书文件是否存在
    if [[ ! -f "$cert_file" || ! -f "$key_file" ]]; then
        error "SSL证书文件不存在"
    fi
    
    # 更新Nginx配置
    sudo sed -i "s|your-domain.com|$DOMAIN|g" /etc/nginx/sites-available/webchaxun
    sudo sed -i "s|/etc/ssl/certs/your-domain.crt|$cert_file|g" /etc/nginx/sites-available/webchaxun
    sudo sed -i "s|/etc/ssl/private/your-domain.key|$key_file|g" /etc/nginx/sites-available/webchaxun
    
    # 测试Nginx配置
    if sudo nginx -t; then
        log "✅ Nginx SSL配置正确"
        sudo systemctl reload nginx
    else
        error "Nginx配置有误"
    fi
}

# 验证SSL证书
verify_ssl() {
    log "验证SSL证书..."
    
    # 检查证书有效期
    local expiration_date
    if [[ "$SSL_TYPE" == "letsencrypt" ]]; then
        expiration_date=$(openssl x509 -in "$CERT_DIR/$DOMAIN/fullchain.pem" -noout -enddate | cut -d= -f2)
    else
        expiration_date=$(openssl x509 -in "$SSL_DIR/certs/$DOMAIN.crt" -noout -enddate | cut -d= -f2)
    fi
    
    log "证书有效期至: $expiration_date"
    
    # 测试HTTPS连接
    if command -v curl &> /dev/null; then
        if curl -s -o /dev/null -w "%{http_code}" "https://$DOMAIN" | grep -q "200"; then
            log "✅ HTTPS连接测试成功"
        else
            warning "⚠️  HTTPS连接测试失败，请检查配置"
        fi
    fi
}

# 创建SSL信息页面
create_ssl_info() {
    log "创建SSL信息页面..."
    
    local info_dir="/var/www/html"
    local info_file="$info_dir/ssl-info.html"
    
    sudo mkdir -p "$info_dir"
    
    cat > /tmp/ssl_info.html << EOF
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSL证书信息</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 800px; margin: 0 auto; }
        .status { padding: 20px; border-radius: 8px; margin: 20px 0; }
        .success { background-color: #d4edda; color: #155724; }
        .warning { background-color: #fff3cd; color: #856404; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <h1>SSL证书配置信息</h1>
        
        <div class="status success">
            <strong>状态:</strong> SSL证书已配置成功
        </div>
        
        <table>
            <tr><th>域名</th><td>$DOMAIN</td></tr>
            <tr><th>证书类型</th><td>$SSL_TYPE</td></tr>
            <tr><th>配置时间</th><td>$(date)</td></tr>
            <tr><th>证书文件</th><td>$cert_file</td></tr>
            <tr><th>私钥文件</th><td>$key_file</td></tr>
            <tr><th>有效期至</th><td>$expiration_date</td></tr>
        </table>
        
        <div class="status warning">
            <strong>注意:</strong> 
            <ul>
                <li>请定期检查证书有效期</li>
                <li>Let's Encrypt证书会自动续期</li>
                <li>自签名证书需要手动更新</li>
            </ul>
        </div>
        
        <p><a href="https://$DOMAIN" target="_blank">访问您的网站</a></p>
    </div>
</body>
</html>
EOF
    
    sudo mv /tmp/ssl_info.html "$info_file"
    log "SSL信息页面已创建: https://$DOMAIN/ssl-info.html"
}

# 显示使用说明
show_usage() {
    cat << EOF
SSL证书配置脚本

用法: $0 <域名> <邮箱> [证书类型]

参数:
  域名     - 您的域名 (例如: example.com)
  邮箱     - 用于证书申请的邮箱
  证书类型 - letsencrypt (默认) 或 self-signed

示例:
  $0 example.com admin@example.com
  $0 test.com admin@example.com self-signed

注意:
  - Let's Encrypt证书需要域名已解析到服务器
  - 自签名证书仅适用于测试环境
EOF
}

# 主函数
main() {
    if [[ $# -lt 2 ]]; then
        show_usage
        exit 1
    fi
    
    DOMAIN="$1"
    EMAIL="$2"
    SSL_TYPE="${3:-letsencrypt}"
    
    log "开始配置SSL证书..."
    
    # 执行配置步骤
    check_params "$@"
    detect_system
    install_dependencies
    
    if [[ "$SSL_TYPE" == "letsencrypt" ]]; then
        get_letsencrypt_cert
    else
        generate_self_signed_cert
    fi
    
    configure_nginx_ssl
    verify_ssl
    create_ssl_info
    
    log "🎉 SSL证书配置完成！"
    echo ""
    echo "配置摘要:"
    echo "域名: $DOMAIN"
    echo "证书类型: $SSL_TYPE"
    echo "访问地址: https://$DOMAIN"
    echo "SSL信息: https://$DOMAIN/ssl-info.html"
    echo ""
    if [[ "$SSL_TYPE" == "letsencrypt" ]]; then
        echo "Let's Encrypt证书已设置自动续期"
    else
        echo "⚠️  自签名证书需要手动更新"
    fi
}

# 运行主函数
main "$@"