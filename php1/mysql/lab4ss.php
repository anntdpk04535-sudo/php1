<?php

require "./db_utils.php";
$errors = [];
$db_untils = new DB_UTILS();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (empty($_POST['productId'])) {
    $errors[] = 'id khong duoc de trong';
  }
  if (empty($_POST['price'])) {
    $errors[] = 'gia tien khong khong duoc de trong';
  }
  if (count($errors) == 0) {
    /** LET DO IT
     * b1: ket noi mysql
     * b2: viet cau query
     * b3: thuc thi va lay ket qua tra ve
     */

    $insert_product_sql = "insert into products(maSP, mota, gia, hinhAnh) values (?,?,?,?)";
    $ketqua = $db_untils->execute($insert_product_sql, [
      $_POST['productId'],
      $_POST['description'],
      $_POST['price'],
      $_POST['image']
    ]);

    if ($ketqua) {
      echo "<div class='alert alert-success role='alert'>
</div>";
    }
  }
}




?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Quản lý sản phẩm</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
    }

    body {
      background: #f4f6f8;
      padding: 30px;
      color: #222;
    }

    h1,
    h2 {
      text-align: center;
      margin-bottom: 25px;
    }

    .product-form {
      max-width: 600px;
      margin: 0 auto 35px;
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 6px;
      font-weight: bold;
    }

    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 15px;
    }

    .form-group textarea {
      min-height: 90px;
      resize: vertical;
    }

    button {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 8px;
      color: #fff;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
    }

    .btn-add {
      background: #2563eb;
    }

    .btn-add:hover {
      background: #1d4ed8;
    }

    .btn-delete {
      margin-top: 12px;
      background: #dc2626;
    }

    .btn-delete:hover {
      background: #b91c1c;
    }

    .product-list {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      max-width: 1000px;
      margin: 0 auto;
    }

    .product-card {
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      transition: transform 0.2s;
    }

    .product-card:hover {
      transform: translateY(-5px);
    }

    .product-card img {
      width: 100%;
      height: 180px;
      object-fit: cover;
    }

    .product-info {
      padding: 16px;
    }

    .product-id {
      font-size: 14px;
      color: #666;
      margin-bottom: 8px;
    }

    .product-description {
      font-size: 16px;
      margin-bottom: 12px;
      line-height: 1.5;
    }

    .product-price {
      font-size: 20px;
      font-weight: bold;
      color: #e63946;
    }

    .alert-danger {
      color: red;
    }

    .alert-sucess {
      color: green;
    }
  </style>
</head>

<body>
  <h1>Quản lý sản phẩm</h1>

  <form class="product-form" id="productForm" method="POST">
    <h2>Thêm sản phẩm</h2>

    <div class="form-group">
      <label for="productId">ID sản phẩm</label>
      <input type="text" id="productId" name="productId" placeholder="Ví dụ: SP004" />
    </div>

    <div class="form-group">
      <label for="description">Mô tả</label>
      <textarea id="description" name="description" placeholder="Nhập mô tả sản phẩm"></textarea>
    </div>

    <div class="form-group">
      <label for="price">Giá</label>
      <input type="text" id="price" name="price" placeholder="Ví dụ: 250.000đ" />
    </div>

    <div class="form-group">
      <label for="image">Hình ảnh</label>
      <input type="url" id="image" name="image" placeholder="Nhập link hình ảnh" />
    </div>

    <button class="btn-add" type="submit">Thêm sản phẩm</button>
    <?php
    if (count($errors) > 0) {
      foreach ($errors as $error) {
        echo "<div class='alert alert-danger role='alert'>
    $error
</div>";
      }
    }
    ?>
  </form>


  <h2>Danh sách sản phẩm</h2>
  <div class="product-list" id="productList">
    <?php
    $select_product_sql = 'select * from products';
    //getAll
    $products = $db_untils->getAll($select_product_sql);
    foreach ($products as $product) {
      $image = !empty($product['hinhAnh']) ? $product['hinhAnh'] : 'https://via.placeholder.com/400x250';
    ?>
      <div class="product-card">
        <img src="<?php echo htmlspecialchars($image); ?>" alt="Hình ảnh sản phẩm" />
        <div class="product-info">
          <p class="product-id">ID: <?php echo htmlspecialchars($product['maSP']); ?></p>
          <p class="product-description">Mô tả: <?php echo htmlspecialchars($product['mota']); ?></p>
          <p class="product-price">Giá: <?php echo htmlspecialchars($product['gia']); ?></p>
          <button class="btn-delete" onclick="">Xóa</button>
        </div>
      </div>
    <?php
    }

    ?>


  </div>

</body>

</html>