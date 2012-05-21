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

	/*
	* Gets information for web backpack for any player/game pair
	*/

	public static function getInfoForWebBackPack($gameId, $playerId)
	{
		$backPack = new stdClass();

		$prefix = Module::getPrefix($gameId);
		if(!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT user_name FROM players WHERE player_id = '{$playerId}'";
		$result = mysql_query($query);
		$name = mysql_fetch_object($result);
		if(!$name) return new returnData(1,NULL,"invalid player id");

		$backPack->owner=$name;

		$query = "SELECT DISTINCT i.item_id, i.name, i.description, i.is_attribute, i.max_qty_in_inventory, i.weight, i.type, i.url, pi.qty, m.name as media_name, m.file_name as media_file_name, m.game_id as media_game_id, im.name as icon_name, im.file_name as icon_file_name, im.game_id as icon_game_id FROM {$prefix}_player_items as pi, {$prefix}_items as i LEFT JOIN media as m ON i.media_id = m.media_id OR i.media_id = 0 LEFT JOIN media as im ON i.icon_media_id = im.media_id OR i.icon_media_id = 0 WHERE pi.player_id = {$playerId} AND pi.item_id = i.item_id";
		$result = mysql_query($query);
		$contents = array();
		while($content = mysql_fetch_object($result))
			$contents[] = $content;

		$backPack->contents = $contents;
		return $backPack;
	}
}
