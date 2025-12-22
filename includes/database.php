<?php
/**
 * 数据库连接和操作类
 * 优化版本 - 包含连接池、错误处理、性能监控
 */

class Database {
    private $pdo;
    private $lastQuery;
    private $queryCount = 0;
    private $queryTime = 0;
    private static $instance = null;
    
    // 连接配置
    private $config = [
        'host' => DB_HOST,
        'username' => DB_USERNAME,
        'password' => DB_PASSWORD,
        'database' => DB_NAME,
        'charset' => DB_CHARSET,
        'port' => DB_PORT,
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true, // 持久连接
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    ];
    
    // 单例模式
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset={$this->config['charset']}";
            $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $this->config['options']);
            
            // 设置连接属性
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch (PDOException $e) {
            $this->logError('数据库连接失败: ' . $e->getMessage());
            throw new Exception('数据库连接失败，请检查配置');
        }
    }
    
    // 执行查询
    public function query($sql, $params = []) {
        $startTime = microtime(true);
        
        try {
            $this->lastQuery = $sql;
            $stmt = $this->pdo->prepare($sql);
            
            // 绑定参数
            foreach ($params as $key => $value) {
                $paramType = PDO::PARAM_STR;
                if (is_int($value)) {
                    $paramType = PDO::PARAM_INT;
                } elseif (is_bool($value)) {
                    $paramType = PDO::PARAM_BOOL;
                } elseif (is_null($value)) {
                    $paramType = PDO::PARAM_NULL;
                }
                $stmt->bindValue($key + 1, $value, $paramType);
            }
            
            $stmt->execute();
            
            $endTime = microtime(true);
            $queryTime = $endTime - $startTime;
            $this->queryCount++;
            $this->queryTime += $queryTime;
            
            // 记录慢查询
            if ($queryTime > 1.0) {
                $this->logSlowQuery($sql, $params, $queryTime);
            }
            
            return $stmt;
            
        } catch (PDOException $e) {
            $this->logError('SQL执行错误: ' . $e->getMessage() . ' SQL: ' . $sql);
            throw new Exception('数据库操作失败');
        }
    }
    
    // 获取单条记录
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    // 获取多条记录
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    // 插入记录
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->query($sql, array_values($data));
        return $this->pdo->lastInsertId();
    }
    
    // 更新记录
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $setParts[] = "{$field} = ?";
            $params[] = $value;
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . " WHERE {$where}";
        $params = array_merge($params, $whereParams);
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    // 删除记录
    public function delete($table, $where, $whereParams = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $whereParams);
        return $stmt->rowCount();
    }
    
    // 事务处理
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    // 获取数据库统计信息
    public function getStats() {
        return [
            'query_count' => $this->queryCount,
            'total_time' => round($this->queryTime, 3),
            'avg_time' => $this->queryCount > 0 ? round($this->queryTime / $this->queryCount, 3) : 0
        ];
    }
    
    // 错误日志
    private function logError($message) {
        $logFile = BASE_PATH . '/logs/db_errors.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    // 慢查询日志
    private function logSlowQuery($sql, $params, $time) {
        $logFile = BASE_PATH . '/logs/slow_queries.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$time}s] SQL: {$sql} Params: " . json_encode($params) . "\n";
        
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    // 数据库表结构检查
    public function checkTableStructure() {
        $requiredTables = [
            'users' => [
                'id', 'username', 'password', 'real_name', 'role', 'created_at', 'updated_at'
            ],
            'excel_files' => [
                'id', 'original_name', 'file_path', 'file_size', 'uploaded_by', 'uploaded_at'
            ]
        ];
        
        $missingTables = [];
        $missingColumns = [];
        
        foreach ($requiredTables as $table => $columns) {
            try {
                $stmt = $this->query("SHOW TABLES LIKE ?", [$table]);
                if (!$stmt->fetch()) {
                    $missingTables[] = $table;
                    continue;
                }
                
                // 检查表结构
                $stmt = $this->query("DESCRIBE {$table}");
                $existingColumns = [];
                while ($row = $stmt->fetch()) {
                    $existingColumns[] = $row['Field'];
                }
                
                foreach ($columns as $column) {
                    if (!in_array($column, $existingColumns)) {
                        $missingColumns[$table][] = $column;
                    }
                }
                
            } catch (Exception $e) {
                $missingTables[] = $table;
            }
        }
        
        return [
            'missing_tables' => $missingTables,
            'missing_columns' => $missingColumns
        ];
    }
}

// 全局数据库实例
function getDatabase() {
    return Database::getInstance();
}

?>