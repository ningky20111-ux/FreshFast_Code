<?php
session_start();

$_SESSION = [];
session_destroy();

header("Location: rider_login.php");
exit;