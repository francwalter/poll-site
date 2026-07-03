<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
    http_response_code(403);
    exit();
}

$entryId = intval($_POST['entry_id'] ?? 0);
$db = Database::getInstance();
$entry = $db->queryOne('SELECT * FROM entries WHERE id = ?', [':id' => $entryId]);
if (!$entry) {
    http_response_code(404);
    exit();
}

$db->query(
    'INSERT INTO archived_entries (poll_id, name, email, created_at, poll_cycle) VALUES (?, ?, ?, ?, ?)',
    [':poll_id' => $entry['poll_id'], ':name' => $entry['name'], ':email' => $entry['email'], ':created_at' => $entry['created_at'], ':cycle' => date('Y-m-d')]
);

$db->query('DELETE FROM entries WHERE id = ?', [':id' => $entryId]);
header('Location: ' . BASE_PATH . '/admin/dashboard.php?id=' . $entry['poll_id']);
exit();