<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

class dialogs extends dbconnection
{
    //Takes in dialog JSON, all fields optional except game_id + user_id + key
    public static function createDialog($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return dialogs::createDialogPack($glob); }
    public static function createDialogPack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->dialog_id = dbconnection::queryInsert(
            "INSERT INTO dialogs (".
            "game_id,".
            (isset($pack->name)           ? "name,"           : "").
            (isset($pack->description)    ? "description,"    : "").
            (isset($pack->icon_media_id)  ? "icon_media_id,"  : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->name)           ? "'".addslashes($pack->name)."',"           : "").
            (isset($pack->description)    ? "'".addslashes($pack->description)."',"    : "").
            (isset($pack->icon_media_id)  ? "'".addslashes($pack->icon_media_id)."',"  : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return dialogs::getDialogPack($pack);
    }

    //Takes in game JSON, all fields optional except dialog_id + user_id + key
    public static function updateDialog($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return dialogs::updateDialogPack($glob); }
    public static function updateDialogPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM dialogs WHERE dialog_id = '{$pack->dialog_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE dialogs SET ".
            (isset($pack->name)           ? "name           = '".addslashes($pack->name)."', "           : "").
            (isset($pack->description)    ? "description    = '".addslashes($pack->description)."', "    : "").
            (isset($pack->icon_media_id)  ? "icon_media_id  = '".addslashes($pack->icon_media_id)."', "  : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE dialog_id = '{$pack->dialog_id}'"
        );

        return dialogs::getDialogPack($pack);
    }

    private static function dialogObjectFromSQL($sql_dialog)
    {
        if(!$sql_dialog) return $sql_dialog;
        $dialog = new stdClass();
        $dialog->dialog_id      = $sql_dialog->dialog_id;
        $dialog->game_id        = $sql_dialog->game_id;
        $dialog->name           = $sql_dialog->name;
        $dialog->description    = $sql_dialog->description;
        $dialog->icon_media_id  = $sql_dialog->icon_media_id;

        return $dialog;
    }

    public static function getDialog($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return dialogs::getDialogPack($glob); }
    public static function getDialogPack($pack)
    {
        $sql_dialog = dbconnection::queryObject("SELECT * FROM dialogs WHERE dialog_id = '{$pack->dialog_id}' LIMIT 1");
        return new return_package(0,dialogs::dialogObjectFromSQL($sql_dialog));
    }

    public static function getDialogsForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return dialogs::getDialogsForGamePack($glob); }
    public static function getDialogsForGamePack($pack)
    {
        $sql_dialogs = dbconnection::queryArray("SELECT * FROM dialogs WHERE game_id = '{$pack->game_id}'");
        $dialogs = array();
        for($i = 0; $i < count($sql_dialogs); $i++)
            if($ob = dialogs::dialogObjectFromSQL($sql_dialogs[$i])) $dialogs[] = $ob;

        return new return_package(0,$dialogs);
    }

    public static function deleteDialog($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return dialogs::deleteDialogPack($glob); }
    public static function deleteDialogPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM dialogs WHERE dialog_id = '{$pack->dialog_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM dialogs WHERE dialog_id = '{$pack->dialog_id}' LIMIT 1");
        return new return_package(0);
    }


    public static function createDialogCharacter($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return dialogs::createDialogCharacterPack($glob); }
    public static function createDialogCharacterPack($pack)
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

        return dialogs::getDialogCharacterPack($pack);
    }

    //Takes in game JSON, all fields optional except dialog_id + user_id + key
    public static function updateDialogCharacter($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return dialogs::updateDialogCharacterPack($glob); }
    public static function updateDialogCharacterPack($pack)
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

        return dialogs::getDialogCharacterPack($pack);
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

    public static function getDialogCharacter($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return dialogs::getDialogCharacterPack($glob); }
    public static function getDialogCharacterPack($pack)
    {
        $sql_dialogCharacter = dbconnection::queryObject("SELECT * FROM dialog_characters WHERE dialog_character_id = '{$pack->dialog_character_id}' LIMIT 1");
        return new return_package(0,dialogs::dialogCharacterObjectFromSQL($sql_dialogCharacter));
    }

    public static function getDialogCharactersForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return dialogs::getDialogCharactersForGamePack($glob); }
    public static function getDialogCharactersForGamePack($pack)
    {
        $sql_dialogCharacters = dbconnection::queryArray("SELECT * FROM dialog_characters WHERE game_id = '{$pack->game_id}'");
        $dialogCharacters = array();
        for($i = 0; $i < count($sql_dialogCharacters); $i++)
            if($ob = dialogs::dialogCharacterObjectFromSQL($sql_dialogCharacters[$i])) $dialogCharacters[] = $ob;

        return new return_package(0,$dialogCharacters);
    }

    public static function deleteDialogCharacter($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return dialogs::deleteDialogCharacterPack($glob); }
    public static function deleteDialogCharacterPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM dialog_characters WHERE dialog_character_id = '{$pack->dialog_character_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM dialog_characters WHERE dialog_character_id = '{$pack->dialog_character_id}' LIMIT 1");
        return new return_package(0);
    }


    public static function createDialogScript($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return dialogs::createDialogScriptPack($glob); }
    public static function createDialogScriptPack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->dialog_script_id = dbconnection::queryInsert(
            "INSERT INTO dialog_scripts (".
            "game_id,".
            (isset($pack->dialog_id)                   ? "dialog_id,"                   : "").
            (isset($pack->parent_dialog_script_id)     ? "parent_dialog_script_id,"     : "").
            (isset($pack->dialog_character_id)         ? "dialog_character_id,"         : "").
            (isset($pack->requirement_root_package_id) ? "requirement_root_package_id," : "").
            (isset($pack->prompt)                      ? "prompt,"                      : "").
            (isset($pack->text)                        ? "text,"                        : "").
            (isset($pack->sort_index)                  ? "sort_index,"                  : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->dialog_id)                   ? "'".addslashes($pack->dialog_id)."',"                   : "").
            (isset($pack->parent_dialog_script_id)     ? "'".addslashes($pack->parent_dialog_script_id)."',"     : "").
            (isset($pack->dialog_character_id)         ? "'".addslashes($pack->dialog_character_id)."',"         : "").
            (isset($pack->requirement_root_package_id) ? "'".addslashes($pack->requirement_root_package_id)."'," : "").
            (isset($pack->prompt)                      ? "'".addslashes($pack->prompt)."',"                      : "").
            (isset($pack->text)                        ? "'".addslashes($pack->text)."',"                        : "").
            (isset($pack->sort_index)                  ? "'".addslashes($pack->sort_index)."',"                  : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return dialogs::getDialogScriptPack($pack);
    }

    //Takes in game JSON, all fields optional except dialog_id + user_id + key
    public static function updateDialogScript($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return dialogs::updateDialogScriptPack($glob); }
    public static function updateDialogScriptPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM dialog_scripts WHERE dialog_script_id = '{$pack->dialog_script_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE dialog_scripts SET ".
            (isset($pack->parent_dialog_script_id)     ? "parent_dialog_script_id     = '".addslashes($pack->parent_dialog_script_id)."', "     : "").
            (isset($pack->dialog_character_id)         ? "dialog_character_id         = '".addslashes($pack->dialog_character_id)."', "         : "").
            (isset($pack->requirement_root_package_id) ? "requirement_root_package_id = '".addslashes($pack->requirement_root_package_id)."', " : "").
            (isset($pack->prompt)                      ? "prompt                      = '".addslashes($pack->prompt)."', "                      : "").
            (isset($pack->text)                        ? "text                        = '".addslashes($pack->text)."', "                        : "").
            (isset($pack->sort_index)                  ? "sort_index                  = '".addslashes($pack->sort_index)."', "                  : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE dialog_script_id = '{$pack->dialog_script_id}'"
        );

        return dialogs::getDialogScriptPack($pack);
    }

    private static function dialogScriptObjectFromSQL($sql_dialogScript)
    {
        if(!$sql_dialogScript) return $sql_dialogScript;
        $dialogScript = new stdClass();
        $dialogScript->dialog_script_id            = $sql_dialogScript->dialog_script_id;
        $dialogScript->game_id                     = $sql_dialogScript->game_id;
        $dialogScript->dialog_id                   = $sql_dialogScript->dialog_id;
        $dialogScript->parent_dialog_script_id     = $sql_dialogScript->parent_dialog_script_id;
        $dialogScript->dialog_character_id         = $sql_dialogScript->dialog_character_id;
        $dialogScript->requirement_root_package_id = $sql_dialogScript->requirement_root_package_id;
        $dialogScript->prompt                      = $sql_dialogScript->prompt;
        $dialogScript->text                        = $sql_dialogScript->text;
        $dialogScript->sort_index                  = $sql_dialogScript->sort_index;

        return $dialogScript;
    }

    public static function getDialogScript($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return dialogs::getDialogScriptPack($glob); }
    public static function getDialogScriptPack($pack)
    {
        $sql_dialogScript = dbconnection::queryObject("SELECT * FROM dialog_scripts WHERE dialog_script_id = '{$pack->dialog_script_id}' LIMIT 1");
        return new return_package(0,dialogs::dialogScriptObjectFromSQL($sql_dialogScript));
    }

    public static function getDialogScriptsForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return dialogs::getDialogScriptsForGamePack($glob); }
    public static function getDialogScriptsForGamePack($pack)
    {
        $sql_dialogScripts = dbconnection::queryArray("SELECT * FROM dialog_scripts WHERE game_id = '{$pack->game_id}'");
        $dialogScripts = array();
        for($i = 0; $i < count($sql_dialogScripts); $i++)
            if($ob = dialogs::dialogScriptObjectFromSQL($sql_dialogScripts[$i])) $dialogScripts[] = $ob;

        return new return_package(0,$dialogScripts);
    }

    public static function getDialogScriptsForDialog($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return dialogs::getDialogScriptsForDialogPack($glob); }
    public static function getDialogScriptsForDialogPack($pack)
    {
        $sql_dialogScripts = dbconnection::queryArray("SELECT * FROM dialog_scripts WHERE dialog_id = '{$pack->dialog_id}'");
        $dialogScripts = array();
        for($i = 0; $i < count($sql_dialogScripts); $i++)
            if($ob = dialogs::dialogScriptObjectFromSQL($sql_dialogScripts[$i])) $dialogScripts[] = $ob;

        return new return_package(0,$dialogScripts);
    }

    public static function deleteDialogScript($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return dialogs::deleteDialogScriptPack($glob); }
    public static function deleteDialogScriptPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM dialog_scripts WHERE dialog_script_id = '{$pack->dialog_script_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM dialog_scripts WHERE dialog_script_id = '{$pack->dialog_script_id}' LIMIT 1");
        return new return_package(0);
    }

}
?>
