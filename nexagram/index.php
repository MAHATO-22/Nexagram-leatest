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

    // Fetch current logged-in user's school
    $user_info_stmt = $pdo->prepare("SELECT school_name FROM users WHERE id = ?");
    $user_info_stmt->execute([$current_user_id]);
    $current_school = $user_info_stmt->fetchColumn() ?: 'YSE College';

    // Suggested Friends Query (Classmates & Friends from SAME school/college only)
    $sug_query = "SELECT u.id, u.username, u.school_name, u.profile_image, u.profile_pic, u.bio,
                  (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as follower_count
                  FROM users u
                  WHERE u.id != :c1 
                  AND u.school_name = :sch
                  AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id = :c2)
                  ORDER BY RAND()
                  LIMIT 5";
    $sug_stmt = $pdo->prepare($sug_query);
    $sug_stmt->execute(['c1' => $current_user_id, 'sch' => $current_school, 'c2' => $current_user_id]);
    $suggested_users = $sug_stmt->fetchAll();

    // Fallback if no specific classmate suggestions found
    if (empty($suggested_users)) {
        $fb_query = "SELECT u.id, u.username, u.school_name, u.profile_image, u.profile_pic, u.bio,
                      (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as follower_count
                      FROM users u
                      WHERE u.id != :c1 
                      AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id = :c2)
                      ORDER BY RAND()
                      LIMIT 5";
        $fb_stmt = $pdo->prepare($fb_query);
        $fb_stmt->execute(['c1' => $current_user_id, 'c2' => $current_user_id]);
        $suggested_users = $fb_stmt->fetchAll();
    }
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
    <link rel="stylesheet" href="css/index.css">
</head>

<body>

    <nav class="sidebar">
        <a href="index.php" class="logo" aria-label="Nexagram - ホーム">Nexagram</a>
        <ul class="nav-menu">
            <li class="nav-item" style="background-color: var(--sidebar-hover);"><strong><a href="index.php">🏠 ホーム</a></strong></li>
            <li class="nav-item"><a href="explore.php">🔍 検索 </a></li>
            <li class="nav-item"><a href="messages.php">✉️ メッセージ</a></li>
            <li class="nav-item"><a href="create_post.php">➕ 作成</a></li>
            <li class="nav-item"><a href="profile.php">👤 プロフィール</a></li>

            <li class="nav-item theme-toggle-btn" id="theme-toggle" style="margin-top: auto;">
                <span id="theme-icon">🌙</span> <span id="theme-text">ダークモード</span>
            </li>
            <li class="nav-item logout-btn" style="margin-top: 0;"><a href="php/logout.php" style="color: #ed4956;">🚪 ログアウト</a></li>
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

        <!-- Suggested Friends Sidebar (Same School / College Friends) -->
        <div class="suggested-sidebar">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                <span style="font-weight: bold; color: var(--text-secondary); font-size: 14px;">🏫 同校の友達おすすめ</span>
                <a href="explore.php" style="text-decoration: none; font-size: 12px; font-weight: bold; color: #0095f6;">すべて見る</a>
            </div>

            <?php if (empty($suggested_users)): ?>
                <div style="font-size: 13px; color: var(--text-secondary); padding: 10px 0;">同じ学校の新しいおすすめユーザーはいません。</div>
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
                                <span style="font-size: 11px; color: #0095f6; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">🏫 <?php echo htmlspecialchars($sug_user['school_name'] ?? 'YSE College'); ?></span>
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
            <div class="quick-emoji-bar">
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('❤️')">❤️</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('😂')">😂</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('👍')">👍</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('🔥')">🔥</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('🎉')">🎉</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('😍')">😍</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('🥰')">🥰</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('😊')">😊</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('😘')">😘</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('🤣')">🤣</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('😅')">😅</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('😭')">😭</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('🤔')">🤔</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('👏')">👏</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('💯')">💯</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('🙏')">🙏</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('✨')">✨</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('🫶')">🫶</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('🥳')">🥳</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('😎')">😎</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('😴')">😴</button>
                <button type="button" class="emoji-btn" onclick="insertCommentEmoji('💔')">💔</button>
            </div>
            <form action="php/comment_process.php" method="POST" class="panel-comment-form">
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

        function insertCommentEmoji(emoji) {
            const input = document.querySelector('.panel-comment-input');
            if (input) {
                input.value += emoji;
                input.focus();
            }
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
                fetch('php/like_process.php?post_id=' + postId)
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

                fetch('php/follow_process.php', {
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