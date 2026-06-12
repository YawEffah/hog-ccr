<?php
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

destroySession();
header('Location: login.php');
exit();
