<?php
require_once('config.php');

class webbackpack
{
	public function __construct()
	{
		$this->conn = @mysql_connect(Config::dbHost, Config::dbUser, Config::dbPass);
		if(!$this->conn)
			die("Configuration of db incorrect.");
      		mysql_select_db (Config::dbSchema);
      		mysql_query("set names utf8");
		mysql_query("set charset set utf8");
	}

	/*
	* Gets information for web backpack for any player/game pair
	*/
	public function getData($gameId, $playerId, $individual=false)
	{
		$backpack = new stdClass();
		//Get game information (needn't be called for every player- should be moved to own function)

		$query = "SELECT games.game_id, games.name, m.name as media_name, m.file_name as media_url, im.name as icon_media_name, im.file_name as icon_media_url FROM games LEFT JOIN media as m ON games.media_id = m.media_id LEFT JOIN media as im ON games.icon_media_id = im.media_id WHERE games.game_id = '{$gameId}'";

		/* Query- formatted for readability -
		$query = "SELECT 
			games.game_id, games.name, m.name as media_name, m.file_name as media_url, im.name as icon_media_name, im.file_name as icon_media_url 
			FROM 
			games LEFT JOIN 
			media as m ON games.media_id = m.media_id LEFT JOIN 
			media as im ON games.icon_media_id = im.media_id 
			WHERE 
		 	games.game_id = '{$gameId}'";
		*/

		$result = mysql_query($query);
		$game = mysql_fetch_object($result);
		if(!$game) return "Invalid Game ID"; 
		$backpack->game=$game;

		//Get owner information
		$query = "SELECT user_name FROM players WHERE player_id = '{$playerId}'";
		$result = mysql_query($query);
		$name = mysql_fetch_object($result);
		if(!$name) return "Invalid Player ID";
		$backpack->owner=$name;
		$backpack->owner->player_id = $playerId;


		/* ATTRIBUTES */
		$query = "SELECT i.item_id, i.name, i.description, i.max_qty_in_inventory, i.weight, i.type, i.url, pi.qty, m.name as media_name, m.file_name as media_file_name, m.game_id as media_game_id, im.name as icon_name, im.file_name as icon_file_name, im.game_id as icon_game_id FROM {$gameId}_player_items as pi, {$gameId}_items as i LEFT JOIN media as m ON i.media_id = m.media_id LEFT JOIN media as im ON i.icon_media_id = im.media_id WHERE pi.player_id = {$playerId} AND pi.item_id = i.item_id AND i.type = 'ATTRIB'";

		/* Query- formatted for readability -
		$query = "SELECT
			i.item_id, i.name, i.description, i.is_attribute, i.max_qty_in_inventory, i.weight, i.type, i.url, 
			pi.qty, 
			m.name as media_name, m.file_name as media_file_name, m.game_id as media_game_id, 
			im.name as icon_name, im.file_name as icon_file_name, im.game_id as icon_game_id 
			FROM 
			{$gameId}_player_items as pi, 
			{$gameId}_items as i LEFT JOIN 
			media as m ON i.media_id = m.media_id LEFT JOIN 
			media as im ON i.icon_media_id = im.media_id
			WHERE 
			pi.player_id = {$playerId} AND pi.item_id = i.item_id
			AND i.type = 'ATTRIB'";
		*/

		$result = mysql_query($query);
		$contents = array();
		while($content = mysql_fetch_object($result))
			$contents[] = $content;

		$backpack->attributes = $contents;


		/* OTHER ITEMS */
		$query = "SELECT i.item_id, i.name, i.description, i.max_qty_in_inventory, i.weight, i.type, i.url, pi.qty, m.name as media_name, m.file_name as media_file_name, m.game_id as media_game_id, im.name as icon_name, im.file_name as icon_file_name, im.game_id as icon_game_id FROM {$gameId}_player_items as pi, {$gameId}_items as i LEFT JOIN media as m ON i.media_id = m.media_id LEFT JOIN media as im ON i.icon_media_id = im.media_id WHERE pi.player_id = {$playerId} AND pi.item_id = i.item_id AND i.type != 'ATTRIB'";

		/* Query- formatted for readability -
		$query = "SELECT
			i.item_id, i.name, i.description, i.is_attribute, i.max_qty_in_inventory, i.weight, i.type, i.url, 
			pi.qty, 
			m.name as media_name, m.file_name as media_file_name, m.game_id as media_game_id, 
			im.name as icon_name, im.file_name as icon_file_name, im.game_id as icon_game_id 
			FROM 
			{$gameId}_player_items as pi, 
			{$gameId}_items as i LEFT JOIN 
			media as m ON i.media_id = m.media_id LEFT JOIN 
			media as im ON i.icon_media_id = im.media_id
			WHERE 
			pi.player_id = {$playerId} AND pi.item_id = i.item_id
			AND i.type != 'ATTRIB'";
		*/

		$result = mysql_query($query);
		$contents = array();
		while($content = mysql_fetch_object($result))
			$contents[] = $content;

		$backpack->items = $contents;


		/* NOTES */
		if($individual)
        		$query = "SELECT note_id FROM notes WHERE (owner_id = '{$playerId}' OR public_to_notebook = '1') AND game_id = '{$gameId}' AND parent_note_id = 0 ORDER BY sort_index ASC";
		else
        		$query = "SELECT note_id FROM notes WHERE owner_id = '{$playerId}' AND game_id = '{$gameId}' AND parent_note_id = 0 ORDER BY sort_index ASC";

        	$result = mysql_query($query);
        	
        	$notes = array();
        	while($note = mysql_fetch_object($result))
            		$notes[] = $this->getFullNoteObject($note->note_id, $playerId);
        	
		$backpack->notes = $notes;
	
	
		return $backpack;
	}


	function getGameData($gameId)
	{
		$query = "SELECT DISTINCT player_id FROM player_log WHERE game_id='{$gameId}'";
		$result = mysql_query($query);
		$backPacks = array();
		while($player = mysql_fetch_object($result))
		{
			$backPacks[] = $this->getData($gameId, $player->player_id);
		}
		return $backPacks;
	}

	function getFullNoteObject($noteId, $playerId=0)
	{
		$query = "SELECT game_id, owner_id, title, public_to_map, public_to_notebook FROM notes WHERE note_id = '{$noteId}'";
		$result = @mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
		if($note = mysql_fetch_object($result))
		{
			$query = "SELECT user_name FROM players WHERE player_id = '{$note->owner_id}'";
			$player = mysql_query($query);
			$playerObj = mysql_fetch_object($player);
			$note->username = $playerObj->user_name;
			$note->contents = $this->getNoteContents($noteId);
			$note->comments = $this->getNoteComments($noteId, $playerId);
			$note->tags = $this->getNoteTags($noteId, $note->game_id);
			$note->likes = $this->getNoteLikes($noteId);
			$note->player_liked = ($playerId == 0 ? 0 : $this->playerLiked($playerId, $noteId));
			$note->icon_media_id = 5;
			return $note;
		}
		return;
	}

	function getNoteContents($noteId)
	{
		$query = "SELECT nc.media_id, nc.type, nc.text, nc.game_id, nc.title, m.file_name, m.game_id FROM note_content as nc LEFT JOIN media as m ON nc.media_id = m.media_id WHERE note_id = '{$noteId}'";
		$result = mysql_query($query);
        
		$contents = array();
		while($content = mysql_fetch_object($result))
			$contents[] = $content;
        	
		return $contents;
	}

	function getNoteComments($noteId, $playerId)
	{
		$query = "SELECT note_id FROM notes WHERE parent_note_id = '{$noteId}'";
		$result = mysql_query($query);
        
		$comments = array();
		while($commentNoteId = mysql_fetch_object($result))
		{
			$comment = $this->getFullNoteObject($commentNoteId->note_id, $playerId);
			$comments[] = $comment;
		}
		return $comments;
	}
	
	function getNoteTags($noteId, $gameId)
	{
		$query = "SELECT note_tags.tag, player_created FROM note_tags LEFT JOIN ((SELECT tag, player_created FROM game_tags WHERE game_id = '{$gameId}') as gt) ON note_tags.tag = gt.tag WHERE note_id = '{$noteId}'";
		$result = mysql_query($query);
		$tags = array();
		while($tag = mysql_fetch_object($result))	
			$tags[] = $tag;
		return $tags;
	}
	
	function getNoteLikes($noteId)
	{
		$query = "SELECT COUNT(*) as numLikes FROM note_likes WHERE note_id = '{$noteId}'";
		$result  = mysql_query($query);
		$likes = mysql_fetch_object($result);
		return $likes->numLikes;
	}

	function playerLiked($playerId, $noteId)
	{
		$query = "SELECT COUNT(*) as liked FROM note_likes WHERE player_id = '{$playerId}' AND note_id = '{$noteId}' LIMIT 1";
		$result = mysql_query($query);
		$liked = mysql_fetch_object($result);
		return $liked->liked;
	}



}
