<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("return_package.php");

class games extends dbconnection
{	
    //Takes in game JSON, all fields optional except user_id + key
    public static function createGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return games::createGamePack($glob); }
    public static function createGamePack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->game_id = dbconnection::queryInsert(
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

        dbconnection::queryInsert("INSERT INTO user_games (game_id, user_id, created) VALUES ('{$pack->game_id}','{$pack->auth->user_id}',CURRENT_TIMESTAMP)");

        dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ('{$pack->game_id}', 'QUESTS',    '1')");
        dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ('{$pack->game_id}', 'GPS',       '2')");
        dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ('{$pack->game_id}', 'INVENTORY', '3')");
        dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ('{$pack->game_id}', 'QR',        '4')");
        dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ('{$pack->game_id}', 'PLAYER',    '5')");
        dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ('{$pack->game_id}', 'NOTE',      '6')");

        mkdir(Config::gamedataFSPath."/{$pack->game_id}",0777);

        return games::getGamePack($pack);
    }

    //Takes in game JSON, all fields optional except user_id + key
    public static function updateGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return games::updateGamePack($glob); }
    public static function updateGamePack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

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

        return games::getGamePack($pack);
    }

    private static function gameObjectFromSQL($sql_game)
    {
        $game = new stdClass();
        $game->game_id       = $sql_game->game_id;
        $game->name          = $sql_game->name;
        $game->description   = $sql_game->description;
        $game->icon_media_id = $sql_game->icon_media_id;
        $game->media_id      = $sql_game->media_id;
        $game->map_type      = $sql_game->map_type;
        $game->latitude      = $sql_game->latitude;
        $game->longitude     = $sql_game->longitude;
        $game->zoom_level    = $sql_game->zoom_level;
        $game->show_player_location = $sql_game->show_player_location;
        $game->full_quick_travel = $sql_game->full_quick_travel;
        $game->allow_note_comments = $sql_game->allow_note_comments;
        $game->allow_note_player_tags = $sql_game->allow_note_player_tags;
        $game->allow_note_likes = $sql_game->allow_note_likes;
        $game->inventory_weight_cap = $sql_game->inventory_weight_cap;
        $game->ready_for_public = $sql_game->ready_for_public;

        return $game;
    }

    public static function getGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return games::getGamePack($glob); }
    public static function getGamePack($pack)
    {
        $sql_game = dbconnection::queryObject("SELECT * FROM games WHERE game_id = '{$pack->game_id}' LIMIT 1");
        if(!$sql_game) return new return_package(2, NULL, "The game you've requested does not exist");
        return new return_package(0,games::gameObjectFromSQL($sql_game));
    }

    public static function getGamesForUser($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return games::getGamesForUserPack($glob); }
    public static function getGamesForUserPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $sql_games = dbconnection::queryArray("SELECT * FROM user_games LEFT JOIN games ON user_games.game_id = games.game_id WHERE user_games.user_id = '{$pack->auth->user_id}'");
        $games = array();
        for($i = 0; $i < count($sql_games); $i++)
            $games[] = games::gameObjectFromSQL($sql_games[$i]);

        return new return_package(0,$games);
    }

    public static function deleteGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return games::deleteGamePack($glob); }
    public static function deleteGamePack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM games WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM game_tab_data WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM state_changes WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM items WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM npcs WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM npc_scripts WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM plaques WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM web_pages WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM notes WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM note_labels WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM note_media WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM quests WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM media WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM scenes WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM instances WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM triggers WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM requirement_root_packages WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM requirement_and_packages WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM requirement_atoms WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM user_game_scenes WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM user_instances WHERE game_id = '{$pack->game_id}' LIMIT 1");
        dbconnection::query("DELETE FROM user_games WHERE game_id = '{$pack->game_id}' LIMIT 1");
        $command = 'rm -rf '. Config::gamedataFSPath . "/{$pack->game_id}";
        exec($command, $output, $return);
        if($return) return new return_package(4, NULL, "unable to delete game directory");
        return new return_package(0);
    }
}
?>
