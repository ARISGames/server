<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("games.php");
require_once("instances.php");
require_once("triggers.php");
require_once("quests.php");
require_once("overlays.php");
require_once("tabs.php");
require_once("dialogs.php");
require_once("requirements.php");
require_once("return_package.php");

class client extends dbconnection
{
    //Phil tested on 7/17/14 determined method 1 (JOIN) was consistently ~3x as fast. //NOTE- ABNORMALLY SMALL DATA SET: NEEDS FURTHER TESTING
    public static function getRecentGamesForPlayer($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::getRecentGamesForPlayerPack($glob); }
    public static function getRecentGamesForPlayerPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        //method 1 (JOIN)
        $sTime = microtime(true);
        $sql_games = dbconnection::queryArray("SELECT * FROM (SELECT game_id, MAX(created) as ts FROM user_log WHERE user_id = '{$pack->auth->user_id}' AND game_id != 0 GROUP BY game_id ORDER BY ts DESC LIMIT 20) as u_log LEFT JOIN games ON u_log.game_id = games.game_id ".($pack->includeDev ? "" : "WHERE games.ready_for_public = TRUE"));
        $games = array();
        for($i = 0; $i < count($sql_games); $i++)
            $games[] = games::gameObjectFromSQL($sql_games[$i]);
        $debugString = "JOIN: ".(microtime(true)-$sTime)."\n";

/*
        //method 2 (SELECT)
        $sTime = microtime(true);
        $sql_logs = dbconnection::queryArray("SELECT game_id, MAX(created) FROM user_log WHERE user_id = '{$pack->auth->user_id}' AND game_id != 0 GROUP BY game_id ORDER BY ts DESC LIMIT 20");
        $games = array();
        for($i = 0; $i < count($sql_logs); $i++)
        {
            $game = dbconnection::queryObject("SELECT * FROM games WHERE game_id = '{$sql_logs[$i]->game_id}'");
            if($game && ($game->ready_for_public || $pack->includeDev))
                $games[] = games::gameObjectFromSQL($game);
        }
        $debugString .= "SELECT: ".(microtime(true)-$sTime)."\n";
*/

        return new return_package(0, $games);
    }

    public static function getSearchGamesForPlayer($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::getSearchGamesForPlayerPack($glob); }
    public static function getSearchGamesForPlayerPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $text = urldecode(addSlashes($pack->text));
        if($text == "") return new return_package(0, array()); //technically, returns ALL games. but that's ridiculous, so return none.

        $sql_games = dbconnection::queryArray("SELECT * FROM games WHERE (name LIKE '%{$text}%' OR description LIKE '%{$text}%') ".($pack->includeDev ? "" : "AND ready_for_public = TRUE")." ORDER BY name ASC LIMIT ".($pack->page*25).",25");
        $games = array();
        for($i = 0; $i < count($sql_games); $i++)
            $games[] = games::gameObjectFromSQL($sql_games[$i]);

        return new return_package(0, $games);
    }

    //Phil tested on 7/17/14 determined method 2 (SELECT) was consistently nearly twice as fast. //NOTE- ABNORMALLY SMALL DATA SET: NEEDS FURTHER TESTING
    public static function getPopularGamesForPlayer($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::getPopularGamesForPlayerPack($glob); }
    public static function getPopularGamesForPlayerPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        else if ($pack->interval == "MONTH") $interval = '1 MONTH';
        else if ($pack->interval == "WEEK")  $interval = '7 DAY';
        else                                 $interval = '1 DAY';

        /*
        //method 1 (JOIN)
        $sTime = microtime(true);
        $sql_games = dbconnection::queryArray("SELECT *, COUNT(DISTINCT user_id) as count FROM games INNER JOIN user_log ON games.game_id = user_log.game_id WHERE user_log.created BETWEEN DATE_SUB(NOW(), INTERVAL {$interval}) AND NOW() ".($pack->includeDev ? "" : "AND games.ready_for_public = TRUE")." GROUP BY games.game_id HAVING count > 1 ORDER BY count DESC LIMIT 20");
        $games = array();
        for($i = 0; $i < count($sql_games); $i++)
            $game[] = games::gameObjectFromSQL($sql_games[$i]);
        $debugString = "JOIN: ".(microtime(true)-$sTime)."\n";
        */

        //method 2 (SELECT)
        $sTime = microtime(true);
        $sql_logs = dbconnection::queryArray("SELECT game_id, COUNT(DISTINCT user_id) as count FROM user_log WHERE created BETWEEN DATE_SUB(NOW(), INTERVAL {$interval}) AND NOW() GROUP BY game_id HAVING count > 0 ORDER BY count DESC LIMIT 20");
        $games = array();
        for($i = 0; $i < count($sql_logs); $i++)
        {
            $game = dbconnection::queryObject("SELECT * FROM games WHERE game_id = '{$sql_logs[$i]->game_id}'");
            if($game && ($game->ready_for_public || $pack->includeDev))
                $games[] = games::gameObjectFromSQL($game);
        }
        $debugString .= "SELECT: ".(microtime(true)-$sTime)."\n";

        return new return_package(0, $games);
    }

    public static function getNearbyGamesForPlayer($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::getNearbyGamesForPlayerPack($glob); }
    public static function getNearbyGamesForPlayerPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $sql_games = dbconnection::queryArray("SELECT * FROM games WHERE latitude BETWEEN {$pack->latitude}-.5 AND {$pack->latitude}+.5 AND longitude BETWEEN {$pack->longitude}-.5 AND {$pack->longitude}+.5 ".($pack->includeDev ? "" : "AND ready_for_public = TRUE")." GROUP BY game_id LIMIT 50");
        $games = array();
        for($i = 0; $i < count($sql_games); $i++)
            $games[] = games::gameObjectFromSQL($sql_games[$i]);

        return new return_package(0, $games);
    }

    public static function getAnywhereGamesForPlayer($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::getAnywhereGamesForPlayerPack($glob); }
    public static function getAnywhereGamesForPlayerPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $sql_games = dbconnection::queryArray("SELECT * FROM games WHERE full_quick_travel = 1 ".($pack->includeDev ? "" : "AND ready_for_public = TRUE"));
        $games = array();
        for($i = 0; $i < count($sql_games); $i++)
            $game[] = games::gameObjectFromSQL($sql_games[$i]);

        return new return_package(0, $games);
    }

    public static function getPlayerGamesForPlayer($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::getPlayerGamesForPlayerPack($glob); }
    public static function getPlayerGamesForPlayerPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $sql_user_games = dbconnection::queryArray("SELECT * FROM user_games WHERE user_id = '{$pack->auth->user_id}'");
        $games = array();
        for($i = 0; $i < count($sql_user_games); $i++)
        {
            $game = dbconnection::queryObject("SELECT * FROM games WHERE game_id = '{$sql_user_games[$i]->game_id}'");
            $games[] = games::gameObjectFromSQL($game);
        }

        return new return_package(0, $games);
    }

    public static function getPlayerPlayedGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::getPlayerPlayedGamePack($glob); }
    public static function getPlayerPlayedGamePack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
        $record = dbconnection::queryObject("SELECT * FROM user_log WHERE user_id = '{$pack->auth->user_id}' AND game_id = '{$pack->game_id}' AND deleted = '0' LIMIT 1");
        $retObj = new stdClass();
        $retObj->game_id = $pack->game_id;
        $retObj->has_played = ($record != null);
        return new return_package(0,$retObj);
    }

    //an odd request...
    //Creates player-owned instances for every item not already player-instantiated, with qty = 0. Makes qty transactions a million times easier.
    public static function touchItemsForPlayer($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::touchItemsForPlayerPack($glob); }
    public static function touchItemsForPlayerPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
        $items = dbconnection::queryArray("SELECT * FROM items WHERE game_id = '{$pack->game_id}'");
        $instances = dbconnection::queryArray("SELECT * FROM instances WHERE game_id = '{$pack->game_id}' AND owner_id = '{$pack->auth->user_id}'");

        for($i = 0; $i < count($items); $i++)
        {
            $exists = false;
            for($j = 0; $j < count($instances); $j++)
            {
                if($items[$i]->item_id == $instances[$j]->object_id)
                    $exists = true;
            }
            if(!$exists)
                dbconnection::queryInsert("INSERT INTO instances (game_id, object_type, object_id, qty, owner_id, created) VALUES ('{$pack->game_id}', 'ITEM', '{$items[$i]->item_id}', 0, '{$pack->auth->user_id}', CURRENT_TIMESTAMP)");
        }

        return new return_package(0);
    }


    public static function getLogsForPlayer($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::getLogsForPlayerPack($glob); }
    public static function getLogsForPlayerPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
        return new return_package(0, array()); //return nothing, because we don't have offline mode implemented in client yet
    }

    public static function getInstancesForPlayer($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::getInstancesForPlayerPack($glob); }
    public static function getInstancesForPlayerPack($pack)
    {
        return instances::getInstancesForGamePack($pack); //actually gets user instances (already wrapped in return_package), as owner_id is set on pack
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
            $gameTriggers[$i]->user_id = $pack->auth->user_id;
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
            $gameQuests[$i]->user_id = $pack->auth->user_id;

            $gameQuests[$i]->requirement_root_package_id = $gameQuests[$i]->active_requirement_root_package_id;
            if(!requirements::evaluateRequirementPackagePack($gameQuests[$i])) continue; //ensure quest is active/visible

            $gameQuests[$i]->requirement_root_package_id = $gameQuests[$i]->complete_requirement_root_package_id;
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
            $gameTabs[$i]->user_id = $pack->auth->user_id;
            if(requirements::evaluateRequirementPackagePack($gameTabs[$i])) 
                $playerTabs[] = $gameTabs[$i];
        }
        return new return_package(0, $playerTabs);
    }

    public static function getOverlaysForPlayer($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::getOverlaysForPlayerPack($glob); }
    public static function getOverlaysForPlayerPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $gameOverlays = overlays::getOverlaysForGamePack($pack)->data;
        $playerOverlays = array();
        for($i = 0; $i < count($gameOverlays); $i++)
        {
            $gameOverlays[$i]->user_id = $pack->auth->user_id;
            if(requirements::evaluateRequirementPackagePack($gameOverlays[$i])) 
                $playerOverlays[] = $gameOverlays[$i];
        }
        return new return_package(0, $playerOverlays);
    }

    public static function getOptionsForPlayerForDialogScript($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::getOptionsForPlayerForDialogScriptPack($glob); }
    public static function getOptionsForPlayerForDialogScriptPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $scriptOptions = dialogs::getDialogOptionsForScript($pack)->data;
        $playerOptions = array();
        for($i = 0; $i < count($scriptOptions); $i++)
        {
            $scriptOptions[$i]->user_id = $pack->auth->user_id;
            if(requirements::evaluateRequirementPackagePack($scriptOptions[$i])) 
                $playerOptions[] = $scriptOptions[$i];
        }
        return new return_package(0, $playerOptions);
    }

    public static function setQtyForInstance($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::setQtyForInstancePack($glob); }
    public static function setQtyForInstancePack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
        dbconnection::query("UPDATE instances SET qty = '{$pack->qty}' WHERE instance_id = '{$pack->instance_id}'");
        return new return_package(0);
    }

    public static function logPlayerResetGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::logPlayerResetGamePack($glob); }
    public static function logPlayerResetGamePack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'RESET_GAME', CURRENT_TIMESTAMP);");
        dbconnection::query("UPDATE user_log SET deleted = 1 WHERE user_id = '{$pack->auth->user_id}' AND game_id = '{$pack->game_id}'");
        //ok technically does more than just 'logs'
        dbconnection::query("DELETE FROM instances WHERE game_id = '{$pack->game_id}' AND owner_id = '{$pack->auth->user_id}' AND owner_id != 0"); //extra '!= 0' to prevent accidentally deleting all non player instances
        return new return_package(0);
    }

    public static function logPlayerBeganGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::logPlayerBeganGamePack($glob); }
    public static function logPlayerBeganGamePack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'BEGIN_GAME', CURRENT_TIMESTAMP);");
        return new return_package(0);
    }

    public static function logPlayerMoved($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::logPlayerMovedPack($glob); }
    public static function logPlayerMovedPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, latitude, longitude, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'MOVE', '{$pack->latitude}', '{$pack->longitude}', CURRENT_TIMESTAMP);");
        return new return_package(0);
    }

    public static function logPlayerViewedTab($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::logPlayerViewedTabPack($glob); }
    public static function logPlayerViewedTabPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'VIEW_TAB', '{$pack->tab_id}', CURRENT_TIMESTAMP);");
        return new return_package(0);
    }

    public static function logPlayerViewedContent($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::logPlayerViewedContentPack($glob); }
    public static function logPlayerViewedContentPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'VIEW_{$pack->content_type}', '{$pack->content_id}', CURRENT_TIMESTAMP);");
        client::checkForCascadingLogs($pack);
        return new return_package(0);
    }

    public static function logPlayerViewedInstance($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::logPlayerViewedInstancePack($glob); }
    public static function logPlayerViewedInstancePack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'VIEW_INSTANCE', '{$pack->instance_id}', CURRENT_TIMESTAMP);");
        return new return_package(0);
    }

    public static function logPlayerTriggeredTrigger($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::logPlayerTriggeredTriggerPack($glob); }
    public static function logPlayerTriggeredTriggerPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'TRIGGER_TRIGGER', '{$pack->trigger_id}', CURRENT_TIMESTAMP);");
        return new return_package(0);
    }

    public static function logPlayerCompletedQuest($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::logPlayerCompletedQuestPack($glob); }
    public static function logPlayerCompletedQuestPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'COMPLETE_QUEST', '{$pack->quest_id}', CURRENT_TIMESTAMP);");
        if(!$pack->silent) client::checkForCascadingLogs($pack);
        return new return_package(0);
    }

    public static function logPlayerReceivedItem($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::logPlayerReceivedItemPack($glob); }
    public static function logPlayerReceivedItemPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, qty, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'RECEIVE_ITEM', '{$pack->item_id}', '{$pack->qty}', CURRENT_TIMESTAMP);");
        return new return_package(0);
    }

    public static function logPlayerLostItem($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return client::logPlayerLostItemPack($glob); }
    public static function logPlayerLostItemPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, qty, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'LOSE_ITEM', '{$pack->item_id}', '{$pack->qty}', CURRENT_TIMESTAMP);");
        return new return_package(0);
    }

    //analyzes the player log to see if any other logs should exist (QUEST_COMPLETE for example is deterministic on the existence of other logs)
    public static function checkForCascadingLogs($pack)
    {
        $quests = dbconnection::queryArray("SELECT * FROM quests WHERE game_id = '{$pack->game_id}'");
        $completedRecords = dbconnection::queryArray("SELECT * FROM user_log WHERE game_id = '{$pack->game_id}' AND user_id = '{$pack->auth->user_id}' AND event_type = 'COMPLETE_QUEST' AND deleted = 0 GROUP BY content_id");

        $incompleteQuests = array();
        for($i = 0; $i < count($quests); $i++)
        {
            $completed = false;
            for($j = 0; $j < count($completedRecords); $j++)
                if($quests[$i]->quest_id == $completedRecords[$j]->content_id) $completed = true;
            if(!$completed) $incompleteQuests[] = $quests[$i];
        }

        $reqQueryPack = new stdClass();
        $reqQueryPack->game_id = $pack->game_id;
        $reqQueryPack->auth = $pack->auth;
        $questQueryPack = new stdClass();
        $questQueryPack->game_id = $pack->game_id;
        $questQueryPack->auth = $pack->auth;
        $questQueryPack->silent = true; //logPlayerCompletedQuest would otherwise recursively call this function. Might as well save it for the end.
        $dirty = false;
        for($i = 0; $i < count($incompleteQuests); $i++)
        {
            $reqQueryPack->requirement_root_package_id = $incompleteQuests[$i]->complete_requirement_root_package_id;
            $questQueryPack->quest_id = $incompleteQuests[$i]->quest_id;
            if(requirements::evaluateRequirementPackagePack($reqQueryPack))
            {
                client::logPlayerCompletedQuestPack($questQueryPack);
                $dirty = true;
            }
        }
        if($dirty) client::checkForCascadingLogs($pack); //log changed, potentially requiring more logs
    }
}

?>
