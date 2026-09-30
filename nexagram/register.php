<?php
session_start();

$register_errors = $_SESSION['register_error'] ?? [];
$old             = $_SESSION['register_old'] ?? [];
$old             = array_map('htmlspecialchars', $old);

unset($_SESSION['register_error'], $_SESSION['register_old']);

// Highlight the exact field(s) that caused the error so the user can see
// which input needs changing, not just read about it in the message.
$error_fields = [];
foreach ($register_errors as $error) {
    if (preg_match('/学籍番号|Student ID/i', $error)) {
        $error_fields['student_id'] = true;
    }
    if (preg_match('/ユーザー名|Username/i', $error)) {
        $error_fields['username'] = true;
    }
    if (preg_match('/メールアドレス|Email/i', $error)) {
        $error_fields['email'] = true;
    }
    if (preg_match('/全ての項目|All fields/i', $error)) {
        $error_fields['student_id'] = true;
        $error_fields['username']   = true;
        $error_fields['email']      = true;
        $error_fields['password']   = true;
    }
}
?>

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
    <a href="index.php" class="logo" aria-label="Nexagram - Home">Nexagram</a>
    <div class="subtitle">キャンパスの仲間とつながり、最新の学内情報をシェアしよう。</div>

    <?php if ($register_errors): ?>
        <ul class="error-list">
            <?php foreach ($register_errors as $error): ?>
                <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!-- データを php/register_process.php に送信します -->
    <form action="php/register_process.php" method="POST">
        <div class="input-group<?php echo isset($error_fields['student_id']) ? ' has-error' : ''; ?>">
            <input type="text" name="student_id" placeholder="Student ID (学籍番号)"
                   value="<?php echo $old['student_id'] ?? ''; ?>" required>
        </div>
        <div class="input-group<?php echo isset($error_fields['username']) ? ' has-error' : ''; ?>">
            <input type="text" name="username" placeholder="Username (ユーザー名)"
                   value="<?php echo $old['username'] ?? ''; ?>" required>
        </div>
        <div class="input-group<?php echo isset($error_fields['school_name']) ? ' has-error' : ''; ?>">
            <input type="text" name="school_name" placeholder="School / College (学校名・大学名)"
                   value="<?php echo $old['school_name'] ?? 'YSE College'; ?>" required>
        </div>
        <div class="input-group<?php echo isset($error_fields['email']) ? ' has-error' : ''; ?>">
            <input type="email" name="email" placeholder="School Email (学校のメールアドレス)"
                   value="<?php echo $old['email'] ?? ''; ?>" required>
        </div>
        <div class="input-group<?php echo isset($error_fields['password']) ? ' has-error' : ''; ?>">
            <input type="password" name="password" placeholder="Password (パスワード)" required>
        </div>
        <div class="input-group<?php echo isset($error_fields['department']) ? ' has-error' : ''; ?>">
            <input type="text" name="department" placeholder="Department (学科)"
                   value="<?php echo $old['department'] ?? ''; ?>" required>
        </div>
        <button type="submit" class="btn-submit">Sign up</button>
    </form>

    <div class="footer-text">
        アカウントをお持ちですか？ <a href="login.php">Log in</a>
    </div>
</div>

</body>
</html>
