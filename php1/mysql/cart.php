<?php
session_start();

$host = '127.0.0.1';
$db   = 'lab4';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}

function h($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

function parsePrice(string $raw): float {
    $clean = preg_replace('/[₫đĐ\s]/u', '', $raw);
    if (strpos($clean, ',') !== false && strpos($clean, '.') === false) {
        $clean = str_replace(',', '', $clean);
    } elseif (preg_match('/\.\d{3}$/', $clean) && substr_count($clean, '.') === 1) {
        $clean = str_replace('.', '', $clean);
    } else {
        $clean = str_replace(',', '', $clean);
    }
    return (float) $clean;
}

/* ── XỬ LÝ POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'add_to_cart') {
        $pid = trim($_POST['product_id'] ?? '');
        $qty = (int)($_POST['qty'] ?? 1);
        if (empty($pid)) { echo json_encode(['ok'=>false,'msg'=>'Thiếu ID sản phẩm.']); exit; }
        if ($qty < 1 || $qty > 99) { echo json_encode(['ok'=>false,'msg'=>'Số lượng phải từ 1–99.']); exit; }
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->execute([$pid]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) { echo json_encode(['ok'=>false,'msg'=>'Sản phẩm không tồn tại.']); exit; }
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        if (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]['qty'] = min(99, $_SESSION['cart'][$pid]['qty'] + $qty);
        } else {
            $_SESSION['cart'][$pid] = [
                'product_id'  => $product['product_id'],
                'description' => $product['description'],
                'price_raw'   => $product['price'],
                'price_num'   => parsePrice($product['price']),
                'image'       => $product['image'],
                'qty'         => $qty,
            ];
        }
        echo json_encode(['ok'=>true,'msg'=>'Đã thêm vào giỏ hàng!','cart_count'=>count($_SESSION['cart'])]);
        exit;
    }

    if ($action === 'update_qty') {
        $pid = trim($_POST['product_id'] ?? '');
        $qty = (int)($_POST['qty'] ?? 1);
        if (!isset($_SESSION['cart'][$pid])) { echo json_encode(['ok'=>false,'msg'=>'Không có trong giỏ.']); exit; }
        $qty = max(1, min(99, $qty));
        $_SESSION['cart'][$pid]['qty'] = $qty;
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'remove_item') {
        $pid = trim($_POST['product_id'] ?? '');
        unset($_SESSION['cart'][$pid]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'clear_cart') {
        $_SESSION['cart'] = [];
        echo json_encode(['ok'=>true]);
        exit;
    }

    /* ── PLACE ORDER ── */
    if ($action === 'place_order') {
        $errors = [];

        $fullname = trim($_POST['fullname'] ?? '');
        $phone    = trim($_POST['phone']    ?? '');
        $email    = trim($_POST['email']    ?? '');
        $address  = trim($_POST['address']  ?? '');
        $note     = trim($_POST['note']     ?? '');
        $payment  = trim($_POST['payment']  ?? '');

        if (empty($fullname) || mb_strlen($fullname) < 2)
            $errors['fullname'] = 'Vui lòng nhập họ tên (ít nhất 2 ký tự).';

        if (empty($phone) || !preg_match('/^(0|\+84)[35789][0-9]{8}$/', $phone))
            $errors['phone'] = 'Số điện thoại không hợp lệ (VD: 0912345678).';

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors['email'] = 'Địa chỉ email không hợp lệ.';

        if (empty($address) || mb_strlen($address) < 10)
            $errors['address'] = 'Địa chỉ phải ít nhất 10 ký tự.';

        if (!in_array($payment, ['cod','bank','momo']))
            $errors['payment'] = 'Vui lòng chọn phương thức thanh toán.';

        // Đọc cart từ JS gửi lên (không phụ thuộc session)
        $cartData = json_decode($_POST['cart_data'] ?? '[]', true);
        if (empty($cartData)) {
            // fallback sang session
            $cartData = array_values($_SESSION['cart'] ?? []);
        }
        if (empty($cartData))
            $errors['cart'] = 'Giỏ hàng trống, không thể đặt hàng.';

        if (!empty($errors)) {
            echo json_encode(['ok'=>false,'errors'=>$errors]);
            exit;
        }

        // Tính tổng
        $total = 0;
        foreach ($cartData as $item) {
            $priceNum = isset($item['price_num']) ? (float)$item['price_num'] : parsePrice($item['price_raw'] ?? '0');
            $total += $priceNum * (int)($item['qty'] ?? 1);
        }

        // Tạo bảng nếu chưa có
        $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            order_id   VARCHAR(20) NOT NULL UNIQUE,
            fullname   VARCHAR(100) NOT NULL,
            phone      VARCHAR(20) NOT NULL,
            email      VARCHAR(150) NOT NULL,
            address    VARCHAR(255) NOT NULL,
            note       TEXT,
            payment    VARCHAR(20) NOT NULL,
            total      DECIMAL(15,2) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            order_id    VARCHAR(20) NOT NULL,
            product_id  VARCHAR(50) NOT NULL,
            description TEXT,
            price_num   DECIMAL(15,2) NOT NULL,
            qty         INT NOT NULL,
            subtotal    DECIMAL(15,2) NOT NULL,
            INDEX (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $orderId = 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 8));

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "INSERT INTO orders (order_id,fullname,phone,email,address,note,payment,total,created_at)
                 VALUES (?,?,?,?,?,?,?,?,NOW())"
            )->execute([$orderId,$fullname,$phone,$email,$address,$note,$payment,$total]);

            $stmtItem = $pdo->prepare(
                "INSERT INTO order_items (order_id,product_id,description,price_num,qty,subtotal)
                 VALUES (?,?,?,?,?,?)"
            );
            foreach ($cartData as $item) {
                $pNum = isset($item['price_num']) ? (float)$item['price_num'] : parsePrice($item['price_raw'] ?? '0');
                $qty  = (int)($item['qty'] ?? 1);
                $stmtItem->execute([$orderId, $item['product_id'], $item['description'], $pNum, $qty, $pNum*$qty]);
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['ok'=>false,'errors'=>['cart'=>'Lỗi DB: '.$e->getMessage()]]);
            exit;
        }

        $_SESSION['cart'] = [];

        echo json_encode(['ok'=>true,'order_id'=>$orderId,'total'=>$total]);
        exit;
    }

    echo json_encode(['ok'=>false,'msg'=>'Action không hợp lệ.']);
    exit;
}

/* ── GET CART COUNT ── */
if (isset($_GET['action']) && $_GET['action'] === 'cart_count') {
    header('Content-Type: application/json');
    echo json_encode(['count' => count($_SESSION['cart'] ?? [])]);
    exit;
}

/* ── LOAD SẢN PHẨM ── */
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl   = rtrim($scriptDir, '/');

$search      = trim($_GET['search'] ?? '');
$searchQuery = '';
$params      = [];
if ($search !== '') {
    $searchQuery = ' WHERE product_id LIKE ? OR description LIKE ?';
    $params[]    = "%$search%";
    $params[]    = "%$search%";
}

$limit  = 6;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM products" . $searchQuery);
$stmtCount->execute($params);
$totalProducts = $stmtCount->fetchColumn();
$totalPages    = max(1, ceil($totalProducts / $limit));

$sql  = "SELECT * FROM products" . $searchQuery . " ORDER BY product_id DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
foreach ($params as $i => $p) $stmt->bindValue($i+1, $p);
$stmt->bindValue(count($params)+1, $limit, PDO::PARAM_INT);
$stmt->bindValue(count($params)+2, $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cart      = $_SESSION['cart'] ?? [];
$cartCount = count($cart);
$cartTotal = 0;
foreach ($cart as $item) $cartTotal += $item['price_num'] * $item['qty'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cửa Hàng – Giỏ Hàng & Thanh Toán</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#f8f5f0;--card:#fff;--text:#1a1a1a;--muted:#6b6b6b;
  --border:#e0d9d0;--accent:#c0392b;--accent-dk:#a52a1f;
  --green:#1a7a4a;--gold:#c9961a;--blue:#2563eb;
  --shadow:0 4px 20px rgba(0,0,0,.08);--radius:14px
}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}

/* TOPBAR */
.topbar{position:sticky;top:0;z-index:900;background:var(--text);color:#fff;
  display:flex;align-items:center;justify-content:space-between;padding:0 28px;height:60px;
  box-shadow:0 2px 12px rgba(0,0,0,.25)}
.topbar-brand{font-family:'Playfair Display',serif;font-size:22px}
.topbar-nav{display:flex;gap:12px;align-items:center}
.topbar-nav a{color:rgba(255,255,255,.75);text-decoration:none;font-size:14px;font-weight:500;transition:color .2s}
.topbar-nav a:hover{color:#fff}
.cart-btn{position:relative;background:var(--accent);border:none;border-radius:8px;
  color:#fff;cursor:pointer;font-size:14px;font-weight:600;padding:8px 18px;
  display:flex;align-items:center;gap:6px;transition:background .2s}
.cart-btn:hover{background:var(--accent-dk)}
.cart-badge{position:absolute;top:-6px;right:-6px;background:var(--gold);color:#fff;
  font-size:11px;font-weight:700;width:20px;height:20px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  opacity:0;transform:scale(0);transition:all .25s cubic-bezier(.34,1.56,.64,1)}
.cart-badge.show{opacity:1;transform:scale(1)}

/* MAIN */
.main{max-width:1200px;margin:0 auto;padding:32px 20px 80px}
.page-title{font-family:'Playfair Display',serif;font-size:32px;margin-bottom:4px}
.page-sub{color:var(--muted);margin-bottom:28px}

/* SEARCH */
.search-row{display:flex;gap:10px;margin-bottom:32px;max-width:500px}
.search-row input{flex:1;padding:10px 16px;border:1.5px solid var(--border);
  border-radius:10px;font-size:15px;font-family:inherit;background:var(--card);
  outline:none;transition:border .2s}
.search-row input:focus{border-color:var(--accent)}
.btn{padding:10px 20px;border-radius:10px;border:none;cursor:pointer;font-family:inherit;font-weight:600;font-size:14px;transition:all .2s}
.btn-primary{background:var(--accent);color:#fff}
.btn-primary:hover{background:var(--accent-dk)}
.btn-secondary{background:var(--border);color:var(--text)}
.btn-secondary:hover{background:#d0c8be}
.btn-success{background:var(--green);color:#fff}
.btn-success:hover{background:#156038}

/* PRODUCT GRID */
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:24px;margin-bottom:40px}
.product-card{background:var(--card);border-radius:var(--radius);overflow:hidden;
  box-shadow:var(--shadow);transition:transform .25s,box-shadow .25s}
.product-card:hover{transform:translateY(-6px);box-shadow:0 12px 32px rgba(0,0,0,.13)}
.product-card img{width:100%;height:190px;object-fit:cover;display:block;background:#eee}
.card-body{padding:16px}
.card-id{font-size:12px;color:var(--muted);margin-bottom:4px}
.card-desc{font-size:15px;line-height:1.5;margin-bottom:10px}
.card-price{font-size:20px;font-weight:700;color:var(--accent);margin-bottom:14px}
.card-actions{display:flex;gap:8px}
.btn-cart{flex:1;padding:9px;border:2px solid var(--accent);border-radius:8px;
  background:transparent;color:var(--accent);font-weight:600;font-size:13px;cursor:pointer;transition:all .2s}
.btn-cart:hover{background:var(--accent);color:#fff}
.btn-detail-sm{padding:9px 14px;border:2px solid var(--border);border-radius:8px;
  background:transparent;color:var(--muted);font-size:13px;cursor:pointer;transition:all .2s}
.btn-detail-sm:hover{border-color:var(--text);color:var(--text)}

/* PAGINATION */
.pagination{display:flex;justify-content:center;gap:8px;flex-wrap:wrap;margin-bottom:16px}
.pagination a,.pagination span{display:inline-flex;align-items:center;justify-content:center;
  min-width:40px;height:40px;padding:0 12px;border-radius:8px;font-weight:600;font-size:15px;text-decoration:none}
.pagination a{background:var(--card);color:var(--blue);border:1.5px solid var(--border);transition:all .2s}
.pagination a:hover{background:var(--blue);color:#fff;border-color:var(--blue)}
.pagination .pg-active{background:var(--blue);color:#fff;border:1.5px solid var(--blue)}
.pagination .pg-disabled{background:#e8e2db;color:#b0a898;cursor:not-allowed;border:1.5px solid var(--border)}
.page-info{text-align:center;color:var(--muted);font-size:14px;margin-bottom:24px}

/* TOAST */
.toast-wrap{position:fixed;bottom:28px;right:28px;z-index:9999;display:flex;flex-direction:column;gap:10px}
.toast{background:#1a1a1a;color:#fff;padding:12px 20px;border-radius:10px;font-size:14px;font-weight:500;
  max-width:300px;transform:translateX(120%);opacity:0;
  transition:all .3s cubic-bezier(.34,1.56,.64,1);border-left:4px solid var(--green)}
.toast.toast-error{border-left-color:var(--accent)}
.toast.show{transform:translateX(0);opacity:1}

/* OVERLAY / MODAL */
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1000;
  justify-content:center;align-items:center;padding:16px}
.overlay.active{display:flex}
.modal-box{background:var(--card);border-radius:18px;width:100%;max-width:540px;
  max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.25);
  animation:popIn .25s cubic-bezier(.34,1.56,.64,1)}
@keyframes popIn{from{transform:scale(.88);opacity:0}to{transform:scale(1);opacity:1}}
.modal-hdr{padding:18px 24px;border-bottom:1.5px solid var(--border);
  display:flex;align-items:center;justify-content:space-between}
.modal-hdr h3{font-family:'Playfair Display',serif;font-size:20px}
.modal-close-btn{background:none;border:none;cursor:pointer;font-size:24px;line-height:1;color:var(--muted);transition:color .2s}
.modal-close-btn:hover{color:var(--accent)}
.modal-body{padding:24px}
.modal-footer{padding:16px 24px;border-top:1.5px solid var(--border);display:flex;justify-content:flex-end;gap:10px}

/* CART */
.cart-empty{text-align:center;padding:40px 0;color:var(--muted)}
.cart-empty svg{width:64px;height:64px;opacity:.35;margin-bottom:12px}
.cart-table{width:100%;border-collapse:collapse}
.cart-table th{text-align:left;padding:8px 10px;font-size:13px;color:var(--muted);border-bottom:1.5px solid var(--border)}
.cart-table td{padding:12px 10px;vertical-align:middle;border-bottom:1px solid var(--border);font-size:14px}
.cart-table tr:last-child td{border-bottom:none}
.cart-img{width:50px;height:50px;object-fit:cover;border-radius:8px}
.qty-input{width:58px;text-align:center;padding:5px;border:1.5px solid var(--border);border-radius:7px;font-size:14px;font-family:inherit}
.qty-input:focus{outline:none;border-color:var(--accent)}
.cart-remove{background:none;border:none;color:var(--accent);cursor:pointer;font-size:18px}
.cart-total-row{display:flex;justify-content:space-between;align-items:center;padding:14px 0 0;font-size:17px;font-weight:700}
.cart-total-row span:last-child{color:var(--accent);font-size:22px}

/* CHECKOUT FORM */
.form-group{margin-bottom:18px}
.form-group label{display:block;font-weight:600;font-size:14px;margin-bottom:6px}
.form-group label .req{color:var(--accent)}
.form-group input,.form-group textarea,.form-group select{
  width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;
  font-size:15px;font-family:inherit;background:var(--bg);outline:none;transition:border .2s}
.form-group input:focus,.form-group textarea:focus{border-color:var(--accent);background:#fff}
.form-group textarea{min-height:80px;resize:vertical}
.field-err{margin-top:5px;font-size:12px;color:var(--accent);display:none;align-items:center;gap:4px}
.field-err.show{display:flex}
.field-err::before{content:'⚠ '}
.input-error{border-color:var(--accent)!important;background:#fff5f5!important}

/* PAYMENT OPTIONS */
.pay-options{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.pay-option{position:relative}
.pay-option input[type="radio"]{position:absolute;opacity:0;width:0;height:0}
.pay-label{display:flex;flex-direction:column;align-items:center;padding:12px 8px;
  border:2px solid var(--border);border-radius:10px;cursor:pointer;transition:all .2s;
  text-align:center;font-size:13px;font-weight:600;gap:6px;background:var(--bg)}
.pay-option input:checked+.pay-label{border-color:var(--accent);background:#fff2f0;color:var(--accent)}
.pay-label svg{width:28px;height:28px}

/* SUCCESS */
.success-box{text-align:center;padding:10px 0 20px}
.success-icon{font-size:64px;margin-bottom:12px}
.success-box h3{font-family:'Playfair Display',serif;font-size:24px;margin-bottom:8px}
.success-box p{color:var(--muted);margin-bottom:6px}
.order-detail-grid{background:var(--bg);border-radius:10px;padding:16px;margin:18px 0;text-align:left}
.order-detail-grid div{display:flex;gap:8px;margin-bottom:8px;font-size:14px;line-height:1.5}
.order-detail-grid div:last-child{margin-bottom:0}
.order-detail-grid b{min-width:120px;color:var(--muted);font-weight:600}

/* DETAIL MODAL */
.detail-img{width:100%;height:220px;object-fit:cover;border-radius:10px;margin-bottom:16px}
.detail-price{font-size:26px;font-weight:700;color:var(--accent);margin-bottom:16px}
.detail-desc{color:var(--muted);font-size:15px;line-height:1.6;margin-bottom:20px}
.qty-row{display:flex;align-items:center;gap:10px;margin-bottom:20px}
.qty-btn{width:36px;height:36px;border-radius:8px;border:1.5px solid var(--border);
  background:var(--bg);font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s}
.qty-btn:hover{border-color:var(--accent);color:var(--accent)}
.qty-display{width:50px;text-align:center;font-size:16px;font-weight:600}

@media(max-width:600px){
  .topbar{padding:0 14px}
  .main{padding:20px 14px 60px}
  .pay-options{grid-template-columns:1fr 1fr}
}
</style>
</head>
<body>

<header class="topbar">
  <span class="topbar-brand">Shop</span>
  <nav class="topbar-nav">
    <a href="DonHang.php">Đơn hàng</a>
    <a href="lab4.php">Quản lý</a>
    <button class="cart-btn" onclick="openCart()">
      🛒 Giỏ hàng
      <span class="cart-badge <?= $cartCount>0?'show':'' ?>" id="cartBadge"><?= $cartCount ?></span>
    </button>
  </nav>
</header>

<main class="main">
  <h1 class="page-title">Danh sách sản phẩm</h1>
  <p class="page-sub">Chọn sản phẩm và thêm vào giỏ hàng</p>

  <form class="search-row" method="GET">
    <input type="text" name="search" placeholder="Tìm kiếm sản phẩm…" value="<?= h($search) ?>">
    <button class="btn btn-primary" type="submit">Tìm</button>
    <?php if ($search!==''): ?>
      <a href="cart.php" class="btn btn-secondary">Xóa</a>
    <?php endif; ?>
  </form>

  <div class="product-grid">
    <?php if (count($products)>0): ?>
      <?php foreach ($products as $p): ?>
      <div class="product-card">
        <img src="<?= h($baseUrl.'/'.ltrim($p['image'],'/')) ?>" alt="<?= h($p['description']) ?>"
             onerror="this.src='https://via.placeholder.com/260x190?text=No+Image'">
        <div class="card-body">
          <p class="card-id">ID: <?= h($p['product_id']) ?></p>
          <p class="card-desc"><?= h($p['description']) ?></p>
          <p class="card-price"><?= h($p['price']) ?></p>
          <div class="card-actions">
            <button class="btn-cart" onclick="openDetail('<?= h(addslashes($p['product_id'])) ?>','<?= h(addslashes($p['description'])) ?>','<?= h(addslashes($p['price'])) ?>','<?= h($baseUrl.'/'.ltrim($p['image'],'/')) ?>')">
              🛒 Thêm vào giỏ
            </button>
            <button class="btn-detail-sm" onclick="openDetail('<?= h(addslashes($p['product_id'])) ?>','<?= h(addslashes($p['description'])) ?>','<?= h(addslashes($p['price'])) ?>','<?= h($baseUrl.'/'.ltrim($p['image'],'/')) ?>',true)">👁</button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="grid-column:1/-1;text-align:center;color:var(--muted);padding:40px 0">Không tìm thấy sản phẩm nào.</p>
    <?php endif; ?>
  </div>

  <?php if ($totalPages>1):
    $sp = $search!==''?'&search='.urlencode($search):'';
  ?>
  <div class="pagination">
    <?php if ($page>1): ?><a href="?page=<?= $page-1 ?><?= $sp ?>">&#9664;</a>
    <?php else: ?><span class="pg-disabled">&#9664;</span><?php endif; ?>
    <?php for($i=1;$i<=$totalPages;$i++): ?>
      <?php if($i==$page): ?><span class="pg-active"><?= $i ?></span>
      <?php else: ?><a href="?page=<?= $i ?><?= $sp ?>"><?= $i ?></a>
      <?php endif; ?>
    <?php endfor; ?>
    <?php if ($page<$totalPages): ?><a href="?page=<?= $page+1 ?><?= $sp ?>">&#9654;</a>
    <?php else: ?><span class="pg-disabled">&#9654;</span><?php endif; ?>
  </div>
  <p class="page-info">Trang <?= $page ?> / <?= $totalPages ?> (<?= $totalProducts ?> sản phẩm)</p>
  <?php endif; ?>
</main>

<!-- MODAL: DETAIL -->
<div class="overlay" id="detailOverlay" onclick="closeOnOverlay(event,'detailOverlay')">
  <div class="modal-box">
    <div class="modal-hdr">
      <h3 id="detailTitle">Chi tiết sản phẩm</h3>
      <button class="modal-close-btn" onclick="closeModal('detailOverlay')">&times;</button>
    </div>
    <div class="modal-body">
      <img id="detailImg" src="" alt="" class="detail-img">
      <p id="detailPrice" class="detail-price"></p>
      <p id="detailDesc" class="detail-desc"></p>
      <div class="qty-row">
        <span style="font-weight:600;font-size:14px">Số lượng:</span>
        <button class="qty-btn" onclick="changeQty(-1)">−</button>
        <span class="qty-display" id="detailQty">1</span>
        <button class="qty-btn" onclick="changeQty(1)">+</button>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('detailOverlay')">Đóng</button>
      <button class="btn btn-primary" id="addToCartBtn" onclick="addToCart()">🛒 Thêm vào giỏ</button>
    </div>
  </div>
</div>

<!-- MODAL: CART -->
<div class="overlay" id="cartOverlay" onclick="closeOnOverlay(event,'cartOverlay')">
  <div class="modal-box" style="max-width:680px">
    <div class="modal-hdr">
      <h3>🛒 Giỏ hàng</h3>
      <button class="modal-close-btn" onclick="closeModal('cartOverlay')">&times;</button>
    </div>
    <div class="modal-body" id="cartBody"></div>
    <div class="modal-footer" id="cartFooter"></div>
  </div>
</div>

<!-- MODAL: CHECKOUT -->
<div class="overlay" id="checkoutOverlay" onclick="closeOnOverlay(event,'checkoutOverlay')">
  <div class="modal-box" style="max-width:560px">
    <div class="modal-hdr">
      <h3>📦 Thông tin đặt hàng</h3>
      <button class="modal-close-btn" onclick="closeModal('checkoutOverlay')">&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label>Họ và tên <span class="req">*</span></label>
        <input type="text" id="f_fullname" placeholder="Nguyễn Văn A" maxlength="100">
        <div class="field-err" id="err_fullname"></div>
      </div>
      <div class="form-group">
        <label>Số điện thoại <span class="req">*</span></label>
        <input type="tel" id="f_phone" placeholder="0912345678" maxlength="15">
        <div class="field-err" id="err_phone"></div>
      </div>
      <div class="form-group">
        <label>Email <span class="req">*</span></label>
        <input type="email" id="f_email" placeholder="example@email.com" maxlength="150">
        <div class="field-err" id="err_email"></div>
      </div>
      <div class="form-group">
        <label>Địa chỉ giao hàng <span class="req">*</span></label>
        <input type="text" id="f_address" placeholder="Số nhà, đường, phường/xã, tỉnh/thành" maxlength="255">
        <div class="field-err" id="err_address"></div>
      </div>
      <div class="form-group">
        <label>Ghi chú đơn hàng</label>
        <textarea id="f_note" placeholder="Giao giờ hành chính, để ở bảo vệ…" maxlength="500"></textarea>
        <div class="field-err" id="err_note"></div>
      </div>
      <div class="form-group">
        <label>Phương thức thanh toán <span class="req">*</span></label>
        <div class="pay-options">
          <label class="pay-option">
            <input type="radio" name="payment" value="cod">
            <span class="pay-label">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M2 10h20"/></svg>COD
            </span>
          </label>
          <label class="pay-option">
            <input type="radio" name="payment" value="bank">
            <span class="pay-label">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>Chuyển khoản
            </span>
          </label>
          <label class="pay-option">
            <input type="radio" name="payment" value="momo">
            <span class="pay-label">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>MoMo
            </span>
          </label>
        </div>
        <div class="field-err" id="err_payment"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('checkoutOverlay')">Quay lại</button>
      <button class="btn btn-success" id="placeOrderBtn" onclick="placeOrder()">✅ Xác nhận đặt hàng</button>
    </div>
  </div>
</div>

<!-- MODAL: SUCCESS -->
<div class="overlay" id="successOverlay">
  <div class="modal-box" style="max-width:480px">
    <div class="modal-body">
      <div class="success-box">
        <div class="success-icon">🎉</div>
        <h3>Đặt hàng thành công!</h3>
        <p>Cảm ơn bạn đã mua hàng. Chúng tôi sẽ liên hệ sớm.</p>
        <div class="order-detail-grid" id="orderDetailBox"></div>
        <button class="btn btn-primary" style="margin-top:12px" onclick="closeModal('successOverlay')">Tiếp tục mua sắm</button>
      </div>
    </div>
  </div>
</div>

<div class="toast-wrap" id="toastWrap"></div>

<script>
/* STATE */
let currentProduct = null;
let detailQty = 1;
let cart = <?= json_encode(array_values($cart)) ?>;

/* TOAST */
function toast(msg, isError=false) {
  const wrap = document.getElementById('toastWrap');
  const el = document.createElement('div');
  el.className = 'toast' + (isError?' toast-error':'');
  el.textContent = msg;
  wrap.appendChild(el);
  requestAnimationFrame(() => el.classList.add('show'));
  setTimeout(() => { el.classList.remove('show'); setTimeout(()=>el.remove(),400); }, 3200);
}

/* MODAL */
function closeOnOverlay(e,id){ if(e.target===document.getElementById(id)) closeModal(id); }
function closeModal(id){ document.getElementById(id).classList.remove('active'); document.body.style.overflow=''; }
function openModal(id){ document.getElementById(id).classList.add('active'); document.body.style.overflow='hidden'; }
document.addEventListener('keydown', e=>{
  if(e.key==='Escape'){['detailOverlay','cartOverlay','checkoutOverlay','successOverlay'].forEach(closeModal);}
});

/* BADGE */
function updateBadge(){
  const badge = document.getElementById('cartBadge');
  const count = cart.reduce((s,i)=>s+i.qty,0);
  badge.textContent = count;
  count>0 ? badge.classList.add('show') : badge.classList.remove('show');
}
updateBadge();

/* DETAIL MODAL */
function openDetail(id,desc,price,img,viewOnly=false){
  currentProduct={id,desc,price,img};
  detailQty=1;
  document.getElementById('detailTitle').textContent = viewOnly?'Chi tiết sản phẩm':'Thêm vào giỏ';
  document.getElementById('detailImg').src=img;
  document.getElementById('detailPrice').textContent=price;
  document.getElementById('detailDesc').textContent=desc;
  document.getElementById('detailQty').textContent='1';
  document.getElementById('addToCartBtn').style.display=viewOnly?'none':'';
  openModal('detailOverlay');
}
function changeQty(delta){
  detailQty=Math.max(1,Math.min(99,detailQty+delta));
  document.getElementById('detailQty').textContent=detailQty;
}

/* PARSE PRICE */
function parsePrice(raw){
  let c=raw.replace(/[₫đĐ\s]/g,'');
  if(c.includes(',')&&!c.includes('.')) c=c.replace(/,/g,'');
  else if(/\.\d{3}$/.test(c)&&(c.match(/\./g)||[]).length===1) c=c.replace(/\./g,'');
  else c=c.replace(/,/g,'');
  return parseFloat(c)||0;
}
function fmtPrice(n){ return new Intl.NumberFormat('vi-VN',{style:'currency',currency:'VND'}).format(n); }
function escHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

/* ADD TO CART */
async function addToCart(){
  if(!currentProduct) return;
  const btn=document.getElementById('addToCartBtn');
  btn.disabled=true; btn.textContent='…';
  const fd=new FormData();
  fd.append('action','add_to_cart');
  fd.append('product_id',currentProduct.id);
  fd.append('qty',detailQty);
  try {
    const res=await fetch('cart.php',{method:'POST',body:fd});
    const data=await res.json();
    if(data.ok){
      const idx=cart.findIndex(i=>i.product_id===currentProduct.id);
      if(idx>=0) cart[idx].qty=Math.min(99,cart[idx].qty+detailQty);
      else cart.push({product_id:currentProduct.id,description:currentProduct.desc,
        price_raw:currentProduct.price,price_num:parsePrice(currentProduct.price),
        image:currentProduct.img,qty:detailQty});
      updateBadge();
      toast('✅ '+data.msg);
      closeModal('detailOverlay');
    } else toast('❌ '+data.msg,true);
  } catch(e){ toast('❌ Lỗi kết nối.',true); }
  finally { btn.disabled=false; btn.textContent='🛒 Thêm vào giỏ'; }
}

/* CART MODAL */
function openCart(){ renderCart(); openModal('cartOverlay'); }
function renderCart(){
  const body=document.getElementById('cartBody');
  const footer=document.getElementById('cartFooter');
  if(cart.length===0){
    body.innerHTML=`<div class="cart-empty">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
        <path d="M1 1h4l2.68 13.39a2 2 0 001.99 1.61h9.72a2 2 0 001.99-1.61L23 6H6"/>
      </svg>
      <p style="font-size:16px;font-weight:600">Giỏ hàng trống</p>
      <p>Hãy thêm sản phẩm để tiếp tục.</p></div>`;
    footer.innerHTML=`<button class="btn btn-secondary" onclick="closeModal('cartOverlay')">Đóng</button>`;
    return;
  }
  let total=0, rows='';
  cart.forEach(item=>{
    const sub=item.price_num*item.qty; total+=sub;
    rows+=`<tr>
      <td><img src="${item.image}" alt="" class="cart-img" onerror="this.src='https://via.placeholder.com/50?text=?'"></td>
      <td><div style="font-weight:600;font-size:14px">${escHtml(item.description)}</div>
          <div style="font-size:12px;color:var(--muted)">ID: ${escHtml(item.product_id)}</div></td>
      <td>${escHtml(item.price_raw)}</td>
      <td><input class="qty-input" type="number" min="1" max="99" value="${item.qty}"
          onchange="updateQty('${escHtml(item.product_id)}',this.value)"></td>
      <td style="font-weight:700;color:var(--accent)">${fmtPrice(sub)}</td>
      <td><button class="cart-remove" onclick="removeItem('${escHtml(item.product_id)}')">🗑</button></td>
    </tr>`;
  });
  body.innerHTML=`<table class="cart-table">
    <thead><tr><th>Ảnh</th><th>Sản phẩm</th><th>Đơn giá</th><th>SL</th><th>Thành tiền</th><th></th></tr></thead>
    <tbody>${rows}</tbody></table>
    <div class="cart-total-row"><span>Tổng cộng:</span><span>${fmtPrice(total)}</span></div>`;
  footer.innerHTML=`
    <button class="btn btn-secondary" style="margin-right:auto" onclick="clearCart()">🗑 Xóa tất cả</button>
    <button class="btn btn-secondary" onclick="closeModal('cartOverlay')">Đóng</button>
    <button class="btn btn-success" onclick="goCheckout()">Đặt hàng →</button>`;
}

async function updateQty(pid,val){
  let qty=parseInt(val); if(isNaN(qty)||qty<1) qty=1; if(qty>99) qty=99;
  const fd=new FormData(); fd.append('action','update_qty'); fd.append('product_id',pid); fd.append('qty',qty);
  await fetch('cart.php',{method:'POST',body:fd});
  const idx=cart.findIndex(i=>i.product_id===pid);
  if(idx>=0) cart[idx].qty=qty;
  updateBadge(); renderCart();
}
async function removeItem(pid){
  const fd=new FormData(); fd.append('action','remove_item'); fd.append('product_id',pid);
  await fetch('cart.php',{method:'POST',body:fd});
  cart=cart.filter(i=>i.product_id!==pid);
  updateBadge(); renderCart(); toast('Đã xóa sản phẩm khỏi giỏ.');
}
async function clearCart(){
  if(!confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?')) return;
  const fd=new FormData(); fd.append('action','clear_cart');
  await fetch('cart.php',{method:'POST',body:fd});
  cart=[]; updateBadge(); renderCart(); toast('Đã xóa toàn bộ giỏ hàng.');
}
function goCheckout(){
  if(cart.length===0){toast('Giỏ hàng trống!',true);return;}
  closeModal('cartOverlay'); openModal('checkoutOverlay');
}

/* VALIDATE */
function showErr(id,msg){
  const el=document.getElementById(id);
  const err=document.getElementById('err_'+id);
  if(!el||!err) return;
  err.textContent=msg;
  msg ? err.classList.add('show') : err.classList.remove('show');
  msg ? el.classList.add('input-error') : el.classList.remove('input-error');
}
function getVal(id){ return document.getElementById(id).value.trim(); }

function validateAll(){
  let ok=true;
  const fn=getVal('f_fullname');
  if(!fn||fn.length<2){ showErr('f_fullname','Vui lòng nhập họ tên (ít nhất 2 ký tự).'); ok=false; }
  else showErr('f_fullname','');

  const ph=getVal('f_phone');
  if(!ph||!/^(0|\+84)[35789][0-9]{8}$/.test(ph)){ showErr('f_phone','Số điện thoại không hợp lệ (VD: 0912345678).'); ok=false; }
  else showErr('f_phone','');

  const em=getVal('f_email');
  if(!em||!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)){ showErr('f_email','Địa chỉ email không hợp lệ.'); ok=false; }
  else showErr('f_email','');

  const ad=getVal('f_address');
  if(!ad||ad.length<10){ showErr('f_address','Địa chỉ phải ít nhất 10 ký tự.'); ok=false; }
  else showErr('f_address','');

  const payEl=document.querySelector('input[name="payment"]:checked');
  if(!payEl){ showErr('err_payment','Vui lòng chọn phương thức thanh toán.'); ok=false; }
  else {
    const pe=document.getElementById('err_payment');
    if(pe){ pe.textContent=''; pe.classList.remove('show'); }
  }
  return ok;
}

/* PLACE ORDER */
async function placeOrder(){
  if(!validateAll()){
    toast('❌ Vui lòng kiểm tra lại thông tin!',true);
    const firstErr=document.querySelector('.input-error');
    if(firstErr) firstErr.scrollIntoView({behavior:'smooth',block:'center'});
    return;
  }

  const btn=document.getElementById('placeOrderBtn');
  btn.disabled=true; btn.textContent='⏳ Đang xử lý…';

  // Lấy tất cả giá trị TRƯỚC khi gửi
  const fullname = getVal('f_fullname');
  const phone    = getVal('f_phone');
  const email    = getVal('f_email');
  const address  = getVal('f_address');
  const note     = getVal('f_note');
  const payEl    = document.querySelector('input[name="payment"]:checked');
  const payment  = payEl ? payEl.value : '';

  const fd=new FormData();
  fd.append('action','place_order');
  fd.append('fullname',fullname);
  fd.append('phone',phone);
  fd.append('email',email);
  fd.append('address',address);
  fd.append('note',note);
  fd.append('payment',payment);
  fd.append('cart_data', JSON.stringify(cart));

  try {
    const res  = await fetch('cart.php',{method:'POST',body:fd});
    const text = await res.text();
    let data;
    try { data=JSON.parse(text); }
    catch(e){ toast('❌ Lỗi phản hồi server: '+text.substring(0,100),true); return; }

    if(data.ok){
      cart=[]; updateBadge();
      closeModal('checkoutOverlay');

      // Hiện popup thành công
      const payLabels={cod:'Thanh toán khi nhận hàng (COD)',bank:'Chuyển khoản ngân hàng',momo:'Ví MoMo'};
      document.getElementById('orderDetailBox').innerHTML=`
        <div><b>Mã đơn hàng:</b><span style="font-weight:700;color:var(--accent)">${escHtml(data.order_id)}</span></div>
        <div><b>Họ tên:</b><span>${escHtml(fullname)}</span></div>
        <div><b>Điện thoại:</b><span>${escHtml(phone)}</span></div>
        <div><b>Email:</b><span>${escHtml(email)}</span></div>
        <div><b>Địa chỉ:</b><span>${escHtml(address)}</span></div>
        <div><b>Thanh toán:</b><span>${escHtml(payLabels[payment]||payment)}</span></div>
        <div><b>Tổng tiền:</b><span style="font-weight:700;color:var(--accent)">${fmtPrice(data.total||0)}</span></div>
        <div><b>Thời gian:</b><span>${new Date().toLocaleString('vi-VN')}</span></div>`;
      openModal('successOverlay');
      toast('🎉 Đặt hàng thành công!');

      // Reset form
      ['f_fullname','f_phone','f_email','f_address','f_note'].forEach(id=>{
        const el=document.getElementById(id); if(el) el.value='';
        showErr(id,'');
      });
      document.querySelectorAll('input[name="payment"]').forEach(r=>r.checked=false);

    } else {
      if(data.errors){
        Object.entries(data.errors).forEach(([k,msg])=>{
          if(k==='payment'){
            const pe=document.getElementById('err_payment');
            if(pe){ pe.textContent=msg; pe.classList.add('show'); }
          } else {
            showErr('f_'+k, msg);
          }
        });
        if(data.errors.cart) toast('❌ '+data.errors.cart,true);
      }
      toast('❌ Vui lòng kiểm tra lại thông tin!',true);
    }
  } catch(e){
    toast('❌ Lỗi kết nối máy chủ: '+e.message,true);
  } finally {
    btn.disabled=false; btn.textContent='✅ Xác nhận đặt hàng';
  }
}
</script>
</body>
</html>