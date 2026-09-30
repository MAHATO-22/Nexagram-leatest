<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexagram - 新規投稿</title>
    <link rel="stylesheet" href="css/create_post.css">
</head>

<body>

    <!-- Left Sidebar Navigation -->
    <nav class="sidebar">
        <a href="index.php" class="logo" aria-label="Nexagram - ホーム">Nexagram</a>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php">🏠 ホーム</a></li>
            <li class="nav-item"><a href="explore.php">🔍 検索</a></li>
            <li class="nav-item"><a href="messages.php">✉️ メッセージ</a></li>
            <li class="nav-item"><strong><a href="create_post.php">➕ 作成</a></strong></li>
            <li class="nav-item"><a href="profile.php">👤 プロフィール</a></li>
            <li class="nav-item logout-btn"><a href="php/logout.php" style="color: #ed4956;">🚪 ログアウト</a></li>
        </ul>
    </nav>

    <!-- Main Form Content Area -->
    <main class="main-content">
        <div class="container">
            <div class="title">新規投稿を作成 (Create New Post)</div>

            <form action="php/create_post_process.php" method="POST" enctype="multipart/form-data">
                <div class="input-group">
                    <label for="image">写真・動画を選択 (Select Image/Video)</label>
                    <input type="file" name="image" id="image" accept="image/*,video/*">
                </div>
                <div class="input-group">
                    <label for="caption">キャプション (Caption)</label>
                    <textarea name="caption" id="caption" placeholder="キャプションを書く... (Write a caption...)"></textarea>
                </div>
                <button type="submit" class="btn-submit">シェアする (Share)</button>
            </form>

            <a href="index.php" class="back-link">キャンセル (Cancel)</a>
        </div>
    </main>

</body>

</html>