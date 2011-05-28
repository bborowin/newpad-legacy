<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<html> 
 <head> 
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 
  <title>Location Search</title> 
  <script src="http://maps.google.com/maps?file=api&amp;v=2.x&amp;key=ABQIAAAAzr2EBOXUKnm_jVnk0OJI7xSosDVG8KKPE1-m51RBrvYughuyMxQ-i1QfUnH94QxWIa6N4U6MouMmBA" type="text/javascript"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
  <style> 
  </style>
 </head> 
 <body> 

<script type="text/javascript">

function Submit(element, event)
{
  var keycode;
  if (window.event) keycode = window.event.keyCode;
  else if (e) keycode = e.which;
  else return true;

  if (keycode == 13)
  {
    query = encodeURI(element.value);
    GetLoc(query);
    /*
    $.get('getLocation.php?address=' + query, function(data) {
      ProcessResult(data);
    });
    */
    element.value = '';
    return false;
  }
  else return true;
}

function GetLoc(addr)
{
        geocoder = new GClientGeocoder();
  

}
function ProcessResult(data, address)
{
  if( null == data || '' == data)
  {
    $('#result').html("Cannot find");
  }
  else
  {
    var response = '';
    var result = JSON.parse(data);
    response += result.Status.code + "<br/>";
    for(i in result.Placemark)
    {
      pl = result.Placemark[i]
      response += pl.address + " (" + pl.AddressDetails.Accuracy + ") @ [" + pl.Point.coordinates[0] + "," + pl.Point.coordinates[1] + "]<br/>";
    }
    $('#result').html(response);
  }
}
</script>

<div>Where? <input type="text" onkeydown="Submit(this, event);"></input></div>
<div id="result"></div>
<?php
include 'qualifier.php';
include 'sql.php';
$q = new Qualifier(false);
$s = new Sql('main');

echo '<select onchange="return false;">';
echo "<option value='none'></option>";
$cities = $s->GetTable("SELECT name, lat, lon FROM main.Cities order by name");
foreach($cities as $row)
{
  $db_name = $row[0];
  $city_name = ucfirst($row[0]);
  echo "<option value=$db_name>$city_name</option>";
}
echo "</select>";
?>    
 
 </body> 
</html>
