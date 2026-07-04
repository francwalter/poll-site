<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/i18n.php';

Auth::requireLogin();

$db = Database::getInstance();
$pollId = $_GET['id'] ?? 1;
$poll = $db->queryOne('SELECT * FROM polls WHERE id = ?', [':id' => $pollId]);
if (!$poll) die('Poll not found');

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCSRFToken($_POST['csrf_token'] ?? '');

    // Check if we are updating password or poll settings
    if (isset($_POST['new_password'])) {
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        $currentHash = getAdminPasswordHash();
        if (Security::verifyPassword($oldPassword, $currentHash)) {
            $newHash = Security::hashPassword($newPassword);

            // Update .env file
            $envPath = __DIR__ . '/../.env';
            if (file_exists($envPath)) {
                $envContent = file_get_contents($envPath);
                // Regex to replace ADMIN_PASSWORD_HASH=...
                // Ensure the hash is properly written to the file without accidental character corruption
                $escapedHash = str_replace('$', '\\$', $newHash);
                $newEnvContent = preg_replace('/^ADMIN_PASSWORD_HASH=.*/m', 'ADMIN_PASSWORD_HASH=' . $escapedHash, $envContent, 1, $count);

                if ($count === 0) {
                    $newEnvContent = rtrim($envContent, "\r\n") . PHP_EOL . 'ADMIN_PASSWORD_HASH=' . $newHash . PHP_EOL;
                }                if ($newEnvContent !== $envContent) {
                    file_put_contents($envPath, $newEnvContent);

                    // Clear internal cache/state if necessary
                    putenv("ADMIN_PASSWORD_HASH=$newHash");

                    $message = 'Password updated successfully.';
                } else {
                    $message = 'Error: Could not update .env file.';
                }
            } else {
                $message = 'Error: .env file not found.';
            }
        } else {
            $message = 'Incorrect current password.';
        }
    } else {
    $title = Security::sanitize($_POST['title'] ?? '');
    $slug = Security::sanitize($_POST['slug'] ?? '');
    $description = Security::sanitize($_POST['description'] ?? '');
    $interval = intval($_POST['interval'] ?? 7);
    $clearDate = $_POST['clear_date'] ?? '';

    $db->query(
        'UPDATE polls SET title = ?, slug = ?, description = ?, recurring_interval_days = ?, next_clear_date = ? WHERE id = ?',
        [':title' => $title, ':slug' => $slug, ':desc' => $description, ':interval' => $interval, ':date' => $clearDate, ':id' => $pollId]
    );
    $message = 'Updated successfully';
    $poll = $db->queryOne('SELECT * FROM polls WHERE id = ?', [':id' => $pollId]);
}
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
        <h1><?php echo translate('poll_settings'); ?></h1>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo strpos($message, 'Incorrect') !== false || strpos($message, 'Error:') !== false ? 'warning' : 'success'; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="POST" class="col-lg-8">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <div class="mb-3">
                <label class="form-label"><?php echo translate('poll_title'); ?></label>
                <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($poll['title']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Slug</label>
                <input type="text" class="form-control" name="slug" value="<?php echo htmlspecialchars($poll['slug'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label"><?php echo translate('poll_description'); ?></label>
                <textarea class="form-control" name="description" rows="5"><?php echo htmlspecialchars($poll['description'] ?? ''); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label"><?php echo translate('interval'); ?> (<?php echo translate('days'); ?>)</label>
                <input type="number" class="form-control" name="interval" value="<?php echo htmlspecialchars($poll['recurring_interval_days']); ?>" min="1">
            </div>
            <div class="mb-3">
                <label class="form-label"><?php echo translate('clear_date'); ?></label>
                <input type="date" class="form-control" name="clear_date" value="<?php echo htmlspecialchars($poll['next_clear_date']); ?>">
            </div>
            <button type="submit" class="btn btn-primary"><?php echo translate('save'); ?></button>
            <a href="<?php echo BASE_PATH; ?>/admin/dashboard.php" class="btn btn-secondary"><?php echo translate('cancel'); ?></a>
        </form>

        <hr class="my-5">

        <h3><?php echo translate('change_password'); ?></h3>
        <form method="POST" class="col-lg-8">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <div class="mb-3">
                <label class="form-label"><?php echo translate('current_password'); ?></label>
                <input type="password" class="form-control" name="old_password" required>
            </div>
            <div class="mb-3">
                <label class="form-label"><?php echo translate('new_password'); ?></label>
                <input type="password" class="form-control" name="new_password" required>
            </div>
            <button type="submit" class="btn btn-warning"><?php echo translate('update_password'); ?></button>
        </form>
    </div>
    <div class="position-fixed top-0 start-0 p-3">
        <a href="<?php echo BASE_PATH; ?>/set_language.php?lang=en" class="btn btn-sm btn-outline-secondary">EN</a>
        <a href="<?php echo BASE_PATH; ?>/set_language.php?lang=de" class="btn btn-sm btn-outline-secondary">DE</a>
    </div>
    <div class="position-fixed bottom-0 end-0 p-3">
        <button id="themeToggle" class="btn btn-sm btn-outline-secondary" onclick="toggleTheme()"><?php echo translate('dark_mode'); ?></button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.BASE_PATH = <?php echo json_encode(BASE_PATH); ?>;
    </script>
    <script src="<?php echo BASE_PATH; ?>/assets/js/script.js"></script>
</body>
</html>