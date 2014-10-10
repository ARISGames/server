<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("return_package.php");

class notes extends dbconnection
{
    public static function createNote($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return note::createNotePack($glob); }
    public static function createNotePack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        if($pack->media)
        {
            $pack->media->auth = $pack->auth;
            $pack->media->game_id = $pack->game_id;
            $pack->media_id = media::createMediaPack($pack->media)->data->media_id;
        }

        $pack->note_id = dbconnection::queryInsert(
            "INSERT INTO notes (".
            "game_id,".
            "user_id,".
            (isset($pack->name)        ? "name,"        : "").
            (isset($pack->description) ? "description," : "").
            (isset($pack->label_id)    ? "label_id,"    : "").
            (isset($pack->media_id)    ? "media_id,"    : "").
            "created".
            ") VALUES (".
            "'".$pack->game_id."',".
            "'".$pack->auth->user_id."',".
            (isset($pack->name)        ? "'".addslashes($pack->name)."',"        : "").
            (isset($pack->description) ? "'".addslashes($pack->description)."'," : "").
            (isset($pack->label_id)    ? "'".addslashes($pack->label_id)."',"    : "").
            (isset($pack->media_id)    ? "'".addslashes($pack->media_id)."',"    : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return notes::getNotePack($noteId);
    }

    public static function updateNote($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return notes::updateNotePack($glob); }
    public static function updateNotePack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM notes WHERE note_id = '{$pack->note_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE notes SET ".
            (isset($pack->name)             ? "name             = '".addslashes($pack->name)."', "             : "").
            (isset($pack->description)      ? "description      = '".addslashes($pack->description)."', "      : "").
            (isset($pack->icon_media_id)    ? "icon_media_id    = '".addslashes($pack->icon_media_id)."', "    : "").
            (isset($pack->media_id)         ? "media_id         = '".addslashes($pack->media_id)."', "         : "").
            (isset($pack->event_package_id) ? "event_package_id = '".addslashes($pack->event_package_id)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE note_id = '{$pack->note_id}'"
        );

        return notes::getNotePack($pack);
    }

    private static function noteObjectFromSQL($sql_note)
    {
        if(!$sql_note) return $sql_note;
        $note = new stdClass();
        $note->note_id        = $sql_note->note_id;
        $note->game_id          = $sql_note->game_id;
        $note->name             = $sql_note->name;
        $note->description      = $sql_note->description;
        $note->icon_media_id    = $sql_note->icon_media_id;
        $note->media_id         = $sql_note->media_id;
        $note->event_package_id = $sql_note->event_package_id;

        return $note;
    }

    public static function getNote($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return notes::getNotePack($glob); }
    public static function getNotePack($pack)
    {
        $sql_note = dbconnection::queryObject("SELECT * FROM notes WHERE note_id = '{$pack->note_id}' LIMIT 1");
        return new return_package(0,notes::noteObjectFromSQL($sql_note));
    }

    public static function getNotesForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return notes::getNotesForGamePack($glob); }
    public static function getNotesForGamePack($pack)
    {
        $sql_notes = dbconnection::queryArray("SELECT * FROM notes WHERE game_id = '{$pack->game_id}'");
        $notes = array();
        for($i = 0; $i < count($sql_notes); $i++)
            if($ob = notes::noteObjectFromSQL($sql_notes[$i])) $notes[] = $ob;
        
        return new return_package(0,$notes);
    }

    public static function deleteNote($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return notes::deleteNotePack($glob); }
    public static function deleteNotePack($pack)
    {
        $note = dbconnection::queryObject("SELECT * FROM notes WHERE note_id = '{$pack->note_id}'");
        $pack->auth->game_id = $note->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM notes WHERE note_id = '{$pack->note_id}' LIMIT 1");
        return new return_package(0);
    }
}
?>
