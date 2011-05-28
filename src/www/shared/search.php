<html>
<head>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<title>

<?php 
include 'qualifier.php';
include 'users.php';

$q = new Qualifier();

$city = $q->Get("city");
$mode = $q->Get("mode");
$s = new Sql($city);
$s_main = new Sql('main');
$u = new Users();
$userId = $u->GetUserId();
$maxHits = intval($u->GetDf($userId, "maxHits", "150"));
$lat = 0;
$lon = 0;
$min = 0;
$max = 0;
$med = 0;
$std = 0;
$radius = 0;
$run = 0;

//get user's last search details
$query = "SELECT lat, lon, radius, low, high FROM $city.Searches WHERE mode = $mode AND userId = $userId ORDER BY ts DESC LIMIT 1;";
$result = $s->GetTable($query);
if($result != null)
while ($row = $result->fetch ())
{
  $lat = $row['lat'];
  $lon = $row['lon'];
  $min = $row['low'];
  $max = $row['high'];
  $radius = $row['radius'];
}

if(0 == $radius)
{
  $zoom = 14;
}
else
{
  $zoom = floor(log(0.32 / $radius, 2) + 10);
  $run = 1;
}

$showAd = ('hide' != $u->Get($userId, "ads"));

//if the user had no previous searches, use city lat/lon
if(0 == $run)
{
  $result = $s_main->GetTable("SELECT lat, lon FROM main.Cities where name LIKE '$city';");
  if($result != null)
  while ($row = $result->fetch ())
  {
    $lat = $row['lat'];
    $lon = $row['lon'];
  }

  $query = "SELECT med, std FROM $city.City WHERE mode = $mode ORDER BY ts DESC LIMIT 1;";
  $result = $s_main->GetTable($query);
  if($result != null)
  while ($row = $result->fetch ())
  {
    $med = $row['med'];
    $std = $row['std'];
  }

  $min = $med - 1.0 * $std;
  $max = $med + 1.0 * $std;
}

$total_count = $s_main->GetScalar("SELECT count(*) FROM $city.Posts WHERE price BETWEEN 1 AND 10000 AND mode = $mode;");

$btm_idx = floor($total_count * 0.05);
$btm_cutoff = $s_main->GetScalar("SELECT price FROM $city.Posts WHERE price BETWEEN 1 AND 10000 AND mode = $mode ORDER BY price LIMIT $btm_idx, 1;");
$btm_cutoff -= $btm_cutoff % 25 + 25;
if($btm_cutoff < 0) $btm_cutoff = 0;

$top_idx = floor($total_count * 0.9);
$top_cutoff = $s_main->GetScalar("SELECT price FROM $city.Posts WHERE price BETWEEN 1 AND 10000 AND mode = $mode ORDER BY price LIMIT $top_idx, 1;");
$top_cutoff -= $top_cutoff % 25 - 25;

?>
</title>

<link href='http://fonts.googleapis.com/css?family=Ubuntu&subset=cyrillic,latin' rel='stylesheet' type='text/css' />
<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
<?
echo '<link href="map_noads.css?time=' . filemtime($map_css) . '" rel="stylesheet" type="text/css" />';
?>


<script type="text/javascript" src="http://maps.google.com/maps/api/js?v=3.2&sensor=false"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>

<script type="text/javascript" src="helpers.js?time=<?php echo(filemtime("helpers.js")); ?>"></script>
<script type="text/javascript" src="price_slider.js?time=<?php echo(filemtime("price_slider.js")); ?>"></script>
<script type="text/javascript" src="manager.js?time=<?php echo(filemtime("manager.js")); ?>"></script>
<script type="text/javascript" src="markers.js?time=<?php echo(filemtime("markers.js")); ?>"></script>
<script type="text/javascript" src="env.js?time=<?php echo(filemtime("env.js")); ?>"></script>
</head>

<script type="text/javascript">
  <?
    echo "$.min_price = $btm_cutoff;";
    echo "$.max_price = $top_cutoff;";
  ?>
  var cache = Object();
  cache.data = Array();  //raw postings' data
  cache.visible = Array();  //subset of would-be-visible postings
</script>
<body onload="Init(<? echo '\'' . $city . '\', ' . $mode . ', ' . $lat . ', ' . $lon . ', ' . $zoom . ', ' . $maxHits . ');';?>">

  <div id="map_canvas"></div>
  
  <div id="prices"><div id="amount">please wait</div></div>
  <div style="background-image: url('img/price_gradient.png');" id="slider_container">
    <div id="slider" style="background-image: url('img/price_gradient.png'); margin:0em; height:100%;"></div>
  </div>
  <div id='cpDiv'>Copyright 2011 Newpad.ca</div>
  </div></div>
  
  <div id="location_container">looking for
  <select id="floor" onchange="filters.floor=$(this).val(); ShowMarkers();">
    <option value="-1"> </option>
    <option value="0">basement</option>
    <option value="1">first floor</option>
    <option value="2">second floor</option>
    <option value="3">third floor</option>
    <option value="999">top floor</option>
  </select>  
  <select id="size" onchange="filters.size=$(this).val(); ShowMarkers();">
    <option value="-1"> </option>
    <option value="0">studio</option>
    <option value="1">1 bedroom</option>
    <option value="2">2 bedroom</option>
    <option value="3">3 bedroom</option>
    <option value="4">4 bedroom</option>
    <option value="5">5 bedroom</option>
    <option value="6">6 bedroom</option>
    <option value="7">7 bedroom</option>
    <option value="8">8 bedroom</option>
    <option value="9">9 bedroom</option>
  </select>
  <select id="mode_dpdn" onchange="window.location.href = 'search.php?city=' + $('#location_dpdn').val() + '&mode=' + value">
  <?
    $query = "SELECT name FROM main.Cities ORDER BY name;";
    $result = array(array(0,'rooms'),array(1,'apartments'),array(2,'commercial'));
    foreach($result as $row)
    {
      $value = $row[0];
      $name = $row[1];
      $selected = '';
      if($_SESSION['mode'] == $value) $selected = 'selected="selected"';
      echo "<option $selected value=\"$value\">$name</option>";
    }
  ?>
  </select>
  in
  <select id="location_dpdn" onchange="window.location.href = 'search.php?city=' + value + '&mode=' + $('#mode_dpdn').val()">
  <?
    $query = "SELECT name FROM main.Cities ORDER BY name;";
    $result = $s_main->GetTable($query);
    foreach($result as $row)
    {
      $name = $row[0];
      $selected = '';
      if($_SESSION['city'] == $name) $selected = 'selected="selected"';
      echo "<option $selected value=\"$name\">$name</option>";
    }
  ?>
  </select>
  </div>
  <div onclick="$('#help').toggle();" id="help_button"><img src="img/help.png" /></div>
  <div id="help">
The first time you use this site, it might take a minute to load all the data -- subsequent visits should be a bit more zippy.<br/>
<br/>
To search, click on the map where you want to live. You can also set the price range with the slider on the right, and additional criteria (like apartment size and which floor it is on) using the filter bar at the top of the map. There you can also choose your city, and whether you're looking for an apartment or a room.<br/>
Once you click the map, some results will appear inside the search area. Each place is marked by one dot, and the search is limited to show at most a hundred dots at a time. If you zoom out and click, you will see the newest places that best match your search criteria. The more you zoom in, the closer you can inspect a small area -- you will see older postings, and those that could not be matched as well to your criteria.<br/>
<br/>
There's a lot of information packed into each dot -- first, its location, which sometimes is exact and sometimes only as precise as postal code or nearest intersection. Second, the opacity -- the more transcluscent a dot, the older the posting. The colour intensity (dark to light) corresponds to the price relative to the price range you've specified. Those are pretty close, visually, but you can see the difference, and tell recent cheap places from old expensive ones, etc.  Finally, the size of the dot corresponds to its relevance given your search criteria -- a better match will be represented with a bigger dot.<br/>
<br/>
<br/>
TL;DR:<br/>
<br/>
Set your search criteria, click on the map where you want to live, and look at the bigger, less faded, lighter-coloured dots first -- those will be the cheapest places that match your criteria.
  </div>
  <!--<div id="debug_container">#debug<div id='debug'></div></div>-->
</body>


</html>
    
