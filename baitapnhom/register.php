<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location: cart.php"); // Nếu đã đăng nhập thì chuyển hướng sang trang mua hàng
    exit;
}

// Kết nối CSDL sử dụng cấu hình chung của bạn
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=lab4;charset=utf8", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($fullname) || empty($email) || empty($username) || empty($password)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Định dạng email không hợp lệ!';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải chứa ít nhất 6 ký tự!';
    } else {
        // Kiểm tra trùng lặp tài khoản hoặc email
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetchColumn() > 0) {
            $error = 'Tên tài khoản hoặc Email đã tồn tại trên hệ thống!';
        } else {
            // Mã hóa mật khẩu bảo mật băm dữ liệu trùng khớp cấu trúc mẫu của bạn
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $stmtInsert = $pdo->prepare("INSERT INTO users (full_name, email, username, password) VALUES (?, ?, ?, ?)");
                $stmtInsert->execute([$fullname, $email, $username, $hashed_password]);
                $success = 'Đăng ký tài khoản thành công! <a href="login.php">Đăng nhập ngay</a>';
            } catch (Exception $e) {
                $error = 'Có lỗi xảy ra: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản</title>
  <link rel="stylesheet" href="style.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background: #f8f5f0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .auth-container { background: #fff; padding: 30px; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); width: 100%; max-width: 420px; margin: 15px; }
        h2 { text-align: center; color: #1a1a1a; margin-bottom: 20px; font-weight: 700; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: #333; }
        .form-group input { width: 100%; padding: 10px 14px; border: 1.5px solid #e0d9d0; border-radius: 9px; font-size: 15px; outline: none; transition: border .2s; background: #f4f6f8; }
        .form-group input:focus { border-color: #c0392b; background: #fff; }
        .btn-auth { width: 100%; padding: 12px; border: none; border-radius: 9px; background: #c0392b; color: #fff; font-size: 16px; font-weight: 600; cursor: pointer; transition: background .2s; margin-top: 10px; }
        .btn-auth:hover { background: #a52a1f; }
        .alert { padding: 12px; border-radius: 9px; font-size: 14px; margin-bottom: 16px; text-align: center; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        p.redirect { text-align: center; margin-top: 16px; font-size: 14px; color: #6b6b6b; }
        p.redirect a { color: #2563eb; text-decoration: none; font-weight: 600; }
        p.redirect a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="auth-container">
    <h2>Đăng Ký Tài Khoản</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <div class="form-group">
            <label>Họ và tên</label>
            <input type="text" name="full_name" placeholder="Nguyễn Văn A" value="<?= htmlspecialchars($fullname ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="example@gmail.com" value="<?= htmlspecialchars($email ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Tên tài khoản (Username)</label>
            <input type="text" name="username" placeholder="nhập tên đăng nhập" value="<?= htmlspecialchars($username ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Mật khẩu</label>
            <input type="password" name="password" placeholder="tối thiểu 6 ký tự">
        </div>
        <button type="submit" class="btn-auth">Đăng ký tài khoản</button>
    </form>
    
    <p class="redirect">Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
</div>

</body>
</html>