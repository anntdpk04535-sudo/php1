<?php

session_start();


$users = [
    [
        'username' => 'admin',
        'password' => '123456',
        'name'     => 'admin'
    ],
    [
        'username' => 'tranvanb',
        'password' => 'mk456789',
        'name'     => 'Trần Văn B'
    ],
    [
        'username' => 'lethic',
        'password' => 'lethic_pwd',
        'name'     => 'Lê Thị C'
    ],
    [
        'username' => 'phamvand',
        'password' => 'dpham_2026',
        'name'     => 'Phạm Văn D'
    ],
    [
        'username' => 'hoangthie',
        'password' => 'hoangE!@#',
        'name'     => 'Hoàng Thị E'
    ]
];
// In mảng ra màn hình để xem cấu trúc
echo "<pre>";
// print_r($users);
echo "</pre>";

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
  $ischeck = false;
  $userlogin  = NULL;
  foreach ($users as $key => $item) {
    // var_dump($user['username']);
    // var_dump($user[$key]['password']);
    if ($item['username'] == $user && $item['password'] == $pass) {
      $ischeck  = true;
      $userlogin = $item;
      break;
    } 
  }
  if($ischeck) {
    echo "dang thanh cong";
    if(isset($_SESSION['user'])) {
      $_SESSION['user'] = array(
        'name' => $userlogin,
      );
      header('Location: page.php');
    }


  } else {
    echo "dang nhap that bai";
  }
} else {
  echo "dang nhap that bai";
}



?>

<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta http-equiv="Content-Language" content="vi">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Đăng nhập - NovaMart</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="section-band">
  <main class="container py-5">
    <div class="auth-panel p-4 p-md-5 mx-auto" style="max-width:460px">
      <a class="navbar-brand fw-bold" href="#">NovaMart</a>
      <h1 class="h3 fw-bold mt-4">Đăng nhập</h1>
      <form class="mt-3" method="post">
        <label class="form-label">Tài khoản</label>
        <input name="user" class="form-control mb-3" type="text" placeholder="Nhập tài khoản">
        <label  class="form-label">Mật khẩu</label>
        <input name="pass" class="form-control mb-3" type="password" placeholder="Nhập mật khẩu">
        <input type="submit" class="btn btn-brand w-100" name="login" value="Đăng nhập">
      </form>
      <p class="mt-3 mb-0">Chưa có tài khoản? <a href="register.html">Đăng ký</a></p>
    </div>
  </main>
</body>
</html>