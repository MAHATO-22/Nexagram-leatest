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
    // 1. Search Query execution
    if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
        $search_query = trim($_GET['search']);
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.profile_pic, u.profile_image, u.bio,
            (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as follower_count,
            (SELECT COUNT(*) FROM follows WHERE follower_id = :c1 AND following_id = u.id) as is_following
            FROM users u
            WHERE u.username LIKE :q AND u.id != :c2
        ");
        $stmt->execute(['q' => "%$search_query%", 'c1' => $current_user_id, 'c2' => $current_user_id]);
        $search_users = $stmt->fetchAll();
    } else {
        // 2. Global Friend Suggestions (Users from around the world)
        $sug_stmt = $pdo->prepare("
            SELECT u.id, u.username, u.profile_pic, u.profile_image, u.bio,
            (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as follower_count,
            (SELECT COUNT(*) FROM follows WHERE follower_id = :c1 AND following_id = u.id) as is_following
            FROM users u
            WHERE u.id != :c2
            ORDER BY is_following ASC, follower_count DESC, u.created_at DESC
            LIMIT 6
        ");
        $sug_stmt->execute(['c1' => $current_user_id, 'c2' => $current_user_id]);
        $suggested_users = $sug_stmt->fetchAll();
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
    <style>
        :root {
            --bg-color: #fafafa;
            --card-bg: #ffffff;
            --text-color: #000000;
            --text-secondary: #8e8e8e;
            --border-color: #dbdbdb;
            --sidebar-hover: #f2f2f2;
            --input-bg: #efefef;
            --avatar-bg: #e4e6eb;
        }

        [data-theme="dark"] {
            --bg-color: #000000;
            --card-bg: #121212;
            --text-color: #f5f5f5;
            --text-secondary: #a8a8a8;
            --border-color: #262626;
            --sidebar-hover: #1c1c1e;
            --input-bg: #1c1c1e;
            --avatar-bg: #3a3b3c;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            transition: background 0.3s, color 0.3s;
        }

        /* Sidebar Navigation */
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

        /* Main Content Layout */
        .main-content {
            margin-left: 245px;
            width: calc(100% - 245px);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
        }

        /* Search Bar Box */
        .search-container {
            width: 100%;
            max-width: 935px;
            margin-bottom: 25px;
        }

        .search-form {
            display: flex;
            gap: 10px;
            width: 100%;
        }

        .search-input {
            flex: 1;
            padding: 12px 18px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 15px;
            background-color: var(--input-bg);
            color: var(--text-color);
            outline: none;
        }

        .search-input:focus {
            border-color: #0095f6;
        }

        .search-btn {
            padding: 0 24px;
            background-color: #0095f6;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .search-btn:hover {
            background-color: #007bb5;
        }

        /* Section Container */
        .section-block {
            width: 100%;
            max-width: 935px;
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: var(--text-color);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* User List Cards */
        .user-list {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 8px 16px;
            list-style: none;
        }

        .user-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .user-item:last-child {
            border-bottom: none;
        }

        .user-info-group {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: inherit;
            min-width: 0;
        }

        .user-avatar {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--border-color);
            background-color: var(--avatar-bg);
            flex-shrink: 0;
        }

        .avatar-placeholder {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background-color: var(--avatar-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
            color: var(--text-color);
            border: 1px solid var(--border-color);
            flex-shrink: 0;
        }

        .user-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .user-name {
            font-weight: bold;
            font-size: 15px;
            color: var(--text-color);
        }

        .user-subtext {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-follow-btn {
            padding: 7px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .action-follow-btn.following {
            background-color: var(--sidebar-hover);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .action-follow-btn.follow {
            background-color: #0095f6;
            color: white;
        }

        .action-chat-btn {
            padding: 7px 14px;
            background-color: var(--sidebar-hover);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        /* Explore Grid Layout */
        .explore-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            width: 100%;
            max-width: 935px;
        }

        .grid-item {
            position: relative;
            width: 100%;
            padding-top: 100%;
            background-color: #000;
            overflow: hidden;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .grid-item img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .grid-item:hover img {
            transform: scale(1.04);
        }
    </style>
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
            <li class="nav-item logout-btn" style="margin-top: 0;"><a href="logout.php" style="color: #ed4956;">🚪 ログアウト</a></li>
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
                                        <span class="user-subtext"><?php echo (int)$u['follower_count']; ?> 人のフォロワー</span>
                                    </div>
                                </a>
                                <div class="user-actions">
                                    <button class="action-follow-btn js-follow-btn <?php echo $u['is_following'] ? 'following' : 'follow'; ?>" data-user-id="<?php echo $u['id']; ?>">
                                        <?php echo $u['is_following'] ? 'フォロー中' : 'フォローする'; ?>
                                    </button>
                                    <a href="messages.php?user_id=<?php echo $u['id']; ?>" class="action-chat-btn">💬 チャット</a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- 2. Global Friend Suggestions Section (when not searching) -->
        <?php if (empty($search_query) && !empty($suggested_users)): ?>
            <div class="section-block">
                <div class="section-title">🌎 世界中のおすすめのユーザー (Suggested Friends Worldwide)</div>
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
                                    <span class="user-subtext"><?php echo (int)$u['follower_count']; ?> 人のフォロワー</span>
                                </div>
                            </a>
                            <div class="user-actions">
                                <button class="action-follow-btn js-follow-btn <?php echo $u['is_following'] ? 'following' : 'follow'; ?>" data-user-id="<?php echo $u['id']; ?>">
                                    <?php echo $u['is_following'] ? 'フォロー中' : 'フォローする'; ?>
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

                fetch('follow_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (data.is_following) {
                            this.textContent = 'フォロー中';
                            this.classList.remove('follow');
                            this.classList.add('following');
                        } else {
                            this.textContent = 'フォローする';
                            this.classList.remove('following');
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