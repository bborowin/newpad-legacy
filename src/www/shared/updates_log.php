
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<html> 
 <head> 
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 
    <title>Update log</title> 
    <link href="layout.css" rel="stylesheet" type="text/css"></link> 
    <!--[if IE]><script language="javascript" type="text/javascript" src="http://people.iola.dk/olau/flot/excanvas.min.js"></script><![endif]--> 
    <script language="javascript" type="text/javascript" src="http://people.iola.dk/olau/flot/jquery.js"></script> 
    <script language="javascript" type="text/javascript" src="http://people.iola.dk/olau/flot/jquery.flot.js"></script> 
    <script language="javascript" type="text/javascript" src="http://people.iola.dk/olau/flot/jquery.flot.stack.js"></script> 
    <script language="javascript" type="text/javascript" src="http://people.iola.dk/olau/flot/jquery.flot.navigate.js"></script> 
    <style> 
    #placeholder .button {
        position: absolute;
        cursor: pointer;
    }
    #placeholder div.button {
        font-size: smaller;
        color: #999;
        background-color: #eee;
        padding: 2px;
    }
    .message {
        padding-left: 50px;
        font-size: smaller;
    }
    </style>
</head> 
<body> 

<div id="placeholder" style="width:1400px;height:700px;"></div>
<?php
include 'qualifier.php';
include 'sql.php';
$q = new Qualifier(false);
$city = $q->Get("city");
$s = new Sql($city);

function GetData($s, $var,$city,$mode,$source)
{
  echo "var $var = [";
  $data = $s->GetTable("select new, UNIX_TIMESTAMP(started)*1000 - 5000*3600 as started from main.Updates where city LIKE '$city' and mode = '$mode' and source = '$source' order by city, started;");

  $first = true;
  if($data != null)
  while ($row = $data->fetch ())
  {
    if($first) { $first = false; } else { echo ", \n"; }
    echo '[' . $row['started'] . ', ' . $row['new'] . ']';
  }   
  echo "];\n";
}
function GetTime($s, $var,$city,$mode,$source)
{
  echo "var $var = [";
  $data = $s->GetTable("select new, UNIX_TIMESTAMP(started)*1000 as started, timestampdiff(SECOND, started, ended) / 60.0 AS totsec from main.Updates where city LIKE '$city' and mode = '$mode' and source = '$source' order by city, started;");

  $first = true;
  if($data != null)
  while ($row = $data->fetch ())
  {
    if($first) { $first = false; } else { echo ", \n"; }
    echo '[' . $row['started'] . ', ' . $row['totsec'] . ']';
  }   
  echo "];\n";
}
function GetHourly($s, $var,$city,$mode,$source)
{
  echo "var $var = [";
  $data = $s->GetTable("select concat(city,'-',mode,'-',source) as ut, sum(timestampdiff(SECOND, started, ended)) as dur, sum(new) as urls, hour(started) from main.Updates  where city LIKE '$city' and mode = '$mode' and source = '$source' group by city,mode,source,hour(started) order by hour(started) desc, minute(started) desc;");

  $first = true;
  if($data != null)
  while ($row = $data->fetch ())
  {
    if($first) { $first = false; } else { echo ", \n"; }
    echo '[' . $row['started'] . ', ' . $row['totsec'] . ']';
  }   
  echo "];\n";
}
?>
<script id="source" language="javascript" type="text/javascript"> 
$(function () {
<?
  GetData($s, "d1", $city, "ap", "cl");
  GetData($s, "d2", $city, "ap", "kij");
  GetData($s, "d3", $city, "rm", "cl");
  GetData($s, "d4", $city, "rm", "kij");
/*
  GetTime($s, "d1", $city, "ap", "cl");
  GetTime($s, "d2", $city, "ap", "kij");
  GetTime($s, "d3", $city, "rm", "cl");
  GetTime($s, "d4", $city, "rm", "kij");
*/
  
  $maxTs = $s->GetScalar("SELECT UNIX_TIMESTAMP(MAX(started))*1000 FROM main.Updates WHERE city = '$city';");
  echo "var maxTs = $maxTs;";
?>

  var options = {
      series: { bars: { show: false, barWidth: 720000 }, lines: { show: true, steps: false }, points: { show: true }, shadowSize: 0 },
      xaxis: { mode: "time", min: maxTs - 100000000, max: maxTs },
      yaxis: { min: 0, max: 100 },
      y2axis: { min: 0, max: 10 },
      zoom: {
          interactive: true
      },
      pan: {
          interactive: true
      }
  };
    
  $.plot($("#placeholder"), [{ label: "ap-cl",  data: d1},{ label: "ap-kij",  data: d2},{ label: "rm-cl",  data: d3},{ label: "rm-kij",  data: d4}], options);
  //$.plot($("#placeholder"), [{label: "count",  data: d1}, {label: "duration",  data: t1, yaxis:2}], options);
});
</script> 
 
 </body> 
</html>
