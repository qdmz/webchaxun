# Web查询系统 - 部署文件说明

## 📁 目录结构

```
deploy/
├── README.md                     # 本文件 - 部署说明
├── 云服务器部署指南.md             # 完整的云服务器部署指南
├── server_requirements.txt        # 服务器环境要求
├── deploy.sh                    # 一键自动部署脚本
├── docker-compose.yml           # Docker编排配置
├── Dockerfile                   # Docker镜像构建文件
├── docker.env                   # Docker环境配置示例
├── nginx/                       # Nginx配置文件
│   ├── nginx.conf              # Nginx主配置
│   └── webchaxun.conf         # 项目站点配置
├── systemd/                     # 系统服务配置
│   └── webchaxun.service      # systemd服务配置
└── scripts/                     # 管理脚本
    ├── backup.sh               # 自动备份脚本
    └── ssl-setup.sh           # SSL证书配置脚本
```

## 🚀 快速开始

### 方式一：一键部署（推荐）

```bash
# 下载并运行部署脚本
wget https://raw.githubusercontent.com/qdmz/webchaxun/main/deploy/deploy.sh
chmod +x deploy.sh
./deploy.sh your-domain.com admin@example.com
```

### 方式二：Docker部署

```bash
# 进入deploy目录
cd webchaxun/deploy

# 配置环境变量
cp docker.env.example docker.env
nano docker.env

# 启动服务
docker-compose up -d
```

## 📋 文件说明

### deploy.sh
- **功能**: 一键自动部署脚本
- **适用系统**: Ubuntu 20.04/22.04, CentOS 7/8, Debian 10/11
- **特性**: 自动安装依赖、配置SSL、设置服务、创建备份

### docker-compose.yml
- **功能**: Docker容器编排
- **服务**: web, nginx, db, redis
- **特性**: 完整的生产环境配置

### nginx/
- **nginx.conf**: Nginx主配置，包含性能优化设置
- **webchaxun.conf**: 项目站点配置，包含SSL和安全头

### scripts/
- **backup.sh**: 自动备份脚本，支持数据库和文件备份
- **ssl-setup.sh**: SSL证书配置脚本，支持Let's Encrypt和自签名证书

### systemd/
- **webchaxun.service**: systemd服务配置，用于管理应用进程

## 🔧 配置说明

### 环境变量配置
```bash
# 生产环境必需配置
SECRET_KEY=your-secret-key-change-in-production
FLASK_ENV=production
DOMAIN=your-domain.com

# 数据库配置
DATABASE_URL=sqlite:///webchaxun.db
# 或使用PostgreSQL: postgresql://user:pass@host/db

# 系统配置
ENABLE_REGISTRATION=true
MAX_FILE_SIZE=16
ALLOWED_EXTENSIONS=xlsx,xls,csv
```

### SSL证书配置
- **Let's Encrypt**: 生产环境推荐，免费且自动续期
- **自签名证书**: 测试环境使用，浏览器会显示警告

### 备份策略
- **频率**: 每日凌晨2点自动备份
- **保留**: 保留30天的备份文件
- **内容**: 数据库、上传文件、配置文件

## 🛠️ 部署流程

1. **环境准备**
   - 更新系统
   - 安装依赖软件
   - 创建项目目录

2. **应用部署**
   - 克隆代码
   - 创建虚拟环境
   - 安装Python依赖
   - 配置环境变量

3. **服务配置**
   - 配置Nginx反向代理
   - 设置systemd服务
   - 配置SSL证书

4. **安全加固**
   - 设置防火墙
   - 配置文件权限
   - 设置备份策略

## 📊 监控维护

### 服务管理
```bash
# 查看服务状态
sudo systemctl status webchaxun nginx

# 查看日志
sudo journalctl -u webchaxun -f
sudo tail -f /var/log/nginx/webchaxun.access.log

# 重启服务
sudo systemctl restart webchaxun nginx
```

### 备份管理
```bash
# 手动备份
sudo /usr/local/bin/backup-webchaxun.sh

# 查看备份
ls -la /var/backups/webchaxun/

# 恢复备份
# 见部署指南中的备份恢复章节
```

## 🔒 安全配置

### 防火墙设置
```bash
# Ubuntu (ufw)
sudo ufw enable
sudo ufw allow 22,80,443/tcp

# CentOS (firewalld)
sudo firewall-cmd --permanent --add-service={ssh,http,https}
sudo firewall-cmd --reload
```

### SSL配置
- 强制HTTPS重定向
- HSTS安全头
- 证书自动续期

### 文件权限
- 应用文件: 755
- 配置文件: 600
- 上传目录: 777

## 🚨 故障排除

### 常见问题
1. **端口占用**: 检查80/443端口是否被占用
2. **权限问题**: 确保文件权限正确设置
3. **SSL证书**: 检查域名解析和证书有效期
4. **服务状态**: 查看systemd和nginx日志

### 日志位置
- 应用日志: `/var/log/webchaxun/`
- Nginx日志: `/var/log/nginx/webchaxun.*`
- 系统日志: `journalctl -u webchaxun`

## 📞 技术支持

- **项目地址**: https://github.com/qdmz/webchaxun
- **问题反馈**: https://github.com/qdmz/webchaxun/issues
- **邮箱联系**: qdmz@vip.qq.com

---

**注意**: 
- 生产环境部署前请仔细阅读《云服务器部署指南》
- 建议先在测试环境中验证部署流程
- 定期更新系统依赖和安全补丁