<?php
require_once("module.php");
require_once("items.php");

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
     * @returns 0 with player id for success, 4 for failure
     */
	public function login($strUser,$strPassword)
	{

		$query = "SELECT * FROM players 
				WHERE user_name = '{$strUser}' and password = MD5('{$strPassword}') LIMIT 1";
		
		NetDebug::trace($query);

		$rs = @mysql_query($query);
		if (mysql_num_rows($rs) < 1) return new returnData(4, NULL, 'bad username or password');
		
		$player = @mysql_fetch_object($rs);
		
		Module::appendLog($intPlayerID, NULL, Module::kLOG_LOGIN);
		
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
     * getPlayers
     * @returns all ARIS players
     */
	public function getPlayers()
	{
		$query = "SELECT player_id, user_name, latitude, longitude FROM players";
		
		//NetDebug::trace($query);

		$rs = @mysql_query($query);
		return new returnData(0, $rs);
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
		$timeLimitInMinutes = 20;
		
		/*
		Unoptimized becasue an index cant be used for the timestamp
	
		$query = "SELECT players.player_id, players.user_name, 
				players.latitude, players.longitude, 
				player_log.timestamp 
				FROM players, player_log
				WHERE 
				players.player_id = player_log.player_id AND
				players.last_game_id = '{$intGameID}' AND
				players.player_id != '{$intPlayerID}' AND
				UNIX_TIMESTAMP( NOW( ) ) - UNIX_TIMESTAMP( player_log.timestamp ) <= ( $timeLimitInMinutes * 60 )
				GROUP BY player_id
				";
		 */
		
		$query = "SELECT players.player_id, players.user_name, 
					players.latitude, players.longitude, player_log.timestamp
					FROM players
					LEFT JOIN player_log ON players.player_id = player_log.player_id
					WHERE players.last_game_id =  '{$intGameID}' AND 
					players.player_id != '{$intPlayerID}' AND
					player_log.timestamp > DATE_SUB( NOW( ) , INTERVAL 20 MINUTE ) 
					GROUP BY player_id";
		
		
		NetDebug::trace($query);


		$rs = @mysql_query($query);
		NetDebug::trace(mysql_error());

		
		$array = array();
		while ($object = mysql_fetch_object($rs)) {
			$array[] = $object;
		}
		
		return new returnData(0, $array);
	}
	
	
	/**
     * Start Over a Game for a Player by deleting all items and logs
     * @returns returnData with data=true if changes were made
     */
	public function startOverGameForPlayer($intGameID, $intPlayerID)
	{	
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$query = "DELETE FROM {$prefix}_player_items WHERE player_id = '{$intPlayerID}'";		
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		$query = "UPDATE player_log
					SET deleted = 1
					WHERE player_id = '{$intPlayerID}' AND game_id = '{$intGameID}'";		
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		
		
		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);
	}	
	
	/**
     * updates the lat/long for the player record
     * @returns players with this game id
     */
	public function updatePlayerLocation($intPlayerID, $intGameID, $floatLat, $floatLong)
	{
		$query = "UPDATE players
					SET latitude = {$floatLat} , longitude = {$floatLong}
					WHERE player_id = {$intPlayerID}";
		
		NetDebug::trace($query);

		@mysql_query($query);
		
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		NetDebug::trace("Inserting Log");
		
		Module::appendLog($intPlayerID, $intGameID, Module::kLOG_MOVE, $floatLat, $floatLong);
		
		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);
	}
	
	

	/**
     * Player Viewed a Node, exectute it's actions
     * @returns returnData with data=true if a player state change was made
     */
	public function nodeViewed($intGameID, $intPlayerID, $intNodeID)
	{	
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		Module::applyPlayerStateChanges($prefix, $intPlayerID, Module::kLOG_VIEW_NODE, $intNodeID);
		Module::appendLog($intPlayerID, $intGameID, Module::kLOG_VIEW_NODE, $intNodeID);

		return new returnData(0, TRUE);
	}
	

	/**
     * Player Viewed an Item, exectute it's actions
     * @returns returnData with data=true if a player state change was made
     */
	public function itemViewed($intGameID, $intPlayerID, $intItemID)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		Module::applyPlayerStateChanges($prefix, $intPlayerID, Module::kLOG_VIEW_ITEM, $intItemID);
		Module::appendLog($intPlayerID, $intGameID, Module::kLOG_VIEW_ITEM, $intItemID);
		
		return new returnData(0, TRUE);
	}
	
	public function npcViewed($intGameID, $intPlayerID, $intNpcID)
	{	
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		Module::applyPlayerStateChanges($prefix, $intPlayerID, Module::kLOG_VIEW_NPC, $intNpcID);
		Module::appendLog($intPlayerID, $intGameID, Module::kLOG_VIEW_NPC, $intNpcID);
		
		return new returnData(0, TRUE);
	}	
	

	/**
     * Removes an Item from the Map and Gives it to the Player
     * @returns returnData with data=true if changes were made
     */
	public function pickupItemFromLocation($intGameID, $intPlayerID, $intItemID, $intLocationID, $qty=1)
	{	
		NetDebug::trace("Pickup $qty of item $intItemID");

		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$currentQty = Module::itemQtyInPlayerInventory($intGameID, $intPlayerID, $intItemID);
		$item = Items::getItem($intGameID, $intItemID)->data;
		$maxQty = $item->max_qty_in_inventory; 
		
		NetDebug::trace("Player currently has $currentQty - Item max is $maxQty");

		
		if ($currentQty+$qty > $maxQty  && $maxQty != -1) {
			//we are going over the limit
			$quantity =  $maxQty - $currentQty+$qty;
			if ($quantity < 1) return new returnData(0, FALSE);
		}
		
		Module::giveItemToPlayer($prefix, $intItemID, $intPlayerID, $qty);
		Module::decrementItemQtyAtLocation($prefix, $intLocationID, $qty); 
		
		Module::appendLog($intPlayerID, $intGameID, Module::kLOG_PICKUP_ITEM, $intItemID, $qty);

		return new returnData(0, TRUE);
	}
	
	/**
     * Removes an Item from the players Inventory and Places it on the map
     * @returns returnData with data=true if changes were made
     */
	public function dropItem($intGameID, $intPlayerID, $intItemID, $floatLat, $floatLong, $qty=1)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		Module::takeItemFromPlayer($prefix, $intItemID, $intPlayerID, $qty);
		Module::giveItemToWorld($prefix, $intItemID, $floatLat, $floatLong, $qty);
		
		Module::appendLog($intPlayerID, $intGameID, Module::kLOG_DROP_ITEM, $intItemID, $qty);

		return new returnData(0, FALSE);
	}		
	
	/**
     * Removes an Item from the players Inventory
     * @returns returnData with data=true if changes were made
     */
	public function destroyItem($intGameID, $intPlayerID, $intItemID, $qty=1)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		Module::takeItemFromPlayer($prefix, $intItemID, $intPlayerID, $qty);
		
		Module::appendLog($intPlayerID, $intGameID, Module::kLOG_DESTROY_ITEM, $intItemID, $qty);

		
		return new returnData(0, FALSE);
	}		
	
	/**
     * Log that player viewed the map
     * @returns Always returns 0
     */
	public function mapViewed($intGameID, $intPlayerID)
	{
		Module::appendLog($intPlayerID, $intGameID, Module::kLOG_VIEW_MAP);
		return new returnData(0, FALSE);

	}
	
	/**
     * Log that player viewed the quests
     * @returns Always returns 0
     */	
	public function questsViewed($intGameID, $intPlayerID)
	{
		Module::appendLog($intPlayerID, $intGameID, Module::kLOG_VIEW_QUESTS);
		return new returnData(0, FALSE);

	}
	
	/**
     * Log that player viewed the inventory
     * @returns Always returns 0
     */	
	public function inventoryViewed($intGameID, $intPlayerID)
	{
		Module::appendLog($intPlayerID, $intGameID, Module::kLOG_VIEW_INVENTORY);
		return new returnData(0, FALSE);

	}			
	
}
?>