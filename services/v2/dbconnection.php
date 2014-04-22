<?php
require_once('../../config.class.php');
Class dbconnection
{
  public $con;
  
  function __construct()
  {
    $this->con = mysqli_connect(Config::v2_host, Config::v2_db_user, Config::v2_db_pass, Config::v2_db);
  }

  function query($query, $debug = false)
  {
    if($debug) echo $query;
    if(!mysqli_query($this->con, $query))
    {
        return false;
    }
    return $this->con->insert_id;
  }

  function queryInsert($query, $debug = false)
  {
    if($debug) echo $query;
    if(!mysqli_query($this->con, $query))
    {
        return false;
    }
    return mysqli_insert_id($this->con);
  }

  function queryObject($query, $debug = false)
  {
    if($debug) echo $query;
    if(!$sql_data = mysqli_query($this->con, $query))
    {
        return false;
    }
    return mysqli_fetch_object($sql_data);
  }

  function queryArray($query, $debug = false)
  {
    if($debug) echo $query;
    if(!$sql_data = mysqli_query($this->con, $query))
    {
        return false;
    }
    $ret = array();
    while($o = mysqli_fetch_object($sql_data))
        $ret[] = $o;
    return $ret;
  }

  function __destruct()
  {
    mysqli_close($this->con);
  }
}
?>
