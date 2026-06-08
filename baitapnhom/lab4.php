<?php
// Lấy thông tin đường dẫn gốc thư mục dự án của file hiện tại để định tuyến chính xác URL ảnh
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

try {
    // Kết nối CSDL MySQL bằng thư viện PDO
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=lab4;charset=utf8", "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) { 
    die("Lỗi kết nối cơ sơ dữ liệu: " . $e->getMessage()); 
}

// Khởi tạo các biến chứa trạng thái thông báo lỗi hoặc thành công rỗng ban đầu
$error = ''; $success = ''; $editProduct = null;

// XỬ LÝ: Khi form POST dữ liệu (Thêm mới, Cập nhật sửa đổi, Xóa) được kích hoạt gửi đi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Gộp chung bộ xử lý dữ liệu đầu vào cho cả 2 hành động 'Thêm' (add) và 'Sửa' (edit) sản phẩm
    if (in_array($action, ['add', 'edit'])) {
        $productId = trim($_POST['productId'] ?? ''); $description = trim($_POST['description'] ?? ''); $price = trim($_POST['price'] ?? ''); $image = trim($_POST['oldImage'] ?? '');
        
        // XỬ LÝ: Tải tập tin hình ảnh lên Server (File Upload) nếu có file được chọn hợp lệ
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/'; 
            // Nếu thư mục 'uploads/' chưa tồn tại trên ổ đĩa, tự động tạo mới với toàn quyền đọc ghi 0777
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            // Đặt tên file bằng cách nối chuỗi hàm time() giúp tên tệp tin độc nhất, không bị lỗi ghi đè nếu trùng tên file ảnh gốc
            $targetPath = $uploadDir . time() . '_' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $image = $targetPath; // Gán lại đường dẫn file ảnh mới vừa upload thành công
            }
        }

        // Kiểm tra cơ bản xem dữ liệu có bị bỏ trống trường nào bắt buộc không
        if (empty($productId) || empty($description) || empty($price) || empty($image)) {
            $error = 'Vui lòng nhập đầy đủ thông tin';
        } else {
            // Hành động THÊM MỚI sản phẩm
            if ($action === 'add') {
                // Kiểm tra xem mã ID sản phẩm định thêm này đã tồn tại sẵn dưới database chưa để tránh lỗi trùng khóa chính (Primary Key)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE product_id = ?"); $stmt->execute([$productId]);
                if ($stmt->fetchColumn() > 0) { 
                    $error = 'ID sản phẩm đã tồn tại!'; 
                } else { 
                    // Thực thi câu lệnh chèn hàng dữ liệu mới vào bảng 'products'
                    $pdo->prepare("INSERT INTO products VALUES (?, ?, ?, ?)")->execute([$productId, $description, $price, $image]); 
                    $success = 'Thêm sản phẩm thành công!'; 
                }
            // Hành động CẬP NHẬT (SỬA) sản phẩm đã có sẵn
            } elseif ($action === 'edit') {
                $pdo->prepare("UPDATE products SET description = ?, price = ?, image = ? WHERE product_id = ?")->execute([$description, $price, $image, $productId]);
                $success = 'Cập nhật sản phẩm thành công!';
            }
        }
    } 
    // Hành động XÓA sản phẩm
    elseif ($action === 'delete') {
        if (!empty($_POST['productId'])) { 
            // Thực hiện xóa sản phẩm theo mã product_id tương ứng nhận được
            $pdo->prepare("DELETE FROM products WHERE product_id = ?")->execute([$_POST['productId']]); 
            $success = 'Xóa sản phẩm thành công!'; 
        }
    }
}

// XỬ LÝ TRẠNG THÁI SỬA: Lấy thông tin sản phẩm cần sửa điền ngược lên Form nếu nhận thấy trên URL có lệnh ?action=edit&id=...
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?"); $stmt->execute([$_GET['id']]); 
    $editProduct = $stmt->fetch(PDO::FETCH_ASSOC); // Lưu bản ghi sản phẩm vào biến để đổ ra form HTML bên dưới
}

// XỬ LÝ: Bộ lọc Tìm kiếm sản phẩm tại trang Admin quản lý
$search = trim($_GET['search'] ?? ''); $params = []; $searchQuery = "";
if ($search !== '') { 
    $searchQuery = " WHERE product_id LIKE ? OR description LIKE ?"; 
    $params = ["%$search%", "%$search%"]; 
}

// XỬ LÝ: Cơ chế phân trang cho trang quản lý Admin (Giới hạn hiển thị 3 sản phẩm trên một trang)
$limit = 3; $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; $offset = ($page - 1) * $limit;

// Tính toán tổng số trang dựa trên kết quả tìm kiếm chia cho giới hạn limit hiển thị
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM products" . $searchQuery); $stmtCount->execute($params);
$totalPages = ceil($stmtCount->fetchColumn() / $limit);

// Truy vấn lấy dữ liệu sản phẩm giới hạn của trang hiện tại đổ lên Grid danh sách bên dưới
$stmt = $pdo->prepare("SELECT * FROM products" . $searchQuery . " ORDER BY product_id DESC LIMIT ? OFFSET ?");
foreach ($params as $index => $param) $stmt->bindValue($index + 1, $param);
$stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT); $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT); $stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý sản phẩm</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0;font-family:Arial,sans-serif}body{background:#f4f6f8;padding:30px;color:#222}h1,h2{text-align:center;margin-bottom:25px}.product-form{max-width:600px;margin:0 auto 35px;background:#fff;padding:20px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1)}.form-group{margin-bottom:15px}.form-group label{display:block;margin-bottom:6px;font-weight:bold}.form-group input,.form-group textarea{width:100%;padding:10px;border:1px solid #ccc;border-radius:8px;font-size:15px}.form-group textarea{min-height:90px;resize:vertical}button{width:100%;padding:12px;border:none;border-radius:8px;color:#fff;font-size:16px;font-weight:bold;cursor:pointer}.btn-add{background:#2563eb}.btn-add:hover{background:#1d4ed8}.btn-delete{margin-top:12px;background:#dc2626}.btn-delete:hover{background:#b91c1c}.btn-edit{margin-top:12px;background:#fbbf24;color:#000}.btn-edit:hover{background:#f59e0b}.product-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;max-width:1000px;margin:0 auto}.product-card{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.1);transition:transform 0.2s}.product-card:hover{transform:translateY(-5px)}.product-card img{width:100%;height:180px;object-fit:cover}.product-info{padding:16px}.product-id{font-size:14px;color:#666;margin-bottom:8px}.product-description{font-size:16px;margin-bottom:12px;line-height:1.5}.product-price{font-size:20px;font-weight:bold;color:#e63946}.alert{padding:15px;margin-bottom:20px;border-radius:8px;text-align:center;max-width:600px;margin:0 auto}.alert-danger{background:#fee2e2;color:#991b1b}.alert-success{background:#dcfce7;color:#166534}.btn-group{display:flex;gap:10px}.pagination{display:flex;justify-content:center;align-items:center;gap:8px;margin-top:30px;flex-wrap:wrap}.pagination a,.pagination span{display:inline-block;min-width:40px;padding:10px 16px;border-radius:8px;text-align:center;text-decoration:none;font-weight:bold;font-size:15px}.pagination a{background:#fff;color:#2563eb;border:1px solid #d1d5db}.pagination a:hover,.pagination .active{background:#2563eb;color:#fff;border:1px solid #2563eb}.pagination .disabled{background:#e5e7eb;color:#9ca3af;cursor:not-allowed}.search-form{max-width:600px;margin:0 auto 20px;display:flex;gap:10px}.search-form input{flex:1;padding:10px;border:1px solid #ccc;border-radius:8px}.search-form button{width:auto;padding:10px 20px}.btn-detail{margin-top:12px;background:#16a34a;color:#fff}.btn-detail:hover{background:#15803d}.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:1000;justify-content:center;align-items:center}.modal-overlay.active{display:flex}.modal-box{background:#fff;border-radius:16px;width:90%;max-width:480px;box-shadow:0 8px 32px rgba(0,0,0,0.2);overflow:hidden}.modal-header{background:#2563eb;color:#fff;padding:16px 20px;display:flex;justify-content:space-between;align-items:center}.modal-close{background:none;border:none;color:#fff;font-size:22px;cursor:pointer}.modal-body{padding:20px}.modal-body img{width:100%;height:220px;object-fit:cover;border-radius:10px;margin-bottom:18px}.modal-row{display:flex;gap:8px;margin-bottom:12px}.modal-label{font-weight:bold;min-width:70px}.modal-value.price{color:#e63946;font-weight:bold;font-size:18px}.modal-footer{padding:14px 20px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end}.modal-footer button{width:auto;padding:10px 28px;background:#6b7280}
  </style>
</head>
<body>
  <h1>Quản lý sản phẩm</h1>
  <div style="text-align:center; margin-bottom: 12px;"><a href="cart.php" style="display:inline-block; background:#16a34a; color:#fff; padding:10px 28px; border-radius:8px; font-weight:bold; text-decoration:none;">🛒 Trang mua hàng &amp; Giỏ hàng</a></div>

  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <form class="product-form" method="POST" enctype="multipart/form-data">
    <h2><?= $editProduct ? 'Sửa sản phẩm' : 'Thêm sản phẩm' ?></h2>
    <input type="hidden" name="action" value="<?= $editProduct ? 'edit' : 'add' ?>">
    
    <div class="form-group"><label>ID sản phẩm</label><input type="text" name="productId" value="<?= htmlspecialchars($editProduct['product_id'] ?? '') ?>" <?= $editProduct ? 'readonly style="background: #e9ecef;"' : 'required' ?> /></div>
    <div class="form-group"><label>Mô tả</label><textarea name="description" required><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea></div>
    <div class="form-group"><label>Giá</label><input type="text" name="price" value="<?= htmlspecialchars($editProduct['price'] ?? '') ?>" required /></div>
    <div class="form-group">
      <label>Hình ảnh</label><input type="file" name="image" accept="image/*" <?= $editProduct ? '' : 'required' ?> />
      <?php if ($editProduct && !empty($editProduct['image'])): ?><input type="hidden" name="oldImage" value="<?= htmlspecialchars($editProduct['image']) ?>"><img src="<?= htmlspecialchars($baseUrl . '/' . ltrim($editProduct['image'], '/')) ?>" style="max-width:150px; display:block; margin-top:10px; border-radius:8px;"><?php endif; ?>
    </div>
    
    <button class="btn-add" type="submit"><?= $editProduct ? 'Cập nhật sản phẩm' : 'Thêm sản phẩm' ?></button>
    <?php if ($editProduct): ?><a href="lab4.php" style="display: block; text-align: center; margin-top: 10px; color: #666; text-decoration: none;">Hủy</a><?php endif; ?>
  </form>

  <form class="search-form" method="GET">
    <input type="text" name="search" placeholder="Tìm kiếm theo ID " value="<?= htmlspecialchars($search) ?>" /><button class="btn-add" type="submit">Tìm kiếm</button>
    <?php if ($search !== ''): ?><a href="lab4.php" style="padding: 10px 20px; background: #e5e7eb; color:#374151; border-radius: 8px; text-decoration: none; display: flex; align-items: center;">Hủy</a><?php endif; ?>
  </form>

  <div class="product-list">
    <?php if (count($products) > 0): foreach ($products as $product): ?>
      <div class="product-card">
        <img src="<?= htmlspecialchars($baseUrl . '/' . ltrim($product['image'], '/')) ?>" />
        <div class="product-info">
          <p class="product-id">ID: <?= htmlspecialchars($product['product_id']) ?></p>
          <p class="product-description">Mô tả: <?= htmlspecialchars($product['description']) ?></p>
          <p class="product-price">Giá: <?= htmlspecialchars($product['price']) ?></p>
          <div class="btn-group">
            <a href="lab4.php?action=edit&id=<?= urlencode($product['product_id']) ?>" style="flex:1;"><button class="btn-edit" type="button">Sửa</button></a>
            <button class="btn-detail" type="button" style="flex:1;" onclick="openModal('<?= htmlspecialchars(addslashes($product['product_id'])) ?>','<?= htmlspecialchars(addslashes($product['description'])) ?>','<?= htmlspecialchars(addslashes($product['price'])) ?>','<?= htmlspecialchars($baseUrl . '/' . ltrim($product['image'], '/')) ?>')">Chi tiết</button>
            <form method="POST" style="flex:1;" onsubmit="return confirm('bạn có chắc muốn xóa ko');">
              <input type="hidden" name="action" value="delete"><input type="hidden" name="productId" value="<?= htmlspecialchars($product['product_id']) ?>"><button class="btn-delete" type="submit">Xóa</button>
            </form>
          </div>
          <a href="cart.php" style="display:block; margin-top:10px; text-align:center; background:#16a34a; color:#fff; padding:10px; border-radius:8px; font-weight:bold; text-decoration:none;">🛒 Xem giỏ hàng &amp; Mua</a>
        </div>
      </div>
    <?php endforeach; else: ?><p style="text-align: center; grid-column: 1 / -1;">Chưa có sản phẩm nào</p><?php endif; ?>
  </div>

  <?php if ($totalPages > 1): $searchParam = $search !== '' ? '&search=' . urlencode($search) : ''; ?>
  <div class="pagination">
    <a href="lab4.php?page=<?= max(1, $page-1) ?><?= $searchParam ?>" class="<?= $page<=1?'disabled':'' ?>">&#9664;</a>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?><a href="lab4.php?page=<?= $i ?><?= $searchParam ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a><?php endfor; ?>
    <a href="lab4.php?page=<?= min($totalPages, $page+1) ?><?= $searchParam ?>" class="<?= $page>=$totalPages?'disabled':'' ?>">&#9654;</a>
  </div>
  <?php endif; ?>

  <div class="modal-overlay" id="detailModal" onclick="if(event.target===this) closeModal()">
    <div class="modal-box">
      <div class="modal-header"><h3>Chi tiết sản phẩm</h3><button class="modal-close" onclick="closeModal()">&times;</button></div>
      <div class="modal-body">
        <img id="modal-img" /><div class="modal-row"><span class="modal-label">ID:</span><span id="modal-id"></span></div>
        <div class="modal-row"><span class="modal-label">Mô tả:</span><span id="modal-desc"></span></div>
        <div class="modal-row"><span class="modal-label">Giá:</span><span class="modal-value price" id="modal-price"></span></div>
      </div>
      <div class="modal-footer"><button onclick="closeModal()">Đóng</button></div>
    </div>
  </div>

  <script>
    // Hàm JavaScript lấy dữ liệu thô của hàng sản phẩm, ghi đè vào các thẻ span trống trong Modal và mở lớp phủ hiển thị lên
    function openModal(id, desc, price, img) { document.getElementById('modal-id').textContent = id; document.getElementById('modal-desc').textContent = desc; document.getElementById('modal-price').textContent = price; document.getElementById('modal-img').src = img; document.getElementById('detailModal').classList.add('active'); document.body.style.overflow = 'hidden'; }
    // Gỡ bỏ lớp class 'active' để ẩn Modal đi và khôi phục lại thanh cuộn dọc (scroll) của trang web như thường
    function closeModal() { document.getElementById('detailModal').classList.remove('active'); document.body.style.overflow = ''; }
    // Lắng nghe phím ESC tắt nhanh hộp thoại đang bật
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
  </script>
</body>
</html>