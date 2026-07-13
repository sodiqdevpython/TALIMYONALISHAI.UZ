<?php
require_once __DIR__.'/../config/auth.php';
$u = require_login();
header('Location: '.role_home($u['role_key']));
exit;
?>
