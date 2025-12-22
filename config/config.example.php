<?php
/**
 * 数据管理系统 - 配置文件示例
 * 复制此文件为 config.php 并修改相应配置
 */

// 数据库配置
$config['database']['host'] = 'localhost';
$config['database']['port'] = '3306';
$config['database']['username'] = 'your_username';
$config['database']['password'] = 'your_password';
$config['database']['name'] = 'your_database';
$config['database']['charset'] = 'utf8mb4';

// 系统配置
$config['system']['timezone'] = 'Asia/Shanghai';
$config['system']['session_name'] = 'data_management_session';
$config['system']['upload_path'] = '../uploads/';
$config['system']['max_file_size'] = 10485760; // 10MB
$config['system']['allowed_extensions'] = ['xlsx', 'xls', 'csv'];

// 安全配置
$config['security']['password_min_length'] = 6;
$config['security']['session_timeout'] = 3600; // 1小时
$config['security']['max_login_attempts'] = 5;
$config['security']['lockout_duration'] = 900; // 15分钟

// 邮件配置（可选）
$config['email']['smtp_host'] = '';
$config['email']['smtp_port'] = 587;
$config['email']['smtp_username'] = '';
$config['email']['smtp_password'] = '';
$config['email']['from_email'] = '';
$config['email']['from_name'] = '数据管理系统';

// 日志配置
$config['log']['enabled'] = true;
$config['log']['level'] = 'INFO'; // DEBUG, INFO, WARNING, ERROR
$config['log']['file'] = '../logs/system.log';

// 开发配置
$config['debug']['enabled'] = false;
$config['debug']['show_errors'] = false;
$config['debug']['log_queries'] = false;

return $config;
?>