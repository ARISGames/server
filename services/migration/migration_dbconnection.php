<?php
require_once('../../config.class.php');
Class migration_dbconnection
{
  private static $mig_con;
  private static $v1_con;
  private static $v2_con;
  private static function connect()
  {
      migration_dbconnection::$mig_con = mysqli_connect(Config::mig_host, Config::mig_db_user, Config::mig_db_pass, Config::mig_db);
      migration_dbconnection::$v1_con = mysqli_connect(Config::v1_host, Config::v1_db_user, Config::v1_db_pass, Config::v1_db);
      migration_dbconnection::$v2_con = mysqli_connect(Config::v2_host, Config::v2_db_user, Config::v2_db_pass, Config::v2_db);
      //this is so dumb...
      mysqli_set_charset(migration_dbconnection::$mig_con,'utf8');
      mysqli_set_charset(migration_dbconnection::$v1_con,'utf8');
      mysqli_set_charset(migration_dbconnection::$v2_con,'utf8');
  }
  private static function disconnect()
  {
    //mysqli_close(migration_dbconnection::$mig_con);
    //mysqli_close(migration_dbconnection::$v1_con);
    //mysqli_close(migration_dbconnection::$v2_con);
  }
  private static function conForString($db)
  {
    if($db == "mig") return migration_dbconnection::$mig_con;
    if($db == "v1") return migration_dbconnection::$v1_con;
    if($db == "v2") return migration_dbconnection::$v2_con;
    return null;
  }

  function __construct() { migration_dbconnection::connect();    }
  function __destruct()  { migration_dbconnection::disconnect(); }

  protected static function query($query, $db = "mig", $debug = false)
  {
    if($debug) echo $query;
    $con = migration_dbconnection::conForString($db);
    if(!(mysqli_query($con, $query)))
    {
        return false;
    }
    return $con->insert_id;
  }

  protected static function queryInsert($query, $db = "mig", $debug = false)
  {
    if($debug) echo $query;
    $con = migration_dbconnection::conForString($db);
    if(!(mysqli_query($con, $query)))
    {
        return false;
    }
    return mysqli_insert_id($con);
  }

  protected static function queryObject($query, $db = "mig", $debug = false)
  {
    if($debug) echo $query;
    $con = migration_dbconnection::conForString($db);
    if(!($sql_data = mysqli_query($con, $query)))
    {
        return false;
    }
    return mysqli_fetch_object($sql_data);
  }

  protected static function queryArray($query, $db = "mig", $debug = false)
  {
    if($debug) echo $query;
    $con = migration_dbconnection::conForString($db);
    if(!($sql_data = mysqli_query($con, $query)))
    {
        return false;
    }
    $ret = array();
    while($o = mysqli_fetch_object($sql_data))
        $ret[] = $o;
    return $ret;
  }
}
?>
