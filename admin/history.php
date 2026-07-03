<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireLogin();

$db = Database::getInstance();
$pollId = $_GET['id'] ?? 1;
$search = $_GET['search'] ?? '';
$page = intval($_GET['page'] ?? 1);
const ITEMS_PER_PAGE = 50;

$whereClause = 'WHERE poll_id = ?';
$params = [':poll_id' => $pollId];

if (!empty($search)) {
    $whereClause .= ' AND (name LIKE ? OR email LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params[':search1'] = $searchTerm;
    $params[':search2'] = $searchTerm;
}

$countResult = $db->queryOne("SELECT COUNT(*) as count FROM archived_entries $whereClause", $params);
$totalCount = $countResult['count'] ?? 0;
$totalPages = ceil($totalCount / ITEMS_PER_PAGE);
$offset = ($page - 1) * ITEMS_PER_PAGE;

$archived = $db->queryAll(
    "SELECT * FROM archived_entries $whereClause ORDER BY archived_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [':limit' => ITEMS_PER_PAGE, ':offset' => $offset])
);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Poll History</h1>
        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-8"><input type="text" class="form-control" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>"></div>
                <div class="col-md-4"><button type="submit" class="btn btn-primary w-100">Search</button></div>
            </div>
        </form>
        <table class="table table-striped">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Cycle</th><th>Joined</th><th>Archived</th></tr>
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
</body>
</html>