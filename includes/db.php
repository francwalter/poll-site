<?php
require_once __DIR__ . '/config.php';

class Database
{
    private static $instance = null;
    private $db = null;

    private function __construct()
    {
        $this->init();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init()
    {
        try {
            $data_dir = dirname(DB_PATH);
            if (!is_dir($data_dir)) {
                mkdir($data_dir, 0755, true);
            }

            $this->db = new SQLite3(DB_PATH);
            $this->db->busyTimeout(5000);
            $this->createTables();
            $this->generateSlugs();
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
            is_active BOOLEAN DEFAULT 1
        )');

        // Add slug column for multi-poll site feature
        $this->db->exec('ALTER TABLE polls ADD COLUMN slug TEXT');

        // Check if slug column is unique, if not, make it unique
        $result = $this->db->query("PRAGMA index_list('polls')");
        $hasUniqueSlugIndex = false;
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if ($row['name'] && strpos($row['name'], 'polls_slug_unique') !== false) {
                $hasUniqueSlugIndex = true;
                break;
        }
    }

        if (!$hasUniqueSlugIndex) {
            try {
                $this->db->exec('CREATE UNIQUE INDEX IF NOT EXISTS polls_slug_unique ON polls(slug)');
            } catch (Exception $e) {
                error_log('Failed to create unique index for slug: ' . $e->getMessage());
    }
}

        $this->db->exec('CREATE TABLE IF NOT EXISTS entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            poll_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            votes INTEGER DEFAULT 0,
            FOREIGN KEY (poll_id) REFERENCES polls(id)
        )');

        $this->db->exec('CREATE TABLE IF NOT EXISTS votes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            poll_id INTEGER NOT NULL,
            entry_id INTEGER NOT NULL,
            ip_address TEXT NOT NULL,
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (poll_id) REFERENCES polls(id),
            FOREIGN KEY (entry_id) REFERENCES entries(id),
            UNIQUE(poll_id, ip_address)
        )');

        // Note: The following table definitions appear to be duplicates and should be reviewed
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

        // Auto-seed a default poll if the table is empty
        $count = $this->db->querySingle('SELECT COUNT(*) FROM polls');
        if ($count === 0) {
            $nextWeek = date('Y-m-d', strtotime('+7 days'));
            $stmt = $this->db->prepare('INSERT INTO polls (id, title, description, next_clear_date, recurring_interval_days, is_active) VALUES (1, ?, ?, ?, 7, 1)');
            $stmt->bindValue(1, 'Weekly Lunch Poll');
            $stmt->bindValue(2, 'Welcome to the weekly recurring lunch poll. Enter your name to participate!');
            $stmt->bindValue(3, $nextWeek);
            $stmt->execute();
        }
    }

    public function generateSlugs() {
        $polls = $this->queryAll('SELECT id, title FROM polls WHERE slug IS NULL OR slug = ""');
        foreach ($polls as $poll) {
            $slug = $this->createSlug($poll['title']);
            $this->query('UPDATE polls SET slug = ? WHERE id = ?', [$slug, $poll['id']]);
        }
        // Re-create unique index after populating slugs to ensure uniqueness
        $this->db->exec('DROP INDEX IF EXISTS polls_slug_unique');
        $this->db->exec('CREATE UNIQUE INDEX IF NOT EXISTS polls_slug_unique ON polls(slug)');
    }

    private function createSlug($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text ?: 'poll-' . uniqid();
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            if ($params) {
                $hasPositionalPlaceholders = (strpos($sql, '?') !== false);
                $isNamedArray = false;
                foreach (array_keys($params) as $k) {
                    if (is_string($k)) {
                        $isNamedArray = true;
                        break;
                    }
                }

                $i = 1;
                foreach ($params as $key => $value) {
                    $bindKey = ($hasPositionalPlaceholders && $isNamedArray) ? $i++ : $key;
                    $stmt->bindValue($bindKey, $value, is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT);
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
