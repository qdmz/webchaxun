<?php
// admin/upload.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// 检查并加载ExcelReader类
if (!class_exists('ExcelReader', false)) {
    $excelReaderFile = __DIR__ . '/../includes/excel-reader.php';
    if (file_exists($excelReaderFile)) {
        require_once $excelReaderFile;
    } else {
        die("错误: 找不到excel-reader.php文件");
    }
}

$auth = new Auth();
$auth->requireLogin();

$admin = $auth->getCurrentAdmin();
$error = '';
$success = '';

// 确保uploads目录存在且可写
$uploadsDir = __DIR__ . '/../uploads';
if (!file_exists($uploadsDir)) {
    if (!mkdir($uploadsDir, 0755, true)) {
        $error = "无法创建uploads目录，请手动创建并设置写入权限";
    }
}

// 检查目录权限
if (file_exists($uploadsDir) && !is_writable($uploadsDir)) {
    // 尝试修改权限
    if (!chmod($uploadsDir, 0755)) {
        $error = "uploads目录不可写，请手动设置目录权限为755或777";
    }
}

// 文件上传处理
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    
    // 检查目录权限
    if (!is_writable($uploadsDir)) {
        $error = "uploads目录不可写，请检查目录权限";
    } else {
        $file = $_FILES['excel_file'];
        
        // 检查文件上传错误
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = "文件大小超过限制";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error = "文件上传不完整";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error = "没有选择文件";
                    break;
                default:
                    $error = "文件上传错误: " . $file['error'];
            }
        } else {
            // 检查文件类型
            $allowedTypes = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/octet-stream'];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['xls', 'xlsx'];
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                $error = "只允许上传Excel文件 (.xls, .xlsx)";
            } else {
                // 生成唯一文件名
                $uniqueName = uniqid() . '_' . time() . '.' . $fileExtension;
                $targetPath = $uploadsDir . '/' . $uniqueName;
                
                // 移动上传的文件
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    
                    // 验证Excel文件格式
                    try {
                        $excelReader = new ExcelReader($targetPath);
                        $sheetNames = $excelReader->getSheetNames();
                        
                        if (empty($sheetNames)) {
                            unlink($targetPath); // 删除无效文件
                            $error = "上传的Excel文件无效或损坏";
                        } else {
                            // 保存到数据库
                            $sql = "INSERT INTO excel_files (original_name, file_path, upload_time) VALUES (?, ?, NOW())";
                            $db->query($sql, [$file['name'], $targetPath]);
                            
                            $success = "文件上传成功！";
                            
                            // 重定向到管理面板
                            header('Location: dashboard.php?msg=uploaded');
                            exit();
                        }
                    } catch (Exception $e) {
                        unlink($targetPath); // 删除无效文件
                        $error = "Excel文件解析错误: " . $e->getMessage();
                    }
                    
                } else {
                    $error = "文件移动失败，请检查目录权限";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>上传Excel文件 - 管理面板</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            margin: 20px 0;
            transition: border-color 0.3s;
        }
        .upload-area:hover {
            border-color: #007bff;
        }
        .upload-area.dragover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        .file-input {
            display: none;
        }
        .upload-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .upload-btn:hover {
            background: #0056b3;
        }
        .file-info {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-upload"></i> 上传Excel文件</h1>
            <nav>
                <span>欢迎, <?= escape($admin['username']) ?></span>
                <a href="dashboard.php"><i class="fas fa-home"></i> 面板首页</a>
                <a href="upload.php" class="active"><i class="fas fa-upload"></i> 上传文件</a>
                <a href="change-password.php"><i class="fas fa-key"></i> 修改密码</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> 退出登录</a>
            </nav>
        </header>
        
        <main>
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= escape($error) ?>
                
                <?php if (strpos($error, '目录权限') !== false): ?>
                <div style="margin-top: 10px; font-size: 14px;">
                    <strong>权限修复说明：</strong><br>
                    <strong>Linux服务器：</strong>运行命令: <code>chmod 755 uploads</code> 或 <code>chmod 777 uploads</code><br>
                    <strong>Windows服务器：</strong>右键点击uploads文件夹 → 属性 → 安全 → 编辑 → 添加Everyone用户 → 给予"完全控制"权限
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= escape($success) ?>
            </div>
            <?php endif; ?>
            
            <div class="upload-section">
                <h2><i class="fas fa-cloud-upload-alt"></i> 上传Excel文件</h2>
                
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="form-group">
                        <label for="excel_file">选择Excel文件 (.xls, .xlsx):</label>
                        
                        <div class="upload-area" id="uploadArea">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #007bff; margin-bottom: 15px;"></i>
                            <h3>拖放文件到此处或点击选择</h3>
                            <p style="color: #666; margin: 10px 0;">支持 .xls 和 .xlsx 格式的Excel文件</p>
                            <p style="color: #999; font-size: 12px;">最大文件大小: <?= ini_get('upload_max_filesize') ?></p>
                            
                            <label for="excel_file" class="upload-btn">
                                <i class="fas fa-folder-open"></i> 选择文件
                            </label>
                            <input type="file" name="excel_file" id="excel_file" class="file-input" 
                                   accept=".xls,.xlsx" required>
                            
                            <div class="file-info" id="fileInfo"></div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> 上传文件
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> 返回管理面板
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="upload-tips">
                <h3><i class="fas fa-lightbulb"></i> 上传说明</h3>
                <ul>
                    <li>支持 Microsoft Excel 97-2003 (.xls) 和 Excel 2007及以上 (.xlsx) 格式</li>
                    <li>文件大小限制: <?= ini_get('upload_max_filesize') ?></li>
                    <li>上传的文件将存储在安全目录中，只有管理员可以访问</li>
                    <li>上传后可以通过管理面板进行查看、重命名和删除操作</li>
                </ul>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
    // 拖放上传功能
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('excel_file');
    const fileInfo = document.getElementById('fileInfo');
    
    // 点击上传区域触发文件选择
    uploadArea.addEventListener('click', function(e) {
        if (e.target !== fileInput) {
            fileInput.click();
        }
    });
    
    // 显示选择的文件信息
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            const file = this.files[0];
            fileInfo.innerHTML = `
                <i class="fas fa-file-excel text-success"></i>
                <strong>${file.name}</strong> (${formatFileSize(file.size)})
            `;
            uploadArea.classList.add('dragover');
        }
    });
    
    // 拖放功能
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', function() {
        uploadArea.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            
            const file = e.dataTransfer.files[0];
            fileInfo.innerHTML = `
                <i class="fas fa-file-excel text-success"></i>
                <strong>${file.name}</strong> (${formatFileSize(file.size)})
            `;
        }
    });
    
    // 文件大小格式化函数
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // 表单提交前的验证
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        if (!fileInput.files.length) {
            e.preventDefault();
            alert('请选择要上传的Excel文件');
            return false;
        }
        
        const file = fileInput.files[0];
        const allowedExtensions = ['.xls', '.xlsx'];
        const fileExtension = file.name.toLowerCase().substring(file.name.lastIndexOf('.'));
        
        if (!allowedExtensions.includes(fileExtension)) {
            e.preventDefault();
            alert('只允许上传Excel文件 (.xls, .xlsx)');
            return false;
        }
        
        return true;
    });
    </script>
</body>
</html>