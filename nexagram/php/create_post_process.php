<?php
// create_post_process.php - Nexagram Create Post Handler
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $caption = trim($_POST['caption']);
    
    $image_destination = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];
        $file_error = $file['error'];

        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm', 'mov', 'avi'];

        if (in_array($file_ext, $allowed_ext)) {
            if ($file_error === 0) {
                if ($file_size < 50000000) {
                    $new_file_name = uniqid('POST_', true) . '.' . $file_ext;
                    $image_destination = '../uploads/' . $new_file_name;

                    if (!move_uploaded_file($file_tmp, $image_destination)) {
                        die("ファイルのアップロードに失敗しました。(Failed to move uploaded file.)");
                    }
                    // Store relative path from nexagram/ root
                    $image_destination = 'uploads/' . $new_file_name;
                } else {
                    die("ファイルサイズが大きすぎます。(File is too large. Max 50MB)");
                }
            } else {
                die("アップロード中にエラーが発生しました。(Error uploading file.)");
            }
        } else {
            die("許可されていないファイル形式です。(Invalid file type.)");
        }
    }

    if (empty($caption) && empty($image_destination)) {
        header("Location: ../create_post.php");
        exit;
    }

    try {
        $sql = "INSERT INTO posts (user_id, caption, image_path) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $caption, $image_destination]);

        echo "<script>
            alert('投稿がシェアされました！ (Post shared successfully!)');
            window.location.href = '../index.php';
        </script>";
        exit;
    } catch (PDOException $e) {
        die("エラー (Database Error): " . $e->getMessage());
    }
} else {
    header("Location: ../create_post.php");
    exit;
}
?>
