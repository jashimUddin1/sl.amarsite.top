<?php
// functions_notifications.php

function generateNotificationsForUser(PDO $pdo, int $user_id): void
{
    // next_meet এখন থেকে 12 ঘন্টার মধ্যে
    // এবং এই user + note এর জন্য notification এখনও নেই
    $sql = "
        INSERT INTO notifications (user_id, note_id, status, action_at)
        SELECT 
            :user_id AS user_id,
            sn.id    AS note_id,
            'unread' AS status,
            NOW()    AS action_at
        FROM school_notes sn
        LEFT JOIN notifications n
            ON n.note_id = sn.id
           AND n.user_id = :user_id
        WHERE sn.next_meet IS NOT NULL
          AND sn.next_meet > NOW()
          AND sn.next_meet <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
          AND n.id IS NULL
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
}
