<?php
 session_start();
 ?>
      <?php


    if (isset($_POST['btnlogin']) && $_POST['btnlogin']) {

    $user = $_POST['username'];
    $pass = $_POST['password'];

    $_SESSION['user'] = $user;
    $_SESSION['pass'] = $pass;

    header('Location: myaccount.php');
    exit();

    }


?>

