<?php
include 'qualifier.php';
include 'users.php';
$q = new Qualifier(false);
$city = $q->Get("city");
$target = $q->Get('target');
$action = $q->Get('action');

$s = new Sql($city);
$u = new Users();
$userId = $u->GetUserId();

function GetPostingId($url)
{
  $con = end(explode('-', $url));
  $str = end(explode('/', $con));
  $query = "SELECT id FROM Postings WHERE url LIKE '%$str'";
  return GetScalar($query);
}

//Check whether response matches that stored in the DB
function IsExpired($s, $id)
{
  $expired = False;
  $data = null;
  $query = "SELECT url FROM Postings WHERE id = $id;";
  $url = $s->GetScalar($query);
  $query = "SELECT CAST(active as unsigned) FROM Postings WHERE id = $id;";
  $active = $s->GetScalar($query);

  if(1 == $active)
  {
    $data = file_get_contents($url);
    
    $err = array('non accessible', 'This posting has been flagged for removal', 'This posting has been deleted by its author','e par son auteur','is not available','The Ad you are looking for is no longer available', "L'annonce que vous cherchez n'est plus disponible, mais nous avons trouv");
    foreach($err as $e)
    {
      if(false !== strpos($data,$e))
      {
        $expired = True;
        break;
      }
    }
  }
  else
  {
    $expired = True;
  }
  
  return $expired;
}

function MarkIfExpired($s, $id)
{
  if(IsExpired($s, $id))
  {
    $query = "UPDATE Postings SET active = 0 WHERE id = " . $id . ";";
    $s->ExecQuery($query);
    return 1;
  }
  return 0;
}

//echo 'city:' . $city . ' ';
//echo 'userId:' . $userId . ' ';
//echo 'target:' . $target . ' ';
//echo 'action:' . $action . ' ';

$expired = MarkIfExpired($s, $target);

if(null!=$target)
{
  $s->ExecQuery("DELETE FROM Flags WHERE userId = $userId AND postingId = $target;");
    
  switch($action)
  {
    case "seen":
      if(!$expired)
      {
        $query = "INSERT INTO Flags VALUES ($userId, $target, 1, DEFAULT, DEFAULT);";
        $s->ExecQuery($query);
      }
      break;
    case "like":
      $query = "INSERT INTO Flags VALUES ($userId, $target, 2, DEFAULT, DEFAULT);";
      $s->ExecQuery($query);
      break;
    case "dislike":
      $query = "INSERT INTO Flags VALUES ($userId, $target, 3, DEFAULT, DEFAULT);";
      $s->ExecQuery($query);
      break;
    case "unlike":
      $query = "INSERT INTO Flags VALUES ($userId, $target, 1, DEFAULT, DEFAULT);";
      $s->ExecQuery($query);
      break;
    case "spam":
      $query = "INSERT INTO Flags VALUES ($userId, $target, 4, DEFAULT, DEFAULT);";
      $s->ExecQuery($query);
      break;
    case "miscat":
      $query = "INSERT INTO Flags VALUES ($userId, $target, 5, DEFAULT, DEFAULT);";
      $s->ExecQuery($query);
      break;
    default:
      break;
  }
}

echo "{\"id\":$target, \"e\":$expired}";
?>
