<?php
require __DIR__ . '/../includes/lib.php';
if (is_post()) {
    csrf_check();
    $_SESSION = [];
    session_destroy();
}
redirect('login.php');
