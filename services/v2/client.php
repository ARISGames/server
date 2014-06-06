<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("games.php");
require_once("instances.php");
require_once("triggers.php");
require_once("quests.php");
require_once("tabs.php");
require_once("requirements.php");
require_once("return_package.php");

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


    public static function getInstancesForPlayer($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::getInstancesForPlayerPack($glob); }
    public static function getInstancesForPlayerPack($pack)
    {
        return instances::getInstancesForGamePack($pack);
    }

    public static function getTriggersForPlayer($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::getTriggersForPlayerPack($glob); }
    public static function getTriggersForPlayerPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $gameTriggers = triggers::getTriggersForGamePack($pack)->data;
        $playerTriggers = array();
        for($i = 0; $i < count($gameTriggers); $i++)
        {
            if(requirements::evaluateRequirementPackagePack($gameTriggers[$i]))
                $playerTriggers[] = $gameTriggers[$i];
        }
        return new return_package(0, $playerTriggers);
    }

    public static function getQuestsForPlayer($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::getQuestsForPlayerPack($glob); }
    public static function getQuestsForPlayerPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $gameQuests = quests::getQuestsForGamePack($pack)->data;
        $playerQuests = new stdClass();
        $playerQuests->active   = array();
        $playerQuests->complete = array();
        for($i = 0; $i < count($gameQuests); $i++)
        {
            $gameQuests[$i]->requirement_root_package_id = $gameQuests[$i]->active_requirement_package_id;
            if(!requirements::evaluateRequirementPackagePack($gameQuests[$i])) continue; //ensure quest is active/visible

            $gameQuests[$i]->requirement_root_package_id = $gameQuests[$i]->complete_requirement_package_id;
            if(requirements::evaluateRequirementPackagePack($gameQuests[$i]))
                $playerQuests->complete[] = $gameQuests[$i];
            else
                $playerQuests->active[] = $gameQuests[$i];

            unset($gameQuests[$i]->requirement_root_package_id); //get rid of redundant attrib
        }
        return new return_package(0, $playerQuests);
    }

    public static function getTabsForPlayer($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::getTabsForPlayerPack($glob); }
    public static function getTabsForPlayerPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $gameTabs = tabs::getTabsForGamePack($pack)->data;
        $playerTabs = array();
        for($i = 0; $i < count($gameTabs); $i++)
        {
            if(requirements::evaluateRequirementPackagePack($gameTabs[$i])) 
                $playerTabs[] = $gameTabs[$i];
        }
        return new return_package(0, $playerTabs);
    }

}

?>
