<?php

require_once "db-utils.php";

$db = new DB_UTILS();

$id =
(int)$_GET['id'];

$category =
$db->getOne(

"SELECT *
FROM categories
WHERE id=?",

[$id]

);

if(!$category){

die("Category không tồn tại");

}

if($_SERVER['REQUEST_METHOD']=='POST'){

    $db->execute(

    "UPDATE categories
    SET
    name=?,
    description=?
    WHERE id=?",

    [
        trim($_POST['name']),
        trim($_POST['description']),
        $id
    ]

    );

    header(
    "Location: dashboard.php?module=category"
    );

    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Sửa Category</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<div class="form-card">

<h2>Sửa Category</h2>

<form method="POST">

<label>Tên</label>

<input
type="text"
name="name"
value="<?= htmlspecialchars($category['name']) ?>">

<label>Mô tả</label>

<textarea
name="description"><?= htmlspecialchars($category['description']) ?></textarea>

<button
type="submit"
class="btn">

Cập nhật

</button>

</form>

</div>

</div>

</body>
</html>