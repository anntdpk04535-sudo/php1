<?php

session_start();

require_once "db-utils.php";

$db = new DB_UTILS();

$module =
$_GET['module']
?? 'category';

?>

<!DOCTYPE html>
<html>
<head>

<title>Admin Dashboard</title>

<link
rel="stylesheet"
href="style.css">

</head>
<body>

<div class="dashboard">

<h1>ADMIN PANEL</h1>

<div class="menu">

<a href="?module=category">
Category
</a>

<a href="?module=product">
Product
</a>

<a href="?module=user">
Users
</a>

<a href="logout.php">
Logout
</a>

</div>

<?php

switch($module){

case 'category':

$categories =
$db->getAll(
"SELECT * FROM categories"
);

?>

<h2>Danh sách Category</h2>

<a
href="category-create.php"
class="btn">

+ Thêm Category

</a>

<br><br>

<table>

<tr>

<th>ID</th>
<th>Tên</th>
<th>Mô tả</th>
<th>Action</th>

</tr>

<?php
foreach($categories as $c):
?>

<tr>

<td><?= $c['id'] ?></td>

<td><?= $c['name'] ?></td>

<td><?= $c['description'] ?></td>

<td>

<a
href="category-edit.php?id=<?= $c['id'] ?>"
class="btn">

Sửa

</a>

<a
href="category-delete.php?id=<?= $c['id'] ?>"
class="btn btn-delete"
onclick="return confirm('Bạn có chắc muốn xóa category này?')">

Xóa

</td>

</tr>

<?php endforeach; ?>

</table>

<?php
break;

case 'product':

$products =
$db->getAll(

"SELECT p.*,
c.name category_name

FROM products p

LEFT JOIN categories c

ON p.category_id=c.id"

);

?>

<h2>Danh sách Product</h2>

<a
href="product-create.php"
class="btn">

+ Thêm Product

</a>

<br><br>

<table>

<tr>

<th>ID</th>
<th>Ảnh</th>
<th>Tên</th>
<th>Category</th>
<th>Giá</th>
<th>Action</th>

</tr>

<?php
foreach($products as $p):
?>

<tr>

<td><?= $p['id'] ?></td>

<td>

<?php

if(
!empty($p['image_url'])
){

?>

<img
src="<?= htmlspecialchars($p['image_url']) ?>"
class="product-image">

<?php

}
elseif(
!empty($p['image'])
){

?>

<img
src="uploads/<?= rawurlencode($p['image']) ?>"
class="product-image">

<?php

}
else{

echo "No Image";

}

?>

</td>

<td><?= $p['name'] ?></td>

<td><?= $p['category_name'] ?></td>

<td>

<?= number_format($p['price']) ?>

</td>

<td>

<a
href="product-edit.php?id=<?= $p['id'] ?>"
class="btn">

Sửa

</a>

<a
href="product-delete.php?id=<?= $p['id'] ?>"
class="btn btn-delete"
onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">

Xóa

</a>

</td>

</tr>

<?php endforeach; ?>

</table>

<?php
break;

case 'user':

$users =
$db->getAll(
"SELECT * FROM users"
);

?>

<h2>Danh sách User</h2>

<table>

<tr>

<th>ID</th>
<th>Họ tên</th>
<th>Email</th>
<th>Role</th>

</tr>

<?php
foreach($users as $u):
?>

<tr>

<td><?= $u['id'] ?></td>

<td><?= $u['fullname'] ?></td>

<td><?= $u['email'] ?></td>

<td><?= $u['role'] ?></td>

</tr>

<?php endforeach; ?>

</table>

<?php
break;
}
?>

</div>

</body>
</html>