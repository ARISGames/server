<?php

require_once("module.php");
require_once("media.php");
require_once("locations.php");

class Notebook extends Module
{
    public function createNote($gameId, $playerId)
    {
        Module::query("INSERT INTO notes (game_id, owner_id) VALUES ('{$gameId}', '{$playerId}')");
        $id = mysql_insert_id();
        Module::saveContentNoAuthentication($gameId, false, 0, 'PlayerNote', $id, 0);
        return new returnData(0, $id);
    }

    public function updateNote($noteId, $title, $publicToMap, $publicToNotebook, $lat, $lon)
    {
        $title = addslashes($title);
        Module::query("UPDATE notes SET title = '{$title}', public_to_map = '{$publicToMap}', public_to_notebook = '{$publicToNotebook}' WHERE note_id = '{$noteId}'");

        $note = Module::queryObject("SELECT * FROM notes WHERE note_id = '{$noteId}'");
        $loc = Module::queryObject("SELECT * FROM locations WHERE game_id = '{$note->game_id}' AND type = 'PlayerNote' AND type_id = '{$noteId}'");
        if($loc) Module::query("UPDATE locations SET name = '{$title}', latitude = '{$lat}', longitude = '{$lon}'  WHERE game_id = {$note->game_id} AND type='PlayerNote' AND type_id = '{$noteId}'");
        else Module::query("INSERT INTO locations (name, latitude, longitude, type, type_id) VALUES ('{$title}', '{$lat}', '{$lon}', 'PlayerNote', '{$noteId}')");

        return new returnData(0);
    }

    public function addContentToNote($noteId,  $mediaId, $type, $title='')
    {
        if($title == '') $title = Date('F jS Y h:i:s A');
        else $title = addslashes($title);


        $note = Module::queryObject("SELECT * FROM notes WHERE note_id = '{$noteId}'");
        Module::query("INSERT INTO note_content (note_id, game_id, media_id, type, title) VALUES ('{$noteId}', '{$note->game_id}', '{$mediaId}', '{$type}', '{$title}')");
        $contentId = mysql_insert_id();

        return new returnData(0, $contentId);
    }

    public function deleteNoteContent($contentId)
    {
        Module::query("DELETE FROM note_content WHERE content_id = '{$contentId}'");
        return new returnData(0);
    }

    public function updateContent($contentId, $title)
    {
        $title = addslashes($title);
        Module::query("UPDATE note_content SET title='{$title}' WHERE content_id='{$contentId}'");
        return new returnData(0);
    }

    public function addCommentToNote($noteId, $playerId, $title, $text)
    {
        Module::query("INSERT INTO notes (game_id, owner_id, parent_note_id, title) VALUES ('{$gameId}', '{$playerId}', '{$noteId}', '{$title}')");
        $commentId = mysql_insert_id();
        return new returnData(0, $commentId);
    }

    public function updateComment($noteId, $title)
    {
        $title = addslashes($title);
        Module::query("UPDATE notes SET title= '{$title}' WHERE note_id = '{$noteId}'");
        return new returnData(0, NULL);	
    }

    private function getNotesVisibleToGame($gameId, $full = false, $page = 0, $psize = 20) 
    {
        $noteIds = Module::queryArray("SELECT note_id FROM notes WHERE game_id = '{$gameId}' AND parent_note_id = '0' AND (public_to_notebook = '1' OR public_to_map = '1') LIMIT ".($page*$psize).",".$psize);

        $notes = array();
        for($i = 0; $i < count($noteIds); $i++)
        {
            if($full) $notes[] = Notebook::getFullNoteObject($noteIds[$i]->note_id);
            else      $notes[] = Notebook::getStubNoteObject($noteIds[$i]->note_id);
        }

        return $notes;
    }

    public function getFullNotesVisibleToGame($gameId, $page = 0, $psize = 20) 
    {
        return new returnData(0, Notebook::getNotesVisibleToGame($gameId,true,$page,$psize));
    }

    public function getStubNotesVisibleToGame($gameId, $page = 0, $psize = 20)
    {
        return new returnData(0, Notebook::getNotesVisibleToGame($gameId,false,$page,$psize));
    }

    private function getNotesVisibleToPlayer($gameId, $playerId, $full = false, $page = 0, $psize = 20)
    {
        $noteIds = Module::queryArray("SELECT note_id FROM notes WHERE game_id = '{$gameId}' AND parent_note_id = 0 AND (owner_id = '{$playerId}' OR public_to_notebook = '1' OR public_to_map = '1') LIMIT ".($page*$psize).",".$psize);

        $notes = array();
        for($i = 0; $i < count($noteIds); $i++)
        {
            if($full) $notes[] = Notebook::getFullNoteObject($noteIds[$i]->note_id);
            else      $notes[] = Notebook::getStubNoteObject($noteIds[$i]->note_id);
        }

        return $notes;
    }

    public function getFullNotesVisibleToPlayer($gameId, $playerId, $page = 0, $psize = 20)
    {
        return new returnData(0, Notebook::getNotesVisibleToPlayer($gameId,$playerId,true,$page,$psize));
    }

    public function getStubNotesVisibleToPlayer($gameId, $playerId, $page = 0, $psize = 20)
    {
        return new returnData(0, Notebook::getNotesVisibleToPlayer($gameId,$playerId,false,$page,$psize));
    }

    public function getNote($noteId)
    {
        $note = Notebook::getFullNoteObject($noteId);
        if($note) return new returnData(0, $note);
        else      return new returnData(1, $note);
    }

    private function getFullNoteObject($noteId)
    {
        $note = Module::queryObject("SELECT * FROM notes WHERE note_id = '{$noteId}'");
        if(!$note) return null;
        $player = Module::queryObject("SELECT * FROM players WHERE player_id = '{$note->owner_id}'");
        $location = Module::queryObject("SELECT * FROM locations WHERE game_id = '{$note->game_id}' AND type = 'PlayerNote' AND type_id = '{$noteId}'");

        $fullNote = new stdClass();
        $fullNote->owner = new stdClass();
        $fullNote->owner->player_id = $player->player_id;
        $fullNote->owner->user_name = $player->user_name;
        $fullNote->owner->display_name = $player->display_name != "" ? $player->display_name : $player->user_name;
        $fullNote->note_id = $note->note_id;
        $fullNote->game_id = $note->game_id;
        $fullNote->title = $note->title;
        $fullNote->description = $note->description;
        $fullNote->location = new stdClass();
        $fullNote->location->latitude  = $location ? $location->latitude  : 0;
        $fullNote->location->longitude = $location ? $location->longitude : 0;
        $fullNote->created = $note->created;
        $fullNote->contents = Notebook::getNoteContents($noteId);
        $fullNote->comments = Notebook::getNoteComments($noteId);
        $fullNote->tags = Notebook::getNoteTags($noteId, $note->game_id);
        $fullNote->likes = Notebook::getNoteLikes($noteId);
        $fullNote->public_to_map = $note->public_to_map;
        $fullNote->public_to_list = $note->public_to_notebook;

        return $fullNote;
    }

    private function getStubNoteObject($noteId)
    {
        $note = Module::queryObject("SELECT * FROM notes WHERE note_id = '{$noteId}'");
        $player = Module::queryObject("SELECT * FROM players WHERE player_id = '{$note->owner_id}'");

        $stubNote = new stdClass();
        $stubNote->owner = new stdClass();
        $stubNote->owner->player_id = $player->player_id;
        $stubNote->owner->user_name = $player->user_name;
        $stubNote->owner->display_name = $player->display_name != "" ? $player->display_name : $player->user_name;
        $stubNote->note_id = $note->note_id;
        $stubNote->game_id = $note->game_id;
        $stubNote->title = $note->title;
        $stubNote->description = $note->description;
        $stubNote->created = $note->created;
        $stubNote->public_to_map = $note->public_to_map;
        $stubNote->public_to_list = $note->public_to_notebook;

        return $stubNote;
    }

    private function getNoteContents($noteId)
    {
        $contentIds = Module::queryArray("SELECT * FROM note_content WHERE note_id = '{$noteId}'");

        $contents = array();
        for($i = 0; $i < count($contentIds); $i++)
        {
            $media = Media::getMediaObject($contentIds[$i]->game_id, $contentIds[$i]->media_id);

            $content = new stdClass();
            $content->content_id      = $contentIds[$i]->content_id;
            $content->type            = $contentIds[$i]->type;
            $content->media_id        = $contentIds[$i]->media_id;
            $content->text            = $contentIds[$i]->text; //LEGACY
            $content->file_path       = $media->file_path; 
            $content->thumb_file_path = $media->thumb_file_path; 
            $content->url_path        = $media->url_path; 
            $content->url             = $media->url; 
            $content->thumb_url       = $media->thumb_url; 


            $contents[] = $content;
        }

        return $contents;
    }

    private function getNoteComments($noteId)
    {
        $commentIds = Module::queryArray("SELECT * FROM notes WHERE parent_note_id = '{$noteId}'");

        $comments = array();
        for($i = 0; $i < count($commentIds); $i++)
        {
            $player = Module::queryObject("SELECT * FROM players WHERE player_id = '{$commentIds[$i]->owner_id}'");

            $comment = new stdClass();
            $comment->owner = new stdClass();
            $comment->owner->player_id = $player->player_id;
            $comment->owner->user_name = $player->user_name;
            $comment->owner->display_name = $player->display_name;
            $comment->comment_id = $commentIds[$i]->note_id;
            $comment->game_id = $commentIds[$i]->game_id;
            $comment->title = $commentIds[$i]->title;
            $comment->description = $commentIds[$i]->description;
            $comment->created = $commentIds[$i]->created;
            $comment->likes = Notebook::getNoteLikes($commentIds[$i]->note_id);

            $comments[] = $comment;
        }
        return $comments;
    }

    private function getNoteTags($noteId, $gameId)
    {
        $tags = Module::queryArray("SELECT gt.tag, gt.tag_id, gt.player_created, gt.media_id FROM note_tags LEFT JOIN ((SELECT tag, tag_id, player_created, media_id FROM game_tags WHERE game_id = '{$gameId}') as gt) ON note_tags.tag_id = gt.tag_id WHERE note_id = '{$noteId}'");
        return $tags;
    }

    private function getNoteLikes($noteId)
    {
        $likes = Module::queryArray("SELECT * FROM note_likes WHERE note_id = '{$noteId}'");
        return $likes;
    }

    public function getGameTags($gameId)
    {
        $tags = Module::queryArray("SELECT tag_id, tag, player_created, media_id from game_tags WHERE game_id = '{$gameId}'");
        return new returnData(0,$tags);
    }

    public function deleteNote($noteId)
    {
        if($noteId == 0) return new returnData(0);//if 0, it would delete ALL notes

        $noteObj = Module::queryObject("SELECT * FROM notes WHERE note_id = '{$noteId}' LIMIT 1");

        Module::query("DELETE FROM note_tags WHERE note_id = '{$noteId}'");
        Module::query("DELETE FROM note_likes WHERE note_id = '{$noteId}'");
        Module::query("DELETE FROM note_flags WHERE note_id = '{$noteId}'");
        Module::query("DELETE FROM note_shares WHERE note_id = '{$noteId}'");
        Module::query("DELETE FROM folder_contents WHERE game_id = {$noteObj->game_id} AND content_type = 'PlayerNote' AND content_id = '{$noteId}'");
        Module::query("DELETE FROM note_content WHERE note_id = '{$noteId}'");
        Module::query("DELETE FROM note_tags WHERE note_id = '{$noteId}'");
        Module::query("DELETE FROM note_likes WHERE note_id = '{$noteId}'");
        Module::query("DELETE FROM notes WHERE note_id = '{$noteId}'");
        Locations::deleteLocationsForObject($noteObj->game_id, "PlayerNote", $noteId);

        $result = Module::query("SELECT note_id FROM notes WHERE parent_note_id = '{$noteId}'");
        while($commentNote = mysql_fetch_object($result)) Notebook::deleteNote($commentNote->note_id);

        return new returnData(0);
    }

    public function addTagToNote($noteId, $tag)
    {
        $note = Module::queryObject("SELECT * FROM notes WHERE note_id = '{$noteId}'");
        $id = Module::queryObject("SELECT tag_id FROM game_tags WHERE game_id = '{$note->game_id}' AND tag = '{$tag}' LIMIT 1");
        if(!$id->tag_id) //doesn't already exist
        {
            $allow = Module::queryObject("SELECT allow_player_tags FROM games WHERE game_id = '{$note->game_id}'");
            if($allow->allow_player_tags != 1) return new returnData(1, NULL, "Player Generated Tags Not Allowed In This Game");	

            Module::query("INSERT INTO game_tags (tag, game_id, player_created) VALUES ('{$tag}', '{$note->game_id}', 1)");
            $id->tag_id = mysql_insert_id();
        }

        if(!Module::queryObject("SELECT * FROM note_tags WHERE note_id = '{$noteId}' AND tag_id = '{$id->tag_id}'"))
            Module::query("INSERT INTO note_tags (note_id, tag_id) VALUES ('{$noteId}', '{$id->tag_id}')");

        return new returnData(0, $id->tag_id);
    }

    public function deleteTagFromNote($noteId, $tagId)
    {
        Module::query("DELETE FROM note_tags WHERE note_id = '{$noteId}' AND tag_id = '{$tagId}'");

        $tagOnOtherNote = Module::queryObject("SELECT * FROM note_tags WHERE tag_id = '{$tagId}'");
        if($tagOnOtherNote)//Deleting a tag from a NOTE can only delete the tag from the GAME if it is the last instantiation of that tag, and the tag was player created
            Module::query("DELETE FROM game_tags WHERE tag_id = '{$tagId}' AND player_created = 1");

        return new returnData(0);
    }

    public function addTagToGame($gameId, $tag, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        Module::query("INSERT INTO game_tags (tag, game_id, player_created) VALUES ('{$tag}', '{$gameId}', 0)");
        return new returnData(0, mysql_insert_id());
    }

    //If author created, demotes to player created. completely wipes if player created. player created tags cannot exist if not instantiated at least once.
    public function deleteTagFromGame($gameId, $tagId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $tag = Module::queryObject("SELECT * FROM game_tags WHERE tag_id = '{$tagId}'");
        if($tag->player_created == 1) //Completely wipe from game
        {
            Module::query("DELETE FROM game_tags WHERE tag_id = '{$tagId}'");
            Module::query("DELETE FROM note_tags WHERE tag_id = '{$tagId}'");
        }
        else //Checks to see if instantiated at least once (a necessary property of player_created notes)
        {
            $notesTagged = Module::queryObject("SELECT note_id FROM note_tags WHERE tag_id = '{$tagId}'");
            if($notesTagged) //Exists at least once- just demote
            {
                Module::query("UPDATE game_tags SET player_created = 1 WHERE tag_id = '{$tagId}'");
            }
            else //isn't instantiated- wipe it
            {
                Module::query("DELETE FROM game_tags WHERE tag_id = '{$tagId}'");
                Module::query("DELETE FROM note_tags WHERE tag_id = '{$tagId}'");
            }
        }
        return new returnData(0);
    }

    public function likeNote($playerId, $noteId)
    {
        Module::query("INSERT INTO note_likes (player_id, note_id) VALUES ('{$playerId}', '{$noteId}')");
        return new returnData(0);
    }

    public function unlikeNote($playerId, $noteId)
    {
        Module::query("DELETE FROM note_likes WHERE player_id = '{$playerId}' AND note_id = '{$noteId}'");
        return new returnData(0);
    }

    /*
    //Expected JSON format
    {
        "gameId":1234,     //<- REQUIRED
        "playerId":1234,   //<- REQUIRED
        "title":"My Note",
        "description":"This is my note",
        "publicToMap":1,  //<- true/false also acceptable (UNQUOTED!)
        "publicToBook":0,
        "location":
            {
                "latitude":1.234,
                "longitude":9.876,
            },
        "media":
            [
                {
                    "path":1234,              //<- Often gameId. the folder within gamedata that you want the image saved
                    "filename":"banana.jpg",  //<- Unimportant (will get changed), but MUST have correct extension (ie '.jpg')
                    "data":"as262dsf6a..."    //<- base64 encoded media data
                }
                ...
            ]
    }
    */
    public function addNoteFromJSON($glob)
    {
        //WHY DOESNT THIS HAPPEN VIA THE FRAMEWORK?!
	$data = file_get_contents("php://input");
        $glob = json_decode($data);

        $gameId       = $glob->gameId;
        $playerId     = $glob->playerId;
        $title        = $glob->title;
        $description  = $glob->description;
        $publicToMap  = $glob->publicToMap;
        $publicToBook = $glob->publicToBook;
        $location     = $glob->location;
        $media        = $glob->media;

        $publicToMap = 1;
        $publicToBook = 1;

        if(!is_numeric($gameId))   return new returnData(1,NULL,"JSON package has no numeric member \"gameId\"");
        if(!is_numeric($playerId)) return new returnData(1,NULL,"JSON package has no numeric member \"playerId\"");

        $noteId = Notebook::createNote($gameId, $playerId)->data;
        Notebook::updateNote($noteId, $title, $publicToMap, $publicToBook, $location->latitude, $location->longitude);

        for($i = 0; is_array($media) && $i < count($media); $i++)
        {
            $mediaId = Media::createMediaFromJSON($media[$i])->data->media_id;
            Notebook::addContentToNote($noteId,$mediaId,"MEDIA");
        }

        return new returnData(0,Notebook::getNote($noteId));
    }
}
?>

