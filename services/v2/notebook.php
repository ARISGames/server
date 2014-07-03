<?php
require_once("dbconnection.php");
require_once("return_package.php");

class notebook extends dbconnection
{
    //Takes in game JSON, all fields optional except user_id + key
    public static function createNote($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return notes::createNotePack($glob); }
    public static function createNotePack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth))
            return new return_package(6, NULL, "Failed Authentication");

        $noteId = dbconnection::queryInsert(
            "INSERT INTO notes (".
            "game_id,".
            "user_id,".
            (isset($pack->name)        ? "name,"        : "").
            (isset($pack->description) ? "description," : "").
            (isset($pack->label_id)    ? "label_id,"    : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            "'".addslashes($pack->user_id)."',".
            (isset($pack->name)        ? "'".addslashes($pack->name)."',"        : "").
            (isset($pack->description) ? "'".addslashes($pack->description)."'," : "").
            (isset($pack->label_id)    ? "'".addslashes($pack->label_id)."',"    : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        for($i = 0; is_array($pack->media) && $i < count($pack->media); $i++)
        {
            $mediaId = Media::createMediaFromJSON($pack->media[$i])->data->media_id;
            Notebook::addContentToNote($noteId,$mediaId,"MEDIA");
        }

        return notebook::getNote($noteId);
    }
}
?>
