from datetime import datetime
from app import db
import os

class ExcelFile(db.Model):
    __tablename__ = 'excel_files'
    
    id = db.Column(db.Integer, primary_key=True)
    filename = db.Column(db.String(255), nullable=False)
    original_filename = db.Column(db.String(255), nullable=False)
    file_path = db.Column(db.String(500), nullable=False)
    file_size = db.Column(db.Integer)
    mime_type = db.Column(db.String(100))
    description = db.Column(db.Text)
    uploaded_by = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    is_active = db.Column(db.Boolean, default=True)
    download_count = db.Column(db.Integer, default=0)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # 关系
    uploader = db.relationship('User', backref='uploaded_files')
    
    def get_file_info(self):
        """获取Excel文件基本信息"""
        try:
            import pandas as pd
            
            # 读取Excel文件信息
            df = pd.read_excel(self.file_path, nrows=0)  # 只读取表头
            sheets = pd.ExcelFile(self.file_path).sheet_names
            
            # 获取第一个工作表的列信息
            first_sheet = sheets[0] if sheets else None
            columns = []
            if first_sheet:
                df_temp = pd.read_excel(self.file_path, sheet_name=first_sheet, nrows=1)
                columns = df_temp.columns.tolist()
            
            return {
                'sheets': sheets,
                'columns': columns,
                'row_count': self._get_row_count()
            }
        except Exception as e:
            return {
                'sheets': [],
                'columns': [],
                'row_count': 0,
                'error': str(e)
            }
    
    def _get_row_count(self):
        """获取行数"""
        try:
            import pandas as pd
            df = pd.read_excel(self.file_path)
            return len(df)
        except:
            return 0
    
    def query_data(self, keyword=None, sheet_name=None, limit=1000):
        """查询Excel数据"""
        try:
            import pandas as pd
            
            # 读取数据
            if sheet_name:
                df = pd.read_excel(self.file_path, sheet_name=sheet_name)
            else:
                df = pd.read_excel(self.file_path)
            
            # 关键词搜索
            if keyword:
                mask = df.astype(str).apply(lambda x: x.str.contains(keyword, case=False, na=False)).any(axis=1)
                df = df[mask]
            
            # 限制返回行数
            if len(df) > limit:
                df = df.head(limit)
            
            # 转换为字典列表
            data = df.fillna('').to_dict('records')
            columns = df.columns.tolist()
            
            return {
                'success': True,
                'data': data,
                'columns': columns,
                'total_rows': len(df),
                'sheets': pd.ExcelFile(self.file_path).sheet_names
            }
        except Exception as e:
            return {
                'success': False,
                'error': str(e),
                'data': [],
                'columns': [],
                'total_rows': 0
            }
    
    def to_dict(self):
        """转换为字典"""
        return {
            'id': self.id,
            'filename': self.filename,
            'original_filename': self.original_filename,
            'file_size': self.file_size,
            'mime_type': self.mime_type,
            'description': self.description,
            'is_active': self.is_active,
            'download_count': self.download_count,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None,
            'uploader': self.uploader.username if self.uploader else None
        }
    
    def delete_file(self):
        """删除文件和记录"""
        try:
            # 删除物理文件
            if os.path.exists(self.file_path):
                os.remove(self.file_path)
            
            # 删除数据库记录
            db.session.delete(self)
            db.session.commit()
            return True
        except Exception as e:
            db.session.rollback()
            raise e

class SystemConfig(db.Model):
    __tablename__ = 'system_configs'
    
    id = db.Column(db.Integer, primary_key=True)
    key = db.Column(db.String(100), unique=True, nullable=False)
    value = db.Column(db.Text)
    description = db.Column(db.String(255))
    updated_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    updater = db.relationship('User')
    
    @classmethod
    def get_config(cls, key, default=None):
        """获取配置值"""
        config = cls.query.filter_by(key=key).first()
        return config.value if config else default
    
    @classmethod
    def set_config(cls, key, value, description=None, updated_by=None):
        """设置配置值"""
        config = cls.query.filter_by(key=key).first()
        if config:
            config.value = value
            config.updated_by = updated_by
            config.updated_at = datetime.utcnow()
        else:
            config = cls(
                key=key,
                value=value,
                description=description,
                updated_by=updated_by
            )
            db.session.add(config)
        
        db.session.commit()
        return config