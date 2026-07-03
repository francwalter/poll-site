<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/poll_maintenance.php';

// session_start() is already called in config.php via session_id() check

$db = Database::getInstance();
$pollId = $_GET['id'] ?? 1;
$poll = $db->queryOne('SELECT * FROM polls WHERE id = ? AND is_active = 1', [':id' => $pollId]);

if ($poll) {
    perform_poll_clear($db, $poll);
    $poll = $db->queryOne('SELECT * FROM polls WHERE id = ? AND is_active = 1', [':id' => $pollId]);
}

if (!$poll) {
    header('Location: admin/login.php');
    exit();
}

$entries = $db->queryAll(
    'SELECT * FROM entries WHERE poll_id = ? ORDER BY created_at DESC',
    [':poll_id' => $pollId]
);
$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $poll ? htmlspecialchars($poll['title']) : 'Poll Site'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <?php if (!$poll): ?>
                    <div class="alert alert-warning">
                        No active polls found. Please <a href="<?php echo BASE_PATH; ?>/admin/login.php">login to the admin panel</a> to create a poll.
                    </div>
                        <?php else: ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h1 class="card-title"><?php echo htmlspecialchars($poll['title']); ?></h1>
                            <?php if ($poll['description']): ?>
                                <p class="card-text"><?php echo htmlspecialchars($poll['description']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><?php echo translate('join_the_poll'); ?></h5>
                        </div>
                        <div class="card-body">
                            <form id="addEntryForm" method="POST" action="<?php echo BASE_PATH; ?>/api/add_entry.php">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="poll_id" value="<?php echo $pollId; ?>">

                                <div class="mb-3">
                                    <label for="name" class="form-label"><?php echo translate('your_name'); ?> *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label"><?php echo translate('email_optional'); ?></label>
                                    <input type="email" class="form-control" id="email" name="email">
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="subscribe" name="subscribe" value="1">
                                    <label class="form-check-label" for="subscribe"><?php echo translate('receive_email_notifications'); ?></label>
                                </div>

                                <button type="submit" class="btn btn-primary"><?php echo translate('join_poll'); ?></button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5><?php echo translate('participants'); ?> (<?php echo count($entries); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($entries)): ?>
                                <p class="text-muted"><?php echo translate('no_participants_yet'); ?></p>
                            <?php else: ?>
                                <ul class="list-group">
                                    <?php foreach ($entries as $entry): ?>
                                        <li class="list-group-item">
                                            <strong><?php echo htmlspecialchars($entry['name']); ?></strong>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
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