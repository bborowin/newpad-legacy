<?php
include 'sql.php';

class Users
{
  private $s;
  function __construct()
  {
    $this->s = new Sql('main');
  }
  
  private function GetId($user)
  {
    //echo 'seeking userId in DB ';
    $query = "SELECT id FROM Users WHERE username LIKE '$user'";
    $id = $this->s->GetScalar($query);
    return $id;
  }

  function GetUserId()
  {
    $user = null;
    if (isset($_COOKIE["user"]))
    {
      $user = $_COOKIE["user"];
      //echo 'cookie found for ' . $user . ' ';
    }
    else
    {
      $user = uniqid (rand(), true);
      setcookie("user", $user, time()+31536000);
      //echo 'new cookie set for ' . $user . ' ';
    }

    $id = -1;
    try
    {
      $id = $this->GetId($user);
      if(-1 == $id || null == $id || '' == $id)
      {
        //echo 'putting userId in DB ';
        $query = "INSERT INTO Users VALUES (null, '" . $user . "', NOW());";
        //echo $query;
        $this->s->ExecQuery($query);
        $id = $this->GetId($user);
      }
      //echo 'userId retrieved: ' . $id . ' ';
    }
    catch(PDOException $e)
    {
      //echo 'Exception : '.$e->getMessage();
    }
    
    return $id;
  }

  //insert new setting name-value pair for userId, or update existing if userId-name key already exists
  function Set($id, $name, $value)
  {
    $query = "INSERT INTO UserSettings VALUES ($id, '$name', '$value') ON DUPLICATE KEY UPDATE value = '$value';";
    $this->s->ExecQuery($query);
  }

  //retrieve setting value for specified user / setting name
  function Get($id, $name)
  {
    return $this->s->GetScalar("SELECT value FROM UserSettings WHERE userId = $id AND name = '$name';");
  }

  //retrieve setting value for specified user / setting name
  function GetDf($id, $name, $default)
  {
    $val = $this->s->GetScalar("SELECT value FROM UserSettings WHERE userId = $id AND name = '$name';");
    if(null == $val) $val = $default;
    return $val;
  }
}

?>
