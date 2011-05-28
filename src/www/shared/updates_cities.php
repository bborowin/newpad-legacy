
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

function GetCity($s, $var,$city)
{
  echo "var $var = [";
  $data = $s->GetTable("select avg(new) as urls, hour(started)* 60 + minute(FROM_UNIXTIME(floor(UNIX_TIMESTAMP(started)/900)*900)) AS slice from main.Updates where started > '2010-02-20' AND city LIKE '$city' group by city,slice order by slice;");

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
  $cities = $s->GetTable("SELECT id,name from main.Cities");
  foreach($cities as $c)
  {
    $id = $c[0];
    GetCity($s, "d$id", $c[1]);
  }
?>

  var options = {
      series: { lines: { show: true, steps: false }, shadowSize: 0 },
      y2axis: { min: 0, max: 10 },
      zoom: {
          interactive: true
      },
      pan: {
          interactive: true
      }
  };
    
  $.plot($("#placeholder"), [ <?
  $cnt = 0;
  $cities = $s->GetTable("SELECT id,name from main.Cities");
  foreach($cities as $c)
  {
    $id = $c[0];
    $city = $c[1];
    if(0 != $cnt) echo ", ";
    echo "{label: \"$city\", data: d$id}";
    $cnt += 1;
  }
?>  ], options);
});
</script> 
 
 </body> 
</html>
