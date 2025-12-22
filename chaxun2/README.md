# webchaxun

一个简单且实用的基于 Flask 的 Excel 查询平台，支持管理员上传 Excel（.xls/.xlsx）、按关键词检索、按部门/用户设置文件可见性，以及用户管理与批量导入。

主要功能
- 用户注册 / 登录 / 修改密码
- 管理员上传、删除 Excel 文件并设置访问权限（按部门或指定用户）
- 按关键词全文检索 Excel 表格内容，展示结果并可下载对应文件
- 管理员用户管理：创建/删除/启用/禁用用户、批量导入用户
- 安全检查：限制文件类型与大小、使用安全文件名保存

目录结构（重要文件）
- `webchaxun/`：应用源代码和模板
  - `app.py`：主 Flask 应用
  - `init_db.py`：初始化数据库脚本（会创建初始 `admin` 用户）
  - `run.py`：开发运行入口
  - `templates/`：HTML 模板
  - `uploads/`：上传的 Excel 文件（运行时生成）
- `.venv/`：建议的虚拟环境（不应提交到仓库）

快速开始（开发环境）

1. 克隆仓库并进入目录：
```bash
git clone https://github.com/qdmz/webchaxun.git
cd webchaxun
```
2. 创建并激活虚拟环境，安装依赖：
```bash
python -m venv .venv
source .venv/bin/activate
pip install -r webchaxun/requirements.txt
```
3. 初始化数据库（将创建 `data.db` 及默认管理员 `admin` / 密码 `admin`；首次登录后请尽快修改管理员密码）：
```bash
python webchaxun/init_db.py
```
4. 启动开发服务器：
```bash
python webchaxun/run.py
# 或使用 flask run
```
5. 在浏览器打开： http://127.0.0.1:5000

演示流程（示例）
- 使用默认管理员登录：用户名 `admin`，密码 `admin`。
- 管理后台：`/admin`，可上传 Excel、管理用户与切换注册开关。
- 上传示例文件后，普通用户可登录并在“关键词查询”页检索内容。

安全与注意事项
- 上传限制：仅允许 `.xls` / `.xlsx`，并通过 `secure_filename` 清理文件名；单文件大小限制为 16 MB（可在 `app.py` 中调整）。
- 管理接口受 `is_admin` 字段控制，请妥善保管管理员账号。
- 本项目自带开发服务器，仅用于开发调试；生产请使用 WSGI 服务器（Gunicorn/uwsgi）并配合 Nginx。

生产部署建议（简要）
- 使用 Gunicorn 启动应用：
```bash
gunicorn -w 4 -b 0.0.0.0:8000 app:app
```
- 使用 Nginx 反向代理并处理静态文件与 TLS，示例配置见下：
```nginx
server {
    listen 80;
    server_name example.com;
    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
    location /uploads/ {
        alias /path/to/repo/webchaxun/uploads/;
    }
}
```

环境变量
- `SECRET_KEY`：请在生产中设置强随机值，覆盖默认开发用 `SECRET_KEY`。

扩展建议
- 将上传的文件存储迁移到专门的对象存储（S3、OSS）以便横向扩展。
- 为关键词检索增加索引或全文搜索引擎（Elasticsearch）以提升性能。
- 增加邮件找回密码与更严格的密码策略。

许可证
- 根据需要添加合适的 `LICENSE` 文件（当前仓库未强制指定）。

如需我帮你：
- 添加 GitHub Actions CI/CD，或
- 创建生产级 Dockerfile 与 docker-compose 配置，或
- 把仓库同步到其他远程（如 `qdmz/chaxun`），请告诉我。
