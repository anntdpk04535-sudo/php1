<?php
require_once "b2.function.php";

$dm=getdanhmuc();
$sp=getsanpham();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        h2{
            color: blue;
        }
        .center{
            width: 70%;
            margin: 0 auto;
        }
        .dm{
            float: left;
            width: 25%;
            margin-right: 2%;
        }
        .sp{
            float: left;
            width: 73%S;
        }
    </style>
</head>
<div>
    <?php

        $danhmuc=[
            ["id"=>"1","name"=>"Áo nữ"],
            ["id"=>"2","name"=>"Áo nam"],
            ["id"=>"3","name"=>"Áo trẻ em"]
        ];
        $dm="";
        foreach ($danhmuc as $item) {
            $link='foreach2.0.php?id='.$item['id'];
            $dm.='<div>
            <a href="'.$link.'">'.$item['name'].'</a>
        </div.';}
        $sanpham=[
            ["id"=>"1","name"=>"Áo sơ mi trắng","price"=>"100000","hinh"=>"hinh1.jpg"],
            ["id"=>"2","name"=>"Áo khoác trắng","price"=>"200000","hinh"=>"hinh2.jpg"],
            ["id"=>"3","name"=>"Bộ suit 2023","price"=>"300000","hinh"=>"hinh3.jpg"],
            ["id"=>"4","name"=>"Vest âu 2023","price"=>"400000","hinh"=>"hinh4.jpg"]
        ];

         $sp="";
         $i=1;
          foreach ($sanpham as $item) {
            extract($item);
            $sp.='<tr>
                <th>'.$i.'</th>
                <th><img src="img/'.$hinh.'" alt=""></th>
                <th>'.$name.'</th>
                <th>'.$price.'</th>
                <th>Edit / Delete</th>
            </tr>';

            $i++;
        }

        


    ?>
<div class="center">
    <div class="dm">
        
       <?= $dm?>
   
  
        
                <table>
                    <?php

            if(isset($_GET['id'])&&is_integer($_GET['id'])){
                echo '<h2>Bạn đang chọn: '.$_GET['id'].'</h2>';

            }

?>
 </div>
 <div class="sp">


            <tr>
                <th>STT</th>
                <th>Hình</th>
                <th>Tên SP</th>
                <th>Giá SP</th>
                <th>Hành động</th>
            </tr>
            <?= $sp?>
        </table>
     </div>
</div>

</body>
</html>