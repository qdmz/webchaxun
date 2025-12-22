from flask import Blueprint, jsonify, request
from flask_login import login_required, current_user
from app.models.user import User
from app.models.excel_file import ExcelFile
from app import db

api_bp = Blueprint('api', __name__)

@api_bp.route('/files')
@login_required
def get_files():
    """获取用户可访问的文件列表（API）"""
    if current_user.is_admin:
        files = ExcelFile.query.filter_by(is_active=True).all()
    else:
        files = current_user.get_accessible_files()
    
    return jsonify({
        'success': True,
        'files': [file.to_dict() for file in files]
    })

@api_bp.route('/search/<int:file_id>')
@login_required
def search_file(file_id):
    """搜索指定文件的数据（API）"""
    keyword = request.args.get('keyword', '').strip()
    sheet_name = request.args.get('sheet_name')
    limit = request.args.get('limit', 1000, type=int)
    
    # 检查权限
    if not current_user.can_access_file(file_id):
        return jsonify({
            'success': False,
            'error': '没有权限访问该文件'
        }), 403
    
    excel_file = ExcelFile.query.get_or_404(file_id)
    result = excel_file.query_data(keyword=keyword, sheet_name=sheet_name, limit=limit)
    
    return jsonify(result)

@api_bp.route('/file-info/<int:file_id>')
@login_required
def get_file_info(file_id):
    """获取文件信息（API）"""
    # 检查权限
    if not current_user.can_access_file(file_id):
        return jsonify({
            'success': False,
            'error': '没有权限访问该文件'
        }), 403
    
    excel_file = ExcelFile.query.get_or_404(file_id)
    file_info = excel_file.get_file_info()
    
    return jsonify({
        'success': True,
        'file': excel_file.to_dict(),
        'info': file_info
    })