<?php
include("config.php");
require_once 'redirect_helper.php';
redirect_if_logged_in('welcomequiz.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#0ea5a0">
    <title>Sign Up — Quiz Competitors</title>
    <link rel="stylesheet" href="page.css">
</head>
<body>
  <header>
    <h2 class="QUIZ">QUIZ COMPETITORS</h2>
    <nav class="navigation">
      <a href="index2.html">Home</a>
      <a href="signup.php" class="nav-active">Sign Up</a>
      <button onclick="location.href='LOGINpage.php';" class="btnlogin-popup">Login</button>
    </nav>
  </header>

  <div class="auth-page-wrap">
    <div class="auth-card">
      <div class="auth-logo">🎓</div>
      <h1>Create Account</h1>
      <p class="auth-sub">Join Quiz Competitors and start climbing the leaderboard</p>

      <form action="signup.php" method="post">
        <?php include('errors.php'); ?>

        <div class="txt_field">
          <input type="text" id="username" name="username" placeholder=" " required>
          <span></span>
          <label for="username">Username</label>
        </div>
        <div class="txt_field">
          <input type="email" id="email" name="email" placeholder=" " required>
          <span></span>
          <label for="email">Email</label>
        </div>
        <div class="txt_field">
          <input type="password" name="password_1" placeholder=" " required>
          <span></span>
          <label>Password</label>
        </div>
        <div class="txt_field">
          <input type="password" name="password_2" placeholder=" " required>
          <span></span>
          <label>Confirm Password</label>
        </div>

        <input type="submit" name="reg_user" value="Create Account">
        <div class="login_link">
          Already a member?<a href="LOGINpage.php">Login</a>
        </div>
      </form>
    </div>
  </div>
  <script src="india-time.js"></script>
</body>
</html>
