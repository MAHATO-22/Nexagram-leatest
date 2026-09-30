<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexagram - ログイン</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

<div class="container">
    <a href="index.php" class="logo" aria-label="Nexagram - Home">Nexagram</a>
    
    <!-- データを php/login_process.php に送信します -->
    <form action="php/login_process.php" method="POST">
        <div class="input-group">
            <input type="text" name="username_email" placeholder="Username / Email (ユーザー名またはメール)" required>
        </div>
        <div class="input-group">
            <input type="password" name="password" placeholder="Password (パスワード)" required>
        </div>
        <button type="submit" class="btn-submit">Log in</button>
    </form>

    <div class="footer-text">
        アカウントをお持ちでないですか？ <a href="register.php">Sign up</a>
    </div>
</div>

</body>
</html>