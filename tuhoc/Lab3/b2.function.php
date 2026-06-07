<?php

function getdanhmuc() {
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
        </div.';
        }
        return $dm;
}

function getsanpham() {
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
        return $sp;
}

?>