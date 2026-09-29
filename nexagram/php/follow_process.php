<?php
// follow_process.php - AJAX Follow / Unfollow User Endpoint
session_start();
header('Content-Type: application/json');

require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$follower_id = (int)$_SESSION['user_id'];
$following_id = isset($_POST['following_id']) ? (int)$_POST['following_id'] : (isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0);

if ($following_id <= 0 || $following_id === $follower_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid target user']);
    exit;
}

try {
    // Check if target user exists
    $user_check = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $user_check->execute([$following_id]);
    if (!$user_check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    // Check if currently following
    $check_stmt = $pdo->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $check_stmt->execute([$follower_id, $following_id]);
    $existing = $check_stmt->fetch();

    if ($existing) {
        // Unfollow
        $del_stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $del_stmt->execute([$follower_id, $following_id]);
        $is_following = false;
    } else {
        // Follow
        $ins_stmt = $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
        $ins_stmt->execute([$follower_id, $following_id]);
        $is_following = true;
    }

    // Check if target user follows current logged-in user (Follow Back situation)
    $follower_check = $pdo->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $follower_check->execute([$following_id, $follower_id]);
    $is_follower = ((bool)$follower_check->fetch());

    // Get updated follower count for the target user
    $cnt_stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
    $cnt_stmt->execute([$following_id]);
    $follower_count = (int)$cnt_stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'is_following' => $is_following,
        'is_follower' => $is_follower,
        'is_mutual' => ($is_following && $is_follower),
        'follower_count' => $follower_count
    ]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
