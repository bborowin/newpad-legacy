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

header("Content-type: text/xml; charset=ISO-8859-1");

  $count = $q->GetDf('count', "10");

  $query  = "SELECT lat, lon, url, count(*) AS age, mode FROM Flags f INNER JOIN Posts p on p.id = f.postingId WHERE f.flag = 1 group by postingId having count(*) >= $count;";

  //now output the data to a simple html table...
  echo '<?xml version="1.0" encoding="ISO-8859-1"?>';
  echo '<markers city="' . $city . '">';
  $result = $s->GetTable($query);
  if($result != null)
  while ($row = $result->fetch ())
  {
    echo '<pt ';
    echo 'url="' . $row['url'] . '" ';
    echo 'lat="' . $row['lat'] . '" ';
    echo 'lng="' . $row['lon'] . '" ';
    echo 'mode="' . $row['mode'] . '" ';
    echo 'ts="' . $row['age'] . '" ';
    echo '></pt>';
  }
  echo "</markers>";

?>
