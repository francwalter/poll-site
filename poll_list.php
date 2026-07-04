<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/i18n.php';

$db = Database::getInstance();
$polls = $db->queryAll('SELECT * FROM polls WHERE is_active = 1');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Poll List - <?php echo SITE_TITLE; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Available Polls</h1>
        <div class="list-group">
            <?php foreach ($polls as $poll): ?>
                <a href="<?php echo BASE_PATH; ?>/<?php echo htmlspecialchars($poll['slug']); ?>" class="list-group-item list-group-item-action">
                    <h5><?php echo htmlspecialchars($poll['title']); ?></h5>
                    <p class="mb-1"><?php echo htmlspecialchars($poll['description'] ?? ''); ?></p>
                </a>
            <?php endforeach; ?>
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
</body>
</html>
