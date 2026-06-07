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

if(
!empty($product['image'])
&&
file_exists(
"uploads/".
$product['image']
)
){

unlink(
"uploads/".
$product['image']
);

}

$db->execute(

"DELETE FROM products
WHERE id=?",

[$id]

);

header(
"Location: dashboard.php?module=product"
);

exit;