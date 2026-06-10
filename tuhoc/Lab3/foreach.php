<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
<form action="" method="post">



</form>

<?php

    $sothich = ["a"=>"Xem phim",
    "b"=>"Chơi game",
    "c"=>"Du lịch"];

    $st="";

    foreach ($sothich as $key => $value) {
        $st.='<input type="checkbox" name="" id=""> '.$value.' <br>';
    }

?>


<?= $st ?>






</body>
</html>