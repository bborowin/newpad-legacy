
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

<div id="placeholder" style="width:1100px;height:700px;"></div>
<?php
include 'qualifier.php';
include 'sql.php';
$q = new Qualifier(false);
$city = $q->Get("city");
$s = new Sql($city);
$step = 60 * $q->GetDf("step", 15);


function GetHourly($s, $var,$city,$mode,$source)
{
  echo "var $var = [";
  $m = 60 * 60;
  //$data = $s->GetTable("select concat(city,'-',mode,'-',source) as ut, sum(timestampdiff(SECOND, started, ended)) as dur, sum(new) as urls, hour(started)* 60 + minute(FROM_UNIXTIME(floor(UNIX_TIMESTAMP(started)/$m)*$m)) AS slice from main.Updates where city LIKE '$city' and mode = '$mode' and source = '$source' group by city,mode,source,slice having urls > 0 order by slice;");
  $data = $s->GetTable("select sum(new) as urls, hour(started) as slice from main.Updates where city LIKE '$city' and mode = '$mode' and source = '$source' group by city,mode,source,slice having urls > 0 order by slice;");

  $first = true;
  if($data != null)
  while ($row = $data->fetch ())
  {
    if($first) { $first = false; } else { echo ", \n"; }
    echo '[' . $row['slice'] . ', ' . $row['urls'] . ']';
  }   
  echo "];\n";
}
?>
<script id="source" language="javascript" type="text/javascript"> 
$(function () {
<?
  GetHourly($s, "d1", $city, "ap", "cl");
  GetHourly($s, "d2", $city, "ap", "kij");
  GetHourly($s, "d3", $city, "rm", "cl");
  GetHourly($s, "d4", $city, "rm", "kij");
?>

  var options = {
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
