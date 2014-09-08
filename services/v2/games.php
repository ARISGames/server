<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("media.php");
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
            (isset($pack->name)                       ? "name,"                       : "").
            (isset($pack->description)                ? "description,"                : "").
            (isset($pack->icon_media_id)              ? "icon_media_id,"              : "").
            (isset($pack->media_id)                   ? "media_id,"                   : "").
            (isset($pack->map_type)                   ? "map_type,"                   : "").
            (isset($pack->map_latitude)               ? "map_latitude,"               : "").
            (isset($pack->map_longitude)              ? "map_longitude,"              : "").
            (isset($pack->map_zoom_level)             ? "map_zoom_level,"             : "").
            (isset($pack->map_show_player)            ? "map_show_player,"            : "").
            (isset($pack->map_show_players)           ? "map_show_players,"           : "").
            (isset($pack->map_offsite_mode)           ? "map_offsite_mode,"           : "").
            (isset($pack->notebook_allow_comments)    ? "notebook_allow_comments,"    : "").
            (isset($pack->notebook_allow_player_tags) ? "notebook_allow_player_tags," : "").
            (isset($pack->notebook_allow_likes)       ? "notebook_allow_likes,"       : "").
            (isset($pack->inventory_weight_cap)       ? "inventory_weight_cap,"       : "").
            (isset($pack->published)                  ? "published,"                  : "").
            (isset($pack->type)                       ? "type,"                       : "").
            (isset($pack->intro_scene_id)             ? "intro_scene_id,"             : "").
            "created".
            ") VALUES (".
            (isset($pack->name)                       ? "'".addslashes($pack->name)."',"                       : "").
            (isset($pack->description)                ? "'".addslashes($pack->description)."',"                : "").
            (isset($pack->icon_media_id)              ? "'".addslashes($pack->icon_media_id)."',"              : "").
            (isset($pack->media_id)                   ? "'".addslashes($pack->media_id)."',"                   : "").
            (isset($pack->map_type)                   ? "'".addslashes($pack->map_type)."',"                   : "").
            (isset($pack->map_latitude)               ? "'".addslashes($pack->map_latitude)."',"               : "").
            (isset($pack->map_longitude)              ? "'".addslashes($pack->map_longitude)."',"              : "").
            (isset($pack->map_zoom_level)             ? "'".addslashes($pack->map_zoom_level)."',"             : "").
            (isset($pack->map_show_player)            ? "'".addslashes($pack->map_show_player)."',"            : "").
            (isset($pack->map_show_players)           ? "'".addslashes($pack->map_show_players)."',"           : "").
            (isset($pack->map_offsite_mode)           ? "'".addslashes($pack->map_offsite_mode)."',"           : "").
            (isset($pack->notebook_allow_comments)    ? "'".addslashes($pack->notebook_allow_comments)."',"    : "").
            (isset($pack->notebook_allow_player_tags) ? "'".addslashes($pack->notebook_allow_player_tags)."'," : "").
            (isset($pack->notebook_allow_likes)       ? "'".addslashes($pack->notebook_allow_likes)."',"       : "").
            (isset($pack->inventory_weight_cap)       ? "'".addslashes($pack->inventory_weight_cap)."',"       : "").
            (isset($pack->published)                  ? "'".addslashes($pack->published)."',"                  : "").
            (isset($pack->type)                       ? "'".addslashes($pack->type)."',"                       : "").
            (isset($pack->intro_scene_id)             ? "'".addslashes($pack->intro_scene_id)."',"             : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        dbconnection::queryInsert("INSERT INTO user_games (game_id, user_id, created) VALUES ('{$pack->game_id}','{$pack->auth->user_id}',CURRENT_TIMESTAMP)");

        dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'QUESTS',    'Quests',    '0', '1', CURRENT_TIMESTAMP)");
        dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'MAP',       'Map',       '0', '2', CURRENT_TIMESTAMP)");
        dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'INVENTORY', 'Inventory', '0', '3', CURRENT_TIMESTAMP)");
        dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'SCANNER',   'Scanner',   '0', '4', CURRENT_TIMESTAMP)");
        dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'DECODER',   'Decoder',   '0', '5', CURRENT_TIMESTAMP)");
        dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'PLAYER',    'Player',    '0', '6', CURRENT_TIMESTAMP)");
        dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'NOTEBOOK',  'Notebook',  '0', '7', CURRENT_TIMESTAMP)");

        mkdir(Config::v2_gamedata_folder."/{$pack->game_id}",0777);

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
            (isset($pack->name)                       ? "name                       = '".addslashes($pack->name)."', "                       : "").
            (isset($pack->description)                ? "description                = '".addslashes($pack->description)."', "                : "").
            (isset($pack->icon_media_id)              ? "icon_media_id              = '".addslashes($pack->icon_media_id)."', "              : "").
            (isset($pack->media_id)                   ? "media_id                   = '".addslashes($pack->media_id)."', "                   : "").
            (isset($pack->map_type)                   ? "map_type                   = '".addslashes($pack->map_type)."', "                   : "").
            (isset($pack->map_latitude)               ? "map_latitude               = '".addslashes($pack->map_latitude)."', "               : "").
            (isset($pack->map_longitude)              ? "map_longitude              = '".addslashes($pack->map_longitude)."', "              : "").
            (isset($pack->map_zoom_level)             ? "map_zoom_level             = '".addslashes($pack->map_zoom_level)."', "             : "").
            (isset($pack->map_show_player)            ? "map_show_player            = '".addslashes($pack->map_show_player)."', "            : "").
            (isset($pack->map_show_players)           ? "map_show_players           = '".addslashes($pack->map_show_players)."', "           : "").
            (isset($pack->map_offsite_mode)           ? "map_offsite_mode           = '".addslashes($pack->map_offsite_mode)."', "           : "").
            (isset($pack->notebook_allow_comments)    ? "notebook_allow_comments    = '".addslashes($pack->notebook_allow_comments)."', "    : "").
            (isset($pack->notebook_allow_player_tags) ? "notebook_allow_player_tags = '".addslashes($pack->notebook_allow_player_tags)."', " : "").
            (isset($pack->notebook_allow_likes)       ? "notebook_allow_likes       = '".addslashes($pack->notebook_allow_likes)."', "       : "").
            (isset($pack->inventory_weight_cap)       ? "inventory_weight_cap       = '".addslashes($pack->inventory_weight_cap)."', "       : "").
            (isset($pack->published)                  ? "published                  = '".addslashes($pack->published)."', "                  : "").
            (isset($pack->type)                       ? "type                       = '".addslashes($pack->type)."', "                       : "").
            (isset($pack->intro_scene_id)             ? "intro_scene_id             = '".addslashes($pack->intro_scene_id)."', "             : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE game_id = '{$pack->game_id}'"
        );

        return games::getGamePack($pack);
    }

    public static function gameObjectFromSQL($sql_game)
    {
        if(!$sql_game) return $sql_game;
        $game = new stdClass();
        $game->game_id                    = $sql_game->game_id;
        $game->name                       = $sql_game->name;
        $game->description                = $sql_game->description;
        $game->icon_media_id              = $sql_game->icon_media_id;
        $game->media_id                   = $sql_game->media_id;
        $game->map_type                   = $sql_game->map_type;
        $game->map_latitude               = $sql_game->map_latitude;
        $game->map_longitude              = $sql_game->map_longitude;
        $game->map_zoom_level             = $sql_game->map_zoom_level;
        $game->map_show_player            = $sql_game->map_show_player;
        $game->map_show_players           = $sql_game->map_show_players;
        $game->map_offsite_mode           = $sql_game->map_offsite_mode;
        $game->notebook_allow_comments    = $sql_game->notebook_allow_comments;
        $game->notebook_allow_player_tags = $sql_game->notebook_allow_player_tags;
        $game->notebook_allow_likes       = $sql_game->notebook_allow_likes;
        $game->inventory_weight_cap       = $sql_game->inventory_weight_cap;
        $game->published                  = $sql_game->published;
        $game->type                       = $sql_game->type;
        $game->intro_scene_id             = $sql_game->intro_scene_id;

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
            if($ob = games::gameObjectFromSQL($sql_games[$i])) $games[] = $ob;

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
        dbconnection::query("DELETE FROM events WHERE game_id = '{$pack->game_id}' LIMIT 1");
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
        dbconnection::query("DELETE FROM user_games WHERE game_id = '{$pack->game_id}' LIMIT 1");
        $command = 'rm -rf '. Config::v2_gamedata_folder . "/{$pack->game_id}";
        exec($command, $output, $return);
        if($return) return new return_package(4, NULL, "unable to delete game directory");
        return new return_package(0);
    }

    public static function getFullGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return games::getFullGamePack($glob); }
    public static function getFullGamePack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $sql_game = dbconnection::queryObject("SELECT * FROM games WHERE game_id = '{$pack->game_id}' LIMIT 1");
        if(!$sql_game) return new return_package(2, NULL, "The game you've requested does not exist");

        $game = games::getGamePack($pack)->data;

        $game->authors = users::getUsersForGamePack($pack)->data; //pack already has auth and game_id

        //heres where we just hack the pack for use in other requests without overhead of creating new packs
        $pack->media_id = $game->media_id;
        $game->media = media::getMediaPack($pack)->data;
        $pack->media_id = $game->icon_media_id;
        $game->icon_media = media::getMediaPack($pack)->data;

        return new return_package(0,$game);
    }
}
?>
