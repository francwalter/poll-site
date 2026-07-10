<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/email.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

$pollId = intval($_POST['poll_id'] ?? 0);
$name = Security::sanitize($_POST['name'] ?? '');
$email = Security::sanitize($_POST['email'] ?? '');
$subscribe = 0; // will be set automatically if email is valid

if (empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Name required']);
    exit();
}

if (!Security::validateName($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Name must contain only letters and numbers without spaces']);
    exit();
}

if (!empty($email) && !Security::validateEmail($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email']);
    exit();
}

$db = Database::getInstance();
$poll = $db->queryOne('SELECT * FROM polls WHERE id = ? AND is_active = 1', [':id' => $pollId]);
if (!$poll) {
    http_response_code(404);
    echo json_encode(['error' => 'Poll not found']);
    exit();
}

$unsubscribeToken = null;
if (!empty($email) && Security::validateEmail($email)) {
    $subscribe = 1;
    $unsubscribeToken = Security::generateUnsubscribeToken();
}

$db->query(
    'INSERT INTO entries (poll_id, name, email, subscribed, unsubscribe_token) VALUES (?, ?, ?, ?, ?)',
    [':poll_id' => $pollId, ':name' => $name, ':email' => !empty($email) ? $email : null, ':subscribed' => $subscribe, ':token' => $unsubscribeToken]
);

$entryId = $db->lastInsertId();
$entry = $db->queryOne('SELECT * FROM entries WHERE id = ?', [':id' => $entryId]);
if ($entry) {
    EmailService::notifySubscribers($pollId, $entry);
}

http_response_code(201);
echo json_encode(['success' => true, 'entry_id' => $entryId]);
exit();