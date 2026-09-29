<?php
// send_message.php - Send message endpoint for Nexagram Real-Time Chat
session_start();
header('Content-Type: application/json');

require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$sender_id = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$message_text = isset($_POST['message_text']) ? trim($_POST['message_text']) : '';

if ($receiver_id <= 0 || $receiver_id === $sender_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid recipient']);
    exit;
}

if ($message_text === '') {
    echo json_encode(['success' => false, 'error' => 'Message text cannot be empty']);
    exit;
}

try {
    $user_check = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $user_check->execute([$receiver_id]);
    if (!$user_check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'User does not exist']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
    $stmt->execute([$sender_id, $receiver_id, $message_text]);
    $message_id = $pdo->lastInsertId();

    $msg_stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
    $msg_stmt->execute([$message_id]);
    $message = $msg_stmt->fetch();

    $message['formatted_time'] = date('H:i', strtotime($message['created_at']));
    $message['formatted_date'] = date('Y/m/d', strtotime($message['created_at']));

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
