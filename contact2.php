<?php
require_once 'config.php';

$contactSuccess = false;
$contactError   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $email     = trim($_POST['email']      ?? '');
    $phone     = trim($_POST['phone']      ?? '');
    $message   = trim($_POST['message']    ?? '');
    $currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    if ($currentUserId <= 0) {
        $currentUserId = null;
    }

    if ($firstName === '' || $message === '' || $email === '') {
        $contactError = 'First name, email, and message are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contactError = 'Please enter a valid email address.';
    } else {
        $firstName = mb_substr($firstName, 0, 100);
        $lastName  = mb_substr($lastName,  0, 100);
        $phone     = mb_substr($phone,     0, 30);
        $message   = mb_substr($message,   0, 2000);

        if ($currentUserId !== null) {
            $contactStmt = mysqli_prepare($conn,
                'INSERT INTO contacts (user_id, first_name, last_name, email, phone, message) VALUES (?, ?, ?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param($contactStmt, 'isssss', $currentUserId, $firstName, $lastName, $email, $phone, $message);
        } else {
            $contactStmt = mysqli_prepare($conn,
                'INSERT INTO contacts (first_name, last_name, email, phone, message) VALUES (?, ?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param($contactStmt, 'sssss', $firstName, $lastName, $email, $phone, $message);
        }
        if (mysqli_stmt_execute($contactStmt)) {
            $contactSuccess = true;

            $chatUserId = (int)($currentUserId ?? 0);
            $chatUsername = trim((string)($_SESSION['username'] ?? ''));
            if ($chatUsername === '') {
                $chatUsername = trim($firstName . ' ' . $lastName);
            }
            if ($chatUsername === '') {
                $chatUsername = 'User';
            }

            $chatInsertStmt = mysqli_prepare($conn,
                'INSERT INTO support_chat_messages (user_id, username, email, sender_role, sender_name, message) VALUES (?, ?, ?, ?, ?, ?)'
            );
            if ($chatInsertStmt) {
                $chatRole = 'user';
                mysqli_stmt_bind_param($chatInsertStmt, 'isssss', $chatUserId, $chatUsername, $email, $chatRole, $chatUsername, $message);
                mysqli_stmt_execute($chatInsertStmt);
                mysqli_stmt_close($chatInsertStmt);
            }
        } else {
            $contactError = 'Could not save your message. Please try again.';
        }
        mysqli_stmt_close($contactStmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | Quiz Competitors</title>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <link rel="stylesheet" href="page.css">
</head>
<body>
    <header>
        <h2 class="QUIZ">QUIZ COMPETITORS</h2>
        <nav class="navigation">
            <a href="index2.html">Home</a>
            <a href="about1.php">About Us</a>
            <a href="contact2.php" class="nav-active">Contact</a>
            <?php if (isset($_SESSION['username'])): ?>
                <a href="account.php" class="btnlogin-popup">My Account</a>
            <?php else: ?>
                <button onclick="location.href='LOGINpage.php';" class="btnlogin-popup">Login</button>
            <?php endif; ?>
        </nav>
    </header>

    <main class="simple-page-wrap">
        <h1 class="simple-page-title">Contact Us</h1>

        <section class="page-update-strip" aria-label="2026 platform update">
            <span class="page-update-badge">2026 UPDATE</span>
            <h2>Support Experience Updated</h2>
            <p>Along with quiz improvements, support flow is now cleaner so your messages and feedback are handled faster.</p>
            <div class="page-update-mini-grid">
                <div class="page-update-mini">
                    <strong>Quicker Replies</strong>
                    <span>Improved contact data handling and review process.</span>
                </div>
                <div class="page-update-mini">
                    <strong>Feedback Loop</strong>
                    <span>Your suggestions now directly shape upcoming updates.</span>
                </div>
                <div class="page-update-mini">
                    <strong>Better Clarity</strong>
                    <span>Clear forms and validations reduce failed submissions.</span>
                </div>
            </div>
        </section>

        <div class="soft-grid">
            <div class="info-card">
                <h3>Contact Information</h3>
                <ul class="info-list">
                    <li><i class="fas fa-phone-alt"></i> <strong> Phone:</strong> <a href="tel:+919876543210">+91 9876543210</a></li>
                    <li><i class="fas fa-paper-plane"></i> <strong> Email:</strong> <a href="mailto:koshalthakur1902@gmail.com">koshalthakur1902@gmail.com</a></li>
                </ul>
                <div class="social-row social-row-left">
                    <a href="https://github.com/koshal-thakur" aria-label="GitHub"><i class="fab fa-github"></i></a>
                    <a href="https://www.linkedin.com/in/koshal-thakur-641721332/" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <div class="info-card">
                <h3>Send a Message</h3>
                <?php if ($contactSuccess): ?>
                    <div class="notice-box notice-success">
                        ✅ Your message has been sent! We'll get back to you soon.
                    </div>
                <?php endif; ?>
                <?php if ($contactError !== ''): ?>
                    <div class="notice-box notice-error">
                        <?php echo htmlspecialchars($contactError); ?>
                    </div>
                <?php endif; ?>
                <form action="contact2.php" method="POST" class="contact-form">
                    <input type="hidden" name="contact_submit" value="1">
                    <div class="txt_field">
                        <input type="text" name="first_name" placeholder=" " required maxlength="100"
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                        <label>First Name</label>
                    </div>
                    <div class="txt_field">
                        <input type="text" name="last_name" placeholder=" " maxlength="100"
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                        <label>Last Name</label>
                    </div>
                    <div class="txt_field">
                        <input type="email" name="email" placeholder=" " required maxlength="191"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        <label>Email</label>
                    </div>
                    <div class="txt_field">
                        <input type="tel" name="phone" placeholder=" " maxlength="30"
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        <label>Phone</label>
                    </div>
                    <div class="txt_field">
                        <textarea name="message" placeholder="Your message" required maxlength="2000"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>
                    <input type="submit" value="Send Message">
                </form>
            </div>
        </div>
    </main>
    <script src="india-time.js"></script>
</body>
</html>