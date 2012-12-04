<?php
require_once("module.php");


class PlayerStateChanges extends Module
{	

	/**
	 * Fetch all Requirements for a Game Event
	 * @returns the requirements
	 */
	public function getPlayerStateChangesForObject($intGameID, $strEventType, $strEventDetail)
	{

		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		if (!$this->isValidEventType($intGameID, $strEventType)) return new returnData(4, NULL, "Invalid event type");

		$query = "SELECT * FROM player_state_changes
			WHERE game_id = {$prefix} AND event_type = '{$strEventType}' and event_detail = '{$strEventDetail}'";
		NetDebug::trace($query);


		$rsResult = @mysql_query($query);

		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		return new returnData(0, $rsResult);
	}

	/**
	 * Fetch a specific state change record
	 * @returns a single requirement
	 */
	public function getPlayerStateChange($intGameID, $intPlayerStateChangeID)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM player_state_changes WHERE game_id = {$prefix} AND id = {$intPlayerStateChangeID} LIMIT 1";

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
	public function createPlayerStateChange($intGameID, $strEventType, $intEventDetail, 
			$strActionType, $strActionDetail, $intActionAmount)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		//test the object type 
		if (!$this->isValidEventType($intGameID, $strEventType)) return new returnData(4, NULL, "Invalid event type");

		//test the requirement type
		if (!$this->isValidActionType($intGameID, $strActionType)) return new returnData(5, NULL, "Invalid action type");


		$query = "INSERT INTO player_state_changes 
			(game_id, event_type, event_detail, action, action_detail, action_amount)
			VALUES ('{$prefix}','{$strEventType}','{$intEventDetail}','{$strActionType}','{$strActionDetail}','{$intActionAmount}')";

		NetDebug::trace("Running a query = $query");	

		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

		return new returnData(0, mysql_insert_id());
	}



	/**
	 * Update a specific Player State Change
	 * @returns true if edit was done, false if no changes were made
	 */
	public function updatePlayerStateChange($intGameID, $intPlayerStateChangeID, $strEventType, 
			$intEventDetail, $strActionType, $strActionDetail, $intActionAmount)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		//test the object type 
		if (!$this->isValidEventType($intGameID, $strEventType)) return new returnData(4, NULL, "Invalid object type");

		//test the requirement type
		if (!$this->isValidActionType($intGameID, $strActionType)) return new returnData(5, NULL, "Invalid action type");



		$query = "UPDATE player_state_changes 
			SET 
			event_type = '{$strEventType}',
				   event_detail = '{$intEventDetail}',
				   action = '{$strActionType}',
				   action_detail = '{$strActionDetail}',
				   action_amount = '{$intActionAmount}'
					   WHERE game_id = '{$prefix}' AND id = '{$intPlayerStateChangeID}'";

		NetDebug::trace("Running a query = $query");	

		@mysql_query($query);
		NetDebug::trace(mysql_error());	

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
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "DELETE FROM player_state_changes WHERE game_id = {$prefix} AND id = {$intPlayerStateChangeID}";

		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

		if (mysql_affected_rows()) {
			return new returnData(0);
		}
		else {
			return new returnData(2, NULL, 'invalid player state change id');
		}

	}


	public function deletePlayerStateChangesThatRefrenceObject($intGameID, $strObjectType, $intObjectId)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$whereClause = '';

		switch ($strObjectType) {
			case 'Node':
				$whereClause = "event_type = 'VIEW_NODE' AND event_detail = '{$intObjectId}'";
				break;			
			case 'Item':
				$whereClause = "(event_type = 'VIEW_ITEM' AND event_detail = '{$intObjectId}') OR
					((action = 'GIVE_ITEM' OR action = 'TAKE_ITEM') AND action_detail = '{$intObjectId}')";
				break;
			case 'Npc':
				$whereClause = "event_type = 'VIEW_NPC' AND event_detail = '{$intObjectId}'";
				break;
			default:
				return new returnData(4, NULL, "invalid object type");
		}

		//Delete the Locations and related QR Codes
		$query = "DELETE FROM player_state_changes WHERE game_id = {$prefix} AND {$whereClause}";

		@mysql_query($query);

		NetDebug::trace("Query: $query" . mysql_error());		


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
	public function eventTypeOptions($intGameID){	
		$options = PlayerStateChanges::lookupEventTypeOptionsFromSQL($intGameID);
		if (!$options) return new returnData(1, NULL, "invalid game id");
		return new returnData(0, $options);
	}

	/**
	 * Fetch the valid content types from the requirements table
	 * @returns an array of strings
	 */
	public function actionTypeOptions($intGameID){	
		$options = PlayerStateChanges::lookupActionTypeOptionsFromSQL($intGameID);
		if (!$options) return new returnData(1, NULL, "invalid game id");
		return new returnData(0, $options);	
	}


	/**
	 * Fetch the valid content types from the requirements table
	 * @returns an array of strings
	 */
	private function lookupEventTypeOptionsFromSQL($intGameID){
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return FALSE;

		$query = "SHOW COLUMNS FROM player_state_changes LIKE 'event_type'";
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
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return FALSE;

		$query = "SHOW COLUMNS FROM player_state_changes LIKE 'action'";
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
