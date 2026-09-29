<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexagram - Register</title>
    <link rel="stylesheet" href="css/register.css">
</head>
<body>

<div class="container">
    <div class="logo">Nexagram</div>
    <div class="subtitle">キャンパスの仲間とつながり、最新の学内情報をシェアしよう。</div>
    
    <!-- データを php/register_process.php に送信します -->
    <form action="php/register_process.php" method="POST">
        <div class="input-group">
            <input type="text" name="student_id" placeholder="Student ID (学籍番号)" required>
        </div>
        <div class="input-group">
            <input type="text" name="username" placeholder="Username (ユーザー名)" required>
        </div>
        <div class="input-group">
            <input type="text" name="school_name" placeholder="School / College (学校名・大学名)" value="YSE College" required>
        </div>
        <div class="input-group">
            <input type="email" name="email" placeholder="School Email (学校のメールアドレス)" required>
        </div>
        <div class="input-group">
            <input type="password" name="password" placeholder="Password (パスワード)" required>
        </div>
        <button type="submit" class="btn-submit">Sign up</button>
    </form>

    <div class="footer-text">
        アカウントをお持ちですか？ <a href="login.php">Log in</a>
    </div>
</div>

</body>
</html>