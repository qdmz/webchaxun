#!/usr/bin/env python3
"""
Web查询系统安装脚本
用于快速初始化和配置系统
"""

import os
import sys
from flask import Flask
from app import create_app, db
from app.models.user import User, SystemConfig

def create_admin_user():
    """创建默认管理员用户"""
    print("正在创建默认管理员账户...")
    
    # 检查是否已存在管理员
    if User.query.filter_by(username='admin').first():
        print("管理员账户已存在，跳过创建。")
        return
    
    admin = User(
        username='admin',
        email='admin@example.com',
        is_admin=True,
        is_active=True
    )
    admin.set_password('admin123')
    db.session.add(admin)
    db.session.commit()
    
    print("✅ 管理员账户创建成功")
    print("   用户名: admin")
    print("   密码: admin123")
    print("   ⚠️  请在生产环境中立即修改默认密码！")

def setup_system_configs():
    """设置系统默认配置"""
    print("正在设置系统默认配置...")
    
    configs = [
        ('enable_registration', 'true', '开放注册开关'),
        ('max_file_size', '16', '最大文件大小(MB)'),
        ('allowed_extensions', 'xlsx,xls,csv', '允许的文件扩展名')
    ]
    
    for key, value, description in configs:
        SystemConfig.set_config(key, value, description)
    
    print("✅ 系统配置设置完成")

def check_environment():
    """检查环境是否满足要求"""
    print("正在检查环境...")
    
    # 检查Python版本
    if sys.version_info < (3, 8):
        print("❌ 错误: 需要Python 3.8或更高版本")
        return False
    
    print(f"✅ Python版本: {sys.version}")
    
    # 检查必要目录
    required_dirs = ['uploads', 'logs']
    for dir_name in required_dirs:
        if not os.path.exists(dir_name):
            os.makedirs(dir_name)
            print(f"✅ 创建目录: {dir_name}")
    
    return True

def main():
    """主安装流程"""
    print("=" * 50)
    print("Web查询系统 - 安装向导")
    print("=" * 50)
    
    # 检查环境
    if not check_environment():
        sys.exit(1)
    
    try:
        # 创建应用实例
        app = create_app()
        
        with app.app_context():
            # 创建数据库表
            print("正在创建数据库表...")
            db.create_all()
            print("✅ 数据库表创建完成")
            
            # 创建管理员账户
            create_admin_user()
            
            # 设置系统配置
            setup_system_configs()
            
            print("\n" + "=" * 50)
            print("🎉 安装完成！")
            print("=" * 50)
            print("\n启动方式:")
            print("  python run.py")
            print("\n访问地址:")
            print("  http://localhost:5000")
            print("\n管理员登录:")
            print("  用户名: admin")
            print("  密码: admin123")
            print("\n⚠️  安全提醒:")
            print("  1. 请立即修改默认管理员密码")
            print("  2. 在生产环境中修改SECRET_KEY")
            print("  3. 根据需要调整系统配置")
            
    except Exception as e:
        print(f"❌ 安装失败: {e}")
        sys.exit(1)

if __name__ == '__main__':
    main()