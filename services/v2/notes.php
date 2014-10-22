<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("return_package.php");

require_once("media.php");

class notes extends dbconnection
{
    public static function createNote($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return notes::createNotePack($glob); }
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
            (isset($pack->media_id)    ? "media_id,"    : "").
            "created".
            ") VALUES (".
            "'".$pack->game_id."',".
            "'".$pack->auth->user_id."',".
            (isset($pack->name)        ? "'".addslashes($pack->name)."',"        : "").
            (isset($pack->description) ? "'".addslashes($pack->description)."'," : "").
            (isset($pack->media_id)    ? "'".addslashes($pack->media_id)."',"    : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        //allow for 'tag_id' in API, but really just use object_tags
        if($pack->tag_id) dbconnection::queryInsert("INSERT INTO object_tags (game_id, object_type, object_id, tag_id, created) VALUES ('{$pack->game_id}', 'NOTE', '{$pack->note_id}', '{$pack->tag_id}', CURRENT_TIMESTAMP)");

        return notes::getNotePack($pack);
    }

    public static function updateNote($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return notes::updateNotePack($glob); }
    public static function updateNotePack($pack)
    {
        $pack->auth->permission = "read_write";
        if(
          $pack->auth->user_id != dbconnection::queryObject("SELECT * FROM notes WHERE note_id = '{$pack->note_id}'")->user_id || 
          !users::authenticateUser($pack->auth)
        ) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE notes SET ".
            (isset($pack->name)        ? "name        = '".addslashes($pack->name)."', "        : "").
            (isset($pack->description) ? "description = '".addslashes($pack->description)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE note_id = '{$pack->note_id}'"
        );

        //allow for 'tag_id' in API, but really just use object_tags
        if($pack->tag_id)
        {
            dbconnection::query("DELETE FROM object_tags WHERE game_id = '{$pack->game_id}' AND object_type = 'NOTE' AND object_id = '{$pack->note_id}'");
            dbconnection::queryInsert("INSERT INTO object_tags (game_id, object_type, object_id, tag_id, created) VALUES ('{$pack->game_id}', 'NOTE', '{$pack->note_id}', '{$pack->tag_id}', CURRENT_TIMESTAMP)");
        }
        
        return notes::getNotePack($pack);
    }

    private static function noteObjectFromSQL($sql_note)
    {
        if(!$sql_note) return $sql_note;
        $note = new stdClass();
        $note->note_id     = $sql_note->note_id;
        $note->game_id     = $sql_note->game_id;
        $note->user_id     = $sql_note->user_id;
        $note->name        = $sql_note->name;
        $note->description = $sql_note->description;
        $note->media_id    = $sql_note->media_id;
        $note->user               = new stdClass();
        $note->user->user_id      = $note->user_id;
        $note->user->user_name    = $sql_note->user_name;
        $note->user->display_name = $sql_note->display_name;

        return $note;
    }

    public static function getNote($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return notes::getNotePack($glob); }
    public static function getNotePack($pack)
    {
        $sql_note = dbconnection::queryObject("SELECT notes.*, users.user_name, users.display_name FROM notes LEFT JOIN users ON notes.user_id = users.user_id WHERE note_id = '{$pack->note_id}' LIMIT 1");
        $note = notes::noteObjectFromSQL($sql_note);
        if($note)
        {
            //allow for 'tag_id' in API, but really just use object_tags
            if($tag = dbconnection::queryObject("SELECT * FROM object_tags WHERE game_id = '{$note->game_id}' AND object_type = 'NOTE' AND object_id = '{$note->note_id}'"))
                $note->tag_id = $tag->tag_id;
            else
                $note->tag_id = "0";
        }
        return new return_package(0,$note);
    }

    public static function getNotesForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return notes::getNotesForGamePack($glob); }
    public static function getNotesForGamePack($pack)
    {
        $notes = array();
        //two separate impls depending on presense of search. search makes query MUCH slower.
        if(isset($pack->search))
        {
            $sql_notes = dbconnection::queryArray("SELECT notes.*, users.user_name, users.display_name FROM notes LEFT JOIN users ON notes.user_id = users.user_id WHERE game_id = '{$pack->game_id}' AND (name LIKE '%{$pack->search}%' OR description LIKE '%{$pack->search}%' OR user_name LIKE '%{$pack->search}%' OR display_name LIKE '%{$pack->search}%')");
            for($i = 0; $i < count($sql_notes); $i++)
            {
                if(!($ob = notes::noteObjectFromSQL($sql_notes[$i]))) continue;

                //allow for 'tag_id' in API, but really just use object_tags
                if($tag = dbconnection::queryObject("SELECT * FROM object_tags WHERE game_id = '{$ob->game_id}' AND object_type = 'NOTE' AND object_id = '{$ob->note_id}'"))
                    $ob->tag_id = $tag->tag_id;
                else 
                    $ob->tag_id = "0";

                $notes[] = $ob;
            }

            //search comments to find matching comments, then RETURN PARENT NOTE- NOT THE COMMENTS THEMSELVES
            $sql_note_comments = dbconnection::queryArray("SELECT * FROM note_comments LEFT JOIN users ON note_comments.user_id = users.user_id WHERE game_id = '{$pack->game_id}' AND (name LIKE '%{$pack->search}%' OR description LIKE '%{$pack->search}%' OR user_name LIKE '%{$pack->search}%' OR display_name LIKE '%{$pack->search}%')");
            for($i = 0; $i < count($sql_note_comments); $i++)
            {
                $sql_note = dbconnection::queryObject("SELECT * FROM notes LEFT JOIN users ON notes.user_id = users.user_id WHERE note_id = '{$sql_note_comments[$i]->note_id}'");
                if(!($ob = notes::noteObjectFromSQL($sql_note))) continue;

                //allow for 'tag_id' in API, but really just use object_tags
                if($tag = dbconnection::queryObject("SELECT * FROM object_tags WHERE game_id = '{$ob->game_id}' AND object_type = 'NOTE' AND object_id = '{$ob->note_id}'"))
                    $ob->tag_id = $tag->tag_id;
                else 
                    $ob->tag_id = "0";

                $notes[] = $ob;
            }
        }
        else
        {
            $sql_notes = dbconnection::queryArray("SELECT notes.*, users.user_name, users.display_name FROM notes LEFT JOIN users ON notes.user_id = users.user_id WHERE game_id = '{$pack->game_id}'");
            for($i = 0; $i < count($sql_notes); $i++)
            {
                if(!($ob = notes::noteObjectFromSQL($sql_notes[$i]))) continue;

                //allow for 'tag_id' in API, but really just use object_tags
                if($tag = dbconnection::queryObject("SELECT * FROM object_tags WHERE game_id = '{$ob->game_id}' AND object_type = 'NOTE' AND object_id = '{$ob->note_id}'"))
                    $ob->tag_id = $tag->tag_id;
                else 
                    $ob->tag_id = "0";

                $notes[] = $ob;
            }
        }
        
        return new return_package(0,$notes);
    }

    public static function deleteNote($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return notes::deleteNotePack($glob); }
    public static function deleteNotePack($pack)
    {
        $note = dbconnection::queryObject("SELECT * FROM notes WHERE note_id = '{$pack->note_id}'");
        $pack->auth->game_id = $note->game_id;
        $pack->auth->permission = "read_write";
        if(
          ($pack->auth->user_id != $note->user_id || !users::authenticateUser($pack->auth)) &&
          !editors::authenticateGameEditor($pack->auth)
        ) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM notes WHERE note_id = '{$pack->note_id}' LIMIT 1");

        //cleanup
        $tags = dbconnection::queryArray("SELECT * FROM object_tags WHERE object_type = 'NOTE' AND object_id = '{$pack->note_id}'");
        for($i = 0; $i < count($tags); $i++)
        {
            $pack->object_tag_id = $tags[$i]->object_tag_id;
            tags::deleteObjectTagPack($pack);
        }

        return new return_package(0);
    }
}
?>
