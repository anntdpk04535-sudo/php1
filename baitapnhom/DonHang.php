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

// ── XỬ LÝ HỦY ĐƠN HÀNG ─────────────────────────────────────────────────────
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'cancel_order'
) {
    $orderId = $_POST['order_id'] ?? '';
    $reason = trim($_POST['cancel_reason'] ?? '');

    if (!empty($orderId)) {
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
            // Redirect để tránh resubmit form
            header("Location: DonHang.php?cancel=success");
            exit;
        }
    }
}

if (isset($_GET['cancel']) && $_GET['cancel'] === 'success') {
    $message = "Hủy đơn hàng thành công!";
}

// ── TÌM KIẾM & LỌC ──────────────────────────────────────────────────────────
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

// ── XEM CHI TIẾT ────────────────────────────────────────────────────────────
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

function statusClass(string $s): string
{
    return match ($s) {
        'Đã đặt' => 'st-dadat',
        'Chờ thanh toán' => 'st-cho',
        'Đã thanh toán' => 'st-paid',
        'Đang giao hàng' => 'st-giao',
        'Hoàn tất' => 'st-hoantat',
        'Thanh toán thất bại' => 'st-fail',
        'Đã hủy' => 'st-huy',
        default => 'st-dadat',
    };
}
function paymentLabel(string $p): string
{
    return match ($p) {
        'vnpay' => '<img src="" style="height:20px;vertical-align:middle;border-radius:4px"> VNPay',
        'momo' => '<img src="https://www.bing.com/th/id/OIP.zCOk6lgPI0ku_feP568Q5AHaHa?w=193&h=193&c=8&rs=1&qlt=90&o=6&dpr=1.3&pid=3.1&rm=2" style="height:20px;vertical-align:middle;border-radius:4px"> MoMo',
        default => '💵 COD',
    };
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Đơn Hàng Của Tôi</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f0f4ff 0%, #faf5ff 100%);
            min-height: 100vh;
            padding: 24px 16px;
        }

        /* ── Header ── */
        .page-header {
            max-width: 1100px;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .page-header h1 {
            font-size: 24px;
            font-weight: 800;
            color: #1e293b;
        }

        .page-header h1 span {
            color: #6366f1;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 16px;
            border-radius: 10px;
            background: white;
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.08);
            transition: all .2s;
        }

        .btn-back:hover {
            background: #6366f1;
            color: white;
        }

        /* ── Alert ── */
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 1100px;
            margin-left: auto;
            margin-right: auto;
        }

        .alert-success {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        /* ── Search & Filter bar ── */
        .filter-bar {
            max-width: 1100px;
            margin: 0 auto 20px;
            background: white;
            border-radius: 14px;
            padding: 18px 20px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-bar label {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            display: block;
            margin-bottom: 5px;
        }

        .search-wrap {
            flex: 1;
            min-width: 200px;
        }

        .search-wrap input {
            width: 100%;
            padding: 10px 14px 10px 38px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: border-color .2s;
            background: #fafafa;
        }

        .search-wrap input:focus {
            border-color: #6366f1;
            background: white;
        }

        .search-icon-wrap {
            position: relative;
        }

        .search-icon-wrap .icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 15px;
            pointer-events: none;
        }

        .filter-select select {
            padding: 10px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            background: #fafafa;
            color: #334155;
            cursor: pointer;
            transition: border-color .2s;
        }

        .filter-select select:focus {
            border-color: #6366f1;
        }

        .btn-search {
            padding: 10px 20px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all .2s;
            white-space: nowrap;
        }

        .btn-search:hover {
            opacity: .9;
            transform: translateY(-1px);
        }

        .btn-reset {
            padding: 10px 16px;
            background: #f1f5f9;
            color: #64748b;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all .2s;
        }

        .btn-reset:hover {
            background: #e2e8f0;
        }

        /* ── Table card ── */
        .card {
            max-width: 1100px;
            margin: 0 auto 24px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .card-head {
            padding: 18px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-head h2 {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
        }

        .result-count {
            font-size: 13px;
            color: #94a3b8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            padding: 12px 16px;
            background: #f8fafc;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid #f8fafc;
            font-size: 14px;
            color: #334155;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #fafbff;
        }

        /* ── Status badges ── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .st-dadat {
            background: #eff6ff;
            color: #2563eb;
        }

        .st-cho {
            background: #fefce8;
            color: #a16207;
        }

        .st-paid {
            background: #f0fdf4;
            color: #15803d;
        }

        .st-giao {
            background: #fff7ed;
            color: #c2410c;
        }

        .st-hoantat {
            background: #f0fdf4;
            color: #065f46;
        }

        .st-fail {
            background: #fef2f2;
            color: #dc2626;
        }

        .st-huy {
            background: #f8fafc;
            color: #94a3b8;
        }

        /* ── Action btn ── */
        .btn-view {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            border-radius: 8px;
            background: #eff6ff;
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: all .2s;
        }

        .btn-view:hover {
            background: #2563eb;
            color: white;
        }

        /* ── Empty state ── */
        .empty {
            padding: 60px 20px;
            text-align: center;
            color: #94a3b8;
        }

        .empty .empty-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .empty p {
            font-size: 15px;
        }

        /* ── Detail panel ── */
        .detail-panel {
            max-width: 1100px;
            margin: 0 auto 24px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .detail-panel .dp-header {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            padding: 20px 28px;
            color: white;
        }

        .detail-panel .dp-header h2 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .detail-panel .dp-header p {
            font-size: 13px;
            opacity: .85;
        }

        .dp-body {
            padding: 24px 28px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        .info-item {
            background: #f8fafc;
            border-radius: 10px;
            padding: 14px 16px;
        }

        .info-item .info-label {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 4px;
        }

        .info-item .info-value {
            font-size: 14px;
            color: #1e293b;
            font-weight: 500;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f1f5f9;
        }

        /* ── Cancel box ── */
        .cancel-box {
            background: #fff5f5;
            border: 1.5px solid #fecaca;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .cancel-box .cancel-title {
            font-size: 14px;
            font-weight: 700;
            color: #dc2626;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cancel-box textarea {
            width: 100%;
            padding: 12px;
            border: 1.5px solid #fecaca;
            border-radius: 8px;
            font-size: 14px;
            resize: vertical;
            outline: none;
            background: white;
            transition: border-color .2s;
        }

        .cancel-box textarea:focus {
            border-color: #dc2626;
        }

        .btn-cancel-submit {
            margin-top: 12px;
            padding: 11px 22px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all .2s;
        }

        .btn-cancel-submit:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }

        .reason-box {
            background: #fef2f2;
            border-left: 4px solid #dc2626;
            border-radius: 8px;
            padding: 14px 16px;
            margin: 16px 0;
            font-size: 14px;
            color: #991b1b;
        }

        .reason-box strong {
            display: block;
            margin-bottom: 4px;
        }

        /* ── Modal overlay ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            z-index: 999;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 16px;
            padding: 28px;
            max-width: 440px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            animation: fadeUp .2s ease;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .modal h3 {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .modal p {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 16px;
        }

        .modal textarea {
            width: 100%;
            padding: 12px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            resize: vertical;
            outline: none;
            transition: border-color .2s;
        }

        .modal textarea:focus {
            border-color: #dc2626;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 16px;
            justify-content: flex-end;
        }

        .btn-modal-cancel {
            padding: 10px 20px;
            background: #f1f5f9;
            color: #64748b;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }

        .btn-modal-cancel:hover {
            background: #e2e8f0;
        }

        .btn-modal-confirm {
            padding: 10px 20px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all .2s;
        }

        .btn-modal-confirm:hover {
            background: #b91c1c;
        }

        @media(max-width:640px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .filter-bar {
                flex-direction: column;
            }

            .search-wrap {
                min-width: 100%;
            }
        }
    </style>
</head>

<body>

    <!-- Header -->
    <div class="page-header">
        <h1>📦 Đơn Hàng <span>Của Tôi</span></h1>
        <a href="index.php" class="btn-back">← Trang chủ</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="max-width:1100px;margin:0 auto 16px;">✅ <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" style="max-width:1100px;margin:0 auto 16px;">⚠️ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Search & Filter -->
    <form method="GET" action="DonHang.php">
        <div class="filter-bar">
            <div class="search-wrap">
                <label>Tìm kiếm</label>
                <div class="search-icon-wrap" style="position:relative;">
                    <span class="icon"
                        style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;">🔍</span>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Mã đơn, tên, số điện thoại..." style="padding-left:36px;">
                </div>
            </div>
            <div class="filter-select">
                <label>Trạng thái</label>
                <select name="status">
                    <option value="">-- Tất cả --</option>
                    <?php
                    $statuses = ['Đã đặt', 'Chờ thanh toán', 'Đã thanh toán', 'Đang giao hàng', 'Hoàn tất', 'Thanh toán thất bại', 'Đã hủy'];
                    foreach ($statuses as $s):
                        ?>
                        <option value="<?= $s ?>" <?= $filter_status === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-select">
                <label>Thanh toán</label>
                <select name="payment">
                    <option value="">-- Tất cả --</option>
                    <option value="cod" <?= $filter_payment === 'cod' ? 'selected' : '' ?>>💵 COD</option>
                    <option value="vnpay" <?= $filter_payment === 'vnpay' ? 'selected' : '' ?>>VNPay</option>
                    <option value="momo" <?= $filter_payment === 'momo' ? 'selected' : '' ?>>MoMo</option>
                </select>
            </div>
            <div style="display:flex;gap:8px;align-items:flex-end;">
                <button type="submit" class="btn-search">🔍 Tìm</button>
                <a href="DonHang.php" class="btn-reset">✕ Xóa lọc</a>
            </div>
        </div>
    </form>

    <!-- Danh sách đơn hàng -->
    <div class="card">
        <div class="card-head">
            <h2>Lịch sử đặt hàng</h2>
            <span class="result-count">Tìm thấy <?= count($orders) ?> đơn hàng</span>
        </div>
        <?php if (empty($orders)): ?>
            <div class="empty">
                <div class="empty-icon">📭</div>
                <p><?= (!empty($search) || !empty($filter_status) || !empty($filter_payment))
                    ? 'Không tìm thấy đơn hàng phù hợp.'
                    : 'Bạn chưa đặt đơn hàng nào.' ?></p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Mã đơn hàng</th>
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
                            <td><strong style="color:#6366f1"><?= htmlspecialchars($o['order_id']) ?></strong></td>
                            <td style="color:#64748b;font-size:13px;"><?= htmlspecialchars($o['created_at']) ?></td>
                            <td><?= paymentLabel($o['payment']) ?></td>
                            <td><strong style="color:#ef4444"><?= number_format($o['total'], 0, ',', '.') ?>đ</strong></td>
                            <td><span
                                    class="badge <?= statusClass($o['status']) ?>"><?= htmlspecialchars($o['status']) ?></span>
                            </td>
                            <td style="text-align:center">
                                <a href="DonHang.php?id=<?= urlencode($o['order_id']) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($filter_status) ? '&status=' . urlencode($filter_status) : '' ?><?= !empty($filter_payment) ? '&payment=' . urlencode($filter_payment) : '' ?>"
                                    class="btn-view">👁 Chi tiết</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Chi tiết đơn hàng -->
    <?php if ($detail): ?>
        <div class="detail-panel">
            <div class="dp-header">
                <h2>Chi tiết đơn hàng #<?= htmlspecialchars($detail['order_id']) ?></h2>
                <p>Đặt lúc <?= htmlspecialchars($detail['created_at']) ?></p>
            </div>
            <div class="dp-body">

                <!-- Thông tin -->
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">👤 Người nhận</div>
                        <div class="info-value"><?= htmlspecialchars($detail['fullname']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">📞 Điện thoại</div>
                        <div class="info-value"><?= htmlspecialchars($detail['phone']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">✉️ Email</div>
                        <div class="info-value"><?= htmlspecialchars($detail['email']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">💳 Thanh toán</div>
                        <div class="info-value"><?= paymentLabel($detail['payment']) ?></div>
                    </div>
                    <div class="info-item" style="grid-column:1/-1">
                        <div class="info-label">📍 Địa chỉ giao hàng</div>
                        <div class="info-value"><?= htmlspecialchars($detail['address']) ?></div>
                    </div>
                    <?php if (!empty($detail['note'])): ?>
                        <div class="info-item" style="grid-column:1/-1">
                            <div class="info-label">📝 Ghi chú</div>
                            <div class="info-value"><?= htmlspecialchars($detail['note']) ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="info-item" style="grid-column:1/-1">
                        <div class="info-label">📌 Trạng thái</div>
                        <div class="info-value">
                            <span
                                class="badge <?= statusClass($detail['status']) ?>"><?= htmlspecialchars($detail['status']) ?></span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($detail['cancel_reason'])): ?>
                    <div class="reason-box">
                        <strong>Lý do hủy:</strong><?= htmlspecialchars($detail['cancel_reason']) ?>
                    </div>
                <?php endif; ?>

                <!-- Sản phẩm -->
                <div class="section-title">🛍 Sản phẩm đã đặt</div>
                <table style="margin-bottom:0;">
                    <thead>
                        <tr>
                            <th>Tên sản phẩm</th>
                            <th style="text-align:center">Số lượng</th>
                            <th style="text-align:right">Đơn giá</th>
                            <th style="text-align:right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detail_items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['description'] ?? 'Sản phẩm') ?></td>
                                <td style="text-align:center">
                                    <span style="background:#f1f5f9;padding:3px 10px;border-radius:20px;font-weight:600">
                                        ×<?= $item['quantity'] ?>
                                    </span>
                                </td>
                                <td style="text-align:right;color:#64748b"><?= number_format($item['price'], 0, ',', '.') ?>đ
                                </td>
                                <td style="text-align:right;font-weight:700;color:#ef4444">
                                    <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr style="background:#f8fafc">
                            <td colspan="3" style="text-align:right;font-weight:700;color:#475569;padding-right:16px">Tổng
                                thanh toán:</td>
                            <td style="text-align:right;font-size:18px;font-weight:800;color:#ef4444">
                                <?= number_format($detail['total'], 0, ',', '.') ?>đ
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Nút hủy đơn (dùng modal) -->
                <?php $cancellable = ['Đã đặt', 'Chờ thanh toán']; ?>
                <?php if (in_array($detail['status'], $cancellable)): ?>
                    <div
                        style="margin-top:24px;padding-top:20px;border-top:1px dashed #e2e8f0;display:flex;justify-content:flex-end;">
                        <button type="button" class="btn-cancel-submit"
                            onclick="openCancelModal('<?= htmlspecialchars($detail['order_id']) ?>')">
                            ❌ Yêu cầu hủy đơn hàng
                        </button>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    <?php endif; ?>

    <!-- ── Modal hủy đơn ── -->
    <div class="modal-overlay" id="cancelModal">
        <div class="modal">
            <h3>❌ Xác nhận hủy đơn hàng</h3>
            <p>Vui lòng cho chúng tôi biết lý do bạn muốn hủy đơn hàng này.</p>
            <form method="POST" action="DonHang.php" id="cancelForm">
                <input type="hidden" name="action" value="cancel_order">
                <input type="hidden" name="order_id" id="modal_order_id" value="">
                <textarea name="cancel_reason" id="modal_reason" rows="4"
                    placeholder="VD: Tôi muốn đổi địa chỉ giao hàng, tôi đặt nhầm sản phẩm..." required></textarea>
                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="closeCancelModal()">Giữ đơn hàng</button>
                    <button type="submit" class="btn-modal-confirm" onclick="return validateCancel()">Xác nhận
                        hủy</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCancelModal(orderId) {
            document.getElementById('modal_order_id').value = orderId;
            document.getElementById('modal_reason').value = '';
            document.getElementById('cancelModal').classList.add('active');
        }
        function closeCancelModal() {
            document.getElementById('cancelModal').classList.remove('active');
        }
        function validateCancel() {
            const reason = document.getElementById('modal_reason').value.trim();
            if (!reason) {
                document.getElementById('modal_reason').style.borderColor = '#dc2626';
                document.getElementById('modal_reason').focus();
                return false;
            }
            return true;
        }
        // Đóng modal khi click ra ngoài
        document.getElementById('cancelModal').addEventListener('click', function (e) {
            if (e.target === this) closeCancelModal();
        });
    </script>

</body>

</html>