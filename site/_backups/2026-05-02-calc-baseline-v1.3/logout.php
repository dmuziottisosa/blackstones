<?php
require_once __DIR__ . '/auth_check.php';
auth_logout();
header('Location: login.php');
exit;
