<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';

$db = Database::getInstance();
$pollId = $_GET['id'] ?? 1;
$poll = $db->queryOne('SELECT * FROM polls WHERE id = ? AND is_active = 1', [':id' => $pollId]);

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($poll['title']); ?> - Poll Site</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
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
                        <h5>Join the Poll</h5>
                    </div>
                    <div class="card-body">
                        <form id="addEntryForm" method="POST" action="/api/add_entry.php">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="poll_id" value="<?php echo $pollId; ?>">
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Your Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email (optional)</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="subscribe" name="subscribe" value="1">
                                <label class="form-check-label" for="subscribe">Receive email notifications</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Join Poll</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>Participants (<?php echo count($entries); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($entries)): ?>
                            <p class="text-muted">No participants yet.</p>
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
            </div>
        </div>
    </div>

    <div class="position-fixed bottom-0 end-0 p-3">
        <button id="themeToggle" class="btn btn-outline-secondary" onclick="toggleTheme()">🌙 Dark Mode</button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/script.js"></script>
</body>
</html>