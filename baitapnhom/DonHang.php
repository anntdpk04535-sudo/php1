<?php
session_start();
$pdo = new PDO("mysql:host=127.0.0.1;dbname=lab4;charset=utf8", "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// XỬ LÝ CẬP NHẬT TRẠNG THÁI VÀ HỦY ĐƠN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $orderId = $_POST['order_id'] ?? '';

    if (!empty($orderId)) {
        // Lấy trạng thái hiện tại của đơn hàng từ CSDL để kiểm tra bảo mật phía Server
        $stmtCheck = $pdo->prepare("SELECT status FROM orders WHERE order_id = ?");
        $stmtCheck->execute([$orderId]);
        $currentStatus = $stmtCheck->fetchColumn();

        if ($action === 'update_status') {
            $newStatus = $_POST['status'] ?? '';
            $allowUpdate = true;

            // Không cho phép chuyển ngược từ Đang giao hàng về Đã đặt
            if ($currentStatus === 'Đang giao hàng' && $newStatus === 'Đã đặt') {
                $allowUpdate = false;
            }
            // Nếu đã Hoàn tất hoặc đã Hủy thì khóa cứng, không cho đổi sang trạng thái khác
            if (in_array($currentStatus, ['Hoàn tất', 'Đã hủy']) && $newStatus !== $currentStatus) {
                $allowUpdate = false;
            }

            if ($allowUpdate && !empty($newStatus)) {
                $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?")->execute([$newStatus, $orderId]);
            }
        }
        // Xử lý hành động Hủy đơn
        elseif ($action === 'cancel_order') {
            // Chỉ cho phép hủy nếu trạng thái hiện tại khác 'Hoàn tất' và khác 'Đã hủy'
            if ($currentStatus !== 'Hoàn tất' && $currentStatus !== 'Đã hủy') {
                $pdo->prepare("UPDATE orders SET status = 'Đã hủy' WHERE order_id = ?")->execute([$orderId]);
            }
        }
    }

    // Tải lại trang và giữ nguyên tham số chi tiết nếu đang xem
    header("Location: DonHang.php" . (isset($_GET['detail']) ? "?detail=" . urlencode($_GET['detail']) : ""));
    exit;
}

$orders = $pdo->query("SELECT * FROM orders ORDER BY order_id DESC")->fetchAll(PDO::FETCH_ASSOC);
$detail = null;
$detail_items = [];
if (isset($_GET['detail'])) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->execute([$_GET['detail']]);
    if ($detail = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$_GET['detail']]);
        $detail_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý Đơn hàng</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333
        }

        .container {
            display: flex;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
            flex-wrap: wrap
        }

        .list-box,
        .detail-box {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            flex: 1;
            min-width: 300px
        }

        .list-box {
            flex: 1.5
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd
        }

        th {
            background: #f8f9fa
        }

        a {
            color: #007bff;
            text-decoration: none
        }

        a:hover {
            text-decoration: underline
        }

        .price {
            color: #d9534f;
            font-weight: bold
        }

        .label {
            font-weight: bold;
            color: #555
        }

        .btn-back {
            display: inline-block;
            margin-bottom: 20px;
            padding: 8px 15px;
            background: #6c757d;
            color: #fff;
            border-radius: 4px;
            text-decoration: none
        }

        .btn-back:hover {
            background: #5a6268;
            color: #fff
        }

        select {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ccc
        }

        select:disabled {
            background: #e9ecef;
            color: #6c757d;
            cursor: not-allowed
        }

        .item-table {
            margin-top: 15px
        }

        .item-table th {
            background: #e9ecef
        }

        /* Style cho nút hủy */
        .btn-cancel {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            padding: 0;
            font-size: 14px;
            text-decoration: underline;
            margin-left: 8px
        }

        .btn-cancel:hover {
            color: #bd2130;
            text-decoration: none
        }
    </style>
</head>

<body>

    <div style="max-width:1200px; margin: 0 auto;">
        <a href="cart.php" class="btn-back">⬅ Quay lại Cửa hàng</a>
    </div>

    <h1>📦 Hệ thống Quản lý Đơn hàng (Admin)</h1>

    <div class="container">
        <div class="list-box">
            <h2>Danh sách đơn hàng</h2>
            <table>
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= htmlspecialchars($order['order_id']) ?></td>
                        <td><?= htmlspecialchars($order['fullname']) ?></td>
                        <td class="price"><?= number_format($order['total'], 0, ',', '.') ?>đ</td>
                        <td>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">

                                <?php if ($order['status'] === 'Hoàn tất'): ?>
                                    <select disabled>
                                        <option>Hoàn tất</option>
                                    </select>
                                <?php elseif ($order['status'] === 'Đã hủy'): ?>
                                    <select disabled>
                                        <option>Đã hủy</option>
                                    </select>
                                <?php else: ?>
                                    <select name="status" onchange="this.form.submit()">
                                        <?php if ($order['status'] === 'Đã đặt'): ?>
                                            <option value="Đã đặt" selected>Đã đặt</option>
                                            <option value="Đang giao hàng">Đang giao hàng</option>
                                            <option value="Hoàn tất">Hoàn tất</option>
                                        <?php endif; ?>

                                        <?php if ($order['status'] === 'Đang giao hàng'): ?>
                                            <option value="Đang giao hàng" selected>Đang giao hàng</option>
                                            <option value="Hoàn tất">Hoàn tất</option>
                                        <?php endif; ?>
                                    </select>
                                <?php endif; ?>
                            </form>
                        </td>
                        <td>
                            <a href="DonHang.php?detail=<?= urlencode($order['order_id']) ?>">Xem chi tiết</a>

                            <?php if ($order['status'] !== 'Hoàn tất' && $order['status'] !== 'Đã hủy'): ?>
                                <form method="POST" style="display:inline;"
                                    onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này không?');">
                                    <input type="hidden" name="action" value="cancel_order">
                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">
                                    <button type="submit" class="btn-cancel">Hủy đơn</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <?php if ($detail): ?>
            <div class="detail-box">
                <h2>Chi tiết đơn hàng #<?= htmlspecialchars($detail['order_id']) ?></h2>
                <p><span class="label">Khách hàng:</span> <?= htmlspecialchars($detail['fullname']) ?></p>
                <p><span class="label">Điện thoại:</span> <?= htmlspecialchars($detail['phone']) ?></p>
                <p><span class="label">Email:</span> <?= htmlspecialchars($detail['email']) ?></p>
                <p><span class="label">Địa chỉ:</span> <?= htmlspecialchars($detail['address']) ?></p>
                <p><span class="label">Ghi chú:</span> <?= htmlspecialchars($detail['note'] ?: 'Không có') ?></p>
                <p><span class="label">Thanh toán:</span> <?= htmlspecialchars($detail['payment']) ?></p>

                <p>
                    <span class="label">Trạng thái:</span>
                <form method="POST" style="display:inline-block; margin-left:5px;">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($detail['order_id']) ?>">

                    <?php if ($detail['status'] === 'Hoàn tất'): ?>
                        <select disabled>
                            <option>Hoàn tất đơn hàng</option>
                        </select>
                    <?php elseif ($detail['status'] === 'Đã hủy'): ?>
                        <select disabled>
                            <option>Đã hủy đơn hàng</option>
                        </select>
                    <?php else: ?>
                        <select name="status" onchange="this.form.submit()">
                            <?php if ($detail['status'] === 'Đã đặt'): ?>
                                <option value="Đã đặt" selected>Đã đặt</option>
                                <option value="Đang giao hàng">Đang giao hàng</option>
                                <option value="Hoàn tất">Hoàn tất</option>
                            <?php endif; ?>

                            <?php if ($detail['status'] === 'Đang giao hàng'): ?>
                                <option value="Đang giao hàng" selected>Đang giao hàng</option>
                                <option value="Hoàn tất">Hoàn tất</option>
                            <?php endif; ?>
                        </select>
                    <?php endif; ?>
                </form>

                <?php if ($detail['status'] !== 'Hoàn tất' && $detail['status'] !== 'Đã hủy'): ?>
                    <form method="POST" style="display:inline; margin-left:10px;"
                        onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này không?');">
                        <input type="hidden" name="action" value="cancel_order">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($detail['order_id']) ?>">
                        <button type="submit" class="btn-cancel" style="font-weight:bold;">[Hủy đơn hàng]</button>
                    </form>
                <?php endif; ?>
                </p>

                <p><span class="label">Tổng tiền:</span> <strong
                        class="price"><?= number_format($detail['total'], 0, ',', '.') ?>đ</strong></p>

                <h3>Sản phẩm trong đơn:</h3>
                <table class="item-table">
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                    </tr>
                    <?php foreach ($detail_items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['description']) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td><?= number_format($item['price'], 0, ',', '.') ?>đ</td>
                            <td class="price"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ</td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>