<?php

session_start();

$_SESSION = [];

session_destroy();

redirect('login.php');
?>