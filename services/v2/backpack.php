<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("media.php");
require_once("return_package.php");
require_once("requirements.php");
require_once("quests.php");

class backpack extends dbconnection
{
    public static function getGroupUsers($pack)
    {
        /* Call 1:
        - They give ARIS a group_name and game_ids
        - Returns an array of (player_id, player_pic, player_thumb, in_game_name)
        */
        $group_name = addslashes($pack->group_name);
        if (empty($group_name)) {
            return new return_package(0, array());
        }
        $game_ids = $pack->game_ids;
        if (is_array($game_ids)) {
            $game_ids = array_map('intval', $game_ids);
        } else {
            $game_ids = array(intval($game_ids));
        }
        if (empty($game_ids)) {
            return new return_package(0, array());
        }
        $relogin = isset($pack->relogin) && $pack->relogin;
        if ($relogin) {
            // For the relogin API: must supply authentication info
            // for an editor of all the games
            foreach ($game_ids as $game_id) {
                $pack->auth->game_id    = $game_id;
                $pack->auth->permission = "read_write"  ;
                if (!editors::authenticateGameEditor($pack->auth)) {
                    return new return_package(6, NULL, "Failed Authentication");
                }
            }
        }
        $game_ids = implode(',', $game_ids);

        $q = "SELECT users.user_id, users.display_name, users.media_id, users.read_write_key
            , media.file_name, media.file_folder
            FROM users
            LEFT JOIN media ON users.media_id = media.media_id
            INNER JOIN user_log ON users.user_id = user_log.user_id
            WHERE users.group_name = \"{$group_name}\"
            AND user_log.game_id IN ({$game_ids})
            GROUP BY users.user_id
            ";
        $users = dbconnection::queryArray($q);
        if ($users === false) $users = array();

        $new_users = array();
        foreach ($users as $user) {
            $new_user = new stdClass();
            $new_user->player_id = intval($user->user_id);
            if ($user->media_id) {
                $media = media::mediaObjectFromSQL($user);
                $new_user->player_pic = $media->url;
                $new_user->player_thumb = $media->thumb_url;
            } else {
                $new_user->player_pic = null;
                $new_user->player_thumb = null;
            }
            $new_user->in_game_name = $user->display_name;
            if ($relogin) {
                $new_user->read_write_key = $user->read_write_key;
            }
            $new_users[] = $new_user;
        }

        return new return_package(0, $new_users);
    }

    public static function getUserBackpack($pack)
    {
        /* Call 2:
        - they give ARIS a player_id, and a game_ids array
        - they get player_id, player_pic, player_thumb, and in_game_name,
          then the following indexed by game_id:
          (inventory, active/completed quests)
        */
        $player_id = intval($pack->player_id);

        $q = "SELECT users.user_id, users.display_name, users.media_id
            , media.file_name, media.file_folder
            FROM users
            LEFT JOIN media ON users.media_id = media.media_id
            WHERE users.user_id = {$player_id}
            ";
        $sql_user = dbconnection::queryObject($q);
        if ($sql_user === false) return new return_package(6, NULL, "User not found");
        $backpack = new stdClass();
        $backpack->player_id = $player_id;
        if ($sql_user->media_id) {
            $media = media::mediaObjectFromSQL($sql_user);
            $backpack->player_pic = $media->url;
            $backpack->player_thumb = $media->thumb_url;
        } else {
            $backpack->player_pic = null;
            $backpack->player_thumb = null;
        }
        $backpack->in_game_name = $sql_user->display_name;

        $game_ids = $pack->game_ids;
        if (is_array($game_ids)) {
            $game_ids = array_map('intval', $game_ids);
        } else {
            $game_ids = array(intval($game_ids));
        }

        $backpack->games = array();
        foreach ($game_ids as $game_id) {
            // First, make sure the player has actually started this game
            $q = "SELECT instance_id
                FROM instances
                WHERE instances.game_id = {$game_id}
                AND instances.owner_type = 'USER'
                AND instances.owner_id = {$player_id}
                ";
            $instances = dbconnection::queryArray($q);
            if ($instances === false || !count($instances)) continue;

            $q = "SELECT name FROM games WHERE game_id = {$game_id}";
            $game_name = dbconnection::queryObject($q)->name;

            $q = "SELECT instances.object_id, instances.qty, items.name, items.type, GROUP_CONCAT(DISTINCT tags.tag SEPARATOR '!TAG!') as tags
                FROM instances
                INNER JOIN items ON items.item_id = instances.object_id
                LEFT JOIN object_tags ON object_tags.object_type = 'ITEM' and object_tags.object_id = instances.object_id
                LEFT JOIN tags ON object_tags.tag_id = tags.tag_id
                WHERE instances.game_id = {$game_id}
                AND instances.object_type = 'ITEM'
                AND instances.owner_type = 'USER'
                AND instances.owner_id = {$player_id}
                AND instances.qty > 0
                GROUP BY instances.object_id
                ";
            $inventory = dbconnection::queryArray($q);
            if ($inventory === false) $inventory = array();
            foreach ($inventory as $item) {
                $item->object_id = intval($item->object_id);
                $item->qty = intval($item->qty);
                $item->tags = explode('!TAG!', $item->tags);
            }

            $obj = new stdClass();
            $obj->game_id = $game_id;
            $all_quests = quests::getQuestsForGame($obj)->data;
            $quests = array();
            foreach ($all_quests as $quest)
            {
                $quest->user_id = $player_id;
                $quest->requirement_root_package_id = $quest->active_requirement_root_package_id;
                if(!requirements::evaluateRequirementPackage($quest)) continue; //ensure quest is active/visible

                $ret_quest = new stdClass();
                $ret_quest->quest_id = $quest->quest_id;
                $ret_quest->name = $quest->name;
                if (isset($quest->active_icon_media_id) && $quest->active_icon_media_id) {
                    $obj = new stdClass();
                    $obj->media_id = $quest->active_icon_media_id;
                    $ret_quest->icon_url = media::getMedia($obj)->data->url;
                }
                $quests[] = $ret_quest;
            }

            $result = new stdClass();
            $result->name = $game_name;
            $result->inventory = $inventory;
            $result->quests = $quests;
            $backpack->games[$game_id] = $result;
        }

        return new return_package(0, $backpack);
    }

    public static function getItemsForGame($pack)
    {
        $game_ids = $pack->game_ids;
        if (is_array($game_ids)) {
            $game_ids = array_map('intval', $game_ids);
        } else {
            $game_ids = array(intval($game_ids));
        }

        $backpack->games = array();
        foreach ($game_ids as $game_id) {

            $q = "SELECT name FROM games WHERE game_id = {$game_id}";
            $game_name = dbconnection::queryObject($q)->name;

            $q = "SELECT items.item_id as object_id, items.name, items.type, GROUP_CONCAT(DISTINCT tags.tag SEPARATOR '!TAG!') as tags
                FROM items
                LEFT JOIN object_tags ON object_tags.object_type = 'ITEM' and object_tags.object_id = items.item_id
                LEFT JOIN tags ON object_tags.tag_id = tags.tag_id
                WHERE items.game_id = {$game_id}
                GROUP BY items.item_id
                ";
            $inventory = dbconnection::queryArray($q);
            if ($inventory === false) $inventory = array();
            foreach ($inventory as $item) {
                $item->object_id = intval($item->object_id);
                $item->qty = intval($item->qty);
                $item->tags = explode('!TAG!', $item->tags);
            }

            $result = new stdClass();
            $result->name = $game_name;
            $result->inventory = $inventory;
            $backpack->games[$game_id] = $result;
        }

        return new return_package(0, $backpack);
    }
}
