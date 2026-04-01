<?php
require_once 'config.php';
require_once 'redirect_helper.php';

ensure_session_started();

if (isset($_SESSION['AdminLoginId'])) {
    redirect_to('adminpannel.php');
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login — Quiz Competitors</title>
        <link rel="stylesheet" href="page.css">
    </head>
    <body class="admin-theme admin-login-page">
        <header>
            <h2 class="QUIZ">QUIZ COMPETITORS</h2>
            <nav class="navigation">
                <a href="index2.html">Home</a>
            </nav>
        </header>

        <div class="admin-login-wrap">
            <div class="admin-login-card">
                <span class="admin-lock">🛡️</span>
                <h1>Admin Login</h1>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'])?>">
                    <input type="text"     placeholder="Admin Name" name="AdminName" required autocomplete="username">
                    <input type="password" placeholder="Password"   name="AdminPass" required autocomplete="current-password">
                    <button type="submit" name="LOGIN">Login to Admin Panel</button>
                </form>
            </div>
        </div>

        <?php
            function input_filter($data){
                $data=trim($data);
                $data=stripslashes($data);
                $data=htmlspecialchars($data);
                return $data;
            }
            if(isset($_POST['LOGIN'])){
                $AdminName=input_filter($_POST['AdminName']);
                $AdminPass=input_filter($_POST['AdminPass']);
                $AdminName=mysqli_real_escape_string($conn,$AdminName);
                $AdminPass=mysqli_real_escape_string($conn,$AdminPass);
                $query="SELECT * FROM `admin_login` WHERE `Admin_Name` =? AND `Admin_Password` =?";
                if($stmt= mysqli_prepare($conn,$query)){
                    mysqli_stmt_bind_param($stmt,"ss",$AdminName,$AdminPass);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_store_result($stmt);
                    if(mysqli_stmt_num_rows($stmt)==1){
                       $_SESSION['AdminLoginId']=$AdminName;
                       header("location: adminpannel.php");
                       exit;
                    }
                    else{
                        echo "<script>alert('Invalid Admin Name or Password')</script>";
                    }
                    mysqli_stmt_close($stmt);
                }
                else{
                    echo "<script>alert('SQL Query cannot be prepared')</script>";
                }
            }
        ?>
        <script src="india-time.js"></script>
    </body>
</html>