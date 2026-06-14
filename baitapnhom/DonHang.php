<?php
session_start();
require_once __DIR__ . "/db_utils.php";
$db = new DB_UTILS();

$message = '';
$error = '';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Lấy thông tin user từ session để lọc đơn hàng
$user_id = $_SESSION['user']['id'] ?? null;
$user_email = $_SESSION['user']['email'] ?? '';
$user_fullname = $_SESSION['user']['full_name'] ?? '';

// ── XỬ LÝ HỦY ĐƠN HÀNG ─────────────────────────────────────────────────────
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'cancel_order'
) {
    $orderId = $_POST['order_id'] ?? '';
    $reason = trim($_POST['cancel_reason'] ?? '');

    if (!empty($orderId)) {
        // Kiểm tra đơn thuộc đúng user (ưu tiên user_id, fallback email/fullname cho đơn cũ)
        $current = $db->getOne(
            "SELECT status FROM orders WHERE order_id = ?
             AND (user_id = ? OR email = ? OR fullname = ?)",
            [$orderId, $user_id, $user_email, $user_fullname]
        );

        $cancellable = ['Đã đặt', 'Chờ thanh toán'];

        if (!$current) {
            $error = "Không tìm thấy đơn hàng!";
        } elseif (!in_array($current['status'], $cancellable)) {
            $error = "Đơn hàng đang giao hoặc đã hoàn tất, không thể hủy!";
        } elseif (empty($reason)) {
            $error = "Bạn phải cung cấp lý do khi hủy đơn hàng!";
        } else {
            $db->execute(
                "UPDATE orders SET status = 'Đã hủy', cancel_reason = ? WHERE order_id = ?",
                [$reason, $orderId]
            );
            $message = "Hủy đơn hàng thành công!";
        }
    }
}

// ── LẤY DANH SÁCH ĐƠN HÀNG CỦA USER ĐANG ĐĂNG NHẬP ────────────────────────
// Ưu tiên user_id (đơn mới), fallback email hoặc fullname (đơn cũ chưa có user_id)
$orders = $db->getAll(
    "SELECT * FROM orders
     WHERE user_id = ? OR email = ? OR fullname = ?
     ORDER BY created_at DESC",
    [$user_id, $user_email, $user_fullname]
);

// ── XEM CHI TIẾT ĐƠN HÀNG ───────────────────────────────────────────────────
$detail = null;
$detail_items = [];
if (isset($_GET['id'])) {
    $view_id = $_GET['id'];
    $detail = $db->getOne(
        "SELECT * FROM orders WHERE order_id = ?
         AND (user_id = ? OR email = ? OR fullname = ?)",
        [$view_id, $user_id, $user_email, $user_fullname]
    );
    if ($detail) {
        $detail_items = $db->getAll(
            "SELECT * FROM order_items WHERE order_id = ?",
            [$view_id]
        );
    }
}

// Helper: trả về CSS class theo trạng thái
function statusClass(string $status): string
{
    return match ($status) {
        'Đã đặt' => 'status-dadat',
        'Chờ thanh toán' => 'status-chothanhtoan',
        'Đã thanh toán' => 'status-dathanhtoan',
        'Đang giao hàng' => 'status-danggiao',
        'Hoàn tất' => 'status-hoantat',
        'Thanh toán thất bại' => 'status-thatbai',
        'Đã hủy' => 'status-dahuy',
        default => 'status-dadat',
    };
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đơn Hàng Của Tôi</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: #f8fafc;
            color: #334155;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        h1,
        h2,
        h3 {
            color: #1e293b;
            margin-bottom: 20px;
        }

        .btn-back {
            display: inline-block;
            margin-bottom: 20px;
            color: #2563eb;
            text-decoration: none;
            font-weight: bold;
        }

        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        th {
            background: #f1f5f9;
            color: #475569;
        }

        /* ── Trạng thái ── */
        .status-badge {
            font-weight: bold;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            white-space: nowrap;
        }

        .status-dadat {
            background: #e0f2fe;
            color: #0369a1;
        }

        .status-chothanhtoan {
            background: #fef9c3;
            color: #92400e;
        }

        .status-dathanhtoan {
            background: #dcfce7;
            color: #15803d;
        }

        .status-danggiao {
            background: #fef3c7;
            color: #b45309;
        }

        .status-hoantat {
            background: #bbf7d0;
            color: #065f46;
        }

        .status-thatbai {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-dahuy {
            background: #f1f5f9;
            color: #64748b;
        }

        .btn-view {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .btn-cancel {
            background: #dc2626;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }

        .btn-cancel:hover {
            background: #b91c1c;
        }

        .detail-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .detail-box p {
            margin-bottom: 8px;
            font-size: 14px;
        }

        .label {
            font-weight: 600;
            color: #475569;
        }

        .empty-state {
            text-align: center;
            color: #94a3b8;
            padding: 40px 0;
        }

        .reason-text {
            background: #fef2f2;
            padding: 12px;
            border-left: 4px solid #dc2626;
            margin: 15px 0;
            color: #991b1b;
            border-radius: 6px;
            font-style: italic;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="index.php" class="btn-back">← Quay lại trang chủ cửa hàng</a>
        <h1>📦 Quản Lý Đơn Hàng Của Bạn</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <h2>Lịch sử đặt hàng</h2>
        <table>
            <tr>
                <th>Mã đơn</th>
                <th>Ngày đặt</th>
                <th>Thanh toán</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
            <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="6" class="empty-state">Bạn chưa đặt đơn hàng nào.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($o['order_id']) ?></strong></td>
                        <td><?= htmlspecialchars($o['created_at']) ?></td>
                        <td><?= $o['payment'] === 'vnpay' ? '💳 VNPay' : '💵 COD' ?></td>
                        <td style="font-weight:bold; color:#dc2626;"><?= number_format($o['total'], 0, ',', '.') ?>đ</td>
                        <td>
                            <span class="status-badge <?= statusClass($o['status']) ?>">
                                <?= htmlspecialchars($o['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="DonHang.php?id=<?= urlencode($o['order_id']) ?>" class="btn-view">Xem chi tiết</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>

        <?php if ($detail): ?>
            <div class="detail-box">
                <h2>Chi Tiết Đơn Hàng: #<?= htmlspecialchars($detail['order_id']) ?></h2>
                <p><span class="label">Người nhận:</span> <?= htmlspecialchars($detail['fullname']) ?></p>
                <p><span class="label">Điện thoại:</span> <?= htmlspecialchars($detail['phone']) ?></p>
                <p><span class="label">Email:</span> <?= htmlspecialchars($detail['email']) ?></p>
                <p><span class="label">Địa chỉ giao hàng:</span> <?= htmlspecialchars($detail['address']) ?></p>
                <p><span class="label">Phương thức thanh toán:</span>
                    <?= $detail['payment'] === 'vnpay' ? '💳 VNPay' : '💵 Tiền mặt (COD)' ?>
                </p>
                <p><span class="label">Trạng thái hiện tại:</span>
                    <span class="status-badge <?= statusClass($detail['status']) ?>">
                        <?= htmlspecialchars($detail['status']) ?>
                    </span>
                </p>

                <?php if (!empty($detail['cancel_reason'])): ?>
                    <div class="reason-text">
                        <strong>Lý do hủy:</strong> <?= htmlspecialchars($detail['cancel_reason']) ?>
                    </div>
                <?php endif; ?>

                <?php
                // Cho phép hủy nếu trạng thái là "Đã đặt" hoặc "Chờ thanh toán"
                $cancellable = ['Đã đặt', 'Chờ thanh toán'];
                if (in_array($detail['status'], $cancellable)):
                    ?>
                    <div style="margin-top:20px; padding-top:20px; border-top:1px dashed #cbd5e1;">
                        <form method="POST" action="DonHang.php">
                            <input type="hidden" name="action" value="cancel_order">
                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($detail['order_id']) ?>">
                            <label style="font-weight:600; display:block; margin-bottom:8px;">Lý do hủy đơn hàng</label>
                            <textarea name="cancel_reason" rows="3" required placeholder="Vui lòng nhập lý do hủy đơn hàng..."
                                style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px; resize:vertical;"></textarea>
                            <button type="submit" class="btn-cancel" style="margin-top:10px;"
                                onclick="return confirm('Bạn có chắc muốn hủy đơn hàng này không?')">
                                ❌ Hủy đơn hàng
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <h3 style="margin-top:20px;">Sản phẩm đã đặt</h3>
                <table>
                    <tr>
                        <th>Tên sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                    </tr>
                    <?php foreach ($detail_items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['description'] ?? 'Sản phẩm không tên') ?></td>
                            <td><?= htmlspecialchars($item['quantity']) ?></td>
                            <td><?= number_format($item['price'], 0, ',', '.') ?>đ</td>
                            <td style="font-weight:bold; color:#dc2626;">
                                <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="3" style="text-align:right; font-weight:bold;">Tổng thanh toán:</td>
                        <td style="font-weight:bold; color:red; font-size:16px;">
                            <?= number_format($detail['total'], 0, ',', '.') ?>đ
                        </td>
                    </tr>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>