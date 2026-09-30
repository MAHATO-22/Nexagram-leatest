<?php
// register_process.php - Nexagram User Registration Handling
session_start();
require_once __DIR__ . '/../config.php';

/**
 * Send the user back to the register page with an explanatory message,
 * keeping whatever they already typed so they don't have to start over.
 */
function back_to_register(array $messages, array $old_input): void
{
    $_SESSION['register_error'] = $messages;
    $_SESSION['register_old']   = $old_input;
    header('Location: ../register.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit;
}

$student_id  = trim($_POST['student_id'] ?? '');
$username    = trim($_POST['username'] ?? '');
$school_name = !empty($_POST['school_name']) ? trim($_POST['school_name']) : 'YSE College';
$email       = trim($_POST['email'] ?? '');
$password    = $_POST['password'] ?? '';

$old_input = [
    'student_id'  => $student_id,
    'username'    => $username,
    'school_name' => $school_name,
    'email'       => $email,
    'department'  => trim($_POST['department'] ?? ''),
];

if ($student_id === '' || $username === '' || $email === '' || $password === '') {
    back_to_register(['全ての項目を入力してください。(All fields are required.)'], $old_input);
}

// Guard against "Data too long" errors (the columns are varchar(50/100)),
// which would otherwise abort the sign-up with a raw SQL message.
$too_long = [];
if (mb_strlen($student_id) > 50) {
    $too_long[] = '学籍番号が長すぎます。(Student ID must be 50 characters or fewer.)';
}
if (mb_strlen($username) > 50) {
    $too_long[] = 'ユーザー名が長すぎます。(Username must be 50 characters or fewer.)';
}
if (mb_strlen($email) > 100) {
    $too_long[] = 'メールアドレスが長すぎます。(Email must be 100 characters or fewer.)';
}
if ($too_long) {
    back_to_register($too_long, $old_input);
}

try {
    // A single OR-ed scan of users, where the CASE reports the *actual* column
    // that matched on the offending row (student_id checked first so the message
    // points at the most specific field). The collation is already
    // case-insensitive (_ai_ci), so this catches case variants such as
    // "User@yse-c.net" vs "user@yse-c.net" too.
    $dup_stmt = $pdo->prepare(
        "SELECT CASE
                    WHEN student_id = ? THEN 'student_id'
                    WHEN username   = ? THEN 'username'
                    WHEN email      = ? THEN 'email'
                END AS field
         FROM users
         WHERE student_id = ? OR username = ? OR email = ?
         LIMIT 1"
    );
    $dup_stmt->execute([$student_id, $username, $email, $student_id, $username, $email]);
    $taken = $dup_stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($taken) {
        $messages = [
            'student_id' => 'この学籍番号は既に登録されています。(This Student ID is already registered.)',
            'username'   => 'このユーザー名は既に使用されています。(This Username is already taken.)',
            'email'      => 'このメールアドレスは既に登録されています。(This Email is already registered.)',
        ];
        back_to_register(array_map(fn ($field) => $messages[$field], $taken), $old_input);
    }

    // Case-sensitivity safety net: if the collation were ever made
    // case-sensitive, the UNIQUE index would still reject the INSERT below, so
    // normalizing both sides keeps the friendly message instead of raw SQL.
    $dup_case = $pdo->prepare(
        "SELECT 1 FROM users
         WHERE LOWER(student_id) = LOWER(?) OR LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?)
         LIMIT 1"
    );
    $dup_case->execute([$student_id, $username, $email]);

    if ($dup_case->fetchColumn() !== false) {
        back_to_register(
            ['この学籍番号、ユーザー名、またはメールアドレスは既に登録されています。(Student ID, Username, or Email already exists.)'],
            $old_input
        );
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $insert_stmt = $pdo->prepare(
        "INSERT INTO users (student_id, username, school_name, email, password) VALUES (?, ?, ?, ?, ?)"
    );
    $insert_stmt->execute([$student_id, $username, $school_name, $email, $hashed_password]);

    // Registration succeeded: the form should start empty next time.
    unset($_SESSION['register_error'], $_SESSION['register_old']);

    echo "<script>
            alert('アカウント登録が完了しました！ログインしてください。 (Registration successful!)');
            window.location.href = '../login.php';
          </script>";
    exit;

} catch (\PDOException $e) {
    $error_info = $e->errorInfo;
    $sqlstate = is_array($error_info) && !empty($error_info[0]) ? (string) $error_info[0] : (string) $e->getCode();
    $errno    = (int) ($error_info[1] ?? 0);

    // 23000 / 1062 == duplicate key: two sign-ups raced on the same unique value
    // between our SELECT and INSERT. Show the friendly message instead of raw SQL.
    // (HY093 is how some drivers report a duplicate on a prepared INSERT.)
    if ($sqlstate === '23000' || $errno === 1062 || $sqlstate === 'HY093') {
        $hint = null;

        // Tell the user *which* value collided, if the driver gives us the key name.
        if (is_array($error_info) && !empty($error_info[2]) && preg_match('/for key \'?(\w+)\'?/i', (string) $error_info[2], $m)) {
            $map = [
                'student_id' => 'この学籍番号は既に登録されています。(This Student ID is already registered.)',
                'username'   => 'このユーザー名は既に使用されています。(This Username is already taken.)',
                'email'      => 'このメールアドレスは既に登録されています。(This Email is already registered.)',
            ];
            $hint = $map[$m[1]] ?? null;
        }

        back_to_register(
            $hint
                ? [$hint]
                : ['この学籍番号、ユーザー名、またはメールアドレスは既に登録されています。(Student ID, Username, or Email already exists.)'],
            $old_input
        );
    }

    die("エラーが発生しました (Error): " . $e->getMessage());
}
