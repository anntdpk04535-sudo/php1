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

$user_id = $_SESSION['user']['id'] ?? null;
$user_email = $_SESSION['user']['email'] ?? '';
$user_fullname = $_SESSION['user']['full_name'] ?? '';

// ── AJAX LẤY CHI TIẾT ĐƠN HÀNG ─────────────────────────────────────────────
if (isset($_GET['ajax_detail'])) {
    $view_id = $_GET['ajax_detail'];
    $detail = $db->getOne(
        "SELECT * FROM orders WHERE order_id = ?
         AND (user_id = ? OR email = ? OR fullname = ?)",
        [$view_id, $user_id, $user_email, $user_fullname]
    );

    if ($detail) {
        $detail_items = $db->getAll("SELECT * FROM order_items WHERE order_id = ?", [$view_id]);

        // Trả về HTML cho modal
        ?>
        <div class="modal-detail-content">
            <div class="modal-header">
                <h2>Chi tiết đơn hàng #<?= htmlspecialchars($detail['order_id']) ?></h2>
                <button onclick="closeDetailModal()" class="close-btn">✕</button>
            </div>

            <div class="modal-body">
                <?php if (in_array($detail['status'], ['Đã đặt', 'Chờ thanh toán'])): ?>
                    <div style="text-align:right; margin-bottom:20px;">
                        <button onclick="openCancelFromDetail('<?= htmlspecialchars($detail['order_id']) ?>')"
                            class="btn btn-danger">❌ Yêu cầu hủy đơn</button>
                    </div>
                <?php endif; ?>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">👤 Người nhận</div><?= htmlspecialchars($detail['fullname']) ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">📞 Điện thoại</div><?= htmlspecialchars($detail['phone']) ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">✉️ Email</div><?= htmlspecialchars($detail['email']) ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">💳 Thanh toán</div><?= paymentLabel($detail['payment']) ?>
                    </div>
                    <div class="info-item" style="grid-column:1/-1">
                        <div class="info-label">📍 Địa chỉ</div><?= htmlspecialchars($detail['address']) ?>
                    </div>
                </div>

                <?php if (!empty($detail['cancel_reason'])): ?>
                    <div class="reason-box">
                        <strong>❌ Lý do hủy:</strong><br><?= nl2br(htmlspecialchars($detail['cancel_reason'])) ?>
                    </div>
                <?php endif; ?>

                <h3 style="margin:24px 0 12px;">🛍 Sản phẩm</h3>
                <table class="detail-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th style="text-align:center">SL</th>
                            <th style="text-align:right">Đơn giá</th>
                            <th style="text-align:right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detail_items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['description'] ?? 'Sản phẩm') ?></td>
                                <td style="text-align:center">×<?= $item['quantity'] ?></td>
                                <td style="text-align:right"><?= number_format($item['price'], 0, ',', '.') ?> ₫</td>
                                <td style="text-align:right;font-weight:700;color:#e11d48">
                                    <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?> ₫
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="total-price">
                    Tổng thanh toán: <strong><?= number_format($detail['total'], 0, ',', '.') ?> ₫</strong>
                </div>
            </div>
        </div>
        <?php
    } else {
        echo "<p style='padding:40px;text-align:center;color:red;'>Không tìm thấy đơn hàng!</p>";
    }
    exit;
}

// ── XỬ LÝ HỦY ĐƠN HÀNG ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    $orderId = $_POST['order_id'] ?? '';
    $reason = trim($_POST['cancel_reason'] ?? '');

    if (!empty($orderId)) {
        $current = $db->getOne(
            "SELECT status FROM orders WHERE order_id = ? AND (user_id = ? OR email = ? OR fullname = ?)",
            [$orderId, $user_id, $user_email, $user_fullname]
        );
        $cancellable = ['Đã đặt', 'Chờ thanh toán'];

        if (!$current)
            $error = "Không tìm thấy đơn hàng!";
        elseif (!in_array($current['status'], $cancellable))
            $error = "Đơn hàng không thể hủy ở trạng thái hiện tại!";
        elseif (empty($reason))
            $error = "Vui lòng nhập lý do hủy!";
        else {
            $db->execute("UPDATE orders SET status = 'Đã hủy', cancel_reason = ? WHERE order_id = ?", [$reason, $orderId]);
            $message = "Hủy đơn hàng thành công!";
            header("Location: DonHang.php?cancel=success");
            exit;
        }
    }
}

if (isset($_GET['cancel']) && $_GET['cancel'] === 'success') {
    $message = "Hủy đơn hàng thành công!";
}

// ── DANH SÁCH ĐƠN HÀNG ─────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$filter_status = $_GET['status'] ?? '';
$filter_payment = $_GET['payment'] ?? '';

$sql = "SELECT * FROM orders WHERE (user_id = ? OR email = ? OR fullname = ?)";
$params = [$user_id, $user_email, $user_fullname];

if (!empty($search)) {
    $sql .= " AND (order_id LIKE ? OR fullname LIKE ? OR phone LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
}
if (!empty($filter_status)) {
    $sql .= " AND status = ?";
    $params[] = $filter_status;
}
if (!empty($filter_payment)) {
    $sql .= " AND payment = ?";
    $params[] = $filter_payment;
}
$sql .= " ORDER BY created_at DESC";
$orders = $db->getAll($sql, $params);

function statusClass(string $s): string
{ /* ... giữ nguyên */
    return match ($s) {
        'Đã đặt' => 'st-dadat', 'Chờ thanh toán' => 'st-cho', 'Đã thanh toán' => 'st-paid',
        'Đang giao hàng' => 'st-giao', 'Hoàn tất' => 'st-hoantat',
        'Thanh toán thất bại' => 'st-fail', 'Đã hủy' => 'st-huy',
        default => 'st-dadat',
    };
}

function paymentLabel(string $p): string
{ /* ... giữ nguyên */
    return match ($p) {
        'vnpay' => '<img src="https://vinadesign.vn/uploads/thumbnails/800/2023/05/vnpay-logo-1-1.jpg" style="height:22px;vertical-align:middle;border-radius:4px"> VNPay',
        'momo' => '<img src="https://www.bing.com/th/id/OIP.zCOk6lgPI0ku_feP568Q5AHaHa?w=193&h=193&c=8&rs=1&qlt=90&o=6&dpr=1.3&pid=3.1&rm=2" style="height:22px;vertical-align:middle;border-radius:4px"> MoMo',
        default => '💵 Thanh toán khi nhận hàng',
    };
}

$cancellableStatuses = ['Đã đặt', 'Chờ thanh toán'];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn Hàng Của Tôi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f0f9ff 100%);
            min-height: 100vh;
            padding: 20px;
            color: #1e2937;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(to right, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #ecfdf5;
            color: #10b981;
        }

        .alert-danger {
            background: #fef2f2;
            color: #ef4444;
        }

        .filter-bar {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.05);
            margin-bottom: 24px;
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            align-items: end;
        }

        .filter-group {
            flex: 1;
            min-width: 220px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #64748b;
            font-size: 13px;
        }

        input,
        select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
        }

        input:focus,
        select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.05);
            overflow: hidden;
        }

        th {
            background: #f8fafc;
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            color: #64748b;
        }

        td {
            padding: 18px 20px;
            border-bottom: 1px solid #f1f5f9;
        }

        tr:hover td {
            background: #f8fafc;
        }

        .badge {
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
        }

        /* Modal chung */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 16px;
            width: 95%;
            max-width: 700px;
            max-height: 90vh;
            overflow: auto;
            box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }

        .modal-body {
            padding: 24px;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #64748b;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .info-item {
            background: #f8fafc;
            padding: 16px;
            border-radius: 12px;
        }

        .info-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 4px;
        }

        .reason-box {
            background: #fef2f2;
            border-left: 5px solid #ef4444;
            padding: 16px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .total-price {
            text-align: right;
            font-size: 22px;
            font-weight: 700;
            color: #e11d48;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-box"></i> Đơn Hàng Của Tôi</h1>
            <a href="index.php" class="btn" style="background:#e2e8f0;color:#475569;">← Về Trang Chủ</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- Filter Bar -->
        <form method="GET" class="filter-bar">
            <!-- ... (giữ nguyên phần filter như cũ) -->
            <div class="filter-group">
                <label>Tìm kiếm</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Mã đơn, tên, SĐT...">
            </div>
            <div class="filter-group">
                <label>Trạng thái</label>
                <select name="status">
                    <option value="">Tất cả</option>
                    <?php foreach (['Đã đặt', 'Chờ thanh toán', 'Đã thanh toán', 'Đang giao hàng', 'Hoàn tất', 'Thanh toán thất bại', 'Đã hủy'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filter_status === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Thanh toán</label>
                <select name="payment">
                    <option value="">Tất cả</option>
                    <option value="cod">💵 COD</option>
                    <option value="vnpay">VNPay</option>
                    <option value="momo">MoMo</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">🔍 Tìm kiếm</button>
                <a href="DonHang.php" class="btn" style="background:#f1f5f9;color:#64748b;">Xóa lọc</a>
            </div>
        </form>

        <!-- Danh sách -->
        <div class="card">
            <div class="card-header"
                style="padding:20px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;">
                <h2>📋 Lịch sử đơn hàng</h2>
                <span>Tìm thấy <strong><?= count($orders) ?></strong> đơn</span>
            </div>

            <?php if (empty($orders)): ?>
                <div style="padding:80px;text-align:center;color:#64748b;">Chưa có đơn hàng nào</div>
            <?php else: ?>
                <table style="width:100%;">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Ngày đặt</th>
                            <th>Thanh toán</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th style="text-align:center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td><strong style="color:var(--primary)"><?= htmlspecialchars($o['order_id']) ?></strong></td>
                                <td><?= htmlspecialchars($o['created_at']) ?></td>
                                <td><?= paymentLabel($o['payment']) ?></td>
                                <td><strong><?= number_format($o['total'], 0, ',', '.') ?> ₫</strong></td>
                                <td><span
                                        class="badge <?= statusClass($o['status']) ?>"><?= htmlspecialchars($o['status']) ?></span>
                                </td>
                                <td style="text-align:center">
                                    <button onclick="openDetailModal('<?= htmlspecialchars($o['order_id']) ?>')"
                                        class="btn btn-primary">👁 Chi tiết</button>
                                    <?php if (in_array($o['status'], $cancellableStatuses)): ?>
                                        <button onclick="openCancelModal('<?= htmlspecialchars($o['order_id']) ?>')"
                                            class="btn btn-danger">Hủy</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Chi Tiết Đơn Hàng -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal">
            <div id="detailContent">Đang tải...</div>
        </div>
    </div>

    <!-- Modal Hủy Đơn -->
    <div class="modal-overlay" id="cancelModal">
        <div class="modal" style="max-width:460px;">
            <div style="padding:32px;">
                <h3>❌ Xác nhận hủy đơn hàng</h3>
                <p>Vui lòng cho biết lý do hủy:</p>
                <form method="POST" action="DonHang.php">
                    <input type="hidden" name="action" value="cancel_order">
                    <input type="hidden" name="order_id" id="modal_order_id">
                    <textarea name="cancel_reason" id="modal_reason" rows="5"
                        style="width:100%;padding:12px;border-radius:12px;border:2px solid #e2e8f0;" required
                        placeholder="Lý do..."></textarea>
                    <div style="margin-top:20px;display:flex;gap:12px;justify-content:flex-end;">
                        <button type="button" onclick="closeCancelModal()" class="btn"
                            style="background:#f1f5f9;color:#475569;">Giữ lại</button>
                        <button type="submit" class="btn btn-danger">Xác nhận hủy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openDetailModal(orderId) {
            document.getElementById('detailModal').classList.add('active');
            fetch(`DonHang.php?ajax_detail=${encodeURIComponent(orderId)}`)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('detailContent').innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('detailContent').innerHTML = "<p style='color:red;padding:40px;text-align:center;'>Lỗi khi tải chi tiết!</p>";
                });
        }

        function closeDetailModal() {
            document.getElementById('detailModal').classList.remove('active');
        }

        function openCancelModal(orderId) {
            document.getElementById('modal_order_id').value = orderId;
            document.getElementById('modal_reason').value = '';
            document.getElementById('cancelModal').classList.add('active');
        }

        function openCancelFromDetail(orderId) {
            closeDetailModal();
            setTimeout(() => openCancelModal(orderId), 300);
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').classList.remove('active');
        }

        // Click ngoài modal để đóng
        document.getElementById('detailModal').addEventListener('click', function (e) {
            if (e.target === this) closeDetailModal();
        });
        document.getElementById('cancelModal').addEventListener('click', function (e) {
            if (e.target === this) closeCancelModal();
        });
    </script>
</body>

</html>