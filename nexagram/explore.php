<?php
// explore.php - Nexagram Explore, Search & Global Friend Suggestions Page
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$search_query = "";
$search_users = [];
$suggested_users = [];
$explore_posts = [];

try {
    // Fetch current user's school
    $user_info_stmt = $pdo->prepare("SELECT school_name FROM users WHERE id = ?");
    $user_info_stmt->execute([$current_user_id]);
    $current_school = $user_info_stmt->fetchColumn() ?: 'YSE College';

    // 1. Search Query execution
    if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
        $search_query = trim($_GET['search']);
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.school_name, u.profile_pic, u.profile_image, u.bio,
            (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as follower_count,
            (SELECT COUNT(*) FROM follows WHERE follower_id = :c1 AND following_id = u.id) as is_following,
            (SELECT COUNT(*) FROM follows WHERE follower_id = u.id AND following_id = :c2) as is_follower
            FROM users u
            WHERE u.username LIKE :q AND u.id != :c3
        ");
        $stmt->execute(['q' => "%$search_query%", 'c1' => $current_user_id, 'c2' => $current_user_id, 'c3' => $current_user_id]);
        $search_users = $stmt->fetchAll();
    } else {
        // 2. Classmate Friend Suggestions (Users from the SAME school/college)
        $sug_stmt = $pdo->prepare("
            SELECT u.id, u.username, u.school_name, u.profile_pic, u.profile_image, u.bio,
            (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as follower_count,
            (SELECT COUNT(*) FROM follows WHERE follower_id = :c1 AND following_id = u.id) as is_following,
            (SELECT COUNT(*) FROM follows WHERE follower_id = u.id AND following_id = :c2) as is_follower
            FROM users u
            WHERE u.id != :c3 AND u.school_name = :sch
            ORDER BY is_follower DESC, is_following ASC, follower_count DESC, u.created_at DESC
            LIMIT 10
        ");
        $sug_stmt->execute(['c1' => $current_user_id, 'c2' => $current_user_id, 'c3' => $current_user_id, 'sch' => $current_school]);
        $suggested_users = $sug_stmt->fetchAll();

        // Fallback if no specific classmate recommendations found
        if (empty($suggested_users)) {
            $fb_stmt = $pdo->prepare("
                SELECT u.id, u.username, u.school_name, u.profile_pic, u.profile_image, u.bio,
                (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as follower_count,
                (SELECT COUNT(*) FROM follows WHERE follower_id = :c1 AND following_id = u.id) as is_following,
                (SELECT COUNT(*) FROM follows WHERE follower_id = u.id AND following_id = :c2) as is_follower
                FROM users u
                WHERE u.id != :c3
                ORDER BY is_follower DESC, is_following ASC, follower_count DESC, u.created_at DESC
                LIMIT 10
            ");
            $fb_stmt->execute(['c1' => $current_user_id, 'c2' => $current_user_id, 'c3' => $current_user_id]);
            $suggested_users = $fb_stmt->fetchAll();
        }
    }

    // 3. Explore Grid Posts
    $post_stmt = $pdo->query("SELECT id, image_path, caption FROM posts ORDER BY created_at DESC");
    $explore_posts = $post_stmt->fetchAll();
} catch (\PDOException $e) {
    die("エラー: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexagram - 検索 / 友達おすすめ</title>
    <link rel="stylesheet" href="css/explore.css">
</head>

<body>

    <!-- Left Sidebar Navigation -->
    <nav class="sidebar">
        <div class="logo">Nexagram</div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php">🏠 ホーム (Home)</a></li>
            <li class="nav-item" style="background-color: var(--sidebar-hover);"><strong><a href="explore.php">🔍 検索 (Search)</a></strong></li>
            <li class="nav-item"><a href="messages.php">✉️ メッセージ (Messages)</a></li>
            <li class="nav-item"><a href="create_post.php">➕ 作成 (Create Post)</a></li>
            <li class="nav-item"><a href="profile.php">👤 プロフィール (Profile)</a></li>

            <li class="nav-item theme-toggle-btn" id="theme-toggle" style="margin-top: auto;">
                <span id="theme-icon">🌙</span> <span id="theme-text">ダークモード</span>
            </li>
            <li class="nav-item logout-btn" style="margin-top: 0;"><a href="php/logout.php" style="color: #ed4956;">🚪 ログアウト</a></li>
        </ul>
    </nav>

    <!-- Main Explore Area -->
    <main class="main-content">

        <!-- Search Bar Box -->
        <div class="search-container">
            <form action="explore.php" method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="世界中のユーザーを検索... (Search global users...)" value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-btn">検索</button>
            </form>
        </div>

        <!-- 1. Search Results Section -->
        <?php if (!empty($search_query)): ?>
            <div class="section-block">
                <div class="section-title">🔍 「<?php echo htmlspecialchars($search_query); ?>」の検索結果</div>
                <ul class="user-list">
                    <?php if (empty($search_users)): ?>
                        <li style="padding: 16px; color: var(--text-secondary); text-align: center; font-size: 14px;">ユーザーが見つかりませんでした。</li>
                    <?php else: ?>
                        <?php foreach ($search_users as $u): ?>
                            <li class="user-item">
                                <a href="profile.php?id=<?php echo $u['id']; ?>" class="user-info-group">
                                    <?php
                                    $avatar = !empty($u['profile_image']) ? $u['profile_image'] : (!empty($u['profile_pic']) && $u['profile_pic'] !== 'default.png' ? $u['profile_pic'] : '');
                                    ?>
                                    <?php if ($avatar): ?>
                                        <img src="<?php echo htmlspecialchars($avatar); ?>" class="user-avatar" alt="Avatar">
                                    <?php else: ?>
                                        <div class="avatar-placeholder"><?php echo mb_substr(htmlspecialchars($u['username']), 0, 1, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                    <div class="user-text">
                                        <span class="user-name"><?php echo htmlspecialchars($u['username']); ?></span>
                                        <span class="user-subtext" style="color: #0095f6; font-size: 12px; font-weight: 500;">🏫 <?php echo htmlspecialchars($u['school_name'] ?? 'YSE College'); ?> • <?php echo (int)$u['follower_count']; ?> 人のフォロワー</span>
                                    </div>
                                </a>
                                <div class="user-actions">
                                    <button class="action-follow-btn js-follow-btn <?php echo $u['is_following'] ? 'following' : ($u['is_follower'] ? 'followback' : 'follow'); ?>" data-user-id="<?php echo $u['id']; ?>" data-is-follower="<?php echo (int)$u['is_follower']; ?>">
                                        <?php echo $u['is_following'] ? 'フォロー中' : ($u['is_follower'] ? '↩️ フォローバック' : 'フォローする'); ?>
                                    </button>
                                    <a href="messages.php?user_id=<?php echo $u['id']; ?>" class="action-chat-btn">💬 チャット</a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- 2. Classmate & School Friend Suggestions Section (when not searching) -->
        <?php if (empty($search_query) && !empty($suggested_users)): ?>
            <div class="section-block">
                <div class="section-title">🏫 同じ学校・大学のおすすめの友達 (Suggested School & College Classmates)</div>
                <ul class="user-list">
                    <?php foreach ($suggested_users as $u): ?>
                        <li class="user-item">
                            <a href="profile.php?id=<?php echo $u['id']; ?>" class="user-info-group">
                                <?php
                                $avatar = !empty($u['profile_image']) ? $u['profile_image'] : (!empty($u['profile_pic']) && $u['profile_pic'] !== 'default.png' ? $u['profile_pic'] : '');
                                ?>
                                <?php if ($avatar): ?>
                                    <img src="<?php echo htmlspecialchars($avatar); ?>" class="user-avatar" alt="Avatar">
                                <?php else: ?>
                                    <div class="avatar-placeholder"><?php echo mb_substr(htmlspecialchars($u['username']), 0, 1, 'UTF-8'); ?></div>
                                <?php endif; ?>
                                <div class="user-text">
                                    <span class="user-name"><?php echo htmlspecialchars($u['username']); ?></span>
                                    <span class="user-subtext" style="color: #0095f6; font-size: 12px; font-weight: 500;">🏫 <?php echo htmlspecialchars($u['school_name'] ?? 'YSE College'); ?> • <?php echo (int)$u['follower_count']; ?> 人のフォロワー</span>
                                </div>
                            </a>
                            <div class="user-actions">
                                <button class="action-follow-btn js-follow-btn <?php echo $u['is_following'] ? 'following' : ($u['is_follower'] ? 'followback' : 'follow'); ?>" data-user-id="<?php echo $u['id']; ?>" data-is-follower="<?php echo (int)$u['is_follower']; ?>">
                                    <?php echo $u['is_following'] ? 'フォロー中' : ($u['is_follower'] ? '↩️ フォローバック' : 'フォローする'); ?>
                                </button>
                                <a href="messages.php?user_id=<?php echo $u['id']; ?>" class="action-chat-btn">💬 チャット</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- 3. Explore Posts Grid -->
        <div class="section-block">
            <div class="section-title">✨ トレンドの投稿 (Explore Feed)</div>
            <div class="explore-grid">
                <?php if (empty($explore_posts)): ?>
                    <div style="grid-column: span 3; text-align: center; color: var(--text-secondary); padding: 40px;">まだ投稿がありません。</div>
                <?php else: ?>
                    <?php foreach ($explore_posts as $post): ?>
                        <div class="grid-item" title="<?php echo htmlspecialchars($post['caption']); ?>">
                            <?php if (!empty($post['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Explore Post">
                            <?php else: ?>
                                <div style="position:absolute;top:0;left:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;padding:12px;text-align:center;background:#222;box-sizing:border-box;">
                                    <?php echo htmlspecialchars(mb_substr($post['caption'] ?? '', 0, 50, 'UTF-8')); ?>...
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <!-- Theme & Follow Scripts -->
    <script>
        // Dark Mode Controller
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const themeText = document.getElementById('theme-text');

        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        document.body.setAttribute('data-theme', savedTheme);
        updateToggleUI(savedTheme);

        themeToggleBtn.addEventListener('click', () => {
            let currentTheme = document.body.getAttribute('data-theme') || 'light';
            let newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            document.documentElement.setAttribute('data-theme', newTheme);
            document.body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateToggleUI(newTheme);
        });

        function updateToggleUI(theme) {
            if (theme === 'dark') {
                themeIcon.textContent = '☀️';
                themeText.textContent = 'ライトモード';
            } else {
                themeIcon.textContent = '🌙';
                themeText.textContent = 'ダークモード';
            }
        }

        // AJAX Follow / Unfollow Buttons
        document.querySelectorAll('.js-follow-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
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
                            this.classList.remove('follow', 'followback');
                            this.classList.add('following');
                        } else if (data.is_follower) {
                            this.textContent = '↩️ フォローバック';
                            this.classList.remove('following', 'follow');
                            this.classList.add('followback');
                        } else {
                            this.textContent = 'フォローする';
                            this.classList.remove('following', 'followback');
                            this.classList.add('follow');
                        }
                    } else {
                        alert(data.error || 'エラーが発生しました');
                    }
                })
                .catch(err => console.error('Follow error:', err));
            });
        });
    </script>
</body>

</html>