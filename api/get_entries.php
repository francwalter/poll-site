<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$pollId = intval($_GET['poll_id'] ?? 1);
$db = Database::getInstance();

$poll = $db->queryOne('SELECT * FROM polls WHERE id = ? AND is_active = 1', [':id' => $pollId]);
if (!$poll) {
    http_response_code(404);
    echo json_encode(['error' => 'Poll not found']);
    exit();
}

$entries = $db->queryAll(
    'SELECT id, name, created_at FROM entries WHERE poll_id = ? ORDER BY created_at DESC',
    [':poll_id' => $pollId]
);

echo json_encode(['poll' => $poll, 'entries' => $entries, 'count' => count($entries)]);
exit();