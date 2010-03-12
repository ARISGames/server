<?php
require_once("module.php");


class Players extends Module
{	
	/**
     * Create a new Player
     * @returns player id
     */
	public function createPlayer($strNewUserName, $strPassword, $strFirstName, $strLastName, $strEmail)
	{
		
		$strNewUserName = addslashes($strNewUserName);	
		$strFirstName = addslashes($strFirstName);	
		$strLastName = addslashes($strLastName);	
		$strEmail = addslashes($strEmail);	
		
		$query = "SELECT player_id FROM players 
				  WHERE user_name = '{$strNewUserName}' LIMIT 1";
			
		if (mysql_fetch_array(mysql_query($query))) {
			return new returnData(4, NULL, 'user exists');
		}
		
		$query = "INSERT INTO players (user_name, password, 
									first_name, last_name, email) 
				  VALUES ('{$strNewUserName}', MD5('$strPassword'),
				  		'{$strFirstName}','{$strLastName}','{$strEmail}')";
			
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');
		
		return new returnData(0, mysql_insert_id());
	}
	
	
	/**
     * Login
     * @returns player id
     */
	public function login($strUser,$strPassword)
	{

		$query = "SELECT * FROM players 
				WHERE user_name = '{$strUser}' and password = MD5('{$strPassword}') LIMIT 1";
		
		//NetDebug::trace($query);

		$rs = @mysql_query($query);
		if (mysql_num_rows($rs) < 1) return new returnData(4, NULL, 'bad username or password');
		
		$player = @mysql_fetch_object($rs);
				
		return new returnData(0, intval($player->player_id));
	}
		

	
	/**
     * updates the player's last game
     * @returns a returnData object, result code 0 on success
     */
	public function updatePlayerLastGame($intPlayerID, $intGameID)
	{
		$query = "UPDATE players
					SET last_game_id = '{$intGameID}'
					WHERE player_id = {$intPlayerID}";
		
		NetDebug::trace($query);

		@mysql_query($query);
		
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);
	}	


	/**
     * getPlayersForGame
     * @returns players with this game id
     */
	public function getPlayersForGame($intGameID)
	{
		$query = "SELECT player_id, user_name, latitude, longitude FROM players 
				WHERE last_game_id = '{$intGameID}'";
		
		//NetDebug::trace($query);

		$rs = @mysql_query($query);
		return new returnData(0, $rs);
	}

	/**
     * getOtherPlayersForGame
     * @returns players with this game id
     */
	public function getOtherPlayersForGame($intGameID, $intPlayerID)
	{
		$query = "SELECT player_id, user_name, latitude, longitude FROM players 
				WHERE last_game_id = '{$intGameID}' AND
				player_id != '{$intPlayerID}'";

		$rs = @mysql_query($query);
		
		$array = array();
		while ($object = mysql_fetch_object($rs)) {
			$array[] = $object;
		}
		
		return new returnData(0, $array);
	}

	/**
     * updates the lat/long for the player record
     * @returns players with this game id
     */
	public function updatePlayerLocation($intPlayerID, $floatLat, $floatLong)
	{
		$query = "UPDATE players
					SET latitude = {$floatLat} , longitude = {$floatLong}
					WHERE player_id = {$intPlayerID}";
		
		//NetDebug::trace($query);

		@mysql_query($query);
		
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);
	}
	
	

	/**
     * Player Viewed a Node, exectute it's actions
     * @returns returnData with data=true if a player state change was made
     */
	public function nodeViewed($intGameID, $intPlayerID, $intNodeID)
	{	
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$changeMade = Module::applyPlayerStateChanges($prefix, $intPlayerID, 'Node', $intNodeID);
		
		return new returnData(0, $changeMade);
	}
	

	/**
     * Player Viewed an Item, exectute it's actions
     * @returns returnData with data=true if a player state change was made
     */
	public function itemViewed($intGameID, $intPlayerID, $intItemID)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$changeMade = Module::applyPlayerStateChanges($prefix, $intPlayerID, 'Item', $intItemID);
		
		return new returnData(0, $changeMade);
	}
	
	
	/**
     * Reset all player Events
     * @returns returnData with data=true if changes were made
     */
	public function resetPlayerEvents($intGameID, $intPlayerID)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$query = "DELETE {$prefix}_player_events
					WHERE player_id = {$intPlayerID}";
		
		//NetDebug::trace($query);

		@mysql_query($query);
		
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);
	}
	
	
	/**
     * Reset all player Items
     * @returns returnData with data=true if changes were made
     */
	public function resetPlayerItems($intGameID, $intPlayerID)
	{	
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$query = "DELETE {$prefix}_player_items
					WHERE player_id = {$intPlayerID}";
		
		//NetDebug::trace($query);

		@mysql_query($query);
		
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);
	}

	/**
     * Removes an Item from the Map and Gives it to the Player
     * @returns returnData with data=true if changes were made
     */
	public function pickupItemFromLocation($intGameID, $intPlayerID, $intItemID, $intLocationID)
	{	
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$this->giveItemToPlayer($prefix, $intItemID, $intPlayerID);
		$this->decrementItemQtyAtLocation($prefix, $intLocationID, 1); 
		return new returnData(0, FALSE);
	}
	
	/**
     * Removes an Item from the players Inventory and Places it on the map
     * @returns returnData with data=true if changes were made
     */
	public function dropItem($intGameID, $intPlayerID, $intItemID, $floatLat, $floatLong)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$this->takeItemFromPlayer($prefix, $intItemID, $intPlayerID);
		$this->giveItemToWorld($prefix, $intItemID, $floatLat, $floatLong, 1);
		return new returnData(0, FALSE);
	}		
	
	/**
     * Removes an Item from the players Inventory
     * @returns returnData with data=true if changes were made
     */
	public function destroyItem($intGameID, $intPlayerID, $intItemID)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$this->takeItemFromPlayer($prefix, $intItemID, $intPlayerID);
		return new returnData(0, FALSE);
	}		
	

	
}
?>