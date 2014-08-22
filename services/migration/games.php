<?php

// THIS IS JUST A COPIED->PASTED CREATEGAME FUNCTION FROM /v2/games.php!!
// dbconnection needs to be switched to migration_dbconnection

require_once("migration_dbconnection.php");
require_once("migration_return_package.php");

class mig_games extends migration_dbconnection
{	
    public static function createGame($pack)
    {
        $pack->game_id = migration_dbconnection::queryInsert(
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
            "CURRENT_TIMESTAMP".
            ")",
        "v2");

        migration_dbconnection::queryInsert("INSERT INTO user_games (game_id, user_id, created) VALUES ('{$pack->game_id}','{$pack->auth->user_id}',CURRENT_TIMESTAMP)", "v2");

        migration_dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'QUESTS',    'Quests',    '0', '1', CURRENT_TIMESTAMP)", "v2");
        migration_dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'MAP',       'Map',       '0', '2', CURRENT_TIMESTAMP)", "v2");
        migration_dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'INVENTORY', 'Inventory', '0', '3', CURRENT_TIMESTAMP)", "v2");
        migration_dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'SCANNER',   'Scanner',   '0', '4', CURRENT_TIMESTAMP)", "v2");
        migration_dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'DECODER',   'Decoder',   '0', '5', CURRENT_TIMESTAMP)", "v2");
        migration_dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'PLAYER',    'Player',    '0', '6', CURRENT_TIMESTAMP)", "v2");
        migration_dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'NOTE',      'Note',      '0', '7', CURRENT_TIMESTAMP)", "v2");

        mkdir(Config::v2_gamedata_folder."/{$pack->game_id}",0777);

        return $pack->game_id;
    }
}
?>
