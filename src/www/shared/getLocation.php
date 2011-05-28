<?php
include 'qualifier.php';
include 'sql.php';

$q = new Qualifier(false);
$a = $q->Get("address");

//api key
$key = "BQIAAAA6hOLU1bkvOi-fZadsQJG8hQ9LXX-QqSpcWug2XD7MA_Eoc-UmBQcum01W8I0uUvYAUTkAX_k-AQ-XA";
//query url
$query = "http://maps.google.com/maps/geo?key=$key&output=json&q=$a";
//print result
echo file_get_contents($query);

?>
