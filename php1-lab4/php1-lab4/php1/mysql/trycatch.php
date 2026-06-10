<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
function chia2so($a, $b) {
    try {
        return $a / $b;
    } catch(DivisionByZeroError $e) {
        echo $e->getMessage();
    }
}


echo "<h1>" . chia2so(4,0) . "</h1>";



?>

