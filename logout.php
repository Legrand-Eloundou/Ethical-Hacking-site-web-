<?php
require_once __DIR__ . '/includes/functions.php';
setcookie('user_id', '', time() - 3600, '/');
session_destroy();
redirect(BASE_URL . '/index.php');