<?php

require_once "db-utils.php";

$db = new DB_UTILS();

$id =
(int)$_GET['id'];

$db->execute(

"DELETE FROM categories
WHERE id=?",

[$id]

);

header(
"Location: dashboard.php?module=category"
);

exit;