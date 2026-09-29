<?php
// login_process.php - Nexagram User Login Handling
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_email = trim($_POST['username_email']);
    $password       = $_POST['password'];

    if (empty($username_email) || empty($password)) {
        die("全ての項目を入力してください。(All fields are required.)");
    }

    try {
        // Username またはEmail で探す
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username_email, $username_email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];

            header("Location: ../index.php");
            exit;
        } else {
            die("ユーザー名、メールアドレス、またはパスワードが間違っています。<br>(Incorrect username/email or password.)");
        }

    } catch (\PDOException $e) {
        die("エラーが発生しました (Error): " . $e->getMessage());
    }
} else {
    header("Location: ../login.php");
    exit;
}
?>
