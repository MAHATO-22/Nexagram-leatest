<?php
// fetch_messages.php - Fetch chat history and mark messages as read
session_start();
header('Content-Type: application/json');

require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$receiver_id = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

if ($receiver_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

try {
    $user_stmt = $pdo->prepare("SELECT id, username, profile_image, profile_pic, bio FROM users WHERE id = ?");
    $user_stmt->execute([$receiver_id]);
    $receiver = $user_stmt->fetch();

    if (!$receiver) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    $avatar = 'default.png';
    if (!empty($receiver['profile_image'])) {
        $avatar = $receiver['profile_image'];
    } elseif (!empty($receiver['profile_pic']) && $receiver['profile_pic'] !== 'default.png') {
        $avatar = $receiver['profile_pic'];
    }
    $receiver['avatar_url'] = $avatar;

    $mark_read = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
    $mark_read->execute([$receiver_id, $current_user_id]);

    if ($last_id > 0) {
        $msg_stmt = $pdo->prepare("
            SELECT * FROM messages 
            WHERE ((sender_id = :c1 AND receiver_id = :r1)
               OR (sender_id = :r2 AND receiver_id = :c2))
              AND id > :last_id
            ORDER BY created_at ASC
        ");
        $msg_stmt->execute([
            'c1' => $current_user_id,
            'r1' => $receiver_id,
            'r2' => $receiver_id,
            'c2' => $current_user_id,
            'last_id' => $last_id
        ]);
    } else {
        $msg_stmt = $pdo->prepare("
            SELECT * FROM messages 
            WHERE (sender_id = :c1 AND receiver_id = :r1)
               OR (sender_id = :r2 AND receiver_id = :c2)
            ORDER BY created_at ASC
        ");
        $msg_stmt->execute([
            'c1' => $current_user_id,
            'r1' => $receiver_id,
            'r2' => $receiver_id,
            'c2' => $current_user_id
        ]);
    }

    $messages = $msg_stmt->fetchAll();

    foreach ($messages as &$msg) {
        $msg['formatted_time'] = date('H:i', strtotime($msg['created_at']));
        $msg['formatted_date'] = date('Y/m/d', strtotime($msg['created_at']));
    }

    echo json_encode([
        'success' => true,
        'current_user_id' => $current_user_id,
        'receiver' => $receiver,
        'messages' => $messages
    ]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
