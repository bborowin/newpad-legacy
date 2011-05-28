<?php

class Sql
{
  private $hostname = "localhost";
  private $database = null;
  private $username = "mapster";
  private $password = "tra18wa";

  function __construct($db)
  {
    $this->database = $db;
  }

  function ExecQuery($query)
  {
    try
    {
      $db = new PDO("mysql:host=$this->hostname;dbname=$this->database", $this->username, $this->password);
      $result = $db->query($query);
      //$db = null;
      return $result;
    }
    catch(PDOException $e)
    {
      //echo 'Exception : '.$e->getMessage();
    }
  }

  function GetTable($query)
  {
    $result = null;
    try
    {
      $host = $this->hostname;
      $dbName = $this->database;
      
      $db = new PDO("mysql:host=$host;dbname=$dbName", $this->username, $this->password);
      $result = $db->query($query);
      //$db = null;
    }
    catch(PDOException $e)
    {
      //echo 'Exception : '.$e->getMessage();
    }
    return $result;
  }

  function GetList($query)
  {
    $list = array();
    try
    {
      $host = $this->hostname;
      $dbName = $this->database;
      
      $db = new PDO("mysql:host=$host;dbname=$dbName", $this->username, $this->password);
      $result = $db->query($query);
      
      foreach($result as $row)
      {
        $list[] = $row[0];
      }
    }
    catch(PDOException $e)
    {
      //echo 'Exception : '.$e->getMessage();
    }
    
    return $list;
  }

  function GetScalar($query)
  {
    $val = null;
    try
    {
      $db = new PDO("mysql:host=$this->hostname;dbname=$this->database", $this->username, $this->password);

      $result = $db->query($query);
      if($result != null) while ($row = $result->fetch ())
      {
        if(sizeof($row) > 0)
        {
          $val = $row[0];
        }
        break;
      }
      $db = null;
    }
    catch(PDOException $e)
    {
      //echo 'Exception : '.$e->getMessage();
    }

    return $val;
  }
}
?>
