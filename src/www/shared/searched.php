<?php
include 'qualifier.php';
include 'users.php';

$q = new Qualifier();
$city = $q->Get("city");
$mode = $q->Get("mode");

$s = new Sql($city);
$u = new Users();
$userId = $u->GetUserId();


header("Content-type: text/xml; charset=ISO-8859-1");

$lat = $q->Get('lat');
$lon = $q->Get('lon');
$radius = $q->Get('radius');
$mode = $q->Get('mode');
$min = $q->Get('min');
$max = $q->Get('max');


//log the search
$query = "INSERT INTO Searches VALUES($userId, $lat, $lon, $radius, $min, $max, DEFAULT, $mode); ";
$s->ExecQuery($query);

?>
