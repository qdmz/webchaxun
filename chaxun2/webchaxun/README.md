# Web查询系统

一个基于Flask的Excel文件数据查询与分析平台，支持用户管理、文件权限控制、数据搜索和导出功能。

## 功能特性

### 用户功能
- 用户注册和登录
- 修改个人资料和密码
- 查看可访问的Excel文件列表
- 按关键词搜索Excel数据
- 查看和下载查询结果
- 导出搜索结果为Excel文件

### 管理功能
- 管理员登录和权限控制
- 上传和管理Excel文件
- 设置文件访问权限
- 用户管理（增删改查）
- 批量导入用户
- 系统参数配置
- 开放/关闭注册开关

### 安全特性
- 用户认证和权限控制
- 文件类型检查
- 文件大小限制
- 文件名安全处理
- 细粒度权限管理

## 技术栈

- **后端**: Flask, SQLAlchemy, Flask-Login
- **前端**: Bootstrap 5, jQuery, Font Awesome
- **数据处理**: pandas, openpyxl
- **数据库**: SQLite (默认), MySQL, PostgreSQL
- **部署**: Gunicorn

## 快速开始

### 1. 环境要求

- Python 3.8+
- pip

### 2. 安装步骤

```bash
# 1. 克隆项目
git clone https://github.com/qdmz/webchaxun.git
cd webchaxun

# 2. 创建虚拟环境
python -m venv venv

# 3. 激活虚拟环境
# Windows:
venv\Scripts\activate
# Linux/Mac:
source venv/bin/activate

# 4. 安装依赖
pip install -r requirements.txt

# 5. 配置环境变量
cp .env.example .env
# 编辑 .env 文件，设置必要的配置

# 6. 初始化数据库
python -c "from app import create_app; app = create_app(); app.app_context().push(); from app import db; db.create_all()"

# 7. 运行应用
python run.py
```

### 3. 访问系统

- 访问地址: http://localhost:5000
- 默认管理员账户: admin / admin123

## 详细部署指南

### 开发环境部署

1. **设置环境变量**
   ```bash
   # 复制环境配置文件
   cp .env.example .env
   
   # 修改 .env 文件中的配置
   SECRET_KEY=your-very-secret-key-here
   DEBUG=True
   ```

2. **数据库初始化**
   ```bash
   # 确保安装了所有依赖
   pip install -r requirements.txt
   
   # 初始化数据库和创建管理员账户
   python -c "
   from app import create_app, db
   from app.models.user import User, SystemConfig
   
   app = create_app()
   with app.app_context():
       db.create_all()
       
       # 创建默认管理员
       if not User.query.filter_by(username='admin').first():
           admin = User(
               username='admin',
               email='admin@example.com',
               is_admin=True,
               is_active=True
           )
           admin.set_password('admin123')
           db.session.add(admin)
           db.session.commit()
       
       # 设置默认系统配置
       SystemConfig.set_config('enable_registration', 'true', '开放注册开关')
       SystemConfig.set_config('max_file_size', '16', '最大文件大小(MB)')
       SystemConfig.set_config('allowed_extensions', 'xlsx,xls,csv', '允许的文件扩展名')
       print('数据库初始化完成！')
   "
   ```

3. **启动开发服务器**
   ```bash
   python run.py
   ```

### 生产环境部署

1. **使用Gunicorn部署**
   ```bash
   # 安装Gunicorn
   pip install gunicorn
   
   # 启动应用
   gunicorn -w 4 -b 0.0.0.0:5000 run:app
   ```

2. **使用systemd服务**
   ```bash
   # 创建服务文件 /etc/systemd/system/webchaxun.service
   [Unit]
   Description=Web查询系统
   After=network.target
   
   [Service]
   User=www-data
   Group=www-data
   WorkingDirectory=/path/to/webchaxun
   Environment=PATH=/path/to/webchaxun/venv/bin
   ExecStart=/path/to/webchaxun/venv/bin/gunicorn -w 4 -b 0.0.0.0:5000 run:app
   Restart=always
   
   [Install]
   WantedBy=multi-user.target
   
   # 启动服务
   sudo systemctl enable webchaxun
   sudo systemctl start webchaxun
   ```

3. **使用Nginx反向代理**
   ```nginx
   server {
       listen 80;
       server_name your-domain.com;
       
       location / {
           proxy_pass http://127.0.0.1:5000;
           proxy_set_header Host $host;
           proxy_set_header X-Real-IP $remote_addr;
           proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
           proxy_set_header X-Forwarded-Proto $scheme;
       }
       
       location /static {
           alias /path/to/webchaxun/static;
           expires 1y;
           add_header Cache-Control "public, immutable";
       }
   }
   ```

## 数据库配置

### SQLite (默认)
```
DATABASE_URL=sqlite:///webchaxun.db
```

### MySQL
```
DATABASE_URL=mysql://username:password@localhost/webchaxun
```

### PostgreSQL
```
DATABASE_URL=postgresql://username:password@localhost/webchaxun
```

## 目录结构

```
webchaxun/
├── app/                    # 应用主目录
│   ├── controllers/        # 控制器
│   │   ├── auth.py        # 认证相关
│   │   ├── main.py        # 主要功能
│   │   ├── admin.py       # 管理功能
│   │   └── api.py         # API接口
│   ├── models/           # 数据模型
│   │   ├── user.py       # 用户模型
│   │   └── excel_file.py # 文件模型
│   └── __init__.py       # 应用初始化
├── static/               # 静态文件
│   ├── css/
│   ├── js/
│   └── images/
├── templates/            # 模板文件
│   ├── auth/
│   ├── admin/
│   └── ...
├── uploads/              # 上传文件目录
├── config/               # 配置文件
├── logs/                 # 日志文件
├── requirements.txt      # Python依赖
├── run.py               # 启动文件
├── .env.example         # 环境变量示例
└── README.md            # 说明文档
```

## 常见问题

### Q: 如何修改上传文件大小限制？
A: 在 `.env` 文件中修改 `MAX_FILE_SIZE` 配置，单位为MB。

### Q: 如何备份数据库？
A: 直接复制数据库文件，或使用相应数据库的备份工具。

### Q: 如何重置管理员密码？
A: 使用Python命令行重置：
```python
from app import create_app, db
from app.models.user import User

app = create_app()
with app.app_context():
    admin = User.query.filter_by(username='admin').first()
    if admin:
        admin.set_password('newpassword')
        db.session.commit()
```

## 开发指南

### 添加新功能
1. 在相应的控制器中添加路由
2. 创建或修改数据模型
3. 添加对应的模板文件
4. 更新静态文件（CSS/JS）

### 代码规范
- 遵循PEP 8代码规范
- 使用有意义的变量名和函数名
- 添加适当的注释和文档字符串

## 许可证

本项目采用MIT许可证，详情请参阅LICENSE文件。

## 贡献

欢迎提交Issue和Pull Request来改进项目。

## 联系方式

- 项目地址: https://github.com/qdmz/webchaxun
- 邮箱: qdmz@vip.qq.com