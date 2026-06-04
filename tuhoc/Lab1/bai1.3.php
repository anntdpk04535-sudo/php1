<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script>
        function kiemtra() {
            var namsinh=document.getElementById("namsinh");
            if(namsinh.value == "" ) {
                alert('Vui lòng nhập năm sinh');
                namsinh.focus();
                return false;
            } else if(!Number( namsinh.value)) {
                alert('Vui lòng nhập số');
                namsinh.focus();
                return false;
            }
                return true;

        }
    </script>
</head>
<body>
    <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
        <div class="form__group">
            <label for="" class="form__label">First name:</label>
            <input type="text" name="firstname" class="form__input">
        </div>
         <div class="form__group">
            <label for="" class="form__label">last name:</label>
            <input type="text" name="lastname" class="form__input">
        </div>
         <div class="form__group">
            <label for="" class="form__label">Năm sinh:</label>
            <input type="text" name="namsinh" id="namsinh" class="form__input">
        </div>
        
        <input type="submit" name="hienthi" onclick="return kiemtra()" value="Hiển thị kết quả">
    </form>

    <?php

    if(isset($_POST['hienthi']) && $_POST['hienthi']) {

    $hoten = $_POST['firstname']." ". $_POST['lastname'];
    $tuoi=2026 - $_POST['namsinh'];

    $kq = "<h1>Thông tin vừa nhập :<br>";
    $kq.=$hoten . "</h1>";
    $kq.="<br><h2>Tuổi:" . $tuoi ."</h2>";
    $kq.="<br>Chúc bạn pass môn!";

    echo $kq;


    }

?>

</body>
</html>