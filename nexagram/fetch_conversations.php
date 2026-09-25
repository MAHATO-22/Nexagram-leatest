<?php
// fetch_conversations.php - List all friends/users with latest message and unread count
session_start();
header('Content-Type: application/json');

require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$current_user_id = $_SESSION['user_id'];

try {
    // Get all users except current user, along with latest message info and unread count
    $sql = "
        SELECT 
            u.id, 
            u.username, 
            u.profile_image, 
            u.profile_pic,
            lm.id as last_message_id,
            lm.message_text as last_message_text,
            lm.sender_id as last_message_sender_id,
            lm.created_at as last_message_time,
            (
                SELECT COUNT(*) 
                FROM messages 
                WHERE sender_id = u.id AND receiver_id = :c1 AND is_read = 0
            ) as unread_count
        FROM users u
        LEFT JOIN (
            SELECT m1.*
            FROM messages m1
            INNER JOIN (
                SELECT 
                    CASE 
                        WHEN sender_id = :c2 THEN receiver_id 
                        ELSE sender_id 
                    END AS other_id,
                    MAX(id) AS max_id
                FROM messages
                WHERE sender_id = :c3 OR receiver_id = :c4
                GROUP BY other_id
            ) m2 ON m1.id = m2.max_id
        ) lm ON u.id = (
            CASE 
                WHEN lm.sender_id = :c5 THEN lm.receiver_id 
                ELSE lm.sender_id 
            END
        )
        WHERE u.id != :c6
        ORDER BY 
            CASE WHEN lm.created_at IS NOT NULL THEN 0 ELSE 1 END,
            lm.created_at DESC,
            u.username ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'c1' => $current_user_id,
        'c2' => $current_user_id,
        'c3' => $current_user_id,
        'c4' => $current_user_id,
        'c5' => $current_user_id,
        'c6' => $current_user_id
    ]);
    $users = $stmt->fetchAll();

    foreach ($users as &$user) {
        $avatar = 'default.png';
        if (!empty($user['profile_image'])) {
            $avatar = $user['profile_image'];
        } elseif (!empty($user['profile_pic']) && $user['profile_pic'] !== 'default.png') {
            $avatar = $user['profile_pic'];
        }
        $user['avatar_url'] = $avatar;

        if ($user['last_message_time']) {
            $timestamp = strtotime($user['last_message_time']);
            if (date('Y-m-d') === date('Y-m-d', $timestamp)) {
                $user['formatted_time'] = date('H:i', $timestamp);
            } else {
                $user['formatted_time'] = date('m/d', $timestamp);
            }
        } else {
            $user['formatted_time'] = '';
        }

        // Format short preview snippet
        if (!empty($user['last_message_text'])) {
            $prefix = ($user['last_message_sender_id'] == $current_user_id) ? 'あなた: ' : '';
            $user['preview_snippet'] = $prefix . mb_substr($user['last_message_text'], 0, 28, 'UTF-8');
            if (mb_strlen($user['last_message_text'], 'UTF-8') > 28) {
                $user['preview_snippet'] .= '...';
            }
        } else {
            $user['preview_snippet'] = 'チャットを開始する...';
        }
    }

    echo json_encode([
        'success' => true,
        'conversations' => $users
    ]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
