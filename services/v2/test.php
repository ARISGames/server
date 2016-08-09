<?php

require_once("dbconnection.php");

class test extends dbconnection
{
    public static function doTest($pack)
    {
      //get quick estimate of # games w/o NOTEBOOK tab (last check = 630/8,000)
      //SELECT games.game_id, ntabs.type FROM games LEFT JOIN (SELECT tabs.game_id, tabs.type FROM tabs WHERE type = 'NOTEBOOK') as ntabs ON games.game_id = ntabs.game_id WHERE ntabs.type IS NULL group by games.game_id;

      dbconnection::query("UPDATE games SET network_level = 'REMOTE' WHERE network_level = 'HYBRID';");
      $games = dbconnection::queryArray("SELECT * FROM games WHERE network_level != 'REMOTE';");

      $n_remote = 0;
      $n_offline = 0;
      $n_notebookd = 0;
      $n_factoried = 0;
      $n_instanced = 0;

      for($i = $pack->from; $i < count($games) && $i < $pack->to; $i++)
      {
        $ought_be_remote = false;
        $game = $games[$i];

        //figure out if ought be remote
        $should_be_empty = dbconnection::queryArray("SELECT * FROM tabs WHERE game_id = '".$game->game_id."' && type = 'NOTEBOOK';");
        if(count($should_be_empty)>0) { $ought_be_remote = true; $n_notebookd++; }
        $should_be_empty = dbconnection::queryArray("SELECT * FROM factories WHERE game_id = '".$game->game_id."';");
        if(count($should_be_empty)>0) { $ought_be_remote = true; $n_factoried++; }
        $should_be_empty = dbconnection::queryArray("SELECT * FROM instances WHERE game_id = '".$game->game_id."' && (owner_type = 'GAME' || owner_type = 'GROUP') && qty > 0;");
        if(count($should_be_empty)>0) { $ought_be_remote = true; $n_instanced++; }

        if($ought_be_remote) dbconnection::query("UPDATE games SET network_level = 'REMOTE'       WHERE game_id = '".$game->game_id."';");
        else                 dbconnection::query("UPDATE games SET network_level = 'REMOTE_WRITE' WHERE game_id = '".$game->game_id."';");
        if($ought_be_remote) $n_remote++;
        else $n_offline++;
      }

      echo(count($games)."\n");
      echo($n_remote."\n");
      echo($n_offline."\n");
      echo($n_notebookd."\n");
      echo($n_factoried."\n");
      echo($n_instanced."\n");
    }
}
?>
