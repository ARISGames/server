<?php
require_once('config.php');

class webbackpack
{
	public function __construct()
	{
		$this->conn = @mysql_connect(Config::dbHost, Config::dbUser, Config::dbPass);
		if(!$this->conn)
			die("Configuration of db incorrect.");
      		mysql_select_db (Config::dbSchema);
      		mysql_query("set names utf8");
		mysql_query("set charset set utf8");
	}

	/*
	* Gets information for web backpack for any player/game pair
	*/
	public function getData($gameId, $playerId)
	{
		$backpack = new stdClass();
		//Get game information (needn't be called for every player- should be moved to own function)

		$query = "SELECT games.game_id, games.name, m.name as media_name, m.file_name as media_url, im.name as icon_media_name, im.file_name as icon_media_url FROM games LEFT JOIN media as m ON games.media_id = m.media_id LEFT JOIN media as im ON games.icon_media_id = im.media_id WHERE games.game_id = '{$gameId}'";

		/* Query- formatted for readability -
		$query = "SELECT 
			games.game_id, games.name, m.name as media_name, m.file_name as media_url, im.name as icon_media_name, im.file_name as icon_media_url 
			FROM 
			games LEFT JOIN 
			media as m ON games.media_id = m.media_id LEFT JOIN 
			media as im ON games.icon_media_id = im.media_id 
			WHERE 
		 	games.game_id = '{$gameId}'";
		*/

		$result = mysql_query($query);
		$game = mysql_fetch_object($result);
		if(!$game) return "Invalid Game ID"; 
		$backpack->game=$game;

		//Get owner information
		$query = "SELECT user_name FROM players WHERE player_id = '{$playerId}'";
		$result = mysql_query($query);
		$name = mysql_fetch_object($result);
		if(!$name) return "Invalid Player ID";
		$backpack->owner=$name;


		/* ATTRIBUTES */
		$query = "SELECT i.item_id, i.name, i.description, i.max_qty_in_inventory, i.weight, i.type, i.url, pi.qty, m.name as media_name, m.file_name as media_file_name, m.game_id as media_game_id, im.name as icon_name, im.file_name as icon_file_name, im.game_id as icon_game_id FROM {$gameId}_player_items as pi, {$gameId}_items as i LEFT JOIN media as m ON i.media_id = m.media_id LEFT JOIN media as im ON i.icon_media_id = im.media_id WHERE pi.player_id = {$playerId} AND pi.item_id = i.item_id AND i.type = 'ATTRIB'";

		/* Query- formatted for readability -
		$query = "SELECT
			i.item_id, i.name, i.description, i.is_attribute, i.max_qty_in_inventory, i.weight, i.type, i.url, 
			pi.qty, 
			m.name as media_name, m.file_name as media_file_name, m.game_id as media_game_id, 
			im.name as icon_name, im.file_name as icon_file_name, im.game_id as icon_game_id 
			FROM 
			{$gameId}_player_items as pi, 
			{$gameId}_items as i LEFT JOIN 
			media as m ON i.media_id = m.media_id LEFT JOIN 
			media as im ON i.icon_media_id = im.media_id
			WHERE 
			pi.player_id = {$playerId} AND pi.item_id = i.item_id
			AND i.type = 'ATTRIB'";
		*/

		$result = mysql_query($query);
		$contents = array();
		while($content = mysql_fetch_object($result))
			$contents[] = $content;

		$backpack->attributes = $contents;


		/* OTHER ITEMS */
		$query = "SELECT i.item_id, i.name, i.description, i.max_qty_in_inventory, i.weight, i.type, i.url, pi.qty, m.name as media_name, m.file_name as media_file_name, m.game_id as media_game_id, im.name as icon_name, im.file_name as icon_file_name, im.game_id as icon_game_id FROM {$gameId}_player_items as pi, {$gameId}_items as i LEFT JOIN media as m ON i.media_id = m.media_id LEFT JOIN media as im ON i.icon_media_id = im.media_id WHERE pi.player_id = {$playerId} AND pi.item_id = i.item_id AND i.type != 'ATTRIB'";

		/* Query- formatted for readability -
		$query = "SELECT
			i.item_id, i.name, i.description, i.is_attribute, i.max_qty_in_inventory, i.weight, i.type, i.url, 
			pi.qty, 
			m.name as media_name, m.file_name as media_file_name, m.game_id as media_game_id, 
			im.name as icon_name, im.file_name as icon_file_name, im.game_id as icon_game_id 
			FROM 
			{$gameId}_player_items as pi, 
			{$gameId}_items as i LEFT JOIN 
			media as m ON i.media_id = m.media_id LEFT JOIN 
			media as im ON i.icon_media_id = im.media_id
			WHERE 
			pi.player_id = {$playerId} AND pi.item_id = i.item_id
			AND i.type != 'ATTRIB'";
		*/

		$result = mysql_query($query);
		$contents = array();
		while($content = mysql_fetch_object($result))
			$contents[] = $content;

		$backpack->items = $contents;

		return $backpack;
	}


	function getGameData($gameId)
	{
		$query = "SELECT DISTINCT player_id FROM player_log WHERE game_id='{$gameId}'";
		$result = mysql_query($query);
		$backPacks = array();
		while($player = mysql_fetch_object($result))
		{
			$backPacks[] = $this->getData($gameId, $player->player_id);
		}
		return $backPacks;
	}
}
