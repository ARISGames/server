<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("games.php");
require_once("return_package.php");

require_once("events.php");
require_once("dialogs.php");
require_once("tabs.php");
require_once("tags.php");
require_once("instances.php");
require_once("factories.php");
require_once("requirements.php");

class plaques extends dbconnection
{
    //Takes in plaque JSON, all fields optional except game_id + user_id + key
    public static function createPlaque($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->plaque_id = dbconnection::queryInsert(
            "INSERT INTO plaques (".
            "game_id,".
            (isset($pack->name)                ? "name,"                : "").
            (isset($pack->description)         ? "description,"         : "").
            (isset($pack->icon_media_id)       ? "icon_media_id,"       : "").
            (isset($pack->media_id)            ? "media_id,"            : "").
            (isset($pack->event_package_id)    ? "event_package_id,"    : "").
            (isset($pack->back_button_enabled) ? "back_button_enabled," : "").
            (isset($pack->continue_function)   ? "continue_function,"   : "").
            (isset($pack->full_screen)         ? "full_screen,"         : "").
            (isset($pack->quest_id)            ? "quest_id,"            : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->name)                ? "'".addslashes($pack->name)."',"                : "").
            (isset($pack->description)         ? "'".addslashes($pack->description)."',"         : "").
            (isset($pack->icon_media_id)       ? "'".addslashes($pack->icon_media_id)."',"       : "").
            (isset($pack->media_id)            ? "'".addslashes($pack->media_id)."',"            : "").
            (isset($pack->event_package_id)    ? "'".addslashes($pack->event_package_id)."',"    : "").
            (isset($pack->back_button_enabled) ? "'".addslashes($pack->back_button_enabled)."'," : "").
            (isset($pack->continue_function)   ? "'".addslashes($pack->continue_function)."',"   : "").
            (isset($pack->full_screen)         ? "'".addslashes($pack->full_screen)."',"         : "").
            (isset($pack->quest_id)            ? "'".addslashes($pack->quest_id)."',"            : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        games::bumpGameVersion($pack);
        return plaques::getPlaque($pack);
    }

    //Takes in game JSON, all fields optional except plaque_id + user_id + key
    public static function updatePlaque($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM plaques WHERE plaque_id = '{$pack->plaque_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE plaques SET ".
            (isset($pack->name)                ? "name                = '".addslashes($pack->name)."', "                : "").
            (isset($pack->description)         ? "description         = '".addslashes($pack->description)."', "         : "").
            (isset($pack->icon_media_id)       ? "icon_media_id       = '".addslashes($pack->icon_media_id)."', "       : "").
            (isset($pack->media_id)            ? "media_id            = '".addslashes($pack->media_id)."', "            : "").
            (isset($pack->event_package_id)    ? "event_package_id    = '".addslashes($pack->event_package_id)."', "    : "").
            (isset($pack->back_button_enabled) ? "back_button_enabled = '".addslashes($pack->back_button_enabled)."', " : "").
            (isset($pack->continue_function)   ? "continue_function   = '".addslashes($pack->continue_function)."', "   : "").
            (isset($pack->full_screen)         ? "full_screen         = '".addslashes($pack->full_screen)."', "         : "").
            (isset($pack->quest_id)            ? "quest_id            = '".addslashes($pack->quest_id)."', "            : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE plaque_id = '{$pack->plaque_id}'"
        );

        games::bumpGameVersion($pack);
        return plaques::getPlaque($pack);
    }

    private static function plaqueObjectFromSQL($sql_plaque)
    {
        if(!$sql_plaque) return $sql_plaque;
        $plaque = new stdClass();
        $plaque->plaque_id           = $sql_plaque->plaque_id;
        $plaque->game_id             = $sql_plaque->game_id;
        $plaque->name                = $sql_plaque->name;
        $plaque->description         = $sql_plaque->description;
        $plaque->icon_media_id       = $sql_plaque->icon_media_id;
        $plaque->media_id            = $sql_plaque->media_id;
        $plaque->media_id_2          = $sql_plaque->media_id_2;
        $plaque->media_id_3          = $sql_plaque->media_id_3;
        $plaque->event_package_id    = $sql_plaque->event_package_id;
        $plaque->back_button_enabled = $sql_plaque->back_button_enabled;
        $plaque->continue_function   = $sql_plaque->continue_function;
        $plaque->full_screen         = $sql_plaque->full_screen;
        $plaque->quest_id            = $sql_plaque->quest_id;

        return $plaque;
    }

    public static function getPlaque($pack)
    {
        $sql_plaque = dbconnection::queryObject("SELECT * FROM plaques WHERE plaque_id = '{$pack->plaque_id}' LIMIT 1");
        return new return_package(0,plaques::plaqueObjectFromSQL($sql_plaque));
    }

    public static function getPlaquesForGame($pack)
    {
        $sql_plaques = dbconnection::queryArray("SELECT * FROM plaques WHERE game_id = '{$pack->game_id}'");
        $plaques = array();
        for($i = 0; $i < count($sql_plaques); $i++)
            if($ob = plaques::plaqueObjectFromSQL($sql_plaques[$i])) $plaques[] = $ob;

        return new return_package(0,$plaques);
    }

    public static function deletePlaque($pack)
    {
        $plaque = dbconnection::queryObject("SELECT * FROM plaques WHERE plaque_id = '{$pack->plaque_id}'");
        $pack->auth->game_id = $plaque->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM plaques WHERE plaque_id = '{$pack->plaque_id}' LIMIT 1");

        //cleanup
        /* Comment out until we've decided on desired behavior...
        $eventpack = dbconnection::queryObject("SELECT * FROM event_packages WHERE event_package_id = '{$plaque->event_package_id}'");
        if($eventpack)
        {
            $pack->event_package_id = $eventpack->event_package_id;
            events::deleteEventPackage($pack);
        }
        */

        $options = dbconnection::queryArray("SELECT * FROM dialog_options WHERE link_type = 'EXIT_TO_PLAQUE' AND link_id = '{$pack->plaque_id}'");
        for($i = 0; $i < count($options); $i++)
        {
            $pack->dialog_option_id = $options[$i]->dialog_option_id;
            dialogs::deleteDialogOption($pack);
        }

        $tabs = dbconnection::queryArray("SELECT * FROM tabs WHERE type = 'PLAQUE' AND content_id = '{$pack->plaque_id}'");
        for($i = 0; $i < count($tabs); $i++)
        {
            $pack->tab_id = $tabs[$i]->tab_id;
            tabs::deleteTab($pack);
        }

        $tags = dbconnection::queryArray("SELECT * FROM object_tags WHERE object_type = 'PLAQUE' AND object_id = '{$pack->plaque_id}'");
        for($i = 0; $i < count($tags); $i++)
        {
            $pack->object_tag_id = $tags[$i]->object_tag_id;
            tags::deleteObjectTag($pack);
        }

        $instances = dbconnection::queryArray("SELECT * FROM instances WHERE object_type = 'PLAQUE' AND object_id = '{$pack->plaque_id}'");
        for($i = 0; $i < count($instances); $i++)
        {
            $pack->instance_id = $instances[$i]->instance_id;
            instances::deleteInstance($pack);
        }

        $factories = dbconnection::queryArray("SELECT * FROM factories WHERE object_type = 'PLAQUE' AND object_id = '{$pack->plaque_id}'");
        for($i = 0; $i < count($factories); $i++)
        {
            $pack->factory_id = $factories[$i]->factory_id;
            factories::deleteFactory($pack);
        }

        $reqAtoms = dbconnection::queryArray("SELECT * FROM requirement_atoms WHERE requirement = 'PLAYER_VIEWED_PLAQUE' AND content_id = '{$pack->plaque_id}'");
        for($i = 0; $i < count($reqAtoms); $i++)
        {
            $pack->requirement_atom_id = $reqAtoms[$i]->requirement_atom_id;
            requirements::deleteRequirementAtom($pack);
        }

        games::bumpGameVersion($pack);
        return new return_package(0);
    }
}
?>
