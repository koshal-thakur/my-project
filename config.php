<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Kolkata');

$username = "";
$email = "";
$errors = array();

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbPort = (int)(getenv('DB_PORT') ?: 3306);
$dbName = getenv('DB_NAME') ?: 'quiz_competitors';

if (!preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
    $dbName = 'quiz_competitors';
}

$escapedDbName = '`' . str_replace('`', '``', $dbName) . '`';

$conn = mysqli_init();
if (!$conn || !mysqli_real_connect($conn, $dbHost, $dbUser, $dbPass, null, $dbPort)) {
    die('Database server connection failed. Check WAMP MySQL is running and credentials in config.php/ENV are correct. Error: ' . mysqli_connect_error());
}

if (!mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $escapedDbName")) {
    die('Unable to create database: ' . mysqli_error($conn));
}

if (!mysqli_select_db($conn, $dbName)) {
    die('Unable to select database: ' . mysqli_error($conn));
}

mysqli_set_charset($conn, 'utf8mb4');
mysqli_query($conn, "SET time_zone = '+05:30'");

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS user (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL,
        email VARCHAR(191) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS leaderboard (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        score INT NOT NULL DEFAULT 0,
        time INT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

mysqli_query($conn, "
    DELETE l1 FROM leaderboard l1
    INNER JOIN leaderboard l2
        ON l1.username = l2.username
       AND (
            l1.score < l2.score
            OR (l1.score = l2.score AND l1.time > l2.time)
            OR (l1.score = l2.score AND l1.time = l2.time AND l1.id > l2.id)
       )
");

$leaderboardUniqueIndexCheckSql = "
    SELECT COUNT(*) AS total
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = ?
      AND TABLE_NAME = 'leaderboard'
      AND COLUMN_NAME = 'username'
      AND NON_UNIQUE = 0
";
$leaderboardUniqueIndexCheckStmt = mysqli_prepare($conn, $leaderboardUniqueIndexCheckSql);
mysqli_stmt_bind_param($leaderboardUniqueIndexCheckStmt, 's', $dbName);
mysqli_stmt_execute($leaderboardUniqueIndexCheckStmt);
$leaderboardUniqueIndexCheckResult = mysqli_stmt_get_result($leaderboardUniqueIndexCheckStmt);
$leaderboardUniqueIndexCheckRow = mysqli_fetch_assoc($leaderboardUniqueIndexCheckResult);
mysqli_stmt_close($leaderboardUniqueIndexCheckStmt);

if ((int)($leaderboardUniqueIndexCheckRow['total'] ?? 0) === 0) {
    mysqli_query($conn, "ALTER TABLE leaderboard ADD UNIQUE INDEX uq_leaderboard_username (username)");
}

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS quiz_question (
        question_number INT PRIMARY KEY,
        question_text TEXT NOT NULL,
        subject VARCHAR(100) NOT NULL DEFAULT 'General Knowledge',
        a_id INT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$subjectColumnCheckSql = "SELECT COUNT(*) AS total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'quiz_question' AND COLUMN_NAME = 'subject'";
$subjectColumnCheckStmt = mysqli_prepare($conn, $subjectColumnCheckSql);
mysqli_stmt_bind_param($subjectColumnCheckStmt, "s", $dbName);
mysqli_stmt_execute($subjectColumnCheckStmt);
$subjectColumnCheckResult = mysqli_stmt_get_result($subjectColumnCheckStmt);
$subjectColumnCheckRow = mysqli_fetch_assoc($subjectColumnCheckResult);
mysqli_stmt_close($subjectColumnCheckStmt);

if ((int)($subjectColumnCheckRow['total'] ?? 0) === 0) {
    mysqli_query($conn, "ALTER TABLE quiz_question ADD COLUMN subject VARCHAR(100) NOT NULL DEFAULT 'General Knowledge' AFTER question_text");
}

mysqli_query($conn, "UPDATE quiz_question SET subject = 'General Knowledge' WHERE subject IS NULL OR subject = '' OR subject = 'General'");

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS options (
        option_id INT AUTO_INCREMENT PRIMARY KEY,
        question_number INT NOT NULL,
        a_id INT NOT NULL,
        choice VARCHAR(255) NOT NULL,
        FOREIGN KEY (question_number) REFERENCES quiz_question(question_number) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$columnCheckSql = "SELECT COUNT(*) AS total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'options' AND COLUMN_NAME = 'a_id'";
$columnCheckStmt = mysqli_prepare($conn, $columnCheckSql);
mysqli_stmt_bind_param($columnCheckStmt, "s", $dbName);
mysqli_stmt_execute($columnCheckStmt);
$columnCheckResult = mysqli_stmt_get_result($columnCheckStmt);
$columnCheckRow = mysqli_fetch_assoc($columnCheckResult);
mysqli_stmt_close($columnCheckStmt);

if ((int)($columnCheckRow['total'] ?? 0) === 0) {
    mysqli_query($conn, "ALTER TABLE options ADD COLUMN a_id INT NOT NULL DEFAULT 0");
}

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS admin_login (
        id INT AUTO_INCREMENT PRIMARY KEY,
        Admin_Name VARCHAR(100) NOT NULL UNIQUE,
        Admin_Password VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$adminCountResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM admin_login");
if ($adminCountResult) {
    $adminCountRow = mysqli_fetch_assoc($adminCountResult);
    if ((int)$adminCountRow['total'] === 0) {
        mysqli_query($conn, "INSERT INTO admin_login (Admin_Name, Admin_Password) VALUES ('admin', 'admin123')");
    }
}

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        rating TINYINT NOT NULL DEFAULT 5,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL DEFAULT '',
        email VARCHAR(191) NOT NULL,
        phone VARCHAR(30) NOT NULL DEFAULT '',
        message TEXT NOT NULL,
        admin_reply TEXT DEFAULT NULL,
        replied_by VARCHAR(100) DEFAULT NULL,
        replied_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS support_chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        username VARCHAR(100) NOT NULL DEFAULT '',
        email VARCHAR(191) NOT NULL DEFAULT '',
        sender_role ENUM('user','admin') NOT NULL,
        sender_name VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_support_chat_user_id (user_id),
        INDEX idx_support_chat_email (email),
        INDEX idx_support_chat_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS support_chat_typing_status (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL DEFAULT 0,
        email VARCHAR(191) NOT NULL DEFAULT '',
        sender_role ENUM('user','admin') NOT NULL,
        is_typing TINYINT(1) NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_support_chat_typing (user_id, email, sender_role),
        INDEX idx_support_chat_typing_updated (updated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$contactsUserIdColumnCheckSql = "SELECT COUNT(*) AS total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'contacts' AND COLUMN_NAME = 'user_id'";
$contactsUserIdColumnCheckStmt = mysqli_prepare($conn, $contactsUserIdColumnCheckSql);
mysqli_stmt_bind_param($contactsUserIdColumnCheckStmt, 's', $dbName);
mysqli_stmt_execute($contactsUserIdColumnCheckStmt);
$contactsUserIdColumnCheckResult = mysqli_stmt_get_result($contactsUserIdColumnCheckStmt);
$contactsUserIdColumnCheckRow = mysqli_fetch_assoc($contactsUserIdColumnCheckResult);
mysqli_stmt_close($contactsUserIdColumnCheckStmt);
if ((int)($contactsUserIdColumnCheckRow['total'] ?? 0) === 0) {
    mysqli_query($conn, "ALTER TABLE contacts ADD COLUMN user_id INT DEFAULT NULL AFTER id");
}

$contactsAdminReplyColumnCheckSql = "SELECT COUNT(*) AS total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'contacts' AND COLUMN_NAME = 'admin_reply'";
$contactsAdminReplyColumnCheckStmt = mysqli_prepare($conn, $contactsAdminReplyColumnCheckSql);
mysqli_stmt_bind_param($contactsAdminReplyColumnCheckStmt, 's', $dbName);
mysqli_stmt_execute($contactsAdminReplyColumnCheckStmt);
$contactsAdminReplyColumnCheckResult = mysqli_stmt_get_result($contactsAdminReplyColumnCheckStmt);
$contactsAdminReplyColumnCheckRow = mysqli_fetch_assoc($contactsAdminReplyColumnCheckResult);
mysqli_stmt_close($contactsAdminReplyColumnCheckStmt);
if ((int)($contactsAdminReplyColumnCheckRow['total'] ?? 0) === 0) {
    mysqli_query($conn, "ALTER TABLE contacts ADD COLUMN admin_reply TEXT DEFAULT NULL AFTER message");
}

$contactsRepliedByColumnCheckSql = "SELECT COUNT(*) AS total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'contacts' AND COLUMN_NAME = 'replied_by'";
$contactsRepliedByColumnCheckStmt = mysqli_prepare($conn, $contactsRepliedByColumnCheckSql);
mysqli_stmt_bind_param($contactsRepliedByColumnCheckStmt, 's', $dbName);
mysqli_stmt_execute($contactsRepliedByColumnCheckStmt);
$contactsRepliedByColumnCheckResult = mysqli_stmt_get_result($contactsRepliedByColumnCheckStmt);
$contactsRepliedByColumnCheckRow = mysqli_fetch_assoc($contactsRepliedByColumnCheckResult);
mysqli_stmt_close($contactsRepliedByColumnCheckStmt);
if ((int)($contactsRepliedByColumnCheckRow['total'] ?? 0) === 0) {
    mysqli_query($conn, "ALTER TABLE contacts ADD COLUMN replied_by VARCHAR(100) DEFAULT NULL AFTER admin_reply");
}

$contactsRepliedAtColumnCheckSql = "SELECT COUNT(*) AS total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'contacts' AND COLUMN_NAME = 'replied_at'";
$contactsRepliedAtColumnCheckStmt = mysqli_prepare($conn, $contactsRepliedAtColumnCheckSql);
mysqli_stmt_bind_param($contactsRepliedAtColumnCheckStmt, 's', $dbName);
mysqli_stmt_execute($contactsRepliedAtColumnCheckStmt);
$contactsRepliedAtColumnCheckResult = mysqli_stmt_get_result($contactsRepliedAtColumnCheckStmt);
$contactsRepliedAtColumnCheckRow = mysqli_fetch_assoc($contactsRepliedAtColumnCheckResult);
mysqli_stmt_close($contactsRepliedAtColumnCheckStmt);
if ((int)($contactsRepliedAtColumnCheckRow['total'] ?? 0) === 0) {
    mysqli_query($conn, "ALTER TABLE contacts ADD COLUMN replied_at DATETIME DEFAULT NULL AFTER replied_by");
}

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS quiz_settings (
        id TINYINT PRIMARY KEY,
        total_time_seconds INT NOT NULL DEFAULT 600,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS quiz_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL,
        subject VARCHAR(100) NOT NULL,
        amount INT NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        gateway VARCHAR(50) NOT NULL,
        transaction_ref VARCHAR(100) NOT NULL UNIQUE,
        status VARCHAR(20) NOT NULL DEFAULT 'paid',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS quiz_payment_callbacks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL,
        order_id VARCHAR(100) NOT NULL,
        transaction_ref VARCHAR(100) NOT NULL,
        signature VARCHAR(255) NOT NULL,
        payment_record_id INT DEFAULT NULL,
        callback_status VARCHAR(30) NOT NULL,
        callback_message VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_payment_callbacks_ref (transaction_ref),
        INDEX idx_payment_callbacks_user (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$transactionRefUniqueIndexSql = "
    SELECT INDEX_NAME
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = ?
      AND TABLE_NAME = 'quiz_payments'
      AND COLUMN_NAME = 'transaction_ref'
      AND NON_UNIQUE = 0
    LIMIT 1
";
$transactionRefUniqueIndexStmt = mysqli_prepare($conn, $transactionRefUniqueIndexSql);
mysqli_stmt_bind_param($transactionRefUniqueIndexStmt, 's', $dbName);
mysqli_stmt_execute($transactionRefUniqueIndexStmt);
$transactionRefUniqueIndexResult = mysqli_stmt_get_result($transactionRefUniqueIndexStmt);
$transactionRefUniqueIndexRow = mysqli_fetch_assoc($transactionRefUniqueIndexResult);
mysqli_stmt_close($transactionRefUniqueIndexStmt);

if (!empty($transactionRefUniqueIndexRow['INDEX_NAME'])) {
    $indexName = str_replace('`', '``', (string)$transactionRefUniqueIndexRow['INDEX_NAME']);
    mysqli_query($conn, "ALTER TABLE quiz_payments DROP INDEX `$indexName`");
}

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS quiz_control (
        id TINYINT PRIMARY KEY,
        is_quiz_live TINYINT(1) NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS quiz_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL,
        subject VARCHAR(100) NOT NULL,
        payment_id INT DEFAULT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        cancelled_by VARCHAR(100) DEFAULT NULL,
        cancel_reason VARCHAR(255) DEFAULT NULL,
        started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME DEFAULT NULL,
        INDEX idx_quiz_attempt_user_status (username, status),
        INDEX idx_quiz_attempt_started (started_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

mysqli_query($conn, "INSERT INTO quiz_settings (id, total_time_seconds) VALUES (1, 600) ON DUPLICATE KEY UPDATE id = id");
mysqli_query($conn, "INSERT INTO quiz_control (id, is_quiz_live) VALUES (1, 0) ON DUPLICATE KEY UPDATE id = id");

$approvalStatusColumnCheckSql = "SELECT COUNT(*) AS total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'quiz_payments' AND COLUMN_NAME = 'admin_approval_status'";
$approvalStatusColumnCheckStmt = mysqli_prepare($conn, $approvalStatusColumnCheckSql);
mysqli_stmt_bind_param($approvalStatusColumnCheckStmt, "s", $dbName);
mysqli_stmt_execute($approvalStatusColumnCheckStmt);
$approvalStatusColumnCheckResult = mysqli_stmt_get_result($approvalStatusColumnCheckStmt);
$approvalStatusColumnCheckRow = mysqli_fetch_assoc($approvalStatusColumnCheckResult);
mysqli_stmt_close($approvalStatusColumnCheckStmt);

if ((int)($approvalStatusColumnCheckRow['total'] ?? 0) === 0) {
    mysqli_query($conn, "ALTER TABLE quiz_payments ADD COLUMN admin_approval_status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER status");
}

$approvedByColumnCheckSql = "SELECT COUNT(*) AS total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'quiz_payments' AND COLUMN_NAME = 'approved_by'";
$approvedByColumnCheckStmt = mysqli_prepare($conn, $approvedByColumnCheckSql);
mysqli_stmt_bind_param($approvedByColumnCheckStmt, "s", $dbName);
mysqli_stmt_execute($approvedByColumnCheckStmt);
$approvedByColumnCheckResult = mysqli_stmt_get_result($approvedByColumnCheckStmt);
$approvedByColumnCheckRow = mysqli_fetch_assoc($approvedByColumnCheckResult);
mysqli_stmt_close($approvedByColumnCheckStmt);

if ((int)($approvedByColumnCheckRow['total'] ?? 0) === 0) {
    mysqli_query($conn, "ALTER TABLE quiz_payments ADD COLUMN approved_by VARCHAR(100) DEFAULT NULL AFTER admin_approval_status");
}

$approvedAtColumnCheckSql = "SELECT COUNT(*) AS total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'quiz_payments' AND COLUMN_NAME = 'approved_at'";
$approvedAtColumnCheckStmt = mysqli_prepare($conn, $approvedAtColumnCheckSql);
mysqli_stmt_bind_param($approvedAtColumnCheckStmt, "s", $dbName);
mysqli_stmt_execute($approvedAtColumnCheckStmt);
$approvedAtColumnCheckResult = mysqli_stmt_get_result($approvedAtColumnCheckStmt);
$approvedAtColumnCheckRow = mysqli_fetch_assoc($approvedAtColumnCheckResult);
mysqli_stmt_close($approvedAtColumnCheckStmt);

if ((int)($approvedAtColumnCheckRow['total'] ?? 0) === 0) {
    mysqli_query($conn, "ALTER TABLE quiz_payments ADD COLUMN approved_at DATETIME DEFAULT NULL AFTER approved_by");
}

if (!function_exists('get_quiz_total_time_seconds')) {
    function get_quiz_total_time_seconds($conn): int
    {
        $defaultSeconds = 600;
        $query = mysqli_query($conn, "SELECT total_time_seconds FROM quiz_settings WHERE id = 1 LIMIT 1");

        if ($query) {
            $row = mysqli_fetch_assoc($query);
            $seconds = (int)($row['total_time_seconds'] ?? $defaultSeconds);
            if ($seconds >= 30 && $seconds <= 7200) {
                return $seconds;
            }
        }

        return $defaultSeconds;
    }
}

if (isset($_POST['reg_user'])) {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password_1 = $_POST['password_1'] ?? '';
    $password_2 = $_POST['password_2'] ?? '';

    if (empty($username)) {
        array_push($errors, "Username is required");
    } elseif (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        array_push($errors, "Username must be 3-50 characters: letters, numbers, underscore only");
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        array_push($errors, "A valid e-mail is required");
    }
    if (empty($password_1)) {
        array_push($errors, "Password is required");
    } elseif (strlen($password_1) < 6) {
        array_push($errors, "Password must be at least 6 characters");
    }
    if ($password_1 !== $password_2) {
        array_push($errors, "Passwords do not match");
    }

    if (count($errors) === 0) {
        $emailCheckStmt = mysqli_prepare($conn, "SELECT id FROM user WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($emailCheckStmt, "s", $email);
        mysqli_stmt_execute($emailCheckStmt);
        $emailCheckResult = mysqli_stmt_get_result($emailCheckStmt);
        if ($emailCheckResult && mysqli_num_rows($emailCheckResult) > 0) {
            array_push($errors, "Email already exists");
        }
        mysqli_stmt_close($emailCheckStmt);
    }

    if (count($errors) === 0) {
        $usernameCheckStmt = mysqli_prepare($conn, "SELECT id FROM user WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($usernameCheckStmt, "s", $username);
        mysqli_stmt_execute($usernameCheckStmt);
        $usernameCheckResult = mysqli_stmt_get_result($usernameCheckStmt);
        if ($usernameCheckResult && mysqli_num_rows($usernameCheckResult) > 0) {
            array_push($errors, "Username already taken");
        }
        mysqli_stmt_close($usernameCheckStmt);
    }

    if (count($errors) === 0) {
        $hashedPassword = password_hash($password_1, PASSWORD_BCRYPT);
        $insertStmt = mysqli_prepare($conn, "INSERT INTO user(username, email, password) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($insertStmt, "sss", $username, $email, $hashedPassword);
        mysqli_stmt_execute($insertStmt);
        $newUserId = mysqli_insert_id($conn);
        mysqli_stmt_close($insertStmt);

        $createdAt = null;
        if ($newUserId > 0) {
            $profileStmt = mysqli_prepare($conn, "SELECT created_at FROM user WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($profileStmt, "i", $newUserId);
            mysqli_stmt_execute($profileStmt);
            $profileResult = mysqli_stmt_get_result($profileStmt);
            $profileRow = $profileResult ? mysqli_fetch_assoc($profileResult) : null;
            $createdAt = $profileRow['created_at'] ?? null;
            mysqli_stmt_close($profileStmt);
        }

        $_SESSION['username'] = $username;
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['email'] = $email;
        $_SESSION['created_at'] = $createdAt;
        $_SESSION['success'] = "You are now logged in";
        header('Location: welcomequiz.php');
        exit;
    }
}

if (isset($_POST['login_user'])) {
    $username    = trim($_POST['username'] ?? '');
    $rawPassword = $_POST['password'] ?? '';

    if (empty($username)) {
        array_push($errors, "Username is required");
    }
    if (empty($rawPassword)) {
        array_push($errors, "Password is required");
    }

    if (count($errors) === 0) {
        $loginStmt = mysqli_prepare($conn, "SELECT id, username, email, password, created_at FROM user WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($loginStmt, "s", $username);
        mysqli_stmt_execute($loginStmt);
        $loginResult = mysqli_stmt_get_result($loginStmt);
        $loginRow    = $loginResult ? mysqli_fetch_assoc($loginResult) : null;
        mysqli_stmt_close($loginStmt);

        $authenticated = false;
        if ($loginRow) {
            $storedHash = (string)($loginRow['password'] ?? '');
            if (password_verify($rawPassword, $storedHash)) {
                $authenticated = true;
            } elseif ($storedHash === md5(mysqli_real_escape_string($conn, $rawPassword))) {
                // Legacy MD5 account — upgrade hash to bcrypt on first login
                $newHash   = password_hash($rawPassword, PASSWORD_BCRYPT);
                $upgStmt   = mysqli_prepare($conn, "UPDATE user SET password = ? WHERE id = ?");
                $legacyId  = (int)$loginRow['id'];
                mysqli_stmt_bind_param($upgStmt, "si", $newHash, $legacyId);
                mysqli_stmt_execute($upgStmt);
                mysqli_stmt_close($upgStmt);
                $authenticated = true;
            }
        }

        if ($authenticated) {
            $_SESSION['username'] = $loginRow['username'];
            $_SESSION['user_id'] = (int)$loginRow['id'];
            $_SESSION['email'] = $loginRow['email'] ?? '';
            $_SESSION['created_at'] = $loginRow['created_at'] ?? null;
            $_SESSION['success']  = "You are now logged in";
            header('Location: welcomequiz.php');
            exit;
        }

        array_push($errors, "Wrong username/password combination");
    }
}
?>