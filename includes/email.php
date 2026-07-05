<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/i18n.php';

class EmailService {
    private static function t($key, array $replacements = []) {
        $text = function_exists('translate') ? translate($key) : $key;
        return empty($replacements) ? $text : strtr($text, $replacements);
    }

    public static function send($to, $subject, $body, $isHtml = true) {
        if (!SMTP_ENABLED) return false;
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
        
        if (EMAIL_TYPE === 'smtp') {
            return self::sendViaSMTP($to, $subject, $body, $headers);
        } else {
            return mail($to, $subject, $body, $headers);
        }
    }
    
    private static function sendViaSMTP($to, $subject, $body, $headers) {
        try {
            $connection = fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 30);
            if (!$connection) {
                return false;
            }
            
            $out = "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n";
            fwrite($connection, $out);
            $response = fgets($connection, 1024);
            
            $out = "AUTH LOGIN\r\n";
            fwrite($connection, $out);
            fgets($connection, 1024);
            
            $out = base64_encode(SMTP_USER) . "\r\n";
            fwrite($connection, $out);
            fgets($connection, 1024);
            
            $out = base64_encode(SMTP_PASSWORD) . "\r\n";
            fwrite($connection, $out);
            fgets($connection, 1024);
            
            $out = "MAIL FROM:<" . SMTP_FROM . ">\r\n";
            fwrite($connection, $out);
            fgets($connection, 1024);
            
            $out = "RCPT TO:<" . $to . ">\r\n";
            fwrite($connection, $out);
            fgets($connection, 1024);
            
            $out = "DATA\r\n";
            fwrite($connection, $out);
            fgets($connection, 1024);
            
            $message = "To: " . $to . "\r\n";
            $message .= "Subject: " . $subject . "\r\n";
            $message .= $headers . "\r\n";
            $message .= $body . "\r\n.\r\n";
            
            fwrite($connection, $message);
            fgets($connection, 1024);
            
            $out = "QUIT\r\n";
            fwrite($connection, $out);
            fclose($connection);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
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
            $subject = self::t('mail_new_participant_subject', [
                '{poll}' => $poll['title']
            ]);
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

        $pollTitle = htmlspecialchars($poll['title']);
        $subscriberName = htmlspecialchars($subscriber['name']);
        $participantName = htmlspecialchars($newEntry['name']);
        $mailLang = $_SESSION['lang'] ?? 'en';
        if (!in_array($mailLang, ['en', 'de'], true)) {
            $mailLang = 'en';
        }

        $token = rawurlencode($subscriber['unsubscribe_token']);
        $lang = rawurlencode($mailLang);
        $unsubscribeLink = SITE_URL . '/api/unsubscribe.php?token=' . $token . '&lang=' . $lang;
        $deleteParticipationLink = SITE_URL . '/api/delete_participation.php?token=' . $token . '&lang=' . $lang;

        $heading = self::t('mail_new_participant_heading');
        $pollLabel = self::t('mail_poll_label');
        $greeting = self::t('mail_greeting_with_new_participant', [
            '{subscriber}' => $subscriberName,
            '{participant}' => $participantName
        ]);
        $participantsLabel = self::t('mail_participants_label');
        $unsubscribeLabel = self::t('mail_unsubscribe_label');
        $deleteParticipationLabel = self::t('mail_delete_participation_label');

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body>
<h2>{$heading}</h2>
<p>{$pollLabel}: <strong>{$pollTitle}</strong></p>
<p>{$greeting}</p>
<h3>{$participantsLabel}</h3>
<ul>$entriesList</ul>
<p><a href="$unsubscribeLink">{$unsubscribeLabel}</a></p>
<p><a href="$deleteParticipationLink">{$deleteParticipationLabel}</a></p>
</body>
</html>
HTML;
    }
}