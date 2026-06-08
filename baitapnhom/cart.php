<?php
session_start();
$host = '127.0.0.1';
$db = 'lab4';
$user = 'root';
$pass = '';
try {
  $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
  $pdo->exec("CREATE TABLE IF NOT EXISTS orders (order_id VARCHAR(50) PRIMARY KEY, fullname VARCHAR(100) NOT NULL, phone VARCHAR(20) NOT NULL, email VARCHAR(150) NOT NULL, address VARCHAR(255) NOT NULL, note TEXT, payment VARCHAR(50) NOT NULL, total INT NOT NULL, status VARCHAR(50) DEFAULT 'Đã đặt', created_at VARCHAR(50) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
  $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (id INT AUTO_INCREMENT PRIMARY KEY, order_id VARCHAR(50) NOT NULL, product_id VARCHAR(20) NOT NULL, quantity INT NOT NULL, price INT NOT NULL, description TEXT, FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {
  die("Lỗi kết nối CSDL: " . $e->getMessage());
}

function h($v)
{
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function parsePrice(string $raw): float
{
  $clean = preg_replace('/[₫đĐ\s]/u', '', $raw);
  if (strpos($clean, ',') !== false && strpos($clean, '.') === false)
    $clean = str_replace(',', '', $clean);
  elseif (preg_match('/\.\d{3}$/', $clean) && substr_count($clean, '.') === 1)
    $clean = str_replace('.', '', $clean);
  else
    $clean = str_replace(',', '', $clean);
  return (float) $clean;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'add_to_cart') {
    $pid = trim($_POST['product_id'] ?? '');
    $qty = (int) ($_POST['qty'] ?? 1);
    if (empty($pid))
      exit(json_encode(['ok' => false, 'msg' => 'Thiếu ID sản phẩm.']));
    if ($qty < 1 || $qty > 99)
      exit(json_encode(['ok' => false, 'msg' => 'Số lượng phải từ 1–99.']));

    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$pid]);
    if (!$product = $stmt->fetch(PDO::FETCH_ASSOC))
      exit(json_encode(['ok' => false, 'msg' => 'Sản phẩm không tồn tại.']));

    if (!isset($_SESSION['cart']))
      $_SESSION['cart'] = [];
    if (isset($_SESSION['cart'][$pid])) {
      $_SESSION['cart'][$pid]['qty'] = min(99, $_SESSION['cart'][$pid]['qty'] + $qty);
    } else {
      $_SESSION['cart'][$pid] = ['product_id' => $product['product_id'], 'description' => $product['description'], 'price_raw' => $product['price'], 'price_num' => parsePrice($product['price']), 'image' => $product['image'], 'qty' => $qty];
    }
    exit(json_encode(['ok' => true, 'msg' => 'Đã thêm vào giỏ hàng!', 'cart_count' => count($_SESSION['cart'])]));
  }
  if ($action === 'update_qty') {
    $pid = trim($_POST['product_id'] ?? '');
    $qty = (int) ($_POST['qty'] ?? 1);
    if (!isset($_SESSION['cart'][$pid]) || $qty < 1 || $qty > 99)
      exit(json_encode(['ok' => false, 'msg' => 'Dữ liệu không hợp lệ.']));
    $_SESSION['cart'][$pid]['qty'] = $qty;
    exit(json_encode(['ok' => true]));
  }
  if ($action === 'remove_item') {
    unset($_SESSION['cart'][trim($_POST['product_id'] ?? '')]);
    exit(json_encode(['ok' => true]));
  }
  if ($action === 'clear_cart') {
    $_SESSION['cart'] = [];
    exit(json_encode(['ok' => true]));
  }
  if ($action === 'place_order') {
    $errors = [];
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $payment = trim($_POST['payment'] ?? '');
    if (empty($fullname) || mb_strlen($fullname) < 2 || mb_strlen($fullname) > 100 || !preg_match('/^[\p{L}\s\'\-\.]+$/u', $fullname))
      $errors['fullname'] = 'Họ tên không hợp lệ.';
    if (!preg_match('/^(0|\+84)[3|5|7|8|9][0-9]{8}$/', $phone))
      $errors['phone'] = 'Số điện thoại không hợp lệ.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150)
      $errors['email'] = 'Email không hợp lệ.';
    if (empty($address) || mb_strlen($address) < 10 || mb_strlen($address) > 255)
      $errors['address'] = 'Địa chỉ từ 10-255 ký tự.';
    if (!in_array($payment, ['cod', 'bank', 'momo']))
      $errors['payment'] = 'Chọn phương thức thanh toán.';
    if (empty($_SESSION['cart']))
      $errors['cart'] = 'Giỏ hàng trống.';
    if (mb_strlen($note) > 500)
      $errors['note'] = 'Ghi chú tối đa 500 ký tự.';
    if (!empty($errors))
      exit(json_encode(['ok' => false, 'errors' => $errors]));
    //Thanh toán
    $total = 0;
    $items = [];
    foreach ($_SESSION['cart'] as $item) {
      $total += $item['price_num'] * $item['qty'];
      $items[] = $item['description'] . ' x' . $item['qty'];
    }
    $orderId = 'NM' . rand(100000, 999999);
    $createdAt = date('j/n/Y');
    try {
      $pdo->beginTransaction();
      $pdo->prepare("INSERT INTO orders VALUES (?,?,?,?,?,?,?,?,?,?)")->execute([$orderId, $fullname, $phone, $email, $address, $note, $payment, $total, 'Đã đặt', $createdAt]);
      $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, description) VALUES (?, ?, ?, ?, ?)");
      foreach ($_SESSION['cart'] as $item)
        $stmtItem->execute([$orderId, $item['product_id'], $item['qty'], $item['price_num'], $item['description']]);
      $pdo->commit();
      $_SESSION['last_order'] = ['order_id' => $orderId, 'fullname' => $fullname, 'phone' => $phone, 'email' => $email, 'address' => $address, 'note' => $note, 'payment' => $payment, 'items' => $items, 'total' => $total, 'status' => 'Đã đặt', 'created_at' => $createdAt];
      $_SESSION['cart'] = [];
      exit(json_encode(['ok' => true, 'order_id' => $orderId]));
    } catch (Exception $e) {
      $pdo->rollBack();
      exit(json_encode(['ok' => false, 'errors' => ['server' => $e->getMessage()]]));
    }
  }
}
if (isset($_GET['action']) && $_GET['action'] === 'cart_count')
  exit(json_encode(['count' => count($_SESSION['cart'] ?? [])]));

$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$search = trim($_GET['search'] ?? '');
$params = [];
$searchQuery = '';
if ($search !== '') {
  $searchQuery = ' WHERE product_id LIKE ? OR description LIKE ?';
  $params = ["%$search%", "%$search%"];
}
$limit = 6;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM products" . $searchQuery);
$stmtCount->execute($params);
$totalProducts = $stmtCount->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

$stmt = $pdo->prepare("SELECT * FROM products" . $searchQuery . " ORDER BY product_id DESC LIMIT ? OFFSET ?");
foreach ($params as $i => $p)
  $stmt->bindValue($i + 1, $p);
$stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
$cart = $_SESSION['cart'] ?? [];
$cartCount = count($cart);
$cartTotal = 0;
foreach ($cart as $item)
  $cartTotal += $item['price_num'] * $item['qty'];
$lastOrder = $_SESSION['last_order'] ?? null;
if ($lastOrder)
  unset($_SESSION['last_order']);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cửa Hàng – Giỏ Hàng &amp; Thanh Toán</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap"
    rel="stylesheet">
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0
    }

    :root {
      --bg: #f8f5f0;
      --card: #fff;
      --text: #1a1a1a;
      --muted: #6b6b6b;
      --border: #e0d9d0;
      --accent: #c0392b;
      --accent-dk: #a52a1f;
      --green: #1a7a4a;
      --gold: #c9961a;
      --blue: #2563eb;
      --shadow: 0 4px 20px rgba(0, 0, 0, .08);
      --radius: 14px
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh
    }

    .topbar {
      position: sticky;
      top: 0;
      z-index: 900;
      background: var(--text);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: between;
      padding: 0 28px;
      height: 60px;
      box-shadow: 0 2px 12px rgba(0, 0, 0, .25)
    }

    .topbar-brand {
      font-family: 'Playfair Display', serif;
      font-size: 22px;
      letter-spacing: .5px
    }

    .topbar-nav {
      display: flex;
      gap: 12px;
      align-items: center
    }

    .topbar-nav a {
      color: rgba(255, 255, 255, .75);
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      transition: color .2s
    }

    .topbar-nav a:hover {
      color: #fff
    }

    .cart-btn {
      position: relative;
      background: var(--accent);
      border: none;
      border-radius: 8px;
      color: #fff;
      cursor: pointer;
      font-size: 14px;
      font-weight: 600;
      padding: 8px 18px;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: background .2s
    }

    .cart-btn:hover {
      background: var(--accent-dk)
    }

    .cart-badge {
      position: absolute;
      top: -6px;
      right: -6px;
      background: var(--gold);
      color: #fff;
      font-size: 11px;
      font-weight: 700;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transform: scale(0);
      transition: all .25s cubic-bezier(.34, 1.56, .64, 1)
    }

    .cart-badge.show {
      opacity: 1;
      transform: scale(1)
    }

    .main {
      max-width: 1200px;
      margin: 0 auto;
      padding: 32px 20px 80px
    }

    .page-title {
      font-family: 'Playfair Display', serif;
      font-size: 32px;
      margin-bottom: 4px
    }

    .page-sub {
      color: var(--muted);
      margin-bottom: 28px
    }

    .search-row {
      display: flex;
      gap: 10px;
      margin-bottom: 32px;
      max-width: 500px
    }

    .search-row input {
      flex: 1;
      padding: 10px 16px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-size: 15px;
      background: var(--card);
      outline: none;
      transition: border .2s
    }

    .search-row input:focus {
      border-color: var(--accent)
    }

    .btn {
      padding: 10px 20px;
      border-radius: 10px;
      border: none;
      cursor: pointer;
      font-family: inherit;
      font-weight: 600;
      font-size: 14px;
      transition: all .2s
    }

    .btn-primary {
      background: var(--accent);
      color: #fff
    }

    .btn-primary:hover {
      background: var(--accent-dk)
    }

    .btn-secondary {
      background: var(--border);
      color: var(--text)
    }

    .btn-secondary:hover {
      background: #d0c8be
    }

    .btn-success {
      background: var(--green);
      color: #fff
    }

    .btn-success:hover {
      background: #156038
    }

    .product-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 24px;
      margin-bottom: 40px
    }

    .product-card {
      background: var(--card);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: var(--shadow);
      transition: transform .25s, box-shadow .25s
    }

    .product-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 12px 32px rgba(0, 0, 0, .13)
    }

    .product-card img {
      width: 100%;
      height: 190px;
      object-fit: cover;
      display: block;
      background: #eee
    }

    .card-body {
      padding: 16px
    }

    .card-id {
      font-size: 12px;
      color: var(--muted);
      margin-bottom: 4px
    }

    .card-desc {
      font-size: 15px;
      line-height: 1.5;
      margin-bottom: 10px
    }

    .card-price {
      font-size: 20px;
      font-weight: 700;
      color: var(--accent);
      margin-bottom: 14px
    }

    .card-actions {
      display: flex;
      gap: 8px
    }

    .btn-cart {
      flex: 1;
      padding: 10px;
      border: none;
      border-radius: 10px;
      background: var(--accent);
      color: #fff;
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
      transition: background .2s
    }

    .btn-cart:hover {
      background: var(--accent-dk)
    }

    .btn-detail-sm {
      padding: 10px 14px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      background: transparent;
      color: var(--muted);
      font-size: 14px;
      cursor: pointer;
      transition: all .2s
    }

    .btn-detail-sm:hover {
      border-color: var(--text);
      color: var(--text)
    }

    .pagination {
      display: flex;
      justify-content: center;
      gap: 8px;
      flex-wrap: wrap;
      margin-bottom: 16px
    }

    .pagination a,
    .pagination span {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 40px;
      height: 40px;
      padding: 0 12px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 15px;
      text-decoration: none
    }

    .pagination a {
      background: var(--card);
      color: var(--blue);
      border: 1.5px solid var(--border);
      transition: all .2s
    }

    .pagination a:hover {
      background: var(--blue);
      color: #fff;
      border-color: var(--blue)
    }

    .pagination .pg-active {
      background: var(--blue);
      color: #fff;
      border: 1.5px solid var(--blue)
    }

    .pagination .pg-disabled {
      background: #e8e2db;
      color: #b0a898;
      cursor: not-allowed;
      border: 1.5px solid var(--border)
    }

    .page-info {
      text-align: center;
      color: var(--muted);
      font-size: 14px;
      margin-bottom: 24px
    }

    .toast-wrap {
      position: fixed;
      bottom: 28px;
      right: 28px;
      z-index: 9999;
      display: flex;
      flex-direction: column;
      gap: 10px
    }

    .toast {
      background: #1a1a1a;
      color: #fff;
      padding: 12px 20px;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 500;
      max-width: 300px;
      transform: translateX(120%);
      opacity: 0;
      transition: all .3s cubic-bezier(.34, 1.56, .64, 1);
      border-left: 4px solid var(--green)
    }

    .toast.toast-error {
      border-left-color: var(--accent)
    }

    .toast.show {
      transform: translateX(0);
      opacity: 1
    }

    .overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .55);
      z-index: 1000;
      justify-content: center;
      align-items: center;
      padding: 16px
    }

    .overlay.active {
      display: flex
    }

    .modal-box {
      background: var(--card);
      border-radius: 18px;
      width: 100%;
      max-width: 540px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
      animation: popIn .25s cubic-bezier(.34, 1.56, .64, 1)
    }

    @keyframes popIn {
      from {
        transform: scale(.88);
        opacity: 0
      }

      to {
        transform: scale(1);
        opacity: 1
      }
    }

    .modal-hdr {
      padding: 18px 24px;
      border-bottom: 1.5px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between
    }

    .modal-hdr h3 {
      font-family: 'Playfair Display', serif;
      font-size: 20px
    }

    .modal-close-btn {
      background: none;
      border: none;
      cursor: pointer;
      font-size: 24px;
      line-height: 1;
      color: var(--muted);
      transition: color .2s
    }

    .modal-close-btn:hover {
      color: var(--accent)
    }

    .modal-body {
      padding: 24px
    }

    .modal-footer {
      padding: 16px 24px;
      border-top: 1.5px solid var(--border);
      display: flex;
      justify-content: flex-end;
      gap: 10px
    }

    .cart-empty {
      text-align: center;
      padding: 40px 0;
      color: var(--muted)
    }

    .cart-table {
      width: 100%;
      border-collapse: collapse
    }

    .cart-table th {
      text-align: left;
      padding: 8px 10px;
      font-size: 13px;
      color: var(--muted);
      border-bottom: 1.5px solid var(--border)
    }

    .cart-table td {
      padding: 12px 10px;
      vertical-align: middle;
      border-bottom: 1px solid var(--border);
      font-size: 14px
    }

    .cart-table tr:last-child td {
      border-bottom: none
    }

    .cart-img {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 8px
    }

    .qty-input {
      width: 58px;
      text-align: center;
      padding: 5px;
      border: 1.5px solid var(--border);
      border-radius: 7px;
      font-size: 14px;
      font-family: inherit
    }

    .qty-input:focus {
      outline: none;
      border-color: var(--accent)
    }

    .cart-remove {
      background: none;
      border: none;
      color: var(--accent);
      cursor: pointer;
      font-size: 18px
    }

    .cart-total-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 14px 0 0;
      font-size: 17px;
      font-weight: 700
    }

    .cart-total-row span:last-child {
      color: var(--accent);
      font-size: 22px
    }

    .form-group {
      margin-bottom: 18px
    }

    .form-group label {
      display: block;
      font-weight: 600;
      font-size: 14px;
      margin-bottom: 6px
    }

    .form-group label span.req {
      color: var(--accent)
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 10px 14px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-size: 15px;
      font-family: inherit;
      background: var(--bg);
      outline: none;
      transition: border .2s
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
      border-color: var(--accent);
      background: #fff
    }

    .form-group textarea {
      min-height: 80px;
      resize: vertical
    }

    .field-err {
      margin-top: 5px;
      font-size: 12px;
      color: var(--accent);
      display: none;
      align-items: center;
      gap: 4px
    }

    .field-err.show {
      display: flex
    }

    .field-err::before {
      content: '⚠'
    }

    .input-error {
      border-color: var(--accent) !important;
      background: #fff5f5 !important
    }

    .pay-options {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px
    }

    .pay-option {
      position: relative
    }

    .pay-option input[type="radio"] {
      position: absolute;
      opacity: 0;
      width: 0;
      height: 0
    }

    .pay-label {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 14px 10px;
      border: 2px solid var(--border);
      border-radius: 10px;
      cursor: pointer;
      transition: all .2s;
      text-align: center;
      font-size: 14px;
      font-weight: 600;
      background: var(--bg)
    }

    .pay-option input:checked+.pay-label {
      border-color: var(--accent);
      background: #fff2f0;
      color: var(--accent)
    }

    .success-box {
      text-align: center;
      padding: 10px 0 20px
    }

    .success-box h3 {
      font-family: 'Playfair Display', serif;
      font-size: 24px;
      margin-bottom: 8px
    }

    .success-box p {
      color: var(--muted);
      margin-bottom: 6px
    }

    .order-detail-grid {
      background: var(--bg);
      border-radius: 10px;
      padding: 16px;
      margin: 18px 0;
      text-align: left
    }

    .order-detail-grid div {
      display: flex;
      gap: 8px;
      margin-bottom: 8px;
      font-size: 14px;
      line-height: 1.5
    }

    .order-detail-grid div:last-child {
      margin-bottom: 0
    }

    .order-detail-grid b {
      min-width: 120px;
      color: var(--muted);
      font-weight: 600
    }

    .detail-img {
      width: 100%;
      height: 220px;
      object-fit: cover;
      border-radius: 10px;
      margin-bottom: 16px
    }

    .detail-price {
      font-size: 26px;
      font-weight: 700;
      color: var(--accent);
      margin-bottom: 16px
    }

    .detail-desc {
      color: var(--muted);
      font-size: 15px;
      line-height: 1.6;
      margin-bottom: 20px
    }

    .qty-row {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 20px
    }

    .qty-btn {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      border: 1.5px solid var(--border);
      background: var(--bg);
      font-size: 20px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all .2s
    }

    .qty-btn:hover {
      border-color: var(--accent);
      color: var(--accent)
    }

    .qty-display {
      width: 50px;
      text-align: center;
      font-size: 16px;
      font-weight: 600
    }

    @media (max-width:600px) {
      .topbar {
        padding: 0 14px
      }

      .main {
        padding: 20px 14px 60px
      }

      .pay-options {
        grid-template-columns: 1fr 1fr
      }

      .cart-table th:nth-child(3),
      .cart-table td:nth-child(3) {
        display: none
      }
    }
  </style>
</head>

<body>

  <header class="topbar">
    <span class="topbar-brand">Shop</span>
    <nav class="topbar-nav">
      <a href="DonHang.php">Đơn Hàng</a> <a href="lab4.php">Quản lý</a>
      <button class="cart-btn" onclick="openCart()">Giỏ hàng <span
          class="cart-badge <?= $cartCount > 0 ? 'show' : '' ?>" id="cartBadge"><?= $cartCount ?></span></button>
    </nav>
  </header>

  <main class="main">
    <h1 class="page-title">Danh sách sản phẩm</h1>
    <p class="page-sub">Chọn sản phẩm và thêm vào giỏ hàng</p>

    <form class="search-row" method="GET">
      <input type="text" name="search" placeholder="Tìm kiếm sản phẩm…" value="<?= h($search) ?>">
      <button class="btn btn-primary" type="submit">Tìm</button>
      <?php if ($search !== ''): ?><a href="cart.php" class="btn btn-secondary">Xóa</a><?php endif; ?>
    </form>

    <div class="product-grid">
      <?php if (count($products) > 0):
        foreach ($products as $p): ?>
          <div class="product-card">
            <img src="<?= h($baseUrl . '/' . ltrim($p['image'], '/')) ?>" alt="<?= h($p['description']) ?>"
              onerror="this.src='https://via.placeholder.com/260x190?text=No+Image'">
            <div class="card-body">
              <p class="card-id">ID: <?= h($p['product_id']) ?></p>
              <p class="card-desc"><?= h($p['description']) ?></p>
              <p class="card-price"><?= h($p['price']) ?></p>
              <div class="card-actions">
                <button class="btn-cart"
                  onclick="openAddToCartModal('<?= h(addslashes($p['product_id'])) ?>','<?= h(addslashes($p['description'])) ?>','<?= h(addslashes($p['price'])) ?>','<?= h($baseUrl . '/' . ltrim($p['image'], '/')) ?>')">Thêm
                  vào giỏ</button>
                <button class="btn-detail-sm"
                  onclick="openDetail('<?= h(addslashes($p['product_id'])) ?>','<?= h(addslashes($p['description'])) ?>','<?= h(addslashes($p['price'])) ?>','<?= h($baseUrl . '/' . ltrim($p['image'], '/')) ?>',true)">Chi
                  tiết</button>
              </div>
            </div>
          </div>
        <?php endforeach; else: ?>
        <p style="grid-column:1/-1;text-align:center;color:var(--muted);padding:40px 0">Không tìm thấy sản phẩm nào.</p>
      <?php endif; ?>
    </div>

    <?php if ($totalPages > 1):
      $sp = $search !== '' ? '&search=' . urlencode($search) : ''; ?>
      <div class="pagination">
        <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?><?= $sp ?>">&#9664;</a><?php else: ?><span
            class="pg-disabled">&#9664;</span><?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <span class="<?= $i == $page ? 'pg-active' : '' ?>"><?= $i == $page ? $i : "<a href='?page=$i$sp'>$i</a>" ?></span>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?><a href="?page=<?= $page + 1 ?><?= $sp ?>">&#9654;</a><?php else: ?><span
            class="pg-disabled">&#9654;</span><?php endif; ?>
      </div>
      <p class="page-info">Trang <?= $page ?> / <?= $totalPages ?> (<?= $totalProducts ?> sản phẩm)</p>
    <?php endif; ?>
  </main>

  <div class="overlay" id="detailOverlay" onclick="closeOnOverlay(event,'detailOverlay')">
    <div class="modal-box">
      <div class="modal-hdr">
        <h3 id="detailTitle">Chi tiết sản phẩm</h3><button class="modal-close-btn"
          onclick="closeModal('detailOverlay')">&times;</button>
      </div>
      <div class="modal-body">
        <img id="detailImg" src="" class="detail-img">
        <p id="detailPrice" class="detail-price"></p>
        <p id="detailDesc" class="detail-desc"></p>
        <div class="qty-row" id="modalQtyRow" style="display:none;"><span>Số lượng:</span><button class="qty-btn"
            onclick="changeQty(-1)">−</button><span class="qty-display" id="detailQty">1</span><button class="qty-btn"
            onclick="changeQty(1)">+</button></div>
      </div>
      <div class="modal-footer"><button class="btn btn-secondary"
          onclick="closeModal('detailOverlay')">Đóng</button><button class="btn btn-primary" id="addToCartBtn"
          onclick="addToCart()">Thêm vào giỏ</button></div>
    </div>
  </div>

  <div class="overlay" id="cartOverlay" onclick="closeOnOverlay(event,'cartOverlay')">
    <div class="modal-box" style="max-width:680px">
      <div class="modal-hdr">
        <h3>Giỏ hàng</h3><button class="modal-close-btn" onclick="closeModal('cartOverlay')">&times;</button>
      </div>
      <div class="modal-body" id="cartBody"></div>
      <div class="modal-footer" id="cartFooter"></div>
    </div>
  </div>

  <div class="overlay" id="checkoutOverlay" onclick="closeOnOverlay(event,'checkoutOverlay')">
    <div class="modal-box" style="max-width:560px">
      <div class="modal-hdr">
        <h3>Thông tin đặt hàng</h3><button class="modal-close-btn"
          onclick="closeModal('checkoutOverlay')">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group"><label>Họ và tên <span class="req">*</span></label><input type="text" id="f_fullname"
            placeholder="Nguyễn Văn A" maxlength="100" oninput="liveValidate('f_fullname')">
          <div class="field-err" id="err_f_fullname"></div>
        </div>
        <div class="form-group"><label>Số điện thoại <span class="req">*</span></label><input type="tel" id="f_phone"
            placeholder="0912345678" maxlength="15" oninput="liveValidate('f_phone')">
          <div class="field-err" id="err_f_phone"></div>
        </div>
        <div class="form-group"><label>Email <span class="req">*</span></label><input type="email" id="f_email"
            placeholder="example@email.com" maxlength="150" oninput="liveValidate('f_email')">
          <div class="field-err" id="err_f_email"></div>
        </div>
        <div class="form-group"><label>Địa chỉ giao hàng <span class="req">*</span></label><input type="text"
            id="f_address" placeholder="Số nhà, đường..." maxlength="255" oninput="liveValidate('f_address')">
          <div class="field-err" id="err_f_address"></div>
        </div>
        <div class="form-group"><label>Ghi chú đơn hàng</label><textarea id="f_note"
            placeholder="Giao giờ hành chính..." maxlength="500" oninput="liveValidate('f_note')"></textarea>
          <div class="field-err" id="err_f_note"></div>
        </div>
        <div class="form-group"><label>Phương thức thanh toán <span class="req">*</span></label>
          <div class="pay-options">
            <label class="pay-option"><input type="radio" name="payment" value="cod" checked><span
                class="pay-label">COD</span></label>
            <label class="pay-option"><input type="radio" name="payment" value="bank"><span class="pay-label">Chuyển
                khoản</span></label>
            <label class="pay-option"><input type="radio" name="payment" value="momo"><span
                class="pay-label">MoMo</span></label>
          </div>
          <div class="field-err" id="err_payment"></div>
        </div>
      </div>
      <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('checkoutOverlay')">Quay
          lại</button><button class="btn btn-success" onclick="placeOrder()" id="placeOrderBtn">Xác nhận đặt
          hàng</button></div>
    </div>
  </div>

  <div class="overlay" id="successOverlay">
    <div class="modal-box" style="max-width:480px">
      <div class="modal-body">
        <div class="success-box">
          <h3>Đặt hàng thành công!</h3>
          <p>Chúng tôi sẽ liên hệ sớm.</p>
          <div class="order-detail-grid" id="orderDetailBox"></div><button class="btn btn-primary"
            onclick="closeModal('successOverlay')">Tiếp tục mua sắm</button>
        </div>
      </div>
    </div>
  </div>

  <div class="toast-wrap" id="toastWrap"></div>

  <script>
    let currentProduct = null, detailQty = 1, cart = <?= json_encode(array_values($cart)) ?>;
    function toast(m, e = false) { const w = document.getElementById('toastWrap'), o = document.createElement('div'); o.className = 'toast' + (e ? ' toast-error' : ''); o.textContent = m; w.appendChild(o); requestAnimationFrame(() => o.classList.add('show')); setTimeout(() => { o.classList.remove('show'); setTimeout(() => o.remove(), 400); }, 3200); }
    function closeOnOverlay(e, id) { if (e.target === document.getElementById(id)) closeModal(id); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); document.body.style.overflow = ''; }
    function openModal(id) { document.getElementById(id).classList.add('active'); document.body.style.overflow = 'hidden'; }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') ['detailOverlay', 'cartOverlay', 'checkoutOverlay', 'successOverlay'].forEach(closeModal); });

    function updateBadge() { const b = document.getElementById('cartBadge'), c = cart.reduce((s, i) => s + i.qty, 0); b.textContent = c; c > 0 ? b.classList.add('show') : b.classList.remove('show'); }
    updateBadge();

    // Hàm xử lý mở Modal khi nhấn nút "Thêm vào giỏ" ngoài trang chủ
    function openAddToCartModal(id, desc, price, img) {
      currentProduct = { id, desc, price, img };
      detailQty = 1; // Reset số lượng về 1 mỗi lần mở

      document.getElementById('detailTitle').textContent = 'Chọn số lượng sản phẩm';
      document.getElementById('detailImg').src = img;
      document.getElementById('detailPrice').textContent = price;
      document.getElementById('detailDesc').textContent = desc;
      document.getElementById('detailQty').textContent = '1';

      // Hiển thị nút tăng/giảm và nút "Xác nhận thêm"
      document.getElementById('modalQtyRow').style.display = 'flex';
      const btnCart = document.getElementById('addToCartBtn');
      btnCart.style.display = '';
      btnCart.textContent = 'Xác nhận thêm vào giỏ';

      openModal('detailOverlay');
    }

    // Hàm xử lý mở Modal khi bấm nút "Chi tiết" ngoài trang chủ
    function openDetail(id, desc, price, img, isViewOnly = false) {
      currentProduct = { id, desc, price, img };
      detailQty = 1;

      document.getElementById('detailTitle').textContent = 'Chi tiết sản phẩm';
      document.getElementById('detailImg').src = img;
      document.getElementById('detailPrice').textContent = price;
      document.getElementById('detailDesc').textContent = desc;

      // Ẩn thanh tăng giảm và ẩn luôn nút thêm vào giỏ nếu chỉ là xem chi tiết
      document.getElementById('modalQtyRow').style.display = 'none';
      document.getElementById('addToCartBtn').style.display = 'none';

      openModal('detailOverlay');
    }

    // Hàm thay đổi số lượng trong Modal
    function changeQty(d) {
      detailQty = Math.max(1, Math.min(99, detailQty + d));
      document.getElementById('detailQty').textContent = detailQty;
    }

    // Hàm gửi request lên Server để thêm sản phẩm thực tế vào giỏ hàng
    async function addToCart() {
      if (!currentProduct) return;
      const btn = document.getElementById('addToCartBtn');
      btn.disabled = true;
      btn.textContent = 'Đang thêm…';

      const fd = new FormData();
      fd.append('action', 'add_to_cart');
      fd.append('product_id', currentProduct.id);
      fd.append('qty', detailQty);

      try {
        const res = await fetch('cart.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok) {
          const idx = cart.findIndex(i => i.product_id === currentProduct.id);
          if (idx >= 0) {
            cart[idx].qty = Math.min(99, cart[idx].qty + detailQty);
          } else {
            cart.push({
              product_id: currentProduct.id,
              description: currentProduct.desc,
              price_raw: currentProduct.price,
              price_num: parsePrice(currentProduct.price),
              image: currentProduct.img,
              qty: detailQty
            });
          }
          updateBadge();
          toast(data.msg);
          closeModal('detailOverlay');
        } else {
          toast(data.msg, true);
        }
      } catch {
        toast('Lỗi kết nối máy chủ.', true);
      } finally {
        btn.disabled = false;
        btn.textContent = 'Xác nhận thêm vào giỏ';
      }
    }

    function parsePrice(r) { let c = r.replace(/[₫đĐ\s]/g, ''); if (c.includes(',') && !c.includes('.')) c = c.replace(/,/g, ''); else if (/\.\d{3}$/.test(c) && (c.match(/\./g) || []).length === 1) c = c.replace(/\./g, ''); else c = c.replace(/,/g, ''); return parseFloat(c) || 0; }
    function fmtPrice(n) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(n); }
    function openCart() { renderCart(); openModal('cartOverlay'); }

    function renderCart() {
      const body = document.getElementById('cartBody'), footer = document.getElementById('cartFooter');
      if (!cart.length) { body.innerHTML = '<div class="cart-empty"><p>Giỏ hàng trống</p></div>'; footer.innerHTML = '<button class="btn btn-secondary" onclick="closeModal(\'cartOverlay\')">Đóng</button>'; return; }
      let total = 0, rows = '';
      cart.forEach(item => {
        let sub = item.price_num * item.qty; total += sub;
        rows += `<tr><td><img src="${item.image}" class="cart-img"></td><td><div>${escHtml(item.description)}</div></td><td>${escHtml(item.price_raw)}</td><td><input class="qty-input" type="number" min="1" max="99" value="${item.qty}" onchange="updateQty('${escHtml(item.product_id)}',this.value)"></td><td style="color:var(--accent);font-weight:700">${fmtPrice(sub)}</td><td><button class="cart-remove" onclick="removeItem('${escHtml(item.product_id)}')">Xóa</button></td></tr>`;
      });
      body.innerHTML = `<table class="cart-table"><thead><tr><th>Ảnh</th><th>Sản phẩm</th><th>Đơn giá</th><th>SL</th><th>Thành tiền</th><th></th></tr></thead><tbody>${rows}</tbody></table><div class="cart-total-row"><span>Tổng cộng:</span><span>${fmtPrice(total)}</span></div>`;
      footer.innerHTML = `<button class="btn btn-secondary" style="margin-right:auto" onclick="clearCart()">Xóa tất cả</button><button class="btn btn-secondary" onclick="closeModal(\'cartOverlay\')">Đóng</button><button class="btn btn-success" onclick="goCheckout()">Đặt hàng</button>`;
    }
    function escHtml(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }

    async function updateQty(pid, val) {
      let qty = parseInt(val); if (isNaN(qty) || qty < 1) qty = 1; if (qty > 99) qty = 99;
      const fd = new FormData(); fd.append('action', 'update_qty'); fd.append('product_id', pid); fd.append('qty', qty); await fetch('cart.php', { method: 'POST', body: fd });
      const idx = cart.findIndex(i => i.product_id === pid); if (idx >= 0) cart[idx].qty = qty; updateBadge(); renderCart();
    }
    async function removeItem(pid) { const fd = new FormData(); fd.append('action', 'remove_item'); fd.append('product_id', pid); await fetch('cart.php', { method: 'POST', body: fd }); cart = cart.filter(i => i.product_id !== pid); updateBadge(); renderCart(); toast('Đã xóa sản phẩm.'); }
    async function clearCart() { if (!confirm('Xóa giỏ hàng?')) return; const fd = new FormData(); fd.append('action', 'clear_cart'); await fetch('cart.php', { method: 'POST', body: fd }); cart = []; updateBadge(); renderCart(); }
    function goCheckout() { if (!cart.length) { toast('Giỏ hàng trống!', true); return; } closeModal('cartOverlay'); openModal('checkoutOverlay'); }

    const validators = {
      f_fullname: v => !v ? 'Nhập họ tên.' : (v.length < 2 ? 'Tối thiểu 2 ký tự.' : (v.length > 100 ? 'Tối đa 100 ký tự.' : (!/^[\p{L}\s'\-\.]+$/u.test(v) ? 'Chỉ chứa chữ cái.' : ''))),
      f_phone: v => !v ? 'Nhập SĐT.' : (!/^(0|\+84)[35789][0-9]{8}$/.test(v) ? 'SĐT không hợp lệ.' : ''),
      f_email: v => !v ? 'Nhập email.' : (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? 'Email không hợp lệ.' : ''),
      f_address: v => !v ? 'Nhập địa chỉ.' : (v.length < 10 ? 'Tối thiểu 10 ký tự.' : ''), f_note: v => v.length > 500 ? 'Tối đa 500 ký tự.' : ''
    };
    function liveValidate(id) { if (!validators[id]) return true; const el = document.getElementById(id), err = document.getElementById('err_' + id), msg = validators[id](el.value.trim()); err.textContent = msg; msg ? err.classList.add('show') : err.classList.remove('show'); msg ? el.add('input-error') : el.classList.remove('input-error'); return msg === ''; }
    function validateAll() { let ok = true;['f_fullname', 'f_phone', 'f_email', 'f_address', 'f_note'].forEach(id => { if (!liveValidate(id)) ok = false; }); return ok; }

    async function placeOrder() {
      if (!validateAll()) { toast('Kiểm tra lại thông tin!', true); return; }
      const btn = document.getElementById('placeOrderBtn'); btn.disabled = true; btn.textContent = 'Đang xử lý…';
      const fd = new FormData(); fd.append('action', 'place_order');['fullname', 'phone', 'email', 'address', 'note'].forEach(k => fd.append(k, document.getElementById('f_' + k).value.trim())); fd.append('payment', document.querySelector('input[name="payment"]:checked').value);
      try {
        const res = await fetch('cart.php', { method: 'POST', body: fd }), data = await res.json();
        if (data.ok) {
          cart = []; updateBadge(); closeModal('checkoutOverlay');
          showOrderSuccess({ order_id: data.order_id, fullname: document.getElementById('f_fullname').value.trim(), phone: document.getElementById('f_phone').value.trim(), email: document.getElementById('f_email').value.trim(), address: document.getElementById('f_address').value.trim(), payment: document.querySelector('input[name="payment"]:checked').value, created_at: new Date().toLocaleString('vi-VN') });
          ['f_fullname', 'f_phone', 'f_email', 'f_address', 'f_note'].forEach(id => document.getElementById(id).value = '');
        } else { toast('Lỗi hệ thống từ máy chủ.', true); }
      } catch { toast('Lỗi kết nối máy chủ.', true); } finally { btn.disabled = false; btn.textContent = 'Xác nhận đặt hàng'; }
    }
    const paymentLabels = { cod: 'Thu tiền khi nhận hàng (COD)', bank: 'Chuyển khoản', momo: 'Ví MoMo' };
    function showOrderSuccess(o) { document.getElementById('orderDetailBox').innerHTML = `<div><b>Mã đơn:</b><span style="color:var(--accent)">${escHtml(o.order_id)}</span></div><div><b>Họ tên:</b><span>${escHtml(o.fullname)}</span></div><div><b>Điện thoại:</b><span>${escHtml(o.phone)}</span></div><div><b>Địa chỉ:</b><span>${escHtml(o.address)}</span></div><div><b>Thanh toán:</b><span>${escHtml(paymentLabels[o.payment] || o.payment)}</span></div><div><b>Thời gian:</b><span>${escHtml(o.created_at)}</span></div>`; openModal('successOverlay'); }
<?php if ($lastOrder): ?>window.addEventListener('DOMContentLoaded', () => showOrderSuccess(<?= json_encode($lastOrder) ?>)); <?php endif; ?>
  </script>
</body>

</html>
<!-- test commit lab4 -->