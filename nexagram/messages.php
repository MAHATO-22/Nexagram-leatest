<?php
// messages.php - Nexagram Real-Time Direct Messaging (Messages) Page
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];

// Selected active chat user ID from query parameter if present
$active_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexagram • メッセージ (Messages)</title>
    <style>
        :root {
            --bg-color: #fafafa;
            --card-bg: #ffffff;
            --text-color: #000000;
            --text-secondary: #8e8e8e;
            --border-color: #dbdbdb;
            --sidebar-hover: #f2f2f2;
            --bubble-sent: linear-gradient(135deg, #0095f6 0%, #007bb5 100%);
            --bubble-sent-text: #ffffff;
            --bubble-received: #efefef;
            --bubble-received-text: #000000;
            --input-bg: #f0f2f5;
            --active-chat-bg: #eef7ff;
            --badge-bg: #0095f6;
            --badge-text: #ffffff;
            --avatar-bg: #e4e6eb;
        }

        [data-theme="dark"] {
            --bg-color: #000000;
            --card-bg: #121212;
            --text-color: #f5f5f5;
            --text-secondary: #a8a8a8;
            --border-color: #262626;
            --sidebar-hover: #1c1c1e;
            --bubble-sent: linear-gradient(135deg, #0095f6 0%, #0072b1 100%);
            --bubble-sent-text: #ffffff;
            --bubble-received: #262626;
            --bubble-received-text: #f5f5f5;
            --input-bg: #1c1c1e;
            --active-chat-bg: #1a2734;
            --badge-bg: #0095f6;
            --badge-text: #ffffff;
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
            height: 100vh;
            overflow: hidden;
            transition: background 0.3s, color 0.3s;
        }

        /* 1. Main Navigation Sidebar */
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

        .nav-item.active {
            font-weight: bold;
            background-color: var(--sidebar-hover);
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

        /* 2. Messages Layout Container */
        .chat-layout-wrapper {
            margin-left: 245px;
            width: calc(100% - 245px);
            height: 100vh;
            display: flex;
            background-color: var(--bg-color);
        }

        /* 3. Conversation Sub-Sidebar (Friends List) */
        .conversations-sidebar {
            width: 350px;
            border-right: 1px solid var(--border-color);
            background-color: var(--card-bg);
            display: flex;
            flex-direction: column;
            height: 100%;
            transition: background 0.3s, border 0.3s;
        }

        .conversations-header {
            padding: 20px 20px 15px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .conversations-header h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 9px 14px 9px 36px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background-color: var(--input-bg);
            color: var(--text-color);
            font-size: 14px;
            outline: none;
        }

        .search-box .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 14px;
        }

        .conversations-list {
            flex: 1;
            overflow-y: auto;
            list-style: none;
        }

        .conversations-list::-webkit-scrollbar {
            width: 5px;
        }
        .conversations-list::-webkit-scrollbar-thumb {
            background-color: var(--border-color);
            border-radius: 10px;
        }

        .conv-item {
            display: flex;
            align-items: center;
            padding: 14px 18px;
            cursor: pointer;
            transition: background 0.2s;
            border-bottom: 1px solid rgba(0, 0, 0, 0.03);
            text-decoration: none;
            color: inherit;
        }

        .conv-item:hover {
            background-color: var(--sidebar-hover);
        }

        .conv-item.active {
            background-color: var(--active-chat-bg);
            border-left: 4px solid #0095f6;
        }

        .conv-avatar-wrapper {
            position: relative;
            margin-right: 14px;
            flex-shrink: 0;
        }

        .conv-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--border-color);
            background-color: var(--avatar-bg);
        }

        .conv-avatar-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--avatar-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .conv-info {
            flex: 1;
            min-width: 0;
        }

        .conv-top-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 4px;
        }

        .conv-username {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-color);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conv-time {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .conv-bottom-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .conv-snippet {
            font-size: 13px;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 190px;
        }

        .conv-item.unread .conv-snippet {
            font-weight: bold;
            color: var(--text-color);
        }

        .unread-badge {
            background-color: var(--badge-bg);
            color: var(--badge-text);
            font-size: 11px;
            font-weight: bold;
            padding: 2px 7px;
            border-radius: 12px;
            min-width: 18px;
            text-align: center;
        }

        /* 4. Active Chat Main Panel */
        .chat-main-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            background-color: var(--card-bg);
        }

        /* Empty State */
        .chat-empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
        }

        .chat-empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .chat-empty-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 8px;
        }

        .chat-empty-sub {
            font-size: 14px;
            max-width: 320px;
            line-height: 1.5;
        }

        /* Active Chat View */
        .chat-header {
            padding: 14px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: var(--card-bg);
            z-index: 10;
        }

        .chat-user-profile {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: inherit;
        }

        .chat-header-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--border-color);
            background-color: var(--avatar-bg);
        }

        .chat-header-info {
            display: flex;
            flex-direction: column;
        }

        .chat-header-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-color);
        }

        .chat-header-status {
            font-size: 12px;
            color: #10b981;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
        }

        .view-profile-btn {
            padding: 6px 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-color);
            color: var(--text-color);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
        }

        .view-profile-btn:hover {
            background-color: var(--sidebar-hover);
        }

        /* Message List Area */
        .messages-container {
            flex: 1;
            padding: 20px 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background-color: var(--bg-color);
        }

        .messages-container::-webkit-scrollbar {
            width: 6px;
        }

        .messages-container::-webkit-scrollbar-thumb {
            background-color: var(--border-color);
            border-radius: 10px;
        }

        .date-divider {
            text-align: center;
            margin: 10px 0;
            position: relative;
        }

        .date-divider span {
            background-color: var(--card-bg);
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            font-weight: 500;
        }

        .msg-row {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            max-width: 75%;
        }

        .msg-row.sent {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .msg-row.received {
            align-self: flex-start;
        }

        .msg-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 2px;
        }

        .msg-bubble-wrapper {
            display: flex;
            flex-direction: column;
        }

        .msg-row.sent .msg-bubble-wrapper {
            align-items: flex-end;
        }

        .msg-bubble {
            padding: 10px 16px;
            border-radius: 18px;
            font-size: 14px;
            line-height: 1.45;
            word-wrap: break-word;
            max-width: 100%;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .msg-row.sent .msg-bubble {
            background: var(--bubble-sent);
            color: var(--bubble-sent-text);
            border-bottom-right-radius: 4px;
        }

        .msg-row.received .msg-bubble {
            background-color: var(--bubble-received);
            color: var(--bubble-received-text);
            border-bottom-left-radius: 4px;
            border: 1px solid var(--border-color);
        }

        .msg-meta {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 3px;
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 0 4px;
        }

        .read-status {
            color: #0095f6;
            font-weight: bold;
        }

        /* Chat Input Footer Area */
        .chat-input-area {
            padding: 14px 20px;
            border-top: 1px solid var(--border-color);
            background-color: var(--card-bg);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .quick-emoji-bar {
            display: flex;
            gap: 12px;
            padding-left: 4px;
        }

        .emoji-btn {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            transition: transform 0.15s;
        }

        .emoji-btn:hover {
            transform: scale(1.3);
        }

        .input-form {
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: var(--input-bg);
            border-radius: 24px;
            padding: 6px 14px 6px 18px;
            border: 1px solid var(--border-color);
        }

        .input-form input {
            flex: 1;
            border: none;
            background: transparent;
            color: var(--text-color);
            font-size: 14px;
            outline: none;
            padding: 6px 0;
        }

        .send-btn {
            background-color: #0095f6;
            color: white;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            flex-shrink: 0;
        }

        .send-btn:hover {
            background-color: #007bb5;
            transform: scale(1.05);
        }

        .send-btn svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        @media (max-width: 900px) {
            .sidebar {
                width: 70px;
                padding: 25px 8px;
            }
            .sidebar .logo {
                font-size: 18px;
                padding-left: 0;
                text-align: center;
            }
            .nav-item span:last-child {
                display: none;
            }
            .chat-layout-wrapper {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
            .conversations-sidebar {
                width: 280px;
            }
        }
    </style>
</head>

<body>

    <!-- 1. Left Navigation Sidebar -->
    <nav class="sidebar">
        <div class="logo">Nexagram</div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php">🏠 <span>ホーム (Home)</span></a></li>
            <li class="nav-item"><a href="explore.php">🔍 <span>検索 (Search)</span></a></li>
            <li class="nav-item active"><strong><a href="messages.php">✉️ <span>メッセージ (Messages)</span></a></strong></li>
            <li class="nav-item"><a href="create_post.php">➕ <span>作成 (Create Post)</span></a></li>
            <li class="nav-item"><a href="profile.php">👤 <span>プロフィール (Profile)</span></a></li>

            <li class="nav-item theme-toggle-btn" id="theme-toggle" style="margin-top: auto;">
                <span id="theme-icon">🌙</span> <span id="theme-text">ダークモード</span>
            </li>
            <li class="nav-item logout-btn" style="margin-top: 0;"><a href="logout.php" style="color: #ed4956;">🚪 <span>ログアウト</span></a></li>
        </ul>
    </nav>

    <!-- 2. Real-Time Chat Layout Wrapper -->
    <div class="chat-layout-wrapper">

        <!-- Conversations List Sidebar -->
        <div class="conversations-sidebar">
            <div class="conversations-header">
                <h2>メッセージ <span style="font-size: 14px; color: var(--text-secondary); font-weight: normal;"><?php echo htmlspecialchars($current_username); ?></span></h2>
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="conv-search-input" placeholder="チャット相手を検索... (Search...)">
                </div>
            </div>
            <div class="conversations-list" id="conversations-list-container">
                <!-- Dynamically loaded via AJAX -->
                <div style="padding: 20px; text-align: center; color: var(--text-secondary);">読み込み中...</div>
            </div>
        </div>

        <!-- Main Chat Panel -->
        <div class="chat-main-panel" id="chat-main-panel">
            <?php if ($active_user_id <= 0): ?>
                <!-- Empty State when no conversation selected -->
                <div class="chat-empty-state" id="chat-empty-state">
                    <div class="chat-empty-icon">✉️</div>
                    <div class="chat-empty-title">メッセージを送信</div>
                    <div class="chat-empty-sub">友達や他のユーザーを選んで、リアルタイムチャットを開始しましょう。</div>
                </div>
            <?php else: ?>
                <!-- Active Chat view container -->
                <div id="active-chat-container" style="display: flex; flex-direction: column; height: 100%;">
                    <!-- Header -->
                    <div class="chat-header">
                        <a href="profile.php?id=<?php echo $active_user_id; ?>" class="chat-user-profile" id="chat-user-link">
                            <img src="default.png" class="chat-header-avatar" id="chat-header-avatar" alt="User Avatar">
                            <div class="chat-header-info">
                                <span class="chat-header-name" id="chat-header-name">ユーザー...</span>
                                <span class="chat-header-status"><span class="status-dot"></span> オンライン</span>
                            </div>
                        </a>
                        <a href="profile.php?id=<?php echo $active_user_id; ?>" class="view-profile-btn" id="chat-header-profile-btn">プロフィールを見る</a>
                    </div>

                    <!-- Messages list -->
                    <div class="messages-container" id="messages-container">
                        <div style="text-align: center; color: var(--text-secondary); padding: 20px;">メッセージを読み込み中...</div>
                    </div>

                    <!-- Input Footer -->
                    <div class="chat-input-area">
                        <div class="quick-emoji-bar">
                            <button class="emoji-btn" onclick="insertEmoji('❤️')">❤️</button>
                            <button class="emoji-btn" onclick="insertEmoji('😂')">😂</button>
                            <button class="emoji-btn" onclick="insertEmoji('👍')">👍</button>
                            <button class="emoji-btn" onclick="insertEmoji('🔥')">🔥</button>
                            <button class="emoji-btn" onclick="insertEmoji('🎉')">🎉</button>
                            <button class="emoji-btn" onclick="insertEmoji('😍')">😍</button>
                        </div>
                        <form class="input-form" id="chat-message-form" onsubmit="handleSendMessage(event)">
                            <input type="text" id="chat-message-input" placeholder="メッセージを入力... (Type a message...)" autocomplete="off">
                            <button type="submit" class="send-btn" title="送信">
                                <svg viewBox="0 0 24 24">
                                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Theme Toggle & Real-Time Chat JavaScript Engine -->
    <script>
        // Dark Mode Controller (Matches Nexagram index.php)
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

        // Real-Time Chat Engine Variables
        let activeUserId = <?php echo $active_user_id; ?>;
        let lastMessageId = 0;
        let pollingInterval = null;
        let allConversations = [];
        let isUserScrolledUp = false;

        // Initialize Application
        document.addEventListener('DOMContentLoaded', () => {
            loadConversations();

            if (activeUserId > 0) {
                fetchChatHistory(activeUserId, false);
            }

            // Start polling engine every 2 seconds
            pollingInterval = setInterval(() => {
                loadConversations();
                if (activeUserId > 0) {
                    fetchChatHistory(activeUserId, true);
                }
            }, 2000);

            // Filter search conversations input
            document.getElementById('conv-search-input').addEventListener('input', (e) => {
                filterConversations(e.target.value.trim().toLowerCase());
            });

            // Track scroll position to prevent forced scrolling when reading history
            const msgContainer = document.getElementById('messages-container');
            if (msgContainer) {
                msgContainer.addEventListener('scroll', () => {
                    const threshold = 50;
                    isUserScrolledUp = msgContainer.scrollHeight - msgContainer.scrollTop - msgContainer.clientHeight > threshold;
                });
            }
        });

        // 1. Fetch Conversations List
        function loadConversations() {
            fetch('fetch_conversations.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        allConversations = data.conversations;
                        const searchTerm = document.getElementById('conv-search-input').value.trim().toLowerCase();
                        filterConversations(searchTerm);
                    }
                })
                .catch(err => console.error('Error fetching conversations:', err));
        }

        function filterConversations(term) {
            const container = document.getElementById('conversations-list-container');
            let filtered = allConversations;

            if (term !== '') {
                filtered = allConversations.filter(c => c.username.toLowerCase().includes(term));
            }

            if (filtered.length === 0) {
                container.innerHTML = `<div style="padding:20px; text-align:center; color:var(--text-secondary);">ユーザーが見つかりません。</div>`;
                return;
            }

            let html = '';
            filtered.forEach(c => {
                const isActive = (c.id == activeUserId) ? 'active' : '';
                const isUnread = (c.unread_count > 0) ? 'unread' : '';
                const unreadBadge = (c.unread_count > 0) ? `<div class="unread-badge">${c.unread_count}</div>` : '';

                let avatarHtml = `<img src="${escapeHtml(c.avatar_url)}" class="conv-avatar" alt="Avatar">`;
                if (!c.avatar_url || c.avatar_url === 'default.png') {
                    const initial = c.username.charAt(0).toUpperCase();
                    avatarHtml = `<div class="conv-avatar-placeholder">${initial}</div>`;
                }

                html += `
                    <div class="conv-item ${isActive} ${isUnread}" onclick="selectUser(${c.id})">
                        <div class="conv-avatar-wrapper">
                            ${avatarHtml}
                        </div>
                        <div class="conv-info">
                            <div class="conv-top-row">
                                <span class="conv-username">${escapeHtml(c.username)}</span>
                                <span class="conv-time">${c.formatted_time}</span>
                            </div>
                            <div class="conv-bottom-row">
                                <span class="conv-snippet">${escapeHtml(c.preview_snippet)}</span>
                                ${unreadBadge}
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // 2. Select User to start/switch chat
        function selectUser(userId) {
            if (activeUserId === userId) return;
            activeUserId = userId;
            lastMessageId = 0;
            isUserScrolledUp = false;

            // Update URL without page reload
            window.history.pushState({}, '', `messages.php?user_id=${userId}`);

            // If main panel had empty state, replace with chat container dynamically
            const mainPanel = document.getElementById('chat-main-panel');
            if (document.getElementById('chat-empty-state')) {
                mainPanel.innerHTML = `
                    <div id="active-chat-container" style="display: flex; flex-direction: column; height: 100%;">
                        <div class="chat-header">
                            <a href="profile.php?id=${userId}" class="chat-user-profile" id="chat-user-link">
                                <img src="default.png" class="chat-header-avatar" id="chat-header-avatar" alt="Avatar">
                                <div class="chat-header-info">
                                    <span class="chat-header-name" id="chat-header-name">読み込み中...</span>
                                    <span class="chat-header-status"><span class="status-dot"></span> オンライン</span>
                                </div>
                            </a>
                            <a href="profile.php?id=${userId}" class="view-profile-btn" id="chat-header-profile-btn">プロフィールを見る</a>
                        </div>
                        <div class="messages-container" id="messages-container">
                            <div style="text-align: center; color: var(--text-secondary); padding: 20px;">メッセージを読み込み中...</div>
                        </div>
                        <div class="chat-input-area">
                            <div class="quick-emoji-bar">
                                <button class="emoji-btn" onclick="insertEmoji('❤️')">❤️</button>
                                <button class="emoji-btn" onclick="insertEmoji('😂')">😂</button>
                                <button class="emoji-btn" onclick="insertEmoji('👍')">👍</button>
                                <button class="emoji-btn" onclick="insertEmoji('🔥')">🔥</button>
                                <button class="emoji-btn" onclick="insertEmoji('🎉')">🎉</button>
                                <button class="emoji-btn" onclick="insertEmoji('😍')">😍</button>
                            </div>
                            <form class="input-form" id="chat-message-form" onsubmit="handleSendMessage(event)">
                                <input type="text" id="chat-message-input" placeholder="メッセージを入力... (Type a message...)" autocomplete="off">
                                <button type="submit" class="send-btn" title="送信">
                                    <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                `;
            }

            fetchChatHistory(userId, false);
            loadConversations();
        }

        // 3. Fetch Messages History or New Messages
        function fetchChatHistory(userId, isPolling = false) {
            const url = isPolling && lastMessageId > 0 
                ? `fetch_messages.php?receiver_id=${userId}&last_id=${lastMessageId}` 
                : `fetch_messages.php?receiver_id=${userId}`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) return;

                    // Update Chat Header Receiver Details
                    const receiver = data.receiver;
                    const avatarImg = document.getElementById('chat-header-avatar');
                    const nameSpan = document.getElementById('chat-header-name');
                    const profileBtn = document.getElementById('chat-header-profile-btn');
                    const userLink = document.getElementById('chat-user-link');

                    if (nameSpan) nameSpan.textContent = receiver.username;
                    if (avatarImg && receiver.avatar_url) avatarImg.src = receiver.avatar_url;
                    if (profileBtn) profileBtn.href = `profile.php?id=${receiver.id}`;
                    if (userLink) userLink.href = `profile.php?id=${receiver.id}`;

                    const container = document.getElementById('messages-container');
                    if (!container) return;

                    if (!isPolling || lastMessageId === 0) {
                        // Full rebuild of message container
                        container.innerHTML = '';
                        if (data.messages.length === 0) {
                            container.innerHTML = `
                                <div style="text-align: center; margin: auto; color: var(--text-secondary);">
                                    <div style="font-size: 40px; margin-bottom: 10px;">👋</div>
                                    <div style="font-weight: 600;">${escapeHtml(receiver.username)} さんにあいさつしましょう</div>
                                    <div style="font-size: 13px; margin-top: 4px;">メッセージを送信して会話を開始します</div>
                                </div>
                            `;
                        } else {
                            let lastDate = '';
                            data.messages.forEach(msg => {
                                if (msg.formatted_date !== lastDate) {
                                    lastDate = msg.formatted_date;
                                    container.appendChild(createDateDivider(lastDate));
                                }
                                container.appendChild(createMessageRow(msg, data.current_user_id, receiver));
                                lastMessageId = Math.max(lastMessageId, parseInt(msg.id));
                            });
                        }
                        scrollToBottom();
                    } else if (data.messages.length > 0) {
                        // Append new incremental messages
                        data.messages.forEach(msg => {
                            container.appendChild(createMessageRow(msg, data.current_user_id, receiver));
                            lastMessageId = Math.max(lastMessageId, parseInt(msg.id));
                        });

                        if (!isUserScrolledUp) {
                            scrollToBottom();
                        }
                    }
                })
                .catch(err => console.error('Error fetching messages:', err));
        }

        // DOM Message Helpers
        function createDateDivider(dateStr) {
            const div = document.createElement('div');
            div.className = 'date-divider';
            div.innerHTML = `<span>${escapeHtml(dateStr)}</span>`;
            return div;
        }

        function createMessageRow(msg, currentUserId, receiver) {
            const isSent = (msg.sender_id == currentUserId);
            const row = document.createElement('div');
            row.className = `msg-row ${isSent ? 'sent' : 'received'}`;
            row.id = `msg-${msg.id}`;

            let avatarHtml = '';
            if (!isSent) {
                const avatarSrc = receiver.avatar_url || 'default.png';
                avatarHtml = `<img src="${escapeHtml(avatarSrc)}" class="msg-avatar" alt="Avatar">`;
            }

            let statusHtml = '';
            if (isSent) {
                statusHtml = msg.is_read == 1 
                    ? `<span class="read-status" title="既読">✓✓ 既読</span>` 
                    : `<span style="color: var(--text-secondary);" title="送信済み">✓</span>`;
            }

            row.innerHTML = `
                ${avatarHtml}
                <div class="msg-bubble-wrapper">
                    <div class="msg-bubble">${escapeHtml(msg.message_text).replace(/\n/g, '<br>')}</div>
                    <div class="msg-meta">
                        <span>${msg.formatted_time}</span>
                        ${statusHtml}
                    </div>
                </div>
            `;
            return row;
        }

        // 4. Handle Send Message Action
        function handleSendMessage(event) {
            event.preventDefault();
            const input = document.getElementById('chat-message-input');
            const messageText = input.value.trim();

            if (messageText === '' || activeUserId <= 0) return;

            // Clear input immediately for optimal smooth UX
            input.value = '';

            const formData = new FormData();
            formData.append('receiver_id', activeUserId);
            formData.append('message_text', messageText);

            fetch('send_message.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    fetchChatHistory(activeUserId, true);
                    loadConversations();
                    isUserScrolledUp = false;
                    scrollToBottom();
                } else {
                    alert('メッセージの送信に失敗しました: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error('Error sending message:', err);
                alert('エラーが発生しました。ネットワークを確認してください。');
            });
        }

        function insertEmoji(emoji) {
            const input = document.getElementById('chat-message-input');
            if (input) {
                input.value += emoji;
                input.focus();
            }
        }

        function scrollToBottom() {
            const container = document.getElementById('messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    </script>
</body>

</html>
