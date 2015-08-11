<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

require_once("tabs.php");
require_once("tags.php");
require_once("instances.php");
require_once("events.php");
require_once("requirements.php");

class dialogs extends dbconnection
{
    //Takes in dialog JSON, all fields optional except game_id + user_id + key
    public static function createDialog($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->dialog_id = dbconnection::queryInsert(
            "INSERT INTO dialogs (".
            "game_id,".
            (isset($pack->name)                   ? "name,"                   : "").
            (isset($pack->description)            ? "description,"            : "").
            (isset($pack->icon_media_id)          ? "icon_media_id,"          : "").
            (isset($pack->intro_dialog_script_id) ? "intro_dialog_script_id," : "").
            (isset($pack->back_button_enabled)    ? "back_button_enabled,"    : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->name)                   ? "'".addslashes($pack->name)."',"                   : "").
            (isset($pack->description)            ? "'".addslashes($pack->description)."',"            : "").
            (isset($pack->icon_media_id)          ? "'".addslashes($pack->icon_media_id)."',"          : "").
            (isset($pack->intro_dialog_script_id) ? "'".addslashes($pack->intro_dialog_script_id)."'," : "").
            (isset($pack->back_button_enabled)    ? "'".addslashes($pack->back_button_enabled)."',"    : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return dialogs::getDialog($pack);
    }

    //Takes in game JSON, all fields optional except dialog_id + user_id + key
    public static function updateDialog($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM dialogs WHERE dialog_id = '{$pack->dialog_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE dialogs SET ".
            (isset($pack->name)                   ? "name                   = '".addslashes($pack->name)."', "                   : "").
            (isset($pack->description)            ? "description            = '".addslashes($pack->description)."', "            : "").
            (isset($pack->icon_media_id)          ? "icon_media_id          = '".addslashes($pack->icon_media_id)."', "          : "").
            (isset($pack->intro_dialog_script_id) ? "intro_dialog_script_id = '".addslashes($pack->intro_dialog_script_id)."', " : "").
            (isset($pack->back_button_enabled)    ? "back_button_enabled    = '".addslashes($pack->back_button_enabled)."', "    : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE dialog_id = '{$pack->dialog_id}'"
        );

        return dialogs::getDialog($pack);
    }

    private static function dialogObjectFromSQL($sql_dialog)
    {
        if(!$sql_dialog) return $sql_dialog;
        $dialog = new stdClass();
        $dialog->dialog_id              = $sql_dialog->dialog_id;
        $dialog->game_id                = $sql_dialog->game_id;
        $dialog->name                   = $sql_dialog->name;
        $dialog->description            = $sql_dialog->description;
        $dialog->icon_media_id          = $sql_dialog->icon_media_id;
        $dialog->intro_dialog_script_id = $sql_dialog->intro_dialog_script_id;
        $dialog->back_button_enabled    = $sql_dialog->back_button_enabled;

        return $dialog;
    }

    public static function getDialog($pack)
    {
        $sql_dialog = dbconnection::queryObject("SELECT * FROM dialogs WHERE dialog_id = '{$pack->dialog_id}' LIMIT 1");
        return new return_package(0,dialogs::dialogObjectFromSQL($sql_dialog));
    }

    public static function getDialogsForGame($pack)
    {
        $sql_dialogs = dbconnection::queryArray("SELECT * FROM dialogs WHERE game_id = '{$pack->game_id}'");
        $dialogs = array();
        for($i = 0; $i < count($sql_dialogs); $i++)
            if($ob = dialogs::dialogObjectFromSQL($sql_dialogs[$i])) $dialogs[] = $ob;

        return new return_package(0,$dialogs);
    }

    public static function deleteDialog($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM dialogs WHERE dialog_id = '{$pack->dialog_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM dialogs WHERE dialog_id = '{$pack->dialog_id}' LIMIT 1");
        //cleanup
        $scripts = dbconnection::queryArray("SELECT * FROM dialog_scripts WHERE dialog_id = '{$pack->dialog_id}'");
        for($i = 0; $i < count($scripts); $i++)
        {
            $pack->dialog_script_id = $scripts[$i]->dialog_script_id;
            dialogs::deleteDialogScript($pack);
        }

        $options = dbconnection::queryArray("SELECT * FROM dialog_options WHERE dialog_id = '{$pack->dialog_id}'");
        for($i = 0; $i < count($options); $i++)
        {
            $pack->dialog_option_id = $options[$i]->dialog_option_id;
            dialogs::deleteDialogOption($pack);
        }
        $options = dbconnection::queryArray("SELECT * FROM dialog_options WHERE link_type = 'EXIT_TO_DIALOG' AND link_id = '{$pack->dialog_id}'");
        for($i = 0; $i < count($options); $i++)
        {
            $pack->dialog_option_id = $options[$i]->dialog_option_id;
            dialogs::deleteDialogOption($pack);
        }

        $tabs = dbconnection::queryArray("SELECT * FROM tabs WHERE type = 'DIALOG' AND content_id = '{$pack->dialog_id}'");
        for($i = 0; $i < count($tabs); $i++)
        {
            $pack->tab_id = $tabs[$i]->tab_id;
            tabs::deleteTab($pack);
        }

        $tags = dbconnection::queryArray("SELECT * FROM object_tags WHERE object_type = 'DIALOG' AND object_id = '{$pack->dialog_id}'");
        for($i = 0; $i < count($tags); $i++)
        {
            $pack->object_tag_id = $tags[$i]->object_tag_id;
            tags::deleteObjectTag($pack);
        }

        $instances = dbconnection::queryArray("SELECT * FROM instances WHERE object_type = 'DIALOG' AND object_id = '{$pack->dialog_id}'");
        for($i = 0; $i < count($instances); $i++)
        {
            $pack->instance_id = $instances[$i]->instance_id;
            instances::deleteInstance($pack);
        }

        $factories = dbconnection::queryArray("SELECT * FROM factories WHERE object_type = 'DIALOG' AND object_id = '{$pack->dialog_id}'");
        for($i = 0; $i < count($factories); $i++)
        {
            $pack->factory_id = $factories[$i]->factory_id;
            factories::deleteFactory($pack);
        }

        $reqAtoms = dbconnection::queryArray("SELECT * FROM requirement_atoms WHERE requirement = 'PLAYER_VIEWED_DIALOG' AND content_id = '{$pack->dialog_id}'");
        for($i = 0; $i < count($reqAtoms); $i++)
        {
            $pack->requirement_atom_id = $reqAtoms[$i]->requirement_atom_id;
            requirements::deleteRequirementAtom($pack);
        }

        return new return_package(0);
    }


    public static function createDialogCharacter($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->dialog_character_id = dbconnection::queryInsert(
            "INSERT INTO dialog_characters (".
            "game_id,".
            (isset($pack->title)     ? "title,"          : "").
            (isset($pack->name)      ? "name,"           : "").
            (isset($pack->media_id)  ? "media_id,"  : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->title)     ? "'".addslashes($pack->title)."',"     : "").
            (isset($pack->name)      ? "'".addslashes($pack->name)."',"      : "").
            (isset($pack->media_id)  ? "'".addslashes($pack->media_id)."',"  : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return dialogs::getDialogCharacter($pack);
    }

    //Takes in game JSON, all fields optional except dialog_id + user_id + key
    public static function updateDialogCharacter($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM dialog_characters WHERE dialog_character_id = '{$pack->dialog_character_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE dialog_characters SET ".
            (isset($pack->title)     ? "title    = '".addslashes($pack->title)."', "     : "").
            (isset($pack->name)      ? "name     = '".addslashes($pack->name)."', "     : "").
            (isset($pack->media_id)  ? "media_id = '".addslashes($pack->media_id)."', "  : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE dialog_character_id = '{$pack->dialog_character_id}'"
        );

        return dialogs::getDialogCharacter($pack);
    }

    private static function dialogCharacterObjectFromSQL($sql_dialogCharacter)
    {
        if(!$sql_dialogCharacter) return $sql_dialogCharacter;
        $dialogCharacter = new stdClass();
        $dialogCharacter->dialog_character_id = $sql_dialogCharacter->dialog_character_id;
        $dialogCharacter->game_id             = $sql_dialogCharacter->game_id;
        $dialogCharacter->title               = $sql_dialogCharacter->title;
        $dialogCharacter->name                = $sql_dialogCharacter->name;
        $dialogCharacter->media_id            = $sql_dialogCharacter->media_id;

        return $dialogCharacter;
    }

    public static function getDialogCharacter($pack)
    {
        $sql_dialogCharacter = dbconnection::queryObject("SELECT * FROM dialog_characters WHERE dialog_character_id = '{$pack->dialog_character_id}' LIMIT 1");
        return new return_package(0,dialogs::dialogCharacterObjectFromSQL($sql_dialogCharacter));
    }

    public static function getDialogCharactersForGame($pack)
    {
        $sql_dialogCharacters = dbconnection::queryArray("SELECT * FROM dialog_characters WHERE game_id = '{$pack->game_id}'");
        $dialogCharacters = array();
        for($i = 0; $i < count($sql_dialogCharacters); $i++)
            if($ob = dialogs::dialogCharacterObjectFromSQL($sql_dialogCharacters[$i])) $dialogCharacters[] = $ob;

        return new return_package(0,$dialogCharacters);
    }

    public static function deleteDialogCharacter($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM dialog_characters WHERE dialog_character_id = '{$pack->dialog_character_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM dialog_characters WHERE dialog_character_id = '{$pack->dialog_character_id}' LIMIT 1");
        //cleanup
        dbconnection::query("UPDATE dialog_scripts SET dialog_character_id = 0 WHERE dialog_character_id = '{$pack->dialog_character_id}'");
        return new return_package(0);
    }


    public static function createDialogScript($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->dialog_script_id = dbconnection::queryInsert(
            "INSERT INTO dialog_scripts (".
            "game_id,".
            (isset($pack->dialog_id)           ? "dialog_id,"           : "").
            (isset($pack->dialog_character_id) ? "dialog_character_id," : "").
            (isset($pack->text)                ? "text,"                : "").
            (isset($pack->event_package_id)    ? "event_package_id,"    : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->dialog_id)             ? "'".addslashes($pack->dialog_id)."',"           : "").
            (isset($pack->dialog_character_id)   ? "'".addslashes($pack->dialog_character_id)."'," : "").
            (isset($pack->text)                  ? "'".addslashes($pack->text)."',"                : "").
            (isset($pack->event_package_id)      ? "'".addslashes($pack->event_package_id)."',"    : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return dialogs::getDialogScript($pack);
    }

    //Takes in game JSON, all fields optional except dialog_id + user_id + key
    public static function updateDialogScript($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM dialog_scripts WHERE dialog_script_id = '{$pack->dialog_script_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE dialog_scripts SET ".
            (isset($pack->dialog_character_id) ? "dialog_character_id = '".addslashes($pack->dialog_character_id)."', " : "").
            (isset($pack->text)                ? "text                = '".addslashes($pack->text)."', "                : "").
            (isset($pack->event_package_id)    ? "event_package_id    = '".addslashes($pack->event_package_id)."', "    : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE dialog_script_id = '{$pack->dialog_script_id}'"
        );

        return dialogs::getDialogScript($pack);
    }

    private static function dialogScriptObjectFromSQL($sql_dialogScript)
    {
        if(!$sql_dialogScript) return $sql_dialogScript;
        $dialogScript = new stdClass();
        $dialogScript->dialog_script_id    = $sql_dialogScript->dialog_script_id;
        $dialogScript->game_id             = $sql_dialogScript->game_id;
        $dialogScript->dialog_id           = $sql_dialogScript->dialog_id;
        $dialogScript->dialog_character_id = $sql_dialogScript->dialog_character_id;
        $dialogScript->text                = $sql_dialogScript->text;
        $dialogScript->event_package_id    = $sql_dialogScript->event_package_id;

        return $dialogScript;
    }

    public static function getDialogScript($pack)
    {
        $sql_dialogScript = dbconnection::queryObject("SELECT * FROM dialog_scripts WHERE dialog_script_id = '{$pack->dialog_script_id}' LIMIT 1");
        return new return_package(0,dialogs::dialogScriptObjectFromSQL($sql_dialogScript));
    }

    public static function getDialogScriptsForGame($pack)
    {
        $sql_dialogScripts = dbconnection::queryArray("SELECT * FROM dialog_scripts WHERE game_id = '{$pack->game_id}'");
        $dialogScripts = array();
        for($i = 0; $i < count($sql_dialogScripts); $i++)
            if($ob = dialogs::dialogScriptObjectFromSQL($sql_dialogScripts[$i])) $dialogScripts[] = $ob;

        return new return_package(0,$dialogScripts);
    }

    public static function getDialogScriptsForDialog($pack)
    {
        $sql_dialogScripts = dbconnection::queryArray("SELECT * FROM dialog_scripts WHERE dialog_id = '{$pack->dialog_id}'");
        $dialogScripts = array();
        for($i = 0; $i < count($sql_dialogScripts); $i++)
            if($ob = dialogs::dialogScriptObjectFromSQL($sql_dialogScripts[$i])) $dialogScripts[] = $ob;

        return new return_package(0,$dialogScripts);
    }

    public static function deleteDialogScript($pack)
    {
        $script = dbconnection::queryObject("SELECT * FROM dialog_scripts WHERE dialog_script_id = '{$pack->dialog_script_id}'");
        $pack->auth->game_id = $script->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM dialog_scripts WHERE dialog_script_id = '{$pack->dialog_script_id}' LIMIT 1");
        //cleanup
        dbconnection::query("UPDATE dialogs SET intro_dialog_script_id = 0 WHERE dialog_id = '{$script->dialog_id}' AND intro_dialog_script_id = '{$script->dialog_script_id}'");

        /* Comment out until we've decided on desired behavior...
        $eventpack = dbconnection::queryObject("SELECT * FROM event_packages WHERE event_package_id = '{$script->event_package_id}'");
        if($eventpack)
        {
            $pack->event_package_id = $eventpack->event_package_id;
            events::deleteEventPackage($pack);
        }
        */

        $options = dbconnection::queryArray("SELECT * FROM dialog_options WHERE parent_dialog_script_id = '{$script->dialog_script_id}'");
        for($i = 0; $i < count($options); $i++)
        {
            $pack->dialog_option_id = $options[$i]->dialog_option_id;
            dialogs::deleteDialogOption($pack);
        }
        $options = dbconnection::queryArray("SELECT * FROM dialog_options WHERE link_type = 'DIALOG_SCRIPT' AND link_id = '{$script->dialog_script_id}'");
        for($i = 0; $i < count($options); $i++)
        {
            $pack->dialog_option_id = $options[$i]->dialog_option_id;
            dialogs::deleteDialogOption($pack);
        }

        $reqAtoms = dbconnection::queryArray("SELECT * FROM requirement_atoms WHERE requirement = 'PLAYER_VIEWED_DIALOG_SCRIPT' AND content_id = '{$pack->dialog_script_id}'");
        for($i = 0; $i < count($reqAtoms); $i++)
        {
            $pack->requirement_atom_id = $reqAtoms[$i]->requirement_atom_id;
            requirements::deleteRequirementAtom($pack);
        }

        return new return_package(0);
    }


    public static function createDialogOption($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->dialog_option_id = dbconnection::queryInsert(
            "INSERT INTO dialog_options (".
            "game_id,".
            (isset($pack->dialog_id)                   ? "dialog_id,"                   : "").
            (isset($pack->parent_dialog_script_id)     ? "parent_dialog_script_id,"     : "").
            (isset($pack->prompt)                      ? "prompt,"                      : "").
            (isset($pack->link_type)                   ? "link_type,"                   : "").
            (isset($pack->link_id)                     ? "link_id,"                     : "").
            (isset($pack->link_info)                   ? "link_info,"                   : "").
            (isset($pack->requirement_root_package_id) ? "requirement_root_package_id," : "").
            (isset($pack->sort_index)                  ? "sort_index,"                  : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->dialog_id)                   ? "'".addslashes($pack->dialog_id)."',"                   : "").
            (isset($pack->parent_dialog_script_id)     ? "'".addslashes($pack->parent_dialog_script_id)."',"     : "").
            (isset($pack->prompt)                      ? "'".addslashes($pack->prompt)."',"                      : "").
            (isset($pack->link_type)                   ? "'".addslashes($pack->link_type)."',"                   : "").
            (isset($pack->link_id)                     ? "'".addslashes($pack->link_id)."',"                     : "").
            (isset($pack->link_info)                   ? "'".addslashes($pack->link_info)."',"                   : "").
            (isset($pack->requirement_root_package_id) ? "'".addslashes($pack->requirement_root_package_id)."'," : "").
            (isset($pack->sort_index)                  ? "'".addslashes($pack->sort_index)."',"                  : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return dialogs::getDialogOption($pack);
    }

    //Takes in game JSON, all fields optional except dialog_id + user_id + key
    public static function updateDialogOption($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM dialog_options WHERE dialog_option_id = '{$pack->dialog_option_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE dialog_options SET ".
            (isset($pack->parent_dialog_script_id)     ? "parent_dialog_script_id     = '".addslashes($pack->parent_dialog_script_id)."', "     : "").
            (isset($pack->prompt)                      ? "prompt                      = '".addslashes($pack->prompt)."', "                      : "").
            (isset($pack->link_type)                   ? "link_type                   = '".addslashes($pack->link_type)."', "                   : "").
            (isset($pack->link_id)                     ? "link_id                     = '".addslashes($pack->link_id)."', "                     : "").
            (isset($pack->link_info)                   ? "link_info                   = '".addslashes($pack->link_info)."', "                   : "").
            (isset($pack->requirement_root_package_id) ? "requirement_root_package_id = '".addslashes($pack->requirement_root_package_id)."', " : "").
            (isset($pack->sort_index)                  ? "sort_index                  = '".addslashes($pack->sort_index)."', "                  : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE dialog_option_id = '{$pack->dialog_option_id}'"
        );

        return dialogs::getDialogOption($pack);
    }

    private static function dialogOptionObjectFromSQL($sql_dialogOption)
    {
        if(!$sql_dialogOption) return $sql_dialogOption;
        $dialogOption = new stdClass();
        $dialogOption->dialog_option_id            = $sql_dialogOption->dialog_option_id;
        $dialogOption->game_id                     = $sql_dialogOption->game_id;
        $dialogOption->dialog_id                   = $sql_dialogOption->dialog_id;
        $dialogOption->parent_dialog_script_id     = $sql_dialogOption->parent_dialog_script_id;
        $dialogOption->prompt                      = $sql_dialogOption->prompt;
        $dialogOption->link_type                   = $sql_dialogOption->link_type;
        $dialogOption->link_id                     = $sql_dialogOption->link_id;
        $dialogOption->link_info                   = $sql_dialogOption->link_info;
        $dialogOption->requirement_root_package_id = $sql_dialogOption->requirement_root_package_id;
        $dialogOption->sort_index                  = $sql_dialogOption->sort_index;

        return $dialogOption;
    }

    public static function getDialogOption($pack)
    {
        $sql_dialogOption = dbconnection::queryObject("SELECT * FROM dialog_options WHERE dialog_option_id = '{$pack->dialog_option_id}' LIMIT 1");
        return new return_package(0,dialogs::dialogOptionObjectFromSQL($sql_dialogOption));
    }

    public static function getDialogOptionsForGame($pack)
    {
        $sql_dialogOptions = dbconnection::queryArray("SELECT * FROM dialog_options WHERE game_id = '{$pack->game_id}'");
        $dialogOptions = array();
        for($i = 0; $i < count($sql_dialogOptions); $i++)
            if($ob = dialogs::dialogOptionObjectFromSQL($sql_dialogOptions[$i])) $dialogOptions[] = $ob;

        return new return_package(0,$dialogOptions);
    }

    public static function getDialogOptionsForDialog($pack)
    {
        $sql_dialogOptions = dbconnection::queryArray("SELECT * FROM dialog_options WHERE dialog_id = '{$pack->dialog_id}'");
        $dialogOptions = array();
        for($i = 0; $i < count($sql_dialogOptions); $i++)
            if($ob = dialogs::dialogOptionObjectFromSQL($sql_dialogOptions[$i])) $dialogOptions[] = $ob;

        return new return_package(0,$dialogOptions);
    }

    public static function getDialogOptionsForScript($pack)
    {
        $sql_dialogOptions = dbconnection::queryArray("SELECT * FROM dialog_options WHERE dialog_id = '{$pack->dialog_id}' AND parent_dialog_script_id = '{$pack->dialog_script_id}'");
        $dialogOptions = array();
        for($i = 0; $i < count($sql_dialogOptions); $i++)
            if($ob = dialogs::dialogOptionObjectFromSQL($sql_dialogOptions[$i])) $dialogOptions[] = $ob;

        return new return_package(0,$dialogOptions);
    }

    public static function deleteDialogOption($pack)
    {
        $option = dbconnection::queryObject("SELECT * FROM dialog_options WHERE dialog_option_id = '{$pack->dialog_option_id}'");
        $pack->auth->game_id = $option->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM dialog_options WHERE dialog_option_id = '{$pack->dialog_option_id}' LIMIT 1");
        //cleanup
        $reqPack = dbconnection::queryObject("SELECT * FROM requirement_root_packages WHERE requirement_root_package_id = '{$option->requirement_root_package_id}'");
        if($reqPack)
        {
            $pack->requirement_root_package_id = $reqPack->requirement_root_package_id;
            requirements::deleteRequirementPackage($pack);
        }

        return new return_package(0);
    }

}
?>
