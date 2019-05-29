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
require_once("util.php");
require_once("return_package.php");

class client extends dbconnection
{
    //Phil tested on 7/17/14 determined method 1 (JOIN) was consistently ~3x as fast. //NOTE- ABNORMALLY SMALL DATA SET: NEEDS FURTHER TESTING
    public static function getRecentGamesForPlayer($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        //method 1 (JOIN)
        $sTime = microtime(true);
        $page = (isset($pack->page) ? intval($pack->page) : 0);
        $sql_games = dbconnection::queryArray("SELECT * FROM (SELECT game_id, MAX(created) as ts FROM user_log WHERE user_id = '{$pack->auth->user_id}' AND game_id != 0 GROUP BY game_id ORDER BY ts DESC LIMIT ".($page*25).",25) as u_log LEFT JOIN games ON u_log.game_id = games.game_id WHERE games.published = TRUE");
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
            if($game && $game->published)
                $games[] = games::gameObjectFromSQL($game);
        }
        $debugString .= "SELECT: ".(microtime(true)-$sTime)."\n";
*/

        return new return_package(0, $games);
    }

    public static function getSearchGamesForPlayer($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $words = array();
        foreach (preg_split('/\s+/', $pack->text) as $word) {
            if ($word != '') {
                $words[] = $word;
            }
        }
        if(count($words) === 0) return new return_package(0, array()); //technically, returns ALL games. but that's ridiculous, so return none.

        $query = "SELECT * FROM games WHERE 1=1";
        foreach ($words as $word) {
            $esc = addslashes($word);
            $query .= " AND (name LIKE '%{$esc}%' OR description LIKE '%{$esc}%')";
        }
        $page = (isset($pack->page) ? intval($pack->page) : 0);
        $query .= " AND published = TRUE ORDER BY name ASC LIMIT ".($page*25).",25";
        $sql_games = dbconnection::queryArray($query);
        $games = array();
        for($i = 0; $i < count($sql_games); $i++)
            $games[] = games::gameObjectFromSQL($sql_games[$i]);

        return new return_package(0, $games);
    }

    //Phil tested on 7/17/14 determined method 2 (SELECT) was consistently nearly twice as fast. //NOTE- ABNORMALLY SMALL DATA SET: NEEDS FURTHER TESTING
    public static function getPopularGamesForPlayer($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

             if ($pack->interval == "MONTH") $interval = '1 MONTH';
        else if ($pack->interval == "WEEK")  $interval = '7 DAY';
        else                                 $interval = '1 DAY';

        /*
        //method 1 (JOIN)
        $sTime = microtime(true);
        $sql_games = dbconnection::queryArray("SELECT *, COUNT(DISTINCT user_id) as count FROM games INNER JOIN user_log ON games.game_id = user_log.game_id WHERE user_log.created BETWEEN DATE_SUB(NOW(), INTERVAL {$interval}) AND NOW() AND games.published = TRUE GROUP BY games.game_id HAVING count > 1 ORDER BY count DESC LIMIT 20");
        $games = array();
        for($i = 0; $i < count($sql_games); $i++)
            $game[] = games::gameObjectFromSQL($sql_games[$i]);
        $debugString = "JOIN: ".(microtime(true)-$sTime)."\n";
        */

        //method 2 (SELECT)
        $sTime = microtime(true);
        $page = (isset($pack->page) ? intval($pack->page) : 0);
        $sql_logs = dbconnection::queryArray("SELECT game_id, COUNT(DISTINCT user_id) as count FROM user_log WHERE created BETWEEN DATE_SUB(NOW(), INTERVAL {$interval}) AND NOW() GROUP BY game_id HAVING count > 0 ORDER BY count DESC LIMIT ".($page*25).",25");
        $games = array();
        for($i = 0; $i < count($sql_logs); $i++)
        {
            $game = dbconnection::queryObject("SELECT * FROM games WHERE game_id = '{$sql_logs[$i]->game_id}'");
            if($game && $game->published)
            {
                $game_object = games::gameObjectFromSQL($game);
                $game_object->player_count = $sql_logs[$i]->count;
                $games[] = $game_object;
            }
        }
        $debugString .= "SELECT: ".(microtime(true)-$sTime)."\n";

        return new return_package(0, $games);
    }

    public static function getNearbyGamesForPlayer($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $dist = 0.5;
        $games = array();
        $filter = '';
        $key_latitude = 'latitude';
        $key_longitude = 'longitude';
        if (isset($pack->filter) && $pack->filter === 'siftr') {
            $filter .= " AND is_siftr AND (password IS NULL OR password = '') AND published ";
            $key_latitude = 'map_latitude';
            $key_longitude = 'map_longitude';
        }

        while(count($games) < 20 && $dist < 64)
        {
            $sql_games = dbconnection::queryArray("SELECT * FROM games WHERE {$key_latitude} BETWEEN {$pack->latitude}-{$dist} AND {$pack->latitude}+{$dist} AND {$key_longitude} BETWEEN {$pack->longitude}-{$dist} AND {$pack->longitude}+{$dist} AND published = TRUE {$filter} GROUP BY game_id LIMIT 50");
            for($i = 0; $i < count($sql_games); $i++) {
                $game = games::gameObjectFromSQL($sql_games[$i]);
                $already_present = false;
                foreach ($games as $g) {
                    if ($g->game_id === $game->game_id) {
                        $already_present = true;
                        break;
                    }
                }
                if (!$already_present) {
                    $games[] = $game;
                }
            }
            $dist *= 2;
        }

        return new return_package(0, $games);
    }

    public static function getAnywhereGamesForPlayer($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $sql_games = dbconnection::queryArray("SELECT * FROM games WHERE full_quick_travel = 1 AND published = TRUE");
        $games = array();
        for($i = 0; $i < count($sql_games); $i++)
            $game[] = games::gameObjectFromSQL($sql_games[$i]);

        return new return_package(0, $games);
    }

    public static function getPlayerGamesForPlayer($pack)
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

    public static function getPlayerPlayedGame($pack)
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
    //Creates player scene if it doesn't already exist
    public static function touchSceneForPlayer($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $game = dbconnection::queryObject("SELECT * FROM games WHERE game_id = '{$pack->game_id}'");
        $scene = dbconnection::queryObject("SELECT * FROM user_game_scenes WHERE game_id = '{$pack->game_id}' AND user_id = '{$pack->auth->user_id}'");
        if(!$scene) dbconnection::queryInsert("INSERT INTO user_game_scenes (user_id, game_id, scene_id, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', '{$game->intro_scene_id}', CURRENT_TIMESTAMP)");

        return new return_package(0);
    }

    //an odd request...
    //Creates player group if it doesn't already exist
    public static function touchGroupForPlayer($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $group = dbconnection::queryObject("SELECT * FROM user_game_groups WHERE game_id = '{$pack->game_id}' AND user_id = '{$pack->auth->user_id}'");
        if(!$group) dbconnection::queryInsert("INSERT INTO user_game_groups (user_id, game_id, group_id, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', '0', CURRENT_TIMESTAMP)");

        return new return_package(0);
    }

    //an odd request...
    //Creates player-owned instances for every item not already player-instantiated, with qty = 0. Makes qty transactions a million times easier.
    public static function touchItemsForPlayer($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $items = dbconnection::queryArray("SELECT * FROM items WHERE game_id = '{$pack->game_id}'");
        $instances = dbconnection::queryArray("SELECT * FROM instances WHERE game_id = '{$pack->game_id}' AND owner_type = 'USER' AND owner_id = '{$pack->auth->user_id}'");

        for($i = 0; $i < count($items); $i++)
        {
            $exists = false;
            for($j = 0; $j < count($instances); $j++)
            {
                if($instances[$j]->object_type == 'ITEM' && $items[$i]->item_id == $instances[$j]->object_id)
                    $exists = true;
            }
            if(!$exists)
                dbconnection::queryInsert("INSERT INTO instances (game_id, object_type, object_id, qty, owner_type, owner_id, created) VALUES ('{$pack->game_id}', 'ITEM', '{$items[$i]->item_id}', 0, 'USER', '{$pack->auth->user_id}', CURRENT_TIMESTAMP)");
        }

        return new return_package(0);
    }

    //an odd request...
    //Creates game-owned (global) instances for every item not already instantiated, with qty = 0. Makes qty transactions a million times easier.
    //will get called redundantly a ton (every time player begins game)- that's ok
    public static function touchItemsForGame($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $items = dbconnection::queryArray("SELECT * FROM items WHERE game_id = '{$pack->game_id}'");
        $instances = dbconnection::queryArray("SELECT * FROM instances WHERE game_id = '{$pack->game_id}' AND owner_type = 'GAME';");

        for($i = 0; $i < count($items); $i++)
        {
            $exists = false;
            for($j = 0; $j < count($instances); $j++)
            {
                if($instances[$j]->object_type == 'ITEM' && $items[$i]->item_id == $instances[$j]->object_id)
                    $exists = true;
            }
            if(!$exists)
                dbconnection::queryInsert("INSERT INTO instances (game_id, object_type, object_id, qty, owner_type, created) VALUES ('{$pack->game_id}', 'ITEM', '{$items[$i]->item_id}', 0, 'GAME', CURRENT_TIMESTAMP)");
        }

        return new return_package(0);
    }

    //an odd request...
    //Creates game-owned (global) instances for every item not already instantiated, with qty = 0. Makes qty transactions a million times easier.
    //not yet sure how often it will get called... needs design
    public static function touchItemsForGroups($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $groups = dbconnection::queryArray("SELECT * FROM groups WHERE game_id = '{$pack->game_id}'");
        $items = dbconnection::queryArray("SELECT * FROM items WHERE game_id = '{$pack->game_id}'");

        for($i = 0; $i < count($groups); $i++)
        {
          $instances = dbconnection::queryArray("SELECT * FROM instances WHERE game_id = '{$pack->game_id}' AND owner_type = 'GROUP' AND owner_id = '{$groups[$i]->group_id}';");
          for($j = 0; $j < count($items); $j++)
          {
              $exists = false;
              for($k = 0; $k < count($instances); $k++)
              {
                  if($instances[$k]->object_type == 'ITEM' && $items[$j]->item_id == $instances[$k]->object_id)
                      $exists = true;
              }
              if(!$exists)
                  dbconnection::queryInsert("INSERT INTO instances (game_id, object_type, object_id, qty, owner_type, owner_id, created) VALUES ('{$pack->game_id}', 'ITEM', '{$items[$j]->item_id}', 0, 'GROUP', '{$groups[$i]->group_id}', CURRENT_TIMESTAMP)");
          }
        }

        return new return_package(0);
    }

    public static function getSceneForPlayer($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $scene = dbconnection::queryObject("SELECT * FROM user_game_scenes WHERE game_id = '{$pack->game_id}' AND user_id = '{$pack->auth->user_id}'");
        $sceneId = $scene ? $scene->scene_id : 0;
        return new return_package(0, dbconnection::queryObject("SELECT * FROM scenes WHERE scene_id = '{$sceneId}'"));
    }

    public static function getGroupForPlayer($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $group = dbconnection::queryObject("SELECT * FROM user_game_groups WHERE game_id = '{$pack->game_id}' AND user_id = '{$pack->auth->user_id}'");
        $groupId = $group ? $group->group_id : 0;
        return new return_package(0, dbconnection::queryObject("SELECT * FROM groups WHERE group_id = '{$groupId}'"));
    }

    public static function getLogsForPlayer($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
        $logs = dbconnection::queryArray("SELECT * FROM user_log WHERE game_id = '{$pack->game_id}' AND user_id = '{$pack->auth->user_id}' AND deleted = 0;");

        return new return_package(0, $logs);
    }

    public static function getInstancesForPlayer($pack)
    {
        return instances::getInstancesForGame($pack); //actually gets user instances (already wrapped in return_package), as owner_id is set on pack
    }

    public static function getTriggersForPlayer($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        if($pack->tick_factories) Client::tickFactoriesForGame($pack);

        $scene = client::getSceneForPlayer($pack)->data;
        $gameTriggers = triggers::getTriggersForGame($pack)->data;
        $playerTriggers = array();
        for($i = 0; $i < count($gameTriggers); $i++)
        {
            $gameTriggers[$i]->user_id = $pack->auth->user_id;
            if($gameTriggers[$i]->scene_id == $scene->scene_id && requirements::evaluateRequirementPackage($gameTriggers[$i]))
                $playerTriggers[] = $gameTriggers[$i];
        }
        return new return_package(0, $playerTriggers);
    }

    public static function getQuestsForPlayer($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $gameQuests = quests::getQuestsForGame($pack)->data;
        $playerQuests = new stdClass();
        $playerQuests->active   = array();
        $playerQuests->complete = array();
        for($i = 0; $i < count($gameQuests); $i++)
        {
            $gameQuests[$i]->user_id = $pack->auth->user_id;

            $gameQuests[$i]->requirement_root_package_id = $gameQuests[$i]->active_requirement_root_package_id;
            if(!requirements::evaluateRequirementPackage($gameQuests[$i])) continue; //ensure quest is active/visible

            $gameQuests[$i]->requirement_root_package_id = $gameQuests[$i]->complete_requirement_root_package_id;
            if(requirements::evaluateRequirementPackage($gameQuests[$i]))
                $playerQuests->complete[] = $gameQuests[$i];
            else
                $playerQuests->active[] = $gameQuests[$i];

            unset($gameQuests[$i]->requirement_root_package_id); //get rid of redundant attrib
        }
        return new return_package(0, $playerQuests);
    }

    public static function getTabsForPlayer($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $gameTabs = tabs::getTabsForGame($pack)->data;
        $playerTabs = array();
        for($i = 0; $i < count($gameTabs); $i++)
        {
            $gameTabs[$i]->user_id = $pack->auth->user_id;
            if(requirements::evaluateRequirementPackage($gameTabs[$i]))
                $playerTabs[] = $gameTabs[$i];
        }
        return new return_package(0, $playerTabs);
    }

    public static function getOverlaysForPlayer($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $gameOverlays = overlays::getOverlaysForGame($pack)->data;
        $playerOverlays = array();
        for($i = 0; $i < count($gameOverlays); $i++)
        {
            $gameOverlays[$i]->user_id = $pack->auth->user_id;
            if(requirements::evaluateRequirementPackage($gameOverlays[$i]))
                $playerOverlays[] = $gameOverlays[$i];
        }
        return new return_package(0, $playerOverlays);
    }

    public static function getOptionsForPlayerForDialogScript($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $scriptOptions = dialogs::getDialogOptionsForScript($pack)->data;
        $playerOptions = array();
        for($i = 0; $i < count($scriptOptions); $i++)
        {
            $scriptOptions[$i]->user_id = $pack->auth->user_id;
            if(requirements::evaluateRequirementPackage($scriptOptions[$i]))
                $playerOptions[] = $scriptOptions[$i];
        }
        return new return_package(0, $playerOptions);
    }

    public static function tickFactoriesForGame($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $factories = dbconnection::queryArray("SELECT * FROM factories WHERE game_id = '{$pack->game_id}'");

        for($i = 0; $i < count($factories); $i++)
        {
            $fac = $factories[$i];
            if($fac->location_bound_type == 'CLUSTER') {
                continue; // triggers::assignClusters handles these
            }
            $insts = dbconnection::queryArray("SELECT * FROM instances WHERE game_id = '{$pack->game_id}' AND factory_id = '{$fac->factory_id}'");
            $now = strtotime(date("Y-m-d H:i:s"));

            //delete any expired
            for($j = 0; $j < count($insts); $j++)
            {
                $inst = $insts[$j];
                $created = strtotime($inst->created);
                if(($now-$created) > $fac->produce_expiration_time)
                  instances::noauth_deleteInstance($inst);
            }

            //create any new
            $updated = strtotime($fac->production_timestamp);

            //this part is reeeaallly ugly
            $in_valid_scene = false;
            $user_scene_id = 0;
            $user_scene = dbconnection::queryObject("SELECT * FROM user_game_scenes WHERE game_id = '{$pack->game_id}' AND user_id = '{$pack->auth->user_id}' LIMIT 1");
            if($user_scene) $user_scene_id = $user_scene->scene_id;
            $facinsts = dbconnection::queryArray("SELECT * FROM instances WHERE game_id = '{$pack->game_id}' AND object_type = 'FACTORY' AND object_id = '{$fac->factory_id}'");
            $reqQueryPack = new stdClass();
            $reqQueryPack->game_id = $pack->game_id;
            $reqQueryPack->user_id = $pack->auth->user_id;
            for($j = 0; $j < count($facinsts) && !$in_valid_scene; $j++)
            {
                $facinsttrigs = dbconnection::queryArray("SELECT * FROM triggers WHERE game_id = '{$pack->game_id}' AND instance_id = '{$facinsts[$j]->instance_id}'");
                for($k = 0; $k < count($facinsttrigs); $k++)
                {
                    if($facinsttrigs[$k]->scene_id == $user_scene_id)
                    {
                        $reqQueryPack->requirement_root_package_id = $facinsttrigs[$k]->requirement_root_package_id;
                        if(requirements::evaluateRequirementPackage($reqQueryPack)) $in_valid_scene = true;
                    }
                }
            }

            if(
               $in_valid_scene &&                                             //in valid scene
               ($now-$updated) >= $fac->seconds_per_production &&        //hasn't generated recently
               count($insts) < $fac->max_production)                          //hasn't reached max production
            {
                if(rand(0,99) < ($fac->production_probability*100))           //roll the dice
                {
                    $lat = 0;
                    $lon = 0;
                    if($fac->location_bound_type == 'PLAYER')
                    {
                        $move = dbconnection::queryObject("SELECT * FROM user_log WHERE game_id = '{$pack->game_id}' AND user_id = '{$pack->auth->user_id}' AND event_type = 'MOVE' ORDER BY created DESC LIMIT 1");
                        if(!$move)
                        {
                            $lat = 0;
                            $lon = 0;
                        }
                        else
                        {
                            $lat = $move->latitude;
                            $lon = $move->longitude;
                        }
                    }
                    else if($fac->location_bound_type == 'LOCATION')
                    {
                        $lat = $fac->trigger_latitude;
                        $lon = $fac->trigger_longitude;
                    }

                    //need to calculate via trig to get donut of valid area, rather than circle/square
                    $dist = ((rand(0,99)/100)*($fac->max_production_distance-$fac->min_production_distance))+$fac->min_production_distance;
                    $theta = rand(0,359)/(2*pi());
                    $latdelta = $dist*sin($theta);
                    $londelta = $dist*cos($theta);

                    //quick and dirty estimate (supposedly actually pretty good if "less than a few KM and not right near the poles")
                    //111,111 meters = 1* latitude
                    //111,111 * cos(latitude) = 1* longitude

                    $latdelta/=111111;
                    $londelta/=(111111*cos($lat+$latdelta));

                    $lat += $latdelta;
                    $lon += $londelta;

                    $instance_id = dbconnection::queryInsert("INSERT INTO instances (game_id, object_id, object_type, qty, infinite_qty, factory_id, created) VALUES ('{$pack->game_id}', '{$fac->object_id}', '{$fac->object_type}', '1', '0', '{$fac->factory_id}', CURRENT_TIMESTAMP)");
                    $trigger_id = dbconnection::queryInsert("INSERT INTO triggers (game_id, instance_id, scene_id, requirement_root_package_id, type, name, title, latitude, longitude, distance, infinite_distance, wiggle, show_title, hidden, trigger_on_enter, icon_media_id, created) VALUES ('{$pack->game_id}', '{$instance_id}', '{$user_scene_id}', '{$fac->trigger_requirement_root_package_id}', 'LOCATION', '{$fac->trigger_title}', '{$fac->trigger_title}', '{$lat}', '{$lon}', '{$fac->trigger_distance}', '{$fac->trigger_infinite_distance}', '{$fac->trigger_wiggle}', '{$fac->trigger_show_title}', '{$fac->trigger_hidden}', '{$fac->trigger_on_enter}', '{$fac->trigger_icon_media_id}', CURRENT_TIMESTAMP);");
                }
                dbconnection::query("UPDATE factories SET production_timestamp = CURRENT_TIMESTAMP WHERE factory_id = '{$fac->factory_id}'");
            }
        }

        return new return_package(0);
    }

    public static function setQtyForInstance($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
        dbconnection::query("UPDATE instances SET qty = '{$pack->qty}' WHERE instance_id = '{$pack->instance_id}'");
        client::checkForCascadingLogs($pack);
        return new return_package(0);
    }

    public static function dropItem($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $scene_id = dbconnection::queryObject("SELECT * FROM user_game_scenes WHERE user_id = '{$pack->auth->user_id}' AND game_id = '{$pack->game_id}'")->scene_id;
        $item = dbconnection::queryObject("SELECT * FROM items WHERE item_id = '{$pack->item_id}'");
        $instance_id = dbconnection::queryInsert(" INSERT INTO instances (game_id, object_id, object_type, qty, infinite_qty, created) VALUES ('{$pack->game_id}', '{$pack->item_id}', 'ITEM', '{$pack->qty}', '0', CURRENT_TIMESTAMP)");
        $trigger_id = dbconnection::queryInsert("INSERT INTO triggers (game_id, instance_id, scene_id, type, latitude, longitude, distance, infinite_distance, created) VALUES ('{$pack->game_id}', '{$instance_id}', '{$scene_id}', 'LOCATION', '{$pack->latitude}', '{$pack->longitude}', '20', '0', CURRENT_TIMESTAMP);");
        client::checkForCascadingLogs($pack);
        return new return_package(0,$o);
    }

    public static function setPlayerScene($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("UPDATE user_game_scenes SET scene_id = '{$pack->scene_id}' WHERE user_id = '{$pack->auth->user_id}' AND game_id = '{$pack->game_id}'");
        return new return_package(0);
    }

    public static function setPlayerGroup($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("UPDATE user_game_groups SET group_id = '{$pack->group_id}' WHERE user_id = '{$pack->auth->user_id}' AND game_id = '{$pack->game_id}'");
        return new return_package(0);
    }

    public static function logPlayerResetGame($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'RESET_GAME', CURRENT_TIMESTAMP);");
        dbconnection::query("UPDATE user_log SET deleted = 1 WHERE user_id = '{$pack->auth->user_id}' AND game_id = '{$pack->game_id}'");
        //ok technically does more than just 'logs' //so should be separated into own func
        dbconnection::query("DELETE FROM instances WHERE game_id = '{$pack->game_id}' AND owner_type = 'USER' AND owner_id = '{$pack->auth->user_id}' AND owner_id != 0"); //extra '!= 0' to prevent accidentally deleting all non player instances
        dbconnection::query("DELETE FROM user_game_scenes WHERE user_id = '{$pack->auth->user_id}' AND game_id = '{$pack->game_id}'");
        return new return_package(0);
    }

    public static function logPlayerBeganGame($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'BEGIN_GAME', CURRENT_TIMESTAMP);");
        return new return_package(0);
    }

    public static function logPlayerMoved($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, latitude, longitude, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'MOVE', '{$pack->latitude}', '{$pack->longitude}', CURRENT_TIMESTAMP);");
        return new return_package(0);
    }

    public static function logPlayerViewedTab($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'VIEW_TAB', '{$pack->tab_id}', CURRENT_TIMESTAMP);");
        return new return_package(0);
    }

    public static function logPlayerViewedContent($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'VIEW_{$pack->content_type}', '{$pack->content_id}', CURRENT_TIMESTAMP);");
        client::checkForCascadingLogs($pack);
        return new return_package(0);
    }

    public static function logPlayerViewedInstance($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $instance = dbconnection::queryObject("SELECT * FROM instances WHERE instance_id = '{$pack->instance_id}';");
        if($instance->factory_id)
        {
          $factory = dbconnection::queryObject("SELECT * FROM factories WHERE factory_id = '{$instance->factory_id}';");
          if($factory->produce_expire_on_view)
            instances::noauth_deleteInstance($instance);
        }
        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'VIEW_INSTANCE', '{$pack->instance_id}', CURRENT_TIMESTAMP);");
        return new return_package(0);
    }

    public static function logPlayerTriggeredTrigger($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'TRIGGER_TRIGGER', '{$pack->trigger_id}', CURRENT_TIMESTAMP);");
        return new return_package(0);
    }

    public static function logPlayerCompletedQuest($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'COMPLETE_QUEST', '{$pack->quest_id}', CURRENT_TIMESTAMP);");
        if(!$pack->silent) client::checkForCascadingLogs($pack);
        return new return_package(0);
    }

    public static function logPlayerReceivedItem($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, qty, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'RECEIVE_ITEM', '{$pack->item_id}', '{$pack->qty}', CURRENT_TIMESTAMP);");
        client::checkForCascadingLogs($pack);
        return new return_package(0);
    }

    public static function logPlayerLostItem($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, qty, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'LOSE_ITEM', '{$pack->item_id}', '{$pack->qty}', CURRENT_TIMESTAMP);");
        client::checkForCascadingLogs($pack);
        return new return_package(0);
    }

    public static function logGameReceivedItem($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, qty, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'GAME_RECEIVE_ITEM', '{$pack->item_id}', '{$pack->qty}', CURRENT_TIMESTAMP);");
        client::checkForCascadingLogs($pack);
        games::bumpGameVersion($pack);
        return new return_package(0);
    }

    public static function logGameLostItem($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, qty, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'GAME_LOSE_ITEM', '{$pack->item_id}', '{$pack->qty}', CURRENT_TIMESTAMP);");
        client::checkForCascadingLogs($pack);
        games::bumpGameVersion($pack);
        return new return_package(0);
    }

    public static function logGroupReceivedItem($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
        dbconnection::queryInsert("INSERT INTO user_log (user_id, group_id, game_id, event_type, content_id, qty, created) VALUES ('{$pack->auth->user_id}', '{$pack->group_id}', '{$pack->game_id}', 'GROUP_RECEIVE_ITEM', '{$pack->item_id}', '{$pack->qty}', CURRENT_TIMESTAMP);");
        client::checkForCascadingLogs($pack);
        games::bumpGameVersion($pack);
        return new return_package(0);
    }

    public static function logGroupLostItem($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
        dbconnection::queryInsert("INSERT INTO user_log (user_id, group_id, game_id, event_type, content_id, qty, created) VALUES ('{$pack->auth->user_id}', '{$pack->group_id}', '{$pack->game_id}', 'GROUP_LOSE_ITEM', '{$pack->item_id}', '{$pack->qty}', CURRENT_TIMESTAMP);");
        client::checkForCascadingLogs($pack);
        games::bumpGameVersion($pack);
        return new return_package(0);
    }

    public static function logPlayerSetScene($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'CHANGE_SCENE', '{$pack->scene_id}', CURRENT_TIMESTAMP);");
        return new return_package(0);
    }

    public static function logPlayerJoinedGroup($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, group_id, event_type, content_id, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', '{$pack->group_id}', 'JOIN_GROUP', '{$pack->group_id}', CURRENT_TIMESTAMP);");
        return new return_package(0);
    }

    public static function logPlayerRanEventPackage($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'RUN_EVENT_PACKAGE', '{$pack->event_package_id}', CURRENT_TIMESTAMP);");
        return new return_package(0);
    }

    public static function logPlayerCreatedNote($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'CREATE_NOTE', '{$pack->note_id}', CURRENT_TIMESTAMP);");
        client::checkForCascadingLogs($pack);
        return new return_package(0);
    }

    public static function logPlayerUploadedMedia($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $lat = $pack->latitude ? $pack->latitude : 0;
        $lng = $pack->longitude ? $pack->longitude : 0;
        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, latitude, longitude, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'UPLOAD_MEDIA_ITEM', '{$pack->media_id}', '{$lat}', '{$lng}', CURRENT_TIMESTAMP);");
        client::checkForCascadingLogs($pack);
        return new return_package(0);
    }

    public static function logPlayerUploadedMediaImage($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $lat = $pack->latitude ? $pack->latitude : 0;
        $lng = $pack->longitude ? $pack->longitude : 0;
        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, latitude, longitude, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'UPLOAD_MEDIA_ITEM_IMAGE', '{$pack->media_id}', '{$lat}', '{$lng}', CURRENT_TIMESTAMP);");
        client::checkForCascadingLogs($pack);
        return new return_package(0);
    }

    public static function logPlayerUploadedMediaAudio($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $lat = $pack->latitude ? $pack->latitude : 0;
        $lng = $pack->longitude ? $pack->longitude : 0;
        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, latitude, longitude, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'UPLOAD_MEDIA_ITEM_AUDIO', '{$pack->media_id}', '{$lat}', '{$lng}', CURRENT_TIMESTAMP);");
        client::checkForCascadingLogs($pack);
        return new return_package(0);
    }

    public static function logPlayerUploadedMediaVideo($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $lat = $pack->latitude ? $pack->latitude : 0;
        $lng = $pack->longitude ? $pack->longitude : 0;
        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, latitude, longitude, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'UPLOAD_MEDIA_ITEM_VIDEO', '{$pack->media_id}', '{$lat}', '{$lng}', CURRENT_TIMESTAMP);");
        client::checkForCascadingLogs($pack);
        return new return_package(0);
    }

    public static function logPlayerCreatedComment($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::queryInsert("INSERT INTO user_log (user_id, game_id, event_type, content_id, created) VALUES ('{$pack->auth->user_id}', '{$pack->game_id}', 'GIVE_NOTE_COMMENT', '{$pack->note_comment_id}', CURRENT_TIMESTAMP);");
        client::checkForCascadingLogs($pack);
        return new return_package(0);
    }

    //analyzes the player log to see if any other logs should exist (QUEST_COMPLETE for example is deterministic on the existence of other logs)
    public static function checkForCascadingLogs($pack)
    {
        $quests = dbconnection::queryArray("SELECT * FROM quests WHERE game_id = '{$pack->game_id}' AND game_id != 0");
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
        $reqQueryPack->user_id = $pack->auth->user_id;
        $questQueryPack = new stdClass();
        $questQueryPack->game_id = $pack->game_id;
        $questQueryPack->auth = $pack->auth;
        $questQueryPack->silent = true; //logPlayerCompletedQuest would otherwise recursively call this function. Might as well save it for the end.
        $dirty = false;
        for($i = 0; $i < count($incompleteQuests); $i++)
        {
            $reqQueryPack->requirement_root_package_id = $incompleteQuests[$i]->complete_requirement_root_package_id;
            $questQueryPack->quest_id = $incompleteQuests[$i]->quest_id;
            if(requirements::evaluateRequirementPackage($reqQueryPack))
            {
                client::logPlayerCompletedQuest($questQueryPack);
                $dirty = true;
            }
        }
        if($dirty) client::checkForCascadingLogs($pack); //log changed, potentially requiring more logs
    }
}

?>
