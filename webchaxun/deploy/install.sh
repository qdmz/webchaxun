#!/bin/bash

# Web查询系统 - 快速安装脚本
# 适用于开发和测试环境

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 项目配置
PROJECT_NAME="webchaxun"
INSTALL_DIR="$PWD"
VENV_DIR="$INSTALL_DIR/venv"

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

# 检查Python版本
check_python() {
    log "检查Python环境..."
    
    if ! command -v python3 &> /dev/null; then
        error "未找到Python3，请先安装Python 3.8+"
    fi
    
    PYTHON_VERSION=$(python3 -c 'import sys; print(".".join(map(str, sys.version_info[:2])))')
    REQUIRED_VERSION="3.8"
    
    if python3 -c "import sys; exit(0 if sys.version_info >= (3, 8) else 1)"; then
        log "Python版本检查通过: $PYTHON_VERSION"
    else
        error "Python版本过低 ($PYTHON_VERSION)，需要3.8+版本"
    fi
}

# 安装系统依赖
install_dependencies() {
    log "安装系统依赖..."
    
    if command -v apt-get &> /dev/null; then
        # Ubuntu/Debian
        sudo apt-get update
        sudo apt-get install -y python3-venv python3-pip git
    elif command -v yum &> /dev/null; then
        # CentOS/RHEL
        sudo yum install -y python3-venv python3-pip git
    elif command -v dnf &> /dev/null; then
        # Fedora
        sudo dnf install -y python3-venv python3-pip git
    elif command -v brew &> /dev/null; then
        # macOS
        brew install python3 git
    else
        warning "无法自动安装依赖，请手动安装Python3和git"
    fi
}

# 创建虚拟环境
create_venv() {
    log "创建Python虚拟环境..."
    
    if [[ -d "$VENV_DIR" ]]; then
        log "虚拟环境已存在，跳过创建"
    else
        python3 -m venv "$VENV_DIR"
        log "虚拟环境创建完成"
    fi
    
    # 激活虚拟环境
    source "$VENV_DIR/bin/activate"
    
    # 升级pip
    pip install --upgrade pip
}

# 安装Python依赖
install_python_deps() {
    log "安装Python依赖包..."
    
    source "$VENV_DIR/bin/activate"
    
    if [[ -f "requirements.txt" ]]; then
        pip install -r requirements.txt
        log "Python依赖安装完成"
    else
        error "未找到requirements.txt文件"
    fi
}

# 配置环境变量
configure_env() {
    log "配置环境变量..."
    
    if [[ ! -f ".env" ]]; then
        if [[ -f ".env.example" ]]; then
            cp .env.example .env
            
            # 生成随机密钥
            SECRET_KEY=$(python3 -c 'import secrets; print(secrets.token_hex(32))')
            
            # 更新配置
            sed -i "s/SECRET_KEY=your-secret-key-here/SECRET_KEY=$SECRET_KEY/" .env
            sed -i "s/DEBUG=True/DEBUG=False/" .env
            
            log "环境配置文件已创建: .env"
        else
            error "未找到.env.example文件"
        fi
    else
        log "环境配置文件已存在，跳过创建"
    fi
}

# 初始化应用
init_app() {
    log "初始化应用..."
    
    source "$VENV_DIR/bin/activate"
    
    # 运行安装脚本
    if [[ -f "install.py" ]]; then
        python install.py
    else
        error "未找到install.py文件"
    fi
}

# 创建启动脚本
create_start_script() {
    log "创建启动脚本..."
    
    cat > start.sh << EOF
#!/bin/bash
# Web查询系统启动脚本

# 激活虚拟环境
source "$VENV_DIR/bin/activate"

# 设置环境变量
export FLASK_ENV=production

# 启动应用
echo "启动Web查询系统..."
python run.py
EOF
    
    chmod +x start.sh
    log "启动脚本已创建: ./start.sh"
}

# 创建开发启动脚本
create_dev_script() {
    log "创建开发模式启动脚本..."
    
    cat > start-dev.sh << EOF
#!/bin/bash
# Web查询系统开发模式启动脚本

# 激活虚拟环境
source "$VENV_DIR/bin/activate"

# 设置开发环境
export FLASK_ENV=development
export DEBUG=True

# 启动开发服务器
echo "启动开发模式服务器..."
python run.py
EOF
    
    chmod +x start-dev.sh
    log "开发模式启动脚本已创建: ./start-dev.sh"
}

# 创建数据库备份脚本
create_backup_script() {
    log "创建数据库备份脚本..."
    
    cat > backup-db.sh << EOF
#!/bin/bash
# 数据库备份脚本

BACKUP_DIR="./backups"
DATE=\$(date +%Y%m%d_%H%M%S)

# 创建备份目录
mkdir -p \$BACKUP_DIR

# 备份数据库
if [[ -f "webchaxun.db" ]]; then
    cp webchaxun.db \$BACKUP_DIR/webchaxun_\$DATE.db
    gzip \$BACKUP_DIR/webchaxun_\$DATE.db
    echo "数据库备份完成: \$BACKUP_DIR/webchaxun_\$DATE.db.gz"
else
    echo "未找到数据库文件"
fi
EOF
    
    chmod +x backup-db.sh
    log "数据库备份脚本已创建: ./backup-db.sh"
}

# 检查安装结果
verify_installation() {
    log "验证安装结果..."
    
    source "$VENV_DIR/bin/activate"
    
    # 检查关键文件
    local errors=0
    
    if [[ ! -f ".env" ]]; then
        error "环境配置文件缺失"
        ((errors++))
    fi
    
    if [[ ! -d "$VENV_DIR" ]]; then
        error "虚拟环境未创建"
        ((errors++))
    fi
    
    if [[ ! -f "webchaxun.db" ]]; then
        error "数据库文件未创建"
        ((errors++))
    fi
    
    # 测试Python导入
    if python -c "from app import create_app; app = create_app(); print('✅ 应用导入成功')" 2>/dev/null; then
        log "✅ 应用验证通过"
    else
        error "❌ 应用验证失败"
        ((errors++))
    fi
    
    if [[ $errors -eq 0 ]]; then
        log "✅ 安装验证通过"
    else
        error "❌ 安装验证失败，发现 $errors 个错误"
    fi
}

# 显示安装结果
show_result() {
    log "🎉 安装完成！"
    echo ""
    echo "================================"
    echo "Web查询系统已成功安装！"
    echo "================================"
    echo ""
    echo "启动方式:"
    echo "  生产模式: ./start.sh"
    echo "  开发模式: ./start-dev.sh"
    echo ""
    echo "数据库备份:"
    echo "  备份数据库: ./backup-db.sh"
    echo "  备份目录: ./backups/"
    echo ""
    echo "配置文件:"
    echo "  环境配置: .env"
    echo "  虚拟环境: $VENV_DIR"
    echo "  数据库: webchaxun.db"
    echo ""
    echo "默认管理员账户:"
    echo "  用户名: admin"
    echo "  密码: admin123"
    echo ""
    echo "⚠️  安全提醒:"
    echo "  1. 请立即修改默认管理员密码"
    echo "  2. 请修改.env文件中的SECRET_KEY"
    echo "  3. 生产环境请使用HTTPS"
    echo ""
    echo "项目地址: https://github.com/qdmz/webchaxun"
    echo "================================"
}

# 显示使用帮助
show_usage() {
    cat << EOF
Web查询系统 - 快速安装脚本

用法: $0 [选项]

选项:
  --help, -h     显示帮助信息
  --dev          开发模式安装
  --no-deps      跳过系统依赖安装
  --update       更新现有安装

示例:
  $0              # 标准安装
  $0 --dev        # 开发模式安装
  $0 --no-deps    # 跳过系统依赖
  $0 --update     # 更新安装
EOF
}

# 主函数
main() {
    log "开始安装Web查询系统..."
    
    # 检查参数
    DEV_MODE=false
    SKIP_DEPS=false
    UPDATE_MODE=false
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            --help|-h)
                show_usage
                exit 0
                ;;
            --dev)
                DEV_MODE=true
                log "启用开发模式"
                shift
                ;;
            --no-deps)
                SKIP_DEPS=true
                log "跳过系统依赖安装"
                shift
                ;;
            --update)
                UPDATE_MODE=true
                log "更新模式"
                shift
                ;;
            *)
                error "未知参数: $1"
                ;;
        esac
    done
    
    # 检查是否在正确的目录
    if [[ ! -f "requirements.txt" || ! -f "install.py" ]]; then
        error "请在项目根目录运行此脚本"
    fi
    
    # 执行安装步骤
    check_python
    
    if [[ "$SKIP_DEPS" != true ]]; then
        install_dependencies
    fi
    
    create_venv
    install_python_deps
    configure_env
    init_app
    create_start_script
    create_dev_script
    create_backup_script
    verify_installation
    show_result
    
    if [[ "$DEV_MODE" == true ]]; then
        log "🚀 启动开发服务器..."
        ./start-dev.sh
    fi
}

# 运行主函数
main "$@"