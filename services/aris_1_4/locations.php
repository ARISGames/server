<?php
require_once("module.php");
require_once("players.php");
require_once("qrcodes.php");


class Locations extends Module
{

	/**
     * Fetch all location in a game
     *
     * @param integer $intGameID The game identifier
     * @return returnData
     * @returns a returnData object containing an array of locations
     * @see returnData
     */
	public function getLocations($intGameID)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		
		$query = "SELECT * FROM {$prefix}_locations";
		$rsResult = @mysql_query($query);
		
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:".mysql_error());
		return new returnData(0, $rsResult);	
	}
	
	
	/**
     * Fetch all locations in a game with matching QR Code information
     *
     * @param integer $intGameID The game identifier
     * @return returnData
     * @returns a returnData object containing an array of locations with the QR code record id and code
     * @see returnData
     */
	public function getLocationsWithQrCode($intGameID)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		
		$query = "SELECT {$prefix}_locations.*,{$prefix}_qrcodes.qrcode_id,{$prefix}_qrcodes.code,{$prefix}_qrcodes.match_media_id
					FROM {$prefix}_locations JOIN {$prefix}_qrcodes
					ON {$prefix}_qrcodes.link_id = {$prefix}_locations.location_id
					WHERE {$prefix}_qrcodes.link_type = 'Location'";
		NetDebug::trace($query);	

		$rsResult = @mysql_query($query);
		NetDebug::trace(mysql_error());	
		
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		return new returnData(0, $rsResult);	
	}	
	
	/**
     * Fetch locations with fulfilled requirements and other player positions
     *
     * @param integer $intGameID The game identifier
     * @param integer $intPlayerID The player identifier
     * @return returnData
     * @returns a returnData object containing an array of locations
     * @see returnData
     */
	public function getLocationsForPlayer($intGameID, $intPlayerID)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$query = "SELECT * FROM {$prefix}_locations 
				WHERE latitude != '' AND longitude != ''
				AND (type != 'Item' OR (item_qty IS NULL OR item_qty != 0))
				";
		$rsLocations = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error" . mysql_error());
		
		$arrayLocations = array();
		
		while ($location = mysql_fetch_object($rsLocations)) {			
			//If location and object it links to meet requirments, add it to the array
			NetDebug::trace('Location ' . $location->location_id . ' Found. Checking Reqs');	

			//Does it Exist?
			switch ($location->type) {
				case 'Item':
					$query = "SELECT icon_media_id FROM {$prefix}_items WHERE item_id = {$location->type_id} LIMIT 1";
					break;
				case 'Node':
					$query = "SELECT icon_media_id FROM {$prefix}_nodes WHERE node_id = {$location->type_id} LIMIT 1";
					break;
				case 'Npc':
					$query = "SELECT icon_media_id FROM {$prefix}_npcs WHERE npc_id = {$location->type_id} LIMIT 1";
					break;
                case 'WebPage':
					$query = "SELECT icon_media_id FROM web_pages WHERE web_page_id = {$location->type_id} LIMIT 1";
					break;
                case 'AugBubble':
					$query = "SELECT icon_media_id FROM aug_bubbles WHERE aug_bubble_id = {$location->type_id} LIMIT 1";
					break;
			}
			
			$rsObject = @mysql_query($query);
			$object = @mysql_fetch_object($rsObject);
			
			if (!$object) {
				NetDebug::trace("Skipping Location:{$location->location_id} becasue it points to something bogus");	
				continue;
			}

			//Does it meet it's requirements?
			if (!$this->objectMeetsRequirements ($prefix, $intPlayerID, 'Location', $location->location_id)) {
               // NetDebug::trace($prefix . " " . $intPlayerID . " 'Location' " . $location->location_id);
				NetDebug::trace("Skipping Location:{$location->location_id} becasue it doesn't meet it's requirements");
				continue;
			}
				
			
			NetDebug::trace('Location:{$location->location_id} is ok');	

			//If location's icon is not defined, use the object's icon
			if (!$location->icon_media_id) {
					$objectsIconMediaId = $object->icon_media_id;
					$location->icon_media_id = $objectsIconMediaId;
			}
			
			//Add it
			$arrayLocations[] = $location;

		}
		
		//Add the others players from this game, making them look like reqular locations
		$playersJSON = Players::getOtherPlayersForGame($intGameID, $intPlayerID);
		$playersArray = $playersJSON->data;
		
		foreach ($playersArray as $player) {
			NetDebug::trace("adding player: " . $player->user_name );	
			
			$tmpPlayerObject = new stdClass();

      		$tmpPlayerObject->name = $player->user_name;
			$tmpPlayerObject->latitude = $player->latitude;
      		$tmpPlayerObject->longitude = $player->longitude;
      		$tmpPlayerObject->type_id = $player->player_id;
      		
      		$tmpPlayerObject->error = "5";
      		$tmpPlayerObject->type = "Player";
      		
			$tmpPlayerObject->description = '';
      		$tmpPlayerObject->force_view = "0";
      		$tmpPlayerObject->hidden = "0";
      		$tmpPlayerObject->icon_media_id = "0";
      		$tmpPlayerObject->item_qty = "0";
     		$tmpPlayerObject->location_id = "0";
      		
      		$arrayLocations[] = $tmpPlayerObject;
      		NetDebug::trace("just adding player: " . $tmpPlayerObject->name );	

		}

		return new returnData(0, $arrayLocations);

	}
	

	
	/**
     * Fetch a specific location
     *
     * @param integer $intGameID The game identifier
     * @param integer $intLocationID The location to fetch
     * @return returnData
     * @returns a returnData object containing a location
     * @see returnData
     */
	public function getLocation($intGameID, $intLocationID)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_locations WHERE location_id = {$intLocationID} LIMIT 1";
	
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		$location = mysql_fetch_object($rsResult);
		if (!$location) return new returnData(2, NULL, "No matching location");
		return new returnData(0, $location);
	}
		
	
	/**
     * Creates a location that points to a given object
     *
     * @param integer $intGameID The game identifier
     * @param string $strLocationName The new name
     * @param integer $intIconMediaID The new icon media id
     * @param double $dblLatitude The new latitude
     * @param double $dblLongitude The new longitude
     * @param integer $dblError The radius in meters from the lat/log point in which this locaiton is triggered
     * @param string $strObjectType A valid object type (see objectTypeOptions())
     * @param string $intObjectID Id for the object
     * @param string $intQuantity Quantity at this location (only used if item)
     * @param bool $boolHidden 0 to display normally, 1 to hide from the player's map
     * @param bool $boolForceView 0 to display normally, 1 to display immediately when player enters range
     * @param bool $boolAllowQuickTravel 0 to disallow, 1 to allow
     * @return returnData
     * @returns a returnData object containing the new locationID
     * @see returnData
     */
	public function createLocation($intGameID, $strLocationName, $intIconMediaID, 
								$dblLatitude, $dblLongitude, $dblError,
								$strObjectType, $intObjectID,
								$intQuantity, $boolHidden, $boolForceView, $boolAllowQuickTravel) {	
			
			Locations::createLocationWithQrCode($intGameID, $strLocationName, $intIconMediaID, 
								$dblLatitude, $dblLongitude, $dblError,
								$strObjectType, $intObjectID,
								$intQuantity, $boolHidden, $boolForceView, $boolAllowQuickTravel, $qrCode = '', 0);
	}
	
     /**
     * Creates a location that points to a given object
     *
     * @param integer $intGameID The game identifier
     * @param string $strLocationName The new name
     * @param integer $intIconMediaID The new icon media id
     * @param double $dblLatitude The new latitude
     * @param double $dblLongitude The new longitude
     * @param integer $dblError The radius in meters from the lat/log point in which this locaiton is triggered
     * @param string $strObjectType A valid object type (see objectTypeOptions())
     * @param string $intObjectID Id for the object
     * @param string $intQuantity Quantity at this location (only used if item)
     * @param bool $boolHidden 0 to display normally, 1 to hide from the player's map
     * @param bool $boolForceView 0 to display normally, 1 to display immediately when player enters range
     * @param bool $boolAllowQuickTravel 0 to disallow, 1 to allow
     * @param string $qrCode Code to use with the decoder
     * @return returnData
     * @returns a returnData object containing the new locationID
     * @see returnData
     */
	public function createLocationWithQrCode($intGameID, $strLocationName, $intIconMediaID, 
								$dblLatitude, $dblLongitude, $dblError,
								$strObjectType, $intObjectID,
								$intQuantity, $boolHidden, $boolForceView, $boolAllowQuickTravel, $qrCode = '', $imageMatchId) {
														
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		if (!$intQuantity) $intQuantity = 1;
		if (!$boolAllowQuickTravel) $boolAllowQuickTravel = 0;
		
		$strLocationName = addslashes($strLocationName);
		if ($dblError < 5) $dblError = 25;
		
		//Check the object Type is good or null
		if ( !Locations::isValidObjectType($intGameID, $strObjectType) or !strlen($strObjectType) > 0 )
			return new returnData(4, NULL, "invalid object type");

		$query = "INSERT INTO {$prefix}_locations 
					(name, icon_media_id, latitude, longitude, error, 
					type, type_id, item_qty, hidden, force_view, allow_quick_travel)
					VALUES ('{$strLocationName}', '{$intIconMediaID}',
							'{$dblLatitude}','{$dblLongitude}','{$dblError}',
							'{$strObjectType}','{$intObjectID}','{$intQuantity}',
							'{$boolHidden}','{$boolForceView}', '{$boolAllowQuickTravel}')";
		
		NetDebug::trace("createLocation: Running a query = $query");	
	
		@mysql_query($query);
		
		if (mysql_error()) {
			NetDebug::trace("createLocation: SQL Error = " . mysql_error());
			return new returnData(3, NULL, "SQL Error");
		}
		
		$newId = mysql_insert_id();
		//Create a coresponding QR Code
		QRCodes::createQRCode($intGameID, "Location", $newId, $qrCode, $imageMatchId);

		return new returnData(0, $newId);

	}





     /**
     * Updates the attributes of a Location
     *
     * @param integer $intGameID The game identifier
     * @param string $intLocationID The location identifier     
     * @param string $strLocationName The new name
     * @param integer $intIconMediaID The new icon media id
     * @param double $dblLatitude The new latitude
     * @param double $dblLongitude The new longitude
     * @param integer $dblError The radius in meters from the lat/log point in which this locaiton is triggered
     * @param string $strObjectType A valid object type (see objectTypeOptions())
     * @param string $intObjectID Id for the object
     * @param string $intQuantity Quantity at this location (only used if item)
     * @param bool $boolHidden 0 to display normally, 1 to hide from the player's map
     * @param bool $boolForceView 0 to display normally, 1 to display immediately when player enters range
     * @param bool $boolAllowQuickTravel 0 to disallow, 1 to allow
     * @return returnData
     * @returns a returnData object containing true if a record was modified
     * @see returnData
     */     
	public function updateLocation($intGameID, $intLocationID, $strLocationName, $intIconMediaID, 
								$dblLatitude, $dblLongitude, $dblError,
								$strObjectType, $intObjectID,
								$intQuantity, $boolHidden, $boolForceView, $boolAllowQuickTravel)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$strLocationName = addslashes($strLocationName);
		if ($dblError < 5) $dblError = 25;

		//Check the object Type is good or null
		if ( !$this->isValidObjectType($intGameID, $strObjectType) or !strlen($strObjectType) > 0 )
			return new returnData(4, NULL, "invalid object type");
		
		$query = "UPDATE {$prefix}_locations
				SET 
				name = '{$strLocationName}',
				icon_media_id = '{$intIconMediaID}', 
				latitude = '{$dblLatitude}', 
				longitude = '{$dblLongitude}', 
				error = '{$dblError}',
				type = '{$strObjectType}',
				type_id = '{$intObjectID}',
				item_qty = '{$intQuantity}',
				hidden = '{$boolHidden}',
				force_view = '{$boolForceView}',
				allow_quick_travel = '{$boolAllowQuickTravel}'
				WHERE location_id = '{$intLocationID}'";
		
		NetDebug::trace("updateLocation: Query: $query");		
		
		@mysql_query($query);
		if (mysql_error()) {
			NetDebug::trace("MySQL Error:" . mysql_error());
			return new returnData(3, NULL, "SQL Error");		
		}
		
		if (mysql_affected_rows()) {
			return new returnData(0, TRUE);
		}
		else {
			return new returnData(0, FALSE);
		}
		
	}	

     /**
     * Updates the attributes of a Location
     *
     * @param integer $intGameID The game identifier
     * @param string $intLocationID The location identifier     
     * @param string $strLocationName The new name
     * @param integer $intIconMediaID The new icon media id
     * @param double $dblLatitude The new latitude
     * @param double $dblLongitude The new longitude
     * @param integer $dblError The radius in meters from the lat/log point in which this locaiton is triggered
     * @param string $strObjectType A valid object type (see objectTypeOptions())
     * @param string $intObjectID Id for the object
     * @param string $intQuantity Quantity at this location (only used if item)
     * @param bool $boolHidden 0 to display normally, 1 to hide from the player's map
     * @param bool $boolForceView 0 to display normally, 1 to display immediately when player enters range
     * @param bool $boolAllowQuickTravel 0 to disallow, 1 to allow
     * @param string $qrCode a code to set for the QR image and the decoder    
     * @return returnData
     * @returns a returnData object containing true if a record was modified
     * @see returnData
     */     
	public function updateLocationWithQrCode($intGameID, $intLocationID, $strLocationName, $intIconMediaID, 
								$dblLatitude, $dblLongitude, $dblError,
								$strObjectType, $intObjectID,
								$intQuantity, $boolHidden, $boolForceView, $boolAllowQuickTravel, $qrCode, $imageMatchId)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$strLocationName = addslashes($strLocationName);
		if ($dblError < 5) $dblError = 25;

		//Check the object Type is good or null
		if ( !$this->isValidObjectType($intGameID, $strObjectType) or !strlen($strObjectType) > 0 )
			return new returnData(4, NULL, "invalid object type");
		
		$query = "UPDATE {$prefix}_locations
				SET 
				name = '{$strLocationName}',
				icon_media_id = '{$intIconMediaID}', 
				latitude = '{$dblLatitude}', 
				longitude = '{$dblLongitude}', 
				error = '{$dblError}',
				type = '{$strObjectType}',
				type_id = '{$intObjectID}',
				item_qty = '{$intQuantity}',
				hidden = '{$boolHidden}',
				force_view = '{$boolForceView}',
				allow_quick_travel = '{$boolAllowQuickTravel}'
				WHERE location_id = '{$intLocationID}'";
		NetDebug::trace("updateLocation: Query: $query");		
		@mysql_query($query);
		if (mysql_error()) {
			NetDebug::trace("MySQL Error:" . mysql_error());
			return new returnData(3, NULL, "SQL Error" . mysql_error());		
		}
		
		
		$query = "UPDATE {$prefix}_qrcodes
				SET 
				code = '{$qrCode}', match_media_id = '{$imageMatchId}'
				WHERE link_type = 'Location' and link_id = '{$intLocationID}'";
		NetDebug::trace("updateLocation: Query: $query");		
		@mysql_query($query);
		
		
		if (mysql_error()) {
			NetDebug::trace("MySQL Error:" . mysql_error());
			return new returnData(3, NULL, "SQL Error on query: {$query} Error:" . mysql_error());		
		}		
		
		if (mysql_affected_rows()) {
			return new returnData(0, TRUE);
		}
		else {
			return new returnData(0, FALSE);
		}
		
	}	


     /**
     * Deletes a Location
     *
     * @param integer $intGameID The game identifier
     * @param string $intLocationID The location identifier     
     * @return returnData
     * @returns a returnData object containing true if a record was deleted
     * @see returnData
     */ 
	public function deleteLocation($intGameID, $intLocationId)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		//Lookup the name of the item
		$query = "DELETE FROM {$prefix}_locations 
				WHERE location_id = '{$intLocationId}'";
		NetDebug::trace("deleteLocation: Query: $query");		
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		//Delete any QR Codes that point here
		QRCodes::deleteQRCodeCodesForLink($intGameID, "Location", $intLocationId);
		
		
		if (mysql_affected_rows()) {
			return new returnData(0, TRUE);
		}
		else {
			return new returnData(0, FALSE);
		}	
	}

     /**
     * Deletes all locations that refer to the given object
     *
     * @param integer $intGameID The game identifier
     * @param string $strObjectType A valid object type (see objectTypeOptions())
     * @param string $intObjectID Id for the object
     * @return returnData
     * @returns a returnData object containing true if a record was deleted
     * @see returnData
     */ 
	public function deleteLocationsForObject($intGameID, $strObjectType, $intObjectId)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		//Check the object Type is good or null
		if ( !Locations::isValidObjectType($intGameID, $strObjectType) or !strlen($strObjectType) > 0 )
			return new returnData(4, NULL, "invalid object type");
			
		//Delete the Locations and related QR Codes
		$query = "DELETE {$prefix}_locations,{$prefix}_qrcodes 
			FROM {$prefix}_locations LEFT OUTER JOIN {$prefix}_qrcodes
			ON
			{$prefix}_locations.location_id={$prefix}_qrcodes.link_id
			WHERE 
			{$prefix}_qrcodes.link_type='Location' AND 
			{$prefix}_locations.type = '{$strObjectType}' AND
			{$prefix}_locations.type_id = '{$intObjectId}'";

		NetDebug::trace("Query: $query");		
		
		@mysql_query($query);
		NetDebug::trace(mysql_error());		

		if (mysql_error()) return new returnData(3, NULL, "SQL Error" . mysql_error());
		
			
		if (mysql_affected_rows()) {
			return new returnData(0, TRUE);
		}
		else {
			return new returnData(0, FALSE);
		}	
	}	
	
	
     /**
     * Fetch the valid content types for use in other location operations
     *
     * @param integer $intGameID The game identifier
     * @return returnData
     * @returns a returnData object containing an array of valid objectType strings
     * @see returnData
     */      
	public function objectTypeOptions($intGameID){	
		$options = Locations::lookupObjectTypeOptionsFromSQL($intGameID);
		if (!$options) return new returnData(1, NULL, "invalid game id");
		return new returnData(0, $options);
	}
	
	
     /**
     * Check if a content type is valid
     *
     * @param integer $intGameID The game identifier
     * @return bool
     * @returns TRUE if valid, FALSE otherwise
     */  
	private function isValidObjectType($intGameID, $strObjectType) {
		$validTypes = Locations::lookupObjectTypeOptionsFromSQL($intGameID);
		return in_array($strObjectType, $validTypes);
	}
	
	
     /**
     * Fetch the valid content types for use in other location operations
     *
     * @param integer $intGameID The game identifier
     * @return array
     * @returns an array of strings
     */  
	private function lookupObjectTypeOptionsFromSQL($intGameID){
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return FALSE;
		
		$query = "SHOW COLUMNS FROM {$prefix}_locations LIKE 'type'";
		$result = mysql_query( $query );
		$row = mysql_fetch_array( $result , MYSQL_NUM );
		$regex = "/'(.*?)'/";
		preg_match_all( $regex , $row[1], $enum_array );
		$enum_fields = $enum_array[1];
		return( $enum_fields );
	}	
		
	
}