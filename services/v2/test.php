<?php

require_once("dbconnection.php");

class test extends dbconnection
{
    public static function doTest($pack)
    {
      $inst_id = dbconnection::queryInsert("INSERT INTO instances (game_id,object_type,object_id,qty,infinite_qty,factory_id,owner_type,owner_id,created) VALUES (7045,'EVENT_PACKAGE','{$pack->event_pack_id}',0,0,0,'GAME_CONTENT',0,CURRENT_TIMESTAMP);");
      $trig_id = dbconnection::queryInsert("INSERT INTO triggers (game_id,instance_id,scene_id,requirement_root_package_id,type,name,title,icon_media_id,latitude,longitude,distance,infinite_distance,wiggle,show_title,hidden,trigger_on_enter,qr_code,created) VALUES (7054, '{$inst_id}', 15292,0,'LOCATION','EVENT TRIGGER','',0,43.070,-89.4015,0,1,0,1,0,0,'',CURRENT_TIMESTAMP);");

    /*
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
      */
    }
}
?>
