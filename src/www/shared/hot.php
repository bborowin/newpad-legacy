<?php
include 'qualifier.php';
include 'users.php';

$q = new Qualifier(false);
$city = $q->Get("city");

$s = new Sql($city);
$u = new Users();
$userId = $u->GetUserId();

function parseToXML($htmlStr) 
{ 
  $xmlStr=str_replace('<','&lt;',$htmlStr); 
  $xmlStr=str_replace('>','&gt;',$xmlStr); 
  $xmlStr=str_replace('"','&quot;',$xmlStr); 
  $xmlStr=str_replace("'",'&#39;',$xmlStr); 
  $xmlStr=str_replace("&",'&amp;',$xmlStr); 
  return $xmlStr; 
} 


  //initialize max results count
  $count = $q->Get('count');

  //query for apartments
  $query  = "SELECT title, price, url, count(*) AS cnt, mode FROM Flags f INNER JOIN Posts p on p.id = f.postingId WHERE flag = 1 group by postingId having count(*) > $count ORDER BY count(*) DESC, p.age LIMIT 25;";
  $result = $s->GetTable($query);
  
  if($result != null)
  while ($row = $result->fetch ())
  {
    $url = $row['url'];
    $title = $row['title'];
    $price = $row['price'];
    echo "<a href=\"$url\">$title</a><br/>";;
  }
?>

