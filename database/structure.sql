-- 数据管理系统数据库结构
-- 版本: 1.0.0
-- 创建时间: 2024-12-18

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `email` varchar(100) NOT NULL COMMENT '邮箱',
  `password` varchar(255) NOT NULL COMMENT '密码哈希',
  `role` enum('admin','user') NOT NULL DEFAULT 'user' COMMENT '用户角色',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active' COMMENT '用户状态',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像路径',
  `last_login` datetime DEFAULT NULL COMMENT '最后登录时间',
  `login_count` int(11) DEFAULT 0 COMMENT '登录次数',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- ----------------------------
-- Table structure for files
-- ----------------------------
DROP TABLE IF EXISTS `files`;
CREATE TABLE `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '文件ID',
  `filename` varchar(255) NOT NULL COMMENT '存储文件名',
  `original_name` varchar(255) NOT NULL COMMENT '原始文件名',
  `file_type` varchar(50) NOT NULL COMMENT '文件类型',
  `file_size` int(11) NOT NULL COMMENT '文件大小（字节）',
  `file_path` varchar(255) NOT NULL COMMENT '文件路径',
  `uploader_id` int(11) NOT NULL COMMENT '上传者ID',
  `mime_type` varchar(100) DEFAULT NULL COMMENT 'MIME类型',
  `upload_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '上传时间',
  `download_count` int(11) DEFAULT 0 COMMENT '下载次数',
  `status` enum('active','deleted') NOT NULL DEFAULT 'active' COMMENT '文件状态',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `uploader_id` (`uploader_id`),
  KEY `file_type` (`file_type`),
  KEY `status` (`status`),
  KEY `upload_time` (`upload_time`),
  CONSTRAINT `files_ibfk_1` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件表';

-- ----------------------------
-- Table structure for data_records
-- ----------------------------
DROP TABLE IF EXISTS `data_records`;
CREATE TABLE `data_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `file_id` int(11) NOT NULL COMMENT '关联文件ID',
  `sheet_name` varchar(255) NOT NULL COMMENT '工作表名称',
  `row_number` int(11) NOT NULL COMMENT '行号',
  `data` json NOT NULL COMMENT '数据内容',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`),
  KEY `sheet_name` (`sheet_name`),
  KEY `row_number` (`row_number`),
  CONSTRAINT `data_records_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数据记录表';

-- ----------------------------
-- Table structure for system_settings
-- ----------------------------
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '设置ID',
  `setting_key` varchar(100) NOT NULL COMMENT '设置键名',
  `setting_value` text COMMENT '设置值',
  `setting_type` enum('string','number','boolean','json') NOT NULL DEFAULT 'string' COMMENT '值类型',
  `description` varchar(255) DEFAULT NULL COMMENT '设置描述',
  `is_public` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否为公开设置',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `is_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统设置表';

-- ----------------------------
-- Table structure for user_sessions
-- ----------------------------
DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '会话ID',
  `session_id` varchar(255) NOT NULL COMMENT '会话标识',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `ip_address` varchar(45) NOT NULL COMMENT 'IP地址',
  `user_agent` text COMMENT '用户代理',
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '最后活动时间',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `user_id` (`user_id`),
  KEY `last_activity` (`last_activity`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户会话表';

-- ----------------------------
-- Table structure for system_logs
-- ----------------------------
DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `level` enum('DEBUG','INFO','WARNING','ERROR') NOT NULL DEFAULT 'INFO' COMMENT '日志级别',
  `category` varchar(50) NOT NULL COMMENT '日志分类',
  `message` text NOT NULL COMMENT '日志消息',
  `context` json DEFAULT NULL COMMENT '上下文数据',
  `user_id` int(11) DEFAULT NULL COMMENT '相关用户ID',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` text COMMENT '用户代理',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `level` (`level`),
  KEY `category` (`category`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统日志表';

-- ----------------------------
-- Insert default system settings
-- ----------------------------
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `is_public`) VALUES
('site_name', '数据管理系统', 'string', '网站名称', 1),
('site_description', '专业的Excel数据管理平台', 'string', '网站描述', 1),
('site_version', '1.0.0', 'string', '系统版本', 1),
('max_file_size', '10485760', 'number', '最大文件大小（字节）', 0),
('allowed_file_types', '["xlsx","xls","csv"]', 'json', '允许的文件类型', 0),
('registration_enabled', '1', 'boolean', '是否允许用户注册', 1),
('admin_email', '', 'string', '管理员邮箱', 0),
('upload_path', '../uploads/', 'string', '文件上传路径', 0),
('session_timeout', '3600', 'number', '会话超时时间（秒）', 0),
('items_per_page', '20', 'number', '每页显示条目数', 0),
('max_login_attempts', '5', 'number', '最大登录尝试次数', 0),
('lockout_duration', '900', 'number', '账户锁定时长（秒）', 0),
('password_min_length', '6', 'number', '密码最小长度', 0),
('backup_enabled', '0', 'boolean', '是否启用自动备份', 0),
('backup_interval', '86400', 'number', '备份间隔（秒）', 0),
('max_backup_files', '7', 'number', '最大备份文件数', 0);

SET FOREIGN_KEY_CHECKS = 1;