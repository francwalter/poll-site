<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/poll_maintenance.php';
require_once __DIR__ . '/includes/email.php';

// session_start() is already called in config.php via session_id() check

$db = Database::getInstance();

$slug = $_GET['slug'] ?? null;

if (!$slug) {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $normalizedPath = trim($requestPath, '/');
    $normalizedBasePath = trim(BASE_PATH, '/');

    if ($normalizedBasePath !== '') {
        if ($normalizedPath === $normalizedBasePath) {
            $normalizedPath = '';
        } elseif (strpos($normalizedPath, $normalizedBasePath . '/') === 0) {
            $normalizedPath = substr($normalizedPath, strlen($normalizedBasePath) + 1);
        }
    }

    if (preg_match('#^([a-zA-Z0-9_-]+)(?:/index\.php)?$#', $normalizedPath, $matches)) {
        $slug = $matches[1];
    }
}

if ($slug) {
    // Fetch by slug
    $poll = $db->queryOne('SELECT * FROM polls WHERE slug = ? AND is_active = 1', [':slug' => $slug]);
} else {
    // Redirect to list if no slug
    header('Location: ' . BASE_PATH . '/poll_list.php');
    exit();
}

if ($poll) {
    perform_poll_clear($db, $poll);
    $poll = $db->queryOne('SELECT * FROM polls WHERE id = ? AND is_active = 1', [':id' => $poll['id']]);
}

if (!$poll) {
    if (isset($_GET['name'])) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(404);
        echo json_encode(['error' => 'Poll not found']);
        exit();
    }
    header('Location: admin/login.php');
    exit();
}

if (isset($_GET['name'])) {
    header('Content-Type: application/json; charset=utf-8');

    $name = Security::sanitize($_GET['name'] ?? '');
    $email = Security::sanitize($_GET['email'] ?? '');

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

    $subscribe = !empty($email) ? 1 : 0;

    $unsubscribeToken = null;
    if ($subscribe) {
        $unsubscribeToken = Security::generateUnsubscribeToken();
    }

    $db->query(
        'INSERT INTO entries (poll_id, name, email, subscribed, unsubscribe_token) VALUES (?, ?, ?, ?, ?)',
        [':poll_id' => $poll['id'], ':name' => $name, ':email' => !empty($email) ? $email : null, ':subscribed' => $subscribe, ':token' => $unsubscribeToken]
    );

    $entryId = $db->lastInsertId();
    $entry = $db->queryOne('SELECT * FROM entries WHERE id = ?', [':id' => $entryId]);
    if ($entry) {
        EmailService::notifySubscribers($poll['id'], $entry);
    }

    http_response_code(201);
    echo json_encode(['success' => true, 'entry_id' => $entryId, 'poll_slug' => $slug]);
    exit();
}

$clearDateDisplay = '-';
if (!empty($poll['next_clear_date'])) {
    $clearDateDisplay = date('d.m.Y', strtotime($poll['next_clear_date']));
}
$intervalDays = (int)($poll['recurring_interval_days'] ?? 0);
$intervalUnit = $intervalDays === 1 ? translate('day') : translate('days');


$entries = $db->queryAll(
    'SELECT * FROM entries WHERE poll_id = ? ORDER BY created_at DESC',
    [':poll_id' => $poll['id']]
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
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <span class="badge bg-primary fs-6 px-3 py-2">
                                    <?php echo translate('interval'); ?>: <?php echo $intervalDays; ?> <?php echo $intervalUnit; ?>
                                </span>
                                <span class="badge bg-info text-dark fs-6 px-3 py-2">
                                    <?php echo translate('clear_date'); ?>: <?php echo htmlspecialchars($clearDateDisplay); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><?php echo translate('join_the_poll'); ?></h5>
                        </div>
                        <div class="card-body">
                            <form id="addEntryForm" method="POST" action="<?php echo BASE_PATH; ?>/api/add_entry.php">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="poll_id" value="<?php echo $poll['id']; ?>">

                                <div class="mb-3">
                                    <label for="name" class="form-label"><?php echo translate('your_name'); ?> *</label>
                                    <input type="text" class="form-control" id="name" name="name" required pattern="[A-Za-z0-9]+" title="Only letters and numbers, no spaces">
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label"><?php echo translate('email_optional'); ?></label>
                                    <input type="email" class="form-control" id="email" name="email">
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
        <a href="<?php echo BASE_PATH; ?>/admin/login.php" class="btn btn-sm btn-outline-secondary"><?php echo translate('admin_login_link'); ?></a>
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