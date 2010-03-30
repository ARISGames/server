<?php
require_once('locations.php');
require_once('nodes.php');
require_once('npcs.php');
require_once('items.php');
require_once("module.php");

class QRCodes extends Module
{

	/**
     * Fetch all Events
     * @returns the events
     */
	public function getQRCodes($intGameID)
	{
		
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		
		$query = "SELECT * FROM {$prefix}_qrcodes";
		NetDebug::trace($query);

		
		$rsResult = @mysql_query($query);
		
		if (mysql_error()) return new returnData(1, NULL, "SQL Error");
		return new returnData(0, $rsResult);
	}
	
	/**
     * Fetch a specific event
     * @returns a single event
     */
	public function getQRCode($intGameID, $intQRCodeID)
	{
		
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_qrcodes WHERE qrcode_id = {$intQRCodeID} LIMIT 1";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		$event = @mysql_fetch_object($rsResult);
		if (!$event) return new returnData(2, NULL, "invalid QRCode id");
		
		return new returnData(0, $event);
		
	}
	
	/**
     * Fetch a QRCode object
     * @returns a 0 with an NPC, Node or Item in addition to the latitute and longitude in the data, 2 for an invalid code, 4 for reqs not met or 5 for a data error
     */
	public function getQRCodeObjectForPlayer($intGameID, $strCode, $intPlayerID)
	{
		
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_qrcodes WHERE code = '{$strCode}' LIMIT 1";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error: ". mysql_error());
		
		$qrcode = @mysql_fetch_object($rsResult);
		
		//Check for a valid QR Code
		if (!$qrcode) 
			return new returnData(2, NULL, "invalid QRCode code");
		
		//Check the requirements of the QR Code's link object
		if (!$this->objectMeetsRequirements ($prefix, $intPlayerID, $qrcode->link_type, $qrcode->link_id))
			return new returnData(4, NULL, "QRCode requirements not met");
		
		//Get the data
		switch ($qrcode->link_type) {
			case 'Location':
				NetDebug::trace("It is Location " . $qrcode->link_id);
				$locationReturnData = Locations::getLocation($intGameID, $qrcode->link_id);
				$location = $locationReturnData->data;
				if (!$location) return new returnData(5, NULL, "bad link in qr code, no matching location found");
				NetDebug::trace("Location Found. Type:" . $location->type .". Look up the Object" );
				switch ($location->type) {
					case 'Npc': 
						NetDebug::trace("It is an NPC");
						$returnResult = Npcs::getNpcWithConversationsForPlayer($intGameID, $location->type_id, $intPlayerID);
						$returnResult->data->type = "Npc";
						break;
					case 'Node': 
						NetDebug::trace("It is an NPC");
						$returnResult = Nodes::getNode($intGameID, $location->type_id);
						$returnResult->data->type = "Node";
						break;
					case 'Item':
						NetDebug::trace("It is an Item");
						$returnResult = Items::getItem($intGameID, $location->type_id);
						$returnResult->data->type = "Item";
						break;	
					default:
						$returnResult = new returnData(5, NULL, "Invalid Location Record. type not recognized");
				}
				
				//No matter the object type, tack on the position
				$returnResult->data->latitude = $location->latitude;
				$returnResult->data->longitude = $location->longitude;
				break;
			
			default:
				$returnResult = new returnData(5, NULL, "Invalid QR Code Record. link_type not recognized");
		}
		
		return $returnResult;
		
	}
	

	
	/**
     * Create a QR Code
     * @returns the new QR Code ID on success
     */
	public function createQRCode($intGameID, $strLinkType, $intLinkID, $strCode)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		if (!$this->isValidObjectType($intGameID, $strLinkType)) return new returnData(4, NULL, "Invalid link type");

		$query = "INSERT INTO {$prefix}_qrcodes 
					(link_type, link_id, code)
					VALUES ('{$strLinkType}','{$intLinkID}','{$strCode}')";
		
		NetDebug::trace("Running a query = $query");	
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error: ". mysql_error());
		
		return new returnData(0, mysql_insert_id());
	}

	
	
	/**
     * Update a QR Code
     * @returns true if edit was done, false if no changes were made
     */
	public function updateQRCode($intGameID, $intQRCodeID, $strLinkType, $intLinkID, $strCode)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		if (!$this->isValidObjectType($intGameID, $strLinkType)) return new returnData(4, NULL, "Invalid link type");


		$query = "UPDATE {$prefix}_qrcodes
					SET 
					link_type = '{$strLinkType}',
					link_id = '{$intLinkID}',
					code = '{$strCode}'
					WHERE qrcode_id = '{$intQRCodeID}'";
		
		NetDebug::trace("Running a query = $query");	
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);
		

	}
			
	
	/**
     * Delete a QR Code
     * @returns true if delete was done, false if no changes were made
     */
	public function deleteQRCode($intGameID, $intQRCodeID)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");		
		
		$query = "DELETE FROM {$prefix}_qrcodes WHERE qrcode_id = {$intQRCodeID}";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		if (mysql_affected_rows()) {
			return new returnData(0, TRUE);
		}
		else {
			return new returnData(2, NULL, 'invalid qrcode id');
		}
		
	}	
	
	/**
     * Fetch the valid content types from the requirements table
     * @returns an array of strings
     */
	public function contentTypeOptions($intGameID){	
		$options = $this->lookupContentTypeOptionsFromSQL($intGameID);
		if (!$options) return new returnData(1, NULL, "invalid game id");
		return new returnData(0, $options);
	}	
	
	
	/**
     * Fetch the valid content types from the requirements table
     * @returns an array of strings
     */
	private function lookupContentTypeOptionsFromSQL($intGameID){
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return FALSE;
		
		$query = "SHOW COLUMNS FROM {$prefix}_qrcodes LIKE 'link_type'";
		NetDebug::trace($query);
		
		$result = @mysql_query( $query );
		$row = @mysql_fetch_array( $result , MYSQL_NUM );
		$regex = "/'(.*?)'/";
		preg_match_all( $regex , $row[1], $enum_array );
		$enum_fields = $enum_array[1];
		return( $enum_fields );
	}

	
	
	/**
     * Check if a content type is valid
     * @returns TRUE if valid
     */
	private function isValidObjectType($intGameID, $strObjectType) {
		$validTypes = $this->lookupContentTypeOptionsFromSQL($intGameID);
		return in_array($strObjectType, $validTypes);
	}


		
	
	
}