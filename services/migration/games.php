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
            (isset($pack->name)                   ? "name,"                   : "").
            (isset($pack->description)            ? "description,"            : "").
            (isset($pack->icon_media_id)          ? "icon_media_id,"          : "").
            (isset($pack->media_id)               ? "media_id,"               : "").
            (isset($pack->map_type)               ? "map_type,"               : "").
            (isset($pack->latitude)               ? "latitude,"               : "").
            (isset($pack->longitude)              ? "longitude,"              : "").
            (isset($pack->zoom_level)             ? "zoom_level,"             : "").
            (isset($pack->show_player_location)   ? "show_player_location,"   : "").
            (isset($pack->full_quick_travel)      ? "full_quick_travel,"      : "").
            (isset($pack->allow_note_comments)    ? "allow_note_comments,"    : "").
            (isset($pack->allow_note_player_tags) ? "allow_note_player_tags," : "").
            (isset($pack->allow_note_likes)       ? "allow_note_likes,"       : "").
            (isset($pack->inventory_weight_cap)   ? "inventory_weight_cap,"   : "").
            (isset($pack->ready_for_public)       ? "ready_for_public,"       : "").
            "created".
            ") VALUES (".
            (isset($pack->name)                   ? "'".addslashes($pack->name)."',"                   : "").
            (isset($pack->description)            ? "'".addslashes($pack->description)."',"            : "").
            (isset($pack->icon_media_id)          ? "'".addslashes($pack->icon_media_id)."',"          : "").
            (isset($pack->media_id)               ? "'".addslashes($pack->media_id)."',"               : "").
            (isset($pack->map_type)               ? "'".addslashes($pack->map_type)."',"               : "").
            (isset($pack->latitude)               ? "'".addslashes($pack->latitude)."',"               : "").
            (isset($pack->longitude)              ? "'".addslashes($pack->longitude)."',"              : "").
            (isset($pack->zoom_level)             ? "'".addslashes($pack->zoom_level)."',"             : "").
            (isset($pack->show_player_location)   ? "'".addslashes($pack->show_player_location)."',"   : "").
            (isset($pack->full_quick_travel)      ? "'".addslashes($pack->full_quick_travel)."',"      : "").
            (isset($pack->allow_note_comments)    ? "'".addslashes($pack->allow_note_comments)."',"    : "").
            (isset($pack->allow_note_player_tags) ? "'".addslashes($pack->allow_note_player_tags)."'," : "").
            (isset($pack->allow_note_likes)       ? "'".addslashes($pack->allow_note_likes)."',"       : "").
            (isset($pack->inventory_weight_cap)   ? "'".addslashes($pack->inventory_weight_cap)."',"   : "").
            (isset($pack->ready_for_public)       ? "'".addslashes($pack->ready_for_public)."',"       : "").
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
