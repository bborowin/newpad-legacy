<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
  <link href='http://fonts.googleapis.com/css?family=Ubuntu&subset=cyrillic,latin' rel='stylesheet' type='text/css' />
  <title>Apartments for rent, roommates to share</title>
  <meta http-equiv="content-type" content="text/html;charset=utf-8" />
  <style type="text/css" >
    body {
      overflow-x:hidden;
      font-family : 'Ubuntu', sans-serif;
      margin:0px;
      padding:0px;
    }

    #banner {
      margin-top:1em;
      width: 100%;
      border-bottom:4px solid #5471D9;
      border-top:4px solid #5471D9;
      background-color:#A8B4E4;
      font-size:200%;
      padding:0.35em;
      padding-left:0.75em;
      
    }
    .section {
      font-size:125%;
      padding-top:1em;
      margin-left:-0.25em;
    }
    #container {
      padding:1.5em;
      padding-top:0.5em;
    }
    #left {
      float: left;
      width: 46.5%;
      padding-right:2em;
      padding-bottom:3em;
      border-right:4px dotted #5471D9;
    }
    #right {
      width: 46.5%;
      float: right;
    }
    .clear {
      height: 0;
      font-size: 1px;
      margin: 0;
      padding: 0;
      line-height: 0;
      clear: both;
    }
  </style>


  <script language="javascript">
  <!--
  function MFmLmAYnralxkrt()
  {
  var zYzRZGBkfUvcKzI=["x73","117","x70","112","x6f","x72","116","x40","110","x65","x77","112","x61","100","x2e","x63","97"];
  var sLiZXdgLMXkrDeW=[""];
  var dOaUghDythjyukN=["x73","x75","x70","x70","111","x72","x74","64","110","101","119","112","x61","100","46","x63","97"];
  for (i=0; i<dOaUghDythjyukN.length; i++) document.write('&#'+dOaUghDythjyukN[i]+';');
  }
  //-->
  </script>

</head>

<body>
<?php
include 'shared/sql.php';

function ShowCities()
{
  $s = new Sql('main');
  $query = "SELECT name FROM Cities ORDER BY name;";
  $result = $s->GetTable($query);

  if($result != null)
  echo '<div style="padding-bottom:0.5em;">';
  while ($row = $result->fetch ())
  {
    echo '<p>' . $row[name] . ': <a href="shared/search.php?city=' . $row[name] . '&mode=' . 1 . '">apartments</a> ';
    echo '<a href="shared/search.php?city=' . $row[name] . '&mode=' . 0 . '">rooms</a></p>';
  }
  echo '</div>';
}

?>

<div id="banner">
Welcome to Newpad!
<span style="float:right; padding-right:6em; padding-top:1.75em; font-size:25%;">version 0.2</span>
</div>
<div id="container">
<div id="left">
<p class="section">
What is it?
</p>
<p>
We make it easier to find an apartment or a room for rent. You can search housing classifieds from Craigslist and Kijiji using a map.
Look for places in a specific area, see at a glance how many are available in your price range. <b>Updated every 15 minutes.</b>
</p>
<p class="section">
How to use it?
</p>
<p>
Follow the links on the right for rooms/apartments in your city.
Click anywhere on the map to search for places in that area.
Change the price range with the slider on the right.
If you zoom in, searches will show more/older postings in that area (up to 50 at a time).
Zooming out will let you see the newest postings over a large area.
</p>
<p class="section">
About:
</p>
<p>
We are a Montreal startup, crafting technologies that simplify the mundane.
We're working on some incredible features that will give our users an unfair advantage in the competitive world of apartment hunting.
Early-adopter invites available soon!
</p>
<p>
Our privacy policy is located <a href="privacy.html">here</a>. You can get in touch with us at 
<script language="javascript">
<!--
MFmLmAYnralxkrt();
//-->
</script>
or via our <a href="http://www.facebook.com/#!/pages/newpadca/180983548600803">Facebook group</a>.
</p>

<div style="float:right;">
<a name="fb_share" type="box_count" share_url="http://newpad.ca"></a><script src="http://static.ak.fbcdn.net/connect.php/js/FB.Share" type="text/javascript"></script>
&nbsp;
<a href="http://twitter.com/share" class="twitter-share-button" data-url="http://newpad.ca" data-text="Amazing new search engine for apartments! Check it out!" data-count="vertical">Tweet</a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>
</div>

</div>

<div id="right">
<p class="section">
Search these cities:
</p>
<?
ShowCities();
?>
</div>
<div class="clear"></div>
</div>

</body>
</html>

