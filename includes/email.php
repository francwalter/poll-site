<?php
require_once __DIR__ . '/config.php';

class EmailService {
    public static function send($to, $subject, $body, $isHtml = true) {
        if (!SMTP_ENABLED) return false;
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
        
        return mail($to, $subject, $body, $headers);
    }
    
    public static function notifySubscribers($pollId, $newEntry) {
        $db = Database::getInstance();
        $poll = $db->queryOne('SELECT * FROM polls WHERE id = ?', [':id' => $pollId]);
        if (!$poll) return false;
        
        $subscribers = $db->queryAll(
            'SELECT * FROM entries WHERE poll_id = ? AND subscribed = 1 AND email IS NOT NULL',
            [':poll_id' => $pollId]
        );
        
        $entries = $db->queryAll(
            'SELECT name FROM entries WHERE poll_id = ? ORDER BY created_at DESC LIMIT 10',
            [':poll_id' => $pollId]
        );
        
        $count = 0;
        foreach ($subscribers as $subscriber) {
            $subject = "New participant in " . htmlspecialchars($poll['title']);
            $body = self::buildNotificationEmail($poll, $newEntry, $entries, $subscriber);
            if (self::send($subscriber['email'], $subject, $body)) {
                $count++;
            }
        }
        return $count;
    }
    
    private static function buildNotificationEmail($poll, $newEntry, $entries, $subscriber) {
        $entriesList = '';
        foreach ($entries as $entry) {
            $entriesList .= '<li>' . htmlspecialchars($entry['name']) . '</li>';
        }
        $unsubscribeLink = SITE_URL . '/api/unsubscribe.php?token=' . $subscriber['unsubscribe_token'];
        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body>
<h2>New Participant Alert</h2>
<p>Poll: <strong>{$poll['title']}</strong></p>
<p>Hi {$subscriber['name']}, <strong>{$newEntry['name']}</strong> has joined!</p>
<h3>Participants:</h3>
<ul>$entriesList</ul>
<p><a href="$unsubscribeLink">Unsubscribe</a></p>
</body>
</html>
HTML;
    }
}
