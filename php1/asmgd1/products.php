









<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta http-equiv="Content-Language" content="vi">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sản phẩm - NovaMart</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
  <nav class="navbar navbar-expand-lg bg-white sticky-top border-bottom">
    <div class="container">
      <a class="navbar-brand fw-bold" href="index.html">NovaMart</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"><span class="navbar-toggler-icon"></span></button>
      <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav me-auto"><li class="nav-item"><a class="nav-link" href="index.html">Trang chủ</a></li><li class="nav-item"><a class="nav-link active" href="products.html">Sản phẩm</a></li><li class="nav-item"><a class="nav-link" href="orders.html">Đơn hàng</a></li><li class="nav-item"><a class="nav-link" href="contact.html">Liên hệ</a></li></ul>
        <a class="btn btn-brand position-relative" href="cart.html"><i class="bi bi-bag me-2"></i>Giỏ hàng <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger" data-cart-count>0</span></a>
      </div>
    </div>
  </nav>
  <main class="py-5">
    <div class="container">
      <div class="row g-4">
        <aside class="col-lg-3">
          <div class="table-panel p-3">
            <h1 class="h5 fw-bold">Bộ lọc</h1>
            <label class="form-label mt-3">Tìm kiếm</label>
            <input class="form-control" id="productSearch" placeholder="Tên sản phẩm">
            <label class="form-label mt-3">Danh mục</label>
            <select class="form-select" id="categoryFilter"><option>Tất cả</option><option>Thời trang</option><option>Phụ kiện</option><option>Công nghệ</option><option>Gia dụng</option></select>
            <label class="form-label mt-3">Giá tối đa: <span id="priceFilterValue">2.000.000d</span></label>
            <input type="range" class="form-range" id="priceFilter" min="300000" max="2000000" step="50000" value="2000000">
            <a class="btn btn-outline-secondary w-100 mt-2" href="wishlist.html">Sản phẩm yêu thích</a>
          </div>
        </aside>
        <section class="col-lg-9">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div><h2 class="h4 fw-bold mb-1">Tất cả sản phẩm</h2><p class="text-muted-2 mb-0" id="productResultText">Đang tải sản phẩm.</p></div>
            <select class="form-select w-auto" id="sortFilter"><option value="newest">Sắp xếp mới nhất</option><option value="price-asc">Giá tăng dần</option><option value="price-desc">Giá giảm dần</option><option value="best-seller">Bán chạy</option></select>
          </div>
          <div class="row g-4" id="productGrid"></div>
          <nav class="mt-4"><ul class="pagination"><li class="page-item active"><a class="page-link" href="#">1</a></li><li class="page-item"><a class="page-link" href="#">2</a></li><li class="page-item"><a class="page-link" href="#">3</a></li></ul></nav>
        </section>
      </div>
    </div>
  </main>
  <div class="toast-container position-fixed bottom-0 end-0 p-3"><div id="shopToast" class="toast"><div class="toast-body"></div></div></div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html>