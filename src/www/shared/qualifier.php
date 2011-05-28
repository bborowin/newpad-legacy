<?php
//handles query string parameters and their persistence in session
class Qualifier
{
  //store all query string variables in session
  function __construct()
  {
    session_start();
    
    foreach($_GET as $key => $value) 
    {
      $_SESSION[$key] = $value;
    }
  }

  public function Get($key)
  {
    $value = null;
    
    if (isset($_POST[$key]))
    { 
      $value = $_POST[$key];
    }
    else if (isset($_GET[$key]))
    {
      $value = $_GET[$key];
    }
    else if (null == $value && isset($_SESSION[$key])) 
    {
      $value = $_SESSION[$key];
    }
    
    if(null != $value)
    {
      $_SESSION[$key] = $value;
    }
    else
    {
      unset($_SESSION[$key]);
    }
    
    return $value;
  }

  public function GetDf($key, $default)
  {
    $val = $this->Get($key);
    if(null == $val) $val = $default;
    return $val;
  }

  public function Set($key, $value)
  {
    $_SESSION[$key] = $value;
  }
}

?>
