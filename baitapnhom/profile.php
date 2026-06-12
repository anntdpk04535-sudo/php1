<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=lab4;charset=utf8", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}

$userId = $_SESSION['user']['id'];
$success = '';
$error = '';

// 1. Xử lý cập nhật thông tin cá nhân
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (!empty($fullName) && !empty($email)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$fullName, $email, $userId]);
            
            // Cập nhật lại thông tin hiển thị trong Session
            $_SESSION['user']['full_name'] = $fullName;
            $_SESSION['user']['email'] = $email;
            $success = "Cập nhật thông tin thành công!";
        } catch (Exception $e) {
            $error = "Email đã được sử dụng bởi tài khoản khác.";
        }
    } else {
        $error = "Vui lòng nhập đầy đủ họ tên và email.";
    }
}

// 2. Lấy danh sách lịch sử đơn hàng của riêng người dùng này
$stmtOrders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmtOrders->execute([$userId]);
$myOrders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang cá nhân người dùng</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #f5f7fb; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1000px; margin: 0 auto; }
        .nav-header { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .nav-header a { text-decoration: none; color: #3498db; font-weight: bold; }
        .layout { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .card h3 { margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px; color: #2c3e50; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px; }
        .form-group input { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-save { background: #2ecc71; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-save:hover { background: #27ae60; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f8f9fa; color: #555; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .status-ordered { background: #e3f2fd; color: #0d47a1; }
        .status-shipping { background: #fff3e0; color: #e65100; }
        .status-done { background: #e8f5e9; color: #1b5e20; }
        .status-canceled { background: #ffebee; color: #b71c1c; }
        .msg { padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-size: 14px; }
        .msg-success { background: #d4edda; color: #155724; }
        .msg-error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<div class="container">
    <div class="nav-header">
        <span>Tài khoản: <strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong> (Quyền: <?= htmlspecialchars($_SESSION['user']['role']) ?>)</span>
        <div>
            <a href="cart.php">🛒 Tiếp tục mua hàng</a> | 
            <a href="logout.php" style="color: #e74c3c;">Đăng xuất</a>
        </div>
    </div>

    <?php if ($success): ?><div class="msg msg-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg msg-error"><?= $error ?></div><?php endif; ?>

    <div class="layout">
        <div class="card">
            <h3>Thông tin cá nhân</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-group">
                    <label>Họ và tên</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($_SESSION['user']['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Địa chỉ Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($_SESSION['user']['email']) ?>" required>
                </div>
                <button type="submit" class="btn-save">Lưu thay đổi</button>
            </form>
        </div>

        <div class="card">
            <h3>Lịch sử đơn hàng đã lưu</h3>
            <?php if (empty($myOrders)): ?>
                <p style="color: #777; text-align: center; margin-top: 20px;">Bạn chưa thực hiện đơn hàng nào.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Ngày đặt</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myOrders as $order): 
                            $statusClass = 'status-ordered';
                            if ($order['status'] === 'Đang giao hàng') $statusClass = 'status-shipping';
                            if ($order['status'] === 'Hoàn tất') $statusClass = 'status-done';
                            if ($order['status'] === 'Đã hủy') $statusClass = 'status-canceled';
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($order['order_id']) ?></strong></td>
                                <td><?= htmlspecialchars($order['created_at']) ?></td>
                                <td style="color: #c0392b; font-weight: bold;"><?= number_format($order['total'], 0, ',', '.') ?>đ</td>
                                <td><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($order['status']) ?></span></td>
                                <td><a href="DonHang.php?id=<?= urlencode($order['order_id']) ?>">Xem</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>