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
$showAd = false;

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

//format and show title
echo ucfirst($city);
if (1==$mode) 
  echo ' Apartments';
else
  echo ' Roommates';

?>
</title>

<link href='http://fonts.googleapis.com/css?family=Ubuntu&subset=cyrillic,latin' rel='stylesheet' type='text/css' />
<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
<?
$map_css = 'map' . (($showAd) ? '.css' : '_noads.css');
echo '<link href="' . $map_css . '?time=' . filemtime($map_css) . '" rel="stylesheet" type="text/css" />';
?>


<script type="text/javascript" src="http://maps.google.com/maps/api/js?v=3.2&sensor=false"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>

<script type="text/javascript" src="util.js?time=<?php echo(filemtime("util.js")); ?>"></script>
<script type="text/javascript" src="markers.js?time=<?php echo(filemtime("markers.js")); ?>"></script>
<script type="text/javascript" src="icon.js?time=<?php echo(filemtime("icon.js")); ?>"></script>
<script type="text/javascript" src="init.js?time=<?php echo(filemtime("init.js")); ?>"></script>
<script type="text/javascript" src="price_slider.js?time=<?php echo(filemtime("price_slider.js")); ?>"></script>
</head>

<script type="text/javascript">
  <?
    echo "$.min_price = $btm_cutoff;";
    echo "$.max_price = $top_cutoff;";
  ?>
</script>
<?php

function IntroPopup($city, $mode)
{
  $modeStr = (1 == $mode) ? "apartments" : "rooms";
  $val = "<div id='introDiv'>Click anywhere on the map to search for $modeStr in that area.</br></br> ";
  $val .= "Adjust your price range using the slider on the right.</br></br></br> ";
  $val .= "<span id=\"dismissDiv\" onclick=\"$(this).parent().hide();\" >Hide this message</span>";
  $val .= '<span id="disableIntroDiv" onclick="$.get(\'action.php\', { city:\'' . $city . '\', action:\'disableIntroDiv\'}); $(this).parent().hide();">Don\'t show again</span></div>';
  
  return $val;
}

function AdSense()
{
  echo '<div id="ads" class="adsense"> ';
  echo '<script type="text/javascript"> ';
  echo 'google_ad_client = "ca-pub-7521172234820738"; ';
  echo 'google_ad_slot = "7235504003"; ';
  echo 'google_ad_width = 160; ';
  echo 'google_ad_height = 600; ';
  echo '</script> ';
  echo '<script type="text/javascript" ';
  echo 'src="http://pagead2.googlesyndication.com/pagead/show_ads.js"> ';
  echo '</script> ';
  echo '</div> ';
}



if(!$u->Get($userId, "hideIntroDiv")) echo IntroPopup($city, $mode);

?>
<body onload="initialize(<? echo '\'' . $city . '\', ' . $mode . ', ' . $lat . ', ' . $lon . ', ' . $zoom . ', ' . $min . ', ' . $max . ', ' . $maxHits . ', ' . $run . ');';?>">

  <div id="map_canvas"></div>
  <? if($showAd) echo AdSense(); ?>
  
  <div id="prices"><div id="amount"></div></div>
  <div style="background-image: url('img/price_gradient.png');" id="slider_container">
    <div id="slider" style="background-image: url('img/price_gradient.png'); margin:0em; height:100%;"></div>
  </div>
  <div title="Each dot marks one place. The size of the dot corresponds to the posting age, and the brightness of the colour corresponds to price. &#13;You can adjust the price range with the slider on the right side of the map. If you hold the mouse cursor over a dot, it will show a quick summary of the posting. Clicking the dot will let you see the original ad, as well as give you the option to mark it as a favourite." id="legendDiv"><img src="img/legend.png"></img></div>
  <div id='cpDiv'>Copyright 2010 Newpad.ca</div>
  <!--<div id="debug_container">#debug
  <div id='debug'></div></div>-->

  </div></div>
  <div id="location_container">looking for
  <select id="floor">
    <option value="ignore"> </option>
    <option value="basement">basement</option>
    <option value="first floor">first floor</option>
    <option value="second floor">second floor</option>
    <option value="third floor">third floor</option>
    <option value="top floor">top floor</option>
  </select>  
  <select id="size">
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
  <select id="mode_dpdn" onchange="window.location.href = 'map.php?city=' + $('#location_dpdn').val() + '&mode=' + value">
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
  <select id="location_dpdn" onchange="window.location.href = 'map.php?city=' + value + '&mode=' + $('#mode_dpdn').val()">
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
  <?
    if(!$u->Get($userId, "hideIntroDiv")) echo IntroPopup($city, $mode);
  ?>
</body>


</html>
    
