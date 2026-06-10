<?php

    session_start();
     if(isset($_SESSION['user']) && $_SESSION['user']!="") {
    unset( $_SESSION['user']);

 }

 if(isset($_SESSION['pass']) && $_SESSION['pass']!="") {
    unset( $_SESSION['pass']);

    header('Location: login-register.php');
 }



?>