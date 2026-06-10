
<?php

session_start();

require_once "db-utils.php";


if(isset($_SESSION['user'])){
    header("Location: dashboard.php");
    exit;
}

$db = new DB_UTILS();

$message = "";

if($_SERVER['REQUEST_METHOD'] == "POST"){

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if(empty($email)){
        $message = "Vui lòng nhập email";
    }
    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){
        $message = "Email không hợp lệ";
    }
    elseif(empty($password)){
        $message = "Vui lòng nhập mật khẩu";
    }
    else{

        $user = $db->getOne(
            "SELECT * FROM users WHERE email=?",
            [$email]
        );

        if(
            $user &&
            password_verify(
                $password,
                $user['password']
            )
        ){

            $_SESSION['user'] = [

                'id' => $user['id'],

                'fullname' => $user['fullname'],

                'email' => $user['email'],

                'role' => $user['role']
            ];

            header("Location: dashboard.php");
            exit;
        }
        else{
            $message = "Sai email hoặc mật khẩu";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Đăng nhập</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="auth-container">

<div class="auth-box">

<h2>Đăng nhập</h2>

<?php if(!empty($message)): ?>
<div class="alert-danger">
<?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<form method="POST">

<div class="input-group">
<label>Email</label>

<input
type="email"
name="email"
placeholder="Nhập email"
required>
</div>

<div class="input-group">
<label>Mật khẩu</label>

<input
type="password"
name="password"
placeholder="Nhập mật khẩu"
required>
</div>

<button
type="submit"
class="btn-login">
Đăng nhập
</button>

</form>

<div class="register">

Chưa có tài khoản?

<a href="register.php">
Đăng ký
</a>

</div>

</div>

</div>

</body>
</html>