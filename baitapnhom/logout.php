<?php
session_start();
unset($_SESSION['user']); // Xóa session thông tin người dùng
header("Location: login.php");
exit;