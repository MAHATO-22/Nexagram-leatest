<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $bio = trim($_POST['bio']);
    
    try {
        // 1. အရင်ဆုံး လက်ရှိ Profile ပုံအဟောင်း ရှိမရှိ ဒေတာဘေ့စ်ကနေ စစ်မယ်
        $stmt = $pdo->prepare("SELECT profile_pic, profile_image FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_user = $stmt->fetch();

        // index.php / profile.php တွေက profile_image ကို အသုံးပြုသောကြောင့် ဒီ column ကို အခြေခံမည်
        $image_destination = $current_user['profile_image'];

        // 2. တကယ်လို့ ဓာတ်ပုံအသစ် ရွေးပြီး တင်လာခဲ့ရင်
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
                    $image_destination = 'uploads/' . $new_file_name;

                    if (move_uploaded_file($file_tmp, $image_destination)) {
                        // ပုံအဟောင်း server ပေါ်မှာ ရှိနေရင် ဖျက်ပစ်မည် (default.png မဖျက်ရ)
                        $old_pic = $current_user['profile_image'];
                        if (!empty($old_pic) && $old_pic !== 'default.png' && file_exists($old_pic)) {
                            unlink($old_pic);
                        }
                    } else {
                        $image_destination = $current_user['profile_image'];
                    }
                } else {
                    die("ファイルサイズが大きすぎます。(File is too large. Max 3MB)");
                }
            } else {
                die("許可されていないファイル形式です。(Invalid file type.)");
            }
        }

        // 3. Database ထဲမှာ အချက်အလက်တွေကို UPDATE လုပ်မယ်
        // profile_pic နဲ့ profile_image နှစ်ခုလုံးကို တပြိုင်နက်တည်း update လုပ်မည်
        $update_sql = "UPDATE users SET bio = ?, profile_pic = ?, profile_image = ? WHERE id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$bio, $image_destination, $image_destination, $user_id]);

        echo "<script>
                alert('プロフィールが更新されました！');
                window.location.href = 'profile.php';
              </script>";
        exit;

    } catch (\PDOException $e) {
        die("エラー: " . $e->getMessage());
    }
} else {
    header("Location: edit_profile.php");
    exit;
}
?>