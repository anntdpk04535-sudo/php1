<?php

require_once "db-utils.php";

$db = new DB_UTILS();

$list = $db->getAll(
    "SELECT * FROM categories
     ORDER BY id DESC"
);

?>

<!DOCTYPE html>
<html>

<head>
    <title>Categories</title>

    <link rel="stylesheet"
    href="style.css">
</head>

<body>

<div class="container">

    <h2>Danh sách sản phẩm</h2>

    <br>

    <a
    href="create.php"
    class="btn">
    Thêm sản phẩm
    </a>

    <br><br>

    <table>

        <tr>
            <th>ID</th>
            <th>Tên danh mục</th>
            <th>Mô tả</th>
            <th>Hành động</th>
        </tr>

        <?php foreach($list as $item): ?>

        <tr>

            <td>
                <?= $item['id'] ?>
            </td>

            <td>
                <?= $item['name'] ?>
            </td>

            <td>
                <?= $item['description'] ?>
            </td>

            <td>

                <a
                href="edit.php?id=<?= $item['id'] ?>"
                class="btn">
                Sửa
                </a>

                <a
                href="delete.php?id=<?= $item['id'] ?>"
                class="btn"
                onclick="return confirm('Xóa?')">
                Xóa
                </a>

            </td>

        </tr>

        <?php endforeach; ?>

    </table>

</div>

</body>
</html>