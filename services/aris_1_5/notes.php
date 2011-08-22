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
	function createNote($gameId, $playerId, $title, $text, $shared)
    {
        $query = "INSERT INTO notes (game_id, owner_id, title, text, shared) VALUES ('{$gameId}', '{$playerId}', '{$title}', '{$text}', '{$shared}')";
        @mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
        
        return new returnData(0, mysql_insert_id());
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
    
    function getNotesForPlayer($playerId)
    {
        $query = "SELECT note_id FROM notes WHERE owner_id = '{$playerId}'";
        $result = @mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
        
        $notes = array();
        while($note = mysql_fetch_object($result))
        {
            $notes[] = Notes::getFullNoteObject($note->note_id);
        }
        
        return new returnData(0, $notes);
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
        $query = "SELECT * FROM note_comments WHERE note_id = '{$noteId}'";
        $resultA = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        
        $comments = array();
        while($comment = mysql_fetch_object($resultA))
        {
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
    
    function addContentToNote($noteId, $title, $mediaId)
    {
        $query = "INSERT INTO note_content (note_id, title, media_id) VALUES ('{$noteId}', '{$title}', '{$mediaId}')";
        $result = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        
        return new returnData(0);
    }
    
    function addCommentToNote($noteId, $playerId, $rating, $text)
    {
        $query = "INSERT INTO note_comments (note_id, player_id, rating, text) VALUES ('{$noteId}', '{$playerId}', '{$rating}', '{$text}')";
        $result = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        
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
        
        return new returnData(0);
    }
}