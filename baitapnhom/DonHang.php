<?php
session_start();
$pdo = new PDO("mysql:host=127.0.0.1;dbname=lab4;charset=utf8", "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?")->execute([$_POST['status'], $_POST['order_id']]);
    header("Location: DonHang.php"); exit;
}

$orders = $pdo->query("SELECT * FROM orders ORDER BY order_id DESC")->fetchAll(PDO::FETCH_ASSOC);
$detail = null; $detail_items = [];
if (isset($_GET['detail'])) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?"); $stmt->execute([$_GET['detail']]);
    if ($detail = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?"); $stmt->execute([$_GET['detail']]);
        $detail_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><title>Đơn hàng của tôi</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px}h1{text-align:center;margin-bottom:20px}.nav{text-align:center;margin-bottom:30px}.nav a{margin:0 10px;text-decoration:none;color:#333;font-weight:bold}.nav a:hover{color:#c0392b}table{width:100%;max-width:900px;margin:0 auto;border-collapse:collapse;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.1)}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid #eee}th{background:#333;color:#fff}.badge{padding:4px 10px;border-radius:12px;font-size:13px;font-weight:bold;color:#fff}.badge-placed{background:#3498db}.badge-shipping{background:#f39c12}.badge-completed{background:#27ae60}.status-form select,.status-form button{padding:4px 8px;border-radius:6px;border:1px solid #ccc;font-size:13px}.status-form button{background:#333;color:#fff;cursor:pointer}.status-form button:hover{background:#555}.price{color:#c0392b;font-weight:bold}.empty{text-align:center;padding:40px;color:#999}.btn-detail{padding:4px 10px;background:#3498db;color:#fff;text-decoration:none;border-radius:6px;font-size:13px}.btn-detail:hover{background:#2980b9}.detail-box{max-width:900px;margin:30px auto;background:#fff;padding:24px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1)}.detail-box h2{margin-bottom:16px;border-bottom:2px solid #333;padding-bottom:8px}.detail-box p{margin:6px 0}.detail-box .label{font-weight:bold;display:inline-block;width:140px}.item-table{width:100%;border-collapse:collapse;margin-top:16px}.item-table th,.item-table td{padding:10px 14px;border:1px solid #ddd}.item-table th{background:#f0f0f0}.back-link{display:inline-block;margin-top:16px;color:#3498db;text-decoration:none;font-weight:bold}
    </style>
</head>
<body>
<h1>Đơn hàng của tôi</h1>
<div class="nav"><a href="cart.php">Cửa Hàng</a><a href="DonHang.php">Đơn Hàng</a><a href="lab4.php">Quản lý</a></div>
<table>
    <tr><th>Mã đơn</th><th>Khách hàng</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Trạng thái</th><th>Cập nhật</th><th>Xem</th></tr>
    <?php if (count($orders) === 0): ?>
        <tr><td colspan="7" class="empty">Chưa có đơn hàng nào.</td></tr>
    <?php else: foreach ($orders as $order): 
        $status = $order['status'];
        $badge = $status === 'Đang giao' ? 'badge-shipping' : ($status === 'Hoàn tất' ? 'badge-completed' : 'badge-placed');
    ?>
        <tr>
            <td><strong>#<?= htmlspecialchars($order['order_id']) ?></strong></td>
            <td><?= htmlspecialchars($order['fullname']) ?></td>
            <td><?= htmlspecialchars($order['created_at']) ?></td>
            <td class="price"><?= number_format($order['total'], 0, ',', '.') ?>đ</td>
            <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($status) ?></span></td>
            <td>
                <form class="status-form" method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">
                    <select name="status">
                        <option value="Đã đặt" <?= $status==='Đã đặt'?'selected':'' ?>>Đã đặt</option>
                        <option value="Đang giao" <?= $status==='Đang giao'?'selected':'' ?>>Đang giao</option>
                        <option value="Hoàn tất" <?= $status==='Hoàn tất'?'selected':'' ?>>Hoàn tất</option>
                    </select>
                    <button type="submit">Lưu</button>
                </form>
            </td>
            <td><a class="btn-detail" href="DonHang.php?detail=<?= htmlspecialchars($order['order_id']) ?>">Chi tiết</a></td>
        </tr>
    <?php endforeach; endif; ?>
</table>

<?php if ($detail): ?>
<div class="detail-box">
    <h2>Chi tiết đơn hàng #<?= htmlspecialchars($detail['order_id']) ?></h2>
    <p><span class="label">Khách hàng:</span> <?= htmlspecialchars($detail['fullname']) ?></p>
    <p><span class="label">Điện thoại:</span> <?= htmlspecialchars($detail['phone']) ?></p>
    <p><span class="label">Email:</span> <?= htmlspecialchars($detail['email']) ?></p>
    <p><span class="label">Địa chỉ:</span> <?= htmlspecialchars($detail['address']) ?></p>
    <p><span class="label">Ghi chú:</span> <?= htmlspecialchars($detail['note'] ?: 'Không có') ?></p>
    <p><span class="label">Thanh toán:</span> <?= htmlspecialchars($detail['payment']) ?></p>
    <p><span class="label">Trạng thái:</span> <?= htmlspecialchars($detail['status']) ?></p>
    <p><span class="label">Tổng tiền:</span> <strong class="price"><?= number_format($detail['total'], 0, ',', '.') ?>đ</strong></p>
    <h3>Sản phẩm trong đơn:</h3>
    <table class="item-table">
        <tr><th>Sản phẩm</th><th>Số lượng</th><th>Đơn giá</th><th>Thành tiền</th></tr>
        <?php foreach ($detail_items as $item): ?>
            <tr><td><?= htmlspecialchars($item['description']) ?></td><td><?= $item['quantity'] ?></td><td><?= number_format($item['price'], 0, ',', '.') ?>đ</td><td class="price"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ</td></tr>
        <?php endforeach; ?>
    </table>
    <a class="back-link" href="DonHang.php">&larr; Quay lại danh sách</a>
</div>
<?php endif; ?>
</body>
</html>