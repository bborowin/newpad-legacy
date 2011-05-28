<?php 
include 'qualifier.php';
include 'users.php';

$q = new Qualifier(true);

$city = $q->Get("city");
$u = new Users();
$userId = $u->GetUserId();
echo $city . ' ' . $userId . ' ';
?>
