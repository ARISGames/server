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
            ($pack->name              ? "name,"              : "").
            ($pack->description       ? "description,"       : "").
            ($pack->icon_media_id     ? "icon_media_id,"     : "").
            ($pack->media_id          ? "media_id,"          : "").
            ($pack->opening_script_id ? "opening_script_id," : "").
            ($pack->closing_script_id ? "closing_script_id," : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            ($pack->name              ? "'".addslashes($pack->name)."',"              : "").
            ($pack->description       ? "'".addslashes($pack->description)."',"       : "").
            ($pack->icon_media_id     ? "'".addslashes($pack->icon_media_id)."',"     : "").
            ($pack->media_id          ? "'".addslashes($pack->media_id)."',"          : "").
            ($pack->opening_script_id ? "'".addslashes($pack->opening_script_id)."'," : "").
            ($pack->closing_script_id ? "'".addslashes($pack->closing_script_id)."'," : "").
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
            ($pack->name              ? "name              = '".addslashes($pack->name)."', "              : "").
            ($pack->description       ? "description       = '".addslashes($pack->description)."', "       : "").
            ($pack->icon_media_id     ? "icon_media_id     = '".addslashes($pack->icon_media_id)."', "     : "").
            ($pack->media_id          ? "media_id          = '".addslashes($pack->media_id)."', "          : "").
            ($pack->opening_script_id ? "opening_script_id = '".addslashes($pack->opening_script_id)."', " : "").
            ($pack->closing_script_id ? "closing_script_id = '".addslashes($pack->closing_script_id)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE dialog_id = '{$pack->dialog_id}'"
        );

        return dialogs::getDialogPack($pack);
    }

    private static function dialogObjectFromSQL($sql_dialog)
    {
        $dialog = new stdClass();
        $dialog->dialog_id            = $sql_dialog->dialog_id;
        $dialog->game_id           = $sql_dialog->game_id;
        $dialog->name              = $sql_dialog->name;
        $dialog->description       = $sql_dialog->description;
        $dialog->icon_media_id     = $sql_dialog->icon_media_id;
        $dialog->media_id          = $sql_dialog->media_id;
        $dialog->opening_script_id = $sql_dialog->opening_script_id;
        $dialog->closing_script_id = $sql_dialog->closing_script_id;

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
            $dialogs[] = dialogs::dialogObjectFromSQL($sql_dialogs[$i]);

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
}
?>
