
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<html> 
 <head> 
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 
    <title>Last update timestamps per city/mode/service</title> 
</head> 
<body> 

<?php
include 'qualifier.php';
include 'sql.php';
$s = new Sql('main');

$query = "select * from (select TIMEDIFF(now(), max(started)) as dur, city, mode from main.Updates group by city,mode) a order by dur desc;";
PrintTable($s, $query, 2);
echo '<br/><br/><br/><br/>';
$query = "SELECT * FROM (SELECT TIMEDIFF(now(), max(started)) as dur, city, mode, source FROM main.Updates GROUP BY city,mode,source) a ORDER BY dur DESC;";
PrintTable($s, $query, 3);

function PrintTable($s, $query, $cols)
{
  $data = $s->GetTable($query);

  if($data != null)
  { 
    echo "<table>";
    while ($row = $data->fetch ())
    {
      echo '<tr>';
      $duration = $row[0];
      $label = $row[1] . '-' . $row[2];
      if(count($row) == $cols)
      {
        $label = $label . '-' . $row[3];
      }
      echo "<td>$duration</td><td>$label</td>";
      echo '</tr>';
    }   
    echo "</table>";
  }
}
?>

 
 </body> 
</html>
