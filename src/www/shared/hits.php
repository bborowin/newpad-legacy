<?php
include 'qualifier.php';
include 'users.php';

$q = new Qualifier(false);
$city = $q->Get("city");
$mode = $q->Get("mode");

$s = new Sql($city);
$u = new Users($s);
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

  //initialize max results count
  $count = $q->Get('count');
  $userId = $q->Get('userId');

  $lat = $q->Get('lat');
  $lon = $q->Get('lon');
  
  //query for apartments
  $query  = "SELECT lat, lon, timestampdiff(DAY,ts,now()) AS age, round(12+(2.2*ln(radius))) FROM Searches WHERE userId = $userId AND timestampdiff(DAY,ts,now()) < 15;";

  //now output the data to a simple html table...
  echo '<?xml version="1.0" encoding="ISO-8859-1"?>';
  echo '<markers city="' . $city . '">';
  $result = $s->GetTable($query);
  if($result != null)
  while ($row = $result->fetch ())
  {
    echo '<pt ';
    echo 'lat="' . $row['lat'] . '" ';
    echo 'lng="' . $row['lon'] . '" ';
    echo 'r="' . $row['radius'] . '" ';
    echo 'ts="' . $row['age'] . '" ';
    echo '></pt>';
  }
  echo "</markers>";

?>
