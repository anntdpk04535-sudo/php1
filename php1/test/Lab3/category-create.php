<?php

require_once "db-utils.php";

$db = new DB_UTILS();

$message = "";

if($_SERVER['REQUEST_METHOD']=='POST'){

    $name =
    trim($_POST['name']);

    $description =
    trim($_POST['description']);

    if(empty($name)){

        $message =
        "Tên category không được để trống";

    }else{

        $db->execute(

        "INSERT INTO categories(
        name,
        description
        )
        VALUES(?,?)",

        [
            $name,
            $description
        ]

        );

        header(
        "Location: dashboard.php?module=category"
        );

        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Thêm Category</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<div class="form-card">

<h2>Thêm Category</h2>

<?php if(!empty($message)): ?>
<div class="alert-danger">
<?= $message ?>
</div>
<?php endif; ?>

<form method="POST">

<label>Tên Category</label>

<input
type="text"
name="name"
required>

<label>Mô tả</label>

<textarea
name="description">
</textarea>

<button
type="submit"
class="btn btn-success">

Thêm Category

</button>

</form>

</div>

</div>

</body>
</html>