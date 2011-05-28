<?php
session_start();

if (count($_GET) > 0)
{
  foreach ($_GET as $key => $value)
  echo "$key = $value</br>";
}

echo $_SESSION['bazimba'];

?>
