<?php
// admin/dashboard.php
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
$files = ExcelReader::getAvailableFiles();

$error = '';
$success = '';

// 删除文件
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $fileId = intval($_GET['delete']);
    
    $sql = "SELECT * FROM excel_files WHERE id = ?";
    $file = $db->fetchOne($sql, [$fileId]);
    
    if ($file) {
        // 删除物理文件
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
        
        // 删除数据库记录
        $sql = "DELETE FROM excel_files WHERE id = ?";
        $db->query($sql, [$fileId]);
        
        header('Location: dashboard.php?msg=deleted');
        exit();
    }
}

// 重命名文件处理
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rename_file'])) {
    $fileId = intval($_POST['file_id']);
    $newName = trim($_POST['new_name']);
    
    if (empty($newName)) {
        $error = "文件名不能为空";
    } else {
        $sql = "SELECT * FROM excel_files WHERE id = ?";
        $file = $db->fetchOne($sql, [$fileId]);
        
        if ($file) {
            // 获取文件扩展名
            $extension = pathinfo($file['original_name'], PATHINFO_EXTENSION);
            $newNameWithExt = $newName . '.' . $extension;
            
            // 更新数据库
            $sql = "UPDATE excel_files SET original_name = ? WHERE id = ?";
            $db->query($sql, [$newNameWithExt, $fileId]);
            
            $success = "文件重命名成功";
            
            // 刷新文件列表
            $files = ExcelReader::getAvailableFiles();
        } else {
            $error = "文件不存在";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理面板 - Excel数据查询系统</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 400px;
            max-width: 90%;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-header h3 {
            margin: 0;
        }
        .close {
            font-size: 24px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-tachometer-alt"></i> 管理面板</h1>
           <nav>
    <span>欢迎, <?= escape($admin['username']) ?></span>
    <a href="dashboard.php"><i class="fas fa-home"></i> 面板首页</a>
    <a href="upload.php"><i class="fas fa-upload"></i> 上传文件</a>
    <a href="change-password.php"><i class="fas fa-key"></i> 修改密码</a> 
    <a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> 前台查看</a>
<a href="settings.php" style="background: rgba(255,255,255,0.3);"><i class="fas fa-cog"></i> 系统设置</a>
  <a href="users.php"><i class="fas fa-key"></i> 用户管理</a> 
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> 退出登录</a>
</nav>
        </header>
        
        <main>
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 文件删除成功
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'uploaded'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 文件上传成功
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= escape($error) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= escape($success) ?>
            </div>
            <?php endif; ?>
            
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-icon bg-primary">
                        <i class="fas fa-file-excel"></i>
                    </div>
                    <div class="card-content">
                        <h3><?= count($files) ?></h3>
                        <p>Excel文件总数</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-icon bg-success">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="card-content">
                        <h3><?= getTotalFileSize($files) ?></h3>
                        <p>总存储大小</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-icon bg-warning">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="card-content">
                        <h3>1</h3>
                        <p>管理员</p>
                    </div>
                </div>
            </div>
            
            <section class="file-management">
                <h2><i class="fas fa-list"></i> 文件管理</h2>
                <a href="upload.php" class="btn btn-primary mb-3">
                    <i class="fas fa-upload"></i> 上传新文件
                </a>
                
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>文件名</th>
                                <th>大小</th>
                                <th>上传时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($files)): ?>
                            <tr>
                                <td colspan="5" class="text-center">暂无文件</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($files as $file): ?>
                                <tr>
                                    <td><?= $file['id'] ?></td>
                                    <td>
                                        <i class="fas fa-file-excel text-success"></i>
                                        <?= escape($file['original_name']) ?>
                                    </td>
                                    <td><?= formatFileSize(filesize($file['file_path'])) ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($file['upload_time'])) ?></td>
                                    <td>
                                        <a href="../index.php?file_id=<?= $file['id'] ?>" 
                                           target="_blank" 
                                           class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> 查看
                                        </a>
                                        <button onclick="openRenameModal(<?= $file['id'] ?>, '<?= escape(pathinfo($file['original_name'], PATHINFO_FILENAME)) ?>')" 
                                                class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> 重命名
                                        </button>
                                        <a href="dashboard.php?delete=<?= $file['id'] ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('确定要删除这个文件吗？')">
                                            <i class="fas fa-trash"></i> 删除
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
    
    <!-- 重命名模态框 -->
    <div id="renameModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> 重命名文件</h3>
                <span class="close" onclick="closeRenameModal()">&times;</span>
            </div>
            <form method="POST" id="renameForm">
                <input type="hidden" name="file_id" id="renameFileId">
                <div class="form-group">
                    <label for="new_name">新文件名（不含扩展名）:</label>
                    <input type="text" id="new_name" name="new_name" required 
                           placeholder="输入新文件名" class="form-control">
                </div>
                <div class="form-actions">
                    <button type="submit" name="rename_file" class="btn btn-primary">
                        <i class="fas fa-save"></i> 保存
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeRenameModal()">
                        <i class="fas fa-times"></i> 取消
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
    // 重命名模态框功能
    function openRenameModal(fileId, fileName) {
        document.getElementById('renameFileId').value = fileId;
        document.getElementById('new_name').value = fileName;
        document.getElementById('renameModal').style.display = 'block';
    }
    
    function closeRenameModal() {
        document.getElementById('renameModal').style.display = 'none';
        document.getElementById('renameForm').reset();
    }
    
    // 点击模态框外部关闭
    window.onclick = function(event) {
        var modal = document.getElementById('renameModal');
        if (event.target == modal) {
            closeRenameModal();
        }
    }
    
    // 回车键提交表单
    document.getElementById('new_name').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('renameForm').submit();
        }
    });
    </script>
</body>
</html>