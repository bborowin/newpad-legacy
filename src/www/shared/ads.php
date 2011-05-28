<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
  <title>moving appliances real estate</title>
  <link href='http://fonts.googleapis.com/css?family=Ubuntu&subset=cyrillic,latin' rel='stylesheet' type='text/css' />
  <meta http-equiv="content-type" content="text/html;charset=utf-8" />
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
</head>

<body style="padding:0px; margin:0px;">
<div style="padding:1em; font-family : 'Ubuntu', sans-serif;" ><a href="../privacy.html">privacy policy</a></div>
<?
include 'qualifier.php';
include 'users.php';
$q = new Qualifier(false);
$city = $q->Get("city");
$s = new Sql($city);
$u = new Users($s);
if('hide' != $u->GetUserSetting($u->GetUserId(), 'ads'))
{
  echo '<div class="adsense" style="width:160px; height:600px;"><script type="text/javascript"><!--
google_ad_client = "ca-pub-7521172234820738";
/* left column ad */
google_ad_slot = "7235504003";
google_ad_width = 160;
google_ad_height = 600;
//-->
</script>
<script type="text/javascript"
src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script></div>';
}
?>
</body>
</html>

