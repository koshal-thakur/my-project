<?php
include("config.php");
require_once 'redirect_helper.php';
ensure_session_started();

if (isset($_POST['logout_user'])) {
  session_unset();
  session_destroy();
  redirect_to('LOGINpage.php');
}

// If already logged in, send to the quiz area
if (isset($_SESSION['username'])) {
  redirect_to('welcomequiz.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#0ea5a0">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <title>Login — Quiz Competitors</title>
  <link rel="manifest" href="manifest.webmanifest">
  <link rel="apple-touch-icon" href="icons/icon-192.svg">
  <link rel="stylesheet" href="page.css">
</head>
<body>
  <!-- Header -->
  <header>
    <h2 class="QUIZ">QUIZ COMPETITORS</h2>
    <nav class="navigation">
      <a href="index2.html">Home</a>
      <a href="LOGINpage.php" class="nav-active">Login</a>
      <button onclick="location.href='signup.php';" class="btnlogin-popup">Sign Up</button>
    </nav>
  </header>

  <div class="auth-page-wrap">
    <div class="auth-card">
      <div class="auth-logo">🔐</div>
      <h1>Welcome Back</h1>
      <p class="auth-sub">Sign in to your Quiz Competitors account</p>
      <div class="auth-update-strip" role="status" aria-live="polite">
        <span class="auth-update-badge">2026 UPDATE</span>
        <p>Faster quiz flow, improved ranking fairness, and cleaner result insights are now live.</p>
      </div>

      <form name="myform" method="post" action="LOGINpage.php">
        <?php include('errors.php'); ?>
        <div class="txt_field">
          <input type="text" id="username" name="username" placeholder=" " required>
          <span></span>
          <label for="username">Username</label>
        </div>
        <div class="txt_field">
          <input type="password" id="password" name="password" placeholder=" " required>
          <span></span>
          <label for="password">Password</label>
        </div>
        <input type="submit" name="login_user" value="Sign In">
        <div class="signup_link">
          Don't have an account?<a href="signup.php">Sign Up</a>
        </div>
      </form>
    </div>
  </div>
  <script src="pwa-init.js"></script>
</body>
</html>
