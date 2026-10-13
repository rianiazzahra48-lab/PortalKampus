<?php
require __DIR__ . '/../includes/lib.php';
redirect(current_user() ? 'dashboard.php' : 'login.php');
