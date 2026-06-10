<?php
session_start();
if (!isset($_SESSION['users'])) { $_SESSION['users'] = []; }

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate
    if (empty($fullname) || empty($email) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ.";
    } elseif (strlen($password) < 6) {
        $error = "Mật khẩu phải từ 6 ký tự.";
    } else {
        // Kiểm tra email tồn tại
        if (isset($_SESSION['users'][$email])) {
            $error = "Email này đã được đăng ký.";
        } else {
            $_SESSION['users'][$email] = [
                'fullname' => $fullname,
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ];
            $success = "Đăng ký thành công! Hãy đăng nhập.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tạo tài khoản - NovaMart</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="brand">NovaMart</div>
        <h2>Tạo tài khoản</h2>
        <?php if($error) echo "<p class='error'>$error</p>"; ?>
        <?php if($success) echo "<p class='success'>$success</p>"; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Họ tên</label>
                <input type="text" name="fullname" placeholder="Nhập họ tên">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="Nhập email">
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" placeholder="Nhập mật khẩu">
            </div>
            <button type="submit">Đăng ký</button>
        </form>
        <div class="footer-link">
            Đã có tài khoản? <a href="index.php">Đăng nhập</a>
        </div>
    </div>
</body>
</html>