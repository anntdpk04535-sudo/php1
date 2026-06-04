
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        .title{
            font-size: 24pt;
            font-weight: bold;
            color: red;
        }
    </style>
</head>
<body>
    <form action="Bai1.php" method="post">
        <input type="text" name="name" placeholder="Nhập tên của bạn"> <br>
        <input type="text" name="tuoi" placeholder="Nhập tuổi của bạn"> <br> 
        <input type="submit" name="hienthi" value="Kết quả">
    </form>

<?php
$total =1000000;

// $wel = "Hello WD21301";
// $wel = '<header>
//         <h1 class="title">Hello WD21301</h1>
//         <h2>'.number_format($total,0,",",".").'</h2>

//     </header>';
// echo $wel;

if(isset($_POST['hienthi']) && ($_POST['hienthi'])) {

    $ten = $_POST['name'];
$tuoi = $_POST['tuoi'];


 $wel = '<header>
         <h1 class="title">'.$ten.'</h1>
         <h2>'.$tuoi.'</h2>

    </header>';
    echo $wel;

}



?>

</body>

</html>