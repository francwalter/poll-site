<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

Auth::requireLogin();

$db = Database::getInstance();
$pollId = $_GET['id'] ?? 1;
$poll = $db->queryOne('SELECT * FROM polls WHERE id = ?', [':id' => $pollId]);
if (!$poll) die('Poll not found');

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = Security::sanitize($_POST['title'] ?? '');
    $description = Security::sanitize($_POST['description'] ?? '');
    $interval = intval($_POST['interval'] ?? 7);
    $clearDate = $_POST['clear_date'] ?? '';
    
    $db->query(
        'UPDATE polls SET title = ?, description = ?, recurring_interval_days = ?, next_clear_date = ? WHERE id = ?',
        [':title' => $title, ':desc' => $description, ':interval' => $interval, ':date' => $clearDate, ':id' => $pollId]
    );
    $message = 'Updated successfully';
    $poll = $db->queryOne('SELECT * FROM polls WHERE id = ?', [':id' => $pollId]);
}

$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_PATH; ?>/admin/dashboard.php">Poll Admin</a>
            <div class="d-flex">
                <a class="btn btn-outline-light me-2" href="<?php echo BASE_PATH; ?>/admin/dashboard.php">Dashboard</a>
                <a class="btn btn-outline-light me-2" href="<?php echo BASE_PATH; ?>/admin/settings.php">Settings</a>
                <a class="btn btn-outline-light me-2" href="<?php echo BASE_PATH; ?>/admin/history.php">History</a>
                <a class="btn btn-outline-danger" href="<?php echo BASE_PATH; ?>/admin/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h1>Poll Settings</h1>
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="POST" class="col-lg-8">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($poll['title']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="5"><?php echo htmlspecialchars($poll['description'] ?? ''); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Interval (days)</label>
                <input type="number" class="form-control" name="interval" value="<?php echo $poll['recurring_interval_days']; ?>" min="1">
            </div>
            <div class="mb-3">
                <label class="form-label">Clear Date</label>
                <input type="date" class="form-control" name="clear_date" value="<?php echo htmlspecialchars($poll['next_clear_date']); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="<?php echo BASE_PATH; ?>/admin/dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
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