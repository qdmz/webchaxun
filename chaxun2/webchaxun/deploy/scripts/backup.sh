#!/bin/bash

# Web查询系统自动备份脚本
# 用于备份数据库和上传文件

# 配置
PROJECT_NAME="webchaxun"
BACKUP_DIR="/var/backups/$PROJECT_NAME"
INSTALL_DIR="/var/www/$PROJECT_NAME"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 日志函数
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
}

# 创建备份目录
create_backup_dir() {
    if [[ ! -d "$BACKUP_DIR" ]]; then
        sudo mkdir -p "$BACKUP_DIR"
        sudo chmod 755 "$BACKUP_DIR"
        log "创建备份目录: $BACKUP_DIR"
    fi
}

# 备份数据库
backup_database() {
    log "开始备份数据库..."
    
    if [[ -f "$INSTALL_DIR/webchaxun.db" ]]; then
        # SQLite数据库备份
        cp "$INSTALL_DIR/webchaxun.db" "$BACKUP_DIR/webchaxun_$DATE.db"
        
        # 压缩数据库备份
        gzip "$BACKUP_DIR/webchaxun_$DATE.db"
        
        # 检查备份是否成功
        if [[ -f "$BACKUP_DIR/webchaxun_$DATE.db.gz" ]]; then
            log "数据库备份完成: webchaxun_$DATE.db.gz"
        else
            error "数据库备份失败"
            return 1
        fi
    else
        warning "未找到数据库文件: $INSTALL_DIR/webchaxun.db"
    fi
}

# 备份上传文件
backup_uploads() {
    log "开始备份上传文件..."
    
    if [[ -d "$INSTALL_DIR/uploads" ]]; then
        # 创建上传文件备份
        tar -czf "$BACKUP_DIR/uploads_$DATE.tar.gz" -C "$INSTALL_DIR" uploads
        
        # 检查备份是否成功
        if [[ -f "$BACKUP_DIR/uploads_$DATE.tar.gz" ]]; then
            log "上传文件备份完成: uploads_$DATE.tar.gz"
        else
            error "上传文件备份失败"
            return 1
        fi
    else
        warning "未找到上传目录: $INSTALL_DIR/uploads"
    fi
}

# 备份配置文件
backup_config() {
    log "开始备份配置文件..."
    
    if [[ -f "$INSTALL_DIR/.env" ]]; then
        # 备份环境配置
        cp "$INSTALL_DIR/.env" "$BACKUP_DIR/.env_$DATE"
        
        # 备份系统配置
        if [[ -f "/etc/systemd/system/$PROJECT_NAME.service" ]]; then
            cp "/etc/systemd/system/$PROJECT_NAME.service" "$BACKUP_DIR/$PROJECT_NAME.service_$DATE"
        fi
        
        if [[ -f "/etc/nginx/sites-available/$PROJECT_NAME" ]]; then
            cp "/etc/nginx/sites-available/$PROJECT_NAME" "$BACKUP_DIR/nginx_$PROJECT_NAME.conf_$DATE"
        fi
        
        log "配置文件备份完成"
    fi
}

# 清理旧备份
cleanup_old_backups() {
    log "清理 $RETENTION_DAYS 天前的备份..."
    
    # 清理数据库备份
    find "$BACKUP_DIR" -name "webchaxun_*.db.gz" -mtime +$RETENTION_DAYS -delete
    find "$BACKUP_DIR" -name "uploads_*.tar.gz" -mtime +$RETENTION_DAYS -delete
    find "$BACKUP_DIR" -name ".env_*" -mtime +$RETENTION_DAYS -delete
    find "$BACKUP_DIR" -name "*.conf_*" -mtime +$RETENTION_DAYS -delete
    
    log "旧备份清理完成"
}

# 生成备份报告
generate_report() {
    log "生成备份报告..."
    
    REPORT_FILE="$BACKUP_DIR/backup_report_$DATE.txt"
    
    cat > "$REPORT_FILE" << EOF
备份报告 - $(date)
============================

项目名称: $PROJECT_NAME
备份时间: $(date '+%Y-%m-%d %H:%M:%S')
备份目录: $BACKUP_DIR

备份文件列表:
$(ls -la "$BACKUP_DIR" | grep "$DATE")

磁盘使用情况:
$(du -sh "$BACKUP_DIR")

服务状态:
- Web查询系统: $(systemctl is-active $PROJECT_NAME)
- Nginx: $(systemctl is-active nginx)

备份完成时间: $(date '+%Y-%m-%d %H:%M:%S')
EOF

    log "备份报告生成完成: $REPORT_FILE"
}

# 验证备份
verify_backups() {
    log "验证备份文件完整性..."
    
    local errors=0
    
    # 验证数据库备份
    if [[ -f "$BACKUP_DIR/webchaxun_$DATE.db.gz" ]]; then
        if ! gzip -t "$BACKUP_DIR/webchaxun_$DATE.db.gz" 2>/dev/null; then
            error "数据库备份损坏"
            ((errors++))
        fi
    fi
    
    # 验证上传文件备份
    if [[ -f "$BACKUP_DIR/uploads_$DATE.tar.gz" ]]; then
        if ! tar -tzf "$BACKUP_DIR/uploads_$DATE.tar.gz" >/dev/null 2>&1; then
            error "上传文件备份损坏"
            ((errors++))
        fi
    fi
    
    if [[ $errors -eq 0 ]]; then
        log "备份验证通过"
    else
        error "备份验证失败，发现 $errors 个错误"
        return 1
    fi
}

# 发送通知（可选）
send_notification() {
    # 这里可以添加邮件或其他通知方式
    # 例如: 发送邮件、发送Webhook通知等
    log "备份通知功能可根据需要配置"
}

# 主函数
main() {
    log "开始执行备份任务..."
    
    # 执行备份步骤
    create_backup_dir
    backup_database || exit 1
    backup_uploads || exit 1
    backup_config
    cleanup_old_backups
    verify_backups || exit 1
    generate_report
    send_notification
    
    log "✅ 备份任务完成！"
    
    # 显示备份信息
    echo ""
    echo "备份摘要:"
    echo "备份目录: $BACKUP_DIR"
    echo "备份时间: $(date '+%Y-%m-%d %H:%M:%S')"
    echo "备份文件数: $(ls "$BACKUP_DIR" | grep "$DATE" | wc -l)"
    echo "总磁盘使用: $(du -sh "$BACKUP_DIR" | cut -f1)"
}

# 运行主函数
main "$@"