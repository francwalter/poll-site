<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/i18n.php';

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'de'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

$token = $_GET['token'] ?? '';
if (empty($token)) {
    http_response_code(400);
    die(translate('invalid_link'));
}

$db = Database::getInstance();
$entry = $db->queryOne('SELECT * FROM entries WHERE unsubscribe_token = ?', [':token' => $token]);
if (!$entry) {
    http_response_code(404);
    die(translate('invalid_link'));
}

$db->query(
    'INSERT INTO archived_entries (poll_id, name, email, created_at, poll_cycle) VALUES (?, ?, ?, ?, ?)',
    [
        ':poll_id' => $entry['poll_id'],
        ':name' => $entry['name'],
        ':email' => $entry['email'],
        ':created_at' => $entry['created_at'],
        ':cycle' => date('Y-m-d')
    ]
);

$db->query('DELETE FROM entries WHERE id = ?', [':id' => $entry['id']]);
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title><?php echo htmlspecialchars(translate('success')); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="alert alert-success"><?php echo htmlspecialchars(translate('successfully_deleted_participation')); ?></div>
    </div>
    <div class="position-fixed top-0 end-0 p-3">
        <button id="themeToggle" class="btn btn-outline-secondary" onclick="toggleTheme()"><?php echo translate('dark_mode'); ?></button>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.BASE_PATH = <?php echo json_encode(BASE_PATH); ?>;
    </script>
    <script src="<?php echo BASE_PATH; ?>/assets/js/script.js"></script>
</body>
</html>

