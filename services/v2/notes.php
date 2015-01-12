<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("return_package.php");

require_once("media.php");
require_once("note_comments.php");
require_once("instances.php");
require_once("triggers.php");

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

        if($pack->trigger)
        {

			$scene_id    = dbconnection::queryObject("SELECT * FROM user_game_scenes WHERE user_id = '{$pack->auth->user_id}' AND game_id = '{$pack->game_id}'")->scene_id;
            $instance_id = dbconnection::queryInsert("INSERT INTO instances (game_id, object_id, object_type, created) VALUES ('{$pack->game_id}', '{$pack->note_id}', 'NOTE', CURRENT_TIMESTAMP)");
            $trigger_id  = dbconnection::queryInsert("INSERT INTO triggers (game_id, instance_id, scene_id, type, latitude, longitude, distance, created) VALUES ( '{$pack->game_id}', '{$instance_id}', '{$scene_id}', 'LOCATION', '{$pack->trigger->latitude}', '{$pack->trigger->longitude}', '25', CURRENT_TIMESTAMP);");
        }

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

        if($pack->trigger)
        {
            $instance = dbconnection::queryObject("SELECT * FROM instances WHERE game_id = '{$pack->game_id}' AND object_type = 'NOTE' AND object_id = '{$pack->note_id}'");
            if(!$instance)
            {
                dbconnection::queryInsert("INSERT INTO instances (game_id, object_id, object_type, created) VALUES ('{$pack->game_id}', '{$pack->note_id}', 'NOTE', CURRENT_TIMESTAMP)");
                $instance = dbconnection::queryObject("SELECT * FROM instances WHERE game_id = '{$pack->game_id}' AND object_type = 'NOTE' AND object_id = '{$pack->note_id}'");
            }

            dbconnection::query("DELETE FROM triggers WHERE game_id = '{$pack->game_id}' AND instance_id = '{$instance->instance_id}'");
            dbconnection::queryInsert("INSERT INTO triggers (game_id, instance_id, type, name, title, latitude, longitude, distance, created) VALUES ( '{$pack->game_id}', '{$instance->instance_id}', 'LOCATION', '{$pack->name}', '{$pack->name}', '{$pack->trigger->latitude}', '{$pack->trigger->longitude}', '25', CURRENT_TIMESTAMP);");
        }

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
        $note->created     = $sql_note->created;
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
            /*
            //Search in PHP
            $notes_arr       = dbconnection::queryArray("SELECT * FROM notes       WHERE game_id = '{$pack->game_id}'");
            $tags_arr        = dbconnection::queryArray("SELECT * FROM tags        WHERE game_id = '{$pack->game_id}' AND object_type = 'NOTE'");
            $object_tags_arr = dbconnection::queryArray("SELECT * FROM object_tags WHERE game_id = '{$pack->game_id}'");

            $tags        = array(); for($i = 0; $i < count($tags_arr);        $i++)        $tags[       $tags_arr[$i]->tag_id] =        $tags_arr[$i];
            $object_tags = array(); for($i = 0; $i < count($object_tags_arr); $i++) $object_tags[$object_tags_arr[$i]->tag_id] = $object_tags_arr[$i];
            $users       = array(); //will be derived on the spot

            $returned
            for($i = 0; $i < count($notes_arr); $i++)
            {
                $note = $notes_arr[$i];
                if(!$users[$note->user_id]) $users[$note->user_id] = dbconnection::queryObject("SELECT * FROM users WHERE user_id = '{$note->user_id}'");
                $user = $users[$note->user_id];
                $tag = $tags[$object_tags_arr[$note->note_id]->tag_id]

                if(
                    preg_match("@".$pack->search."@is",$note->name) ||
                    preg_match("@".$pack->search."@is",$note->description) ||
                    preg_match("@".$pack->search."@is",$user->user_name) ||
                    preg_match("@".$pack->search."@is",$user->display_name) ||
                    preg_match("@".$pack->search."@is",$tag->tag)
                )
                {
                    $note->user_name    = $users->user_name;
                    $note->display_name = $users->display_name;
                    $n = notes::noteObjectFromSQL($note);
                    $n-tag_id = $tag->tag_id;
                    $notes[] = $n;
                }
            }
            */

            //Search w/ SQL
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

    public static function searchNotes($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return notes::searchNotesPack($glob); }
    public static function searchNotesPack($pack)
    {
        $game_id = $pack->game_id;
        $search_terms = isset($pack->search_terms) ? $pack->search_terms : array();
        $note_count = $pack->note_count;
        $user_id = $pack->user_id;
        $order_by = $pack->order_by;
        $tag_ids = isset($pack->tag_ids) ? $pack->tag_ids : array();

        $lines = array();

        $lines[] = "SELECT notes.*, users.user_name, users.display_name, object_tags.tag_id, tags.tag, COUNT(note_likes.note_like_id) AS note_likes";
        $lines[] = "FROM notes";
        $lines[] = "JOIN users ON notes.user_id = users.user_id";
        if ($order_by === 'popular' || !empty($search_terms)) {
            $lines[] = "LEFT JOIN note_comments ON notes.note_id = note_comments.note_id";
        }
        if ($order_by === 'popular') {
            $lines[] = "LEFT JOIN note_likes ON notes.note_id = note_likes.note_id";
        }
        $lines[] = "LEFT JOIN object_tags ON object_tags.object_type = 'NOTE' AND notes.note_id = object_tags.object_id";
        $lines[] = "LEFT JOIN tags ON object_tags.tag_id = tags.tag_id";
        $lines[] = "LEFT JOIN note_likes ON notes.note_id = note_likes.note_id";

        $lines[] = "WHERE 1=1";
        $lines[] = "AND notes.game_id = '{$game_id}'";
        $searchables = array('notes.name', 'notes.description', 'notes.user_name', 'notes.display_name', 'note_comments.description');
        foreach ($search_terms as $term) {
            $matches = array();
            foreach ($searchables as $key) {
                $matches[] = "({$key} LIKE '%{$term}%')";
            }
            $lines[] = 'AND (' . implode(' OR ', $matches) . ')';
        }
        if ($user_id) {
            $lines[] = "AND notes.user_id = '{$user_id}'";
        }
        if (!empty($tag_ids)) {
            $tag_list = implode(',', $tag_ids);
            $lines[] = "AND object_tags.tag_id IN ({$tag_list})";
        }

        $lines[] = "GROUP BY notes.note_id";
        if ($order_by === 'popular') {
            $lines[] = "ORDER BY (COUNT(note_likes.note_id) + COUNT(note_comments.note_id)) DESC";
        }
        else if ($order_by === 'recent') {
            $lines[] = "ORDER BY notes.created DESC";
        }

        if ($note_count) {
            $lines[] = "LIMIT {$note_count}";
        }

        $query = implode(' ', $lines);
        $sql_notes = dbconnection::queryArray($query);
        $notes = array();
        for ($i = 0; $i < count($sql_notes); $i++) {
            $ob = notes::noteObjectFromSQL($sql_notes[$i]);
            if (!$ob) continue;
            $ob->tag_id = $sql_notes[$i]->tag_id;
            $ob->note_likes = $sql_notes[$i]->note_likes;
            $ob->media = media::getMediaPack((object) array('media_id' => $ob->media_id));
            $ob->comments = note_comments::getNoteCommentsForNotePack((object) array('game_id' => $ob->game_id, 'note_id' => $ob->note_id));
            $notes[] = $ob;
        }
        return new return_package(0, $notes);
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

    public static function likeNote($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return notes::likeNotePack($glob); }
    public static function likeNotePack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $existing = dbconnection::queryObject(
            "SELECT * FROM note_likes"
            . " WHERE game_id = '{$pack->game_id}'"
            . " AND note_id = '{$pack->note_id}'"
            . " AND user_id = '{$pack->auth->user_id}'"
        );
        if($existing)
        {
            return new return_package(0);
        }

        dbconnection::queryInsert(
            "INSERT INTO note_likes"
            . " (game_id, note_id, user_id, created)"
            . " VALUES"
            . " ( '" . $pack->game_id       . "'"
            . " , '" . $pack->note_id       . "'"
            . " , '" . $pack->auth->user_id . "'"
            . " , CURRENT_TIMESTAMP"
            . " )"
        );

        return new return_package(0);
    }

    public static function unlikeNote($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return notes::unlikeNotePack($glob); }
    public static function unlikeNotePack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "DELETE FROM note_likes"
            . " WHERE game_id = '{$pack->game_id}'"
            . " AND note_id = '{$pack->note_id}'"
            . " AND user_id = '{$pack->auth->user_id}'"
            . " LIMIT 1"
        );

        return new return_package(0);
    }
}
?>
