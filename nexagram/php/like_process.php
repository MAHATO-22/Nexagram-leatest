<?php
// like_process.php - Nexagram Like / Unlike API
session_start();
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$post_id = null;
if (isset($_GET['post_id'])) {
    $post_id = intval($_GET['post_id']);
} elseif (isset($_POST['post_id'])) {
    $post_id = intval($_POST['post_id']);
}

if (empty($post_id) || $post_id <= 0) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Invalid post_id']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    
    if ($stmt->rowCount() > 0) {
        $delete_stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $delete_stmt->execute([$user_id, $post_id]);
    } else {
        $insert_stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $insert_stmt->execute([$user_id, $post_id]);
    }

    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $count_stmt->execute([$post_id]);
    $new_like_count = $count_stmt->fetchColumn();

    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ? AND post_id = ?");
    $check_stmt->execute([$user_id, $post_id]);
    $is_liked = $check_stmt->fetchColumn() > 0;

    echo json_encode([
        'success'    => true,
        'status'     => 'success',
        'like_count' => (int)$new_like_count,
        'is_liked'   => $is_liked
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>
