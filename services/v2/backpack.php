<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("media.php");
require_once("return_package.php");

class backpack extends dbconnection
{
    public static function getGroupUsers($pack)
    {
        /* Call 1:
        - They give ARIS a group_name and game_ids
        - Returns an array of (player_id, player_pic, player_thumb, in_game_name)
        */
        $group_name = addslashes($pack->group_name);
        $game_ids = $pack->game_ids;
        if (is_array($game_ids)) {
            $game_ids = array_map('intval', $game_ids);
        } else {
            $game_ids = array(intval($game_ids));
        }
        if (empty($game_ids)) {
            return new return_package(0, array());
        }
        $game_ids = implode(',', $game_ids);

        $q = "SELECT users.user_id, users.display_name, users.media_id
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
            $q = "SELECT instances.object_id, instances.qty, items.name, items.type
                FROM instances
                INNER JOIN items ON items.item_id = instances.object_id
                WHERE instances.game_id = {$game_id}
                AND instances.object_type = 'ITEM'
                AND instances.owner_type = 'USER'
                AND instances.owner_id = {$player_id}
                AND instances.qty > 0
                ";
            $inventory = dbconnection::queryArray($q);
            if ($inventory === false) $inventory = array();
            foreach ($inventory as $item) {
                $item->object_id = intval($item->object_id);
                $item->qty = intval($item->qty);
            }

            $q = "SELECT DISTINCT content_id
                FROM user_log
                WHERE user_id = {$player_id}
                AND game_id = {$game_id}
                AND event_type = 'COMPLETE_QUEST'
                AND NOT deleted
                ";
            $sql_quests = dbconnection::queryArray($q);
            if ($sql_quests === false) $sql_quests = array();
            $quests = array();
            foreach ($sql_quests as $quest) {
                $quests[] = intval($quest->content_id);
            }

            $result = new stdClass();
            $result->inventory = $inventory;
            $result->quests = $quests;
            $backpack->games[$game_id] = $result;
        }

        return new return_package(0, $backpack);
    }
}
