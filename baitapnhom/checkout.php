<!-- <?php
session_start();
require "./db_utils.php";
$db = new DB_UTILS();

// --- XỬ LÝ KHI BẤM "TIẾP TỤC MUA HÀNG" ---
// Khi người dùng bấm nút này ở trang hoàn tất, xóa dữ liệu đơn hàng tạm thời và quay về trang chủ
if (isset($_GET['clear_order'])) {
    unset($_SESSION['last_order']);
    header("Location: index.php");
    exit;
}

$order_success = false;
$placed_order = null;

// Kiểm tra xem có dữ liệu đơn hàng vừa mới đặt thành công hay không để hiển thị trạng thái xem đơn hàng
if (isset($_SESSION['last_order'])) {
    $placed_order = $_SESSION['last_order'];
    $order_success = true;
}

// Nếu không phải là trạng thái xem đơn hàng thành công VÀ giỏ hàng trống, không cho truy cập trang thanh toán
if (!$order_success && empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit;
}

// Lấy thông tin tóm tắt đơn hàng để hiển thị (Chỉ xử lý khi đang ở bước nhập thông tin đơn hàng)
$cart_items = [];
$total_all = 0;
if (!$order_success) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $products = $db->getAll("SELECT * FROM products WHERE id IN ($placeholders)", $ids);

    foreach ($products as $p) {
        $qty = $_SESSION['cart'][$p['id']];
        $clean_price = (int)preg_replace('/[^0-9]/', '', $p['gia']);
        $subtotal = $clean_price * $qty;
        $total_all += $subtotal;
        $cart_items[] = [
            'tenSP' => $p['tenSP'],
            'qty' => $qty,
            'subtotal' => $subtotal
        ];
    }
}

$errors = [];

// --- XỬ LÝ SUBMIT ĐƠN HÀNG ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$order_success) {
    $fullname       = trim($_POST['fullname'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $notes          = trim($_POST['notes'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'cod'); // Lấy phương thức thanh toán người dùng chọn

    if (empty($fullname)) $errors[] = "Vui lòng cung cấp Họ và tên người nhận";
    if (empty($phone))    $errors[] = "Vui lòng cung cấp Số điện thoại liên hệ";
    if (empty($address))  $errors[] = "Vui lòng cung cấp Địa chỉ nhận hàng";
    if (!in_array($payment_method, ['cod', 'bank'])) $errors[] = "Phương thức thanh toán không hợp lệ";

    if (empty($errors)) {
        /* LƯU Ý: Nếu bạn có bảng orders và order_details, bạn có thể thực hiện lệnh INSERT dữ liệu 
           của khách hàng ($fullname, $phone, $address, $total_all, $payment_method) bằng $db->execute() tại đây.
        */
        
        // Tạo ngẫu nhiên một mã đơn hàng giả lập để phục vụ việc xem đơn hàng và ghi nội dung chuyển khoản
        $order_code = 'DH-' . strtoupper(substr(md5(time()), 0, 6));

        // Lưu thông tin đơn hàng vừa đặt vào Session tạm thời để khách hàng xem trực tiếp thông tin đơn hàng đang đặt
        $_SESSION['last_order'] = [
            'order_code'     => $order_code,
            'fullname'       => $fullname,
            'phone'          => $phone,
            'address'        => $address,
            'notes'          => $notes,
            'payment_method' => $payment_method,
            'items'          => $cart_items,
            'total_all'      => $total_all,
            'date'           => date('d/m/Y H:i')
        ];

        // Đặt hàng thành công -> Xóa sạch giỏ hàng hiện tại
        unset($_SESSION['cart']);
        
        // Chuyển hướng lại trang thanh toán bằng cơ chế điều hướng GET giúp tránh việc trùng lặp dữ liệu khi người dùng ấn F5 (Reload)
        header("Location: checkout.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Thanh toán đơn hàng</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #f0f2f5; font-family: 'Segoe UI', Arial, sans-serif; color: #222; }
    .navbar {
      background: linear-gradient(135deg, #1e3a8a, #2563eb); color: #fff; padding: 0 30px;
      display: flex; align-items: center; height: 58px; box-shadow: 0 2px 8px rgba(0,0,0,.2);
    }
    .navbar .brand { font-size: 20px; font-weight: 700; }
    .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
    .checkout-layout { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 24px; }
    .card { background: #fff; border-radius: 14px; padding: 24px; box-shadow: 0 4px 16px rgba(0,0,0,.08); }
    .card-title { font-size: 18px; font-weight: 700; margin-bottom: 18px; color: #1e3a8a; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
    .form-group label { font-weight: 600; font-size: 14px; color: #374151; }
    .form-group input, .form-group textarea { padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 9px; font-size: 14px; outline: none; }
    .form-group input:focus, .form-group textarea:focus { border-color: #2563eb; }
    .form-group textarea { min-height: 80px; resize: vertical; }
    .summary-item { display: flex; justify-content: space-between; font-size: 14px; padding: 8px 0; border-bottom: 1px dashed #e5e7eb; }
    .btn { display: block; width: 100%; text-align: center; padding: 12px; border: none; border-radius: 9px; font-size: 16px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .2s; }
    .btn-success { background: #16a34a; color: #fff; }
    .btn-success:hover { background: #15803d; }
    .btn-secondary { background: #6b7280; color: #fff; margin-top: 10px; }
    .alert { padding: 10px 14px; border-radius: 9px; margin-bottom: 14px; font-size: 14px; background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .success-card { text-align: center; padding: 40px; }
    .success-icon { font-size: 50px; margin-bottom: 15px; color: #16a34a; }
    
    /* STYLE CHO PHẦN THÔNG TIN CHI TIẾT ĐƠN HÀNG ĐÃ ĐẶT VÀ KHUNG TÀI KHOẢN NGÂN HÀNG */
    .order-detail-table { width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 20px; }
    .order-detail-table td { padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
    .order-detail-table td:first-child { color: #6b7280; width: 160px; font-weight: 500; }
    .order-detail-table td:last-child { font-weight: 600; color: #1f2937; }
    .bank-box { background: #eff6ff; border: 1px dashed #3b82f6; padding: 16px; border-radius: 10px; margin-top: 10px; font-size: 14px; line-height: 1.6; }
    
    @media (max-width: 768px) { .checkout-layout { grid-template-columns: 1fr; } }
  </style>
</head>
<body>

<nav class="navbar">
  <span class="brand">🛍️ Tiến hành thanh toán</span>
</nav>

<div class="container">
  
  <?php if ($order_success && $placed_order): ?>
    <div class="card" style="max-width: 750px; margin: 0 auto; padding: 30px;">
      <div style="text-align: center; margin-bottom: 25px;">
        <div class="success-icon">🎉</div>
        <h2 style="color: #166534; margin-bottom: 8px;">Đặt hàng thành công!</h2>
        <p style="color: #4b5563;">Cảm ơn bạn đã mua sắm tại hệ thống. Đơn hàng của bạn đang được xử lý.</p>
      </div>

      <div style="background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 12px; padding: 22px;">
        <h3 style="color: #1e3a8a; font-size: 16px; font-weight: 700; margin-bottom: 14px; border-bottom: 2px solid #cbd5e1; padding-bottom: 6px;">📋 THÔNG TIN ĐƠN HÀNG CHI TIẾT</h3>
        
        <table class="order-detail-table">
          <tr><td>Mã đơn hàng:</td><td style="color: #2563eb !important; font-weight: 700 !important;"><?= $placed_order['order_code'] ?></td></tr>
          <tr><td>Thời gian đặt hàng:</td><td><?= $placed_order['date'] ?></td></tr>
          <tr><td>Họ và tên người nhận:</td><td><?= htmlspecialchars($placed_order['fullname']) ?></td></tr>
          <tr><td>Số điện thoại di động:</td><td><?= htmlspecialchars($placed_order['phone']) ?></td></tr>
          <tr><td>Địa chỉ nhận hàng:</td><td><?= htmlspecialchars($placed_order['address']) ?></td></tr>
          <?php if (!empty($placed_order['notes'])): ?>
            <tr><td>Ghi chú đơn hàng:</td><td style="font-style: italic; color: #4b5563 !important; font-weight: normal !important;"><?= htmlspecialchars($placed_order['notes']) ?></td></tr>
          <?php endif; ?>
          <tr><td>Hình thức thanh toán:</td><td style="color: #d97706 !important; font-weight: 700 !important;"><?= $placed_order['payment_method'] === 'bank' ? 'Chuyển khoản ngân hàng' : 'Thanh toán khi nhận hàng (COD)' ?></td></tr>
        </table>

        <?php if ($placed_order['payment_method'] === 'bank'): ?>
          <div class="bank-box">
            <p style="font-weight: 700; color: #1e40af; margin-bottom: 6px; font-size: 14.5px;">🏦 THÔNG TIN TÀI KHOẢN CHUYỂN KHOẢN:</p>
            <div>Ngân hàng: <strong>MB Bank (Ngân hàng Quân Đội)</strong></div>
            <div>Số tài khoản: <strong>123456789999</strong></div>
            <div>Chủ tài khoản: <strong>CỬA HÀNG </strong></div>
            <div>Số tiền chuyển khoản: <strong style="color: #dc2626; font-size: 16px;"><?= number_format($placed_order['total_all'], 0, ',', '.') ?>đ</strong></div>
            <div>Nội dung chuyển khoản chuẩn: <strong style="color: #2563eb; background: #fff; padding: 2px 8px; border: 1px solid #bfdbfe; border-radius: 4px;"><?= $placed_order['order_code'] ?></strong></div>
            <p style="color: #6b7280; font-size: 12px; margin-top: 8px; font-style: italic;">* Lưu ý: Bạn vui lòng chuyển khoản đúng số tiền và ghi chính xác mã nội dung đơn hàng phía trên để hệ thống duyệt đơn tự động.</p>
          </div>
        <?php endif; ?>

        <div style="margin-top: 20px; border-top: 1px dashed #cbd5e1; padding-top: 14px;">
          <p style="font-weight: 700; color: #374151; margin-bottom: 10px; font-size: 14px;">📦 Danh sách sản phẩm mua:</p>
          <?php foreach ($placed_order['items'] as $item): ?>
            <div style="display: flex; justify-content: space-between; font-size: 13.5px; padding: 4px 0;">
              <span style="color: #4b5563; max-width: 75%;"><?= htmlspecialchars($item['tenSP']) ?> <span style="color: #9ca3af;">x<?= $item['qty'] ?></span></span>
              <span style="font-weight: 600; color: #1f2937;"><?= number_format($item['subtotal'], 0, ',', '.') ?>đ</span>
            </div>
          <?php endforeach; ?>
          
          <div style="display: flex; justify-content: space-between; font-weight: 700; font-size: 15px; margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
            <span>Tổng tiền thanh toán đơn hàng:</span>
            <span style="color: #dc2626; font-size: 18px; font-weight: 800;"><?= number_format($placed_order['total_all'], 0, ',', '.') ?>đ</span>
          </div>
        </div>
      </div>

      <div style="margin-top: 25px; text-align: center;">
        <a href="?clear_order=1" class="btn btn-success" style="display: inline-block; width: auto; padding: 10px 35px;">Tiếp tục mua hàng</a>
      </div>
    </div>

  <?php else: ?>
    <div class="checkout-layout">
      
      <div class="card">
        <div class="card-title">👤 Thông tin giao hàng</div>
        
        <?php foreach ($errors as $e): ?>
          <div class="alert">⚠️ <?= $e ?></div>
        <?php endforeach; ?>

        <form method="POST" action="checkout.php">
          <div class="form-group">
            <label>Họ và tên người nhận <span style="color:red">*</span></label>
            <input type="text" name="fullname" placeholder="Nhập đầy đủ họ tên" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>"/>
          </div>
          <div class="form-group">
            <label>Số điện thoại di động <span style="color:red">*</span></label>
            <input type="text" name="phone" placeholder="Nhập số điện thoại nhận cuộc gọi giao hàng" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"/>
          </div>
          <div class="form-group">
            <label>Địa chỉ nhận hàng chuẩn xác <span style="color:red">*</span></label>
            <input type="text" name="address" placeholder="Số nhà, tên đường, phường/xã, quận/huyện..." value="<?= htmlspecialchars($_POST['address'] ?? '') ?>"/>
          </div>
          <div class="form-group">
            <label>Ghi chú đơn hàng (Nếu có)</label>
            <textarea name="notes" placeholder="Lời nhắn gửi tới cửa hàng hoặc shipper..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
          </div>

          <div class="form-group" style="margin-bottom: 18px;">
            <label>Phương thức thanh toán <span style="color:red">*</span></label>
            <div style="display: flex; gap: 24px; margin-top: 6px; background: #f8fafc; padding: 12px; border-radius: 9px; border: 1px solid #e5e7eb;">
              <label style="font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; color: #374151;">
                <input type="radio" name="payment_method" value="cod" checked onchange="toggleFormBankInfo(false)"/> COD (Nhận hàng thanh toán)
              </label>
              <label style="font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; color: #374151;">
                <input type="radio" name="payment_method" value="bank" onchange="toggleFormBankInfo(true)"/> Chuyển khoản ngân hàng
              </label>
            </div>
          </div>

          <div id="form-bank-preview" style="display: none; background: #f0fdf4; border: 1px dashed #16a34a; padding: 14px; border-radius: 9px; margin-bottom: 16px; font-size: 13.5px;">
            <p style="font-weight: 700; color: #166534; margin-bottom: 4px;">🏦 Hệ thống chuyển khoản:</p>
            <p>Sau khi nhấn Xác nhận đơn hàng, hệ thống sẽ cấp cho bạn một <strong>Mã đơn hàng riêng biệt</strong> cùng thông tin Số tài khoản MB Bank chi tiết để bạn tiến hành quét mã / chuyển khoản.</p>
          </div>
          
          <button type="submit" class="btn btn-success">🔔 Xác nhận đặt đơn hàng</button>
          <a href="cart.php" class="btn btn-secondary"> Quay lại sửa giỏ hàng</a>
        </form>
      </div>

      <div class="card" style="height: fit-content;">
        <div class="card-title">📦 Tóm tắt đơn hàng</div>
        
        <div style="max-height: 250px; overflow-y: auto; margin-bottom: 15px;">
          <?php foreach ($cart_items as $item): ?>
            <div class="summary-item">
              <span style="max-width: 70%; font-weight: 600; color: #374151;"><?= htmlspecialchars($item['tenSP']) ?> <span style="color: #6b7280; font-weight: normal;">x<?= $item['qty'] ?></span></span>
              <span style="font-weight: 700;"><?= number_format($item['subtotal'], 0, ',', '.') ?>đ</span>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 16px; font-weight: 700; padding-top: 10px; border-top: 2px solid #e5e7eb;">
          <span>Tổng tiền thanh toán:</span>
          <span style="color: #dc2626; font-size: 20px; font-weight: 800;"><?= number_format($total_all, 0, ',', '.') ?>đ</span>
        </div>
      </div>

    </div>
  <?php endif; ?>
</div>

<script>
// Hàm Javascript ẩn/hiện thông báo chuyển khoản khi thay đổi radio button trên Form nhập liệu
function toggleFormBankInfo(show) {
    var previewBox = document.getElementById('form-bank-preview');
    if (previewBox) {
        previewBox.style.display = show ? 'block' : 'none';
    }
}
</script>
</body>
</html> -->