<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$success = false;

// လက်ရှိ User အချက်အလက်ယူမည်
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username    = trim($_POST['username']);
    $new_school_name = !empty($_POST['school_name']) ? trim($_POST['school_name']) : 'YSE College';
    $profile_image_path = $user['profile_image']; // မူလ ပုံလမ်းကြောင်း ထိန်းထားမည်

    // 1. Profile Picture Update ပြုလုပ်ခြင်း
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_image']['tmp_name'];
        $fileName = $_FILES['profile_image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadFileDir = 'uploads/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $newFileName = 'avatar_' . $user_id . '_' . time() . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
                    unlink($user['profile_image']);
                }
                $profile_image_path = $dest_path;
            } else {
                $message = 'ပုံတင်ရာတွင် အမှားအယွင်း ရှိနေပါသည်။';
            }
        } else {
            $message = 'JPG, JPEG, PNG, GIF, WEBP ဖိုင်များကိုသာ လက်ခံပါသည်။';
        }
    }

    // 2. Database တွင် အချက်အလက်များ Update လုပ်မည်
    if (empty($message)) {
        if (!empty($new_username)) {
            $update_stmt = $pdo->prepare("UPDATE users SET username = ?, school_name = ?, profile_image = ? WHERE id = ?");
            if ($update_stmt->execute([$new_username, $new_school_name, $profile_image_path, $user_id])) {
                $_SESSION['username'] = $new_username;
                header("Location: profile.php");
                exit();
            } else {
                $message = 'Database Update ပြုလုပ်ခြင်း မအောင်မြင်ပါ။';
            }
        } else {
            $message = 'ကျေးဇူးပြု၍ Username ထည့်ပေးပါ။';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile • Nexagram</title>
    <link rel="stylesheet" href="css/edit_profile.css">
</head>

<body>

    <div class="edit-box">
        <h3>Edit Profile</h3>

        <?php if ($message): ?>
            <div class="error-msg"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Form မှာ ပုံတင်နိုင်ရန် enctype="multipart/form-data" မဖြစ်မနေ ပါရပါမည် -->
        <form method="POST" enctype="multipart/form-data">
            <div class="avatar-preview-container">
                <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" id="imgPreview" class="avatar-preview">
                <?php else: ?>
                    <div class="avatar-preview" id="imgPreviewText"><?php echo mb_substr(htmlspecialchars($user['username']), 0, 1, 'UTF-8'); ?></div>
                    <img src="" id="imgPreview" class="avatar-preview" style="display:none;">
                <?php endif; ?>

                <label for="profile_image" class="file-input-label">Change profile photo</label>
                <input type="file" name="profile_image" id="profile_image" accept="image/*" onchange="previewImage(event)">
            </div>

            <div class="input-group">
                <label>Username (ユーザー名)</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>

            <div class="input-group">
                <label>School / College (学校名・大学名)</label>
                <input type="text" name="school_name" value="<?php echo htmlspecialchars($user['school_name'] ?? 'YSE College'); ?>" required>
            </div>

            <button type="submit" class="save-btn">Save Changes</button>
            <a href="profile.php" class="cancel-btn">Cancel</a>
        </form>
    </div>

    <script>
        // ပုံရွေးလိုက်တာနဲ့ တန်းပြီး Preview ပြပေးမည့် JavaScript
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('imgPreview');
                const textPreview = document.getElementById('imgPreviewText');

                output.src = reader.result;
                output.style.display = 'block';
                if (textPreview) {
                    textPreview.style.display = 'none';
                }
            };
            if (event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            }
        }
    </script>

</body>

</html>
