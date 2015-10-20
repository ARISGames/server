<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("media.php");
require_once("scenes.php");
require_once("return_package.php");

class games extends dbconnection
{
    //Takes in game JSON, all fields optional except user_id + key
    public static function createGame($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        if (isset($pack->siftr_url) && $pack->siftr_url == '') {
            // For creation, a siftr_url of undefined, null, or empty string are all the same.
            // They all become NULL in the db.
            $pack->siftr_url = null;
        }
        $url_result = games::isValidSiftrURL($pack);
        if ($url_result->returnCode != 0) return $url_result;

        $pack->game_id = dbconnection::queryInsert(
            "INSERT INTO games (".
            (isset($pack->name)                                         ? "name,"                                         : "").
            (isset($pack->description)                                  ? "description,"                                  : "").
            (isset($pack->icon_media_id)                                ? "icon_media_id,"                                : "").
            (isset($pack->media_id)                                     ? "media_id,"                                     : "").
            (isset($pack->map_type)                                     ? "map_type,"                                     : "").
            (isset($pack->map_focus)                                    ? "map_focus,"                                    : "").
            (isset($pack->map_latitude)                                 ? "map_latitude,"                                 : "").
            (isset($pack->map_longitude)                                ? "map_longitude,"                                : "").
            (isset($pack->map_zoom_level)                               ? "map_zoom_level,"                               : "").
            (isset($pack->map_show_player)                              ? "map_show_player,"                              : "").
            (isset($pack->map_show_players)                             ? "map_show_players,"                             : "").
            (isset($pack->map_offsite_mode)                             ? "map_offsite_mode,"                             : "").
            (isset($pack->notebook_allow_comments)                      ? "notebook_allow_comments,"                      : "").
            (isset($pack->notebook_allow_likes)                         ? "notebook_allow_likes,"                         : "").
            (isset($pack->notebook_trigger_scene_id)                    ? "notebook_trigger_scene_id,"                    : "").
            (isset($pack->notebook_trigger_requirement_root_package_id) ? "notebook_trigger_requirement_root_package_id," : "").
            (isset($pack->notebook_trigger_title)                       ? "notebook_trigger_title,"                       : "").
            (isset($pack->notebook_trigger_icon_media_id)               ? "notebook_trigger_icon_media_id,"               : "").
            (isset($pack->notebook_trigger_distance)                    ? "notebook_trigger_distance,"                    : "").
            (isset($pack->notebook_trigger_infinite_distance)           ? "notebook_trigger_infinite_distance,"           : "").
            (isset($pack->notebook_trigger_wiggle)                      ? "notebook_trigger_wiggle,"                      : "").
            (isset($pack->notebook_trigger_show_title)                  ? "notebook_trigger_show_title,"                  : "").
            (isset($pack->notebook_trigger_hidden)                      ? "notebook_trigger_hidden,"                      : "").
            (isset($pack->notebook_trigger_on_enter)                    ? "notebook_trigger_on_enter,"                    : "").
            (isset($pack->inventory_weight_cap)                         ? "inventory_weight_cap,"                         : "").
            (isset($pack->is_siftr)                                     ? "is_siftr,"                                     : "").
            (isset($pack->siftr_url)                                    ? "siftr_url,"                                    : "").
            (isset($pack->published)                                    ? "published,"                                    : "").
            (isset($pack->type)                                         ? "type,"                                         : "").
            (isset($pack->intro_scene_id)                               ? "intro_scene_id,"                               : "").
            (isset($pack->latitude)                                     ? "latitude,"                                     : "").
            (isset($pack->longitude)                                    ? "longitude,"                                    : "").
            (isset($pack->network_level)                                ? "network_level,"                                : "").
            (isset($pack->allow_download)                               ? "allow_download,"                               : "").
            (isset($pack->preload_media)                                ? "preload_media,"                                : "").
            (isset($pack->moderated)                                    ? "moderated,"                                    : "").
            (isset($pack->version)                                      ? "version,"                                      : "").
            "created".
            ") VALUES (".
            (isset($pack->name)                                         ? "'".addslashes($pack->name)."',"                                         : "").
            (isset($pack->description)                                  ? "'".addslashes($pack->description)."',"                                  : "").
            (isset($pack->icon_media_id)                                ? "'".addslashes($pack->icon_media_id)."',"                                : "").
            (isset($pack->media_id)                                     ? "'".addslashes($pack->media_id)."',"                                     : "").
            (isset($pack->map_type)                                     ? "'".addslashes($pack->map_type)."',"                                     : "").
            (isset($pack->map_focus)                                    ? "'".addslashes($pack->map_focus)."',"                                    : "").
            (isset($pack->map_latitude)                                 ? "'".addslashes($pack->map_latitude)."',"                                 : "").
            (isset($pack->map_longitude)                                ? "'".addslashes($pack->map_longitude)."',"                                : "").
            (isset($pack->map_zoom_level)                               ? "'".addslashes($pack->map_zoom_level)."',"                               : "").
            (isset($pack->map_show_player)                              ? "'".addslashes($pack->map_show_player)."',"                              : "").
            (isset($pack->map_show_players)                             ? "'".addslashes($pack->map_show_players)."',"                             : "").
            (isset($pack->map_offsite_mode)                             ? "'".addslashes($pack->map_offsite_mode)."',"                             : "").
            (isset($pack->notebook_allow_comments)                      ? "'".addslashes($pack->notebook_allow_comments)."',"                      : "").
            (isset($pack->notebook_allow_likes)                         ? "'".addslashes($pack->notebook_allow_likes)."',"                         : "").
            (isset($pack->notebook_trigger_scene_id)                    ? "'".addslashes($pack->notebook_trigger_scene_id)."',"                    : "").
            (isset($pack->notebook_trigger_requirement_root_package_id) ? "'".addslashes($pack->notebook_trigger_requirement_root_package_id)."'," : "").
            (isset($pack->notebook_trigger_title)                       ? "'".addslashes($pack->notebook_trigger_title)."',"                       : "").
            (isset($pack->notebook_trigger_icon_media_id)               ? "'".addslashes($pack->notebook_trigger_icon_media_id)."',"               : "").
            (isset($pack->notebook_trigger_distance)                    ? "'".addslashes($pack->notebook_trigger_distance)."',"                    : "").
            (isset($pack->notebook_trigger_infinite_distance)           ? "'".addslashes($pack->notebook_trigger_infinite_distance)."',"           : "").
            (isset($pack->notebook_trigger_wiggle)                      ? "'".addslashes($pack->notebook_trigger_wiggle)."',"                      : "").
            (isset($pack->notebook_trigger_show_title)                  ? "'".addslashes($pack->notebook_trigger_show_title)."',"                  : "").
            (isset($pack->notebook_trigger_hidden)                      ? "'".addslashes($pack->notebook_trigger_hidden)."',"                      : "").
            (isset($pack->notebook_trigger_on_enter)                    ? "'".addslashes($pack->notebook_trigger_on_enter)."',"                    : "").
            (isset($pack->inventory_weight_cap)                         ? "'".addslashes($pack->inventory_weight_cap)."',"                         : "").
            (isset($pack->is_siftr)                                     ? "'".addslashes($pack->is_siftr)."',"                                     : "").
            (isset($pack->siftr_url)                                    ? "'".addslashes($pack->siftr_url)."',"                                    : "").
            (isset($pack->published)                                    ? "'".addslashes($pack->published)."',"                                    : "").
            (isset($pack->type)                                         ? "'".addslashes($pack->type)."',"                                         : "").
            (isset($pack->intro_scene_id)                               ? "'".addslashes($pack->intro_scene_id)."',"                               : "").
            (isset($pack->latitude)                                     ? "'".addslashes($pack->latitude)."',"                                     : "").
            (isset($pack->longitude)                                    ? "'".addslashes($pack->longitude)."',"                                    : "").
            (isset($pack->network_level)                                ? "'".addslashes($pack->network_level)."',"                                : "").
            (isset($pack->allow_download)                               ? "'".addslashes($pack->allow_download)."',"                               : "").
            (isset($pack->preload_media)                                ? "'".addslashes($pack->preload_media)."',"                                : "").
            (isset($pack->moderated)                                    ? "'".addslashes($pack->moderated)."',"                                    : "").
            (isset($pack->version)                                      ? "'".addslashes($pack->version)."',"                                      : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        dbconnection::queryInsert("INSERT INTO user_games (game_id, user_id, created) VALUES ('{$pack->game_id}','{$pack->auth->user_id}',CURRENT_TIMESTAMP)");

        //                                                                                                          game_id,         type,    name, icon, sort, created
        dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'QUESTS',    '', '0', '1', CURRENT_TIMESTAMP)");
        dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'MAP',       '', '0', '2', CURRENT_TIMESTAMP)");
        dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'INVENTORY', '', '0', '3', CURRENT_TIMESTAMP)");
        dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'SCANNER',   '', '0', '4', CURRENT_TIMESTAMP)");
        dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'DECODER',   '', '0', '5', CURRENT_TIMESTAMP)");
        dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'PLAYER',    '', '0', '6', CURRENT_TIMESTAMP)");
        dbconnection::query("INSERT INTO tabs (game_id, type, name, icon_media_id, sort_index, created) VALUES ('{$pack->game_id}', 'NOTEBOOK',  '', '0', '7', CURRENT_TIMESTAMP)");

        mkdir(Config::v2_gamedata_folder."/{$pack->game_id}",0777);


        $pack->name = "Starting Scene";
        $pack->description = "";
        scenes::createScene($pack);

        return games::getGame($pack);
    }

    //no need for security- just invalidates local game downloads
    public static function bumpGameVersion($pack)
    {
      dbconnection::query("UPDATE games SET version = version+1, last_active = CURRENT_TIMESTAMP WHERE game_id = '{$pack->game_id}';");
    }

    //Takes in game JSON, all fields optional except user_id + key
    public static function updateGame($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $url_result = games::isValidSiftrURL($pack);
        if ($url_result->returnCode != 0) return $url_result;
        // If the URL is an empty string (but NOT undefined or null), that means it is being explicitly set to NULL in db.
        $unset_url = isset($pack->siftr_url) && $pack->siftr_url == '';
        if ($unset_url) unset($pack->siftr_url);

        //ensure requested scene_id exists, otherwise pick one from list of existing scenes
        //this is a hack, in case you were wondering...
        if(!dbconnection::queryObject("SELECT * FROM scenes WHERE scene_id = '{$pack->intro_scene_id}' AND game_id = '{$pack->game_id}'"))
        {
            $pack->intro_scene_id = 0; //fallback if we can't find a good one
            $scenes = dbconnection::queryArray("SELECT * FROM scenes WHERE game_id = '{$pack->game_id}'");
            if(count($scenes) > 0) $pack->intro_scene_id = $scenes[0]->scene_id;
        }

        dbconnection::query(
            "UPDATE games SET ".
            (isset($pack->name)                                         ? "name                                         = '".addslashes($pack->name)."', "                                         : "").
            (isset($pack->description)                                  ? "description                                  = '".addslashes($pack->description)."', "                                  : "").
            (isset($pack->icon_media_id)                                ? "icon_media_id                                = '".addslashes($pack->icon_media_id)."', "                                : "").
            (isset($pack->media_id)                                     ? "media_id                                     = '".addslashes($pack->media_id)."', "                                     : "").
            (isset($pack->map_type)                                     ? "map_type                                     = '".addslashes($pack->map_type)."', "                                     : "").
            (isset($pack->map_focus)                                    ? "map_focus                                    = '".addslashes($pack->map_focus)."', "                                    : "").
            (isset($pack->map_latitude)                                 ? "map_latitude                                 = '".addslashes($pack->map_latitude)."', "                                 : "").
            (isset($pack->map_longitude)                                ? "map_longitude                                = '".addslashes($pack->map_longitude)."', "                                : "").
            (isset($pack->map_zoom_level)                               ? "map_zoom_level                               = '".addslashes($pack->map_zoom_level)."', "                               : "").
            (isset($pack->map_show_player)                              ? "map_show_player                              = '".addslashes($pack->map_show_player)."', "                              : "").
            (isset($pack->map_show_players)                             ? "map_show_players                             = '".addslashes($pack->map_show_players)."', "                             : "").
            (isset($pack->map_offsite_mode)                             ? "map_offsite_mode                             = '".addslashes($pack->map_offsite_mode)."', "                             : "").
            (isset($pack->notebook_allow_comments)                      ? "notebook_allow_comments                      = '".addslashes($pack->notebook_allow_comments)."', "                      : "").
            (isset($pack->notebook_allow_likes)                         ? "notebook_allow_likes                         = '".addslashes($pack->notebook_allow_likes)."', "                         : "").
            (isset($pack->notebook_trigger_scene_id)                    ? "notebook_trigger_scene_id                    = '".addslashes($pack->notebook_trigger_scene_id)."', "                    : "").
            (isset($pack->notebook_trigger_requirement_root_package_id) ? "notebook_trigger_requirement_root_package_id = '".addslashes($pack->notebook_trigger_requirement_root_package_id)."', " : "").
            (isset($pack->notebook_trigger_title)                       ? "notebook_trigger_title                       = '".addslashes($pack->notebook_trigger_title)."', "                       : "").
            (isset($pack->notebook_trigger_icon_media_id)               ? "notebook_trigger_icon_media_id               = '".addslashes($pack->notebook_trigger_icon_media_id)."', "               : "").
            (isset($pack->notebook_trigger_distance)                    ? "notebook_trigger_distance                    = '".addslashes($pack->notebook_trigger_distance)."', "                    : "").
            (isset($pack->notebook_trigger_infinite_distance)           ? "notebook_trigger_infinite_distance           = '".addslashes($pack->notebook_trigger_infinite_distance)."', "           : "").
            (isset($pack->notebook_trigger_wiggle)                      ? "notebook_trigger_wiggle                      = '".addslashes($pack->notebook_trigger_wiggle)."', "                      : "").
            (isset($pack->notebook_trigger_show_title)                  ? "notebook_trigger_show_title                  = '".addslashes($pack->notebook_trigger_show_title)."', "                  : "").
            (isset($pack->notebook_trigger_hidden)                      ? "notebook_trigger_hidden                      = '".addslashes($pack->notebook_trigger_hidden)."', "                      : "").
            (isset($pack->notebook_trigger_on_enter)                    ? "notebook_trigger_on_enter                    = '".addslashes($pack->notebook_trigger_on_enter)."', "                    : "").
            (isset($pack->inventory_weight_cap)                         ? "inventory_weight_cap                         = '".addslashes($pack->inventory_weight_cap)."', "                         : "").
            (isset($pack->is_siftr)                                     ? "is_siftr                                     = '".addslashes($pack->is_siftr)."', "                                     : "").
            (isset($pack->siftr_url)                                    ? "siftr_url                                    = '".addslashes($pack->siftr_url)."', "                                    : "").
            ($unset_url                                                 ? "siftr_url                                    = NULL, "                                                                  : "").
            (isset($pack->published)                                    ? "published                                    = '".addslashes($pack->published)."', "                                    : "").
            (isset($pack->type)                                         ? "type                                         = '".addslashes($pack->type)."', "                                         : "").
            (isset($pack->intro_scene_id)                               ? "intro_scene_id                               = '".addslashes($pack->intro_scene_id)."', "                               : "").
            (isset($pack->latitude)                                     ? "latitude                                     = '".addslashes($pack->latitude)."', "                                     : "").
            (isset($pack->longitude)                                    ? "longitude                                    = '".addslashes($pack->longitude)."', "                                    : "").
            (isset($pack->network_level)                                ? "network_level                                = '".addslashes($pack->network_level)."', "                                : "").
            (isset($pack->allow_download)                               ? "allow_download                               = '".addslashes($pack->allow_download)."', "                               : "").
            (isset($pack->preload_media)                                ? "preload_media                                = '".addslashes($pack->preload_media)."', "                                : "").
            (isset($pack->moderated)                                    ? "moderated                                    = '".addslashes($pack->moderated)."', "                                    : "").
            (isset($pack->version)                                      ? "version                                      = '".addslashes($pack->version)."', "                                      : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE game_id = '{$pack->game_id}'"
        );

        games::bumpGameVersion($pack);
        return games::getGame($pack);
    }

    public static function gameObjectFromSQL($sql_game)
    {
        if(!$sql_game) return $sql_game;
        $game = new stdClass();
        $game->game_id                                      = $sql_game->game_id;
        $game->name                                         = $sql_game->name;
        $game->description                                  = $sql_game->description;
        $game->icon_media_id                                = $sql_game->icon_media_id;
        $game->media_id                                     = $sql_game->media_id;
        $game->map_type                                     = $sql_game->map_type;
        $game->map_focus                                    = $sql_game->map_focus;
        $game->map_latitude                                 = $sql_game->map_latitude;
        $game->map_longitude                                = $sql_game->map_longitude;
        $game->map_zoom_level                               = $sql_game->map_zoom_level;
        $game->map_show_player                              = $sql_game->map_show_player;
        $game->map_show_players                             = $sql_game->map_show_players;
        $game->map_offsite_mode                             = $sql_game->map_offsite_mode;
        $game->notebook_allow_comments                      = $sql_game->notebook_allow_comments;
        $game->notebook_allow_likes                         = $sql_game->notebook_allow_likes;
        $game->notebook_trigger_scene_id                    = $sql_game->notebook_trigger_scene_id;
        $game->notebook_trigger_requirement_root_package_id = $sql_game->notebook_trigger_requirement_root_package_id;
        $game->notebook_trigger_title                       = $sql_game->notebook_trigger_title;
        $game->notebook_trigger_icon_media_id               = $sql_game->notebook_trigger_icon_media_id;
        $game->notebook_trigger_distance                    = $sql_game->notebook_trigger_distance;
        $game->notebook_trigger_infinite_distance           = $sql_game->notebook_trigger_infinite_distance;
        $game->notebook_trigger_wiggle                      = $sql_game->notebook_trigger_wiggle;
        $game->notebook_trigger_show_title                  = $sql_game->notebook_trigger_show_title;
        $game->notebook_trigger_hidden                      = $sql_game->notebook_trigger_hidden;
        $game->notebook_trigger_on_enter                    = $sql_game->notebook_trigger_on_enter;
        $game->inventory_weight_cap                         = $sql_game->inventory_weight_cap;
        $game->is_siftr                                     = $sql_game->is_siftr;
        $game->siftr_url                                    = $sql_game->siftr_url;
        $game->published                                    = $sql_game->published;
        $game->type                                         = $sql_game->type;
        $game->intro_scene_id                               = $sql_game->intro_scene_id;
        $game->latitude                                     = $sql_game->latitude;
        $game->longitude                                    = $sql_game->longitude;
        $game->network_level                                = $sql_game->network_level;
        $game->allow_download                               = $sql_game->allow_download;
        $game->preload_media                                = $sql_game->preload_media;
        $game->moderated                                    = $sql_game->moderated;
        $game->version                                      = $sql_game->version;

        return $game;
    }

    public static function getGame($pack)
    {
        $sql_game = dbconnection::queryObject("SELECT * FROM games WHERE game_id = '{$pack->game_id}' LIMIT 1");
        if(!$sql_game) return new return_package(2, NULL, "The game you've requested does not exist");
        return new return_package(0,games::gameObjectFromSQL($sql_game));
    }

    public static function searchSiftrs($pack)
    {
        $siftr_url = isset($pack->siftr_url) ? addslashes($pack->siftr_url) : null;
        $count     = isset($pack->count    ) ? intval    ($pack->count    ) : 0   ;
        $order_by  = isset($pack->order_by ) ?            $pack->order_by   : null;
        $days      = isset($pack->days     ) ? intval    ($pack->days     ) : 30  ;
        $search    = isset($pack->search   ) ? addslashes($pack->search   ) : null;

        $q = "SELECT g.* FROM games AS g";
        if ($order_by === "recent" || $order_by === "popular") {
            $q .= " LEFT JOIN notes AS n ON g.game_id = n.game_id";
            // TODO: also use note_comments?
        }

        $q .= " WHERE g.is_siftr";
        if ($siftr_url) $q .= " AND g.siftr_url = '".$pack->siftr_url."'";
        if (!$siftr_url) $q .= " AND g.published";
        if ($search) {
            foreach (preg_split('/\s+/', $search) as $word) {
                if ($word != '') {
                    $q .= " AND (g.name LIKE '%$word%' OR g.description LIKE '%$word%' OR g.siftr_url LIKE '%$word%')";
                }
            }
        }
        if ($order_by === "recent") {
            $q .= " GROUP BY g.game_id";
            $q .= " ORDER BY MAX(n.last_active) DESC";
        }
        else if ($order_by === "popular") {
            $q .= " AND (n.created IS NULL OR DATEDIFF(NOW(), n.created) <= $days)";
            $q .= " GROUP BY g.game_id";
            $q .= " ORDER BY COUNT(n.note_id) DESC";
        }

        if ($count) $q .= " LIMIT $count";

        $sql_games = dbconnection::queryArray($q);
        $games = array();
        for($i = 0; $i < count($sql_games); $i++) {
            if ( $ob = games::gameObjectFromSQL($sql_games[$i]) ) {
                $games[] = $ob;
            }
        }
        return new return_package(0, $games);
    }

    public static function isValidSiftrURL($pack)
    {
        // If URL is undefined, null, or empty string, return true.
        // (but, these have different meanings in createGame and updateGame, see those fns for details)
        if (!property_exists($pack, 'siftr_url')) return new return_package(0, true);
        $url = (string) ($pack->siftr_url);
        if ($url == '') return new return_package(0, true);

        $sql_game = dbconnection::queryObject("SELECT * FROM games WHERE siftr_url = '{$url}' LIMIT 1");
        if ($sql_game) {
            if (isset($pack->game_id) && intval($pack->game_id) == intval($sql_game->game_id)) {
                // all good, we're updating an existing Siftr to have its existing URL
            }
            else {
                return new return_package(2, NULL, "That URL is already taken");
            }
        }
        if (!preg_match('/[A-Za-z]/', $url))
            return new return_package(2, NULL, "The URL must have at least one letter");
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $url))
            return new return_package(2, NULL, "The URL must consist only of letters, numbers, underscores, and dashes");
        if ($url == 'editor') // special case
            return new return_package(2, NULL, "That URL is already taken");
        return new return_package(0, true);
    }

    public static function getGamesForUser($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $sql_games = dbconnection::queryArray("SELECT * FROM user_games LEFT JOIN games ON user_games.game_id = games.game_id WHERE user_games.user_id = '{$pack->auth->user_id}' AND games.game_id IS NOT NULL");
        $games = array();
        for($i = 0; $i < count($sql_games); $i++)
            if($ob = games::gameObjectFromSQL($sql_games[$i])) $games[] = $ob;

        return new return_package(0,$games);
    }

    public static function deleteGame($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        if(!is_numeric($pack->game_id)) return new return_package(1, NULL, "Invalid game ID format");

        $tables = array();
        $tables[] = "dialog_characters";
        $tables[] = "dialog_options";
        $tables[] = "dialog_scripts";
        $tables[] = "dialogs";
        $tables[] = "event_packages";
        $tables[] = "events";
        $tables[] = "factories";
        $tables[] = "game_comments";
        $tables[] = "instances";
        $tables[] = "items";
        $tables[] = "media";
        $tables[] = "note_comments";
        $tables[] = "note_likes";
        $tables[] = "notes";
        $tables[] = "object_tags";
        $tables[] = "overlays";
        $tables[] = "plaques";
        $tables[] = "quests";
        $tables[] = "requirement_and_packages";
        $tables[] = "requirement_atoms";
        $tables[] = "requirement_root_packages";
        $tables[] = "scenes";
        $tables[] = "tabs";
        $tables[] = "tags";
        $tables[] = "triggers";
        $tables[] = "user_game_scenes";
        $tables[] = "user_games";
        $tables[] = "web_hooks";
        $tables[] = "web_pages";

        dbconnection::query("DELETE FROM games WHERE game_id = '{$pack->game_id}' LIMIT 1");
        for($i = 0; $i < count($tables); $i++)
          dbconnection::query("DELETE FROM {$tables[$i]} WHERE game_id = '{$pack->game_id}'");

        $command = 'rm -rf '. Config::v2_gamedata_folder . "/{$pack->game_id}";
        exec($command, $output, $return);
        if($return) return new return_package(4, NULL, "unable to delete game directory");
        return new return_package(0);
    }

    public static function cleanOrphans($pack)
    {
      $tables = array();
      $tables[] = "dialog_characters";
      $tables[] = "dialog_options";
      $tables[] = "dialog_scripts";
      $tables[] = "dialogs";
      $tables[] = "event_packages";
      $tables[] = "events";
      $tables[] = "factories";
      $tables[] = "game_comments";
      $tables[] = "instances";
      $tables[] = "items";
      $tables[] = "media";
      $tables[] = "note_comments";
      $tables[] = "note_likes";
      $tables[] = "notes";
      $tables[] = "object_tags";
      $tables[] = "overlays";
      $tables[] = "plaques";
      $tables[] = "quests";
      $tables[] = "requirement_and_packages";
      $tables[] = "requirement_atoms";
      $tables[] = "requirement_root_packages";
      $tables[] = "scenes";
      $tables[] = "tabs";
      $tables[] = "tags";
      $tables[] = "triggers";
      $tables[] = "user_game_scenes";
      $tables[] = "user_games";
      $tables[] = "web_hooks";
      $tables[] = "web_pages";

      for($i = 0; $i < count($tables); $i++)
      {
        $arr = dbconnection::queryArray("SELECT {$tables[$i]}.game_id as game_id, games.game_id as n_game_id FROM {$tables[$i]} LEFT JOIN games ON {$tables[$i]}.game_id = games.game_id WHERE games.game_id IS NULL GROUP BY game_id;");
        for($j = 0; $j < count($arr); $j++)
        {
          if($tables[$i] == "media" && $arr[$j]->game_id == 0) continue; //allow default media
          if($pack->execute)
            dbconnection::query("DELETE FROM {$tables[$i]} WHERE game_id = '{$arr[$j]->game_id}';");
          else //dry run
            echo "DELETE FROM {$tables[$i]} WHERE game_id = '{$arr[$j]->game_id}'; (game_id = '{$arr[$j]->game_id}')\n";
        }
      }

      //instances
      $types = array();
      $tables = array();
      $ids = array();
      $types[] = "PLAQUE"; $tables[] = "plaques"; $ids[] = "plaque_id";
      $types[] = "ITEM"; $tables[] = "items"; $ids[] = "item_id";
      $types[] = "DIALOG"; $tables[] = "dialogs"; $ids[] = "dialog_id";
      $types[] = "WEB_PAGE"; $tables[] = "web_pages"; $ids[] = "web_page_id";
      $types[] = "NOTE"; $tables[] = "notes"; $ids[] = "note_id";
      $types[] = "FACTORY"; $tables[] = "factories"; $ids[] = "factory_id";

      for($i = 0; $i < count($types); $i++)
      {
        $arr = dbconnection::queryArray("SELECT instances.* FROM instances LEFT JOIN {$tables[$i]} ON instances.object_id = {$tables[$i]}.{$ids[$i]} WHERE instances.object_type = '{$types[$i]}' AND {$tables[$i]}.{$ids[$i]} IS NULL;");
        for($j = 0; $j < count($arr); $j++)
        {
          if($pack->execute)
            dbconnection::query("DELETE FROM instances WHERE instance_id = '{$arr[$j]->instance_id}';");
          else //dry run
            echo "DELETE FROM instances WHERE instance_id = '{$arr[$j]->instance_id}'; (game_id = '{$arr[$j]->game_id}')\n";
        }
      }

      //triggers
      $arr = dbconnection::queryArray("SELECT triggers.* FROM triggers LEFT JOIN instances ON triggers.instance_id = instances.instance_id WHERE instances.instance_id IS NULL;");
      for($j = 0; $j < count($arr); $j++) //use '$j' for consistency
      {
        if($pack->execute)
          dbconnection::query("DELETE FROM triggers WHERE trigger_id = '{$arr[$j]->trigger_id}';");
        else //dry run
          echo "DELETE FROM triggers WHERE trigger_id = '{$arr[$j]->trigger_id}'; (game_id = '{$arr[$j]->game_id}')\n";
      }

      //requirements
      // \/ query to manually see requirement tree in SQL. nice for debugging.
      //SELECT ratom.requirement_atom_id, rand.requirement_and_package_id, rroot.requirement_root_package_id FROM requirement_atoms as ratom LEFT JOIN requirement_and_packages as rand ON ratom.requirement_and_package_id = rand.requirement_and_package_id LEFT JOIN requirement_root_packages as rroot ON rand.requirement_root_package_id = rroot.requirement_root_package_id WHERE rand.game_id = 3259;
      $arr = dbconnection::queryArray("SELECT requirement_and_packages.* FROM requirement_and_packages LEFT JOIN requirement_root_packages ON requirement_and_packages.requirement_root_package_id = requirement_root_packages.requirement_root_package_id WHERE requirement_root_packages.requirement_root_package_id IS NULL;");
      for($j = 0; $j < count($arr); $j++) //use '$j' for consistency
      {
        if($pack->execute)
          dbconnection::query("DELETE FROM requirement_and_packages WHERE requirement_and_package_id = '{$arr[$j]->requirement_and_package_id}';");
        else //dry run
          echo "DELETE FROM requirement_and_packages WHERE requirement_and_package_id = '{$arr[$j]->requirement_and_package_id}'; (game_id = '{$arr[$j]->game_id}')\n";
      }
      $arr = dbconnection::queryArray("SELECT requirement_atoms.* FROM requirement_atoms LEFT JOIN requirement_and_packages ON requirement_atoms.requirement_and_package_id = requirement_and_packages.requirement_and_package_id WHERE requirement_and_packages.requirement_and_package_id IS NULL;");
      for($j = 0; $j < count($arr); $j++) //use '$j' for consistency
      {
        if($pack->execute)
          dbconnection::query("DELETE FROM requirement_atoms WHERE requirement_atom_id = '{$arr[$j]->requirement_atom_id}';");
        else //dry run
          echo "DELETE FROM requirement_atoms WHERE requirement_atom_id = '{$arr[$j]->requirement_atom_id}'; (game_id = '{$arr[$j]->game_id}')\n";
      }

      return new return_package(0);
    }

    public static function getFullGame($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $sql_game = dbconnection::queryObject("SELECT * FROM games WHERE game_id = '{$pack->game_id}' LIMIT 1");
        if(!$sql_game) return new return_package(2, NULL, "The game you've requested does not exist");

        $game = games::getGame($pack)->data;

        $game->authors = users::getUsersForGame($pack)->data; //pack already has auth and game_id

        //heres where we just hack the pack for use in other requests without overhead of creating new packs
        $pack->media_id = $game->media_id;
        $game->media = media::getMedia($pack)->data;
        $pack->media_id = $game->icon_media_id;
        $game->icon_media = media::getMedia($pack)->data;

        return new return_package(0,$game);
    }
}
?>
