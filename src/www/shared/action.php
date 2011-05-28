<?php
include 'qualifier.php';
include 'users.php';

$q = new Qualifier(false);
$u = new Users();

$userId = $u->GetUserId();
$action = $q->Get("action");

switch($action)
{
  case "disableIntroDiv":
    $u->Set($userId, "hideIntroDiv", "true");
    break;
  default:
    break;
}
?>
