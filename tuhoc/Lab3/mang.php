<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
    <form action="" method="post">

    <input type="number" name="a" id="a">
    
    <input type="number" name="b" id="b">

    <input type="submit" name="hienthi" value="KQ">


    </form>

    <?php
    
    if(isset($_POST['hienthi']) && $_POST['hienthi']) {
        $a = $_POST['a'];
        $b = $_POST['b'];

        $mang = [];

        for ($i=$a; $i <= $b; $i++) { 
            $mang[]=$i;   
        

        }
        //  var_dump($mang);

        $mangso = "";
        $mangtong="";
        $tong=0;
        $mangtc="";
        $tc=0;
        $mangtl="";
        $tl=0;


        for ($i=0; $i < count($mang); $i++) { 

            $mangso .= $mang[$i]. " , "; 
            $mangtong .= $mang[$i]. " + ";
            $tong += $mang[$i];

            if($mang[$i] % 2 ==0) {
                $mangtc.=$mang[$i]. " + ";
                $tc += $mang[$i];

            } else {
                $mangtl.=$mang[$i]. " + ";
                $tl += $mang[$i];
            }

        }
echo '<h2>Mảng vừa nhập: <span>['.rtrim($mangso,", ").']</span></h2>';
echo ' <h2>Tổng mảng: <span>'.rtrim($mangtong,"+ ").'='.$tong.'</span></h2>';
echo ' <h2>Tổng mảng: <span>'.rtrim($mangtc,"+ ").'='.$tc.'</span></h2>';
echo ' <h2>Tổng mảng: <span>'.rtrim($mangtl,"+ ").'='.$tl.'</span></h2>';
    }
    
    
    ?>

<!-- 
    <h2>Mảng vừa nhập: <span>[1,2,3,4]</span></h2>
    
    <h2>Tổng mảng: <span>1 + 2 + 3 = 6</span></h2> -->


</body>
</html>