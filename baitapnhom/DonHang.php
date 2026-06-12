<?php
session_start();
// Bắt buộc đăng nhập để xem đơn hàng
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
$role = $_SESSION['user']['role'] ?? 'user';

// 1. API REAL-TIME (TRẢ VỀ JSON CHO JAVASCRIPT CẬP NHẬT GIAO DIỆN KHÔNG CẦN F5)
if (isset($_GET['action']) && $_GET['action'] === 'get_status_realtime') {
    $orderId = $_GET['order_id'] ?? '';
    if ($role === 'admin') {
        $stmt = $pdo->prepare("SELECT status, cancel_reason, created_at FROM orders WHERE order_id = ?");
        $stmt->execute([$orderId]);
    } else {
        $stmt = $pdo->prepare("SELECT status, cancel_reason, created_at FROM orders WHERE order_id = ? AND user_id = ?");
        $stmt->execute([$orderId, $userId]);
    }
    $orderData = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($orderData ? $orderData : ['status' => '']);
    exit;
}

// 2. API XỬ LÝ GỬI REORDER QUA AJAX (MUA LẠI ĐƠN HÀNG)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reorder_ajax') {
    $orderId = $_POST['order_id'] ?? '';
    if (!empty($orderId)) {
        $stmtItems = $pdo->prepare("SELECT oi.*, p.image, p.price AS price_str FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?");
        $stmtItems->execute([$orderId]);
        $oldItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($oldItems)) {
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            foreach ($oldItems as $item) {
                $pid = $item['product_id'];
                $qty = (int)$item['quantity'];

                $cleanPrice = preg_replace('/[₫đĐ\s]/u', '', $item['price']);
                $priceNum = (float)str_replace(',', '', $cleanPrice);

                if (isset($_SESSION['cart'][$pid])) {
                    $_SESSION['cart'][$pid]['qty'] = min(99, $_SESSION['cart'][$pid]['qty'] + $qty);
                } else {
                    $_SESSION['cart'][$pid] = [
                        'product_id'  => $pid,
                        'description' => $item['description'],
                        'price_raw'   => $item['price_str'] ?? number_format($item['price'], 0, ',', '.') . 'đ',
                        'price_num'   => $priceNum,
                        'image'       => $item['image'] ?? 'https://via.placeholder.com/260x190?text=No+Image',
                        'qty'         => $qty
                    ];
                }
            }
            echo json_encode(['ok' => true]);
            exit;
        }
    }
    echo json_encode(['ok' => false]);
    exit;
}

// 3. XỬ LÝ CẬP NHẬT TRẠNG THÁI VÀ HỦY ĐƠN (FORM POST TRUYỀN THỐNG)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $orderId = $_POST['order_id'] ?? '';

    if (!empty($orderId)) {
        $stmtCheck = $pdo->prepare("SELECT status FROM orders WHERE order_id = ?");
        $stmtCheck->execute([$orderId]);
        $currentStatus = $stmtCheck->fetchColumn();

        // CHỈNH SỬA LOGIC: Kiểm tra quyền hạn và ép luồng trạng thái nghiêm ngặt
        if ($action === 'update_status' && $role === 'admin') {
            $newStatus = $_POST['status'] ?? '';
            $allowUpdate = true;
            
            // CHẶN: Nếu đang "Đã đặt" mà chuyển trực tiếp sang "Hoàn tất" -> Từ chối cập nhật
            if ($currentStatus === 'Đã đặt' && $newStatus === 'Hoàn tất') $allowUpdate = false;
            // CHẶN: Không cho phép chuyển ngược từ "Đang giao hàng" về lại "Đã đặt"
            if ($currentStatus === 'Đang giao hàng' && $newStatus === 'Đã đặt') $allowUpdate = false;
            // CHẶN: Nếu đơn hàng đã đóng (Hoàn tất / Đã huỷ) thì không được đổi sang trạng thái khác nữa
            if (in_array($currentStatus, ['Hoàn tất', 'Đã hủy']) && $newStatus !== $currentStatus) $allowUpdate = false;

            if ($allowUpdate && !empty($newStatus) && $newStatus !== 'Đã hủy') {
                $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?")->execute([$newStatus, $orderId]);
            }
        } 
        elseif ($action === 'approve_order' && $role === 'admin') {
            if ($currentStatus === 'Đã đặt') {
                $pdo->prepare("UPDATE orders SET status = 'Đang giao hàng' WHERE order_id = ?")->execute([$orderId]);
            }
        }
        elseif ($action === 'cancel_order') {
            $reason = trim($_POST['cancel_reason'] ?? '');
            if (empty($reason)) {
                die("Lỗi: Bạn bắt buộc phải nhập lý do hủy đơn hàng!");
            }
            if ($role === 'admin') {
                if ($currentStatus !== 'Hoàn tất' && $currentStatus !== 'Đã hủy') {
                    $pdo->prepare("UPDATE orders SET status = 'Đã hủy', cancel_reason = ? WHERE order_id = ?")->execute([$reason, $orderId]);
                }
            } else {
                if ($currentStatus === 'Đã đặt') {
                    $pdo->prepare("UPDATE orders SET status = 'Đã hủy', cancel_reason = ? WHERE order_id = ?")->execute([$reason, $orderId]);
                }
            }
        }
        header("Location: DonHang.php?id=" . urlencode($orderId));
        exit;
    }
}

// LẤY DỮ LIỆU HIỂN THỊ DANH SÁCH + TÌM KIẾM
$search = trim($_GET['search'] ?? '');
$searchQuery = "";
$params = [];
if ($search !== '') {
    $searchQuery = " AND (order_id LIKE ? OR fullname LIKE ? OR phone LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$orderIdParam = $_GET['id'] ?? '';
$detail = null; $detail_items = [];

if (!empty($orderIdParam)) {
    if ($role === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
        $stmt->execute([$orderIdParam]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
        $stmt->execute([$orderIdParam, $userId]);
    }
    $detail = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($detail) {
        $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$orderIdParam]);
        $detail_items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    }
} else {
    if ($role === 'admin') {
        $whereClause = ($searchQuery !== "") ? " WHERE " . ltrim($searchQuery, " AND") : "";
        $stmtAll = $pdo->prepare("SELECT * FROM orders" . $whereClause . " ORDER BY created_at DESC");
        $stmtAll->execute($params);
    } else {
        $stmtAll = $pdo->prepare("SELECT * FROM orders WHERE user_id = ?" . $searchQuery . " ORDER BY created_at DESC");
        $stmtAll->execute(array_merge([$userId], $params));
    }
    $all_orders = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý & Tìm kiếm đơn hàng</title>
  <link rel="stylesheet" href="style.css">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; }
        body { background: #f4f6f9; padding: 30px; color: #333; }
        .container { max-width: 850px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
        h2, h3 { margin-bottom: 20px; color: #2c3e50; font-weight: 700; }
        .btn-back { display: inline-block; padding: 8px 16px; background: #7f8c8d; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; margin-bottom: 20px; font-size: 14px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #3498db; }
        .info-item { font-size: 15px; line-height: 1.6; }
        .info-item strong { color: #555; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e1e8ed; font-size: 15px; }
        th { background: #f1f4f6; color: #475569; }
        .price { color: #e74c3c; font-weight: 700; }
        
        /* CSS SHOPEE TIMELINE HÀNG DỌC */
        .shopee-vertical-timeline-box { background: #fafafa; border: 1px solid #edf0f5; padding: 25px; border-radius: 8px; margin-top: 30px; }
        .shopee-v-timeline { list-style: none; position: relative; padding-left: 30px; }
        .shopee-v-timeline::before { content: ''; position: absolute; left: 5px; top: 12px; width: 2px; height: 75%; background: #e0e0e0; }
        
        .shopee-v-item { position: relative; margin-bottom: 30px; display: flex; flex-direction: column; }
        .shopee-v-item:last-child { margin-bottom: 0; }
        .shopee-v-dot { position: absolute; left: -29px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: #e0e0e0; border: 2px solid #fff; z-index: 2; transition: all 0.3s ease; }
        
        .shopee-v-content { font-size: 14px; color: #9e9e9e; line-height: 1.5; }
        .shopee-v-title { font-weight: 600; color: #9e9e9e; margin-bottom: 3px; font-size: 15px; }
        .shopee-v-time { font-size: 12px; color: #b5b5b5; }

        .shopee-v-item.completed .shopee-v-dot { background: #2ecc71; }
        .shopee-v-item.completed .shopee-v-title { color: #27ae60; }
        .shopee-v-item.completed .shopee-v-content { color: #555; }

        .shopee-v-item.active .shopee-v-dot { background: #26bc4e; box-shadow: 0 0 0 4px rgba(38,188,78,0.2); width: 14px; height: 14px; left: -30px; }
        .shopee-v-item.active .shopee-v-title { color: #26bc4e; font-size: 16px; font-weight: bold; }
        .shopee-v-item.active .shopee-v-content { color: #111; }

        .shopee-v-item.danger-active .shopee-v-dot { background: #ff424e; box-shadow: 0 0 0 4px rgba(255,66,78,0.2); width: 14px; height: 14px; left: -30px; }
        .shopee-v-item.danger-active .shopee-v-title { color: #ff424e; font-size: 16px; font-weight: bold; }
        .shopee-v-item.danger-active .shopee-v-content { color: #111; }
        
        .admin-controls { background: #fff3cd; padding: 15px; border-radius: 8px; display: inline-flex; gap: 10px; align-items: center; border: 1px solid #ffeeba; font-size: 14px; }
        select { padding: 6px 10px; border-radius: 4px; border: 1px solid #ccc; }
        
        /* BUTTONS */
        .btn-cancel { background: #e74c3c; color: white; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: bold; }
        .btn-cancel:hover { background: #c0392b; }
        .btn-approve { background: #2ecc71; color: white; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: bold; }
        .btn-approve:hover { background: #27ae60; }
        .btn-reorder { background: #3498db; color: white; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: bold; }
        .btn-reorder:hover { background: #2980b9; }
        
        /* SEARCH */
        .search-container { display: flex; gap: 10px; margin-bottom: 20px; max-width: 100%; }
        .search-input { flex: 1; padding: 10px 15px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none; }
        .search-input:focus { border-color: #3498db; }
        .btn-search { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-search:hover { background: #2980b9; }
        .btn-clear { padding: 10px 15px; background: #e5e7eb; color: #374151; border: none; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; display: flex; align-items: center; }
        .btn-clear:hover { background: #d1d5db; }

        /* MODAL */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center; }
        .modal-content { background: #fff; padding: 25px; border-radius: 10px; width: 100%; max-width: 450px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); animation: pop 0.25s ease; }
        .modal textarea { width: 100%; height: 100px; padding: 10px; border: 1px solid #ddd; border-radius: 6px; resize: none; margin-bottom: 15px; outline: none; font-size: 14px;}
        .modal-buttons { display: flex; justify-content: flex-end; gap: 10px; }
        .btn-close { background: #bdc3c7; color: #333; border: none; padding: 8px 14px; border-radius: 4px; cursor: pointer; }
        .badge-role { font-size: 11px; background: #e67e22; color: #fff; padding: 2px 6px; border-radius: 4px; margin-left: 5px; text-transform: uppercase;}
        @keyframes pop { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>
<div class="container">
    <?php if ($detail): ?>
        <a href="cart.php" class="btn-back">🏪 Về Cửa Hàng</a>
        <a href="DonHang.php" class="btn-back" style="background:#34495e;">📋 Danh Sách Đơn Hàng</a>
        
        <h2>Chi tiết đơn hàng: <span style="color:#3498db;"><?= htmlspecialchars($detail['order_id']) ?></span></h2>
        
        <div class="info-grid">
            <div class="info-item"><strong>Người nhận:</strong> <?= htmlspecialchars($detail['fullname']) ?></div>
            <div class="info-item"><strong>Điện thoại:</strong> <?= htmlspecialchars($detail['phone']) ?></div>
            <div class="info-item"><strong>Email:</strong> <?= htmlspecialchars($detail['email']) ?></div>
            <div class="info-item"><strong>Ngày đặt:</strong> <?= htmlspecialchars($detail['created_at']) ?></div>
            <div class="info-item" style="grid-column: span 2;"><strong>Địa chỉ:</strong> <?= htmlspecialchars($detail['address']) ?></div>
            <div class="info-item"><strong>Tổng thanh toán:</strong> <strong class="price"><?= number_format($detail['total'], 0, ',', '.') ?>đ</strong></div>
        </div>

        <h3>Sản phẩm đã mua</h3>
        <table>
            <thead><tr><th>Tên mặt hàng</th><th style="text-align: center;">Số lượng</th><th style="text-align: right;">Thành tiền</th></tr></thead>
            <tbody>
                <?php foreach ($detail_items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['description']) ?></td>
                        <td style="text-align: center; font-weight: bold;"><?= $item['quantity'] ?></td>
                        <td style="text-align: right;" class="price"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div id="actionControlsContainer" style="margin-top: 25px; display: flex; justify-content: space-between; align-items: center; gap: 15px;"></div>

        <div class="shopee-vertical-timeline-box">
            <h3 style="font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">Trạng thái lịch trình vận chuyển</h3>
            <ul class="shopee-v-timeline" id="verticalTimelineContainer"></ul>
        </div>

    <?php else: ?>
        <h2>📋 Danh sách đơn hàng <?= $role === 'admin' ? '<span class="badge-role">Tất cả user (Admin)</span>' : '' ?></h2>
        <a href="lab4.php" class="btn-back">Trang chủ</a>
        <a href="cart.php" class="btn-back">🏪 Về Cửa Hàng</a>
        
        
        <form class="search-container" method="GET" action="DonHang.php">
            <input type="text" name="search" class="search-input" placeholder="Tìm theo Mã đơn hàng, Tên hoặc Số điện thoại khách hàng..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn-search">🔍 Tìm kiếm</button>
            <?php if ($search !== ''): ?>
                <a href="DonHang.php" class="btn-clear">Xóa bộ lọc</a>
            <?php endif; ?>
        </form>

        <table>
            <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
            <tbody>
                <?php if (count($all_orders) > 0): ?>
                    <?php foreach ($all_orders as $order): ?>
                        <tr>
                            <td><strong>#<?= htmlspecialchars($order['order_id']) ?></strong></td>
                            <td><?= htmlspecialchars($order['fullname']) ?></td>
                            <td class="price"><?= number_format($order['total'], 0, ',', '.') ?>đ</td>
                            <td style="font-weight: bold;">
                                <?php if ($order['status'] === 'Đã đặt'): ?><span style="color: #f39c12;">⏳ Đã đặt</span>
                                <?php elseif ($order['status'] === 'Đang giao hàng'): ?><span style="color: #3498db;">🚚 Đang giao</span>
                                <?php elseif ($order['status'] === 'Hoàn tất'): ?><span style="color: #2ecc71;">⭐ Hoàn tất</span>
                                <?php else: ?><span style="color: #e74c3c;">❌ Đã hủy</span><?php endif; ?>
                            </td>
                            <td><a href="DonHang.php?id=<?= urlencode($order['order_id']) ?>" style="color:#3498db; text-decoration:none; font-weight: 600;">[Xem chi tiết]</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; color: #7f8c8d; padding: 30px;">Không tìm thấy đơn hàng nào khớp với từ khóa.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="modal" id="cancelModal">
    <div class="modal-content">
        <h3>Lý do hủy đơn hàng</h3>
        <form method="POST" action="DonHang.php?id=<?= urlencode($detail['order_id'] ?? '') ?>" onsubmit="return validateCancelForm()">
            <input type="hidden" name="action" value="cancel_order"><input type="hidden" name="order_id" value="<?= htmlspecialchars($detail['order_id'] ?? '') ?>">
            <textarea name="cancel_reason" id="cancel_reason" placeholder="Nhập lý do hủy..."></textarea>
            <div class="modal-buttons">
                <button type="button" class="btn-close" onclick="closeCancelModal()">Quay lại</button>
                <button type="submit" class="btn-cancel" style="padding: 8px 16px;">Xác nhận hủy</button>
            </div>
        </form>
    </div>
</div>

<script>
const currentRole = '<?= $role ?>';
const currentOrderId = '<?= $detail ? $detail['order_id'] : '' ?>';
const dateOrdered = '<?= $detail ? $detail['created_at'] : '' ?>';
let lastKnownStatus = '';

function openCancelModal() { document.getElementById('cancelModal').style.display = 'flex'; }
function closeCancelModal() { document.getElementById('cancelModal').style.display = 'none'; const s = document.querySelector('select[name="status"]'); if(s) s.value = lastKnownStatus; }
function validateCancelForm() { if(document.getElementById('cancel_reason').value.trim() === '') { alert('Bạn vui lòng nhập lý do trước khi hủy!'); return false; } return true; }
function handleAdminStatusChange(s) { if(s.value === 'Đã hủy') { openCancelModal(); } else { document.getElementById('statusForm').submit(); } }

function updateVerticalTimelineDOM(status, reason, paymentMethod = '') {
    const container = document.getElementById('verticalTimelineContainer');
    if(!container) return;
    let html = '';

    // Nhãn hiển thị phương thức
    const paymentLabels = { cod: 'COD', bank: 'Banking', momo: 'MoMo', vnpay: 'VNPay' };
    let payText = paymentLabels[paymentMethod] || '<?= $detail['payment'] ?? "cod" ?>';

    if (status === 'Đã hủy') {
        html += `
            <li class="shopee-v-item danger-active">
                <div class="shopee-v-dot"></div>
                <div class="shopee-v-content">
                    <div class="shopee-v-title">❌ Đơn hàng đã hủy</div>
                    <div class="shopee-v-text">Lý do hủy: <span style="color:#e74c3c; font-weight:500;">${reason || 'Không rõ lý do'}</span></div>
                </div>
            </li>
        `;
    }

    // MỐC 3: HOÀN TẤT
    let classMoc3 = (status === 'Hoàn tất') ? 'active' : '';
    html += `
        <li class="shopee-v-item ${classMoc3}">
            <div class="shopee-v-dot"></div>
            <div class="shopee-v-content">
                <div class="shopee-v-title">⭐ Giao hàng thành công (Hoàn tất)</div>
                <div class="shopee-v-text">Đơn hàng đã được giao thành công đến người nhận và kết thúc lộ trình.</div>
            </div>
        </li>
    `;

    // MỐC 2: ĐANG GIAO HÀNG
    let classMoc2 = '';
    if (status === 'Đang giao hàng') classMoc2 = 'active';
    else if (status === 'Hoàn tất') classMoc2 = 'completed';
    html += `
        <li class="shopee-v-item ${classMoc2}">
            <div class="shopee-v-dot"></div>
            <div class="shopee-v-content">
                <div class="shopee-v-title">🚚 Đang giao hàng</div>
                <div class="shopee-v-text">Đơn hàng đang được bưu tá đi giao tới địa chỉ của bạn.</div>
            </div>
        </li>
    `;

    // MỐC 1: ĐÃ ĐẶT
    let classMoc1 = '';
    if (status === 'Đã đặt') classMoc1 = 'active';
    else if (status === 'Đang giao hàng' || status === 'Hoàn tất') classMoc1 = 'completed';
    html += `
        <li class="shopee-v-item ${classMoc1}">
            <div class="shopee-v-dot"></div>
            <div class="shopee-v-content">
                <div class="shopee-v-title">📄 Đặt hàng thành công</div>
                <div class="shopee-v-text">Đơn hàng của bạn đã được ghi nhận thành công qua hình thức: <b style="color:#e67e22; text-transform:uppercase;">${paymentLabels[payText] || payText}</b>.</div>
                <div class="shopee-v-time">${dateOrdered}</div>
            </div>
        </li>
    `;

    container.innerHTML = html;
}

function updateActionControlsDOM(status) {
    const container = document.getElementById('actionControlsContainer');
    if(!container) return;
    let leftHtml = ''; let rightHtml = '';

    if ((currentRole === 'admin' && status !== 'Hoàn tất' && status !== 'Đã hủy') || (currentRole !== 'admin' && status === 'Đã đặt')) {
        leftHtml += `<button type="button" class="btn-cancel" onclick="openCancelModal()">❌ Hủy đơn hàng</button> `;
    }
    if (currentRole !== 'admin' && (status === 'Hoàn tất' || status === 'Đã hủy')) {
        leftHtml += `<button type="button" class="btn-reorder" onclick="executeReorder('${currentOrderId}')">🔄 Mua lại đơn hàng</button> `;
    }
    if (currentRole === 'admin' && status === 'Đã đặt') {
        leftHtml += `
            <form method="POST" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn duyệt đơn?');">
                <input type="hidden" name="action" value="approve_order"><input type="hidden" name="order_id" value="${currentOrderId}">
                <button type="submit" class="btn-approve">✅ Duyệt đơn hàng</button>
            </form>
        `;
    }
    
    // ĐIỀU CHỈNH OPTION BOX CHO ADMIN: Khống chế các lựa chọn dựa theo logic bài toán
    if (currentRole === 'admin' && status !== 'Đã hủy' && status !== 'Hoàn tất') {
        let selectOptionsHtml = '';
        
        if (status === 'Đã đặt') {
            // Khi đơn mới "Đã đặt", chỉ cho phép chuyển lên "Đang giao hàng" hoặc "Đã hủy" (ẨN HOÀN TẤT)
            selectOptionsHtml = `
                <option value="Đã đặt" selected>Đã đặt</option>
                <option value="Đang giao hàng">Đang giao hàng</option>
                <option value="Đã hủy">Hủy đơn hàng</option>
            `;
        } else if (status === 'Đang giao hàng') {
            // Khi đơn đã "Đang giao hàng", chỉ cho phép chuyển lên "Hoàn tất" hoặc "Đã hủy" (ẨN ĐÃ ĐẶT)
            selectOptionsHtml = `
                <option value="Đang giao hàng" selected>Đang giao hàng</option>
                <option value="Hoàn tất">Hoàn tất</option>
                <option value="Đã hủy">Hủy đơn hàng</option>
            `;
        }
        
        rightHtml = `
            <div class="admin-controls">
                <strong>Xử lý đơn hàng (Admin):</strong>
                <form method="POST" id="statusForm" style="display: flex; gap: 8px;">
                    <input type="hidden" name="action" value="update_status"><input type="hidden" name="order_id" value="${currentOrderId}">
                    <select name="status" onchange="handleAdminStatusChange(this)">
                        ${selectOptionsHtml}
                    </select>
                </form>
            </div>
        `;
    }
    container.innerHTML = `<div>${leftHtml}</div>${rightHtml}`;
}

async function checkOrderStatusRealtime() {
    if(!currentOrderId) return;
    try {
        const response = await fetch(`DonHang.php?action=get_status_realtime&order_id=${currentOrderId}`);
        const data = await response.json();
        if (data && data.status && data.status !== lastKnownStatus) {
            lastKnownStatus = data.status;
            updateVerticalTimelineDOM(data.status, data.cancel_reason);
            updateActionControlsDOM(data.status);
        }
    } catch (e) { console.log("Lỗi đồng bộ Real-time."); }
}

if(currentOrderId) {
    window.addEventListener('DOMContentLoaded', () => {
        checkOrderStatusRealtime();
        setInterval(checkOrderStatusRealtime, 3000);
    });
}

async function executeReorder(orderId) {
    const fd = new FormData();
    fd.append('action', 'reorder_ajax');
    fd.append('order_id', orderId);
    try {
        const response = await fetch('DonHang.php', { method: 'POST', body: fd });
        const resData = await response.json();
        if(resData.ok) { window.location.href = 'cart.php?checkout=1'; } 
        else { alert('Có lỗi xảy ra khi mua lại.'); }
    } catch (e) { alert('Lỗi kết nối máy chủ.'); }
}
</script>
</body>
</html>