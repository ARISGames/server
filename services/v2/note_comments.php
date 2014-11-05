<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("return_package.php");

class note_comments extends dbconnection
{
    public static function createNoteComment($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return note_comments::createNoteCommentPack($glob); }
    public static function createNoteCommentPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->note_comment_id = dbconnection::queryInsert(
            "INSERT INTO note_comments (".
            "game_id,".
            "note_id,".
            "user_id,".
            (isset($pack->name)        ? "name,"        : "").
            (isset($pack->description) ? "description," : "").
            "created".
            ") VALUES (".
            "'".$pack->game_id."',".
            "'".$pack->note_id."',".
            "'".$pack->auth->user_id."',".
            (isset($pack->name)        ? "'".addslashes($pack->name)."',"        : "").
            (isset($pack->description) ? "'".addslashes($pack->description)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return note_comments::getNoteCommentPack($pack);
    }

    public static function updateNoteComment($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return note_comments::updateNoteCommentPack($glob); }
    public static function updateNoteCommentPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(
          $pack->auth->user_id != dbconnection::queryObject("SELECT * FROM note_comments WHERE note_comment_id = '{$pack->note_comment_id}'")->user_id || 
          !users::authenticateUser($pack->auth)
        ) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE note_comments SET ".
            (isset($pack->name)        ? "name        = '".addslashes($pack->name)."', "        : "").
            (isset($pack->description) ? "description = '".addslashes($pack->description)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE note_comment_id = '{$pack->note_comment_id}'"
        );
        return note_comments::getNoteCommentPack($pack);
    }

    private static function noteCommentObjectFromSQL($sql_note_comment)
    {
        if(!$sql_note_comment) return $sql_note_comment;
        $note_comment = new stdClass();
        $note_comment->note_comment_id = $sql_note_comment->note_comment_id;
        $note_comment->note_id         = $sql_note_comment->note_id;
        $note_comment->game_id         = $sql_note_comment->game_id;
        $note_comment->user_id         = $sql_note_comment->user_id;
        $note_comment->name            = $sql_note_comment->name;
        $note_comment->description     = $sql_note_comment->description;
        $note_comment->user               = new stdClass();
        $note_comment->user->user_id      = $note_comment->user_id;
        $note_comment->user->user_name    = $sql_note_comment->user_name;
        $note_comment->user->display_name = $sql_note_comment->display_name;

        return $note_comment;
    }

    public static function getNoteComment($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return note_comments::getNoteCommentPack($glob); }
    public static function getNoteCommentPack($pack)
    {
        $sql_note_comment = dbconnection::queryObject("SELECT note_comments.*, users.user_name, users.display_name FROM note_comments LEFT JOIN users ON note_comments.user_id = users.user_id WHERE note_comment_id = '{$pack->note_comment_id}' LIMIT 1");
        $note_comment = note_comments::noteCommentObjectFromSQL($sql_note_comment);

        return new return_package(0,$note_comment);
    }

    public static function getNoteCommentsForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return note_comments::getNoteCommentsForGamePack($glob); }
    public static function getNoteCommentsForGamePack($pack)
    {
        $sql_note_comments = dbconnection::queryArray("SELECT note_comments.*, users.user_name, users.display_name FROM note_comments LEFT JOIN users ON note_comments.user_id = users.user_id WHERE game_id = '{$pack->game_id}'");
        $note_comments = array();
        for($i = 0; $i < count($sql_note_comments); $i++)
        {
            if(!($ob = note_comments::noteCommentObjectFromSQL($sql_note_comments[$i]))) continue;
            $note_comments[] = $ob;
        }
        
        return new return_package(0,$note_comments);
    }

    public static function getNoteCommentsForNote($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return note_comments::getNoteCommentsForNotePack($glob); }
    public static function getNoteCommentsForNotePack($pack)
    {
        $sql_note_comments = dbconnection::queryArray("SELECT note_comments.*, users.user_name, users.display_name FROM note_comments LEFT JOIN users ON note_comments.user_id = users.user_id WHERE game_id = '{$pack->game_id}' AND note_id = '{$pack->note_id}'");
        $note_comments = array();
        for($i = 0; $i < count($sql_note_comments); $i++)
        {
            if(!($ob = note_comments::noteCommentObjectFromSQL($sql_note_comments[$i]))) continue;
            $note_comments[] = $ob;
        }
        
        return new return_package(0,$note_comments);
    }

    public static function deleteNoteComment($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return note_comments::deleteNoteCommentPack($glob); }
    public static function deleteNoteCommentPack($pack)
    {
        $note_comment = dbconnection::queryObject("SELECT * FROM note_comments WHERE note_comment_id = '{$pack->note_comment_id}'");
        $pack->auth->game_id = $note_comment->game_id;
        $pack->auth->permission = "read_write";
        if(
          ($pack->auth->user_id != $note_comment->user_id || !users::authenticateUser($pack->auth)) &&
          !editors::authenticateGameEditor($pack->auth)
        ) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM note_comments WHERE note_comment_id = '{$pack->note_comment_id}' LIMIT 1");

        return new return_package(0);
    }
}
?>
