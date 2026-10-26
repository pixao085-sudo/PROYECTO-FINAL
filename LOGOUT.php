<?php
require_once __DIR__ . '/includes/app.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}
csrf_check();
$_SESSION=[];
if (ini_get('session.use_cookies')) {
    $p=session_get_cookie_params();
    setcookie(session_name(),'',time()-3600,$p['path'],$p['domain'],$p['secure'],$p['httponly']);
}
session_destroy();
header('Location: login.php?salida=1', true, 303);
exit;
