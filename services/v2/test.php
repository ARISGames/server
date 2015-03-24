<?php

require_once("dbconnection.php");

class test extends dbconnection
{
    public static function doTest($pack)
    {
      $games = dbconnection::queryArray("SELECT * FROM games;");
      for($i = 0; $i < count($games); $i++)
      {
        $lat = 0;
        $lon = 0;
        $triggers = dbconnection::queryArray("SELECT * FROM triggers WHERE game_id = '{$games[$i]->game_id}';");
        $n = count($triggers);
        for($j = 0; $j < $n; $j++)
        {
          $lat += $triggers[$j]->latitude/$n;
          $lon += $triggers[$j]->longitude/$n;
        }
        dbconnection::query("UPDATE games SET latitude = '{$lat}', longitude = '{$lon}' WHERE game_id = '{$games[$i]->game_id}';");
      }
      return 0;
    }
}
?>
