<?php

//ini_set('display_errors', 'On');
//error_reporting(E_ALL);

require_once("module.php");
require_once("media.php");
require_once("games.php");
require_once("locations.php");
require_once("players.php");
require_once("playerStateChanges.php");
require_once("editorFoldersAndContent.php");

class Notes extends Module
{
	//Returns note_id
	function createNewNote($gameId, $playerId, $lat=0, $lon=0)
	{
		$query = "INSERT INTO notes (game_id, owner_id, title) VALUES ('{$gameId}', '{$playerId}', 'New Note')";
		Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
		$nId = mysql_insert_id();
		Module::saveContentNoAuthentication($gameId, false, 0, 'PlayerNote', $nId, 0);
		Module::processGameEvent($playerId, $gameId, Module::kLOG_GET_NOTE, $nId);
		Players::dropNote($gameId, $playerId, $nId, $lat, $lon);
		return new returnData(0, $nId);
	}

	function createNewNoteStartIncomplete($gameId, $playerId, $lat=0, $lon=0)
	{
		$query = "INSERT INTO notes (game_id, owner_id, title, incomplete) VALUES ('{$gameId}', '{$playerId}', 'New Note', '1')";
		Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
		$nId = mysql_insert_id();
		Module::saveContentNoAuthentication($gameId, false, 0, 'PlayerNote', $nId, 0);
		Module::processGameEvent($playerId, $gameId, Module::kLOG_GET_NOTE, $nId);
		Players::dropNote($gameId, $playerId, $nId, $lat, $lon);
		return new returnData(0, $nId);
	}

	function updateNote($noteId, $title, $publicToMap, $publicToNotebook, $sortIndex='0')
	{
		$title = addslashes($title);
		$query = "UPDATE notes SET title = '{$title}', public_to_map = '{$publicToMap}', public_to_notebook = '{$publicToNotebook}', sort_index='{$sortIndex}' WHERE note_id = '{$noteId}'";
		Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());

		$query = "SELECT * FROM notes WHERE note_id = '{$noteId}'";
		$result = Module::query($query);
		$noteobj = mysql_fetch_object($result);

		$query = "UPDATE locations SET name = '{$title}' WHERE game_id = {$noteobj->game_id} AND type='PlayerNote' AND type_id = '{$noteId}'";
		Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());

		return new returnData(0);
	}

	function addContentToNote($noteId, $gameId, $playerId, $mediaId, $type, $text, $title='', $lat=0, $lon=0)
	{
		$text = addslashes($text);
		if($title == '') $title = Date('F jS Y h:i:s A');
		else $title = addslashes($title);
		$query = "INSERT INTO note_content (note_id, game_id, media_id, type, text, title) VALUES ('{$noteId}', '{$gameId}', '{$mediaId}', '{$type}', '{$text}', '{$title}')";
		$result = Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
		$contentId = mysql_insert_id();

		Module::processGameEvent($playerId, $gameId, Module::kLOG_UPLOAD_MEDIA_ITEM, $contentId, $lat, $lon);
		if($type == "PHOTO")
			Module::processGameEvent($playerId, $gameId, Module::kLOG_UPLOAD_MEDIA_ITEM_IMAGE, $contentId, $lat, $lon);
		else if($type == "AUDIO")
			Module::processGameEvent($playerId, $gameId, Module::kLOG_UPLOAD_MEDIA_ITEM_AUDIO, $contentId, $lat, $lon);
		else if($type == "VIDEO")
			Module::processGameEvent($playerId, $gameId, Module::kLOG_UPLOAD_MEDIA_ITEM_VIDEO, $contentId, $lat, $lon);
		return new returnData(0, $contentId);
	}

	function addContentToNoteFromFileName($gameId, $noteId, $playerId, $filename, $type, $name="playerUploadedContent")
	{
		if(!$name) $name = date("Y-m-d H:i:s");
		$newMediaResultData = Media::createMedia($gameId, $name, $filename, 0);
		$newMediaId = $newMediaResultData->data->media_id;

		return Notes::addContentToNote($noteId, $gameId, $playerId, $newMediaId, $type, "", "");
	}

	function updateContent($contentId, $text)
	{
		$text = addslashes($text);
		$query = "UPDATE note_content SET text='{$text}' WHERE content_id='{$contentId}'";
		Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
		return new returnData(0);
	}

	function deleteNoteContent($contentId)
	{
		$query = "DELETE FROM note_content WHERE content_id = '{$contentId}'";
		Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
		return new returnData(0);
	}

	function updateContentTitle($contentId, $title)
	{
		$title = addslashes($title);
		$query = "UPDATE note_content SET title='{$title}' WHERE content_id='{$contentId}'";
		Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
		return new returnData(0);
	}

	function addCommentToNote($gameId, $playerId, $noteId, $title="New Comment")
	{
		$query = "SELECT owner_id FROM notes WHERE game_id = '{$gameId}' AND note_id = '{$noteId}'";
		$result = Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
		$questownerobj = mysql_fetch_object($result);
		$questowner = $questownerobj->owner_id;

		Module::processGameEvent($playerId, $gameId, Module::kLOG_GIVE_NOTE_COMMENT, $noteId);

		$query = "INSERT INTO notes (game_id, owner_id, parent_note_id, title, incomplete) VALUES ('{$gameId}', '{$playerId}', '{$noteId}', '{$title}', '1')";
		$result = Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
		$commentId = mysql_insert_id();

		return new returnData(0, $commentId);
	}

	function addCommentToNoteStartIncomplete($gameId, $playerId, $noteId, $title="New Comment") //Depracated, may appear in some phones, remove 3 montsh from 2/6/13
	{
		$query = "SELECT owner_id FROM notes WHERE game_id = '{$gameId}' AND note_id = '{$noteId}'";
		$result = Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
		$questownerobj = mysql_fetch_object($result);
		$questowner = $questownerobj->owner_id;

		Module::processGameEvent($playerId, $gameId, Module::kLOG_GIVE_NOTE_COMMENT, $noteId);

		$query = "INSERT INTO notes (game_id, owner_id, parent_note_id, title, incomplete) VALUES ('{$gameId}', '{$playerId}', '{$noteId}', '{$title}', '1')";
		$result = Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
		$commentId = mysql_insert_id();

		return new returnData(0, $commentId);
	}

	function setNoteComplete($noteId)
	{
		$query = "UPDATE notes SET incomplete = '0' WHERE note_id = '{$noteId}'";
		$result = Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
		return new returnData(0, NULL);
	}

	function updateComment($noteId, $title)
	{
		$title = addslashes($title);
		$query = "UPDATE notes SET title= '{$title}', incomplete = '0' WHERE note_id = '{$noteId}'";
		$result = Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
		return new returnData(0, $newAve);	
	}

	function updateLocation($gameId, $noteId, $lat, $lng)
	{
		Module::giveNoteToWorld($gameId, $noteId, $lat, $lng);
	}

	//Gets all notes accessible through the notebook by an arbitrary player
	function getNotesForGame($gameId, $playerId) 
	{
		$query = "SELECT note_id FROM notes WHERE game_id = '{$gameId}' AND parent_note_id = '0' AND (public_to_notebook = '1' OR public_to_map = '1')";
		$result = Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());

		$notes = array();
		while($note = mysql_fetch_object($result))
		{
			$notes[] = Notes::getFullNoteObject($note->note_id, $playerId);
		}
		return new returnData(0, $notes);
	}

	function getNotesWithAttributes($json)
	{

		$gameId       = $json["gameId"];
		$playerId     = $json["playerId"];

		$searchType   = $json["searchType"];
		$tagIds       = $json["tagIds"];
		$searchTerms  = $json["searchTerms"];
		$date         = $json["date"];
		$lastLocation = $json["lastLocation"];	
		$noteCount    = $json["noteCount"];

		$notesName   = "result_notes";
		$notesSelect = "";

		if(count($tagIds) > 0)
		{
			$tagsJoin = " INNER JOIN (SELECT * FROM note_tags WHERE tag_id = {$tagIds[0]}";
			for($i = 1; $i < count($tagIds); ++$i)
			{
				$tagsJoin .= " OR tag_id = {$tagIds[$i]}";
			}
			$tagsJoin .= ") AS selectedTags ON {$notesName}.note_id = selectedTags.note_id";
		}

		if(count($searchTerms) > 0)
		{
			$searchTermsJoin = " INNER JOIN (SELECT * FROM note_content WHERE type = 'TEXT'";
			for($i = 0; $i < count($searchTerms); ++$i)
			{
				$searchTermsJoin .= " AND text LIKE '%{$searchTerms[$i]}%'";
			} 	
			$searchTermsJoin .= ") AS textWithTerms ON {$notesName}.note_id = textWithTerms.note_id";
		}

		switch($searchType)
		{	
			case 0:
				$searchSort  = " LEFT JOIN note_likes ON {$notesName}.note_id = note_likes.note_id LEFT JOIN (SELECT player_id FROM players WHERE curator = '1') AS curators ON note_likes.player_id = curators.player_id GROUP BY {$notesName}.note_id ORDER BY COUNT(curators.player_id) DESC, {$notesName}.created DESC";
				break;
			case 1:
				$searchSort  = " LEFT JOIN note_likes ON {$notesName}.note_id = note_likes.note_id LEFT JOIN note_shares ON {$notesName}.note_id = note_shares.note_id LEFT JOIN notes AS note_comments ON {$notesName}.note_id = note_comments.note_id GROUP BY {$notesName}.note_id ORDER BY (COUNT(note_likes.note_id) + COUNT(note_shares.note_id) + COUNT(note_comments.note_id)) DESC, {$notesName}.created DESC";
				break;
			case 2:
				$searchSort  = " ORDER BY {$notesName}.created DESC";
				break;
			case 3:
				$notesSelect .= " AND owner_id = {$playerId}";
				$searchSort  = " ORDER BY {$notesName}.created DESC";
				break;

		}

		if($date)
		{
			$date = strtotime($date);
			$notesSelect  .= " AND UNIX_TIMESTAMP(created) < {$date}";
			if($noteCount) 
				$limitString = " LIMIT {$noteCount}";
		}	
		else
		{
			if($lastLocation && $noteCount)
				$limitString = " LIMIT {$lastLocation}, {$noteCount}";
			else if($lastLocaion && !$noteCount)
				$limitString = " OFFSET {$lastLocation}";
			else if(!$lastLocation && $noteCount)
				$limitString = " LIMIT {$noteCount}";
		}

		$query = "SELECT {$notesName}.note_id FROM (SELECT note_id, created FROM notes WHERE game_id = '{$gameId}' AND parent_note_id = '0' AND (public_to_notebook = '1' OR public_to_map = '1'){$notesSelect}) AS {$notesName}{$tagsJoin}{$searchTermsJoin}{$searchSort}{$limitString}"; 
		$result = Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());

		$notes = array();
		while($note = mysql_fetch_object($result))
		{
			$notes[] = Notes::getFullNoteObject($note->note_id, $playerId);
		}
		return new returnData(0, $notes);

	}

	//Gets an individual's notes. 
	function getNotesForPlayer($playerId, $gameId)
	{
		$query = "SELECT note_id FROM notes WHERE owner_id = '{$playerId}' AND game_id = '{$gameId}' AND parent_note_id = 0 ORDER BY sort_index ASC";
		$result = Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());

		$notes = array();
		while($note = mysql_fetch_object($result))
		{
			$notes[] = Notes::getFullNoteObject($note->note_id, $playerId);
		}

		return new returnData(0, $notes);
	}

	function getNoteById($noteId, $playerId=0)
	{
		$note = Notes::getFullNoteObject($noteId, $playerId);
		return new returnData(0, $note);
	}

	function getFullNoteObject($noteId, $playerId=0)
	{
		$query = "SELECT * FROM notes WHERE note_id = '{$noteId}'";
		$result = Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
		if($note = mysql_fetch_object($result))
		{
			$query = "SELECT user_name, display_name FROM players WHERE player_id = '{$note->owner_id}'";
			$player = Module::query($query);
			$playerObj = mysql_fetch_object($player);
			$type = MODULE::kSHARE_FACEBOOK;
			$note->facebook_shares = Notes::getNoteShares($noteId, $type);	
			$type = MODULE::kSHARE_TWITTER;
			$note->twitter_shares = Notes::getNoteShares($noteId, $type);	
			$type = MODULE::kSHARE_PINTEREST;
			$note->pinterest_shares = Notes::getNoteShares($noteId, $type);	
			$type = MODULE::kSHARE_EMAIL;
			$note->email_shares = Notes::getNoteShares($noteId, $type);	
			$note->username = $playerObj->user_name;
			$note->displayname = $playerObj->display_name;
			$note->contents = Notes::getNoteContents($noteId, $note->game_id);
			$note->comments = Notes::getNoteComments($noteId, $playerId);
			$note->tags = Notes::getNoteTags($noteId, $note->game_id);
			$note->likes = Notes::getNoteLikes($noteId);
			$note->player_liked = ($playerId == 0 ? 0 : Notes::playerLiked($playerId, $noteId));
			$note->player_flagged = ($playerId == 0 ? 0 : Notes::playerFlagged($playerId, $noteId));
			$note->icon_media_id = 5;
			if($note->dropped = Notes::noteDropped($noteId, $note->game_id))
				$location = Notes::getNoteLocation($noteId, $note->game_id);	
			else
			{
				$location = new stdClass();
				$location->lat = $location->lon = 0;
			}
			$note->lat = $location->lat;
			$note->lon = $location->lon;
			return $note;
		}
		return;
	}

	function getNoteShares($noteId, $type)
	{
		$query = "SELECT COUNT(*) AS shares FROM note_shares WHERE note_id = '{$noteId}' AND share_type = '{$type}'";
		$result = Module::query($query);
		$resultObj = mysql_fetch_object($result);
		return $resultObj->shares;		
	}

	function getNoteContents($noteId, $gameId)
	{
		$query = "SELECT * FROM note_content WHERE note_id = '{$noteId}'";
		$result = Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());

		$contents = array();
		while($content = mysql_fetch_object($result))
		{
			$content->media = Media::getMediaObject($gameId, $content->media_id);
			$contents[] = $content;
		}

		return $contents;
	}

	function getNoteComments($noteId, $playerId)
	{
		$query = "SELECT note_id FROM notes WHERE incomplete = '0' AND parent_note_id = '{$noteId}'";
		$result = Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());

		$comments = array();
		while($commentNoteId = mysql_fetch_object($result))
		{
			$comment = Notes::getFullNoteObject($commentNoteId->note_id, $playerId);
			$comments[] = $comment;
		}
		return $comments;
	}

	//Gets list of all tags owned by a note
	//Array of json objects:
	//	tag = 'my tag'
	//	tag_id = 4
	//	player_created = 0 (if 0, by game author, and potentially used in requirements. if 1, created by some player)
	function getNoteTags($noteId, $gameId)
	{
		$query = "SELECT gt.tag, gt.tag_id, gt.player_created, gt.media_id FROM note_tags LEFT JOIN ((SELECT tag, tag_id, media_id, player_created, media_id FROM game_tags WHERE game_id = '{$gameId}') as gt) ON note_tags.tag_id = gt.tag_id WHERE note_id = '{$noteId}'";
		$result = Module::query($query);
		$tags = array();
		while($tag = mysql_fetch_object($result))	
			$tags[] = $tag;
		return $tags;
	}

	function getGameTag($tagId)
	{
		$query = "SELECT * FROM game_tags WHERE tag_id = '{$tagId}'";
		$result = Module::query($query);
		if($tag = mysql_fetch_object($result))
			return new returnData(0,$tag);
		else
			return new returnData(1,NULL,"Tag Not Found");
	}

	function getNoteLikes($noteId)
	{
		$query = "SELECT COUNT(*) as numLikes FROM note_likes WHERE note_id = '{$noteId}'";
		$result  = Module::query($query);
		$likes = mysql_fetch_object($result);
		return $likes->numLikes;
	}

	function playerLiked($playerId, $noteId)
	{
		$query = "SELECT COUNT(*) as liked FROM note_likes WHERE player_id = '{$playerId}' AND note_id = '{$noteId}' LIMIT 1";
		$result = Module::query($query);
		$liked = mysql_fetch_object($result);
		return $liked->liked;
	}

	function playerFlagged($playerId, $noteId)
	{
		$query = "SELECT COUNT(*) as flagged FROM note_flags WHERE player_id = '{$playerId}' AND note_id = '{$noteId}' LIMIT 1";
		$result = Module::query($query);
		$flagged = mysql_fetch_object($result);
		return $flagged->flagged;
	}

	function noteDropped($noteId, $gameId)
	{
		$query = "SELECT * FROM locations WHERE game_id = {$gameId} AND type='PlayerNote' AND type_id='{$noteId}' LIMIT 1";
		$result = Module::query($query);

		if(mysql_num_rows($result) > 0)
			return true;
		else
			return false;
	}

	function getNoteLocation($noteId, $gameId)
	{
		$query = "SELECT * FROM locations WHERE game_id = {$gameId} AND type='PlayerNote' AND type_id='{$noteId}' LIMIT 1";
		$result = Module::query($query);
		$locObj = mysql_fetch_object($result);
		$retLoc = new stdClass();
		$retLoc->lat = $locObj->latitude;
		$retLoc->lon = $locObj->longitude;
		return $retLoc;
	}

	function deleteNote($noteId)
	{
		//If noteId is 0, it will rather elegantly delete EVERYTHING in the note database 
		//becasue 0 is used for the parent_id of all new notes
		if($noteId == 0) return new returnData(0);

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
		while($commentNote = mysql_fetch_object($result)) Notes::deleteNote($commentNote->note_id);

		return new returnData(0);
	}

	//Gets all tags in game (NOT note/tag pairs), regardless of how created
	//Array of json objects
	//	tag = 'my tag'
	//	tag_id = 4
	//	player_created = 0 (0 means tag created by game author, 1 means created by some player, and is instantiated at least once in a note in game)
	function getGameTags($gameId)
	{
		$query = "SELECT tag_id, tag, player_created, media_id from game_tags WHERE game_id = '{$gameId}'";
		$result = Module::query($query);
		$tags = array();
		while($tag = mysql_fetch_object($result))	
			$tags[] = $tag;
		return $tags;
	}

	function getAllTagsInGame($gameId)
	{
		$tags = Notes::getGameTags($gameId);
		return new returnData(0, $tags);
	}

	function addTagToNote($noteId, $gameId, $tag)
	{
		//Check if tag exists for game
		$query = "SELECT tag_id FROM game_tags WHERE game_id = '{$gameId}' AND tag = '{$tag}' LIMIT 1";
		$result = Module::query($query);
		$id = mysql_fetch_object($result);

		//If not
		if(!$id->tag_id)
		{
			//Make sure it is ok for player to create tag for game
			$query = "SELECT allow_player_tags FROM games WHERE game_id='{$gameId}'";	
			$result = Module::query($query);
			$allow = mysql_fetch_object($result);
			if($allow->allow_player_tags != 1)
				//Player not allowed to create own tag
				return new returnData(1, NULL, "Player Generated Tags Not Allowed In This Game");	

			//Create tag for game
			$query = "INSERT INTO game_tags (tag, game_id, player_created) VALUES ('{$tag}', '{$gameId}', 1)";
			Module::query($query);
			$id->tag_id = mysql_insert_id();
		}

		//Apply tag to note
		$query = "INSERT INTO note_tags (note_id, tag_id) VALUES ('{$noteId}', '{$id->tag_id}')";
		Module::query($query);

		return new returnData(0, $id->tag_id);
	}

	function deleteTagFromNote($noteId, $tagId)
	{
		$query = "DELETE FROM note_tags WHERE note_id = '{$noteId}' AND tag_id = '{$tagId}'";
		Module::query($query);

		$query = "SELECT * FROM note_tags WHERE tag_id = '{$tagId}'";
		$result = Module::query($query);
		if(mysql_num_rows($result) == 0)
		{
			//Deleting a tag from a note can only delete the tag from the game if it is the last instantiation of that tag, and the tag was player created
			$query = "DELETE FROM game_tags WHERE tag_id = '{$tagId}' AND player_created = 1";
			Module::query($query);
		}
		return new returnData(0);
	}

	function addTagToGame($gameId, $tag, $editorId, $editorToken)
	{
		if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
			return new returnData(6, NULL, "Failed Authentication");

		$query = "INSERT INTO game_tags (tag, game_id, player_created) VALUES ('{$tag}', '{$gameId}', 0)";
		Module::query($query);
		return new returnData(0, mysql_insert_id());
	}

	//If author created, demotes to player created. completely wipes if player created. player created tags cannot exist if not instantiated at least once.
	function deleteTagFromGame($gameId, $tagId, $editorId, $editorToken)
	{
		if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
			return new returnData(6, NULL, "Failed Authentication");

		$query = "SELECT * FROM game_tags WHERE tag_id = '{$tagId}'"; 
		$result = Module::query($query);
		$tag = mysql_fetch_object($result);
		if($tag->player_created == 1)
		{
			//Completely wipe from game
			$query = "DELETE FROM game_tags WHERE tag_id = '{$tagId}'";
			Module::query($query);
			$query = "DELETE FROM note_tags WHERE tag_id = '{$tagId}'";
			Module::query($query);
		}
		else
		{
			//Checks to see if instantiated at least once (a necessary property of player_created notes)
			$query = "SELECT note_id FROM note_tags WHERE tag_id = '{$tagId}'";
			$result = Module::query($query);
			if(mysql_num_rows($result) > 0) //Exists at least once- just demote
			{
				$query = "UPDATE game_tags SET player_created = 1 WHERE tag_id = '{$tagId}'";
				$result = Module::query($query);
			}
			else //isn't instantiated- wipe it
			{
				$query = "DELETE FROM game_tags WHERE tag_id = '{$tagId}'";
				Module::query($query);
				$query = "DELETE FROM note_tags WHERE tag_id = '{$tagId}'";
				Module::query($query);
			}
		}
		return new returnData(0);
	}

	function likeNote($playerId, $noteId)
	{
		$query = "SELECT owner_id, game_id FROM notes WHERE note_id = '{$noteId}'";
		$result = Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
		$questownerobj = mysql_fetch_object($result);
		$questowner = $questownerobj->owner_id;
		$gameId = $questownerobj->game_id;

		Module::processGameEvent($playerId, $gameId, Module::kLOG_GIVE_NOTE_LIKE, $noteId);

		$query = "INSERT INTO note_likes (player_id, note_id) VALUES ('{$playerId}', '{$noteId}')";
		Module::query($query);

		return new returnData(0);
	}

	function unlikeNote($playerId, $noteId)
	{
		$query = "DELETE FROM note_likes WHERE player_id = '{$playerId}' AND note_id = '{$noteId}'";
		Module::query($query);
		return new returnData(0);
	}

	function flagNote($playerId, $noteId)
	{
		$query = "INSERT INTO note_flags (player_id, note_id) VALUES ('{$playerId}', '{$noteId}')";
		Module::query($query);

		$query = "SELECT COUNT(*) AS numFlags FROM note_flags WHERE note_id = '{$noteId}'"; 
		Module::query($query);
		$flags = mysql_fetch_object($result);
		if($flags->numFlags == 3)
		{
			$query2 = "SELECT notes.parent_id, notes.title, players.email FROM notes INNER JOIN players ON notes.owner_id = players.player_id WHERE note_id = '{$noteId}''";
			$result = Module::query($query2);
			if (!$player = mysql_fetch_array($result)) return new returnData(4, NULL, "Not a player");

			$email = $player['email'];
			$isComment = $player['parent_id'];
			$noteContent = $player['title'];
			$typeOfNoteDescription;
			if($isComment != 0) $typeOfNoteDescription = "Comment";
			else $typeOfNoteDescription = "Note";

			$subject = "'{$typeOfNoteDescription}' Flagged 3 Times";
			$body = "Your '{$typeOfNoteDescription}' that had the text: '{$noteContent}' has been flagged 3 times.<br><br>Please be aware of others on this platform. The YOI team has been notified and will be looking into the matter.";
			$playerNotificationSent = Module::sendEmail($email, $subject, $body);
			$email = "hanshaw@wisc.edu";
			$subject = "NoteId '{$noteId}' Flagged";
			$body    = "Sending to Note Owner: '{$playerNotificationSent}' <br><br>Keep being awesome";
			if (Module::sendEmail($email, $subject, $body) && $playerNotificationSent) return new returnData(0, NULL);
			else return new returnData(5, NULL, "Mail could not be sent");
		}

		return new returnData(0);
	}

	function unflagNote($playerId, $noteId)
	{
		$query = "DELETE FROM note_flags WHERE player_id = '{$playerId}' AND note_id = '{$noteId}'";
		Module::query($query);
		return new returnData(0);
	}

	function sharedNoteToFacebook($playerId, $noteId)
	{
		$type  = MODULE::kSHARE_FACEBOOK;
		$query = "INSERT INTO note_shares (player_id, note_id, share_type) VALUES ('{$playerId}', '{$noteId}', '{$type}')";
		Module::query($query);
		return new returnData(0);
	}

	function sharedNoteToTwitter($playerId, $noteId)
	{
		$type  = MODULE::kSHARE_TWITTER;
		$query = "INSERT INTO note_shares (player_id, note_id, share_type) VALUES ('{$playerId}', '{$noteId}', '{$type}')";
		Module::query($query);
		return new returnData(0);
	}

	function sharedNoteToPinterest($playerId, $noteId)
	{
		$type  = MODULE::kSHARE_PINTEREST;
		$query = "INSERT INTO note_shares (player_id, note_id, share_type) VALUES ('{$playerId}', '{$noteId}', '{$type}')";
		Module::query($query);
		return new returnData(0);
	}

	function sharedNoteToEmail($playerId, $noteId)
	{
		$type  = MODULE::kSHARE_EMAIL;
		$query = "INSERT INTO note_shares (player_id, note_id, share_type) VALUES ('{$playerId}', '{$noteId}', '{$type}')";
		Module::query($query);
		return new returnData(0);
	}

	// API FUNCTIONS \/ \/ \/
	public static function getFullNotesForGame($noteReqObj)
	{
		$gameId = $noteReqObj['gameId'];

		if(is_numeric($gameId))
			$gameId = intval($gameId);
		else
			return new returnData(1, "Error- Invalid Game Id: ".$gameId);

		if (!Module::query("SELECT * FROM games WHERE game_id = '{$gameId}'")) 
			return new returnData(1, "Error- Invalid Game Id: ".$gameId);

		$notes = array();
		$query = "SELECT note_id, owner_id FROM notes WHERE game_id = '{$gameId}' AND parent_note_id = 0";
		$result = Module::query($query);
		while($noteObj = mysql_fetch_object($result))
		{
			$note = Notes::getDetailedFullNoteObject($noteObj->note_id, $noteObj->player_id);
			$notes[] = $note;
		}
		return new returnData(0,$notes);
	}

	public static function getDetailedPlayerNotes($playerId, $gameId, $individual=true)
	{
		/* NOTES */
		if($individual)
			$query = "SELECT note_id FROM notes WHERE (owner_id = '{$playerId}' OR public_to_notebook = '1') AND game_id = '{$gameId}' AND parent_note_id = 0 ORDER BY sort_index ASC";
		else
			$query = "SELECT note_id FROM notes WHERE owner_id = '{$playerId}' AND game_id = '{$gameId}' AND parent_note_id = 0 ORDER BY sort_index ASC";

		$result = Module::query($query);

		$notes = array();
		while($noteId = mysql_fetch_object($result)) {
			$note = Notes::getDetailedFullNoteObject($noteId->note_id, $playerId);
			//Phil commented out these lines because they make no sense- notes have no media, their CONTENT does. 6/11
			//$note->media_url = Media::getMediaDirectoryURL($note->game_id)->data . '/' . $note->media_url;
			//$note->icon_url = Media::getMediaDirectoryURL(0)->data . '/' . $note->icon_url;
			$notes[] = $note;
		}
		return $notes;
	}

	private static function getDetailedFullNoteObject($noteId, $playerId=0)
	{
		$query = "SELECT note_id, game_id, owner_id, title, public_to_map, public_to_notebook, created FROM notes WHERE note_id = '{$noteId}'";
		$result = Module::query($query);
		if (mysql_error()) return new returnData(1, NULL, mysql_error());
		if($note = mysql_fetch_object($result))
		{
			$query = "SELECT user_name FROM players WHERE player_id = '{$note->owner_id}'";
			$player = Module::query($query);
			$playerObj = mysql_fetch_object($player);
			$note->username = $playerObj->user_name;
			$note->displayname = $playerObj->display_name;
			$note->contents = Notes::getNoteContentsAPI($noteId);
			$note->comments = Notes::getNoteCommentsAPI($noteId, $playerId);
			$note->tags = Notes::getNoteTagsAPI($noteId, $note->game_id);
			$note->likes = Notes::getNoteLikesAPI($noteId);
			$note->player_liked = ($playerId == 0 ? 0 : Notes::playerLikedAPI($playerId, $noteId));
			$note->icon_media_id = 5;
			if($note->dropped = Notes::noteDropped($noteId, $note->game_id))
				$location = Notes::getNoteLocation($noteId, $note->game_id);	
			else
			{
				$location = new stdClass();
				$location->lat = $location->lon = 0;
			}
			$note->lat = $location->lat;
			$note->lon = $location->lon;
			return $note;
		}
		return;
	}

	private static function getNoteContentsAPI($noteId)
	{
		//the whole 'm.file_path AS file_name' is for legacy reasons... Phil 10/12/2012
		$query = "SELECT nc.media_id, nc.type, nc.text, nc.game_id, nc.title, m.file_path, m.file_path AS file_name, m.game_id FROM note_content as nc LEFT JOIN media as m ON nc.media_id = m.media_id WHERE note_id = '{$noteId}'";
		$result = Module::query($query);
		$contents = array();
		while($content = mysql_fetch_object($result))
		{
			$content->media_url = Config::gamedataWWWPath . '/' . $content->file_path;
                        $content->media_thumb_url = substr($content->media_url,0,strrpos($content->media_url,'.')).'_128'.substr($content->media_url,strrpos($content-media_url,'.'));
			$contents[] = $content;
		}
		return $contents;
	}

	private static function getNoteCommentsAPI($noteId, $playerId)
	{
		$query = "SELECT note_id FROM notes WHERE parent_note_id = '{$noteId}'";
		$result = Module::query($query);

		$comments = array();
		while($commentNoteId = mysql_fetch_object($result))
		{
			$comment = Notes::getDetailedFullNoteObject($commentNoteId->note_id, $playerId);
			$comments[] = $comment;
		}
		return $comments;
	}

	private static function getNoteTagsAPI($noteId, $gameId)
	{
		$query = "SELECT gt.tag, gt.tag_id, gt.player_created, gt.media_id FROM note_tags LEFT JOIN ((SELECT tag, tag_id, media_id, player_created FROM game_tags WHERE game_id = '{$gameId}') as gt) ON note_tags.tag_id = gt.tag_id WHERE note_id = '{$noteId}'";
		$result = Module::query($query);
		$tags = array();
		while($tag = mysql_fetch_object($result))	
			$tags[] = $tag;
		return $tags;
	}

	private static function getNoteLikesAPI($noteId)
	{
		$query = "SELECT COUNT(*) as numLikes FROM note_likes WHERE note_id = '{$noteId}'";
		$result  = Module::query($query);
		$likes = mysql_fetch_object($result);
		return $likes->numLikes;
	}

	private static function playerLikedAPI($playerId, $noteId)
	{
		$query = "SELECT COUNT(*) as liked FROM note_likes WHERE player_id = '{$playerId}' AND note_id = '{$noteId}' LIMIT 1";
		$result = Module::query($query);
		$liked = mysql_fetch_object($result);
		return $liked->liked;
	}
}
