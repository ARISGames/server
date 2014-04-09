<?php
require_once('mysql_conf.php');
Class dbconnection
{
  public $con;
  
  function __construct()
  {
    $this->con = new mysqli(MysqlConf::host, MysqlConf::db_user, MysqlConf::db_pass, MysqlConf::db);
  }

  function query($query, $debug = false)
  {
    if($debug) echo $query;
    $this->con->query($query);
    return $this->con->insert_id;
  }

  function queryObj($query, $debug = false)
  {
    if($debug) echo $query;
    $result = $this->con->query($query);
    return $result->fetch_object();
  }

  function queryArray($query, $debug = false)
  {
    if($debug) echo $query;
    $result = $this->con->query($query);
    $ret = array();
    while($o = $result->fetch_object())
      $ret[] = $o;
    return $ret;
  }

  function __destruct()
  {
    $this->con->close();
  }
}
?>
