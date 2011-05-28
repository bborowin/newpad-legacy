<?php
include 'qualifier.php';
include 'sql.php';
$q = new Qualifier(false);
$city = $q->Get("city");
$s = new Sql($city);
$result = $s->GetTable("SELECT userId, count(*) AS hits, min(ts) as first, max(ts) AS last, sec_to_time(max(ts)-min(ts)) as duration FROM $city.Searches WHERE userId > 2 GROUP BY userId HAVING count(*) > 1 ORDER BY max(ts) desc;");

// with duration in hours:
//SELECT userId, count(*) AS hits, min(ts) as first, max(ts) AS last, sec_to_time(max(ts)-min(ts)) as duration FROM $city.Searches WHERE userId > 2 GROUP BY userId ORDER BY max(ts) desc;

$first = true;
echo '{ "aaData" : [ ';
if($result != null)
while ($row = $result->fetch ())
{
  if($first) { $first = false; } else { echo ", \n"; }
  echo '["<a href=\"../staging/searches.php?city=' . $city . '&userId=' . $row['userId'] . '\">' . $row['userId'] . '</a>", "' . $row['hits'] . '", "' . $row['first'] . '", "' . $row['last'] . '"]';
}
echo ' ] }';
?>
