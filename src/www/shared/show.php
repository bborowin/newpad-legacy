
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
    <link href="show.css?time=<?php echo(filemtime("show.css")); ?>" rel="stylesheet" type="text/css"/>
    <title>Posting detail</title>

    <?php 
      include 'qualifier.php';
      include 'users.php';

      $q = new Qualifier(true);

      $city = $q->Get("city");
      $mode = $q->Get("mode");
      $flag = $q->Get("flag");
      $target = $q->Get("target");
      $id = $q->Get("postingId");
      $s = new Sql($city);
      $s_main = new Sql('main');
      $u = new Users();
      $userId = $u->GetUserId();
      
      $seen = $s->GetScalar("SELECT count(*) FROM Flags WHERE postingId = $id AND userId != $userId;");
      $liked = $s->GetScalar("SELECT count(*) FROM Flags WHERE postingId = $id AND flag = 2;");
      //$liked = $s->GetScalar("SELECT count(*) FROM Flags WHERE postingId = $id AND flag = 2 AND userId != $userId;");
      $disliked = $s->GetScalar("SELECT count(*) FROM Flags WHERE postingId = $id AND flag = 3;");
      //$disliked = $s->GetScalar("SELECT count(*) FROM Flags WHERE postingId = $id AND flag = 3 AND userId != $userId;");
      
      $blurb = 'user rating: ' . $liked - $disliked . '(' . $seen . ')';
        
    ?>

  <script language="javascript">
  <!--
  $(document).ready(function () {
    $('#help').hide();
  });
  
  function Action(city, id, action)
  {
    var t = null;
    if('' != id && null != id && null != action)
    {
      $.get("tag.php", { city:city, target:id, action:action});
      t=setTimeout("close()",250);
    }
    
    if(!t) close();
  }
  //-->
  </script>
  <style>
  </style>
  </head>
<body>

<div id="bar">
  <span style="float:left; padding-top:0.1em;">
    <?
      if(2 == $flag)
      {
        echo '<button onclick="Action(\'' . $city .'\', ' . $id.  ', \'unlike\');" title="Remove this from your list of favourites">Remove from favourites</button>';
      }
      else
      {
        echo '<button onclick="Action(\'' . $city .'\', ' . $id.  ', \'like\');" title="Tag this posting as a favourite (marked green in your search)">Favourite</button>';
      }
    ?>
    <button onclick="Action('<? echo $city; ?>', <? echo $id; ?>, 'dislike');" title="Flag this posting as substandard (you won't see it again)">Blacklist</button>
    <button onclick="Action('<? echo $city; ?>', <? echo $id; ?>, null);" title="Close this window and return to your search">Return to map</button>
    <span style="padding-right:3em;">&nbsp;</span>
    <button onclick="Action('<? echo $city; ?>', <? echo $id; ?>, 'expired');" title="Mark this posting as expired">Expired</button>
    <button onclick="Action('<? echo $city; ?>', <? echo $id; ?>, 'spam');" title="Flag this posting as spam or inappropriate">Spam</button>
    <button onclick="Action('<? echo $city; ?>', <? echo $id; ?>, 'miscat');" title="Flag this posting as miscategorized">Wrong category</button>
    <span style="margin-left:5em; font-size:75%; cursor:help;" onclick="$('#help').toggle();" title="Click here to toggle help window">What do these buttons do?</span>
  </span>
  <span style="float:right; padding-right:0.6em; font-size:125%;"><? echo $blurb; ?></span>
</div>
<div onclick="$('#help').toggle();" id="help">
  <p><i>Favourite</i>: add this posting to your favourites so you can find it easily later.</p>
  <p><i>Blacklist</i>: if the place is really crummy, you can tag it as such, and it will not show up in your search again</p>
  <p><i>Back to map</i>: close the page displaying the ad and return to your search</p>
  <p><i>Expired</i>: sometimes a posting will expire before we catch it - clicking this button will remove it</p>
  <p><i>Spam</i>: if you think this post is spam or inappropriate, flag it as such</p>
  <p><i>Wrong category</i>: flag the ad if you think it's miscategorized (eg, a room rental in the apartments section)</p>
</div>
<iframe src="<? echo $target; ?>" width="100%" height="92%">
  <p>Your browser does not support iframes.</p>
</iframe>
</body>
</html>

