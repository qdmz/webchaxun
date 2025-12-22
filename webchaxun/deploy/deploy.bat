@echo off
REM Web查询系统 - Windows部署脚本

echo ========================================
echo Web查询系统 - Windows部署工具
echo ========================================
echo.

REM 检查Python是否安装
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [错误] 未找到Python，请先安装Python 3.8+
    pause
    exit /b 1
)

echo [信息] 检测到Python已安装
python --version

REM 创建虚拟环境
echo.
echo [信息] 创建Python虚拟环境...
if not exist "venv" (
    python -m venv venv
    echo [成功] 虚拟环境创建完成
) else (
    echo [信息] 虚拟环境已存在
)

REM 激活虚拟环境
echo.
echo [信息] 激活虚拟环境...
call venv\Scripts\activate.bat

REM 升级pip
echo.
echo [信息] 升级pip...
python -m pip install --upgrade pip

REM 安装依赖
echo.
echo [信息] 安装Python依赖包...
if exist "requirements.txt" (
    pip install -r requirements.txt
    echo [成功] 依赖包安装完成
) else (
    echo [错误] 未找到requirements.txt文件
    pause
    exit /b 1
)

REM 配置环境变量
echo.
echo [信息] 配置环境变量...
if not exist ".env" (
    if exist ".env.example" (
        copy .env.example .env
        echo [成功] 环境配置文件已创建
    ) else (
        echo [错误] 未找到.env.example文件
        pause
        exit /b 1
    )
) else (
    echo [信息] 环境配置文件已存在
)

REM 初始化应用
echo.
echo [信息] 初始化应用...
if exist "install.py" (
    python install.py
    echo [成功] 应用初始化完成
) else (
    echo [错误] 未找到install.py文件
    pause
    exit /b 1
)

REM 创建启动脚本
echo.
echo [信息] 创建启动脚本...

REM Windows启动脚本
echo @echo off > start.bat
echo echo 启动Web查询系统... >> start.bat
echo call venv\Scripts\activate.bat >> start.bat
echo set FLASK_ENV=production >> start.bat
echo python run.py >> start.bat
echo pause >> start.bat

REM 开发模式启动脚本
echo @echo off > start-dev.bat
echo echo 启动开发模式服务器... >> start-dev.bat
echo call venv\Scripts\activate.bat >> start-dev.bat
echo set FLASK_ENV=development >> start-dev.bat
echo set DEBUG=True >> start-dev.bat
echo python run.py >> start-dev.bat
echo pause >> start-dev.bat

echo [成功] 启动脚本创建完成

REM 创建数据库备份脚本
echo.
echo [信息] 创建数据库备份脚本...

echo @echo off > backup-db.bat
echo set DATE=%date:~0,4%%date:~5,2%%date:~8,2%_%time:~0,2%%time:~3,2%%time:~6,2% >> backup-db.bat
echo set DATE=%DATE: =0% >> backup-db.bat
echo echo 创建数据库备份... >> backup-db.bat
echo if not exist "backups" mkdir backups >> backup-db.bat
echo if exist "webchaxun.db" copy webchaxun.db backups\webchaxun_%DATE%.db >> backup-db.bat
echo echo 备份完成: backups\webchaxun_%DATE%.db >> backup-db.bat
echo pause >> backup-db.bat

echo [成功] 备份脚本创建完成

REM 显示安装结果
echo.
echo ========================================
echo 🎉 安装完成！
echo ========================================
echo.
echo 启动方式:
echo   生产模式: start.bat
echo   开发模式: start-dev.bat
echo.
echo 数据库备份:
echo   备份数据库: backup-db.bat
echo   备份目录: .\backups\
echo.
echo 配置文件:
echo   环境配置: .env
echo   虚拟环境: .\venv\
echo   数据库: webchaxun.db
echo.
echo 默认管理员账户:
echo   用户名: admin
echo   密码: admin123
echo.
echo ⚠️  安全提醒:
echo   1. 请立即修改默认管理员密码
echo   2. 请修改.env文件中的SECRET_KEY
echo   3. 生产环境请使用HTTPS
echo.
echo 项目地址: https://github.com/qdmz/webchaxun
echo ========================================
echo.

REM 询问是否启动服务
set /p choice="是否立即启动开发服务器? (y/n): "
if /i "%choice%"=="y" (
    echo.
    echo 启动开发服务器...
    call start-dev.bat
) else (
    echo.
    echo 安装完成！您可以运行 start.bat 启动服务器
)

pause