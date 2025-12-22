import os
from datetime import datetime
from flask import Flask, render_template, request, redirect, url_for, flash, send_from_directory, abort
from flask_sqlalchemy import SQLAlchemy
from flask_login import LoginManager, UserMixin, login_user, login_required, logout_user, current_user
from werkzeug.security import generate_password_hash, check_password_hash
from werkzeug.utils import secure_filename
import pandas as pd

BASE_DIR = os.path.dirname(__file__)
UPLOAD_FOLDER = os.path.join(BASE_DIR, 'uploads')
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

app = Flask(__name__)
app.config['SECRET_KEY'] = os.environ.get('SECRET_KEY', 'dev-secret')
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///' + os.path.join(BASE_DIR, 'data.db')
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024  # 16 MB

ALLOWED_EXTENSIONS = {'xls', 'xlsx'}

db = SQLAlchemy(app)
login_manager = LoginManager(app)
login_manager.login_view = 'auth.login'


class Config(db.Model):
    key = db.Column(db.String(80), primary_key=True)
    value = db.Column(db.String(200))


class User(UserMixin, db.Model):
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(80), unique=True, nullable=False)
    password_hash = db.Column(db.String(200), nullable=False)
    is_admin = db.Column(db.Boolean, default=False)
    department = db.Column(db.String(80), default='')
    active = db.Column(db.Boolean, default=True)

    def set_password(self, password):
        self.password_hash = generate_password_hash(password)

    def check_password(self, password):
        return check_password_hash(self.password_hash, password)


class ExcelFile(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    filename = db.Column(db.String(200), nullable=False)
    original_name = db.Column(db.String(200), nullable=False)
    uploaded_by = db.Column(db.String(80))
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    allowed_departments = db.Column(db.String(200), default='')
    allowed_users = db.Column(db.String(200), default='')


@login_manager.user_loader
def load_user(user_id):
    return User.query.get(int(user_id))


def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS


def open_registration():
    cfg = Config.query.get('open_registration')
    return cfg and cfg.value == '1'


@app.route('/', endpoint='main.index')
def index():
    if current_user.is_authenticated:
        return redirect(url_for('main.files'))
    return render_template('index.html', open_registration=open_registration())


@app.route('/register', methods=['GET', 'POST'], endpoint='auth.register')
def register():
    if not open_registration():
        flash('注册已关闭', 'warning')
        return redirect(url_for('main.index'))
    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']
        dept = request.form.get('department', '')
        if User.query.filter_by(username=username).first():
            flash('用户名已存在', 'danger')
            return redirect(url_for('auth.register'))
        user = User(username=username, department=dept)
        user.set_password(password)
        db.session.add(user)
        db.session.commit()
        flash('注册成功，请登录', 'success')
        return redirect(url_for('auth.login'))
    return render_template('register.html')


@app.route('/login', methods=['GET', 'POST'], endpoint='auth.login')
def login():
    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']
        user = User.query.filter_by(username=username).first()
        if not user or not user.check_password(password) or not user.active:
            flash('用户名或密码错误，或用户被禁用', 'danger')
            return redirect(url_for('auth.login'))
        login_user(user)
        flash('登录成功', 'success')
        return redirect(url_for('main.files'))
    return render_template('login.html')


@app.route('/logout', endpoint='auth.logout')
@login_required
def logout():
    logout_user()
    flash('已登出', 'info')
    return redirect(url_for('main.index'))


@app.route('/profile', methods=['GET', 'POST'], endpoint='auth.profile')
@login_required
def profile():
    if request.method == 'POST':
        pwd = request.form.get('password')
        if pwd:
            current_user.set_password(pwd)
            db.session.commit()
            flash('密码已更新', 'success')
            return redirect(url_for('auth.profile'))
    return render_template('profile.html')


@app.route('/files', endpoint='main.files')
@login_required
def files():
    # show files user can access
    all_files = ExcelFile.query.order_by(ExcelFile.created_at.desc()).all()
    def can_view(f):
        if current_user.is_admin:
            return True
        if f.allowed_users:
            if current_user.username in [u.strip() for u in f.allowed_users.split(',') if u.strip()]:
                return True
        if f.allowed_departments:
            if current_user.department and current_user.department in [d.strip() for d in f.allowed_departments.split(',') if d.strip()]:
                return True
        return False
    visible = [f for f in all_files if can_view(f)]
    return render_template('files.html', files=visible)


@app.route('/download/<int:file_id>', endpoint='main.download_file')
@login_required
def download(file_id):
    f = ExcelFile.query.get_or_404(file_id)
    if not current_user.is_admin:
        if f.allowed_users and current_user.username not in f.allowed_users.split(','):
            if not (current_user.department and current_user.department in f.allowed_departments.split(',')):
                abort(403)
    return send_from_directory(app.config['UPLOAD_FOLDER'], f.filename, as_attachment=True, download_name=f.original_name)


@app.route('/search', methods=['GET', 'POST'], endpoint='main.search')
@login_required
def search():
    results = []
    keyword = ''
    if request.method == 'POST':
        keyword = request.form.get('keyword', '').strip()
        if keyword:
            files = ExcelFile.query.all()
            for f in files:
                # permission check
                if not current_user.is_admin:
                    if f.allowed_users and current_user.username not in f.allowed_users.split(','):
                        if not (current_user.department and current_user.department in f.allowed_departments.split(',')):
                            continue
                path = os.path.join(app.config['UPLOAD_FOLDER'], f.filename)
                try:
                    xl = pd.read_excel(path, sheet_name=None, engine='openpyxl')
                    for sheet_name, df in xl.items():
                        df = df.fillna('')
                        mask = df.astype(str).apply(lambda col: col.str.contains(keyword, case=False, na=False))
                        matched = df[mask.any(axis=1)]
                        if not matched.empty:
                            results.append({'file': f, 'sheet': sheet_name, 'rows': matched.head(200).to_html(classes='table table-sm table-striped', index=False)})
                except Exception:
                    continue
    return render_template('search_results.html', results=results, keyword=keyword)


@app.route('/admin', endpoint='admin.dashboard')
@login_required
def admin_index():
    if not current_user.is_admin:
        abort(403)
    files = ExcelFile.query.order_by(ExcelFile.created_at.desc()).all()
    users = User.query.order_by(User.username).all()
    reg = Config.query.get('open_registration')
    return render_template('admin_dashboard.html', files=files, users=users, registration_open=(reg and reg.value=='1'))


@app.route('/admin/upload', methods=['GET', 'POST'], endpoint='admin.upload_file')
@login_required
def admin_upload():
    if not current_user.is_admin:
        abort(403)
    if request.method == 'POST':
        if 'excel' not in request.files:
            flash('未选择文件', 'danger')
            return redirect(request.url)
        file = request.files['excel']
        if file.filename == '':
            flash('未选择文件', 'danger')
            return redirect(request.url)
        if file and allowed_file(file.filename):
            filename = secure_filename(f"{int(datetime.utcnow().timestamp())}_{file.filename}")
            file.save(os.path.join(app.config['UPLOAD_FOLDER'], filename))
            ef = ExcelFile(filename=filename, original_name=file.filename, uploaded_by=current_user.username)
            ef.allowed_departments = request.form.get('allowed_departments','')
            ef.allowed_users = request.form.get('allowed_users','')
            db.session.add(ef)
            db.session.commit()
            flash('上传成功', 'success')
            return redirect(url_for('admin.dashboard'))
        else:
            flash('仅支持 xls/xlsx 文件', 'danger')
            return redirect(request.url)
    return render_template('admin_upload.html')


@app.route('/admin/delete/<int:file_id>', methods=['POST'], endpoint='admin.delete_file')
@login_required
def admin_delete(file_id):
    if not current_user.is_admin:
        abort(403)
    f = ExcelFile.query.get_or_404(file_id)
    try:
        os.remove(os.path.join(app.config['UPLOAD_FOLDER'], f.filename))
    except Exception:
        pass
    db.session.delete(f)
    db.session.commit()
    flash('已删除', 'info')
    return redirect(url_for('admin.dashboard'))


@app.route('/admin/users', methods=['GET', 'POST'], endpoint='admin.users')
@login_required
def admin_users():
    if not current_user.is_admin:
        abort(403)
    if request.method == 'POST':
        action = request.form.get('action')
        if action == 'create':
            username = request.form['username']
            pwd = request.form['password']
            dept = request.form.get('department','')
            is_admin = bool(request.form.get('is_admin'))
            if User.query.filter_by(username=username).first():
                flash('用户已存在', 'danger')
            else:
                u = User(username=username, department=dept, is_admin=is_admin)
                u.set_password(pwd)
                db.session.add(u)
                db.session.commit()
                flash('用户已创建', 'success')
        elif action == 'toggle_registration':
            cfg = Config.query.get('open_registration')
            if not cfg:
                cfg = Config(key='open_registration', value='1')
                db.session.add(cfg)
            else:
                cfg.value = '0' if cfg.value=='1' else '1'
            db.session.commit()
            flash('开关已切换', 'info')
    users = User.query.order_by(User.username).all()
    reg = Config.query.get('open_registration')
    return render_template('admin_users.html', users=users, registration_open=(reg and reg.value=='1'))


@app.route('/admin/user/toggle/<int:user_id>', methods=['POST'], endpoint='admin.user_toggle')
@login_required
def admin_user_toggle(user_id):
    if not current_user.is_admin:
        abort(403)
    u = User.query.get_or_404(user_id)
    u.active = not u.active
    db.session.commit()
    flash(f'用户 {u.username} 状态已切换', 'info')
    return redirect(url_for('admin.users'))


@app.route('/admin/user/delete/<int:user_id>', methods=['POST'], endpoint='admin.user_delete')
@login_required
def admin_user_delete(user_id):
    if not current_user.is_admin:
        abort(403)
    u = User.query.get_or_404(user_id)
    if u.username == 'admin':
        flash('禁止删除初始管理员', 'danger')
        return redirect(url_for('admin_users'))
    db.session.delete(u)
    db.session.commit()
    flash(f'用户 {u.username} 已删除', 'info')
    return redirect(url_for('admin.users'))


@app.route('/admin/import_users', methods=['POST'], endpoint='admin.import_users')
@login_required
def admin_import_users():
    if not current_user.is_admin:
        abort(403)
    if 'users_file' not in request.files:
        flash('未选择文件', 'danger')
        return redirect(url_for('admin.users'))
    file = request.files['users_file']
    if file.filename == '':
        flash('未选择文件', 'danger')
        return redirect(url_for('admin_users'))
    filename = file.filename.lower()
    try:
        if filename.endswith('.csv'):
            df = pd.read_csv(file)
        elif filename.endswith(('.xls', '.xlsx')):
            df = pd.read_excel(file)
        else:
            flash('仅支持 CSV 或 Excel 文件导入', 'danger')
            return redirect(url_for('admin.users'))
        created = 0
        updated = 0
        for _, row in df.iterrows():
            username = str(row.get('username') or row.get('user') or '').strip()
            if not username:
                continue
            pwd = str(row.get('password') or '').strip() or 'changeme'
            dept = str(row.get('department') or '')
            is_admin_flag = str(row.get('is_admin') or '').strip().lower() in ('1','true','yes','y')
            active_flag = not (str(row.get('active') or '').strip().lower() in ('0','false','no','n'))
            u = User.query.filter_by(username=username).first()
            if u:
                u.department = dept
                u.is_admin = is_admin_flag
                u.active = active_flag
                if pwd:
                    u.set_password(pwd)
                updated += 1
            else:
                u = User(username=username, department=dept, is_admin=is_admin_flag, active=active_flag)
                u.set_password(pwd)
                db.session.add(u)
                created += 1
        db.session.commit()
        flash(f'导入完成：创建 {created}，更新 {updated}', 'success')
    except Exception as e:
        flash(f'导入失败: {e}', 'danger')
    return redirect(url_for('admin.users'))


# 兼容模板期望的别名路由与缺失视图
@app.route('/change_password', methods=['GET', 'POST'], endpoint='auth.change_password')
@login_required
def change_password():
    return profile()


@app.route('/admin/files', endpoint='admin.files')
@login_required
def admin_files_alias():
    return admin_index()


@app.route('/admin/settings', endpoint='admin.settings')
@login_required
def admin_settings_alias():
    return admin_users()


@app.route('/dashboard', endpoint='main.dashboard')
@login_required
def dashboard():
    files = ExcelFile.query.order_by(ExcelFile.created_at.desc()).all()
    return render_template('dashboard.html', files=files)


@app.route('/query/<int:file_id>', endpoint='main.query_page')
@login_required
def query_page(file_id):
    f = ExcelFile.query.get_or_404(file_id)
    return render_template('query.html', file=f)


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
