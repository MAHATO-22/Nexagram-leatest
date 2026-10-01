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
    <link rel="stylesheet" href="css/messages.css">
</head>

<body>

    <!-- 1. Left Navigation Sidebar -->
    <nav class="sidebar">
        <a href="index.php" class="logo" aria-label="Nexagram - ホーム">Nexagram</a>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php">🏠 <span>ホーム</span></a></li>
            <li class="nav-item"><a href="explore.php">🔍 <span>検索</span></a></li>
            <li class="nav-item active"><strong><a href="messages.php">✉️ <span>メッセージ</span></a></strong></li>
            <li class="nav-item"><a href="create_post.php">➕ <span>作成</span></a></li>
            <li class="nav-item"><a href="profile.php">👤 <span>プロフィール</span></a></li>

            <li class="nav-item theme-toggle-btn" id="theme-toggle" style="margin-top: auto;">
                <span id="theme-icon">🌙</span> <span id="theme-text">ダークモード</span>
            </li>
            <li class="nav-item logout-btn" style="margin-top: 0;"><a href="php/logout.php" style="color: #ed4956;">🚪 <span>ログアウト</span></a></li>
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
                            <button class="emoji-btn" onclick="insertEmoji('🥰')">🥰</button>
                            <button class="emoji-btn" onclick="insertEmoji('😊')">😊</button>
                            <button class="emoji-btn" onclick="insertEmoji('😘')">😘</button>
                            <button class="emoji-btn" onclick="insertEmoji('🤣')">🤣</button>
                            <button class="emoji-btn" onclick="insertEmoji('😅')">😅</button>
                            <button class="emoji-btn" onclick="insertEmoji('😭')">😭</button>
                            <button class="emoji-btn" onclick="insertEmoji('🤔')">🤔</button>
                            <button class="emoji-btn" onclick="insertEmoji('👏')">👏</button>
                            <button class="emoji-btn" onclick="insertEmoji('💯')">💯</button>
                            <button class="emoji-btn" onclick="insertEmoji('🙏')">🙏</button>
                            <button class="emoji-btn" onclick="insertEmoji('✨')">✨</button>
                            <button class="emoji-btn" onclick="insertEmoji('🫶')">🫶</button>
                            <button class="emoji-btn" onclick="insertEmoji('🥳')">🥳</button>
                            <button class="emoji-btn" onclick="insertEmoji('😎')">😎</button>
                            <button class="emoji-btn" onclick="insertEmoji('😴')">😴</button>
                            <button class="emoji-btn" onclick="insertEmoji('💔')">💔</button>
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
            fetch('php/fetch_conversations.php')
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
                                <button class="emoji-btn" onclick="insertEmoji('🥰')">🥰</button>
                                <button class="emoji-btn" onclick="insertEmoji('😊')">😊</button>
                                <button class="emoji-btn" onclick="insertEmoji('😘')">😘</button>
                                <button class="emoji-btn" onclick="insertEmoji('🤣')">🤣</button>
                                <button class="emoji-btn" onclick="insertEmoji('😅')">😅</button>
                                <button class="emoji-btn" onclick="insertEmoji('😭')">😭</button>
                                <button class="emoji-btn" onclick="insertEmoji('🤔')">🤔</button>
                                <button class="emoji-btn" onclick="insertEmoji('👏')">👏</button>
                                <button class="emoji-btn" onclick="insertEmoji('💯')">💯</button>
                                <button class="emoji-btn" onclick="insertEmoji('🙏')">🙏</button>
                                <button class="emoji-btn" onclick="insertEmoji('✨')">✨</button>
                                <button class="emoji-btn" onclick="insertEmoji('🫶')">🫶</button>
                                <button class="emoji-btn" onclick="insertEmoji('🥳')">🥳</button>
                                <button class="emoji-btn" onclick="insertEmoji('😎')">😎</button>
                                <button class="emoji-btn" onclick="insertEmoji('😴')">😴</button>
                                <button class="emoji-btn" onclick="insertEmoji('💔')">💔</button>
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
                ? `php/fetch_messages.php?receiver_id=${userId}&last_id=${lastMessageId}` 
                : `php/fetch_messages.php?receiver_id=${userId}`;

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

            fetch('php/send_message.php', {
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
