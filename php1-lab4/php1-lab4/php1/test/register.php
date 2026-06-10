<?php

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["user"]) && isset($_POST["pass"])) {
  $user = $_POST['user'];
  $pass = $_POST['pass'];
  if (empty($user)) {
    if (strlen($user) == 0) {
      echo "Ten ko dc de trong";
    }
    if (strlen($pass) < 6) {
      echo "Mat khau ko dc be hon 6 ky tu";
    }
  }
}




?>







<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta http-equiv="Content-Language" content="vi">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Đăng ký - NovaMart</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="section-band">
  <main class="container py-5">
    <div class="auth-panel p-4 p-md-5 mx-auto" style="max-width:520px">
      <a class="navbar-brand fw-bold" href="index.html">NovaMart</a>
      <h1 class="h3 fw-bold mt-4">Tạo tài khoản</h1>
      <form class="mt-3">
        <label class="form-label">Họ tên</label><input class="form-control mb-3">
        <label class="form-label">Email</label><input class="form-control mb-3" type="email">
        <label class="form-label">Mật khẩu</label><input class="form-control mb-3" type="password">
        <button class="btn btn-brand w-100">Đăng ký</button>
      </form>
      <p class="mt-3 mb-0">Đã có tài khoản? <a href="login.html">Đăng nhập</a></p>
    </div>
  </main>
</body>
</html>