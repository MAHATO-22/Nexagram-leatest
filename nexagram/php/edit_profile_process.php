<?php
// edit_profile_process.php - Nexagram Edit Profile Handler
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $bio = trim($_POST['bio'] ?? '');
    
    try {
        $stmt = $pdo->prepare("SELECT profile_pic, profile_image FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_user = $stmt->fetch();

        $image_destination = $current_user['profile_image'];

        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
            $file = $_FILES['profile_pic'];
            $file_name = $file['name'];
            $file_tmp  = $file['tmp_name'];
            $file_size = $file['size'];
            $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_ext, $allowed_ext)) {
                if ($file_size < 3000000) { // Max 3MB
                    $new_file_name = uniqid('AVATAR_', true) . "." . $file_ext;
                    $dest_path = '../uploads/' . $new_file_name;

                    if (move_uploaded_file($file_tmp, $dest_path)) {
                        $old_pic = $current_user['profile_image'];
                        if (!empty($old_pic) && $old_pic !== 'default.png' && file_exists('../' . $old_pic)) {
                            unlink('../' . $old_pic);
                        }
                        $image_destination = 'uploads/' . $new_file_name;
                    }
                } else {
                    die("ファイルサイズが大きすぎます。(File is too large. Max 3MB)");
                }
            } else {
                die("許可されていないファイル形式です。(Invalid file type.)");
            }
        }

        $update_sql = "UPDATE users SET bio = ?, profile_pic = ?, profile_image = ? WHERE id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$bio, $image_destination, $image_destination, $user_id]);

        echo "<script>
                alert('プロフィールが更新されました！');
                window.location.href = '../profile.php';
              </script>";
        exit;

    } catch (\PDOException $e) {
        die("エラー: " . $e->getMessage());
    }
} else {
    header("Location: ../edit_profile.php");
    exit;
}
?>
