// Web查询系统主要JavaScript功能

$(document).ready(function() {
    // 初始化
    initializeTooltips();
    initializeFileUpload();
    initializeSearch();
    initializeDataTables();
});

// 初始化工具提示
function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// 初始化文件上传
function initializeFileUpload() {
    const uploadArea = $('.upload-area');
    
    if (uploadArea.length) {
        uploadArea.on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('dragover');
        });
        
        uploadArea.on('dragleave', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
        });
        
        uploadArea.on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
            
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                $('#file').prop('files', files);
                updateFileLabel(files[0].name);
            }
        });
    }
    
    // 文件选择变化
    $('#file').on('change', function() {
        const fileName = $(this).prop('files')[0]?.name || '选择文件';
        updateFileLabel(fileName);
    });
}

// 更新文件标签
function updateFileLabel(fileName) {
    const label = $('.file-label');
    if (label.length) {
        label.text(fileName);
    }
}

// 初始化搜索功能
function initializeSearch() {
    const searchForm = $('#searchForm');
    const resultsContainer = $('#searchResults');
    const loadingIndicator = $('#loadingIndicator');
    
    if (searchForm.length) {
        searchForm.on('submit', function(e) {
            e.preventDefault();
            performSearch();
        });
    }
    
    // 实时搜索（可选）
    $('#searchKeyword').on('input', debounce(function() {
        const keyword = $(this).val().trim();
        if (keyword.length >= 2 || keyword.length === 0) {
            performSearch();
        }
    }, 500));
}

// 执行搜索
function performSearch() {
    const fileId = $('#fileId').val();
    const keyword = $('#searchKeyword').val().trim();
    const sheetName = $('#sheetName').val();
    const resultsContainer = $('#searchResults');
    const loadingIndicator = $('#loadingIndicator');
    
    if (!fileId) {
        showAlert('请选择要查询的文件', 'warning');
        return;
    }
    
    // 显示加载动画
    loadingIndicator.show();
    resultsContainer.hide();
    
    // 发送搜索请求
    $.ajax({
        url: '/search',
        method: 'POST',
        data: {
            file_id: fileId,
            keyword: keyword,
            sheet_name: sheetName
        },
        success: function(response) {
            loadingIndicator.hide();
            
            if (response.success) {
                displaySearchResults(response);
            } else {
                showAlert('搜索失败: ' + response.error, 'danger');
                resultsContainer.hide();
            }
        },
        error: function(xhr, status, error) {
            loadingIndicator.hide();
            showAlert('搜索请求失败: ' + error, 'danger');
            resultsContainer.hide();
        }
    });
}

// 显示搜索结果
function displaySearchResults(response) {
    const resultsContainer = $('#searchResults');
    const resultCount = $('#resultCount');
    const resultTable = $('#resultTable tbody');
    
    // 更新结果计数
    resultCount.text(response.total_rows);
    
    // 清空现有结果
    resultTable.empty();
    
    if (response.data.length === 0) {
        resultTable.append('<tr><td colspan="100%" class="text-center">没有找到匹配的数据</td></tr>');
    } else {
        response.data.forEach(function(row, index) {
            const tr = $('<tr></tr>');
            
            // 添加序号
            tr.append('<td>' + (index + 1) + '</td>');
            
            // 添加数据列
            response.columns.forEach(function(column) {
                const value = row[column] || '';
                const displayValue = value.toString().substring(0, 100);
                tr.append('<td>' + escapeHtml(displayValue) + '</td>');
            });
            
            resultTable.append(tr);
        });
    }
    
    // 显示结果区域
    resultsContainer.show();
    
    // 滚动到结果区域
    resultsContainer[0].scrollIntoView({ behavior: 'smooth' });
}

// 初始化数据表格
function initializeDataTables() {
    if (typeof $.fn.DataTable !== 'undefined') {
        $('.data-table').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Chinese.json'
            }
        });
    }
}

// 显示警告信息
function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // 在页面顶部显示警告
    $('main.container-fluid').prepend(alertHtml);
    
    // 自动消失
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}

// HTML转义
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// 防抖函数
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// 确认删除
function confirmDelete(message, callback) {
    if (confirm(message || '确定要删除吗？')) {
        callback();
    }
}

// 批量操作
function initializeBatchOperations() {
    const selectAll = $('#selectAll');
    const itemCheckboxes = $('.item-checkbox');
    
    selectAll.on('change', function() {
        const isChecked = $(this).prop('checked');
        itemCheckboxes.prop('checked', isChecked);
        updateBatchButton();
    });
    
    itemCheckboxes.on('change', function() {
        updateBatchButton();
        updateSelectAll();
    });
}

// 更新批量按钮状态
function updateBatchButton() {
    const selectedCount = $('.item-checkbox:checked').length;
    const batchButton = $('#batchButton');
    
    if (batchButton.length) {
        batchButton.prop('disabled', selectedCount === 0);
        if (selectedCount > 0) {
            batchButton.text(`批量操作 (${selectedCount})`);
        } else {
            batchButton.text('批量操作');
        }
    }
}

// 更新全选状态
function updateSelectAll() {
    const totalCheckboxes = $('.item-checkbox').length;
    const checkedCheckboxes = $('.item-checkbox:checked').length;
    const selectAll = $('#selectAll');
    
    if (selectAll.length) {
        selectAll.prop('checked', totalCheckboxes === checkedCheckboxes);
    }
}

// 导出数据
function exportData(data, filename, type) {
    const blob = new Blob([data], { type: type });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
}

// 复制到剪贴板
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showAlert('已复制到剪贴板', 'success');
    }).catch(function(err) {
        console.error('复制失败:', err);
        showAlert('复制失败', 'danger');
    });
}

// 格式化文件大小
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// 格式化日期
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('zh-CN') + ' ' + date.toLocaleTimeString('zh-CN');
}

// AJAX通用封装
function ajaxRequest(url, method, data, successCallback, errorCallback) {
    $.ajax({
        url: url,
        method: method,
        data: data,
        success: successCallback,
        error: errorCallback || function(xhr, status, error) {
            showAlert('请求失败: ' + error, 'danger');
        }
    });
}

// 表单验证
function validateForm(formSelector) {
    const form = $(formSelector);
    let isValid = true;
    
    form.find('input[required], textarea[required], select[required]').each(function() {
        if (!$(this).val().trim()) {
            $(this).addClass('is-invalid');
            isValid = false;
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    return isValid;
}

// 清除表单验证状态
function clearFormValidation(formSelector) {
    $(formSelector).find('.is-invalid').removeClass('is-invalid');
}