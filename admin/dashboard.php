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

if (!$poll) {
    die('Poll not found');
}

$entries = $db->queryAll(
    'SELECT * FROM entries WHERE poll_id = ? ORDER BY created_at DESC',
    [':poll_id' => $pollId]
);

$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
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
        <h1>
            <a href="<?php echo BASE_PATH ?: '/'; ?>/?id=<?php echo $poll['id']; ?>" target="_blank" class="text-decoration-none d-inline-flex align-items-center dashboard-poll-link">
                <?php echo htmlspecialchars($poll['title']); ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-box-arrow-up-right ms-2 text-secondary" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5z"/>
                    <path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0v-5z"/>
                </svg>
            </a>
        </h1>
        <p class="text-muted"><?php echo htmlspecialchars($poll['description'] ?? ''); ?></p>

        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5><?php echo translate('entries'); ?> (<?php echo count($entries); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($entries)): ?>
                            <p class="text-muted"><?php echo translate('no_entries'); ?></p>
                        <?php else: ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th><?php echo translate('name'); ?></th>
                                        <th><?php echo translate('email'); ?></th>
                                        <th><?php echo translate('joined'); ?></th>
                                        <th><?php echo translate('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($entries as $entry): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($entry['name']); ?></td>
                                        <td><?php echo htmlspecialchars($entry['email'] ?? '-'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($entry['created_at'])); ?></td>
                                        <td>
                                            <a href="<?php echo BASE_PATH; ?>/admin/edit.php?id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-primary"><?php echo translate('edit'); ?></a>
                                            <form method="POST" action="<?php echo BASE_PATH; ?>/api/delete_entry.php" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="entry_id" value="<?php echo $entry['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('<?php echo translate('confirm_delete'); ?>?')"><?php echo translate('delete'); ?></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h5><?php echo translate('settings'); ?></h5></div>
                    <div class="card-body">
                        <p><strong><?php echo translate('clear_date'); ?>:</strong> <?php echo htmlspecialchars($poll['next_clear_date']); ?></p>
                        <p><strong><?php echo translate('interval'); ?>:</strong> <?php echo $poll['recurring_interval_days']; ?> <?php echo translate('days'); ?></p>
                        <a href="<?php echo BASE_PATH; ?>/admin/settings.php" class="btn btn-secondary w-100"><?php echo translate('edit'); ?></a>
                    </div>
                </div>
            </div>
        </div>
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