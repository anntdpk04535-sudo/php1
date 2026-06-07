<?php
session_start();
if (!isset($_SESSION['user'])){
  header('Location: ./login.php');
  exit;
}

?>
<!doctype html>
<html lang="vi">

<head>
  <meta charset="utf-8">
  <meta http-equiv="Content-Language" content="vi">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tài khoản - NovaMart</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
</head>

<body>

  <nav class="navbar bg-white border-bottom">
    <div class="container"><a class="navbar-brand fw-bold" href="index.html">NovaMart</a><a class="btn btn-outline-secondary" href="login.html">Đăng xuất</a></div>
  </nav>
  <main class="container py-5">
    <h1 class="h3 fw-bold mb-4">Tài khoản của tôi</h1>
    <div class="row g-4">
      <aside class="col-lg-3">
        <div class="list-group"><a class="list-group-item list-group-item-action active" href="account.html">Hồ sơ</a><a class="list-group-item list-group-item-action" href="orders.html">Đơn hàng</a><a class="list-group-item list-group-item-action" href="cart.html">Giỏ hàng</a></div>
      </aside>
      <section class="col-lg-9">
        <form class="table-panel p-4">
          <h2 class="h5">Thông tin cá nhân</h2>
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Họ tên</label><input class="form-control" value="<?php echo $_SESSION['user']['name'] ?>"></div>
            <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" value=""></div>
            <div class="col-md-6"><label class="form-label">SĐT</label><input class="form-control" value=""></div>
              <div class="col-md-6"><label class="form-label">Sinh Nhật</label><input type='date' class="form-control" value=""></div>
                <div class="col-md-6"><label class="form-label">Ảnh Cá Nhân</label><input type='file' class="form-control" value=""></div>
            <div class="col-12"><label class="form-label">Địa chỉ mặc định</label><input class="form-control" value=""></div>
          </div><button class="btn btn-brand mt-3">Lưu thay đổi</button>
        </form>
      </section>
    
    </div>
  </main>
</body>

</html>