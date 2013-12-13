<?php

require_once("module.php");
require_once("media.php");
require_once("locations.php");
require_once("../../libraries/wideimage/WideImage.php");

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

    public function uploadMedia($glob)
    {
        //$glob = json_decode(file_get_contents('php://input'));
        //Module::serverErrorLog(json_encode($glob));

        $path     = $glob["path"];
        $filename = $glob["filename"];
        $data     = $glob["data"];
        
        $gameMediaDirectory = Media::getMediaDirectory($path)->data;

        $md5 = md5((string)microtime().$filename);
        $ext = substr($filename, -3);
        $newMediaFileName = 'aris'.$md5.'.'.$ext;
        $resizedMediaFileName = 'aris'.$md5.'_128.'.$ext;

        if(
                //Images
                $ext != "jpg" &&
                $ext != "png" &&
                $ext != "gif" &&
                //Video
                $ext != "mp4" &&
                $ext != "mov" &&
                $ext != "m4v" &&
                $ext != "3gp" &&
                //Audio
                $ext != "caf" &&
                $ext != "mp3" &&
                $ext != "aac" &&
                $ext != "m4a" &&
                //Overlays
                $ext != "zip"
          )
        return new returnData(1,NULL,"Invalid filetype:$ext");

        $fullFilePath = $gameMediaDirectory."/".$newMediaFileName;

        $fp = fopen($fullFilePath, 'w');
        if(!$fp) return new returnData(1,NULL,"Couldn't open file:$fullFilePath");
        fwrite($fp,base64_decode($data));
        fclose($fp);

        if($ext == "jpg" || $ext == "png" || $ext == "gif")
        {
            $img = WideImage::load($gameMediaDirectory."/".$newMediaFileName);
            $img = $img->resize(128, 128, 'outside');
            $img = $img->crop('center','center',128,128);
            $img->saveToFile($gameMediaDirectory."/".$resizedMediaFileName);
        }
        else if($ext == "mp4") //only works with mp4
        {
            /*
               $ffmpeg = '../../libraries/ffmpeg';
               $videoFilePath      = $gameMediaDirectory."/".$newMediaFileName; 
               $tempImageFilePath  = $gameMediaDirectory."/temp_".$resizedMediaFileName; 
               $imageFilePath      = $gameMediaDirectory."/".$resizedMediaFileName; 
               $cmd = "$ffmpeg -i $videoFilePath 2>&1"; 
               $thumbTime = 1;
               if(preg_match('/Duration: ((\d+):(\d+):(\d+))/s', shell_exec($cmd), $videoLength))
               $thumbTime = (($videoLength[2] * 3600) + ($videoLength[3] * 60) + $videoLength[4])/2; 
               $cmd = "$ffmpeg -i $videoFilePath -deinterlace -an -ss $thumbTime -t 00:00:01 -r 1 -y -vcodec mjpeg -f mjpeg $tempImageFilePath 2>&1"; 
               shell_exec($cmd);

               $img = WideImage::load($tempImageFilePath);
               $img = $img->resize(128, 128, 'outside');
               $img = $img->crop('center','center',128,128);
               $img->saveToFile($imageFilePath);
             */
        }

        Module::serverErrorLog("Uploaded W/JSON $newMediaFileName");

        return new returnData(0,$newMediaFileName);
    }

}
?>

