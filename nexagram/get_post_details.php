<?php
// get_post_details.php - Nexagram Post Details API (Likes & Comments)
// profile.php ရဲ့ Post Modal ကနေ AJAX နဲ့ ခေါ်သုံးတဲ့ endpoint
session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Login မလုပ်ထားရင် JSON error ပြန်မယ်
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$current_user_id = $_SESSION['user_id'];

// post_id ရှိမရှိ စစ်ဆေးခြင်း
if (!isset($_GET['post_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'post_id is required']);
    exit;
}

$post_id = intval($_GET['post_id']);

if ($post_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid post_id']);
    exit;
}

try {
    // 1. Like အရေအတွက်နဲ့ လက်ရှိ user  like ပေးထားသလား
    $stmt = $pdo->prepare("SELECT 
        (SELECT COUNT(*) FROM likes WHERE likes.post_id = ?) AS like_count,
        (SELECT COUNT(*) FROM likes WHERE likes.post_id = ? AND likes.user_id = ?) AS is_liked");
    $stmt->execute([$post_id, $post_id, $current_user_id]);
    $counts = $stmt->fetch();

    // 2. Comment များကို ဆွဲထုတ်ခြင်း (username နဲ့ အတူ)
    $c_stmt = $pdo->prepare("SELECT comments.id, comments.comment_text, comments.created_at, users.username 
                             FROM comments 
                             JOIN users ON comments.user_id = users.id 
                             WHERE comments.post_id = ? 
                             ORDER BY comments.created_at ASC");
    $c_stmt->execute([$post_id]);
    $comments = $c_stmt->fetchAll();

    // Frontend က ထောင့်စားဖြစ်အောင် comment ပြောင်းပေးခြင်း
    $formatted_comments = [];
    foreach ($comments as $c) {
        $formatted_comments[] = [
            'id'         => (int)$c['id'],
            'username'   => $c['username'],
            'comment'    => $c['comment_text'],
            'created_at' => $c['created_at']
        ];
    }

    // JSON ပြန်ပေးခြင်း (success = true ကို အသုံးပြုသည်)
    echo json_encode([
        'success'     => true,
        'status'      => 'success',
        'like_count'  => (int)$counts['like_count'],
        'is_liked'    => (int)$counts['is_liked'] > 0,
        'comments'    => $formatted_comments
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (\PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>
