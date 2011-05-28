<!-- 484f2387-6b24-4d08-b28b-79145f8ce44d -->
<?php
session_start();

if (count($_GET) > 0)
{
  foreach ($_GET as $key => $value)
  {
    $_SESSION[$key]=$_GET[$key];
  }
}
?>
