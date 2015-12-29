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
        - They give ARIS a group_id
        - group_id gives player_id, player_pic, and in_game_name
        */
        $group_id = intval($pack->group_id);
        $q = "SELECT users.user_id, users.display_name, users.media_id
            , media.file_name, media.file_folder
            FROM user_game_groups
            JOIN users ON users.user_id = user_game_groups.user_id
            LEFT JOIN media ON users.media_id = media.media_id
            WHERE user_game_groups.group_id = {$group_id}
            ";
        $users = dbconnection::queryArray($q);
        if ($users === false) $users = array();

        $new_users = array();
        foreach ($users as $user) {
            $new_user = new stdClass();
            $new_user->player_id = $user->user_id;
            if ($user->media_id) {
                $media = media::mediaObjectFromSQL($user);
                $new_user->player_pic = $media->url; // what about thumb_url ?
            } else {
                $new_user->player_pic = null;
            }
            $new_user->in_game_name = $user->display_name;
            $new_users[] = $new_user;
        }

        return new return_package(0, $new_users);
    }

    public static function getUserBackpack($pack)
    {
        /* Call 2:
        - they give ARIS a player_id, and auth for an owner of 1 or more games
        - they get player_id, player_pic, and in_game_name,
          then the following indexed by game_id:
          (inventory, active/completed quests, notes)
        */
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $q = "SELECT users.user_id, users.display_name, users.media_id
            , media.file_name, media.file_folder
            FROM users
            LEFT JOIN media ON users.media_id = media.media_id
            WHERE users.user_id = {$pack->user_id}
            ";
        $sql_user = dbconnection::queryObject($q);
        if ($sql_user === false) return new return_package(6, NULL, "User not found");
        $backpack = new stdClass();
        $backpack->player_id = $sql_user->user_id;
        if ($sql_user->media_id) {
            $media = media::mediaObjectFromSQL($sql_user);
            $backpack->player_pic = $media->url; // what about thumb_url ?
        } else {
            $backpack->player_pic = null;
        }
        $backpack->in_game_name = $sql_user->display_name;

        $q = "SELECT game_id FROM user_games WHERE user_id = {$pack->auth->user_id}";
        $games = dbconnection::queryArray($q);
        if ($games === false) $games = array();

        $backpack->games = array();
        foreach ($games as $game) {
            $game_id = $game->game_id;

            $q = "SELECT object_id, qty
                FROM instances
                WHERE game_id = {$game_id}
                AND object_type = 'ITEM'
                AND owner_type = 'USER'
                AND owner_id = {$pack->auth->user_id}
                AND qty > 0
                ";
            $inventory = dbconnection::queryArray($q);
            if ($inventory === false) $inventory = array();

            $q = "SELECT DISTINCT content_id
                FROM user_log
                WHERE user_id = {$pack->auth->user_id}
                AND game_id = {$game_id}
                AND event_type = 'COMPLETE_QUEST'
                ";
            $sql_quests = dbconnection::queryArray($q);
            if ($sql_quests === false) $sql_quests = array();
            $quests = array();
            foreach ($sql_quests as $quest) {
                $quests[] = $quest->content_id;
            }

            // TODO: notes

            $result = new stdClass();
            $result->inventory = $inventory;
            $result->quests = $quests;
            $backpack->games[$game_id] = $result;
        }

        return new return_package(0, $backpack);
    }
}
