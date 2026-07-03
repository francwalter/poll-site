<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$token = $_GET['token'] ?? '';
if (empty($token)) {
    die('Invalid link');
}

$db = Database::getInstance();
$entry = $db->queryOne('SELECT * FROM entries WHERE unsubscribe_token = ?', [':token' => $token]);
if (!$entry) {
    die('Invalid link');
}

$db->query('UPDATE entries SET subscribed = 0 WHERE id = ?', [':id' => $entry['id']]);
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Unsubscribed</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="alert alert-success">Successfully unsubscribed</div>
    </div>
    <div class="position-fixed top-0 end-0 p-3">
        <button id="themeToggle" class="btn btn-outline-secondary" onclick="toggleTheme()">🌙 Dark Mode</button>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.BASE_PATH = <?php echo json_encode(BASE_PATH); ?>;
    </script>
    <script src="<?php echo BASE_PATH; ?>/assets/js/script.js"></script>
</body>
</html>