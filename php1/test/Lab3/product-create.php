<?php

require_once "db-utils.php";

$db = new DB_UTILS();

$categories =
$db->getAll(
"SELECT * FROM categories"
);

$message = "";

if($_SERVER['REQUEST_METHOD']=='POST'){

    $name =
    trim($_POST['name']);

    $price =
    (float)$_POST['price'];

    $description =
    trim($_POST['description']);

    $category_id =
    $_POST['category_id'];

    $imageName = "";
$imageUrl =
trim($_POST['image_url']);

    if(
    isset($_FILES['image'])
    &&
    $_FILES['image']['error']==0
    ){
$imageName =
time() . "_" .
preg_replace(
"/[^a-zA-Z0-9._-]/",
"_",
basename($_FILES['image']['name'])
);

        move_uploaded_file(

        $_FILES['image']['tmp_name'],

        "uploads/".$imageName

        );
    }

    $db->execute(

    "INSERT INTO products(
category_id,
name,
price,
image,
image_url,
description
)
VALUES(?,?,?,?,?,?)",

 [
$category_id,
$name,
$price,
$imageName,
$imageUrl,
$description
]

    );

    header(
    "Location: dashboard.php?module=product"
    );

    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Thêm Product</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<div class="form-card">

<h2>Thêm Product</h2>

<form
method="POST"
enctype="multipart/form-data">

<label>Tên sản phẩm</label>

<input
type="text"
name="name"
required>

<label>Danh mục</label>

<select name="category_id">

<?php foreach($categories as $c): ?>

<option value="<?= $c['id'] ?>">

<?= htmlspecialchars($c['name']) ?>

</option>

<?php endforeach; ?>

</select>

<label>Giá</label>

<input
type="number"
name="price"
required>

<label>Hình ảnh</label>

<input
type="file"
name="image">

<label>URL hình ảnh</label>

<input
type="text"
name="image_url"
placeholder="https://example.com/image.jpg">

<label>Mô tả</label>

<textarea
name="description">
</textarea>

<button
type="submit"
class="btn btn-success">

Thêm Product

</button>

</form>

</div>

</div>

</body>
</html>