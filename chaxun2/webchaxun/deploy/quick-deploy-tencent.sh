#!/bin/bash

# Web查询系统 - 腾讯轻量云广东区快速部署脚本

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 项目信息
PROJECT_NAME="webchaxun"
REPO_URL="https://github.com/qdmz/webchaxun.git"
INSTALL_DIR="/var/www/$PROJECT_NAME"
DOMAIN=""
EMAIL=""

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

info() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] INFO: $1${NC}"
}

# 显示横幅
show_banner() {
    echo -e "${GREEN}"
    echo "=================================================="
    echo "    Web查询系统 - 腾讯轻量云部署工具"
    echo "    地区: 广东区 (广州)"
    echo "    一键部署，无需复杂配置"
    echo "=================================================="
    echo -e "${NC}"
}

# 获取服务器信息
get_server_info() {
    log "获取服务器信息..."
    
    # 获取IP地址
    IP=$(curl -s ifconfig.me || curl -s ipinfo.io/ip || curl -s icanhazip.com)
    if [[ -z "$IP" ]]; then
        IP=$(ip route get 8.8.8.8 | awk '{print $7}' | head -1)
    fi
    
    info "服务器IP: $IP"
    
    # 获取系统信息
    OS=$(cat /etc/os-release | grep '^NAME=' | cut -d= -f2 | tr -d '"')
    VER=$(cat /etc/os-release | grep '^VERSION_ID=' | cut -d= -f2 | tr -d '"')
    
    info "操作系统: $OS $VER"
    
    # 检查腾讯云环境
    if dmidecode -s system-product-name 2>/dev/null | grep -qi "Tencent"; then
        info "检测到腾讯云服务器环境"
    fi
}

# 显示使用说明
show_usage() {
    cat << EOF
Web查询系统 - 腾讯轻量云广东区快速部署

用法: $0 [域名] [邮箱]

参数:
  域名     可选，您的域名 (如: example.com)
  邮箱     可选，用于SSL证书申请

示例:
  $0                           # 仅使用IP访问
  $0 example.com admin@example.com  # 使用域名和SSL证书

快速部署:
  1. 在腾讯云购买轻量服务器 (2核2G Ubuntu 22.04)
  2. SSH连接到服务器
  3. 运行: wget https://raw.githubusercontent.com/qdmz/webchaxun/main/deploy/quick-deploy-tencent.sh
  4. 运行: chmod +x quick-deploy-tencent.sh
  5. 运行: ./quick-deploy-tencent.sh

购买地址:
  https://cloud.tencent.com/product/lighthouse
EOF
}

# 主函数
main() {
    show_banner
    
    # 获取参数
    DOMAIN="$1"
    EMAIL="$2"
    
    if [[ -n "$DOMAIN" && -z "$EMAIL" ]]; then
        error "如果提供域名，必须同时提供邮箱地址"
    fi
    
    # 获取服务器信息
    get_server_info
    
    # 显示部署信息
    if [[ -n "$DOMAIN" ]]; then
        info "将部署到域名: $DOMAIN"
        info "邮箱: $EMAIL"
    else
        info "将使用IP地址访问"
    fi
    
    echo ""
    read -p "确认开始部署? (y/N): " confirm
    if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
        log "部署已取消"
        exit 0
    fi
    
    # 运行完整部署脚本
    log "开始部署..."
    
    # 下载并执行完整部署脚本
    if command -v wget &> /dev/null; then
        wget -O deploy-full.sh https://raw.githubusercontent.com/qdmz/webchaxun/main/deploy/deploy.sh
    elif command -v curl &> /dev/null; then
        curl -o deploy-full.sh https://raw.githubusercontent.com/qdmz/webchaxun/main/deploy/deploy.sh
    else
        error "需要wget或curl来下载部署脚本"
    fi
    
    chmod +x deploy-full.sh
    
    # 执行部署
    if [[ -n "$DOMAIN" ]]; then
        ./deploy-full.sh "$DOMAIN" "$EMAIL"
    else
        log "请手动配置域名后重新运行SSL证书配置"
        log "现在开始基础部署..."
        # 使用临时域名执行部署
        ./deploy-full.sh "temp.local" "temp@temp.com"
    fi
}

# 运行主函数
main "$@"