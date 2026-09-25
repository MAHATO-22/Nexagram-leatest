<?php
// index.php - Nexagram Home Feed with Pure Facebook Layout & Active Profile Image Sync
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];

try {
    $query = "SELECT posts.*, users.username, users.profile_image,
              (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) as like_count,
              (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = ?) as is_liked,
              (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) as comment_count
              FROM posts 
              JOIN users ON posts.user_id = users.id 
              ORDER BY posts.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$current_user_id]);
    $posts = $stmt->fetchAll();

    // Suggested Friends Query (Users from around the world not yet followed)
    $sug_query = "SELECT u.id, u.username, u.profile_image, u.profile_pic, u.bio,
                  (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as follower_count
                  FROM users u
                  WHERE u.id != ? AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id = ?)
                  ORDER BY RAND()
                  LIMIT 5";
    $sug_stmt = $pdo->prepare($sug_query);
    $sug_stmt->execute([$current_user_id, $current_user_id]);
    $suggested_users = $sug_stmt->fetchAll();
} catch (\PDOException $e) {
    die("エラー: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexagram</title>
    <style>
        :root {
            --bg-color: #fafafa;
            --card-bg: #ffffff;
            --text-color: #000000;
            --text-secondary: #8e8e8e;
            --border-color: #dbdbdb;
            --sidebar-hover: #f2f2f2;
            --comment-bubble-bg: #f0f2f5;
            --overlay-bg: rgba(0, 0, 0, 0.5);
            --avatar-bg: #e4e6eb;
        }

        [data-theme="dark"] {
            --bg-color: #000000;
            --card-bg: #121212;
            --text-color: #f5f5f5;
            --text-secondary: #a8a8a8;
            --border-color: #262626;
            --sidebar-hover: #1c1c1e;
            --comment-bubble-bg: #242526;
            --overlay-bg: rgba(0, 0, 0, 0.7);
            --avatar-bg: #3a3b3c;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Arial, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            transition: background 0.3s, color 0.3s;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 245px;
            border-right: 1px solid var(--border-color);
            padding: 25px 12px;
            display: flex;
            flex-direction: column;
            background-color: var(--card-bg);
            z-index: 100;
            transition: background 0.3s, border 0.3s;
        }

        .logo {
            font-size: 26px;
            font-weight: bold;
            font-style: italic;
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 35px;
            padding-left: 12px;
        }

        .nav-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .nav-item {
            padding: 12px;
            margin: 4px 0;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background 0.2s;
        }

        .nav-item:hover {
            background-color: var(--sidebar-hover);
        }

        .nav-item a {
            text-decoration: none;
            color: var(--text-color);
            display: block;
            width: 100%;
        }

        .theme-toggle-btn {
            color: var(--text-color);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .logout-btn {
            margin-top: auto;
            color: #ed4956;
            font-weight: bold;
        }


        /* Sidebar ကို ကျော်ပြီး ကျန်တဲ့ ဧရိယာတစ်ခုလုံးရဲ့ အလယ်တည့်တည့် ရောက်အောင် ညှိမည် */
        .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
            display: flex;
            justify-content: center;
            padding: 30px 20px;
            gap: 32px;
            box-sizing: border-box;
        }

        .feed-container {
            width: 100%;
            max-width: 600px;
            margin: 0;
        }

        .suggested-sidebar {
            width: 320px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: 14px;
            height: fit-content;
            position: sticky;
            top: 30px;
        }

        @media (max-width: 1050px) {
            .suggested-sidebar {
                display: none;
            }
            .feed-container {
                margin: 0 auto;
            }
        }

        .post-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 24px;
            overflow: hidden;
            transition: background 0.3s, border 0.3s;
        }

        .post-header {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .profile-img-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 12px;
            border: 1px solid var(--border-color);
            background-color: var(--avatar-bg);
        }

        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--avatar-bg);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 18px;
            font-weight: bold;
            color: var(--text-color);
            margin-right: 12px;
            text-transform: uppercase;
            border: 1px solid var(--border-color);
        }

        .header-info {
            display: flex;
            flex-direction: column;
        }

        .username {
            font-weight: bold;
            font-size: 15px;
            color: var(--text-color);
        }

        .post-time-top {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        .post-image-box {
            width: 100%;
            max-height: 580px;
            background-color: #000;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .post-image {
            width: 100%;
            height: auto;
            display: block;
            object-fit: contain;
        }

        .counts-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            font-size: 13px;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-color);
        }

        .likes-count-display {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .action-bar {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            padding: 4px;
        }

        .action-button {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            color: var(--text-color);
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .action-button:hover {
            background-color: var(--sidebar-hover);
        }

        .action-icon {
            font-size: 18px;
        }

        .post-info {
            padding: 14px 16px;
        }

        .post-caption {
            font-size: 14px;
            line-height: 1.5;
            color: var(--text-color);
        }

        .post-caption .author {
            font-weight: bold;
            margin-right: 8px;
        }

        .comment-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--overlay-bg);
            z-index: 999;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .comment-sheet {
            position: fixed;
            bottom: -100%;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 550px;
            height: 75vh;
            background: var(--card-bg);
            border-radius: 16px 16px 0 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: bottom 0.4s cubic-bezier(0.1, 0.76, 0.55, 0.94);
            border: 1px solid var(--border-color);
            box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.15);
        }

        .comment-sheet.active {
            bottom: 0;
        }

        .sheet-header {
            padding: 14px;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            border-bottom: 1px solid var(--border-color);
            position: relative;
        }

        .sheet-close-btn {
            position: absolute;
            right: 16px;
            top: 14px;
            font-size: 20px;
            cursor: pointer;
            color: var(--text-secondary);
        }

        .sheet-body {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }

        .panel-comment-item {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .panel-comment-bubble {
            background-color: var(--comment-bubble-bg);
            padding: 10px 14px;
            border-radius: 18px;
            max-width: 100%;
            word-break: break-word;
        }

        .panel-comment-user {
            font-weight: bold;
            font-size: 13px;
            color: var(--text-color);
            display: block;
            margin-bottom: 2px;
        }

        .panel-comment-text {
            font-size: 13px;
            color: var(--text-color);
            line-height: 1.4;
        }

        .sheet-footer {
            border-top: 1px solid var(--border-color);
            padding: 12px;
            background-color: var(--card-bg);
        }

        .panel-comment-form {
            display: flex;
            background: var(--comment-bubble-bg);
            padding: 8px 14px;
            border-radius: 20px;
            align-items: center;
        }

        .panel-comment-input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 14px;
            background: transparent;
            color: var(--text-color);
        }

        .panel-comment-submit {
            background: none;
            border: none;
            color: #0095f6;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            margin-left: 10px;
        }

        .no-comments {
            text-align: center;
            color: var(--text-secondary);
            font-size: 14px;
            padding-top: 30px;
        }

        .no-posts {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
            font-size: 14px;
        }

        /* 1. Post Card တစ်ခုစီရဲ့ Margin အကွာအဝေးနှင့် Border သတ်မှတ်ခြင်း */
        /* Light Theme - မူလ Post Card ပုံစံ */
        .post-card {
            background-color: #ffffff;
            border: 1px solid #dbdbdb;
            border-radius: 8px;
            margin-bottom: 24px;
            overflow: hidden;
        }

        .post-image-box {
            width: 100%;
            max-height: 500px;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .post-image {
            width: 100%;
            height: 100%;
            max-height: 500px;
            object-fit: cover;
        }

        /* ==========================================
   Dark Mode CSS (မလွတ်ရအောင် အကုန်ဖမ်းထားသည်)
   ========================================== */

        /* 1. Post Card ရဲ့ Background ကို အမဲရောင် သို့ ပြောင်းမည် */
        [data-theme="dark"] .post-card,
        body.dark .post-card,
        .dark .post-card {
            background-color: #242526 !important;
            border-color: #393a3b !important;
        }

        /* 2. Post Card ထဲက စာသား/Username/Caption မှန်သမျှ အဖြူရောင် ပြောင်းမည် */
        [data-theme="dark"] .post-card *,
        [data-theme="dark"] .post-card span,
        [data-theme="dark"] .post-card div,
        [data-theme="dark"] .post-card a,
        [data-theme="dark"] .post-caption,
        [data-theme="dark"] .username,
        [data-theme="dark"] .post-time-top,
        [data-theme="dark"] .likes-count-display,
        [data-theme="dark"] .comments-count-display,
        [data-theme="dark"] .action-button,
        [data-theme="dark"] .like-text,
        body.dark .post-card *,
        .dark .post-card * {
            color: #e4e6eb !important;
        }

        /* 3. Border လိုင်းများ အမဲရောင်ပြောင်းရန် */
        [data-theme="dark"] .action-bar,
        [data-theme="dark"] .counts-row,
        [data-theme="dark"] .post-header,
        body.dark .action-bar,
        .dark .action-bar {
            border-color: #393a3b !important;
        }
    </style>

</head>

<body>

    <nav class="sidebar">
        <div class="logo">Nexagram</div>
        <ul class="nav-menu">
            <li class="nav-item" style="background-color: var(--sidebar-hover);"><strong><a href="index.php">🏠 ホーム (Home)</a></strong></li>
            <li class="nav-item"><a href="explore.php">🔍 検索 (Search)</a></li>
            <li class="nav-item"><a href="messages.php">✉️ メッセージ (Messages)</a></li>
            <li class="nav-item"><a href="create_post.php">➕ 作成 (Create Post)</a></li>
            <li class="nav-item"><a href="profile.php">👤 プロフィール (Profile)</a></li>

            <li class="nav-item theme-toggle-btn" id="theme-toggle" style="margin-top: auto;">
                <span id="theme-icon">🌙</span> <span id="theme-text">ダークモード</span>
            </li>
            <li class="nav-item logout-btn" style="margin-top: 0;"><a href="logout.php" style="color: #ed4956;">🚪 ログアウト</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <div class="feed-container">

            <?php if (empty($posts)): ?>
                <div class="post-card no-posts">まだ投稿がありません。(No posts yet)</div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-card" id="post_<?php echo $post['id']; ?>">

                        <!-- 1. Post Header (Avatar, Username, Time) -->
                        <div class="post-header">
                            <?php if (!empty($post['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($post['profile_image']); ?>" class="profile-img-avatar" alt="User Profile">
                            <?php else: ?>
                                <div class="avatar-circle">
                                    <?php echo mb_substr(htmlspecialchars($post['username']), 0, 1, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>

                            <div class="header-info">
                                <span class="username"><?php echo htmlspecialchars($post['username']); ?></span>
                                <span class="post-time-top"><?php echo date('Y年m月d日 H:i', strtotime($post['created_at'])); ?></span>
                            </div>
                        </div>

                        <!-- 2. Post Caption (စာသား) -->
                        <?php if (!empty($post['caption'])): ?>
                            <div class="post-info">
                                <div class="post-caption">
                                    <?php echo nl2br(htmlspecialchars($post['caption'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- 3. Post Media (Photo / Video) -->
                        <?php if (!empty($post['image_path'])): ?>
                            <div class="post-image-box">
                                <?php
                                $ext = strtolower(pathinfo($post['image_path'], PATHINFO_EXTENSION));
                                $video_exts = ['mp4', 'webm', 'mov', 'avi'];
                                ?>

                                <?php if (in_array($ext, $video_exts)): ?>
                                    <video controls class="post-image" style="max-height: 500px; width: 100%;">
                                        <source src="<?php echo htmlspecialchars($post['image_path']); ?>" type="video/<?php echo $ext; ?>">
                                        Your browser does not support the video tag.
                                    </video>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" class="post-image" alt="Post Image">
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- 4. Counts Row (Like / Comment အရေအတွက်) -->
                        <div class="counts-row">
                            <div class="likes-count-display">
                                <span>👍❤️</span>
                                <span class="like-count-num"><?php echo $post['like_count']; ?></span> 件のいいね
                            </div>
                            <div class="comments-count-display">
                                <span><?php echo $post['comment_count']; ?> 件のコメント</span>
                            </div>
                        </div>

                        <!-- 5. Action Bar (Like / Comment Buttons) -->
                        <div class="action-bar">
                            <a href="#" class="action-button like-btn" data-post-id="<?php echo $post['id']; ?>">
                                <span class="action-icon"><?php echo $post['is_liked'] ? '💙' : '🤍'; ?></span>
                                <span style="<?php echo $post['is_liked'] ? 'color: #1877f2;' : ''; ?>" class="like-text">いいね!</span>
                            </a>

                            <div class="action-button" onclick="openCommentSheet(<?php echo $post['id']; ?>)">
                                <span class="action-icon">💬</span>
                                <span>コメントする</span>
                            </div>
                        </div>

                    </div> <!-- post-card ပိတ်သည့်နေရာ -->
                <?php endforeach; ?>
            <?php endif; ?>

        </div> <!-- feed-container -->

        <!-- Suggested Friends Sidebar (Users from around the world) -->
        <div class="suggested-sidebar">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                <span style="font-weight: bold; color: var(--text-secondary); font-size: 14px;">おすすめのユーザー</span>
                <a href="explore.php" style="text-decoration: none; font-size: 12px; font-weight: bold; color: #0095f6;">すべて見る</a>
            </div>

            <?php if (empty($suggested_users)): ?>
                <div style="font-size: 13px; color: var(--text-secondary); padding: 10px 0;">新しいおすすめユーザーはいません。</div>
            <?php else: ?>
                <?php foreach ($suggested_users as $sug_user): ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 0;">
                        <a href="profile.php?id=<?php echo $sug_user['id']; ?>" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; min-width: 0; flex: 1;">
                            <?php if (!empty($sug_user['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($sug_user['profile_image']); ?>" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-color); flex-shrink: 0;">
                            <?php else: ?>
                                <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--avatar-bg); display: flex; align-items: center; justify-content: center; font-weight: bold; color: var(--text-color); border: 1px solid var(--border-color); flex-shrink: 0;">
                                    <?php echo mb_substr(htmlspecialchars($sug_user['username']), 0, 1, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                            <div style="display: flex; flex-direction: column; min-width: 0;">
                                <span style="font-weight: bold; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-color);"><?php echo htmlspecialchars($sug_user['username']); ?></span>
                                <span style="font-size: 12px; color: var(--text-secondary);"><?php echo $sug_user['follower_count']; ?> 人のフォロワー</span>
                            </div>
                        </a>
                        <button class="index-follow-btn" data-user-id="<?php echo $sug_user['id']; ?>" style="padding: 6px 14px; background-color: #0095f6; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: bold; cursor: pointer; flex-shrink: 0; margin-left: 8px;">
                            フォロー
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <div class="comment-overlay" id="commentOverlay" onclick="closeCommentSheet()"></div>
    <div class="comment-sheet" id="commentSheet">
        <div class="sheet-header">
            <span>コメント (Comments)</span>
            <span class="sheet-close-btn" onclick="closeCommentSheet()">✕</span>
        </div>

        <div class="sheet-body" id="sheetBody">
        </div>

        <div class="sheet-footer">
            <form action="comment_process.php" method="POST" class="panel-comment-form">
                <input type="hidden" name="post_id" id="sheetPostId">
                <input type="text" name="comment_text" class="panel-comment-input" placeholder="コメントを追加..." required>
                <button type="submit" class="panel-comment-submit">投稿</button>
            </form>
        </div>
    </div>

    <script>
        const postsCommentsData = JSON.parse(`<?php
                                                $encoded_data = [];
                                                foreach ($posts as $p) {
                                                    $c_stmt = $pdo->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = ? ORDER BY comments.created_at ASC");
                                                    $c_stmt->execute([$p['id']]);
                                                    $comments = $c_stmt->fetchAll();

                                                    $encoded_data[$p['id']] = [];
                                                    foreach ($comments as $c) {
                                                        $encoded_data[$p['id']][] = [
                                                            'username' => htmlspecialchars($c['username']),
                                                            'text' => htmlspecialchars($c['comment_text'])
                                                        ];
                                                    }
                                                }
                                                echo addslashes(json_encode($encoded_data, JSON_UNESCAPED_UNICODE));
                                                ?>`);

        const overlay = document.getElementById('commentOverlay');
        const sheet = document.getElementById('commentSheet');
        const sheetBody = document.getElementById('sheetBody');
        const sheetPostIdInput = document.getElementById('sheetPostId');

        function openCommentSheet(postId) {
            sheetPostIdInput.value = postId;
            sheetBody.innerHTML = '';

            const comments = postsCommentsData[postId] || [];

            if (comments.length === 0) {
                sheetBody.innerHTML = '<div class="no-comments">まだコメントがありません。</div>';
            } else {
                comments.forEach(c => {
                    const item = document.createElement('div');
                    item.className = 'panel-comment-item';
                    item.innerHTML = `
                    <div class="panel-comment-bubble">
                        <span class="panel-comment-user">${c.username}</span>
                        <span class="panel-comment-text">${c.text}</span>
                    </div>
                `;
                    sheetBody.appendChild(item);
                });
            }

            overlay.style.display = 'block';
            setTimeout(() => overlay.style.opacity = '1', 10);
            sheet.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeCommentSheet() {
            overlay.style.opacity = '0';
            sheet.classList.remove('active');
            document.body.style.overflow = '';
            setTimeout(() => overlay.style.display = 'none', 300);
        }

        const themeToggleBtn = document.getElementById("theme-toggle");
        const themeIcon = document.getElementById("theme-icon");
        const themeText = document.getElementById("theme-text");

        const currentTheme = localStorage.getItem("theme") || "light";
        document.documentElement.setAttribute("data-theme", currentTheme);
        updateToggleUI(currentTheme);

        themeToggleBtn.addEventListener("click", function() {
            let theme = document.documentElement.getAttribute("data-theme");
            theme = (theme === "dark") ? "light" : "dark";

            // html ရော body ပါ နှစ်ခုလုံးကို dark ချိန်ပေးလိုက်သည်
            document.documentElement.setAttribute("data-theme", theme);
            document.body.setAttribute("data-theme", theme);

            localStorage.setItem("theme", theme);
            updateToggleUI(theme);
        });

        function updateToggleUI(theme) {
            if (theme === "dark") {
                themeIcon.textContent = "☀️";
                themeText.textContent = "ライトモード";
            } else {
                themeIcon.textContent = "🌙";
                themeText.textContent = "ダークモード";
            }
        }
    </script>

    <script>
        document.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); // Page Reload မဖြစ်အောင် တားဆီးသည်

                let postId = this.getAttribute('data-post-id');
                let card = this.closest('.post-card'); // Post Card တစ်ခုလုံးကို ယူသည်
                let countSpan = card.querySelector('.like-count-num');
                let iconSpan = this.querySelector('.action-icon');
                let textSpan = this.querySelector('.like-text');

                // Backend ဆီ AJAX လှမ်းပို့သည်
                fetch('like_process.php?post_id=' + postId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            countSpan.innerText = data.like_count;
                            if (data.is_liked) {
                                iconSpan.innerText = '💙';
                                textSpan.style.color = '#1877f2';
                            } else {
                                iconSpan.innerText = '🤍';
                                textSpan.style.color = '';
                            }
                        }
                    })
                    .catch(err => console.error(err));
            });
        });

        document.querySelectorAll('.index-follow-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const formData = new FormData();
                formData.append('following_id', userId);

                fetch('follow_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (data.is_following) {
                            this.textContent = 'フォロー中';
                            this.style.backgroundColor = 'var(--sidebar-hover)';
                            this.style.color = 'var(--text-color)';
                        } else {
                            this.textContent = 'フォロー';
                            this.style.backgroundColor = '#0095f6';
                            this.style.color = 'white';
                        }
                    }
                })
                .catch(err => console.error(err));
            });
        });
    </script>
</body>

</html>