<?php
require("module.php");


class Events extends Module
{
	
	
	/**
     * Fetch all Events
     * @returns the events
     */
	public function getEvents($intGameID)
	{
		
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		
		$query = "SELECT * FROM {$prefix}_events";
		NetDebug::trace($query);

		
		$rsResult = @mysql_query($query);
		
		if (mysql_error()) return new returnData(1, NULL, "SQL Error");
		return new returnData(0, $rsResult);
	}
	
	/**
     * Fetch a specific event
     * @returns a single event
     */
	public function getEvent($intGameID, $intEventID)
	{
		
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_events WHERE event_id = {$intEventID} LIMIT 1";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		$event = @mysql_fetch_object($rsResult);
		if (!$event) return new returnData(2, NULL, "invalid event id");
		
		return new returnData(0, $event);
		
	}
	
	/**
     * Create an Event
     * @returns the new eventID on success
     */
	public function createEvent($intGameID, $strDescription)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "INSERT INTO {$prefix}_events 
					(description)
					VALUES ('{$strDescription}')";
		
		NetDebug::trace("Running a query = $query");	
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		return new returnData(0, mysql_insert_id());
	}

	
	
	/**
     * Update a specific Event
     * @returns true if edit was done, false if no changes were made
     */
	public function updateEvent($intGameID, $intEventID, $strDescription)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "UPDATE {$prefix}_events 
					SET description = '{$strDescription}'
					WHERE event_id = '{$intEventID}'";
		
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
	public function deleteEvent($intGameID, $intEventID)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$query = "DELETE FROM {$prefix}_events WHERE event_id = {$intEventID}";
		
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