<?php
include 'qualifier.php';
include 'users.php';

//get params
$q = new Qualifier();
$city = $q->Get("city");
$mode = $q->Get("mode");


//hook up user & db
$s = new Sql($city);
$u = new Users();
$userId = $u->GetUserId();
header("Content-type: text/xml; charset=ISO-8859-1");

//build query to retrieve ALL active posts
$query  = "SELECT id, lat, lon, price, url, title, age, mode, IFNULL(f.flag, 0) AS flag, IFNULL(a.value, '') AS attr FROM $city.Posts p ";
$query .= "LEFT JOIN (SELECT flag, postingId FROM $city.Flags WHERE userId = $userId) f ON f.postingId = p.id ";
$query .= "LEFT JOIN Attributes a ON p.id = a.postingId ";
$query .= "WHERE mode = $mode AND p.age < 15 AND IFNULL(f.flag, 0) NOT IN (3,4,5) ";
$query .= "ORDER BY age;";
$posts = RetrievePosts($s, $query);

//output data in XML
echo MakeXML($city, $userId, $posts);


// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

function parseToXML($htmlStr) 
{ 
  $xmlStr=str_replace('<','&lt;',$htmlStr); 
  $xmlStr=str_replace('>','&gt;',$xmlStr); 
  $xmlStr=str_replace('"','&quot;',$xmlStr); 
  $xmlStr=str_replace("'",'&#39;',$xmlStr); 
  $xmlStr=str_replace("&",'&amp;',$xmlStr); 
  return $xmlStr; 
} 

//Retrieve postings within search area and place in array
function RetrievePosts($s, $query)
{
  $posts = array();
  $result = $s->GetTable($query);
  if($result != null) while ($row = $result->fetch())
  {
    $post = array();
    array_push($post, $row['id']);
    array_push($post, $row['lat']);
    array_push($post, $row['lon']);
    array_push($post, $row['price']);
    array_push($post, $row['title']);
    array_push($post, $row['address']);
    array_push($post, $row['url']);
    array_push($post, $row['age']);
    array_push($post, $row['flag']);
    array_push($post, $row['attr']);
    
    array_push($posts,$post);
  }
  
  return $posts;
}

function MakeXML($city, $userId, $posts)
{
  //get the top/bottom 5% prices
  $count = count($posts);
  $min_idx = floor($count * 0.05);
  $max_idx = floor($count * 0.95);
  $min_price = 0;
  $max_price = 10000;
  if ($count > 0)
  {
    $min_price = $posts[$min_idx][3];
    $max_price = $posts[$max_idx][3];
  }

  //prepare output
  $out = '<?xml version="1.0" encoding="ISO-8859-1"?>';
  $out .= '<markers sig="' . $city . '- ' . $userId . '">';
  $out .=  "<stats count=\"$count\" btm=\"$min_price\" top=\"$max_price\"></stats>";
  foreach($posts as $p)
  {
    $out .= '<pt ';
    $out .= 'id="' . $p[0] . '" ';
    $out .= 'lat="' . $p[1] . '" ';
    $out .= 'lng="' . $p[2] . '" ';
    $out .= 'price="' . parseToXML($p[3]) . '" ';
    $out .= 'title="' . parseToXML($p[4]) . '" ';
    //$out .= 'address="' . parseToXML($p[5]) . '" ';
    $out .= 'url="' . parseToXML($p[6]) . '" ';
    $out .= 'age="' . parseToXML($p[7]) . '" ';
    $out .= 'flag="' . $p[8] . '" ';
    $out .= 'attr="' . $p[9] . '" ';
    $out .= '></pt>';
  }
  $out .= "$pts</markers>\n";
  
  return $out;
}
?>
