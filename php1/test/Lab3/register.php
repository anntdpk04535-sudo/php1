<?php

require_once "db-utils.php";

$db = new DB_UTILS();

$message = "";

if($_SERVER['REQUEST_METHOD']=="POST"){

    $fullname = trim($_POST['fullname'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $password = $_POST['password'] ?? '';

    $confirm_password =
    $_POST['confirm_password'] ?? '';

    if(strlen($fullname) < 3){

        $message =
        "Họ tên tối thiểu 3 ký tự";

    }
    elseif(
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ){

        $message =
        "Email không hợp lệ";

    }
    elseif(
        strlen($password) < 6
    ){

        $message =
        "Mật khẩu tối thiểu 6 ký tự";

    }
    elseif(
        $password !=
        $confirm_password
    ){

        $message =
        "Mật khẩu xác nhận không khớp";

    }
    else{

        $check =
        $db->getOne(

        "SELECT *
        FROM users
        WHERE email=?",

        [$email]

        );

        if($check){

            $message =
            "Email đã tồn tại";

        }
        else{

            $hash =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $db->execute(

            "INSERT INTO users(
                fullname,
                email,
                password,
                role
            )
            VALUES(?,?,?,?)",

            [
                $fullname,
                $email,
                $hash,
                'user'
            ]

            );

            header(
            "Location: login.php"
            );

            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Đăng ký</title>

<link
rel="stylesheet"
href="style.css">

</head>

<body>

<div class="auth-container">

<div class="auth-box">

<h2>Đăng ký</h2>

<?php if(!empty($message)): ?>
<div class="alert-danger">
<?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<form method="POST">

<div class="input-group">

<label>Họ tên</label>

<input
type="text"
name="fullname"
required>

</div>

<div class="input-group">

<label>Email</label>

<input
type="email"
name="email"
required>

</div>

<div class="input-group">

<label>Mật khẩu</label>

<input
type="password"
name="password"
required>

</div>

<div class="input-group">

<label>Nhập lại mật khẩu</label>

<input
type="password"
name="confirm_password"
required>

</div>

<button
type="submit"
class="btn-login">

Đăng ký

</button>

</form>

<div class="register">

Đã có tài khoản?

<a href="login.php">

Đăng nhập

</a>

</div>

</div>

</div>

</body>
</html>