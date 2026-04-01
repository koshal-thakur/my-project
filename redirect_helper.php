<?php
if (!function_exists('redirect_to')) {
    function redirect_to($path)
    {
        header('Location: ' . $path);
        exit;
    }
}

if (!function_exists('ensure_session_started')) {
    function ensure_session_started()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

if (!function_exists('require_user_login')) {
    function require_user_login($redirectPath = 'LOGINpage.php')
    {
        ensure_session_started();
        if (!isset($_SESSION['username'])) {
            redirect_to($redirectPath);
        }
    }
}

if (!function_exists('require_admin_login')) {
    function require_admin_login($redirectPath = 'adminlogin.php')
    {
        ensure_session_started();
        if (!isset($_SESSION['AdminLoginId'])) {
            redirect_to($redirectPath);
        }
    }
}

if (!function_exists('redirect_if_logged_in')) {
    function redirect_if_logged_in($redirectPath = 'welcomequiz.php')
    {
        ensure_session_started();
        if (isset($_SESSION['username'])) {
            redirect_to($redirectPath);
        }
    }
}
