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
  $userLogin = NULL;
  foreach ($users as $key => $item) {
    // var_dump($user['username']);
    // var_dump($user[$key]['password']);
    if ($item['username'] == $user && $item['password'] == $pass) {
      $ischeck  = true;
      $userLogin = $item;
      break;
    } 
  }
  if($ischeck) {
    echo "dang thanh cong";
    /**
     * luu lai user da dang nhap -> day den trang profile
     * 
     */
      $_SESSION['user'] = $userLogin;
      header('Location: ./account.php');
      exit;
  } else {
    echo "dang nhap that bai";
  }
} else {
  echo "dang nhap that bai";
}
var_dump($_SESSION['user'])
?>

<!doctype html>
<html lang="vi">

<head>
  <meta charset="utf-8">
  <meta http-equiv="Content-Language" content="vi">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Đăng nhập - NovaMart</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
</head>

<body class="section-band">
  <main class="container py-5">
    <div class="auth-panel p-4 p-md-5 mx-auto" style="max-width:460px">
      <a class="navbar-brand fw-bold" href="index.html">NovaMart</a>
      <h1 class="h3 fw-bold mt-4">Đăng nhập</h1>
      <form class="mt-3" method="post" action="">
        <label class="form-label">Tài khoản</label>
        <input name="user" class="form-control mb-3" type="text" placeholder="nhập tài khoản">
        <label class="form-label">Mật khẩu</label>
        <input name="pass" class="form-control mb-3" placeholder="nhập mật khẩu" type="password">

        <button type="submit" name="dangnhap" class="btn btn-brand w-100">Đăng nhập</button>

      </form>
      <p class="mt-3 mb-0">Chưa có tài khoản? <a href="register.html">Đăng ký</a></p>
    </div>
  </main>
</body>

</html>