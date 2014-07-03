<?php
require_once('../../config.class.php');
require_once('util.php');
Class dbconnection
{
  private static $con;
  private static function connect()    { dbconnection::$con = mysqli_connect(Config::v2_host, Config::v2_db_user, Config::v2_db_pass, Config::v2_db); }
  private static function disconnect() { mysqli_close(dbconnection::$con); }

  function __construct()
  {
    dbconnection::connect();
  }

  protected static function query($query, $debug = false)
  {
    if($debug) util::serverErrorLog($query);
    if(!(mysqli_query(dbconnection::$con, $query)))
    {
        return false;
    }
    return dbconnection::$con->insert_id;
  }

  protected static function queryInsert($query, $debug = false)
  {
    if($debug) echo $query;
    if(!(mysqli_query(dbconnection::$con, $query)))
    {
        return false;
    }
    return mysqli_insert_id(dbconnection::$con);
  }

  protected static function queryObject($query, $debug = false)
  {
    if($debug) echo $query;
    if(!($sql_data = mysqli_query(dbconnection::$con, $query)))
    {
        return false;
    }
    return mysqli_fetch_object($sql_data);
  }

  protected static function queryArray($query, $debug = false)
  {
    if($debug) echo $query;
    if(!($sql_data = mysqli_query(dbconnection::$con, $query)))
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
    dbconnection::disconnect();
  }
}
?>
