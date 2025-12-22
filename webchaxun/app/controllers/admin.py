from flask import Blueprint, render_template, request, redirect, url_for, flash, jsonify, current_app
from flask_login import login_required, current_user
from werkzeug.utils import secure_filename
from werkzeug.security import generate_password_hash
from app.models.user import User, FilePermission, SystemConfig
from app.models.excel_file import ExcelFile
from app import db
import os
import pandas as pd
from datetime import datetime
import uuid

admin_bp = Blueprint('admin', __name__)

def admin_required(f):
    """管理员权限装饰器"""
    def decorated_function(*args, **kwargs):
        if not current_user.is_authenticated or not current_user.is_admin:
            flash('需要管理员权限', 'error')
            return redirect(url_for('auth.login'))
        return f(*args, **kwargs)
    decorated_function.__name__ = f.__name__
    return decorated_function

@admin_bp.route('/')
@login_required
@admin_required
def dashboard():
    """管理员仪表板"""
    stats = {
        'total_users': User.query.count(),
        'active_users': User.query.filter_by(is_active=True).count(),
        'total_files': ExcelFile.query.count(),
        'active_files': ExcelFile.query.filter_by(is_active=True).count(),
        'total_downloads': db.session.query(db.func.sum(ExcelFile.download_count)).scalar() or 0
    }
    return render_template('admin/dashboard.html', stats=stats)

@admin_bp.route('/files')
@login_required
@admin_required
def files():
    """文件管理"""
    page = request.args.get('page', 1, type=int)
    search = request.args.get('search', '').strip()
    
    query = ExcelFile.query
    if search:
        query = query.filter(ExcelFile.original_filename.contains(search))
    
    files = query.order_by(ExcelFile.created_at.desc()).paginate(
        page=page, per_page=20, error_out=False
    )
    
    return render_template('admin/files.html', files=files, search=search)

@admin_bp.route('/upload', methods=['GET', 'POST'])
@login_required
@admin_required
def upload_file():
    """上传Excel文件"""
    if request.method == 'POST':
        if 'file' not in request.files:
            flash('请选择文件', 'error')
            return render_template('admin/upload.html')
        
        file = request.files['file']
        description = request.form.get('description', '').strip()
        
        if file.filename == '':
            flash('请选择文件', 'error')
            return render_template('admin/upload.html')
        
        if file and allowed_file(file.filename):
            try:
                # 生成安全的文件名
                original_filename = file.filename
                file_extension = original_filename.rsplit('.', 1)[1].lower()
                secure_name = secure_filename(original_filename)
                unique_filename = f"{uuid.uuid4().hex}_{secure_name}"
                
                # 确保上传目录存在
                upload_dir = current_app.config['UPLOAD_FOLDER']
                os.makedirs(upload_dir, exist_ok=True)
                
                file_path = os.path.join(upload_dir, unique_filename)
                file.save(file_path)
                
                # 获取文件大小
                file_size = os.path.getsize(file_path)
                
                # 保存到数据库
                excel_file = ExcelFile(
                    filename=unique_filename,
                    original_filename=original_filename,
                    file_path=file_path,
                    file_size=file_size,
                    mime_type=file.content_type,
                    description=description,
                    uploaded_by=current_user.id
                )
                
                db.session.add(excel_file)
                db.session.commit()
                
                flash('文件上传成功', 'success')
                return redirect(url_for('admin.files'))
                
            except Exception as e:
                db.session.rollback()
                flash(f'文件上传失败: {str(e)}', 'error')
                return render_template('admin/upload.html')
        else:
            flash('不支持的文件类型，请上传Excel文件', 'error')
    
    return render_template('admin/upload.html')

def allowed_file(filename):
    """检查文件类型是否允许"""
    ALLOWED_EXTENSIONS = {'xlsx', 'xls', 'csv'}
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

@admin_bp.route('/delete-file/<int:file_id>')
@login_required
@admin_required
def delete_file(file_id):
    """删除文件"""
    excel_file = ExcelFile.query.get_or_404(file_id)
    
    try:
        excel_file.delete_file()
        flash('文件删除成功', 'success')
    except Exception as e:
        flash(f'文件删除失败: {str(e)}', 'error')
    
    return redirect(url_for('admin.files'))

@admin_bp.route('/file-permissions/<int:file_id>')
@login_required
@admin_required
def file_permissions(file_id):
    """文件权限管理"""
    excel_file = ExcelFile.query.get_or_404(file_id)
    permissions = FilePermission.query.filter_by(file_id=file_id).all()
    users = User.query.filter_by(is_active=True).all()
    
    return render_template('admin/file_permissions.html', 
                         file=excel_file, permissions=permissions, users=users)

@admin_bp.route('/set-permission', methods=['POST'])
@login_required
@admin_required
def set_permission():
    """设置文件权限"""
    user_id = request.form.get('user_id', type=int)
    file_id = request.form.get('file_id', type=int)
    can_view = request.form.get('can_view') == 'on'
    can_download = request.form.get('can_download') == 'on'
    
    user = User.query.get_or_404(user_id)
    excel_file = ExcelFile.query.get_or_404(file_id)
    
    # 检查权限是否已存在
    permission = FilePermission.query.filter_by(user_id=user_id, file_id=file_id).first()
    
    if permission:
        permission.can_view = can_view
        permission.can_download = can_download
        permission.granted_by = current_user.id
        permission.granted_at = datetime.utcnow()
    else:
        permission = FilePermission(
            user_id=user_id,
            file_id=file_id,
            can_view=can_view,
            can_download=can_download,
            granted_by=current_user.id
        )
        db.session.add(permission)
    
    db.session.commit()
    flash('权限设置成功', 'success')
    return redirect(url_for('admin.file_permissions', file_id=file_id))

@admin_bp.route('/delete-permission/<int:permission_id>')
@login_required
@admin_required
def delete_permission(permission_id):
    """删除权限"""
    permission = FilePermission.query.get_or_404(permission_id)
    file_id = permission.file_id
    
    db.session.delete(permission)
    db.session.commit()
    
    flash('权限删除成功', 'success')
    return redirect(url_for('admin.file_permissions', file_id=file_id))

@admin_bp.route('/users')
@login_required
@admin_required
def users():
    """用户管理"""
    page = request.args.get('page', 1, type=int)
    search = request.args.get('search', '').strip()
    
    query = User.query
    if search:
        query = query.filter(
            User.username.contains(search) | 
            User.email.contains(search) |
            User.department.contains(search)
        )
    
    users = query.order_by(User.created_at.desc()).paginate(
        page=page, per_page=20, error_out=False
    )
    
    return render_template('admin/users.html', users=users, search=search)

@admin_bp.route('/create-user', methods=['GET', 'POST'])
@login_required
@admin_required
def create_user():
    """创建用户"""
    if request.method == 'POST':
        username = request.form.get('username', '').strip()
        email = request.form.get('email', '').strip()
        password = request.form.get('password', '')
        department = request.form.get('department', '').strip()
        is_admin = request.form.get('is_admin') == 'on'
        
        if not username or not email or not password:
            flash('请填写必填字段', 'error')
            return render_template('admin/create_user.html')
        
        if User.query.filter_by(username=username).first():
            flash('用户名已存在', 'error')
            return render_template('admin/create_user.html')
        
        if User.query.filter_by(email=email).first():
            flash('邮箱已被使用', 'error')
            return render_template('admin/create_user.html')
        
        user = User(
            username=username,
            email=email,
            department=department,
            is_admin=is_admin,
            is_active=True
        )
        user.set_password(password)
        
        db.session.add(user)
        db.session.commit()
        
        flash('用户创建成功', 'success')
        return redirect(url_for('admin.users'))
    
    return render_template('admin/create_user.html')

@admin_bp.route('/edit-user/<int:user_id>', methods=['GET', 'POST'])
@login_required
@admin_required
def edit_user(user_id):
    """编辑用户"""
    user = User.query.get_or_404(user_id)
    
    if request.method == 'POST':
        email = request.form.get('email', '').strip()
        department = request.form.get('department', '').strip()
        is_admin = request.form.get('is_admin') == 'on'
        is_active = request.form.get('is_active') == 'on'
        password = request.form.get('password', '').strip()
        
        if not email:
            flash('邮箱不能为空', 'error')
            return render_template('admin/edit_user.html', user=user)
        
        # 检查邮箱是否已被其他用户使用
        existing_user = User.query.filter(User.email == email, User.id != user_id).first()
        if existing_user:
            flash('邮箱已被其他用户使用', 'error')
            return render_template('admin/edit_user.html', user=user)
        
        user.email = email
        user.department = department
        user.is_admin = is_admin
        user.is_active = is_active
        
        if password:
            user.set_password(password)
        
        db.session.commit()
        flash('用户信息更新成功', 'success')
        return redirect(url_for('admin.users'))
    
    return render_template('admin/edit_user.html', user=user)

@admin_bp.route('/toggle-user/<int:user_id>')
@login_required
@admin_required
def toggle_user(user_id):
    """启用/禁用用户"""
    user = User.query.get_or_404(user_id)
    
    if user.id == current_user.id:
        flash('不能禁用自己', 'error')
    else:
        user.is_active = not user.is_active
        db.session.commit()
        status = '启用' if user.is_active else '禁用'
        flash(f'用户已{status}', 'success')
    
    return redirect(url_for('admin.users'))

@admin_bp.route('/import-users', methods=['GET', 'POST'])
@login_required
@admin_required
def import_users():
    """批量导入用户"""
    if request.method == 'POST':
        if 'file' not in request.files:
            flash('请选择文件', 'error')
            return render_template('admin/import_users.html')
        
        file = request.files['file']
        if file.filename == '':
            flash('请选择文件', 'error')
            return render_template('admin/import_users.html')
        
        if file and allowed_file(file.filename):
            try:
                # 读取Excel文件
                df = pd.read_excel(file)
                
                # 检查必需的列
                required_columns = ['username', 'email', 'password']
                if not all(col in df.columns for col in required_columns):
                    flash('Excel文件必须包含列: username, email, password', 'error')
                    return render_template('admin/import_users.html')
                
                # 导入用户
                success_count = 0
                error_count = 0
                
                for index, row in df.iterrows():
                    try:
                        username = str(row['username']).strip()
                        email = str(row['email']).strip()
                        password = str(row['password']).strip()
                        department = str(row.get('department', '')).strip()
                        is_admin = bool(row.get('is_admin', False))
                        
                        if not username or not email or not password:
                            error_count += 1
                            continue
                        
                        # 检查是否已存在
                        if User.query.filter_by(username=username).first():
                            error_count += 1
                            continue
                        
                        if User.query.filter_by(email=email).first():
                            error_count += 1
                            continue
                        
                        # 创建用户
                        user = User(
                            username=username,
                            email=email,
                            department=department,
                            is_admin=is_admin,
                            is_active=True
                        )
                        user.set_password(password)
                        
                        db.session.add(user)
                        success_count += 1
                        
                    except Exception as e:
                        error_count += 1
                
                db.session.commit()
                flash(f'用户导入完成: 成功 {success_count} 个，失败 {error_count} 个', 'success')
                
            except Exception as e:
                flash(f'文件处理失败: {str(e)}', 'error')
        else:
            flash('不支持的文件类型', 'error')
    
    return render_template('admin/import_users.html')

@admin_bp.route('/settings')
@login_required
@admin_required
def settings():
    """系统设置"""
    configs = {
        'enable_registration': SystemConfig.get_config('enable_registration', 'true'),
        'max_file_size': SystemConfig.get_config('max_file_size', '16'),
        'allowed_extensions': SystemConfig.get_config('allowed_extensions', 'xlsx,xls,csv')
    }
    return render_template('admin/settings.html', configs=configs)

@admin_bp.route('/update-settings', methods=['POST'])
@login_required
@admin_required
def update_settings():
    """更新系统设置"""
    enable_registration = request.form.get('enable_registration', 'off')
    max_file_size = request.form.get('max_file_size', '16')
    allowed_extensions = request.form.get('allowed_extensions', 'xlsx,xls,csv')
    
    SystemConfig.set_config('enable_registration', enable_registration, '开放注册开关', current_user.id)
    SystemConfig.set_config('max_file_size', max_file_size, '最大文件大小(MB)', current_user.id)
    SystemConfig.set_config('allowed_extensions', allowed_extensions, '允许的文件扩展名', current_user.id)
    
    flash('系统设置更新成功', 'success')
    return redirect(url_for('admin.settings'))