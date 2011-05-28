<html>
<head>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<title>
<?php 
include 'qualifier.php';
include 'users.php';

$q = new Qualifier(false);

$city = $q->Get("city");
$userId = $q->Get("userId");
$s = new Sql($city);
$u = new Users($s);

?>
</title>

<link href='http://fonts.googleapis.com/css?family=Ubuntu&subset=cyrillic,latin' rel='stylesheet' type='text/css' />
<link href="map_noads.css" rel="stylesheet" type="text/css">
<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>

<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>

<script type="text/javascript" src="util.js"></script>
<script type="text/javascript" src="helpers.js"></script>
<script type="text/javascript" src="search.js"></script>
</head>
<?php

$lat = 0;
$lon = 0;
$med = 0;
$std = 0;

$result = $s->GetTable("SELECT lat, lon FROM City WHERE mode = 0;");
if($result != null)
while ($row = $result->fetch ())
{
  $lat = $row['lat'];
  $lon = $row['lon'];
}

function Body($city, $lat, $lon, $zoom, $userId)
{
  $t = '<body style="font-family : \'Ubuntu\', sans-serif; margin:0px; padding:0px;" onload="initialize(\'' . $city . '\', ' . $lat . ', ' . $lon . ', ' . $zoom . ', ' . $userId . ')">';
  $t .= '<div id="map_canvas" style="width:100%; height:100%"></div>';
  $t .= '</body>';
  return $t;
}

echo Body($city, $lat, $lon, 13, $userId);

?>
</html>
    
