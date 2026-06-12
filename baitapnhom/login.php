<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location: cart.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=lab4;charset=utf8", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Kiểm tra mật khẩu (hỗ trợ cả mật khẩu băm password_hash)
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id'        => $user['id'],
                'username'  => $user['username'],
                'full_name' => $user['full_name'],
                'email'     => $user['email'],
                'role'      => $user['role'] ?? 'user' // Lưu thông tin phân quyền
            ];
            header("Location: cart.php");
            exit;
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không chính xác!';
        }
    } else {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập hệ thống</title>
  <link rel="stylesheet" href="style.css">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background: #f4f6f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .auth-box { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { width: 100%; padding: 10px; background: #3498db; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; }
        .btn:hover { background: #2980b9; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; }
        p { text-align: center; margin-top: 15px; font-size: 14px; }
        a { color: #3498db; text-decoration: none; }
    </style>
</head>
<body>
<div class="auth-box">
    <h2>ĐĂNG NHẬP</h2>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Tên tài khoản</label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>Mật khẩu</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn">Đăng nhập</button>
    </form>
    <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
</div>
</body>
</html>