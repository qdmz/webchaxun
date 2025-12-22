<?php
/**
 * 数据查询页面
 */

// 处理搜索请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $searchTerm = $_POST['search'] ?? '';
    $filters = $_POST['filters'] ?? [];
    
    $results = searchData($searchTerm, $filters);
    
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }
}

// 获取查询统计
$stats = getQueryStats();
$recentSearches = getRecentSearches();
?>

<div class="space-y-6">
    <!-- 页面标题和搜索栏 -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 font-display">数据查询</h2>
            <p class="text-gray-600">搜索和筛选系统中的数据记录</p>
        </div>
    </div>

    <!-- 搜索表单 -->
    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <form method="post" id="searchForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">搜索关键词</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="text" name="search" 
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="输入搜索关键词..." 
                               value="<?php echo htmlspecialchars($_POST['search'] ?? ''); ?>">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">文件筛选</label>
                    <select name="file_id" class="form-control">
                        <option value="">所有文件</option>
                        <?php
                        $files = getAvailableFiles();
                        foreach ($files as $file) {
                            $selected = isset($_POST['file_id']) && $_POST['file_id'] == $file['id'] ? 'selected' : '';
                            echo "<option value='{$file['id']}' $selected>" . htmlspecialchars($file['original_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">工作表</label>
                    <input type="text" name="sheet_name" 
                           class="form-control"
                           placeholder="工作表名称" 
                           value="<?php echo htmlspecialchars($_POST['sheet_name'] ?? ''); ?>">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">日期范围</label>
                    <div class="flex space-x-2">
                        <input type="date" name="date_from" 
                               class="form-control"
                               value="<?php echo htmlspecialchars($_POST['date_from'] ?? ''); ?>">
                        <input type="date" name="date_to" 
                               class="form-control"
                               value="<?php echo htmlspecialchars($_POST['date_to'] ?? ''); ?>">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">显示数量</label>
                    <select name="limit" class="form-control">
                        <option value="20" <?php echo (($_POST['limit'] ?? 20) == 20) ? 'selected' : ''; ?>>20 条</option>
                        <option value="50" <?php echo (($_POST['limit'] ?? 20) == 50) ? 'selected' : ''; ?>>50 条</option>
                        <option value="100" <?php echo (($_POST['limit'] ?? 20) == 100) ? 'selected' : ''; ?>>100 条</option>
                        <option value="200" <?php echo (($_POST['limit'] ?? 20) == 200) ? 'selected' : ''; ?>>200 条</option>
                    </select>
                </div>
            </div>
            
            <div class="flex justify-between items-center">
                <button type="button" onclick="clearForm()" 
                        class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">
                    清空条件
                </button>
                <div class="space-x-3">
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        搜索
                    </button>
                    <button type="button" onclick="exportResults()" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        导出
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- 搜索结果 -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">搜索结果</h3>
        </div>
        
        <div class="p-6">
            <?php if (isset($_POST['search'])): ?>
                <?php
                $page = max(1, $_GET['page'] ?? 1);
                $limit = 20;
                $offset = ($page - 1) * $limit;
                
                $results = searchDataTable($_POST['search'], $offset, $limit);
                $total = searchCount($_POST['search']);
                $totalPages = ceil($total / $limit);
                
                if (!empty($results)): ?>
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">
                            找到 <span class="font-semibold text-gray-900"><?php echo $total; ?></span> 条结果
                        </p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="sortable" onclick="sortTable('file_name')">文件名</th>
                                    <th class="sortable" onclick="sortTable('sheet_name')">工作表</th>
                                    <th class="sortable" onclick="sortTable('row_number')">行号</th>
                                    <th class="sortable" onclick="sortTable('created_at')">时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['file_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['sheet_name']); ?></td>
                                    <td><?php echo $record['row_number']; ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($record['created_at'])); ?></td>
                                    <td>
                                        <div class="flex space-x-2">
                                            <button onclick="viewDetails(<?php echo $record['id']; ?>)" 
                                                    class="text-blue-600 hover:text-blue-800" title="查看详情">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7z"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- 分页 -->
                    <?php if ($totalPages > 1): ?>
                    <div class="mt-6 flex justify-between items-center">
                        <div class="text-sm text-gray-700">
                            显示 <?php echo $offset + 1; ?> 到 <?php echo min($offset + $limit, $total); ?> 共 <?php echo $total; ?> 条记录
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?action=query&search=<?php echo urlencode($_POST['search']); ?>&page=<?php echo $page - 1; ?>" 
                                   class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50">上一页</a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?action=query&search=<?php echo urlencode($_POST['search']); ?>&page=<?php echo $i; ?>" 
                                   class="px-3 py-1 text-sm border <?php echo $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50'; ?> rounded-md">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?action=query&search=<?php echo urlencode($_POST['search']); ?>&page=<?php echo $page + 1; ?>" 
                                   class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50">下一页</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="text-center py-12">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <p class="text-gray-500">没有找到匹配的数据记录</p>
                        <p class="text-sm text-gray-400 mt-2">请尝试调整搜索条件</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-12">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-gray-500">请输入搜索条件开始查询</p>
                    <p class="text-sm text-gray-400 mt-2">支持关键词搜索和高级筛选</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 查询统计 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 查询统计 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">查询统计</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">今日查询</span>
                        <span class="text-sm font-medium text-gray-900"><?php echo $stats['today_queries']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">本周查询</span>
                        <span class="text-sm font-medium text-gray-900"><?php echo $stats['week_queries']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">总记录数</span>
                        <span class="text-sm font-medium text-gray-900"><?php echo number_format($stats['total_records']); ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">总查询数</span>
                        <span class="text-sm font-medium text-gray-900"><?php echo number_format($stats['total_queries']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 最近搜索 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">最近搜索</h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <?php if (empty($recentSearches)): ?>
                    <p class="text-sm text-gray-500 text-center">暂无搜索记录</p>
                    <?php else: ?>
                        <?php foreach ($recentSearches as $search): ?>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($search['term']); ?></p>
                                <p class="text-xs text-gray-500">
                                    <?php echo date('m-d H:i', strtotime($search['created_at'])); ?>
                                    · <?php echo $search['results']; ?> 个结果
                                </p>
                            </div>
                            <button onclick="quickSearch('<?php echo addslashes($search['term']); ?>')" 
                                    class="text-blue-600 hover:text-blue-800 text-sm">
                                重新搜索
                            </button>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function clearForm() {
    document.getElementById('searchForm').reset();
}

function exportResults() {
    const searchTerm = document.querySelector('input[name="search"]').value;
    if (!searchTerm) {
        alert('请先进行搜索');
        return;
    }
    
    // 构建导出URL
    const exportUrl = '?action=export&search=' + encodeURIComponent(searchTerm);
    window.open(exportUrl, '_blank');
}

function quickSearch(term) {
    document.querySelector('input[name="search"]').value = term;
    document.getElementById('searchForm').submit();
}

function viewDetails(id) {
    // 打开详情模态框
    alert('查看详情功能开发中...');
}
</script>

<?php
/**
 * 搜索数据
 */
function searchData($searchTerm, $filters = []) {
    // 这里实现实际的数据搜索逻辑
    return [
        'results' => [],
        'total' => 0,
        'page' => 1,
        'limit' => 20
    ];
}

/**
 * 获取可用文件
 */
function getAvailableFiles() {
    $conn = getDatabaseConnection();
    $files = [];
    
    $result = $conn->query("SELECT id, original_name FROM files WHERE status = 'active' ORDER BY upload_time DESC");
    
    while ($row = $result->fetch_assoc()) {
        $files[] = $row;
    }
    
    $conn->close();
    return $files;
}

/**
 * 搜索数据表
 */
function searchDataTable($searchTerm, $offset, $limit) {
    // 这里实现实际的数据表搜索
    return [];
}

/**
 * 统计搜索结果数
 */
function searchCount($searchTerm) {
    // 这里实现实际的计数逻辑
    return 0;
}

/**
 * 获取查询统计
 */
function getQueryStats() {
    return [
        'today_queries' => 25,
        'week_queries' => 156,
        'total_records' => 15420,
        'total_queries' => 2341
    ];
}

/**
 * 获取最近搜索
 */
function getRecentSearches($limit = 5) {
    // 这里应该从搜索日志表获取
    return [];
}
?>