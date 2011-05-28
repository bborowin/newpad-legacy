
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<html> 
 <head> 
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 
    <title>Average update density in posts per minute</title> 
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
$step = $q->GetDf("step", 30);

function GetHourly($s, $var, $key, $step)
{
  echo "var $var = [";
  $data = $s->GetTable("SELECT floor(idx/$step)*$step AS foo, sum(cnt) / $step as cnt FROM main.Buckets WHERE uId LIKE '$key' GROUP BY foo ORDER BY idx;");

  $first = true;
  if($data != null)
  while ($row = $data->fetch ())
  {
    if($first) { $first = false; } else { echo ", \n"; }
    echo '[' . $row['foo'] / 60 . ', ' . $row['cnt'] . ']';
  }   
  echo "];\n";
}
?>
<script id="source" language="javascript" type="text/javascript"> 
$(function () {
<?
  GetHourly($s, "d1", "$city-ap-cl", $step);
  GetHourly($s, "d2", "$city-ap-kij", $step);
  GetHourly($s, "d3", "$city-rm-cl", $step);
  GetHourly($s, "d4", "$city-rm-kij", $step);
?>

  var options = {
      //series: { stack: 0, bars: { barWidth: <? echo $step ?>, show: true }, shadowSize: 0 },
      series: { lines: { show: true, steps: false }, points: { show: true }, shadowSize: 0 },
      y2axis: { min: 0, max: 10 },
      zoom: {
          interactive: true
      },
      pan: {
          interactive: true
      }
  };
    
  $.plot($("#placeholder"), [{ label: "ap-cl",  data: d1},{ label: "ap-kij",  data: d2},{ label: "rm-cl",  data: d3},{ label: "rm-kij",  data: d4}], options);
});
</script> 
 
 </body> 
</html>
