<?php
require_once('../../config.class.php');
require_once('util.php');
Class dbconnection
{
  private static $con;
  private static function connect()    { dbconnection::$con = mysqli_connect(Config::v2_host, Config::v2_db_user, Config::v2_db_pass, Config::v2_db); mysqli_set_charset(dbconnection::$con,'utf8'); }
  private static function disconnect() { mysqli_close(dbconnection::$con); }

  function __construct()
  {
    dbconnection::connect();
  }

  protected static function query($query, $debug = false)
  {
    if($debug) echo $query;
    if(!(mysqli_query(dbconnection::$con, $query)))
    {
      util::errorLog(mysqli_error(dbconnection::$con));
      return false;
    }
    return dbconnection::$con->insert_id;
  }

  protected static function queryInsert($query, $debug = false)
  {
    if($debug) echo $query;
    if(!(mysqli_query(dbconnection::$con, $query)))
    {
      util::errorLog(mysqli_error(dbconnection::$con));
      return false;
    }
    return mysqli_insert_id(dbconnection::$con);
  }

  protected static function queryObject($query, $debug = false)
  {
    if($debug) echo $query;
    if(!($sql_data = mysqli_query(dbconnection::$con, $query)))
    {
      util::errorLog(mysqli_error(dbconnection::$con));
      return false;
    }
    return mysqli_fetch_object($sql_data);
  }

  protected static function queryObjectAssoc($query, $debug = false)
  {
    if($debug) echo $query;
    if(!($sql_data = mysqli_query(dbconnection::$con, $query)))
    {
      util::errorLog(mysqli_error(dbconnection::$con));
      return false;
    }
    return mysqli_fetch_array($sql_data,MYSQLI_ASSOC);
  }

  protected static function queryArray($query, $debug = false)
  {
    if($debug) echo $query;
    if(!($sql_data = mysqli_query(dbconnection::$con, $query)))
    {
      util::errorLog(mysqli_error(dbconnection::$con));
      return false;
    }
    $ret = array();
    while($o = mysqli_fetch_object($sql_data))
      $ret[] = $o;
    return $ret;
  }

  protected static function queryArrayAssoc($query, $debug = false) //confusing name- returns "array of assoc objects"
  {
    if($debug) echo $query;
    if(!($sql_data = mysqli_query(dbconnection::$con, $query)))
    {
      util::errorLog(mysqli_error(dbconnection::$con));
      return false;
    }
    $ret = array();
    while($o = mysqli_fetch_array($sql_data,MYSQLI_ASSOC))
      $ret[] = $o;
    return $ret;
  }

  function __destruct()
  {
    dbconnection::disconnect();
  }
}
?>
