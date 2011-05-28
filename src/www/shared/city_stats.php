<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
  <?php
    include 'qualifier.php';
    include 'sql.php';
    $q = new Qualifier(false);
    $city = $q->Get("city");
    echo "<title>User statistics for" . ucfirst($city) . "</title>";
  ?>
  <meta http-equiv="content-type" content="text/html;charset=utf-8" />
  <script type="text/javascript" src="http://danvk.org/dygraphs/dygraph-combined.js"></script>
</head>

<body>
<div id="labels"></div>
<div style="position:absolute; top:100px; width:1400px; height:500px;" id="graphdiv"></div>
<script type="text/javascript">
  g = new Dygraph(
  document.getElementById("graphdiv"),
<?
  $s = new Sql("main");
  $step = 60 * $q->GetDf("step", 5);
  //$result = $s->GetTable("SELECT from_unixtime(floor(unix_timestamp(c1.ts)/($step))*($step)) AS stamp, c1.cnt AS rooms, c2.cnt AS apts FROM $city.City c1 INNER JOIN (SELECT from_unixtime(floor(unix_timestamp(ts)/($step))*($step)) AS stamp, cnt FROM $city.City WHERE mode = 1) AS c2 ON from_unixtime(floor(unix_timestamp(c1.ts)/($step))*($step)) = c2.stamp AND c1.mode = 0 GROUP BY c2.stamp ORDER BY c2.stamp;");
  $query = "select tried, searched, users, FROM_UNIXTIME(ROUND(UNIX_TIMESTAMP(ts)/($step))*$step) AS tsmp from Stats where city IN (select id from Cities where name LIKE '$city') order by city, ts;";
  $result = $s->GetTable($query);
  $first = true;
  if($result != null)
  while ($row = $result->fetch ())
  {
    if($first) { $first = false; } else { echo ' +' . "\n"; }
    echo '"' . $row['tsmp'] . ', ' . $row['tried'] . ', ' . $row['searched'] . ', ' . $row['users'] . '\n"';
  }
?>,
{
  labels: [ "Date", "Visitors", "Tried", "Users"],
  labelsDiv: document.getElementById("labels"), 
  rollPeriod: 1,  //intervals in rolling average
  strokeWidth: 2
});
</script>
</body>
</html>

