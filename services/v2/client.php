<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("return_package.php");
require_once("games.php");

class client extends dbconnection
{
      public static function getRecentGamesForPlayer($userId, $includeDev = 1)
      {
         $sTime = microtime(true);
         $query = "SELECT * FROM (SELECT game_id, MAX(created) as ts FROM user_log 
                   WHERE user_id = '".$userId."' AND game_id != 0 
                   GROUP BY game_id ORDER BY ts DESC LIMIT 20) as u_log LEFT JOIN games
                   ON u_log.game_id = games.game_id";
         $logs = dbconnection::queryArray($query);
         $games = array();
         if(!$logs) return new return_package(0, $games); //no recent games were found
         for($i = 0; $i < count($logs); $i++)
         {
             $sTime = microtime(true);
             $gameObj = $logs[$i];
             $game = games::gameObjectFromSQL($gameObj);
             if($game->ready_for_public || $includeDev) $games[] = $game;
         }
         //var_dump($games);
         return new return_package(0, $games);
      }

      public static function getSearchGamesForPlayer($userId, $textToFind, $includeDev = 1)
      {
         $textToFind = addSlashes($textToFind);
         $textToFind = urldecode($textToFind);
         if($includeDev) $query = "SELECT * FROM games WHERE (name LIKE '%".$textToFind."%' OR description LIKE '%".$textToFind."%') ORDER BY name ASC LIMIT ".($page*25).",25";
         else $query = "SELECT * FROM games WHERE (name LIKE '%".$textToFind."%' OR description LIKE '%".$textToFind."%') AND ready_for_public = TRUE ORDER BY name ASC LIMIT ".($page*25).",25";
         $sql_games = dbconnection::queryArray($query);
         $games = array();
         if(!$sql_games) return new return_package(0, $games); //no games were found
         for($i = 0; $i < count($sql_games); $i++)
             if($ob = games::gameObjectFromSQL($sql_games[$i])) $games[] = $ob;

         //var_dump($games);
         return new return_package(0, $games);
      } 

      public static function getPopularGamesForPlayer($user_id, $time, $includeDev = 1)
      {
         if($time == 0) $queryInterval = '1 DAY';
         else if ($time == 1) $queryInterval = '7 DAY';
         else if ($time == 2) $queryInterval = '1 MONTH';

         if ($includeDev) $query = "SELECT *, COUNT(DISTINCT user_id) as count FROM games INNER JOIN user_log ON games.game_id = user_log.game_id WHERE user_log.created BETWEEN DATE_SUB(NOW(), INTERVAL ".$queryInterval.") AND NOW() GROUP BY games.game_id HAVING count > 1 ORDER BY count DESC LIMIT 20;";
         else $query = "SELECT *, COUNT(DISTINCT user_id) as count FROM games INNER JOIN user_log ON games.game_id = user_log.game_id WHERE ready_for_public = TRUE AND user_log.created BETWEEN DATE_SUB(NOW(), INTERVAL ".$queryInterval.") AND NOW() GROUP BY games.game_id HAVING count > 1 ORDER BY count DESC LIMIT 20;";


         $sql_games = dbconnection::queryArray($query);
         $games = array();
         if(!$sql_games) return new return_package(0, $games); //no games were found
         for($i = 0; $i < count($sql_games); $i++)
             if($ob = games::gameObjectFromSQL($sql_games[$i])) $games[] = $ob;

         //var_dump($games);
         return new return_package(0, $games);
      }

      public static function getNearbyGamesForPlayer($user_id, $latitude, $longitude, $includeDev = 1)
      {
         if($includeDev) $query = "SELECT * FROM games WHERE games.latitude BETWEEN {$latitude}-.5 AND {$latitude}+.5 AND games.longitude BETWEEN {$longitude}-.5 AND {$longitude}+.5 GROUP BY games.game_id LIMIT 50";
         else $query = "SELECT * FROM games WHERE games.latitude BETWEEN {$latitude}-.5 AND {$latitude}+.5 AND games.longitude BETWEEN {$longitude}-.5 AND {$longitude}+.5 AND games.ready_for_public = TRUE GROUP BY games.games_id LIMIT 50";
        
         $sql_games = dbconnection::queryArray($query);
         $games = array();
         if(!$sql_games) return new return_package(0, $games); //no games were found
         for($i = 0; $i < count($sql_games); $i++)
             if($ob = games::gameObjectFromSQL($sql_games[$i])) $games[] = $ob;

         var_dump($games);
         return new return_package(0, $games);
      }

      /*
      public static function getAnywhereGamesForPlayer($user_id, $includeDev = 1)
      {
         if($includeDev) $query = "SELECT * FROM games WHERE games.full_quick_travel = 1";
         else $query = "SELECT * FROM games WHERE games.full_quick_travel = 1 AND games.ready_for_public = TRUE";

         $sql_games = dbconnection::queryArray($query);
         $games = array();
         if(!$sql_games) return new return_package(0, $games); //no games were found
         for($i = 0; $i < count($sql_games); $i++)
             if($ob = games::gameObjectFromSQL($sql_games[$i])) $games[] = $ob;

         //var_dump($games);
         return new return_package(0, $games);
      }
      */

}
      
?>
