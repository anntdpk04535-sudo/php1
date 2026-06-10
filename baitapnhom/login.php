<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location: cart.php"); // Nếu đã đăng nhập rồi thì chuyển thẳng ra trang bán hàng
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

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng điền đầy đủ tài khoản và mật khẩu!';
    } else {
        // Tìm kiếm tài khoản dựa vào Username
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Xác thực mật khẩu đã băm (password_verify)
        if ($user && password_verify($password, $user['password'])) {
            // Đăng nhập thành công -> Lưu thông tin thiết yếu vào Session
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'email' => $user['email']
            ];
            
            header("Location: cart.php"); // Chuyển hướng sang trang mua hàng chính
            exit;
        } else {
            $error = 'Tài khoản hoặc mật khẩu không chính xác!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập hệ thống</title>
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
        .alert { padding: 12px; border-radius: 9px; font-size: 14px; margin-bottom: 16px; text-align: center; background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        p.redirect { text-align: center; margin-top: 16px; font-size: 14px; color: #6b6b6b; }
        p.redirect a { color: #2563eb; text-decoration: none; font-weight: 600; }
        p.redirect a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="auth-container">
    <h2>Đăng Nhập</h2>
    
    <?php if ($error): ?>
        <div class="alert"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label>Tên đăng nhập</label>
            <input type="text" name="username" placeholder="Nhập username của bạn" value="<?= htmlspecialchars($username ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Mật khẩu</label>
            <input type="password" name="password" placeholder="Nhập mật khẩu" required>
        </div>
        <button type="submit" class="btn-auth">Đăng nhập</button>
    </form>
    
    <p class="redirect">Chưa có tài khoản? <a href="register.php">Đăng ký ngay tại đây</a></p>
</div>

</body>
</html>