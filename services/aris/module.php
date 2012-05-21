<?php
require_once('../../config.class.php');
require_once('returnData.class.php');
require_once('qrcodes.php');

abstract class Module
{
	//constants for player_log table enums
	const kLOG_LOGIN = 'LOGIN';
	const kLOG_MOVE = 'MOVE';
	const kLOG_PICKUP_ITEM = 'PICKUP_ITEM';
	const kLOG_DROP_ITEM = 'DROP_ITEM';
	const kLOG_DROP_NOTE = 'DROP_NOTE';
	const kLOG_DESTROY_ITEM = 'DESTROY_ITEM';
	const kLOG_VIEW_ITEM = 'VIEW_ITEM';
	const kLOG_VIEW_NODE = 'VIEW_NODE';
	const kLOG_VIEW_NPC = 'VIEW_NPC';
    const kLOG_VIEW_WEBPAGE = 'VIEW_WEBPAGE';
    const kLOG_VIEW_AUGBUBBLE = 'VIEW_AUGBUBBLE';
	const kLOG_VIEW_MAP = 'VIEW_MAP';
	const kLOG_VIEW_QUESTS = 'VIEW_QUESTS';
	const kLOG_VIEW_INVENTORY = 'VIEW_INVENTORY';
	const kLOG_ENTER_QRCODE = 'ENTER_QRCODE';
	const kLOG_UPLOAD_MEDIA_ITEM = 'UPLOAD_MEDIA_ITEM';
    const kLOG_UPLOAD_MEDIA_ITEM_IMAGE = 'UPLOAD_MEDIA_ITEM_IMAGE';
	const kLOG_UPLOAD_MEDIA_ITEM_AUDIO = 'UPLOAD_MEDIA_ITEM_AUDIO';
	const kLOG_UPLOAD_MEDIA_ITEM_VIDEO = 'UPLOAD_MEDIA_ITEM_VIDEO';

    const kLOG_RECEIVE_WEBHOOK = 'RECEIVE_WEBHOOK';
    const kLOG_COMPLETE_QUEST = 'COMPLETE_QUEST';
	
	//constants for gameID_requirements table enums
	const kREQ_PLAYER_HAS_ITEM = 'PLAYER_HAS_ITEM';
	const kREQ_PLAYER_VIEWED_ITEM = 'PLAYER_VIEWED_ITEM';
	const kREQ_PLAYER_VIEWED_NODE = 'PLAYER_VIEWED_NODE';
	const kREQ_PLAYER_VIEWED_NPC = 'PLAYER_VIEWED_NPC';
    const kREQ_PLAYER_VIEWED_WEBPAGE = 'PLAYER_VIEWED_WEBPAGE';
    const kREQ_PLAYER_VIEWED_AUGBUBBLE = 'PLAYER_VIEWED_AUGBUBBLE';
	const kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM = 'PLAYER_HAS_UPLOADED_MEDIA_ITEM';
    const kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE = 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE';
	const kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO = 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO';
    const kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO = 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO';
    const kREQ_PLAYER_HAS_COMPLETED_QUEST = 'PLAYER_HAS_COMPLETED_QUEST';
    const kREQ_PLAYER_HAS_RECEIVED_INCOMING_WEBHOOK = 'PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK';
	
	const kRESULT_DISPLAY_NODE = 'Node';
	const kRESULT_DISPLAY_QUEST = 'QuestDisplay';
	const kRESULT_COMPLETE_QUEST = 'QuestComplete';
	const kRESULT_DISPLAY_LOCATION = 'Location';
    const kRESULT_EXECUTE_WEBHOOK = 'OutgoingWebhook';

	//constants for player_state_changes table enums
	const kPSC_GIVE_ITEM = 'GIVE_ITEM';
	const kPSC_TAKE_ITEM = 'TAKE_ITEM';	
	
	//constants for player created items (pictures, etc...)
	const kPLAYER_CREATED_ITEM_CONTENT_TYPE = 'Item';
	const kPLAYER_CREATED_ITEM_DEFAULT_ICON_NUM = '2';
	const kPLAYER_CREATED_ITEM_PHOTO_ICON_NUM = 36;
	const kPLAYER_CREATED_ITEM_AUDIO_ICON_NUM = 34;
	const kPLAYER_CREATED_ITEM_VIDEO_ICON_NUM = 35;
	const kPLAYER_CREATED_ITEM_DEFAULT_PARENT_FOLDER_ID = '-1';
	
	
	public function Module()
	{
		$this->conn = @mysql_connect(Config::dbHost, Config::dbUser, Config::dbPass);
		if (!$this->conn) {
			Module::serverErrorLog("Problem Connecting to MySQL: " . mysql_error());
			if(Config::adminEmail) Module::sendEmail(Config::adminEmail,"ARIS Server Error", mysql_error());
		}
      	mysql_select_db (Config::dbSchema);
      	mysql_query("set names utf8");
		mysql_query("set charset set utf8");
	}	
	
	/**
     * Fetch the prefix of a game
     * @returns a prefix string without the trailing _
     */
	protected function getPrefix($intGameID) {	
		//Lookup game information
		$query = "SELECT prefix FROM games WHERE game_id = '{$intGameID}' LIMIT 1";
		//NetDebug::trace($query);
		$rsResult = @mysql_query($query);
		if (mysql_num_rows($rsResult) < 1) return FALSE;
		$gameRecord = mysql_fetch_array($rsResult);
		return substr($gameRecord['prefix'],0,strlen($gameRecord['prefix'])-1);
		
	}
	
	/**
     * Fetch the GameID from a prefix
     * @returns a gameID int
     */
	protected function getGameIdFromPrefix($strPrefix) {	
		//Lookup game information
		$query = "SELECT game_id FROM games WHERE prefix= '{$strPrefix}_'";
		$rsResult = @mysql_query($query);
		if (mysql_num_rows($rsResult) < 1) return FALSE;
		$gameRecord = mysql_fetch_array($rsResult);
		return $gameRecord['game_id'];
		
	}	
	
	
	
    /**
     * Adds the specified item to the specified player. Returns the actual number added after concidering item max
     */
     protected function giveItemToPlayer($strGamePrefix, $intItemID, $intPlayerID, $qtyToGive=1) {
		$currentQty = Module::itemQtyInPlayerInventory($strGamePrefix, $intPlayerID, $intItemID);
		$item = Items::getItem($strGamePrefix, $intItemID)->data;
		$maxQty = $item->max_qty_in_inventory; 
		
		NetDebug::trace("Module: giveItemToPlayer: Player currently has $currentQty - Item max is $maxQty");

		
		if ($currentQty + $qtyToGive > $maxQty  && $maxQty != -1) {
			//we are going over the limit
			$qtyToGive =  $maxQty - $currentQty;
			NetDebug::trace("Module: giveItemToPlayer: Attempted to go over item max qty. Request change to $qtyToGive");
		}
		
		if ($qtyToGive < 1) return 0;
		else {
			Module::adjustQtyForPlayerItem($strGamePrefix, $intItemID, $intPlayerID, $qtyToGive);
			return $qtyToGive;
		}
    }
	
	
	/**
     * Removes the specified item from the user.
     */ 
    protected function takeItemFromPlayer($strGamePrefix, $intItemID, $intPlayerID, $qtyToTake=1) {
		Module::adjustQtyForPlayerItem($strGamePrefix, $intItemID, $intPlayerID, -$qtyToTake);
    }
 

     protected function removeItemFromAllPlayerInventories($strGamePrefix, $intItemID ) {
		$query = "DELETE FROM {$strGamePrefix}_player_items 
					WHERE item_id = $intItemID";
    	$result = @mysql_query($query);
    	NetDebug::trace($query . mysql_error());    
    }
 
    /**
    * Updates the qty a player has of an item
    */ 
    protected function adjustQtyForPlayerItem($strGamePrefix, $intItemID, $intPlayerID, $amountOfAdjustment) {
		
        //PHIL_REQ_CODE:
        // This is the only time these functions are called OTHER than via appendLog. This is necessary, as this is the only function that
        // has potential for completing a quest and DOESN'T append anything to the log. 
        // See 'appendLog' for a detailed description of these functions.
        //
        // Note- these functions have built in special functionality in dealing with kLOG_PICKUP_ITEM that DOES directly append the quest completed
        // log. So nothing need be done with either $qObs nor $wObs.
        $qObs = Module::appendCompletedQuestsIfReady($intPlayerID, $strGamePrefix, Module::kLOG_PICKUP_ITEM, $intItemID, $amountOfAdjustment);
        $wObs = Module::fireOffWebHooksIfReady($intPlayerID, $strGamePrefix, Module::kLOG_PICKUP_ITEM, $intItemID, $amountOfAdjustment);
        
		//Get any existing record
		$query = "SELECT * FROM {$strGamePrefix}_player_items 
					WHERE player_id = $intPlayerID AND item_id = $intItemID LIMIT 1";
    	$result = @mysql_query($query);
    	NetDebug::trace($query . mysql_error());

    	if ($existingPlayerItem = @mysql_fetch_object($result)) {
    		NetDebug::trace("We have an existing record for that player and item");

 			//Check if this change will make the qty go to < 1, if so delete the record
 			$newQty = $existingPlayerItem->qty + $amountOfAdjustment;
 			if ($newQty < 1) {
 				NetDebug::trace("Adjustment would result in a qty of $newQty so delete the record");
 				$query = "DELETE FROM {$strGamePrefix}_player_items 
					WHERE player_id = $intPlayerID AND item_id = $intItemID";
    			NetDebug::trace($query);
    			@mysql_query($query);
    		}
    		else {
 				//Update the qty
 				NetDebug::trace("Updating Qty to $newQty");
 				$query = "UPDATE {$strGamePrefix}_player_items 
 							SET qty = $newQty
							WHERE player_id = $intPlayerID AND item_id = $intItemID";
    			NetDebug::trace($query);
    			@mysql_query($query);
 			}
    	}
    	else if ($amountOfAdjustment > 0) {
    		//Create a record
    		NetDebug::trace("Creating a new player_item record");

    		$query = "INSERT INTO {$strGamePrefix}_player_items 
										  (player_id, item_id, qty) VALUES ($intPlayerID, $intItemID, $amountOfAdjustment)
										  ON duplicate KEY UPDATE item_id = $intItemID";
			NetDebug::trace($query);
			@mysql_query($query);
    	}
    	else NetDebug::trace("Decrementing the qty of an item the player does not have. Ignored.");
    	
    }
    
	
	/**
     * Decrement the item_qty at the specified location by the specified amount, default of 1
     */ 
    protected function decrementItemQtyAtLocation($strGamePrefix, $intLocationID, $intQty = 1) {
   		//If this location has a null item_qty, decrementing it will still be a null
		$query = "UPDATE {$strGamePrefix}_locations 
					SET item_qty = item_qty-{$intQty}
					WHERE location_id = '{$intLocationID}' AND item_qty > 0";
   		NetDebug::trace($query);	
    	@mysql_query($query);    	
	}
	
	
	/**
     * Adds an item to Locations at the specified latitude, longitude
     */ 
    protected function giveItemToWorld($strGamePrefix, $intItemID, $floatLat, $floatLong, $intQty = 1) {
		//Find any items on the map nearby
		$clumpingRangeInMeters = 10;
		
		$query = "SELECT *,((ACOS(SIN($floatLat * PI() / 180) * SIN(latitude * PI() / 180) + 
					COS($floatLat * PI() / 180) * COS(latitude * PI() / 180) * 
					COS(($floatLong - longitude) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) * 1609.344
				AS `distance`, location_id 
				FROM {$strGamePrefix}_locations 
				WHERE type = 'item' AND type_id = '{$intItemID}'
				HAVING distance<= {$clumpingRangeInMeters}
				ORDER BY distance ASC"; 	
    	$result = @mysql_query($query);
    	NetDebug::trace($query . ' ' . mysql_error());  
    	
    	if ($closestLocationWithinClumpingRange = @mysql_fetch_object($result)) {
    		//We have a match
    		NetDebug::trace("An item exists nearby, adding to that location");   	

    		$query = "UPDATE {$strGamePrefix}_locations
    				SET item_qty = item_qty + {$intQty}
    				WHERE location_id = {$closestLocationWithinClumpingRange->location_id}";
    		NetDebug::trace($query . ' ' . mysql_error());  
    		@mysql_query($query);
    	}
		else {
			NetDebug::trace("No item exists nearby, creating a new location");   	

			$itemName = $this->getItemName($strGamePrefix, $intItemID);
			$error = 100; //Use 100 meters
			$icon_media_id = $this->getItemIconMediaId($strGamePrefix, $intItemID); //Set the map icon = the item's icon
			
			$query = "INSERT INTO {$strGamePrefix}_locations (name, type, type_id, icon_media_id, latitude, longitude, error, item_qty)
											  VALUES ('{$itemName}','Item','{$intItemID}', '{$icon_media_id}', '{$floatLat}','{$floatLong}', '{$error}','{$intQty}')";
    		NetDebug::trace($query . ' ' . mysql_error());  
    		@mysql_query($query);
    		
    		$newId = mysql_insert_id();
    		//Create a coresponding QR Code
			QRCodes::createQRCode($strGamePrefix, "Location", $newId, '');
    	}
    }
	
	
	/**
     * Adds a note to Locations at the specified latitude, longitude
     */ 
    protected function giveNoteToWorld($strGamePrefix, $noteId, $floatLat, $floatLong) {
		
		$query = "SELECT * FROM {$strGamePrefix}_locations 
				WHERE type = 'PlayerNote' AND type_id = '{$noteId}'";	
    	$result = @mysql_query($query);
    	NetDebug::trace($query . ' ' . mysql_error());  
    	
    	if ($existingNote = @mysql_fetch_object($result)) {
    		//We have a match
    		NetDebug::trace("This note has already been placed");   	

    		$query = "UPDATE {$strGamePrefix}_locations
    				SET latitude = '{$floatLat}', longitude = '{$floatLong}'
    				WHERE location_id = {$existingNote->location_id}";
    		NetDebug::trace($query . ' ' . mysql_error());  
    		@mysql_query($query);
    	}
		else {
			NetDebug::trace("Note has not yet been placed");   	

			$error = 100; //Use 100 meters
			$query = "SELECT title FROM notes WHERE note_id = '{$noteId}'";
			$result = @mysql_query($query);
        	$obj = @mysql_fetch_object($result);
			$title = $obj->title;
			
			$query = "INSERT INTO {$strGamePrefix}_locations (name, type, type_id, icon_media_id, latitude, longitude, error, item_qty)
											  VALUES ('{$title}','PlayerNote','{$noteId}', '71', '{$floatLat}','{$floatLong}', '{$error}','1')";
    		NetDebug::trace($query . ' ' . mysql_error());  
    		@mysql_query($query);
    		
    		$newId = mysql_insert_id();
    		//Create a coresponding QR Code
			QRCodes::createQRCode($strGamePrefix, "Location", $newId, '');
    	}
    }
	
	protected function metersBetweenLatLngs($lat1, $lon1, $lat2, $lon2) { 

		$theta = $lon1 - $lon2; 
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)); 
		$dist = acos($dist); 
		$dist = rad2deg($dist); 
		$miles = $dist * 60 * 1.1515;
	 	$unit = strtoupper($unit);
		return ($miles * 1609.344); //convert to meters
	}
	
    
    /**
    * Checks if a record Exists
    **/
    protected function recordExists($strPrefix, $strTable, $intRecordID){
    	$key = substr($strTable, 0, strlen($strTable)-1);
    	$query = "SELECT * FROM {$strPrefix}_{$strTable} WHERE {$key} = $intRecordID";
    	$rsResult = @mysql_query($query);
		if (mysql_error()) return FALSE;
		if (mysql_num_rows($rsResult) < 1) return FALSE;
		return true;
    }
	
	/**
    * Looks up an item name
    **/
    protected function getItemName($strPrefix, $intItemID){
    	$query = "SELECT name FROM {$strPrefix}_items WHERE item_id = $intItemID";
    	$rsResult = @mysql_query($query);		
		$row = @mysql_fetch_array($rsResult);	
		return $row['name'];
    }
    
 	/**
    * Looks up an item icon media id
    **/
    protected function getItemIconMediaId($strPrefix, $intItemID){
    	$query = "SELECT name FROM {$strPrefix}_items WHERE item_id = $intItemID";
    	$rsResult = @mysql_query($query);		
		$row = @mysql_fetch_array($rsResult);	
		return $row['icon_media_id'];
    }   
		
	/** 
	 * playerHasLog
	 *
     * Checks if the specified user has the specified log event in the game
	 *
     * @return boolean
     */
    protected function playerHasLog($strPrefix, $intPlayerID, $strEventType, $strEventDetail) {

		$intGameID = Module::getGameIdFromPrefix($strPrefix);

		$query = "SELECT 1 FROM player_log 
					WHERE player_id = '{$intPlayerID}' AND
						game_id = '{$intGameID}' AND
						event_type = '{$strEventType}' AND
						event_detail_1 = '{$strEventDetail}' AND
						deleted = 0
					LIMIT 1";
				
		//NetDebug::trace($query);
		$rsResult = @mysql_query($query);
		if (mysql_num_rows($rsResult) > 0) return true;
		else return false;	

    }
    

	/**
     * Checks if a player has an item with a minimum quantity
     *
     * @param integer $gameId The game identifier
     * @param integer $playerID The player identifier
     * @param integer $itemId The item identifier
     * @param integer $minItemQuantity The minimum quantity to qualify, 1 if unspecified
     * @return bool
     * @returns TRUE if the player has >= the minimum quantity, FALSE otherwise
     */     
    protected function playerHasItem($gameID, $playerID, $itemID, $minItemQuantity) {
    	if (!$minItemQuantity) $minItemQuantity = 1;
    	//NetDebug::trace("checking if player $playerID has atleast $minItemQuantity of item $itemID in inventory");		
    	$qty = Module::itemQtyInPlayerInventory($gameID, $playerID, $itemID);
    	if ($qty >= $minItemQuantity) return TRUE;
		else return false;
    }		
    
    
	/**
     * Checks the quantity a player has of an item in their inventory
     *
     * @param integer $gameId The game identifier
     * @param integer $playerId The player identifier
     * @param integer $itemId The item identifier
     * @return integer
     * @returns the quantity of the item in the player's inventory
     */       
    protected function itemQtyInPlayerInventory($gameId, $playerId, $itemId) {
    	$prefix = Module::getPrefix($gameId);
		if (!$prefix) return FALSE;
    
		$query = "SELECT qty FROM {$prefix}_player_items 
									  WHERE player_id = '{$playerId}' 
									  AND item_id = '{$itemId}' LIMIT 1";
		
		$rsResult = @mysql_query($query);
		$playerItem = mysql_fetch_object($rsResult);
		if ($playerItem) {
			return $playerItem->qty;
		}
		else {
			return 0;
		}
    }	    
    
	/** 
	 * playerHasUploadedMedia
	 *
     * Checks if the specified user has uploaded media near the specified location.
     * NOTE- $mediaType should be Module::kLOG_UPLOAD_MEDIA_ITEM_IMAGE, Module::kLOG_UPLOAD_MEDIA_ITEM_AUDIO, Module::kLOG_UPLOAD_MEDIA_ITEM_VIDEO, or just
     * Module::kLOG_UPLOAD_MEDIA_ITEM for any
     * @return boolean
     */
    
    //Spelled 'distAnce' wrong in function name and variable name... afraid to change it... the repurcussions could be ASTRONOMICAL.
    protected function playerHasUploadedMediaItemWithinDistence($intGameID, $intPlayerID, $dblLatitude, $dblLongitude, $dblDistenceInMeters, $qty, $mediaType) {
    	$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return FALSE;

		$query = "SELECT {$prefix}_items.*
					FROM player_log, {$prefix}_items
					WHERE 
						player_log.player_id = '{$intPlayerID}' AND
						player_log.game_id = '{$intGameID}' AND
						player_log.event_type = '". $mediaType ."' AND
						player_log.event_detail_1 = {$prefix}_items.item_id AND
						player_log.deleted = 0 AND
						
						(((acos(sin(({$dblLatitude}*pi()/180)) * sin((origin_latitude*pi()/180))+cos(({$dblLatitude}*pi()/180)) * 
						cos((origin_latitude*pi()/180)) * 
						cos((({$dblLongitude} - origin_longitude)*pi()/180))))*180/pi())*60*1.1515*1.609344*1000) < {$dblDistenceInMeters}";						
						
		NetDebug::trace($query);
		$rsResult = @mysql_query($query);
		if (@mysql_num_rows($rsResult) >= $qty) return true;
		else return false;

    }	    
    
	/** 
	 * objectMeetsRequirements
	 *
     * Checks all requirements for the specified object for the specified user
     * @return boolean
     */	
	protected function objectMeetsRequirements ($strPrefix, $intPlayerID, $strObjectType, $intObjectID) {		
		//NetDebug::trace("Checking Requirements for {$strObjectType}:{$intObjectID} for playerID:$intPlayerID in gameID:$strPrefix");

		//Fetch the requirements
		$query = "SELECT requirement,
			requirement_detail_1,requirement_detail_2,requirement_detail_3,
			boolean_operator, not_operator
			FROM {$strPrefix}_requirements 
			WHERE content_type = '{$strObjectType}' AND content_id = '{$intObjectID}'";
		$rsRequirments = @mysql_query($query);
		
		$andsMet = FALSE;
		$requirementsExist = FALSE;
		while ($requirement = mysql_fetch_array($rsRequirments)) {
			$requirementsExist = TRUE;
			//NetDebug::trace("Requirement for {$strObjectType}:{$intObjectID} is {$requirement['requirement']}:{$requirement['requirement_detail_1']}");
			//Check the requirement
			
			$requirementMet = FALSE;
			switch ($requirement['requirement']) {
				//Log related
				case Module::kREQ_PLAYER_VIEWED_ITEM:
					$requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_VIEW_ITEM, 
						$requirement['requirement_detail_1']);
					break;
				case Module::kREQ_PLAYER_VIEWED_NODE:
					$requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_VIEW_NODE, 
						$requirement['requirement_detail_1']);
					break;
				case Module::kREQ_PLAYER_VIEWED_NPC:
					$requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_VIEW_NPC, 
                                                           $requirement['requirement_detail_1']);
					break;
                		case Module::kREQ_PLAYER_VIEWED_WEBPAGE:
					$requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_VIEW_WEBPAGE, 
                                                           $requirement['requirement_detail_1']);
					break;
                		case Module::kREQ_PLAYER_VIEWED_AUGBUBBLE:
					$requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_VIEW_AUGBUBBLE, 
                                                           $requirement['requirement_detail_1']);
					break;
                		case Module::kREQ_PLAYER_HAS_RECEIVED_INCOMING_WEBHOOK:
					$requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_RECEIVE_WEBHOOK, 
                                                            $requirement['requirement_detail_1']);
					break;
				//Inventory related	
				case Module::kREQ_PLAYER_HAS_ITEM:
					$requirementMet = Module::playerHasItem($strPrefix, $intPlayerID, 
						$requirement['requirement_detail_1'], $requirement['requirement_detail_2']);
					break;
				//Data Collection
				case Module::kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM:
					$requirementMet = Module::playerHasUploadedMediaItemWithinDistence($strPrefix, $intPlayerID, 
						$requirement['requirement_detail_3'], $requirement['requirement_detail_4'], 
                                                                                       $requirement['requirement_detail_1'], $requirement['requirement_detail_2'], Module::kLOG_UPLOAD_MEDIA_ITEM);
					break;
                		case Module::kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO:
                    			NetDebug::trace("isAudio");
					$requirementMet = Module::playerHasUploadedMediaItemWithinDistence($strPrefix, $intPlayerID, 
                                                                                       $requirement['requirement_detail_3'], $requirement['requirement_detail_4'], 
                                                                                       $requirement['requirement_detail_1'], $requirement['requirement_detail_2'], Module::kLOG_UPLOAD_MEDIA_ITEM_AUDIO);
                    			NetDebug::trace($requirementMet);
					break;
                		case Module::kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO:
					$requirementMet = Module::playerHasUploadedMediaItemWithinDistence($strPrefix, $intPlayerID, 
                                                                                       $requirement['requirement_detail_3'], $requirement['requirement_detail_4'], 
                                                                                       $requirement['requirement_detail_1'], $requirement['requirement_detail_2'], Module::kLOG_UPLOAD_MEDIA_ITEM_VIDEO);
					break;
                		case Module::kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE:
					$requirementMet = Module::playerHasUploadedMediaItemWithinDistence($strPrefix, $intPlayerID, 
                                                                                       $requirement['requirement_detail_3'], $requirement['requirement_detail_4'], 
                                                                                       $requirement['requirement_detail_1'], $requirement['requirement_detail_2'], Module::kLOG_UPLOAD_MEDIA_ITEM_IMAGE);
					break;
				case Module::kREQ_PLAYER_HAS_COMPLETED_QUEST:
					$requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_COMPLETE_QUEST, 
                                                           $requirement['requirement_detail_1']);
					break;	
			}//switch
            
            		//Account for the 'NOT's
			if($requirement['not_operator'] == "NOT") $requirementMet = !$requirementMet;

			if ($requirement['boolean_operator'] == "AND" && $requirementMet == FALSE) {
				//NetDebug::trace("An AND requirement was not met. Requirements Failed.");
				return FALSE;
			}

			if ($requirement['boolean_operator'] == "AND" && $requirementMet == TRUE) {
				//NetDebug::trace("An AND requirement was met. Remembering");
				$andsMet = TRUE;
			}
			
			if ($requirement['boolean_operator'] == "OR" && $requirementMet == TRUE){
				//NetDebug::trace("An OR requirement was met. Requirements Passed.");
				return TRUE;
			}
			
			if ($requirement['boolean_operator'] == "OR" && $requirementMet == FALSE){
                		$requirementsMet = FALSE;
            		}

		}
        
		if (!$requirementsExist) {
			//NetDebug::trace("No requirements exist. Requirements Passed.");
			return TRUE;
		}
		if ($andsMet) {
			//NetDebug::trace("All AND requirements exist. Requirements Passed.");
			return TRUE;
		}
		else {
			//NetDebug::trace("At end. Requirements Not Passed.");			
			return FALSE;
		}
	}	
	
	
	/** 
	 * applyPlayerStateChanges
	 *
     * Applies any state changes for the given object
     * @return boolean. True if a change was made, false otherwise
     */	
	protected function applyPlayerStateChanges($strPrefix, $intPlayerID, $strEventType, $strEventDetail) {	
		
		$changeMade = FALSE;
		
		//Fetch the state changes
		$query = "SELECT * FROM {$strPrefix}_player_state_changes 
									  WHERE event_type = '{$strEventType}'
									  AND event_detail = '{$strEventDetail}'";
		NetDebug::trace($query);

		$rsStateChanges = @mysql_query($query);
		
		while ($stateChange = mysql_fetch_array($rsStateChanges)) {
			NetDebug::trace("State Change Found");

			//Check the requirement
			switch ($stateChange['action']) {
				case Module::kPSC_GIVE_ITEM:
					//echo 'Running a GIVE_ITEM';
					Module::giveItemToPlayer($strPrefix, $stateChange['action_detail'], $intPlayerID,$stateChange['action_amount']);
					$changeMade = TRUE;
					break;
				case Module::kPSC_TAKE_ITEM:
					//echo 'Running a TAKE_ITEM';
					Module::takeItemFromPlayer($strPrefix, $stateChange['action_detail'], $intPlayerID,$stateChange['action_amount']);
					$changeMade = TRUE;
					break;
			}
		}//stateChanges loop
		
		return $changeMade;
	}
		
    /**
     * Add a row to the player log
     * @returns true on success
     */
	protected function appendLog($intPlayerID, $intGameID, $strEventType, $strEventDetail1=null, $strEventDetail2=null)
	{
        /*
         READ THIS TO UNDERSTAND HOW THIS RIDICULOUS CODE WORKS
         ------------------------------------------------------
         The following code's purpose is to know when to append 'quest complete' or when to fire off a web hook. The reason these both require special code 
         is because they are one time EVENTS that rely on a current STATE (where the requirements for a location for example, define a STATE (location showing) 
         as a result of a current STATE (requirements complete)).
         
         Simply put- for any event incoming to the log, this code checks if that event is the last one required to complete a quest or fire a web hook. If so,
         it does it. 
         
         However, it gets much more complicated with edge cases, which I will comment details about inline with the code, prefixed with the string 
         '//PHIL_REQ_CODE:' so the relevant comments can be quickly searched for.
         
         Few have read this code and lived to understand it. Good luck, and God speed.
         */
        if($intGameID != ""){
            //PHIL_REQ_CODE:
            // $qObs is a list of 'quest objects' that need to be appended to the log. The reason they are bubbled up 
            // to this level of the call stack rather than simply appended upon their discovery is for chained quests.
            // Since appendLog only checks if the current quest being appended is THE (singular) last necessary appendation to complete
            // a quest, if it were recursively called, the thing that was necessary to complete the first quest has yet to be 
            // appended, so it would return false. 
            // Simply, it finds all the quests that are being completed, stores them here, appends the thing that completed them, 
            // and then further down in this function they are finally (recursively) called to be appended in case they are the last thing to 
            // complete another quest.
            $qObs = Module::appendCompletedQuestsIfReady($intPlayerID, $intGameID, $strEventType, $strEventDetail1, $strEventDetail2);
            //PHIL_REQ_CODE:
            // $wObs is a list of 'web hook objects' to be fired. They (unlike $qObs) CAN be fired further down the call stack, 
            // and so they are. They are bubbled up similarily to $qObs as well because they largely use the same code, and it 
            // doesn't hurt. But currently, nothing need be done with $wObs.
            $wObs = Module::fireOffWebHooksIfReady($intPlayerID, $intGameID, $strEventType, $strEventDetail1, $strEventDetail2);
        }
        else{
            NetDebug::trace("GameID = -" .$intGameID . "-");
        }
        
        
		$query = "INSERT INTO player_log 
        (player_id, game_id, event_type, event_detail_1,event_detail_2) 
        VALUES 
        ({$intPlayerID},{$intGameID},'{$strEventType}','{$strEventDetail1}','{$strEventDetail2}')";
		
		@mysql_query($query);
		
		NetDebug::trace($query);
        
		
		if (mysql_error()) {
			NetDebug::trace(mysql_error());
			return false;
		}
		
        if($qObs != "NO")
        {
            //PHIL_REQ_CODE:
            // This appends the quests that were completed as a result of whatever was just appended to the log.
            // It does this recursively for the case that the completion of these also completes a quest. 
            foreach($qObs as $key => $qOb){
                Module::appendLog($qOb->pid, $qOb->gid, "COMPLETE_QUEST", $qOb->id, 'N/A');
            }
        }
        
		else return true;
	}	

	    //PHIL_REQ_CODE:
    // Takes as input an event, and checks to see if that event is sufficient to complete ANY quests for a certain user. Returns
    // an array of 'Quest Objects', or "NO" if no quests are to be completed.
    // NOTE: this function is called 'appendCompletedQuestsIfReady'.
    // It CALLS a function called    'appendCompletedQuestIfReady' for every one of the questS. (<- capitol 'S' intentional for emphasis)
    //
    //
    // *** This function is called from 'appendLog' 99% of the time. The only other place is from inventory changes. (more explanation there)
    protected function appendCompletedQuestsIfReady($intPlayerId, $intGameID, $strEventType, $strEventDetail1, $strEventDetail2){
        
        if($strEventDetail1 == null) $strEventDetail1 = "N/A";
        if($strEventDetail2 == null) $strEventDetail2 = "N/A";
        
        
        $query = "SELECT * FROM {$intGameID}_quests";
        $result = @mysql_query($query);
        
        $qObs = array();
        while($quest = mysql_fetch_object($result)){
            $qOb = Module::appendCompletedQuestIfReady($intPlayerId, $intGameID, $strEventType, $strEventDetail1, $strEventDetail2, $quest->quest_id);
            if($qOb != "NO") $qObs[] = $qOb; //PHIL_REQ_CODE: Only adds quest if the event being passed into it will complete it. Otherwise, the function returns "NO".
        }
        if(count($qObs)==0) return "NO";
        else return $qObs;
    }
    
    //PHIL_REQ_CODE:
    // Takes as input an event AND a quest ID. Checks if the current event completes THAT quest. If yes, returns a bunch of data
    // about the quest called a 'Quest Object', and if not, returns "NO".
    protected function appendCompletedQuestIfReady($intPlayerId, $intGameID, $strEventType, $strEventDetail1, $strEventDetail2, $intQid){
        
        //PHIL_REQ_CODE:
        // $unfinishedBusiness contains two parts-
        //  unfinishedORRequirements contains all unfinished OR requirements. If ANY of these equal the event happening, this completes the quest.
        //  unfinishedANDRequirements ''    ''  ''  ''  ''  AND requirements. If there is only ONE of these, AND it equals the event happening, this completes the quest.
        $unfinishedBusiness = Module::getOutstandingRequirements($intGameID, $intPlayerId, 'QuestComplete', $intQid);
        
        for($x = 0; $x < count($unfinishedBusiness->unfinishedORRequirements); $x++){
            if($strEventDetail1 == $unfinishedBusiness->unfinishedORRequirements[$x]['requirement_detail_1']){

                //PHIL_REQ_CODE: Weird special calculations in case that event type is dealing with inventory
                if(($strEventType == Module::kLOG_PICKUP_ITEM && $unfinishedBusiness->unfinishedORRequirements[$x]['event'] == Module::kLOG_PICKUP_ITEM && $strEventDetail2 >= 0) || 
                   ($strEventType == Module::kLOG_DROP_ITEM && $unfinishedBusiness->unfinishedORRequirements[$x]['event'] == Module::kLOG_DROP_ITEM && $strEventDetail2 < 0)){
                    $query = "SELECT qty FROM {$intGameID}_player_items WHERE player_id = '{$intPlayerId}' AND item_id = '{$strEventDetail1}'";
                    $result = mysql_query($query);
                    if($newQty = mysql_fetch_object($result)){
                        $newQty = $newQty->qty + $strEventDetail2;
                    }
                    if($strEventDetail2 >= 0){
                        if($newQty >= $unfinishedBusiness->unfinishedORRequirements[$x]['requirement_detail_2']){
                            Module::appendCompletedQuest($intQid, $intPlayerId, $intGameID);
                        }
                    }
                    else {
                        if($newQty < $unfinishedBusiness->unfinishedORRequirements[$x]['requirement_detail_2']){
                            Module::appendCompletedQuest($intQid, $intPlayerId, $intGameID);
                        }
                    }
                }
                // END weird special inventory calculations
            
                else{
                    if($strEventType == $unfinishedBusiness->unfinishedORRequirements[$x]['event']){
                        if($strEventDetail2 == $unfinishedBusiness->unfinishedORRequirements[$x]['requirement_detail_2']){
                            //PHIL_REQ_CODE: 
                            // The below line is commented out so it can be bubbled down the call stack to get appended later.
                            // However, at this point we KNOW that this quest needs to get appended (which we do later)
                            
                            //Module::appendCompletedQuest($intQid, $intPlayerId, $intGameID); 
                            
                            
                            //PHIL_REQ_CODE:
                            // This creates the quest object with all of the quests necessary info
                            $qOb = new stdClass();
                            $qOb->append = true;
                            $qOb->id = $intQid;
                            $qOb->pid = $intPlayerId;
                            $qOb->gid = $intGameID;
                            return $qOb;
                        }
                    }
                }
            }
        }
    
        //PHIL_REQ_CODE: Above 'loop' comments also apply to this 'if'. Only difference is that any one of those will denote the quest completed, while this NEEDS to be the only one.
        if(count($unfinishedBusiness->unfinishedANDRequirements) == 1){            
            if($strEventDetail1 == $unfinishedBusiness->unfinishedANDRequirements[0]['requirement_detail_1']){
                
                //Weird special calculations in case that event type is dealing with inventory
                if(($strEventType == Module::kLOG_PICKUP_ITEM && $unfinishedBusiness->unfinishedANDRequirements[0]['event'] == Module::kLOG_PICKUP_ITEM && $strEventDetail2 >= 0) || 
                   ($strEventType == Module::kLOG_DROP_ITEM && $unfinishedBusiness->unfinishedANDRequirements[0]['event'] == Module::kLOG_DROP_ITEM && $strEventDetail2 < 0)){
                    $query = "SELECT qty FROM {$intGameID}_player_items WHERE player_id = '{$intPlayerId}' AND item_id = '{$strEventDetail1}'";
                    $result = mysql_query($query);
                    $newQty = mysql_fetch_object($result);
                    if($newQty){
                        $newQty = $newQty->qty + $strEventDetail2;
                    }
                    else {
                        $newQty = strEventDetail2;
                    }
                    if($strEventDetail2 >= 0){
                        if($newQty >= $unfinishedBusiness->unfinishedANDRequirements[0]['requirement_detail_2']){
                            Module::appendCompletedQuest($intQid, $intPlayerId, $intGameID);
                        }
                    }
                    else {
                        if($newQty < $unfinishedBusiness->unfinishedANDRequirements[0]['requirement_detail_2']){
                            Module::appendCompletedQuest($intQid, $intPlayerId, $intGameID);
                        }
                    }
                }
                // END weird special inventory calculations
            
                else{
                    if($strEventType == $unfinishedBusiness->unfinishedANDRequirements[0]['event']){
                        if($strEventDetail2 == $unfinishedBusiness->unfinishedANDRequirements[0]['requirement_detail_2']){
                            //Module::appendCompletedQuest($intQid, $intPlayerId, $intGameID);
                            $qOb = new stdClass();
                            $qOb->append = true;
                            $qOb->id = $intQid;
                            $qOb->pid = $intPlayerId;
                            $qOb->gid = $intGameID;
                            return $qOb;
                        }
                    }
                }
            }
        }
        
        //PHIL_REQ_CODE: Function returns "NO" if no OR reqs were completed as a result of the event, and the last AND requirement was also not completed as a result of the event
        return "NO";
    }
    
    protected function appendCompletedQuest($intQid, $intPlayerId, $intGameId){
        //PHIL_REQ_CODE: This shouldn't get called anymore, as ANY appendation of the log should go through 'appendLog()'
        $query = "INSERT INTO player_log 
        (player_id, game_id, event_type, event_detail_1,event_detail_2) 
        VALUES 
        ({$intPlayerId},{$intGameId},'COMPLETE_QUEST','{$intQid}','N/A')";
		
		@mysql_query($query);
		
		NetDebug::trace($query);
        
		
		if (mysql_error()) {
			NetDebug::trace(mysql_error());
			return false;
		}
		
		else return true;
        
    }
    
    
    //PHIL_REQ_CODE:
    // All 'webHook' functions below are DIRECTLY analogous to the above 'questComplete' functions above. See those comments for details.
    // I will only note where they differ in this code
    /**
     * Fire off outgoing web hooks if requirement is final one needed
     * @returns true on success
     */
    protected function fireOffWebHooksIfReady($intPlayerId, $intGameID, $strEventType, $strEventDetail1="N/A", $strEventDetail2="N/A"){
        if($strEventDetail1 == null) $strEventDetail1 = "N/A";
        if($strEventDetail2 == null) $strEventDetail2 = "N/A";
        
        $query = "SELECT * FROM web_hooks WHERE incoming = '0' AND game_id = '{$intGameID}'";
        $result = mysql_query($query);
        
        $wObs = array();
        while($webHook = mysql_fetch_object($result)){
            $wOb = Module::fireOffWebHookIfReady($intPlayerId, $intGameID, $strEventType, $strEventDetail1, $strEventDetail2, $webHook->web_hook_id);
            if($wOb != "NO") $wObs[] = $wOb;
        }
        if(count($wObs)==0) return "NO";
        else return $wObs;
    }
    
    
    protected function fireOffWebHookIfReady($intPlayerId, $intGameID, $strEventType, $strEventDetail1="N/A", $strEventDetail2="N/A", $intWid){
        $unfinishedBusiness = Module::getOutstandingRequirements($intGameID, $intPlayerId, 'OutgoingWebHook', $intWid);
        if($unfinishedBusiness == 0) return;
        for($x = 0; $x < count($unfinishedBusiness->unfinishedORRequirements); $x++){
            if($strEventDetail1 == $unfinishedBusiness->unfinishedORRequirements[$x]['requirement_detail_1']){
                
                //Weird special calculations in case that event type is dealing with inventory
                if(($strEventType == Module::kLOG_PICKUP_ITEM && $unfinishedBusiness->unfinishedORRequirements[$x]['event'] == Module::kLOG_PICKUP_ITEM && $strEventDetail2 >= 0) || 
                   ($strEventType == Module::kLOG_DROP_ITEM && $unfinishedBusiness->unfinishedORRequirements[$x]['event'] == Module::kLOG_DROP_ITEM && $strEventDetail2 < 0)){
                    $query = "SELECT qty FROM {$intGameID}_player_items WHERE player_id = '{$intPlayerId}' AND item_id = '{$strEventDetail1}'";
                    $result = mysql_query($query);
                    if($newQty = mysql_fetch_object($result)){
                        $newQty = $newQty->qty + $strEventDetail2;
                    }
                    if($strEventDetail2 >= 0){
                        if($newQty >= $unfinishedBusiness->unfinishedORRequirements[$x]['requirement_detail_2']){
                            Module::appendCompletedQuest($intQid, $intPlayerId, $intGameID);
                        }
                    }
                    else {
                        if($newQty < $unfinishedBusiness->unfinishedORRequirements[$x]['requirement_detail_2']){
                            Module::appendCompletedQuest($intQid, $intPlayerId, $intGameID);
                        }
                    }
                }
                // END weird special inventory calculations
                
                else{
                    if($strEventType == $unfinishedBusiness->unfinishedORRequirements[$x]['event']){
                        if($strEventDetail2 == $unfinishedBusiness->unfinishedORRequirements[$x]['requirement_detail_2']){
                            //PHIL_REQ_CODE:
                            // This differs from the quest complete check because this ACTUALLY fires off the webhook, rather than waiting for it to be done
                            // down the call stack.
                            Module::fireOffWebHook($intWid, $intPlayerId, $intGameID);
                            $wOb = new stdClass();
                            $wOb->id = $intWid;
                            $wOb->pid = $intPlayerId;
                            $wOb->gid = $intGameID;
                            return $wOb;
                        }
                    }
                }
            }
        }
        if(count($unfinishedBusiness->unfinishedANDRequirements) == 1){
            if($strEventDetail1 == $unfinishedBusiness->unfinishedANDRequirements[0]['requirement_detail_1']){
                
                //Weird special calculations in case that event type is dealing with inventory
                if(($strEventType == Module::kLOG_PICKUP_ITEM && $unfinishedBusiness->unfinishedANDRequirements[0]['event'] == Module::kLOG_PICKUP_ITEM && $strEventDetail2 >= 0) || 
                   ($strEventType == Module::kLOG_DROP_ITEM && $unfinishedBusiness->unfinishedANDRequirements[0]['event'] == Module::kLOG_DROP_ITEM && $strEventDetail2 < 0)){
                    $query = "SELECT qty FROM {$intGameID}_player_items WHERE player_id = '{$intPlayerId}' AND item_id = '{$strEventDetail1}'";
                    $result = mysql_query($query);
                    if($newQty = mysql_fetch_object($result)){
                        $newQty = $newQty->qty + $strEventDetail2;
                    }
                    if($strEventDetail2 >= 0){
                        if($newQty >= $unfinishedBusiness->unfinishedANDRequirements[0]['requirement_detail_2']){
                            Module::appendCompletedQuest($intQid, $intPlayerId, $intGameID);
                        }
                    }
                    else {
                        if($newQty < $unfinishedBusiness->unfinishedANDRequirements[0]['requirement_detail_2']){
                            Module::appendCompletedQuest($intQid, $intPlayerId, $intGameID);
                        }
                    }
                }
                // END weird special inventory calculations
                
                else{
                    if($strEventType == $unfinishedBusiness->unfinishedANDRequirements[0]['event']){
                        if($strEventDetail2 == $unfinishedBusiness->unfinishedANDRequirements[0]['requirement_detail_2']){
                            Module::fireOffWebHook($intWid, $intPlayerId, $intGameID);
                            $wOb = new stdClass();
                            $wOb->id = $intWid;
                            $wOb->pid = $intPlayerId;
                            $wOb->gid = $intGameID;
                            return $wOb;
                        }
                    }
                }
            }
        }
        return "NO";
    }
   
    
    protected function fireOffWebHook($intWid, $intPlayerId, $intGameId){
        $query = "SELECT * FROM web_hooks WHERE web_hook_id = '{$intWid}'";
        $result = mysql_query($query);
        $webHook = mysql_fetch_object($result);
        $name = str_replace(" ", "", $webHook->name);
        $url = $webHook->url . "?hook=" . $name . "&wid=" . $webHook->web_hook_id . "&gameid=" . $intGameId . "&playerid=" . $intPlayerId; 
        NetDebug::trace($url);
        file_get_contents($url);
        return 0;
    }
    
    
    
    //PHIL_REQ_CODE:
    // This very closely emulates the above function of 'objectMeetsRequirements'. However, rather than this returning true or false, 
    // it simply returns a list of remaining requirements in two groups:
    // unfinishedANDRequirements, and unfinishedORRequirements.
    
    /**
     * Gets requirements that have not yet been met for an event
     * @returns 0 if all requirements are met, returns array of requirements if any outstanding
     */
    
    protected function getOutstandingRequirements($strPrefix, $intPlayerID, $strObjectType, $intObjectID){
        //Fetch the requirements
		$query = "SELECT requirement,
        requirement_detail_1,requirement_detail_2,requirement_detail_3,
        boolean_operator 
        FROM {$strPrefix}_requirements 
        WHERE content_type = '{$strObjectType}' AND content_id = '{$intObjectID}'";
		$rsRequirments = @mysql_query($query);
        
        $unfinishedANDRequirements = array();
        $unfinishedORRequirements = array();
		
		$complete = TRUE;
		$requirementsExist = FALSE;
		while ($requirement = mysql_fetch_array($rsRequirments)) {
			$requirementsExist = TRUE;
			//NetDebug::trace("Requirement for {$strObjectType}:{$intObjectID} is {$requirement['requirement']}:{$requirement['requirement_detail_1']}");
			//Check the requirement
			
			$requirementMet = FALSE;
			switch ($requirement['requirement']) {
                    //Log related
				case Module::kREQ_PLAYER_VIEWED_ITEM:
					$requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_VIEW_ITEM, 
                                                           $requirement['requirement_detail_1']);
                    $requirement['event'] = Module::kLOG_VIEW_ITEM;
					break;
				case Module::kREQ_PLAYER_VIEWED_NODE:
					$requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_VIEW_NODE, 
                                                           $requirement['requirement_detail_1']);
                    $requirement['event'] = Module::kLOG_VIEW_NODE;
					break;
				case Module::kREQ_PLAYER_VIEWED_NPC:
					$requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_VIEW_NPC, 
                                                           $requirement['requirement_detail_1']);
                    $requirement['event'] = Module::kLOG_VIEW_NPC;
					break;	
                case Module::kREQ_PLAYER_VIEWED_WEBPAGE:
					$requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_VIEW_WEBPAGE, 
                                                           $requirement['requirement_detail_1']);
                    $requirement['event'] = Module::kLOG_VIEW_WEBPAGE;
					break;
                case Module::kREQ_PLAYER_VIEWED_AUGBUBBLE:
					$requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_VIEW_AUGBUBBLE, 
                                                           $requirement['requirement_detail_1']);
                    $requirement['event'] = Module::kLOG_VIEW_AUGBUBBLE;
					break;
                case Module::kREQ_PLAYER_HAS_RECEIVED_INCOMING_WEBHOOK:
					$requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_RECEIVE_WEBHOOK, 
                                                           $requirement['requirement_detail_1']);
                    $requirement['event'] = Module::kLOG_RECEIVE_WEBHOOK;
					break;
                    //Inventory related	
				case Module::kREQ_PLAYER_HAS_ITEM:
					$requirementMet = Module::playerHasItem($strPrefix, $intPlayerID, 
                                                            $requirement['requirement_detail_1'], $requirement['requirement_detail_2']);
                    $requirement['event'] = Module::kLOG_PICKUP_ITEM;
					break;
                    //Data Collection
				case Module::kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM:
					$requirementMet = Module::playerHasUploadedMediaItemWithinDistence($strPrefix, $intPlayerID, 
                                                                                       $requirement['requirement_detail_3'], $requirement['requirement_detail_4'], 
                                                                                       $requirement['requirement_detail_1'], $requirement['requirement_detail_2'], Module::kLOG_UPLOAD_MEDIA_ITEM);
                    $requirement['event'] = Module::kLOG_UPLOAD_MEDIA_ITEM;
					break;
                case Module::kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE:
					$requirementMet = Module::playerHasUploadedMediaItemWithinDistence($strPrefix, $intPlayerID, 
                                                                                       $requirement['requirement_detail_3'], $requirement['requirement_detail_4'], 
                                                                                       $requirement['requirement_detail_1'], $requirement['requirement_detail_2'], Module::kLOG_UPLOAD_MEDIA_ITEM_IMAGE);
                    $requirement['event'] = Module::kLOG_UPLOAD_MEDIA_ITEM_IMAGE;
					break;
                case Module::kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO:
					$requirementMet = Module::playerHasUploadedMediaItemWithinDistence($strPrefix, $intPlayerID, 
                                                                                       $requirement['requirement_detail_3'], $requirement['requirement_detail_4'], 
                                                                                       $requirement['requirement_detail_1'], $requirement['requirement_detail_2'], Module::kLOG_UPLOAD_MEDIA_ITEM_AUDIO);
                    $requirement['event'] = Module::kLOG_UPLOAD_MEDIA_ITEM_AUDIO;
					break;
                case Module::kREQ_PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO:
					$requirementMet = Module::playerHasUploadedMediaItemWithinDistence($strPrefix, $intPlayerID, 
                                                                                       $requirement['requirement_detail_3'], $requirement['requirement_detail_4'], 
                                                                                       $requirement['requirement_detail_1'], $requirement['requirement_detail_2'], Module::kLOG_UPLOAD_MEDIA_ITEM_VIDEO);
                    $requirement['event'] = Module::kLOG_UPLOAD_MEDIA_ITEM_VIDEO;
					break;
				case Module::kREQ_PLAYER_HAS_COMPLETED_QUEST:
					$requirementMet = Module::playerHasLog($strPrefix, $intPlayerID, Module::kLOG_COMPLETE_QUEST, 
                                                           $requirement['requirement_detail_1']);
                    $requirement['event'] = Module::kLOG_COMPLETE_QUEST;
					break;	
			}//switch
            
            //Account for the 'NOT's
            if($requirement['not_operator'] == "NOT") $requirementMet = !$requirementMet;
            
			if ($requirement['boolean_operator'] == "AND" && $requirementMet == FALSE) {
				//NetDebug::trace("An AND requirement was not met. Requirements Failed.");
				$unfinishedANDRequirements[] = $requirement;
                $complete = FALSE;
			}
			if ($requirement['boolean_operator'] == "OR" && $requirementMet == TRUE){
				//NetDebug::trace("An OR requirement was met. Requirements Passed.");
				return 0;
			}
			
			if ($requirement['boolean_operator'] == "OR" && $requirementMet == FALSE){
                $unfinishedORRequirements[] = $requirement;
                $complete = FALSE;
            }

            
		}
        
        //while
		//NetDebug::trace("At the end of all the requirements for this object and any AND were passed, no ORs were passed.");
		//So no ORs were met, and possibly all ands were met
		if (!$requirementsExist || $complete) {
			//NetDebug::trace("No requirements exist. Requirements Passed.");
			return 0;
		}
		else {
			//NetDebug::trace("At end. Requirements Not Passed.");
            $retObj->unfinishedANDRequirements=$unfinishedANDRequirements;
            $retObj->unfinishedORRequirements=$unfinishedORRequirements;
			return $retObj;
		}

    }
	
	/**
     * Add a row to the server error log
     * @returns void
     */
	protected function serverErrorLog($message)
	{
		NetDebug::trace("Logging an Error: $message");
		$errorLogFile = fopen(Config::serverErrorLog, "a");
		$errorData = date('c') . ' "' . $message . '"' ."\n";
		fwrite($errorLogFile, $errorData);
		fclose($errorLogFile);
	}
	
	/**
     * Sends an Email
     * @returns 0 on success
     */
	protected function sendEmail($to, $subject, $body) {
	  	include_once('../../libraries/phpmailer/class.phpmailer.php');
	
	  	if (empty($to)) {
			  return false;
	  	}
	  	
	  	NetDebug::trace("TO: $to");
		NetDebug::trace("SUBJECT: $subject");
		NetDebug::trace("BODY: $body");
	  	
	  	$mail = new phpmailer;
	  	$mail->PluginDir = '../../libraries/phpmailer';      // plugin directory (eg smtp plugin)
	
	  	$mail->CharSet = 'UTF-8';
		$mail->Subject = substr(stripslashes($subject), 0, 900);
	  	$mail->From = 'noreply@arisgames.org';
	  	$mail->FromName = 'ARIS Mailer';
	
	  	$mail->AddAddress($to, 'ARIS Author');
		$mail->MsgHTML($body);
	
	
	  	$mail->WordWrap = 79;                               // set word wrap
	
	  	if ($mail->Send()) return true;
	  	else return false;

	}
	
}
