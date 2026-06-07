<?php

require_once "db-utils.php";

$db = new DB_UTILS();

$id =
(int)$_GET['id'];

$product =
$db->getOne(

"SELECT *
FROM products
WHERE id=?",

[$id]

);

$categories =
$db->getAll(
"SELECT * FROM categories"
);

if($_SERVER['REQUEST_METHOD']=='POST'){

    $imageName =
    $product['image'];

    if(
    isset($_FILES['image'])
    &&
    $_FILES['image']['error']==0
    ){

        $imageName =
        time().'_'.
        $_FILES['image']['name'];

        move_uploaded_file(

        $_FILES['image']['tmp_name'],

        "uploads/".$imageName

        );
    }

  $db->execute(

"UPDATE products
SET
category_id=?,
name=?,
price=?,
image=?,
image_url=?,
description=?
WHERE id=?",

  [
    $_POST['category_id'],
    $_POST['name'],
    $_POST['price'],
    $imageName,
    trim($_POST['image_url']),
    $_POST['description'],
    $id
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
<title>Sửa sản phẩm</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="edit-container">

    <div class="edit-card">

        <h2>Sửa sản phẩm</h2>

        <form
        method="POST"
        enctype="multipart/form-data">

            <div class="form-group">

                <label>Tên sản phẩm</label>

                <input
                type="text"
                name="name"
                value="<?= htmlspecialchars($product['name']) ?>"
                required>

            </div>

            <div class="form-group">

                <label>Danh mục</label>

                <select name="category_id">

                    <?php foreach($categories as $c): ?>

                    <option
                    value="<?= $c['id'] ?>"
                    <?= $c['id']==$product['category_id'] ? 'selected' : '' ?>>

                        <?= htmlspecialchars($c['name']) ?>

                    </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-group">

                <label>Giá sản phẩm</label>

                <input
                type="number"
                name="price"
                value="<?= $product['price'] ?>"
                required>

            </div>

            <div class="form-group">

                <label>Ảnh hiện tại</label>
                <div class="form-group">

    <label>URL hình ảnh</label>

    <input
    type="text"
    name="image_url"
    value="<?= htmlspecialchars($product['image_url'] ?? '') ?>"
    placeholder="https://example.com/image.jpg">

</div>

                <br>

               <?php

if(!empty($product['image_url'])){

?>

<img
src="<?= htmlspecialchars($product['image_url']) ?>"
class="preview-image">

<?php

}
elseif(!empty($product['image'])){

?>

<img
src="uploads/<?= rawurlencode($product['image']) ?>"
class="preview-image">

<?php

}

?>

            </div>

            <div class="form-group">

                <label>Đổi ảnh</label>

                <input
                type="file"
                name="image">
                

            </div>

            <div class="form-group">

                <label>Mô tả</label>

                <textarea
                name="description"><?= htmlspecialchars($product['description']) ?></textarea>

            </div>

            <button
            type="submit"
            class="save-btn">

                Cập nhật sản phẩm

            </button>

        </form>

    </div>

</div>

</body>
</html>