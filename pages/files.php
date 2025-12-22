<?php
/**
 * 文件管理页面
 */

// 处理文件上传
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $result = handleFileUpload($_FILES['file']);
    
    if ($result['success']) {
        $success = '文件上传成功！';
    } else {
        $error = $result['message'];
    }
}

// 获取文件列表
$page = max(1, $_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';

$files = getFiles($limit, $offset, $search);
$totalFiles = getTotalFiles($search);
$totalPages = ceil($totalFiles / $limit);
?>

<div class="space-y-6">
    <!-- 页面标题和操作栏 -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 font-display">文件管理</h2>
            <p class="text-gray-600">上传、管理和组织您的数据文件</p>
        </div>
        
        <div class="flex space-x-3">
            <button onclick="document.getElementById('uploadModal').classList.remove('hidden')" 
                    class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                上传文件
            </button>
        </div>
    </div>

    <!-- 搜索和筛选 -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <form method="get">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="搜索文件名或上传者...">
                </form>
            </div>
        </div>
    </div>

    <!-- 文件列表 -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">文件名</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">大小</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">类型</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">上传者</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">上传时间</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($files)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-gray-500"><?php echo $search ? '没有找到匹配的文件' : '暂无文件，请上传第一个文件'; ?></p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($files as $file): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($file['original_name']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($file['filename']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo formatFileSize($file['file_size']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($file['file_type']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($file['username'] ?? 'Unknown'); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo date('Y-m-d H:i', strtotime($file['upload_time'])); ?></td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <button class="text-green-600 hover:text-green-800 transition-colors" title="下载">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                        </svg>
                                    </button>
                                    <button class="text-blue-600 hover:text-blue-800 transition-colors" title="查看数据">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                    <button onclick="confirmDelete(<?php echo $file['id']; ?>)" class="text-red-600 hover:text-red-800 transition-colors" title="删除">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 分页 -->
        <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    显示 <?php echo $offset + 1; ?> 到 <?php echo min($offset + $limit, $totalFiles); ?> 共 <?php echo $totalFiles; ?> 条记录
                </div>
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?action=files&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" 
                           class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50">上一页</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?action=files&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                           class="px-3 py-1 text-sm border <?php echo $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50'; ?> rounded-md">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?action=files&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" 
                           class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50">下一页</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 上传模态框 -->
<div id="uploadModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">上传文件</h3>
            <button onclick="document.getElementById('uploadModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <span class="text-red-800"><?php echo htmlspecialchars($error); ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="text-green-800"><?php echo htmlspecialchars($success); ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">选择文件</label>
                <input type="file" name="file" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       accept=".xlsx,.xls,.csv" required>
                <p class="text-xs text-gray-500 mt-1">支持 Excel (.xlsx, .xls) 和 CSV 格式，最大 10MB</p>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('uploadModal').classList.add('hidden')" 
                        class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">
                    取消
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    上传
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmDelete(fileId) {
    if (confirm('确定要删除这个文件吗？此操作不可撤销。')) {
        // 实现删除逻辑
        window.location.href = '?action=files&delete=' + fileId;
    }
}

// 如果有成功消息，3秒后关闭模态框
<?php if (isset($success)): ?>
setTimeout(() => {
    document.getElementById('uploadModal').classList.add('hidden');
}, 3000);
<?php endif; ?>
</script>

<?php
/**
 * 处理文件上传
 */
function handleFileUpload($file) {
    $allowedTypes = ['xlsx', 'xls', 'csv'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    // 检查文件大小
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => '文件大小超过限制（最大10MB）'];
    }
    
    // 检查文件类型
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedTypes)) {
        return ['success' => false, 'message' => '不支持的文件类型'];
    }
    
    // 生成唯一文件名
    $filename = uniqid() . '_' . $file['name'];
    $uploadPath = '../uploads/' . $filename;
    
    // 移动文件
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // 保存到数据库
        $conn = getDatabaseConnection();
        $stmt = $conn->prepare("INSERT INTO files (filename, original_name, file_type, file_size, file_path, uploader_id) VALUES (?, ?, ?, ?, ?, ?)");
        
        $fileType = getFileType($fileExtension);
        $uploaderId = getCurrentUser()['id'];
        
        $stmt->bind_param("ssssii", $filename, $file['name'], $fileType, $file['size'], $uploadPath, $uploaderId);
        
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return ['success' => true, 'message' => '文件上传成功'];
        } else {
            $stmt->close();
            $conn->close();
            unlink($uploadPath); // 删除已上传的文件
            return ['success' => false, 'message' => '数据库保存失败'];
        }
    } else {
        return ['success' => false, 'message' => '文件上传失败'];
    }
}

/**
 * 获取文件类型描述
 */
function getFileType($extension) {
    $types = [
        'xlsx' => 'Excel 2007+',
        'xls' => 'Excel 97-2003',
        'csv' => 'CSV'
    ];
    return $types[$extension] ?? 'Unknown';
}

/**
 * 获取文件列表
 */
function getFiles($limit, $offset, $search = '') {
    $conn = getDatabaseConnection();
    
    $files = [];
    $sql = "
        SELECT f.*, u.username 
        FROM files f 
        LEFT JOIN users u ON f.uploader_id = u.id 
        WHERE f.status = 'active'
    ";
    
    if ($search) {
        $sql .= " AND (f.original_name LIKE ? OR u.username LIKE ?)";
    }
    
    $sql .= " ORDER BY f.upload_time DESC LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($search) {
        $searchParam = "%$search%";
        $stmt->bind_param("sssii", $searchParam, $searchParam, $limit, $offset);
    } else {
        $stmt->bind_param("ii", $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $files[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    return $files;
}

/**
 * 获取文件总数
 */
function getTotalFiles($search = '') {
    $conn = getDatabaseConnection();
    
    $sql = "SELECT COUNT(*) as count FROM files f LEFT JOIN users u ON f.uploader_id = u.id WHERE f.status = 'active'";
    
    if ($search) {
        $sql .= " AND (f.original_name LIKE ? OR u.username LIKE ?)";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($search) {
        $searchParam = "%$search%";
        $stmt->bind_param("ss", $searchParam, $searchParam);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    return $row['count'];
}
?>