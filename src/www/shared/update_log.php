<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
  <title>Updates history</title>
  <meta http-equiv="content-type" content="text/html;charset=utf-8" />
  <script type="text/javascript" src="http://danvk.org/dygraphs/dygraph-combined.js"></script>
</head>

<body>
<div id="labels"></div>
<div style="position:absolute; top:100px; width:1400px; height:500px;" id="graphdiv"></div>
<script type="text/javascript">
  g = new Dygraph(
  document.getElementById("graphdiv"),
<?php
include 'qualifier.php';
include 'sql.php';
$q = new Qualifier(false);
$city = $q->Get("city");
$s = new Sql($city);
$step = 60 * $q->GetDf("step", 15);
$mode = $q->GetDf("mode", "ap");
$src = $q->GetDf("src", "cl");
$result = $s->GetTable("select concat(city, '-', mode, '-', source) as foobar, timestampdiff(SECOND, started, ended) AS totsec, new, started, ended from main.Updates where city LIKE '$city' and mode = '$mode' and source = '$src' order by city, mode, source, started, ended;");
//$result = $s->GetTable("select concat(city, '-', mode, '-', source) as foobar, sum(new) as new, started from main.Updates where city LIKE '$city' and mode = '$mode' and source = '$src' group by city, mode, started order by city, mode, source, started;");

//$result = $s->GetTable("SELECT from_unixtime(floor(unix_timestamp(c1.ts)/($step))*($step)) AS stamp, c1.cnt AS rooms, c2.cnt AS apts FROM $city.City c1 INNER JOIN (SELECT from_unixtime(floor(unix_timestamp(ts)/($step))*($step)) AS stamp, cnt FROM $city.City WHERE mode = 1) AS c2 ON from_unixtime(floor(unix_timestamp(c1.ts)/($step))*($step)) = c2.stamp AND c1.mode = 0 GROUP BY c2.stamp ORDER BY c2.stamp;");


$first = true;
if($result != null)
while ($row = $result->fetch ())
{
  if($first) { $first = false; } else { echo ' +' . "\n"; }
  echo '"' . $row['started'] . ', ' . $row['new'] . ', ' . $row['foobar'] . '\n"';
}
?>,
{
  labels: [ "Date", "Added", "Source" ],
  labelsDiv: document.getElementById("labels"), 
  rollPeriod: 1,  //intervals in rolling average
  strokeWidth: 1
});
</script>
</body>
</html>

