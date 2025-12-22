from datetime import datetime
from flask import Blueprint, render_template, request, redirect, url_for, flash, jsonify
from flask_login import login_user, logout_user, login_required, current_user
from werkzeug.security import check_password_hash
from app.models.user import User, SystemConfig
from app import db

auth_bp = Blueprint('auth', __name__)

def is_registration_enabled():
    """检查是否开放注册"""
    return SystemConfig.get_config('enable_registration', 'true').lower() == 'true'

@auth_bp.route('/login', methods=['GET', 'POST'])
def login():
    """用户登录"""
    if request.method == 'POST':
        username = request.form.get('username', '').strip()
        password = request.form.get('password', '')
        remember = request.form.get('remember', False)
        
        if not username or not password:
            flash('请填写用户名和密码', 'error')
            return render_template('auth/login.html')
        
        user = User.query.filter_by(username=username).first()
        
        if user and user.check_password(password) and user.is_active:
            login_user(user, remember=remember)
            user.last_login = datetime.utcnow()
            db.session.commit()
            
            next_page = request.args.get('next')
            if next_page:
                return redirect(next_page)
            
            if user.is_admin:
                return redirect(url_for('admin.dashboard'))
            else:
                return redirect(url_for('main.dashboard'))
        else:
            flash('用户名、密码错误或账户已被禁用', 'error')
    
    return render_template('auth/login.html')

@auth_bp.route('/register', methods=['GET', 'POST'])
def register():
    """用户注册"""
    if not is_registration_enabled():
        flash('当前未开放注册', 'error')
        return redirect(url_for('auth.login'))
    
    if request.method == 'POST':
        username = request.form.get('username', '').strip()
        email = request.form.get('email', '').strip()
        password = request.form.get('password', '')
        confirm_password = request.form.get('confirm_password', '')
        department = request.form.get('department', '').strip()
        
        # 验证
        if not username or not email or not password:
            flash('请填写所有必填字段', 'error')
            return render_template('auth/register.html')
        
        if password != confirm_password:
            flash('两次输入的密码不一致', 'error')
            return render_template('auth/register.html')
        
        if len(password) < 6:
            flash('密码长度至少6位', 'error')
            return render_template('auth/register.html')
        
        # 检查用户名和邮箱是否已存在
        if User.query.filter_by(username=username).first():
            flash('用户名已存在', 'error')
            return render_template('auth/register.html')
        
        if User.query.filter_by(email=email).first():
            flash('邮箱已被使用', 'error')
            return render_template('auth/register.html')
        
        # 创建用户
        user = User(
            username=username,
            email=email,
            department=department,
            is_active=True
        )
        user.set_password(password)
        
        db.session.add(user)
        db.session.commit()
        
        flash('注册成功！请登录', 'success')
        return redirect(url_for('auth.login'))
    
    return render_template('auth/register.html')

@auth_bp.route('/logout')
@login_required
def logout():
    """用户登出"""
    logout_user()
    flash('已成功退出登录', 'info')
    return redirect(url_for('main.index'))

@auth_bp.route('/change-password', methods=['GET', 'POST'])
@login_required
def change_password():
    """修改密码"""
    if request.method == 'POST':
        current_password = request.form.get('current_password', '')
        new_password = request.form.get('new_password', '')
        confirm_password = request.form.get('confirm_password', '')
        
        if not current_password or not new_password or not confirm_password:
            flash('请填写所有字段', 'error')
            return render_template('auth/change_password.html')
        
        if not current_user.check_password(current_password):
            flash('当前密码错误', 'error')
            return render_template('auth/change_password.html')
        
        if new_password != confirm_password:
            flash('两次输入的新密码不一致', 'error')
            return render_template('auth/change_password.html')
        
        if len(new_password) < 6:
            flash('新密码长度至少6位', 'error')
            return render_template('auth/change_password.html')
        
        current_user.set_password(new_password)
        db.session.commit()
        
        flash('密码修改成功', 'success')
        return redirect(url_for('main.dashboard'))
    
    return render_template('auth/change_password.html')

@auth_bp.route('/profile')
@login_required
def profile():
    """用户资料"""
    return render_template('auth/profile.html', user=current_user)

@auth_bp.route('/edit-profile', methods=['GET', 'POST'])
@login_required
def edit_profile():
    """编辑用户资料"""
    if request.method == 'POST':
        email = request.form.get('email', '').strip()
        department = request.form.get('department', '').strip()
        
        if not email:
            flash('邮箱不能为空', 'error')
            return render_template('auth/edit_profile.html', user=current_user)
        
        # 检查邮箱是否已被其他用户使用
        existing_user = User.query.filter(User.email == email, User.id != current_user.id).first()
        if existing_user:
            flash('邮箱已被其他用户使用', 'error')
            return render_template('auth/edit_profile.html', user=current_user)
        
        current_user.email = email
        current_user.department = department
        db.session.commit()
        
        flash('资料更新成功', 'success')
        return redirect(url_for('auth.profile'))
    
    return render_template('auth/edit_profile.html', user=current_user)