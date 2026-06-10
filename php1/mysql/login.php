<?php
require "./user.php";
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "php1";

try {
  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 

  echo "Connected successfully";
} catch(PDOException $e) {
  echo "Connection failed: " . $e->getMessage();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["username"]) && isset($_POST["pass"])) {
  $username = $_POST['username'];
  $pass = $_POST['pass'];
  if (empty($username)) {
    if (strlen($username) == 0) {
      echo "Ten ko dc de trong";
    }
    if (strlen($pass) < 6) {
      echo "Mat khau ko dc be hon 6 ky tu";
    }
  }
  $user = new User("user", $username, $pass);

  $sql_login = "select * from users where username = :username and password = :password";
  $stmt = $conn->prepare($sql_login);
  $stmt->execute(
    array(
      'username' => htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8'),
      'password' => htmlspecialchars($user->password, ENT_QUOTES, 'UTF-8')
    )
  );
  $userLogin = $stmt->fetch();
  if ($userLogin != NULL) {
    $_SESSION['user'] = array(
      'name' => 'user',
      'username' => $username,
      'pass' => $pass
    );
    header('Location: ./account.php');
    exit;
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
  <link href="assets/css/styles.css" rel="stylesheet">
</head>

<body class="section-band">
  <main class="container py-5">
    <div class="auth-panel p-4 p-md-5 mx-auto" style="max-width:460px">
      <a class="navbar-brand fw-bold" href="index.html">NovaMart</a>
      <h1 class="h3 fw-bold mt-4">Đăng nhập</h1>
      <form class="mt-3" method="post" action="">
        <label class="form-label">Tài khoản</label>
        <input name="username" class="form-control mb-3" type="text" placeholder="nhập tài khoản">
        <label class="form-label">Mật khẩu</label>
        <input name="pass" class="form-control mb-3" placeholder="nhập mật khẩu" type="password">

        <button type="submit" name="dangnhap" class="btn btn-brand w-100">Đăng nhập</button>

      </form>
      <p class="mt-3 mb-0">Chưa có tài khoản? <a href="register.html">Đăng ký</a></p>
    </div>
  </main>
</body>

</html>