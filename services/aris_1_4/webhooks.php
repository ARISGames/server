<?php
require_once("module.php");


class WebHooks extends Module
{	
	
	/**
     * Fetch all WebHooks
     * @returns the WebHooks
     */
	public function getWebHooks($intGameID)
	{
		
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		
		$query = "SELECT * FROM web_hooks WHERE game_id = '{$intGameID}'";
		//NetDebug::trace($query);

		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		return new returnData(0, $rsResult);
	}
		
	
	/**
     * Fetch a specific event
     * @returns a single event
     */
	public function getWebHook($intGameID, $intWebHookID)
	{
		
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM web_hooks WHERE game_id = '{$intGameID}' AND web_hook_id = '{$intWebHookID}' LIMIT 1";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		$event = @mysql_fetch_object($rsResult);
		if (!$event) return new returnData(2, NULL, "invalid quest id");
		
		return new returnData(0, $event);
		
	}
	
	/**
     * Create an Event
     * @returns the new eventID on success
     */
	public function createWebHook($intGameID, $strName, $strURL, $boolIncoming)
	{
		
		$strName = addslashes($strName);	
		$strURL = addslashes($strURL);	
		
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "INSERT INTO web_hooks
					(game_id, name, url, incoming)
					VALUES ('{$intGameID}', '{$strName}','{$strURL}','{$boolIncoming}')";
		
		NetDebug::trace("Running a query = $query");	
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		return new returnData(0, mysql_insert_id());
	}

	
	
	/**
     * Update a specific Event
     * @returns true if edit was done, false if no changes were made
     */
	public function updateWebHook($intGameID, $intWebHookID, $strName, $strURL)
	{
		
		$strName = addslashes($strName);	
		$strURL = addslashes($strURL);	
		
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "UPDATE web_hooks
					SET 
					name = '{$strName}',
					url = '{$strURL}'
					WHERE web_hook_id = '{$intWebHookID}'";
		
		NetDebug::trace("Running a query = $query");	
		
		@mysql_query($query);
		NetDebug::trace(mysql_error());	

		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);
		

	}
			
	
	/**
     * Delete an Event
     * @returns true if delete was done, false if no changes were made
     */
	public function deleteWebHook($intGameID, $intWebHookID)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$query = "DELETE FROM web_hooks WHERE web_hook_id = {$intWebHookID}";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		if (mysql_affected_rows()) {
			return new returnData(0, TRUE);
		}
		else {
			return new returnData(2, NULL, 'invalid event id');
		}
		
	}	
	
	
}