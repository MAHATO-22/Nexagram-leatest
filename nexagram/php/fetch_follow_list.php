<?php
// fetch_follow_list.php - Fetch Followers / Following list modal data
session_start();
header('Content-Type: application/json');

require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];
$target_user_id  = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $current_user_id;
$type            = isset($_GET['type']) && $_GET['type'] === 'following' ? 'following' : 'followers';

if ($target_user_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

try {
    if ($type === 'followers') {
        $sql = "
            SELECT u.id, u.username, u.profile_image, u.profile_pic, u.school_name,
            (SELECT COUNT(*) FROM follows WHERE follower_id = :curr1 AND following_id = u.id) as is_following,
            (SELECT COUNT(*) FROM follows WHERE follower_id = u.id AND following_id = :curr2) as is_follower
            FROM follows f
            JOIN users u ON f.follower_id = u.id
            WHERE f.following_id = :target
            ORDER BY is_follower DESC, f.created_at DESC
        ";
    } else {
        $sql = "
            SELECT u.id, u.username, u.profile_image, u.profile_pic, u.school_name,
            (SELECT COUNT(*) FROM follows WHERE follower_id = :curr1 AND following_id = u.id) as is_following,
            (SELECT COUNT(*) FROM follows WHERE follower_id = u.id AND following_id = :curr2) as is_follower
            FROM follows f
            JOIN users u ON f.following_id = u.id
            WHERE f.follower_id = :target
            ORDER BY is_follower DESC, f.created_at DESC
        ";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'curr1'  => $current_user_id,
        'curr2'  => $current_user_id,
        'target' => $target_user_id
    ]);
    $users = $stmt->fetchAll();

    foreach ($users as &$u) {
        $avatar = 'default.png';
        if (!empty($u['profile_image'])) {
            $avatar = $u['profile_image'];
        } elseif (!empty($u['profile_pic']) && $u['profile_pic'] !== 'default.png') {
            $avatar = $u['profile_pic'];
        }
        $u['avatar_url']   = $avatar;
        $u['is_following'] = ((int)$u['is_following'] > 0);
        $u['is_follower']  = ((int)$u['is_follower'] > 0);
        $u['is_mutual']    = ($u['is_following'] && $u['is_follower']);
    }

    echo json_encode([
        'success' => true,
        'type' => $type,
        'users' => $users
    ]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
