


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        .red{
            color: red;
        }
        .blue{
            color: blue;
        }
    </style>
    
</head>
<body>
   <?php

    $lop = 'WD21301';
    $namhoc = 2026;
    $kq = 'Hello: <b>'.$lop.'</b> năm học: <b>'.$namhoc.'</b>';
    $gvhd = "Trần Bá Hộ";
    echo $kq;
    $kq2= '<div class="boxcenter">
        <h2 class="red">Lớp: '.$lop.' - Năm học : '.$namhoc.'</h2>
        <p class="blue">GVHD: <span>'.$gvhd.'</span></p>

    </div>';
    echo $kq2;

?>
</body>
</html>