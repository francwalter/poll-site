<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/i18n.php';

Auth::requireLogin();

$db = Database::getInstance();
$pollId = $_GET['id'] ?? 1;
$search = $_GET['search'] ?? '';
$page = intval($_GET['page'] ?? 1);
const ITEMS_PER_PAGE = 50;

$whereClause = 'WHERE poll_id = :poll_id';
$params = [':poll_id' => $pollId];

if (!empty($search)) {
    $whereClause .= ' AND (name LIKE :search1 OR email LIKE :search2)';
    $searchTerm = '%' . $search . '%';
    $params[':search1'] = $searchTerm;
    $params[':search2'] = $searchTerm;
}

$countResult = $db->queryOne("SELECT COUNT(*) as count FROM archived_entries $whereClause", $params);
$totalCount = $countResult['count'] ?? 0;
$totalPages = ceil($totalCount / ITEMS_PER_PAGE);
$offset = ($page - 1) * ITEMS_PER_PAGE;

$archived = $db->queryAll(
    "SELECT * FROM archived_entries $whereClause ORDER BY archived_at DESC LIMIT :limit OFFSET :offset",
    array_merge($params, [':limit' => ITEMS_PER_PAGE, ':offset' => $offset])
);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>History</title>
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
        <h1><?php echo translate('poll_history'); ?></h1>
        <form method="GET" class="mb-4">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($pollId); ?>">
            <div class="row">
                <div class="col-md-8"><input type="text" class="form-control" name="search" placeholder="<?php echo translate('search'); ?>..." value="<?php echo htmlspecialchars($search); ?>"></div>
                <div class="col-md-4"><button type="submit" class="btn btn-primary w-100"><?php echo translate('search'); ?></button></div>
            </div>
        </form>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo translate('name'); ?></th>
                    <th><?php echo translate('email'); ?></th>
                    <th><?php echo translate('cycle'); ?></th>
                    <th><?php echo translate('joined'); ?></th>
                    <th><?php echo translate('archived'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($archived as $entry): ?>
                <tr>
                    <td><?php echo htmlspecialchars($entry['name']); ?></td>
                    <td><?php echo htmlspecialchars($entry['email'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($entry['poll_cycle']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($entry['created_at'])); ?></td>
                    <td><?php echo date('M d, Y', strtotime($entry['archived_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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