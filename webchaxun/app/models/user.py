from datetime import datetime
from flask_sqlalchemy import SQLAlchemy
from flask_login import UserMixin
from werkzeug.security import generate_password_hash, check_password_hash
from app import db

class User(UserMixin, db.Model):
    __tablename__ = 'users'
    
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(80), unique=True, nullable=False)
    email = db.Column(db.String(120), unique=True, nullable=False)
    password_hash = db.Column(db.String(255), nullable=False)
    department = db.Column(db.String(100))
    is_admin = db.Column(db.Boolean, default=False)
    is_active = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    last_login = db.Column(db.DateTime)
    
    # 关系
    file_permissions = db.relationship('FilePermission', backref='user', lazy=True, cascade='all, delete-orphan')
    
    def set_password(self, password):
        """设置密码"""
        self.password_hash = generate_password_hash(password)
    
    def check_password(self, password):
        """验证密码"""
        return check_password_hash(self.password_hash, password)
    
    def get_accessible_files(self):
        """获取用户有权限访问的文件"""
        accessible_files = []
        for permission in self.file_permissions:
            if permission.can_view and permission.file.is_active:
                accessible_files.append(permission.file)
        return accessible_files
    
    def can_access_file(self, file_id):
        """检查用户是否有权限访问指定文件"""
        if self.is_admin:
            return True
        
        permission = FilePermission.query.filter_by(
            user_id=self.id, 
            file_id=file_id, 
            can_view=True
        ).first()
        
        return permission is not None and permission.file.is_active
    
    def to_dict(self):
        """转换为字典"""
        return {
            'id': self.id,
            'username': self.username,
            'email': self.email,
            'department': self.department,
            'is_admin': self.is_admin,
            'is_active': self.is_active,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'last_login': self.last_login.isoformat() if self.last_login else None
        }

class FilePermission(db.Model):
    __tablename__ = 'file_permissions'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    file_id = db.Column(db.Integer, db.ForeignKey('excel_files.id'), nullable=False)
    can_view = db.Column(db.Boolean, default=True)
    can_download = db.Column(db.Boolean, default=True)
    granted_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    granted_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    # 关系
    file = db.relationship('ExcelFile', backref='permissions')
    granter = db.relationship('User', foreign_keys=[granted_by])
    
    __table_args__ = (db.UniqueConstraint('user_id', 'file_id'),)