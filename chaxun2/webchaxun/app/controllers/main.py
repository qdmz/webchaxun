from flask import Blueprint, render_template, request, redirect, url_for, flash, jsonify, send_file
from flask_login import login_required, current_user
from werkzeug.utils import secure_filename
from app.models.user import User, SystemConfig
from app.models.excel_file import ExcelFile, FilePermission
from app import db
import os
import pandas as pd
from datetime import datetime
import uuid

main_bp = Blueprint('main', __name__)

def allowed_file(filename):
    """检查文件类型是否允许"""
    ALLOWED_EXTENSIONS = {'xlsx', 'xls', 'csv'}
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

@main_bp.route('/')
def index():
    """首页 - 显示项目功能和使用说明"""
    return render_template('index.html')

@main_bp.route('/dashboard')
@login_required
def dashboard():
    """用户仪表板"""
    # 获取用户可访问的文件列表
    if current_user.is_admin:
        accessible_files = ExcelFile.query.filter_by(is_active=True).all()
    else:
        accessible_files = current_user.get_accessible_files()
    
    return render_template('dashboard.html', files=accessible_files)

@main_bp.route('/files')
@login_required
def files():
    """显示所有可用Excel文件列表"""
    if current_user.is_admin:
        files = ExcelFile.query.filter_by(is_active=True).all()
    else:
        files = current_user.get_accessible_files()
    
    return render_template('files.html', files=files)

@main_bp.route('/query/<int:file_id>')
@login_required
def query_page(file_id):
    """查询页面"""
    # 检查权限
    if not current_user.can_access_file(file_id):
        flash('您没有权限访问该文件', 'error')
        return redirect(url_for('main.files'))
    
    excel_file = ExcelFile.query.get_or_404(file_id)
    file_info = excel_file.get_file_info()
    
    return render_template('query.html', file=excel_file, file_info=file_info)

@main_bp.route('/search', methods=['POST'])
@login_required
def search():
    """搜索Excel数据"""
    file_id = request.form.get('file_id')
    keyword = request.form.get('keyword', '').strip()
    sheet_name = request.form.get('sheet_name')
    
    if not file_id:
        return jsonify({'success': False, 'error': '请选择文件'})
    
    # 检查权限
    if not current_user.can_access_file(int(file_id)):
        return jsonify({'success': False, 'error': '没有权限访问该文件'})
    
    excel_file = ExcelFile.query.get_or_404(file_id)
    result = excel_file.query_data(keyword=keyword, sheet_name=sheet_name)
    
    return jsonify(result)

@main_bp.route('/download/<int:file_id>')
@login_required
def download_file(file_id):
    """下载Excel文件"""
    excel_file = ExcelFile.query.get_or_404(file_id)
    
    # 检查权限
    if not current_user.can_access_file(file_id):
        flash('您没有权限下载该文件', 'error')
        return redirect(url_for('main.files'))
    
    # 更新下载次数
    excel_file.download_count += 1
    db.session.commit()
    
    return send_file(
        excel_file.file_path,
        as_attachment=True,
        download_name=excel_file.original_filename
    )

@main_bp.route('/download-results', methods=['POST'])
@login_required
def download_results():
    """下载查询结果"""
    data = request.form.get('data')
    filename = request.form.get('filename', '查询结果')
    
    if not data:
        flash('没有可下载的数据', 'error')
        return redirect(request.referrer or url_for('main.dashboard'))
    
    try:
        import json
        import io
        
        # 解析JSON数据
        records = json.loads(data)
        
        if not records:
            flash('没有可下载的数据', 'error')
            return redirect(request.referrer or url_for('main.dashboard'))
        
        # 创建DataFrame
        df = pd.DataFrame(records)
        
        # 创建Excel文件
        output = io.BytesIO()
        with pd.ExcelWriter(output, engine='openpyxl') as writer:
            df.to_excel(writer, index=False, sheet_name='查询结果')
        
        output.seek(0)
        
        return send_file(
            output,
            as_attachment=True,
            download_name=f'{filename}_{datetime.now().strftime("%Y%m%d_%H%M%S")}.xlsx',
            mimetype='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        )
    except Exception as e:
        flash(f'下载失败: {str(e)}', 'error')
        return redirect(request.referrer or url_for('main.dashboard'))

@main_bp.route('/help')
def help():
    """帮助页面"""
    return render_template('help.html')

# 错误处理
@main_bp.errorhandler(404)
def not_found_error(error):
    return render_template('errors/404.html'), 404

@main_bp.errorhandler(500)
def internal_error(error):
    db.session.rollback()
    return render_template('errors/500.html'), 500