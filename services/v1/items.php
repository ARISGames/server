<?php
require_once("module.php");
require_once("media.php");
require_once("games.php");
require_once("locations.php");
require_once("playerStateChanges.php");
require_once("editorFoldersAndContent.php");

class Items extends Module
{
	
	
	/**
     * Gets the items within a game
     * @param integer $gameID The game identifier
     * @return returnData
     * @returns a returnData object containing an array of items
     * @see returnData
     */
	public static function getItems($gameId)
	{
		
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		
		$query = "SELECT * FROM {$prefix}_items";
		NetDebug::trace($query);

		
		$rsResult = @mysql_query($query);
		
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		return new returnData(0, $rsResult);
	}
	
	/**
     * Gets the items within a player's inventory
     *
	 * @param integer $gameID The game identifier
     * @param integer $playerId The player identifier
     * @return returnData
     * @returns a returnData object containing an array of items
     * @see returnData
     */
	public static function getItemsForPlayer($gameId, $playerId)
	{
		
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		
		$query = "SELECT {$prefix}_items.*, {$prefix}_player_items.qty 
					FROM {$prefix}_items
					JOIN {$prefix}_player_items 
					ON {$prefix}_items.item_id = {$prefix}_player_items.item_id
					WHERE player_id = $playerId";
		NetDebug::trace($query);
		
		$rsResult = @mysql_query($query);
		if (!$rsResult) return new returnData(0, NULL);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		return new returnData(0, $rsResult);
	}	
	
	/**
     * Gets the Attributes for a player
     *
	 * @param integer $gameID The game identifier
     * @param integer $playerId The player identifier
     * @return returnData
     * @returns a returnData object containing an array of items
     * @see returnData
     */
	public static function getAttributesForPlayer($gameId, $playerId)
	{
		
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
        
		
		$query = "SELECT {$prefix}_items.*, {$prefix}_player_items.qty 
        FROM {$prefix}_items
        JOIN {$prefix}_player_items 
        ON {$prefix}_items.item_id = {$prefix}_player_items.item_id
        WHERE {$prefix}_items.is_attribute = '1' AND player_id = $playerId";
		NetDebug::trace($query);
		
		$rsResult = @mysql_query($query);
		if (!$rsResult) return new returnData(0, NULL);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		return new returnData(0, $rsResult);
	}
	
	
	/**
     * Gets a single item from a game
     *
     * @param integer $gameID The game identifier
     * @param integer $itemId The item identifier
     * @return returnData
     * @returns a returnData object containing an items
     * @see returnData
     */
	public static function getItem($gameId, $itemId)
	{
		
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_items WHERE item_id = {$itemId} LIMIT 1";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		$item = @mysql_fetch_object($rsResult);
		if (!$item) return new returnData(2, NULL, "invalid item id");
		
		return new returnData(0, $item);
		
	}
	
	/**
     * Gets a single item from a game
     * 
     * @param integer $gameId The game identifier
     * @param string $name The name
     * @param string $description The html formatted description
     * @param integer $iconMediaId The item's media identifier
     * @param integer $mediaId The item's icon media identifier
     * @param bool $droppable 1 if this item can be dropped, 0 if not 
     * @param bool $destroyable 1 if this item can be detroyed, 0 if not
     * @param integer $maxQuantityInPlayerInventory The maximum amount of this item a player can have in their inventory
     * @return returnData
     * @returns a returnData object containing the new item identifier
     * @see returnData
     */
	public static function createItem($gameId, $name, $description, 
								$iconMediaId, $mediaId, $droppable, $destroyable, $attribute, $maxQuantityInPlayerInventory, $weight, $url, $type)
	{
		$name = addslashes($name);	
		$description = addslashes($description);	
		
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "INSERT INTO {$prefix}_items 
					(name, description, icon_media_id, media_id, dropable, destroyable, is_attribute, max_qty_in_inventory, weight, url, type)
					VALUES ('{$name}', 
							'{$description}',
							'{$iconMediaId}', 
							'{$mediaId}', 
							'$droppable',
							'$destroyable',
                            '$attribute',
							'$maxQuantityInPlayerInventory',
                            '$weight',
                            '$url',
                            '$type')";
		
		NetDebug::trace("createItem: Running a query = $query");	
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);		
		
		return new returnData(0, mysql_insert_id());
	}
	
	/**
     * Create an item, simultaneously creating media from a file, linking it to the new media and giving it to a player.
     *
     * This service combines several calls into one, avoiding async delays caused by data dependancies
     * created while waiting for createItem() and createMedia() to return the new item/media id
     *
     * @param integer $gameId The game identifier
     * @param integer $playerId The player identifier
     * @param string $name The name
     * @param string $description The html formatted description
     * @param string $fileName The name of the file in the game's data directory that will act as this item's media
     * @param bool $droppable 1 if this item can be dropped, 0 if not 
     * @param bool $destroyable 1 if this item can be detroyed, 0 if not
     * @param float $latitude Latitude where item was created 
     * @param float $longitude Longitude where item was created     
     * @return returnData
     * @returns a returnData object containing the new item identifier
     * @see returnData
     * @see uploadHandler.php
     */
	public static function createItemAndGiveToPlayer($gameId, $playerId, $name, $description, 
								$fileName, $droppable, $destroyable, $latitude, $longitude, $fileType="NORMAL")
	{
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$name = addslashes($name);
		$description = addslashes($description);
		
        //Does game allow players to drop items?
		if ($droppable) { 
			$game = Games::getGame($gameId);
			$droppable = $game->data->allow_player_created_locations;
		}
        
        if($fileType != "NOTE")
        {
            //Create the Media
            $newMediaResultData = Media::createMedia($gameId, $name, $fileName, 0);
            $newMediaID = $newMediaResultData->data->media_id;
		
            $type = Media::getMediaType($fileName);
            if($type == "Image"){
                $iconNum = Module::kPLAYER_CREATED_ITEM_PHOTO_ICON_NUM;
            }
            else if($type == "Audio"){
                $iconNum = Module::kPLAYER_CREATED_ITEM_AUDIO_ICON_NUM;
            }
            else if($type == "Video"){
                $iconNum = Module::kPLAYER_CREATED_ITEM_VIDEO_ICON_NUM;
            }
            else{
                $iconNum = Module::kPLAYER_CREATED_ITEM_DEFAULT_ICON_NUM;
            }
        }
        else
        {
            $newMediaId = 0;
            $iconNum = Module::kPLAYER_CREATED_ITEM_DEFAULT_ICON_NUM;
        }
        
        //Create the Item
        $query = "INSERT INTO {$prefix}_items 
					(name, description, media_id, is_attribute, dropable, destroyable,
					creator_player_id, origin_latitude, origin_longitude, icon_media_id, type)
					VALUES ('$name', 
							'$description',
							'$newMediaID', 
                            '',
							'$droppable',
							'$destroyable',
							'$playerId', '$latitude', '$longitude',
							'$iconNum', '$fileType')";
		
		NetDebug::trace("createItem: Running a query = $query");	
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());
		
		$newItemID = mysql_insert_id();
		
		Module::appendLog($playerId, $gameId, Module::kLOG_UPLOAD_MEDIA_ITEM, $newItemID);
        $type = Media::getMediaType($fileName);
        if($type == "Image"){
            Module::appendLog($playerId, $gameId, Module::kLOG_UPLOAD_MEDIA_ITEM_IMAGE, $newItemID);
        }
        else if($type == "Audio"){
            Module::appendLog($playerId, $gameId, Module::kLOG_UPLOAD_MEDIA_ITEM_AUDIO, $newItemID);
        }
        else if($type == "Video"){
            Module::appendLog($playerId, $gameId, Module::kLOG_UPLOAD_MEDIA_ITEM_VIDEO, $newItemID);
        }
        
		$qty = 1;
		Module::giveItemToPlayer($prefix, $newItemID, $playerId, $qty); 
		
		$pciContentType = Module::kPLAYER_CREATED_ITEM_CONTENT_TYPE;

		$folderId = Module::kPLAYER_CREATED_ITEM_DEFAULT_PARENT_FOLDER_ID;
		$query = "INSERT INTO {$prefix}_folder_contents 
					(folder_id, content_type, content_id, previous_id)
					VALUES 
					('$folderId', '$pciContentType', '{$newItemID}', '0')";
					
		@mysql_query($query);
		
		return new returnData(0, TRUE);
	}	

	/**
     * Create an item, simultaneously creating media from a file and a location.
     *
     * This service combines several calls into one, avoiding async delays.
     *
     * @param integer $gameId The game identifier
     * @param integer $playerId The player identifier to use as author
     * @param string $name The name
     * @param string $description The html formatted description
     * @param string $fileName The name of the file in the game's data directory that will act as this item's media
     * @param bool $droppable 1 if this item can be dropped, 0 if not 
     * @param bool $destroyable 1 if this item can be detroyed, 0 if not
     * @param float $latitude Latitude where item was created 
     * @param float $longitude Longitude where item was created     
     * @return returnData
     * @returns a returnData object containing the new item identifier
     * @see returnData
     * @see uploadHandler.php
     */
	public static function createItemAndPlaceOnMap($gameId, $playerId, $name, $description, 
								$fileName, $droppable, $destroyable, $latitude, $longitude, $fileType="NORMAL")
	{
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$name = addslashes($name);
		$description = ($description);
		
        //Does game allow players to drop items?
		if ($droppable) { 
			$game = Games::getGame($gameId);
			$droppable = $game->data->allow_player_created_locations;
		}
        
        	if($fileType != "NOTE")
        	{
            		//Create the Media
            		$newMediaResultData = Media::createMedia($gameId, $name, $fileName, 0);
            		$newMediaID = $newMediaResultData->data->media_id;
            		
            		$type = Media::getMediaType($fileName);
            		if($type == "Image"){
                		$iconNum = Module::kPLAYER_CREATED_ITEM_PHOTO_ICON_NUM;
            		}
            		else if($type == "Audio"){
                		$iconNum = Module::kPLAYER_CREATED_ITEM_AUDIO_ICON_NUM;
            		}
            		else if($type == "Video"){
                		$iconNum = Module::kPLAYER_CREATED_ITEM_VIDEO_ICON_NUM;
            		}
            		else{
                		$iconNum = Module::kPLAYER_CREATED_ITEM_DEFAULT_ICON_NUM;
            		}
        	}
        	else
        	{
            		$newMediaId = 0;
            		$iconNum = Module::kPLAYER_CREATED_ITEM_DEFAULT_ICON_NUM;
        	}
		
		
		//Create the Item
		$query = "INSERT INTO {$prefix}_items 
					(name, description, media_id, dropable, destroyable,
					creator_player_id, origin_latitude, origin_longitude, icon_media_id, type)
					VALUES ('{$name}', 
							'{$description}',
							'{$newMediaID}', 
							'$droppable',
							'$destroyable',
							'$playerId', '$latitude', '$longitude',
							'$iconNum', '$fileType')";
		
		NetDebug::trace("createItem: Running a query = $query");	
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());
		
		$newItemID = mysql_insert_id();
		
		Module::appendLog($playerId, $gameId, Module::kLOG_UPLOAD_MEDIA_ITEM, $newItemID);
        $type = Media::getMediaType($fileName);
        if($type == "Image"){
            Module::appendLog($playerId, $gameId, Module::kLOG_UPLOAD_MEDIA_ITEM_IMAGE, $newItemID);
        }
        else if($type == "Audio"){
            Module::appendLog($playerId, $gameId, Module::kLOG_UPLOAD_MEDIA_ITEM_AUDIO, $newItemID);
        }
        else if($type == "Video"){
            Module::appendLog($playerId, $gameId, Module::kLOG_UPLOAD_MEDIA_ITEM_VIDEO, $newItemID);
        }
        
        
		Locations::createLocation($gameId, $name, 0, 
								$latitude, $longitude, 25,
								"Item", $newItemID,
								1, 0, 0, 0);
								
		$pciContentType = Module::kPLAYER_CREATED_ITEM_CONTENT_TYPE;
		
		$folderId = Module::kPLAYER_CREATED_ITEM_DEFAULT_PARENT_FOLDER_ID;
		$query = "INSERT INTO {$prefix}_folder_contents 
					(folder_id, content_type, content_id, previous_id)
					VALUES 
					('$folderId', '$pciContentType', '{$newItemID}', '0')";
					
		@mysql_query($query);
		
		return new returnData(0, TRUE);
	}	
	
	
	/**
     * Updates an item's properties
     *
     * @param integer $gameId The game identifier
     * @param integer $itemId The item identifier
     * @param string $name The new name
     * @param string $description The new html formatted description
     * @param integer $iconMediaId The new icon media identifier
     * @param integer $mediaId The new media identifier
     * @param bool $droppable 1 if this item can be dropped, 0 if not 
     * @param bool $destroyable 1 if this item can be detroyed, 0 if not
     * @param integer $maxQuantityInPlayerInventory The new maximum quantity of this itema player may hold
     * @return returnData
     * @returns a returnData object containing a TRUE if an change was made, FALSE otherwise
     * @see returnData
     */
	public static function updateItem($gameId, $itemId, $name, $description, 
								$iconMediaId, $mediaId, $droppable, $destroyable, $attribute, $maxQuantityInPlayerInventory, $weight, $url, $type)
	{
		$prefix = Module::getPrefix($gameId);
		
		$name = addslashes($name);	
		$description = addslashes($description);	
		
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "UPDATE {$prefix}_items 
					SET name = '{$name}', 
						description = '{$description}', 
						icon_media_id = '{$iconMediaId}',
						media_id = '{$mediaId}', 
						dropable = '{$droppable}',
						destroyable = '{$destroyable}',
                        is_attribute = '{$attribute}',
						max_qty_in_inventory = '{$maxQuantityInPlayerInventory}',
                        weight = '{$weight}',
                        url = '{$url}',
                        type = '{$type}'
					WHERE item_id = '{$itemId}'";
		
		NetDebug::trace("updateNpc: Running a query = $query");	
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);
 		
		if (mysql_affected_rows()) return new returnData(0, TRUE, "Success Running:" . $query);
		else return new returnData(0, FALSE, "Success Running:" . $query);
		

	}
			
	
	/**
     * Deletes an Item from a game, removing any refrence made to it in the rest of the game
     *
     * When this service runs, locations, requirements, playerStatechanges and player inventories
     * are updated to remove any refrence to the deleted item.
     *
     * @param integer $gameId The game identifier
     * @param integer $itemId The item identifier
     * @return returnData
     * @returns a returnData object containing a TRUE if an change was made, FALSE otherwise
     * @see returnData
     */
	public static function deleteItem($gameId, $itemId)
	{
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		Locations::deleteLocationsForObject($gameId, 'Item', $itemId);
		Requirements::deleteRequirementsForRequirementObject($gameId, 'Item', $itemId);
		PlayerStateChanges::deletePlayerStateChangesThatRefrenceObject($gameId, 'Item', $itemId);
		Module::removeItemFromAllPlayerInventories($prefix, $itemId );
		
		$query = "DELETE FROM {$prefix}_items WHERE item_id = {$itemId}";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		if (mysql_affected_rows()) {
			return new returnData(0, TRUE);
		}
		else {
			return new returnData(0, FALSE);
		}
		
	}	












	// \/ \/ \/ BACKPACK FUNCTIONS \/ \/ \/

	/**
	Gets array of JSON encoded 'web backpacks', containing player information relating to items, attributes, and notes gained throughout a game. For an example of its use, see 'getBackPacksFromArray.html'.
	@param: $gameId- An integer representing the game_id of the game information desired.
	@param: $playerArray- Either a JSON encoded array of integer player_ids of all the players whose information is desired, a single integer if only one player's information is desired, or nothing if all player information for an entire game is desired.
	@returns: On success, returns JSON encoded game object with a parameter containing an array of player objects with various parameters describing a player's information.
		  If gameId is empty, returns 'Error- Empty Game' and aborts the function.
		  If game with gameId does not exist, returns 'Error- Invalid Game Id' and aborts the function.
		  If playerArray is anything other than the specified options, returns 'Error- Invalid Player Array' and aborts the function.
	**/
	public static function getPlayerBackpacksFromArray($gameId, $playerArray)
	{
		if(is_numeric($gameId))
			$gameId = intval($gameId);
		else
			return new returnData(1, "Error- Empty Game");

		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, "Error- Invalid Game Id");

		$game = Games::getGameInfoForWebBackPack($gameId);
		if(is_null($playerArray))
		{
			$game->backpacks =  Items::getGameDataBP($gameId);
			return new returnData(0,$game);
		}
		else if(is_array($playerArray))
		{
			$game->backpacks =  Items::getArrayDataBP($gameId, $playerArray);
			return new returnData(0,$game);
		}
		else if(is_numeric($playerArray))
		{
			$game->backpacks = Items::getDataBP($gameId, intval($playerArray));
			return new returnData(0,$game,true);
		}
		else
		{
			return new returnData(1, "Error- Invalid Player Array");
		}
	}

	private static function getGameDataBP($gameId)
	{
		$backPacks = array();
		$query = "SELECT DISTINCT player_id FROM player_log WHERE game_id='{$gameId}'";
		$result = mysql_query($query);
		while($player = mysql_fetch_object($result))
		{
			$backPacks[] = Items::getDataBP($gameId, $player->player_id);
		}
		return $backPacks;
	}

	private static function getArrayDataBP($gameId, $playerArray)
	{
		$backPacks = array();
		foreach($playerArray as $player)
		{
			$backPacks[] = Items::getDataBP($gameId, $player);
		}
		return $backPacks;
	}

	/*
	* Gets information for web backpack for any player/game pair
	*/
	private static function getDataBP($gameId, $playerId, $individual=false)
	{
		$backpack = new stdClass();

		//Get owner information
		$query = "SELECT user_name FROM players WHERE player_id = '{$playerId}'";
		$result = mysql_query($query);
		$name = mysql_fetch_object($result);
		if(!$name) return "Invalid Player ID";
		$backpack->owner=$name;
		$backpack->owner->player_id = $playerId;

		/* ATTRIBUTES */
		$query = "SELECT i.item_id, i.name, i.description, i.max_qty_in_inventory, i.weight, i.type, i.url, pi.qty, m.name as media_name, m.file_name as media_file_name, m.game_id as media_game_id, im.name as icon_name, im.file_name as icon_file_name, im.game_id as icon_game_id FROM {$gameId}_player_items as pi, {$gameId}_items as i LEFT JOIN media as m ON i.media_id = m.media_id LEFT JOIN media as im ON i.icon_media_id = im.media_id WHERE pi.player_id = {$playerId} AND pi.item_id = i.item_id AND i.type = 'ATTRIB'";

		$result = mysql_query($query);
		$contents = array();
		while($content = mysql_fetch_object($result))
			$contents[] = $content;

		$backpack->attributes = $contents;

		/* OTHER ITEMS */
		$query = "SELECT i.item_id, i.name, i.description, i.max_qty_in_inventory, i.weight, i.type, i.url, pi.qty, m.name as media_name, m.file_name as media_file_name, m.game_id as media_game_id, im.name as icon_name, im.file_name as icon_file_name, im.game_id as icon_game_id FROM {$gameId}_player_items as pi, {$gameId}_items as i LEFT JOIN media as m ON i.media_id = m.media_id LEFT JOIN media as im ON i.icon_media_id = im.media_id WHERE pi.player_id = {$playerId} AND pi.item_id = i.item_id AND i.type != 'ATTRIB'";

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
            		$notes[] = Items::getFullNoteObjectBP($note->note_id, $playerId);
        	
		$backpack->notes = $notes;
	
		return $backpack;
	}

	private static function getFullNoteObjectBP($noteId, $playerId=0)
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
			$note->contents = Items::getNoteContentsBP($noteId);
			$note->comments = Items::getNoteCommentsBP($noteId, $playerId);
			$note->tags = Items::getNoteTagsBP($noteId, $note->game_id);
			$note->likes = Items::getNoteLikesBP($noteId);
			$note->player_liked = ($playerId == 0 ? 0 : Items::playerLikedBP($playerId, $noteId));
			$note->icon_media_id = 5;
			return $note;
		}
		return;
	}

	private static function getGameInfoBP($gameId)
	{
		$query = "SELECT games.game_id, games.name, pcm.name as pc_media_name, pcm.file_name as pc_media_url, m.name as media_name, m.file_name as media_url, im.name as icon_media_name, im.file_name as icon_media_url FROM games LEFT JOIN media as m ON games.media_id = m.media_id LEFT JOIN media as im ON games.icon_media_id = im.media_id LEFT JOIN media as pcm on games.pc_media_id = pcm.media_id WHERE games.game_id = '{$gameId}'";

		$result = mysql_query($query);
		$game = mysql_fetch_object($result);
		if(!$game) return "Invalid Game ID"; 

		$query = "SELECT editors.name FROM game_editors JOIN editors ON editors.editor_id = game_editors.editor_id WHERE game_editors.game_id = '{$gameId}'";
		$result = mysql_query($query);
		$auth = array();

		while($a = mysql_fetch_object($result))
			$auth[] = $a;

		$game->authors = $auth;

		return $game;
	}

	private static function getNoteContentsBP($noteId)
	{
		$query = "SELECT nc.media_id, nc.type, nc.text, nc.game_id, nc.title, m.file_name, m.game_id FROM note_content as nc LEFT JOIN media as m ON nc.media_id = m.media_id WHERE note_id = '{$noteId}'";
		$result = mysql_query($query);
        
		$contents = array();
		while($content = mysql_fetch_object($result))
			$contents[] = $content;
        	
		return $contents;
	}

	private static function getNoteCommentsBP($noteId, $playerId)
	{
		$query = "SELECT note_id FROM notes WHERE parent_note_id = '{$noteId}'";
		$result = mysql_query($query);
	
		$comments = array();
		while($commentNoteId = mysql_fetch_object($result))
		{
			$comment = Items::getFullNoteObjectBP($commentNoteId->note_id, $playerId);
			$comments[] = $comment;
		}
		return $comments;
	}
	
	private static function getNoteTagsBP($noteId, $gameId)
	{
		$query = "SELECT note_tags.tag, player_created FROM note_tags LEFT JOIN ((SELECT tag, player_created FROM game_tags WHERE game_id = '{$gameId}') as gt) ON note_tags.tag = gt.tag WHERE note_id = '{$noteId}'";
		$result = mysql_query($query);
		$tags = array();
		while($tag = mysql_fetch_object($result))	
			$tags[] = $tag;
		return $tags;
	}
	
	private static function getNoteLikesBP($noteId)
	{
		$query = "SELECT COUNT(*) as numLikes FROM note_likes WHERE note_id = '{$noteId}'";
		$result  = mysql_query($query);
		$likes = mysql_fetch_object($result);
		return $likes->numLikes;
	}
	
	private static function playerLikedBP($playerId, $noteId)
	{
		$query = "SELECT COUNT(*) as liked FROM note_likes WHERE player_id = '{$playerId}' AND note_id = '{$noteId}' LIMIT 1";
		$result = mysql_query($query);
		$liked = mysql_fetch_object($result);
		return $liked->liked;
	}
}
