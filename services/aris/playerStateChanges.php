<?php
require("module.php");


class PlayerStateChanges extends Module
{	
	
	/**
     * Fetch all Requirements for a Game Event
     * @returns the requirements
     */
	public function getPlayerStateChangesForObject($intGameID, $strEventType, $strEventDetail)
	{
		
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		if (!$this->isValidEventType($intGameID, $strEventType)) return new returnData(4, NULL, "Invalid event type");
		
		$query = "SELECT * FROM {$prefix}_player_state_changes
					WHERE event_type = '{$strEventType}' and event_detail = '{$strEventDetail}'";
		NetDebug::trace($query);

		
		$rsResult = @mysql_query($query);
		
		if (mysql_error()) return new returnData(1, NULL, "SQL Error");
		return new returnData(0, $rsResult);
	}
	
	/**
     * Fetch a specific state change record
     * @returns a single requirement
     */
	public function getPlayerStateChange($intGameID, $intPlayerStateChangeID)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_player_state_changes WHERE id = {$intPlayerStateChangeID} LIMIT 1";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		$row = @mysql_fetch_object($rsResult);
		if (!$row) return new returnData(2, NULL, "invalid player state change id");
		
		return new returnData(0, $row);	
	}
	
	/**
     * Create a Player State Change
     * @returns the new playerStateChangeID on success
     */
	public function createPlayerStateChange($intGameID, $strEventType, $strEventDetail, $strActionType, $strActionDetail)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		//test the object type 
		if (!$this->isValidEventType($intGameID, $strEventType)) return new returnData(4, NULL, "Invalid event type");
				
		//test the requirement type
		if (!$this->isValidActionType($intGameID, $strActionType)) return new returnData(5, NULL, "Invalid action type");
		
		
		$query = "INSERT INTO {$prefix}_player_state_changes 
					(event_type, event_detail, action, action_detail)
					VALUES ('{$strEventType}','{$strEventDetail}','{$strActionType}','{$strActionDetail}')";
		
		NetDebug::trace("Running a query = $query");	
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		return new returnData(0, mysql_insert_id());
	}

	
	
	/**
     * Update a specific Player State Change
     * @returns true if edit was done, false if no changes were made
     */
	public function updatePlayerStateChange($intGameID, $intPlayerStateChangeID, $strEventType, $strEventDetail, $strActionType, $strActionDetail)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		//test the object type 
		if (!$this->isValidEventType($intGameID, $strEventType)) return new returnData(4, NULL, "Invalid object type");
				
		//test the requirement type
		if (!$this->isValidActionType($intGameID, $strActionType)) return new returnData(5, NULL, "Invalid action type");
		
		

		$query = "UPDATE {$prefix}_player_state_changes 
					SET 
					event_type = '{$strEventType}',
					event_detail = '{$strEventDetail}',
					action = '{$strActionType}',
					action_detail = '{$strActionDetail}'
					WHERE id = '{$intPlayerStateChangeID}'";
		
		NetDebug::trace("Running a query = $query");	
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);
	}
			
	
	/**
     * Delete an Requirement
     * @returns 0 on success
     */
	public function deletePlayerStateChange($intGameID, $intPlayerStateChangeID)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$query = "DELETE FROM {$prefix}_player_state_changes WHERE id = {$intPlayerStateChangeID}";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		if (mysql_affected_rows()) {
			return new returnData(0);
		}
		else {
			return new returnData(2, NULL, 'invalid player state change id');
		}
		
	}	
	
	/**
     * Fetch the valid content types from the requirements table
     * @returns an array of strings
     */
	public function eventTypeOptions($intGameID){	
		$options = $this->lookupContentTypeOptionsFromSQL($intGameID);
		if (!$options) return new returnData(1, NULL, "invalid game id");
		return new returnData(0, $options);
	}

	/**
     * Fetch the valid content types from the requirements table
     * @returns an array of strings
     */
	public function actionTypeOptions($intGameID){	
		$options = $this->lookupActionTypeOptionsFromSQL($intGameID);
		if (!$options) return new returnData(1, NULL, "invalid game id");
		return new returnData(0, $options);	
	}

	
	/**
     * Fetch the valid content types from the requirements table
     * @returns an array of strings
     */
	private function lookupEventTypeOptionsFromSQL($intGameID){
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return FALSE;
		
		$query = "SHOW COLUMNS FROM {$prefix}_player_state_changes LIKE 'event_type'";
		NetDebug::trace($query);
		
		$result = @mysql_query( $query );
		$row = @mysql_fetch_array( $result , MYSQL_NUM );
		$regex = "/'(.*?)'/";
		preg_match_all( $regex , $row[1], $enum_array );
		$enum_fields = $enum_array[1];
		return( $enum_fields );
	}

	/**
     * Fetch the valid requirement types from the requirements table
     * @returns an array of strings
     */
	private function lookupActionTypeOptionsFromSQL($intGameID){
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return FALSE;
		
		$query = "SHOW COLUMNS FROM {$prefix}_player_state_changes LIKE 'action'";
		$result = mysql_query( $query );
		$row = mysql_fetch_array( $result , MYSQL_NUM );
		$regex = "/'(.*?)'/";
		preg_match_all( $regex , $row[1], $enum_array );
		$enum_fields = $enum_array[1];
		return( $enum_fields );
	}	
	
	
	/**
     * Check if a content type is valid
     * @returns TRUE if valid
     */
	private function isValidEventType($intGameID, $strObjectType) {
		$validTypes = $this->lookupEventTypeOptionsFromSQL($intGameID);
		return in_array($strObjectType, $validTypes);
	}

	/**
     * Check if a requirement type is valid
     * @returns TRUE if valid
     */
	private function isValidActionType($intGameID, $strActionType) {
		$validTypes = $this->lookupActionTypeOptionsFromSQL($intGameID);
		NetDebug::trace($validTypes);
		NetDebug::trace('Requested Type:' . $strActionType);
		return in_array($strActionType, $validTypes);
	}	
	



}