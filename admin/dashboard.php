<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

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
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand">Poll Admin</span>
            <ul class="navbar-nav ms-auto flex-row">
                <li class="nav-item"><a class="nav-link" href="/admin/settings.php">Settings</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/history.php">History</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container mt-5">
        <h1><?php echo htmlspecialchars($poll['title']); ?></h1>
        <p class="text-muted"><?php echo htmlspecialchars($poll['description'] ?? ''); ?></p>

        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Entries (<?php echo count($entries); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($entries)): ?>
                            <p class="text-muted">No entries</p>
                        <?php else: ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($entries as $entry): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($entry['name']); ?></td>
                                        <td><?php echo htmlspecialchars($entry['email'] ?? '-'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($entry['created_at'])); ?></td>
                                        <td>
                                            <a href="/admin/edit.php?id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                            <form method="POST" action="/api/delete_entry.php" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="entry_id" value="<?php echo $entry['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
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
                    <div class="card-header"><h5>Settings</h5></div>
                    <div class="card-body">
                        <p><strong>Clear Date:</strong> <?php echo htmlspecialchars($poll['next_clear_date']); ?></p>
                        <p><strong>Interval:</strong> <?php echo $poll['recurring_interval_days']; ?> days</p>
                        <a href="/admin/settings.php" class="btn btn-secondary w-100">Edit</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>