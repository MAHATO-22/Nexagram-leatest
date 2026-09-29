<?php
// delete_post.php - Nexagram Delete Post Handler
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: ../profile.php");
    exit();
}

$post_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
$stmt->execute([$post_id, $user_id]);
$post = $stmt->fetch();

if ($post) {
    if (!empty($post['image_path']) && file_exists('../' . $post['image_path'])) {
        unlink('../' . $post['image_path']);
    }
    
    $delete_stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $delete_stmt->execute([$post_id]);
}

header("Location: ../profile.php");
exit();
?>
