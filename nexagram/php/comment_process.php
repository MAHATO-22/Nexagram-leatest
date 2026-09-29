<?php
// comment_process.php - Nexagram Comment API
session_start();
require_once '../config.php';

$is_ajax = (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
);

if ($is_ajax) {
    header('Content-Type: application/json; charset=utf-8');
}

if (!isset($_SESSION['user_id'])) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }
    header("Location: ../index.php");
    exit;
}

$user_id      = $_SESSION['user_id'];
$post_id      = intval($_POST['post_id'] ?? 0);
$comment_text = trim($_POST['comment_text'] ?? $_POST['comment'] ?? '');

if ($post_id <= 0 || $comment_text === '') {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Invalid post or empty comment']);
        exit;
    }
    $referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
    header("Location: " . $referer);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, comment_text) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $post_id, $comment_text]);
    $new_comment_id = $pdo->lastInsertId();

    if ($is_ajax) {
        $u_stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $u_stmt->execute([$user_id]);
        $username = $u_stmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'status'  => 'success',
            'comment' => [
                'id'         => (int)$new_comment_id,
                'username'   => $username,
                'comment'    => $comment_text
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
    header("Location: " . $referer);
    exit;

} catch (\PDOException $e) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
    die("エラー: " . $e->getMessage());
}
?>
