<?php
function perform_poll_clear($db, $poll) {
    $today = date('Y-m-d');
    if ($poll['next_clear_date'] && $today >= $poll['next_clear_date']) {
        // Move entries to archive
        $entries = $db->queryAll('SELECT * FROM entries WHERE poll_id = ?', [':poll_id' => $poll['id']]);
        
        foreach ($entries as $entry) {
            $db->query(
                'INSERT INTO archived_entries (poll_id, name, email, created_at, poll_cycle) VALUES (?, ?, ?, ?, ?)',
                [
                    ':poll_id' => $entry['poll_id'], 
                    ':name' => $entry['name'], 
                    ':email' => $entry['email'], 
                    ':created_at' => $entry['created_at'], 
                    ':cycle' => $poll['next_clear_date']
                ]
            );
        }
        
        // Delete current entries
        $db->query('DELETE FROM entries WHERE poll_id = ?', [':poll_id' => $poll['id']]);
        
        // Calculate new clear date
        $newDate = date('Y-m-d', strtotime($today . ' + ' . $poll['recurring_interval_days'] . ' days'));
        
        // Update poll
        $db->query(
            'UPDATE polls SET next_clear_date = ? WHERE id = ?',
            [':date' => $newDate, ':id' => $poll['id']]
        );
    }
}
