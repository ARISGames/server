<?php

require_once("dbconnection.php");
require_once("returnData.php");
require_once("users.php");
require_once("editors.php");

class games extends dbconnection
{	
    //Takes in game JSON, all fields optional except user_id + key
    public static function createGameJSON($glob)
    {
	$data = file_get_contents("php://input");
        $glob = json_decode($data);
        return games::createGame($glob);
    }

    public static function createGame($pack)
    {
        if(!users::authenticateUser($pack->auth->user_id, $pack->auth->key, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $gameId = dbconnection::queryInsert(
            "INSERT INTO games (".
            ($pack->name                   ? "name,"                   : "").
            ($pack->description            ? "description,"            : "").
            ($pack->icon_media_id          ? "icon_media_id,"          : "").
            ($pack->media_id               ? "media_id,"               : "").
            ($pack->map_type               ? "map_type,"               : "").
            ($pack->latitude               ? "latitude,"               : "").
            ($pack->longitude              ? "longitude,"              : "").
            ($pack->zoom_level             ? "zoom_level,"             : "").
            ($pack->show_player_location   ? "show_player_location,"   : "").
            ($pack->full_quick_travel      ? "full_quick_travel,"      : "").
            ($pack->allow_note_comments    ? "allow_note_comments,"    : "").
            ($pack->allow_note_player_tags ? "allow_note_player_tags," : "").
            ($pack->allow_note_likes       ? "allow_note_likes,"       : "").
            ($pack->inventory_weight_cap   ? "inventory_weight_cap,"   : "").
            ($pack->ready_for_public       ? "ready_for_public,"       : "").
            "created".
            ") VALUES (".
            ($pack->name                   ? "'".addslashes($pack->name)."',"                   : "").
            ($pack->description            ? "'".addslashes($pack->description)."',"            : "").
            ($pack->icon_media_id          ? "'".addslashes($pack->icon_media_id)."',"          : "").
            ($pack->media_id               ? "'".addslashes($pack->media_id)."',"               : "").
            ($pack->map_type               ? "'".addslashes($pack->map_type)."',"               : "").
            ($pack->latitude               ? "'".addslashes($pack->latitude)."',"               : "").
            ($pack->longitude              ? "'".addslashes($pack->longitude)."',"              : "").
            ($pack->zoom_level             ? "'".addslashes($pack->zoom_level)."',"             : "").
            ($pack->show_player_location   ? "'".addslashes($pack->show_player_location)."',"   : "").
            ($pack->full_quick_travel      ? "'".addslashes($pack->full_quick_travel)."',"      : "").
            ($pack->allow_note_comments    ? "'".addslashes($pack->allow_note_comments)."',"    : "").
            ($pack->allow_note_player_tags ? "'".addslashes($pack->allow_note_player_tags)."'," : "").
            ($pack->allow_note_likes       ? "'".addslashes($pack->allow_note_likes)."',"       : "").
            ($pack->inventory_weight_cap   ? "'".addslashes($pack->inventory_weight_cap)."',"   : "").
            ($pack->ready_for_public       ? "'".addslashes($pack->ready_for_public)."',"       : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        dbconnection::queryInsert("INSERT INTO user_games (game_id, user_id, created) VALUES ('{$gameId}','{$pack->auth->user_id}',CURRENT_TIMESTAMP)");

        dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ('{$gameId}', 'QUESTS',    '1')");
        dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ('{$gameId}', 'GPS',       '2')");
        dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ('{$gameId}', 'INVENTORY', '3')");
        dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ('{$gameId}', 'QR',        '4')");
        dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ('{$gameId}', 'PLAYER',    '5')");
        dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ('{$gameId}', 'NOTE',      '6')");

        mkdir(Config::gamedataFSPath."/{$gameId}",0777);

        return games::getGame($gameId);
    }

    //Takes in game JSON, all fields optional except user_id + key
    public static function updateGameJSON($glob)
    {
	$data = file_get_contents("php://input");
        $glob = json_decode($data);
        return games::updateGame($glob);
    }

    public static function updateGame($pack)
    {
        if(!editors::authenticateGameEditor($pack->game_id, $pack->auth->user_id, $pack->auth->key, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE games SET ".
            ($pack->name                   ? "name                   = '".addslashes($pack->name)."', "                   : "").
            ($pack->description            ? "description            = '".addslashes($pack->description)."', "            : "").
            ($pack->icon_media_id          ? "icon_media_id          = '".addslashes($pack->icon_media_id)."', "          : "").
            ($pack->media_id               ? "media_id               = '".addslashes($pack->media_id)."', "               : "").
            ($pack->map_type               ? "map_type               = '".addslashes($pack->map_type)."', "               : "").
            ($pack->latitude               ? "latitude               = '".addslashes($pack->latitude)."', "               : "").
            ($pack->longitude              ? "longitude              = '".addslashes($pack->longitude)."', "              : "").
            ($pack->zoom_level             ? "zoom_level             = '".addslashes($pack->zoom_level)."', "             : "").
            ($pack->show_player_location   ? "show_player_location   = '".addslashes($pack->show_player_location)."', "   : "").
            ($pack->full_quick_travel      ? "full_quick_travel      = '".addslashes($pack->full_quick_travel)."', "      : "").
            ($pack->allow_note_comments    ? "allow_note_comments    = '".addslashes($pack->allow_note_comments)."', "    : "").
            ($pack->allow_note_player_tags ? "allow_note_player_tags = '".addslashes($pack->allow_note_player_tags)."', " : "").
            ($pack->allow_note_likes       ? "allow_note_likes       = '".addslashes($pack->allow_note_likes)."', "       : "").
            ($pack->inventory_weight_cap   ? "inventory_weight_cap   = '".addslashes($pack->inventory_weight_cap)."', "   : "").
            ($pack->ready_for_public       ? "ready_for_public       = '".addslashes($pack->ready_for_public)."', "       : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE game_id = '{$pack->game_id}'"
        );

        return games::getGame($pack->game_id);
    }

    public static function getGame($gameId)
    {
        $sql_game = dbconnection::queryObject("SELECT * FROM games WHERE game_id = '{$gameId}' LIMIT 1");
        if(!$sql_game) return new returnData(2, NULL, "The game you've requested does not exist");

        $game = new stdClass();
        $game->game_id = $sql_game->game_id;
        $game->name = $sql_game->name;
        $game->description = $sql_game->description;
        $game->icon_media_id = $sql_game->icon_media_id;
        $game->media_id = $sql_game->media_id;
        $game->map_type = $sql_game->map_type;
        $game->latitude = $sql_game->latitude;
        $game->longitude = $sql_game->longitude;
        $game->zoom_level = $sql_game->zoom_level;
        $game->show_player_location = $sql_game->show_player_location;
        $game->full_quick_travel = $sql_game->full_quick_travel;
        $game->allow_note_comments = $sql_game->allow_note_comments;
        $game->allow_note_player_tags = $sql_game->allow_note_player_tags;
        $game->allow_note_likes = $sql_game->allow_note_likes;
        $game->inventory_weight_cap = $sql_game->inventory_weight_cap;
        $game->ready_for_public = $sql_game->ready_for_public;

        return new returnData(0,$game);
    }

    public static function deleteGame($gameId, $userId, $key)
    {
        if(!editors::authenticateGameEditor($gameId, $userId, $key, "read_write")) return new returnData(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM games WHERE game_id = '{$gameId}' LIMIT 1");
        dbconnection::query("DELETE FROM game_tab_data WHERE game_id = '{$gameId}' LIMIT 1");
        $command = 'rm -rf '. Config::gamedataFSPath . "/{$gameId}";
        exec($command, $output, $return);
        if($return) return new returnData(4, NULL, "unable to delete game directory");
        return new returnData(0);
    }
}
?>
