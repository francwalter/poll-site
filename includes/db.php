<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $db = null;
    
    private function __construct() {
        $this->init();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function init() {
        try {
            $data_dir = dirname(DB_PATH);
            if (!is_dir($data_dir)) {
                mkdir($data_dir, 0755, true);
            }
            
            $this->db = new SQLite3(DB_PATH);
            $this->db->busyTimeout(5000);
            $this->createTables();
        } catch (Exception $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    
    private function createTables() {
        $this->db->exec('CREATE TABLE IF NOT EXISTS polls (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            next_clear_date DATE,
            recurring_interval_days INTEGER DEFAULT 7,
            is_active BOOLEAN DEFAULT 1
        )');
        
        $this->db->exec('CREATE TABLE IF NOT EXISTS entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            poll_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            email TEXT,
            subscribed BOOLEAN DEFAULT 0,
            unsubscribe_token TEXT UNIQUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');
        
        $this->db->exec('CREATE TABLE IF NOT EXISTS archived_entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            poll_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            email TEXT,
            created_at DATETIME,
            archived_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            poll_cycle TEXT NOT NULL
        )');
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            if ($params) {
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value, is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT);
                }
            }
            return $stmt->execute();
        } catch (Exception $e) {
            if (DEBUG) throw new Exception('Database query failed: ' . $e->getMessage());
            return false;
        }
    }
    
    public function queryOne($sql, $params = []) {
        $result = $this->query($sql, $params);
        return $result ? $result->fetchArray(SQLITE3_ASSOC) : null;
    }
    
    public function queryAll($sql, $params = []) {
        $result = $this->query($sql, $params);
        $data = [];
        if ($result) {
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $data[] = $row;
            }
        }
        return $data;
    }
    
    public function lastInsertId() {
        return $this->db->lastInsertRowID();
    }
}

function db() {
    return Database::getInstance();
}