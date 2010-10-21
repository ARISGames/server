<?php
require_once("module.php");
require_once("players.php");
require_once("qrcodes.php");


class Locations extends Module
{

	/**
     * Fetch all Locations
     * @returns the locations rs
     */
	public function getLocations($intGameID)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		
		$query = "SELECT * FROM {$prefix}_locations";
		$rsResult = @mysql_query($query);
		
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		return new returnData(0, $rsResult);	
	}
	
	
	/**
     * Fetch all locations for a given player
     * @returns the locations that meet requirements and have a qty > 0
     */
	public function getLocationsForPlayer($intGameID, $intPlayerID)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$query = "SELECT * FROM {$prefix}_locations 
				WHERE latitude != '' AND longitude != ''
				AND (type != 'Item' OR (item_qty IS NULL OR item_qty != 0))
				";
		$rsLocations = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		$arrayLocations = array();
		
		while ($location = mysql_fetch_object($rsLocations)) {			
			//If location and object it links to meet requirments, add it to the array
			NetDebug::trace('Location ' . $location->location_id . ' Found. Checking Reqs');	

			if ($this->objectMeetsRequirements ($prefix, $intPlayerID, 'Location', $location->location_id)
				AND
				$this->objectMeetsRequirements ($prefix, $intPlayerID, $location->type, $location->type_id)) {
				
					$arrayLocations[] = $location;
					NetDebug::trace('Reqs Met. Adding to Result');	

			}
			else NetDebug::trace('Reqs Failed. Moving On');	
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
     * @returns a single location
     */
	public function getLocation($intGameID, $intLocationID)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_locations WHERE location_id = {$intLocationID} LIMIT 1";
	
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		$location = mysql_fetch_object($rsResult);
		if (!$location) return new returnData(2, NULL, "No matching location");
		return new returnData(0, $location);
	}
		
	
	
	
	/**
     * Places a location placeholder on the map which links to an aris game object type and id
     * @returns the new locationID on success
     */
	public function createLocation($intGameID, $strLocationName, $intIconMediaID, 
								$dblLatitude, $dblLongitude, $dblError,
								$strObjectType, $intObjectID,
								$intQuantity, $boolHidden, $boolForceView, $boolAllowQuickTravel) {
														
		$prefix = $this->getPrefix($intGameID);
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
		QRCodes::createQRCode($intGameID, "Location", $newId);

		return new returnData(0, $newId);

	}




	/**
     * Updates the attributes of a Location
     * @returns true if a record was modified, false if no changes were required (could be from not matching the location id)
     */
	public function updateLocation($intGameID, $intLocationID, $strLocationName, $intIconMediaID, 
								$dblLatitude, $dblLongitude, $dblError,
								$strObjectType, $intObjectID,
								$intQuantity, $boolHidden, $boolForceView, $boolAllowQuickTravel = 0)
	{
		$prefix = $this->getPrefix($intGameID);
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
     * Deletes a Location
     * @returns true if a location was deleted, false if no changes were required (could be from not matching the location id)
     */
	public function deleteLocation($intGameID, $intLocationId)
	{
		$prefix = $this->getPrefix($intGameID);
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
	
	public function deleteLocationsForObject($intGameID, $strObjectType, $intObjectId)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		//Check the object Type is good or null
		if ( !Locations::isValidObjectType($intGameID, $strObjectType) or !strlen($strObjectType) > 0 )
			return new returnData(4, NULL, "invalid object type");
			
		//Delete the Locations and related QR Codes
		$query = "DELETE {$prefix}_locations,{$prefix}_qrcodes 
			FROM {$prefix}_locations OUTER JOIN {$prefix}_qrcodes
			WHERE 
			{$prefix}_qrcodes.link_type='Location' AND 
			{$prefix}_locations.location_id={$prefix}_qrcodes.link_id AND
			{$prefix}_locations.type = '{$strObjectType}' AND
			{$prefix}_locations.type_id = '{$intObjectId}'";

		NetDebug::trace("Query: $query");		
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
			
		if (mysql_affected_rows()) {
			return new returnData(0, TRUE);
		}
		else {
			return new returnData(0, FALSE);
		}	
	}	
	
	
	/**
     * Fetch the valid content types from the requirements table
     * @returns an array of strings
     */
	public function objectTypeOptions($intGameID){	
		$options = Locations::lookupObjectTypeOptionsFromSQL($intGameID);
		if (!$options) return new returnData(1, NULL, "invalid game id");
		return new returnData(0, $options);
	}
	
	
	/**
     * Check if a content type is valid
     * @returns TRUE if valid
     */
	private function isValidObjectType($intGameID, $strObjectType) {
		$validTypes = Locations::lookupObjectTypeOptionsFromSQL($intGameID);
		return in_array($strObjectType, $validTypes);
	}
	
	
	/**
     * Fetch the valid requirement types from the requirements table
     * @returns an array of strings
     */
	private function lookupObjectTypeOptionsFromSQL($intGameID){
		$prefix = $this->getPrefix($intGameID);
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