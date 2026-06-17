<?php
//land_logout.php
session_start();

// regenerate session id to mitigate fixation attacks
session_regenerate_id(true);

// clear session data
$_SESSION = [];

// if sesion uses cookies, expire
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// destroy the session
session_destroy();

// redirect to login (use the landing login file)
header('Location: land_login.php');
exit();

?>