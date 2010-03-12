<?php
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
     * @returns an NPC, Nopde or Item with a type value specifying which
     */
	public function getQRCodeObjectForPlayer($intGameID, $intQRCodeID, $intPlayerID)
	{
		
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_qrcodes WHERE qrcode_id = {$intQRCodeID} LIMIT 1";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		$qrcode = @mysql_fetch_object($rsResult);
		if (!$qrcode) return new returnData(2, NULL, "invalid QRCode id");
		
		switch ($qrcode->type) {
			case 'Npc': 
				$returnResult = Npcs::getNpcWithConversationsForPlayer($intGameID, $qrcode->type_id, $intPlayerID);
				$returnResult->data->type = "Npc";
				break;
			case 'Node': 
				$returnResult = Nodes::getNode($intGameID, $qrcode->type_id);
				$returnResult->data->type = "Node";
				break;
			case 'Item': 
				$returnResult = Items::getItem($intGameID, $qrcode->type_id);
				$returnResult->data->type = "Item";
				break;	
		}
		
		return $returnResult;
		
	}
	

	
	/**
     * Create an Event
     * @returns the new eventID on success
     */
	public function createQRCode($intGameID, $intQRCodeID, $strObjectType, $intObjectID)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		if (!$this->isValidObjectType($intGameID, $strObjectType)) return new returnData(4, NULL, "Invalid object type");

		$query = "INSERT INTO {$prefix}_qrcodes 
					(qrcode_id, type, type_id)
					VALUES ('{$intQRCodeID}','{$strObjectType}','{$intObjectID}')";
		
		NetDebug::trace("Running a query = $query");	
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		return new returnData(0, mysql_insert_id());
	}

	
	
	/**
     * Update a specific Event
     * @returns true if edit was done, false if no changes were made
     */
	public function updateQRCode($intGameID, $intQRCodeID, $strObjectType, $intObjectID)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		if (!$this->isValidObjectType($intGameID, $strObjectType)) return new returnData(4, NULL, "Invalid object type");


		$query = "UPDATE {$prefix}_qrcodes
					SET 
					type = '{$strObjectType}',
					type_id = '{$intObjectID}',
					WHERE qrcode_id = '{$intQRCodeID}'";
		
		NetDebug::trace("Running a query = $query");	
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);
		

	}
			
	
	/**
     * Delete an Event
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
		
		$query = "SHOW COLUMNS FROM {$prefix}_qrcodes LIKE 'type'";
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