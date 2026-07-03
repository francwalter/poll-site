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
$name = Security::sanitize($_POST['name'] ?? '');
$email = Security::sanitize($_POST['email'] ?? '');

if (empty($name)) {
    http_response_code(400);
    exit();
}

if (!empty($email) && !Security::validateEmail($email)) {
    http_response_code(400);
    exit();
}

$db = Database::getInstance();
$entry = $db->queryOne('SELECT * FROM entries WHERE id = ?', [':id' => $entryId]);
if (!$entry) {
    http_response_code(404);
    exit();
}

$db->query(
    'UPDATE entries SET name = ?, email = ? WHERE id = ?',
    [':name' => $name, ':email' => !empty($email) ? $email : null, ':id' => $entryId]
);

header('Location: ' . BASE_PATH . '/admin/dashboard.php?id=' . $entry['poll_id']);
exit();