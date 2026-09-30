<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$logged_in_user_id = $_SESSION['user_id'];
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['user_id']) ? (int)$_GET['user_id'] : $logged_in_user_id);

// 1. User アカウント情報取得
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

if (!$user) {
    // Falls back to logged in user if user not found
    $user_id = $logged_in_user_id;
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch();
}

$is_own_profile = ($user_id === (int)$logged_in_user_id);

// 2. User 投稿を取得
$posts_stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$posts_stmt->execute([$user_id]);
$posts = $posts_stmt->fetchAll();
$post_count = count($posts);

// 3. Follower & Following Counts
$followers_stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
$followers_stmt->execute([$user_id]);
$followers_count = (int)$followers_stmt->fetchColumn();

$following_stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
$following_stmt->execute([$user_id]);
$following_count = (int)$following_stmt->fetchColumn();

// 4. Check follow relationships
$is_following = false;
$is_follower = false;
if (!$is_own_profile) {
    $check_follow = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = ?");
    $check_follow->execute([$logged_in_user_id, $user_id]);
    $is_following = ((int)$check_follow->fetchColumn() > 0);

    $check_follower = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = ?");
    $check_follower->execute([$user_id, $logged_in_user_id]);
    $is_follower = ((int)$check_follower->fetchColumn() > 0);
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?> • Profile</title>
    <link rel="stylesheet" href="css/profile.css">
</head>

<body>

   <!-- Navigation Sidebar -->
<div class="sidebar">
    <div>
        <a href="index.php" class="logo" aria-label="Nexagram - Home">Nexagram</a>
        <div class="nav-menu">
            <a href="index.php" class="nav-item">
                <span>🏠</span> ホーム
            </a>
            <a href="explore.php" class="nav-item">
                <span>🔍</span> 検索
            </a>
            <a href="messages.php" class="nav-item">
                <span>✉️</span> メッセージ
            </a>
            <a href="create_post.php" class="nav-item">
                <span>➕</span> 作成
            </a>
            <a href="profile.php" class="nav-item <?php echo $is_own_profile ? 'active' : ''; ?>">
                <span>👤</span> プロフィール
            </a>
        </div>
    </div>

    <div class="nav-bottom">
        <a href="#" class="nav-item">
            <span>🌙</span> ダークモード
        </a>
        <a href="php/logout.php" class="nav-item" style="color: #ed4956;">
            <span>🚪</span> ログアウト
        </a>
    </div>
</div>


    <!-- Main Content Area -->
    <div class="main-content">
        <div class="profile-container">

            <!-- User Profile Header -->
            <div class="profile-header">
                <div class="avatar-box">
                    <?php if (!empty($user['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" class="avatar-circle">
                    <?php else: ?>
                        <div class="avatar-circle"><?php echo mb_substr(htmlspecialchars($user['username']), 0, 1, 'UTF-8'); ?></div>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                    <div style="font-size: 13px; color: #0095f6; font-weight: 600; margin-top: 4px; margin-bottom: 4px;">
                        🏫 <?php echo htmlspecialchars($user['school_name'] ?? 'YSE College'); ?>
                    </div>
                    <?php if ($is_follower): ?>
                        <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 8px; font-weight: 500;">
                            👤 あなたをフォローしています (Follows you)
                        </div>
                    <?php endif; ?>
                    <div class="stats-row">
                        <span><strong><?php echo $post_count; ?></strong> 投稿</span>
                        <span style="cursor: pointer;" onclick="openFollowModal('followers')"><strong id="followers-count"><?php echo $followers_count; ?></strong> フォロワー</span>
                        <span style="cursor: pointer;" onclick="openFollowModal('following')"><strong><?php echo $following_count; ?></strong> フォロー中</span>
                    </div>
                    <?php if ($is_own_profile): ?>
                        <a href="edit_profile.php" class="edit-btn">プロフィールを編集</a>
                    <?php else: ?>
                        <div style="display: flex; gap: 10px; margin-top: 10px;">
                            <?php
                            $btn_label = 'フォローする';
                            $btn_style = 'background-color: #0095f6; color: white; border: none;';
                            if ($is_following) {
                                $btn_label = 'フォロー中';
                                $btn_style = 'background-color: var(--btn-bg); color: var(--btn-text); border: 1px solid var(--border-color);';
                            } elseif ($is_follower) {
                                $btn_label = '↩️ フォローバック (Follow Back)';
                                $btn_style = 'background-color: #0095f6; color: white; border: none; font-weight: bold;';
                            }
                            ?>
                            <button id="follow-btn" class="edit-btn" style="<?php echo $btn_style; ?> font-weight: bold; cursor: pointer; padding: 6px 16px;">
                                <?php echo $btn_label; ?>
                            </button>
                            <a href="messages.php?user_id=<?php echo $user_id; ?>" class="edit-btn" style="background-color: var(--btn-bg); color: var(--btn-text); border: 1px solid var(--border-color); text-decoration: none; font-weight: 600; padding: 6px 16px;">💬 メッセージ</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Posts Grid -->
            <div class="posts-grid">
                <?php foreach ($posts as $p): ?>
                    <div class="grid-item" onclick='openModal(<?php echo json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>
                        <?php
                        if (!empty($p['image_path'])) {
                            $ext = strtolower(pathinfo($p['image_path'], PATHINFO_EXTENSION));
                            if (in_array($ext, ['mp4', 'webm'])) {
                                echo '<video src="' . htmlspecialchars($p['image_path']) . '"></video>';
                            } else {
                                echo '<img src="' . htmlspecialchars($p['image_path']) . '">';
                            }
                        } else {
                            echo '<div class="text-post-preview">' . htmlspecialchars(mb_substr($p['caption'] ?? '', 0, 50, 'UTF-8')) . '...</div>';
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

    <!-- Instagram Style Post Modal -->
    <div class="modal-overlay" id="postModal" onclick="closeModal(event)">
        <div class="modal-box" onclick="event.stopPropagation()">
            <div class="modal-media" id="modalMedia"></div>

            <div class="modal-details">
                <!-- 1. Header -->
                <div class="modal-header-sec">
                    <div class="user-info-group">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" class="modal-avatar">
                        <?php else: ?>
                            <div class="modal-avatar"><?php echo mb_substr(htmlspecialchars($user['username']), 0, 1, 'UTF-8'); ?></div>
                        <?php endif; ?>
                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                    </div>
                    <button class="options-btn" onclick="toggleOptionsMenu()">•••</button>
                </div>

                <!-- 2. Caption & Comments -->
                <div class="modal-body-sec">
                    <div class="caption-row">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" class="modal-avatar">
                        <?php else: ?>
                            <div class="modal-avatar"><?php echo mb_substr(htmlspecialchars($user['username']), 0, 1, 'UTF-8'); ?></div>
                        <?php endif; ?>
                        <div>
                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                            <span id="modalCaption"></span>
                        </div>
                    </div>
                    <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 12px 0;">
                    <div id="commentsContainer"></div>
                </div>

                <!-- 3. Footer (Action Buttons, Likes & Input) -->
                <div class="modal-footer-sec">
                    <div class="action-icons">
                        <span class="like-btn" id="likeIcon" onclick="toggleLike()">🤍</span>
                        <span style="cursor: pointer;" onclick="focusComment()">💬</span>
                    </div>
                    <div style="font-weight: 600; font-size: 14px;" id="likeCountText">0 likes</div>
                    <div class="post-date" id="modalDate"></div>

                    <div class="add-comment-box">
                        <input type="text" id="commentInput" placeholder="Add a comment..." onkeypress="handleCommentKeyPress(event)">
                        <button class="post-btn" onclick="submitComment()">Post</button>
                    </div>
                </div>

                <!-- Options Popup -->
                <div class="options-menu" id="optionsMenu">
                    <a href="#" id="deletePostBtn" class="delete-text">Delete</a>
                    <a href="#" id="editPostBtn">Edit</a>
                    <button onclick="toggleOptionsMenu()">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentPostId = null;

        function openModal(post) {
            currentPostId = post.id;
            const modal = document.getElementById('postModal');
            const mediaBox = document.getElementById('modalMedia');
            const captionBox = document.getElementById('modalCaption');
            const dateBox = document.getElementById('modalDate');

            document.getElementById('deletePostBtn').href = 'php/delete_post.php?id=' + post.id;
            document.getElementById('editPostBtn').href = 'edit_post.php?id=' + post.id;

            captionBox.innerText = ' ' + (post.caption || '');
            if (post.created_at) {
                dateBox.innerText = new Date(post.created_at).toDateString().toUpperCase();
            }

            if (post.image_path) {
                const ext = post.image_path.split('.').pop().toLowerCase();
                if (['mp4', 'webm'].includes(ext)) {
                    mediaBox.innerHTML = '<video src="' + post.image_path + '" controls></video>';
                } else {
                    mediaBox.innerHTML = '<img src="' + post.image_path + '">';
                }
            } else {
                mediaBox.innerHTML = '<p style="padding: 20px; text-align: center; color: #fff;">' + (post.caption || '') + '</p>';
            }

            // Real-time Likes & Comments
            loadPostDetails(post.id);

            modal.style.display = 'flex';
        }

        function loadPostDetails(postId) {
            fetch('php/get_post_details.php?post_id=' + postId)
                .then(res => res.json())
                .then(data => {
                    if (data.success !== true && data.status !== 'success') return;

                    document.getElementById('likeCountText').innerText = data.like_count + ' likes';
                    document.getElementById('likeIcon').innerText = data.is_liked ? '❤️' : '🤍';

                    const container = document.getElementById('commentsContainer');
                    container.innerHTML = '';
                    if (data.comments) {
                        data.comments.forEach(c => {
                            const div = document.createElement('div');
                            div.className = 'comment-item';
                            div.innerHTML = '<strong>' + c.username + '</strong> <span>' + c.comment + '</span>';
                            container.appendChild(div);
                        });
                    }
                })
                .catch(err => console.error("Fetch Error:", err));
        }

        function toggleLike() {
            if (!currentPostId) return;
            const formData = new FormData();
            formData.append('post_id', currentPostId);

            fetch('php/like_process.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success === true || data.status === 'success') {
                        document.getElementById('likeIcon').innerText = data.is_liked ? '❤️' : '🤍';
                        document.getElementById('likeCountText').innerText = data.like_count + ' likes';
                    }
                })
                .catch(err => console.error("Like Error:", err));
        }

        function focusComment() {
            document.getElementById('commentInput').focus();
        }

        function handleCommentKeyPress(e) {
            if (e.key === 'Enter') submitComment();
        }

        function submitComment() {
            const input = document.getElementById('commentInput');
            const comment = input.value.trim();
            if (!comment || !currentPostId) return;

            const formData = new FormData();
            formData.append('post_id', currentPostId);
            // backend က comment_text ကို သုံးသည်
            formData.append('comment_text', comment);

            fetch('php/comment_process.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success === true || data.status === 'success') {
                        input.value = '';
                        loadPostDetails(currentPostId);
                    }
                })
                .catch(err => console.error("Comment Error:", err));
        }

        function closeModal(e) {
            if (e.target.classList.contains('modal-overlay')) {
                document.getElementById('postModal').style.display = 'none';
                document.getElementById('optionsMenu').style.display = 'none';
            }
        }

        function toggleOptionsMenu() {
            const menu = document.getElementById('optionsMenu');
            menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
        }

        const followBtn = document.getElementById('follow-btn');
        if (followBtn) {
            followBtn.addEventListener('click', function() {
                const formData = new FormData();
                formData.append('following_id', <?php echo $user_id; ?>);

                fetch('php/follow_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const cnt = document.getElementById('followers-count');
                        if (cnt) cnt.textContent = data.follower_count;

                        if (data.is_following) {
                            followBtn.textContent = 'フォロー中';
                            followBtn.style.backgroundColor = 'var(--btn-bg)';
                            followBtn.style.color = 'var(--btn-text)';
                            followBtn.style.border = '1px solid var(--border-color)';
                        } else if (data.is_follower) {
                            followBtn.textContent = '↩️ フォローバック (Follow Back)';
                            followBtn.style.backgroundColor = '#0095f6';
                            followBtn.style.color = 'white';
                            followBtn.style.border = 'none';
                        } else {
                            followBtn.textContent = 'フォローする';
                            followBtn.style.backgroundColor = '#0095f6';
                            followBtn.style.color = 'white';
                            followBtn.style.border = 'none';
                        }
                    } else {
                        alert(data.error || 'Follow action failed');
                    }
                })
                .catch(err => console.error(err));
            });
        }

        // Followers & Following Modal Engine
        let activeFollowTab = 'followers';
        let followModalUserId = <?php echo $user_id; ?>;

        function openFollowModal(type) {
            activeFollowTab = type;
            document.getElementById('followListModal').style.display = 'flex';
            switchFollowTab(type);
        }

        function closeFollowModal(e) {
            if (e.target.id === 'followListModal') {
                document.getElementById('followListModal').style.display = 'none';
            }
        }

        function switchFollowTab(type) {
            activeFollowTab = type;
            const tabF = document.getElementById('tab-followers');
            const tabFing = document.getElementById('tab-following');

            if (type === 'followers') {
                tabF.style.borderBottom = '2px solid #0095f6';
                tabF.style.color = 'var(--text-color)';
                tabFing.style.borderBottom = 'none';
                tabFing.style.color = 'var(--text-secondary)';
            } else {
                tabFing.style.borderBottom = '2px solid #0095f6';
                tabFing.style.color = 'var(--text-color)';
                tabF.style.borderBottom = 'none';
                tabF.style.color = 'var(--text-secondary)';
            }

            const body = document.getElementById('followListBody');
            body.innerHTML = '<div style="text-align: center; color: var(--text-secondary); padding: 20px;">読み込み中...</div>';

            fetch(`php/fetch_follow_list.php?type=${type}&user_id=${followModalUserId}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.success || data.users.length === 0) {
                        body.innerHTML = `<div style="text-align: center; color: var(--text-secondary); padding: 20px;">${type === 'followers' ? 'フォロワーはいません。' : 'フォロー中のユーザーはいません。'}</div>`;
                        return;
                    }

                    let html = '';
                    data.users.forEach(u => {
                        let btnText = 'フォローする';
                        let btnStyle = 'background-color: #0095f6; color: white; border: none;';

                        if (u.is_following) {
                            btnText = 'フォロー中';
                            btnStyle = 'background-color: var(--btn-bg); color: var(--btn-text); border: 1px solid var(--border-color);';
                        } else if (u.is_follower) {
                            btnText = '↩️ フォローバック';
                            btnStyle = 'background-color: #0095f6; color: white; border: none; font-weight: bold;';
                        }

                        const isSelf = (u.id == <?php echo $logged_in_user_id; ?>);
                        const followBtnHtml = isSelf ? '' : `
                            <button class="list-follow-btn-${u.id}" onclick="toggleListFollow(${u.id}, this)" style="padding: 5px 12px; border-radius: 6px; font-size: 12px; cursor: pointer; ${btnStyle}">
                                ${btnText}
                            </button>
                            <a href="messages.php?user_id=${u.id}" style="padding: 5px 10px; background-color: var(--btn-bg); color: var(--btn-text); border: 1px solid var(--border-color); border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600;">💬</a>
                        `;

                        const followerTag = (u.is_follower && !u.is_following) ? `<span style="font-size: 11px; color: #0095f6;">(あなたをフォロー中)</span>` : '';

                        html += `
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 4px; border-bottom: 1px solid var(--border-color);">
                                <a href="profile.php?id=${u.id}" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit; flex: 1; min-width: 0;">
                                    <img src="${u.avatar_url}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-color); flex-shrink: 0;">
                                    <div style="display: flex; flex-direction: column; min-width: 0;">
                                        <span style="font-weight: bold; font-size: 13px; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(u.username)} ${followerTag}</span>
                                        <span style="font-size: 11px; color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">🏫 ${escapeHtml(u.school_name || 'YSE College')}</span>
                                    </div>
                                </a>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    ${followBtnHtml}
                                </div>
                            </div>
                        `;
                    });
                    body.innerHTML = html;
                });
        }

        function toggleListFollow(userId, btnEl) {
            const formData = new FormData();
            formData.append('following_id', userId);

            fetch('php/follow_process.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (data.is_following) {
                            btnEl.textContent = 'フォロー中';
                            btnEl.style.backgroundColor = 'var(--btn-bg)';
                            btnEl.style.color = 'var(--btn-text)';
                            btnEl.style.border = '1px solid var(--border-color)';
                        } else if (data.is_follower) {
                            btnEl.textContent = '↩️ フォローバック';
                            btnEl.style.backgroundColor = '#0095f6';
                            btnEl.style.color = 'white';
                            btnEl.style.border = 'none';
                        } else {
                            btnEl.textContent = 'フォローする';
                            btnEl.style.backgroundColor = '#0095f6';
                            btnEl.style.color = 'white';
                            btnEl.style.border = 'none';
                        }
                    }
                });
        }
    </script>

    <!-- Followers / Following List Modal -->
    <div class="modal-overlay" id="followListModal" onclick="closeFollowModal(event)" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); z-index: 1200; justify-content: center; align-items: center;">
        <div style="background: var(--bg-color); color: var(--text-color); width: 90%; max-width: 440px; border-radius: 12px; overflow: hidden; border: 1px solid var(--border-color); display: flex; flex-direction: column; max-height: 80vh;">
            <div style="display: flex; border-bottom: 1px solid var(--border-color); background: var(--card-bg);">
                <button id="tab-followers" onclick="switchFollowTab('followers')" style="flex: 1; padding: 14px; background: none; border: none; font-weight: bold; font-size: 15px; color: var(--text-color); border-bottom: 2px solid #0095f6; cursor: pointer;">フォロワー</button>
                <button id="tab-following" onclick="switchFollowTab('following')" style="flex: 1; padding: 14px; background: none; border: none; font-weight: bold; font-size: 15px; color: var(--text-secondary); cursor: pointer;">フォロー中</button>
                <button onclick="document.getElementById('followListModal').style.display='none'" style="padding: 14px; background: none; border: none; font-size: 18px; color: var(--text-color); cursor: pointer;">✕</button>
            </div>
            <div id="followListBody" style="padding: 12px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 8px;">
                <div style="text-align: center; color: var(--text-secondary); padding: 20px;">読み込み中...</div>
            </div>
        </div>
    </div>
</body>

</html>