<?php

require_once("dbconnection.php");

class test extends dbconnection
{
    public static function doTest($pack)
    {
      //dbconnection::query("UPDATE games SET network_level = 'REMOTE' WHERE network_level == 'HYBRID';");
      $games = dbconnection::queryArray("SELECT * FROM games WHERE network_level != 'REMOTE';");

      for($i = 0; $i < count($games) && $i < 10; $i++)
      {
        $ought_be_remote = false;
        $game = $games[$i];

        //figure out if ought be remote
        $should_be_empty = dbconnection::queryArray("SELECT * FROM tabs WHERE game_id = '".$game->game_id."' && type == 'NOTEBOOK';");
        if(count($should_be_empty)) $ought_be_remote = true;
        $should_be_empty = dbconnection::queryArray("SELECT * FROM factories WHERE game_id = '".$game->game_id."';");
        if(count($should_be_empty)) $ought_be_remote = true;
        $should_be_empty = dbconnection::queryArray("SELECT * FROM instances WHERE game_id = '".$game->game_id."' && (owner_type == 'GAME' || owner_type == 'GROUP');");
        if(count($should_be_empty)) $ought_be_remote = true;

        //if($ought_be_remote) dbconnection::query("UPDATE games SET network_level = 'REMOTE'       WHERE game_id = '".$game->game_id."';");
        //else                 dbconnection::query("UPDATE games SET network_level = 'REMOTE_WRITE' WHERE game_id = '".$game->game_id."';");
        echo($game->game_id." ".$ought_be_remote);
      }
    }
}
?>
