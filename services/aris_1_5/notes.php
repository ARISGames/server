<?php
require_once("module.php");
require_once("media.php");
require_once("games.php");
require_once("locations.php");
require_once("playerStateChanges.php");
require_once("editorFoldersAndContent.php");

class Notes extends Module
{
    //Returns note_id
	function createNewNote($gameId, $playerId)
    {
        $query = "INSERT INTO notes (game_id, owner_id, title) VALUES ('{$gameId}', '{$playerId}', 'New Note')";
        @mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
        
        return new returnData(0, mysql_insert_id());
    }
    
    function updateNote($noteId, $title, $shared)
    {
        $query = "UPDATE notes SET title = '{$title}', shared = '{$shared}' WHERE note_id = '{$noteId}'";
        @mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
        
        return new returnData(0);
    }
    
    function addContentToNote($noteId, $gameId, $mediaId, $type, $text)
    {
        $query = "INSERT INTO note_content (note_id, game_id, media_id, type, text) VALUES ('{$noteId}', '{$gameId}', '{$mediaId}', '{$type}', '{$text}')";
        $result = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        
        return new returnData(0);
    }
    
    function addContentToNoteFromFileName($gameId, $noteId, $filename, $type, $name="playerUploadedContent")
    {
        $newMediaResultData = Media::createMedia($gameId, $name, $filename, 0);
        $newMediaId = $newMediaResultData->data->media_id;
        
        return Notes::addContentToNote($noteId, $gameId, $newMediaId, $type, "");
    }
    
    function addCommentToNote($gameId, $playerId, $noteId, $rating)
    {
        $query = "INSERT INTO notes (game_id, owner_id, parent_note_id, parent_rating, title) VALUES ('{game_id}', '{$playerId}', '{$noteId}', '{$rating}', 'New Comment')";
        $result = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        $commentId = mysql_insert_id();
        
        $query = "SELECT ave_rating, num_ratings FROM notes WHERE note_id = '{$noteId}'";
        $result = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        
        if($aveComment = mysql_fetch_object($result))
        {
            $newAve = (($aveComment->num_ratings)/($aveComment->num_ratings + 1) * $aveComment->ave_rating) + (1/($aveComment->num_ratings + 1) * $rating);
            $query = "UPDATE notes SET ave_rating = '{$newAve}', num_ratings = '" . ($aveComment->num_ratings + 1) . "' WHERE note_id = '{$noteId}'";
            $result = @mysql_query($query);
            if (mysql_error()) return new returnData(1, NULL, mysql_error());
        }
        return new returnData(0, $commentId);
    }
    
    function getNotesForGame($gameId)
    {
        $query = "SELECT note_id FROM notes WHERE game_id = '{$gameId}'";
        $result = @mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
        
        $notes = array();
        while($note = mysql_fetch_object($result))
        {
            $notes[] = Notes::getFullNoteObject($note->note_id);
        }
        
        return new returnData(0, $notes);
    }
    
    function getNotesForPlayer($playerId, $gameId)
    {
        $query = "SELECT note_id FROM notes WHERE owner_id = '{$playerId}' AND game_id = '{$gameId}'";
        $result = @mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
        
        $notes = array();
        while($note = mysql_fetch_object($result))
        {
            $notes[] = Notes::getFullNoteObject($note->note_id);
        }
        
        return new returnData(0, $notes);
    }
    
    function getNoteById($noteId)
    {
        $note = Notes::getFullNoteObject($noteId);
        return new returnData(0, $note);
    }
    
    function getFullNoteObject($noteId)
    {
        $query = "SELECT * FROM notes WHERE note_id = '{$noteId}'";
        $result = @mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
        if($note = mysql_fetch_object($result))
        {
            $note->contents = Notes::getNoteContents($noteId);
            $note->comments = Notes::getNoteComments($noteId);
            $note->icon_media_id = 5;
            return $note;
        }
        return;
    }
    
    function getNoteContents($noteId)
    {
        $query = "SELECT * FROM note_content WHERE note_id = '{$noteId}'";
        $result = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        
        $contents = array();
        while($content = mysql_fetch_object($result))
        {
            $contents[] = $content;
        }
        
        return $contents;
    }
    
    function getNoteComments($noteId)
    {
        $query = "SELECT note_id FROM notes WHERE parent_note_id = '{$noteId}'";
        $resultA = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        
        $comments = array();
        while($commentNoteId = mysql_fetch_object($resultA))
        {
            $comment = Notes::getFullNoteObject($commentNoteId);
            $query = "SELECT user_name FROM players WHERE player_id = '{$comment->player_id}'";
            $resultB = @mysql_query($query);
            if (mysql_error()) return new returnData(1, NULL, mysql_error());
            if($player = mysql_fetch_object($resultB))
            {
                $comment->player_name = $player->user_name;
                $comments[] = $comment;
            }
        }
        return $comments;
    }
    
    function deleteNote($noteId)
    {
        $query = "SELECT note_id FROM notes WHERE parent_note_id = '{$noteId}'";
        $result = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        
        while($commentNote = mysql_fetch_object($result))
        {
            deleteNote($commentNote->note_id);
        }
        
        $query = "DELETE FROM notes, note_content WHERE note_id = '{$noteId}'";
        @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        
        return new returnData(0);
    }
    
    function deleteNoteContent($contentId)
    {
        $query = "DELETE FROM note_content WHERE content_id = '{$contentId}'";
        @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        
        return new returnData(0);
    }
}
















