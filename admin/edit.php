<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

Auth::requireLogin();

$db = Database::getInstance();
$entryId = intval($_GET['id'] ?? 0);
$entry = $db->queryOne('SELECT * FROM entries WHERE id = ?', [':id' => $entryId]);
if (!$entry) die('Entry not found');

$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Entry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="col-lg-6 mx-auto">
            <h1>Edit Entry</h1>
            <form method="POST" action="/api/edit_entry.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="entry_id" value="<?php echo $entryId; ?>">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($entry['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($entry['email'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="/admin/dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>