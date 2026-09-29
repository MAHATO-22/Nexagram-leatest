<?php
// register_process.php - Nexagram User Registration Handling
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id  = trim($_POST['student_id']);
    $username    = trim($_POST['username']);
    $school_name = !empty($_POST['school_name']) ? trim($_POST['school_name']) : 'YSE College';
    $email       = trim($_POST['email']);
    $password    = $_POST['password'];

    if (empty($student_id) || empty($username) || empty($email) || empty($password)) {
        die("全ての項目を入力してください。(All fields are required.)");
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE student_id = ? OR username = ? OR email = ?");
        $stmt->execute([$student_id, $username, $email]);
        
        if ($stmt->rowCount() > 0) {
            die("この学籍番号、ユーザー名、またはメールアドレスは既に登録されています。<br>(Student ID, Username, or Email already exists.)");
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (student_id, username, school_name, email, password) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $pdo->prepare($sql);
        $insert_stmt->execute([$student_id, $username, $school_name, $email, $hashed_password]);

        echo "<script>
                alert('アカウント登録が完了しました！ログインしてください。 (Registration successful!)');
                window.location.href = '../login.php';
              </script>";
        exit;

    } catch (\PDOException $e) {
        die("エラーが発生しました (Error): " . $e->getMessage());
    }
} else {
    header("Location: ../register.php");
    exit;
}
?>
