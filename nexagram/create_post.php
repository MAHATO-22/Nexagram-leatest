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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Arial, sans-serif;
        }

        body {
            background-color: #fafafa;
            color: #000000;
            display: flex;
        }

        /* Sidebar Navigation Design */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 245px;
            border-right: 1px solid #dbdbdb;
            padding: 25px 12px;
            display: flex;
            flex-direction: column;
            background-color: #ffffff;
            z-index: 100;
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
            background-color: #f2f2f2;
        }

        .nav-item a {
            text-decoration: none;
            color: #000000;
            display: block;
            width: 100%;
        }

        .logout-btn {
            margin-top: auto;
            color: #ed4956;
            font-weight: bold;
        }

        /* Main Content Center Area */
        .main-content {
            margin-left: 245px;
            width: calc(100% - 245px);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #fafafa;
        }

        .container {
            background: #ffffff;
            border: 1px solid #dbdbdb;
            width: 450px;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
            color: #262626;
        }

        .input-group {
            margin-bottom: 15px;
        }

        .input-group label {
            display: block;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #262626;
        }

        .input-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px dashed #dbdbdb;
            background: #fafafa;
            border-radius: 4px;
            cursor: pointer;
        }

        .input-group textarea {
            width: 100%;
            height: 120px;
            padding: 10px;
            background: #fafafa;
            border: 1px solid #dbdbdb;
            border-radius: 4px;
            font-size: 14px;
            outline: none;
            resize: none;
        }

        .input-group textarea:focus {
            border-color: #a8a8a8;
        }

        .btn-submit {
            width: 100%;
            background-color: #0095f6;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-submit:hover {
            background-color: #1877f2;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            color: #8e8e8e;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <!-- Left Sidebar Navigation -->
    <nav class="sidebar">
        <div class="logo">Nexagram</div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="index.php">🏠 ホーム (Home)</a></li>
            <li class="nav-item"><a href="explore.php">🔍 検索 (Search)</a></li>
            <li class="nav-item"><a href="messages.php">✉️ メッセージ (Messages)</a></li>
            <li class="nav-item"><strong><a href="create_post.php">➕ 作成 (Create Post)</a></strong></li>
            <li class="nav-item"><a href="profile.php">👤 プロフィール (Profile)</a></li>
            <li class="nav-item logout-btn"><a href="logout.php" style="color: #ed4956;">🚪 ログアウト</a></li>
        </ul>
    </nav>

    <!-- Main Form Content Area -->
    <main class="main-content">
        <div class="container">
            <div class="title">新規投稿を作成 (Create New Post)</div>

            <form action="create_post_process.php" method="POST" enctype="multipart/form-data">
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