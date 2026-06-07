<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script>
        function kiemtra() {
            var a=document.getElementById('a').value;
            var b=document.getElementById('b').value;

            if(a=="" ) {
                alert('Vui lòng nhập a');
            }

            if(b=="" ) {
                alert('Vui lòng nhập b');
            }

        }
    </script>
</head>
<body>
    
    <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">

    <div>Hình này là hình gì?</div>

    <div>
        <label for="">Cạnh a</label>
        <input type="text" name="a" id="a">
    </div>

    <div>
        <label for="">Cạnh b</label>
        <input type="text" name="b" id="b">
    </div>

    <input type="submit" name="hienthi" onclick="return kiemtra()" value="Kết quả">

    </form>

    <?php

        if(isset($_POST['hienthi']) && $_POST['hienthi']) {
            
        }
    
    ?>


    <div>
        <span>Cạnh A: </span>
    </div>
    
     <div>
        <span>Cạnh B: </span>
    </div>

</body>
</html>