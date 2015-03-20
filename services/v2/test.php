<?php

require_once("dbconnection.php");

class test extends dbconnection
{
    public static function doTest($pack)
    {
      echo dbconnection::queryObject("SELECT NOW() as now FROM games LIMIT 1")->now;
      echo "\n";
      echo date("Y-m-d H:i:s");
      echo "\n";
    }
}
?>
