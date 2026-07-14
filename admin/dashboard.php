<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/i18n.php';

Auth::requireLogin();

$db = Database::getInstance();

// Check if we are viewing a specific poll or the dashboard list
if (isset($_GET['id'])) {
    $pollId = intval($_GET['id']);
    $poll = $db->queryOne('SELECT * FROM polls WHERE id = ?', [':id' => $pollId]);

if (!$poll) {
    die(translate('poll_not_found'));
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
        <title><?php echo translate('poll'); ?>: <?php echo htmlspecialchars($poll['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container position-relative flex-wrap">
            <a class="navbar-brand position-absolute start-50 translate-middle-x d-none d-md-block" href="<?php echo BASE_PATH; ?>/admin/dashboard.php"><?php echo translate('poll_admin'); ?></a>
            <a class="navbar-brand w-100 text-center d-md-none mb-2" href="<?php echo BASE_PATH; ?>/admin/dashboard.php"><?php echo translate('poll_admin'); ?></a>
            <div class="d-flex flex-wrap gap-2 ms-auto justify-content-end">
                <a class="btn btn-sm btn-outline-light" href="<?php echo BASE_PATH; ?>/admin/dashboard.php"><?php echo translate('dashboard'); ?></a>
                <a class="btn btn-sm btn-outline-light" href="<?php echo BASE_PATH; ?>/admin/settings.php?id=<?php echo $pollId; ?>"><?php echo translate('settings'); ?></a>
                <a class="btn btn-sm btn-outline-light" href="<?php echo BASE_PATH; ?>/admin/history.php?id=<?php echo $pollId; ?>"><?php echo translate('history'); ?></a>
                <a class="btn btn-sm btn-outline-danger" href="<?php echo BASE_PATH; ?>/admin/logout.php"><?php echo translate('logout'); ?></a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h1>
            <a href="<?php echo BASE_PATH; ?>/<?php echo htmlspecialchars($poll['slug']); ?>" target="_blank" class="text-decoration-none">
            <?php echo htmlspecialchars($poll['title']); ?>
            </a>
        </h1>
        <p class="text-muted"><?php echo htmlspecialchars($poll['description'] ?? ''); ?></p>

        <div class="row mt-4">
            <div class="col-lg-12">
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
                                        <td><?php echo date('M d, Y H:i', strtotime($entry['created_at'])); ?></td>
                                        <td>
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
        </div>
    </div>
    <div class="position-fixed top-0 start-0 p-3">
        <a href="<?php echo BASE_PATH; ?>/set_language.php?lang=en" class="btn btn-sm btn-outline-secondary">EN</a>
        <a href="<?php echo BASE_PATH; ?>/set_language.php?lang=de" class="btn btn-sm btn-outline-secondary">DE</a>
    </div>
    <div class="position-fixed bottom-0 end-0 p-3">
        <button id="themeToggle" class="btn btn-sm btn-outline-secondary" onclick="toggleTheme()"><?php echo translate('dark_mode'); ?></button>
    </div>
    <script>window.BASE_PATH = <?php echo json_encode(BASE_PATH); ?>;</script>
    <script src="<?php echo BASE_PATH; ?>/assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
    exit();
}

// Default: Show all polls
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action' === 'create']) {
    Security::verifyCSRFToken($_POST['csrf_token'] ?? '');
    $title = Security::sanitize($_POST['title'] ?? 'New Poll');
    $slug = Security::sanitize($_POST['slug'] ?? 'new-poll-' . time());

    $db->query(
        'INSERT INTO polls (title, slug, is_active) VALUES (?, ?, 1)',
        [':title' => $title, ':slug' => $slug]
    );
    header('Location: dashboard.php');
    exit();
}

$polls = $db->queryAll('SELECT * FROM polls WHERE is_active = 1');
$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo translate('admin_dashboard'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container position-relative flex-wrap">
            <a class="navbar-brand position-absolute start-50 translate-middle-x d-none d-md-block" href="<?php echo BASE_PATH; ?>/admin/dashboard.php"><?php echo translate('poll_admin'); ?></a>
            <a class="navbar-brand w-100 text-center d-md-none mb-2" href="<?php echo BASE_PATH; ?>/admin/dashboard.php"><?php echo translate('poll_admin'); ?></a>
            <div class="d-flex flex-wrap gap-2 ms-auto justify-content-end">
                <a class="btn btn-sm btn-outline-light" href="<?php echo BASE_PATH; ?>/admin/dashboard.php"><?php echo translate('dashboard'); ?></a>
                <a class="btn btn-sm btn-outline-light" href="<?php echo BASE_PATH; ?>/admin/settings.php"><?php echo translate('settings'); ?></a>
                <a class="btn btn-sm btn-outline-light" href="<?php echo BASE_PATH; ?>/admin/history.php"><?php echo translate('history'); ?></a>
                <a class="btn btn-sm btn-outline-danger" href="<?php echo BASE_PATH; ?>/admin/logout.php"><?php echo translate('logout'); ?></a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?php echo translate('manage_polls'); ?></h1>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createPollModal"><?php echo translate('create_new_poll'); ?></button>
        </div>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo translate('poll_title'); ?></th>
                    <th><?php echo translate('slug'); ?></th>
                    <th><?php echo translate('actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($polls as $poll): ?>
                <tr>
                    <td><?php echo htmlspecialchars($poll['title']); ?></td>
                    <td><code><?php echo htmlspecialchars($poll['slug']); ?></code></td>
                    <td>
                        <a href="<?php echo BASE_PATH; ?>/admin/dashboard.php?id=<?php echo $poll['id']; ?>" class="btn btn-sm btn-info"><?php echo translate('view_entries'); ?></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Create Poll Modal -->
    <div class="modal fade" id="createPollModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo translate('create_new_poll'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo translate('poll_title'); ?></label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo translate('slug'); ?></label>
                        <input type="text" class="form-control" name="slug" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo translate('cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo translate('create'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="position-fixed top-0 start-0 p-3">
        <a href="<?php echo BASE_PATH; ?>/set_language.php?lang=en" class="btn btn-sm btn-outline-secondary">EN</a>
        <a href="<?php echo BASE_PATH; ?>/set_language.php?lang=de" class="btn btn-sm btn-outline-secondary">DE</a>
    </div>
    <div class="position-fixed bottom-0 end-0 p-3">
        <button id="themeToggle" class="btn btn-sm btn-outline-secondary" onclick="toggleTheme()"><?php echo translate('dark_mode'); ?></button>
    </div>
    <script>window.BASE_PATH = <?php echo json_encode(BASE_PATH); ?>;</script>
    <script src="<?php echo BASE_PATH; ?>/assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
