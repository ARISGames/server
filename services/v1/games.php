<?php
require_once("module.php");
require_once("media.php");
require_once("quests.php");

class Games extends Module
{	

	/**
	 * Fetch all games
	 * @returns Object Recordset for each Game.
	 */
	public function getGames()
	{
		$query = "SELECT * FROM games";
		$rs = @mysql_query($query);
		if (mysql_error())  return new returnData(3, NULL, 'SQL error');
		return new returnData(0, $rs, NULL);		
	}

	/**
	 * Fetch one game
	 * @returns Object Recordset for each Game.
	 */
	public function getGame($intGameID)
	{
		$query = "SELECT * FROM games WHERE game_id = {$intGameID} LIMIT 1";
		$rs = @mysql_query($query);
		if (mysql_error())  return new returnData(3, NULL, 'SQL error');

		$game = @mysql_fetch_object($rs);
		if (!$game) return new returnData(2, NULL, "invalid game id");

		return new returnData(0, $game);

	}

	/**
	 * Fetch all game info needed for the game list
	 * @param integer The player identifier
	 * @param float The player's current latitude
	 * @param float The player's current longitude
	 * @param float The max distance in Meters  
	 * @param bool Include locational or non-locational games  
	 * @param bool Include Games in Development 
	 * @return returnData
	 * @returns a returnData object containing an array of games
	 * @see returnData
	 */

	public function getGamesForPlayerAtLocation($playerId, $latitude, $longitude, $maxDistance=99999999, $locational, $includeGamesinDevelopment)
	{
		if ($includeGamesinDevelopment) $query = "SELECT game_id FROM games WHERE is_locational = '{$locational}'";
		else $query = "SELECT game_id FROM games WHERE is_locational = '{$locational}' AND ready_for_public = TRUE ";

		$gamesRs = @mysql_query($query);
		NetDebug::trace(mysql_error());

		$games = array();

		while ($game = @mysql_fetch_object($gamesRs)) {
			$gameObj = new stdClass;
			$gameObj = Games::getFullGameObject($game->game_id, $playerId, 1, $maxDistance, $latitude, $longitude);
			if($gameObj != NULL){
				//NetDebug::trace("Select");
				$games[] = $gameObj;
			}
			//else NetDebug::trace("Skip");
		}
		return new returnData(0, $games, NULL);

	}		


	/**
	 * Returns:
	 * A single game in the same format as though an array of games was being searched
	 * @param integer The Game to get info for
	 * @param integer The Id of the player requesting the info
	 * @param boolean If true, get all information relating to location. Otherwise, don't bother- saves time.
	 * @param integer Distance at which games further than should be ignored. Not necessary if 'boolGetLocationalInfo' = 0
	 * @param float Not necessary if 'boolGetLocationalInfo' = 0
	 * @param float Not necessary if 'boolGetLocationalInfo' = 0
	 * @returns a whole bunch of stuff. Returns NULL if $boolGetLocationalInfo is set (1) and game is at a distance further than $intSkipAtDistance.
	 */

	public function getOneGame($intGameId, $intPlayerId, $boolGetLocationalInfo = 0, $intSkipAtDistance = 99999999, $latitude = 0, $longitude = 0)
	{
		$games = array();

		$gameObj = new stdClass;
		$gameObj = Games::getFullGameObject($intGameId, $intPlayerId, $boolGetLocationalInfo, $intSkipAtDistance, $latitude, $longitude);
		if($gameObj != NULL){
			NetDebug::trace("Select");
			$games[] = $gameObj;
		}

		return new returnData(0, $games, NULL);

	}	


	/**
	 * Returns:
	 * A single game in the same format as though an array of games was being searched
	 * @param integer The Game to get info for
	 * @param string ISO8601 YYYY-MM-DD Start Date
	 * @param string ISO8601 YYYY-MM-DD End Date
	 * @returns a returnData object containing an array of player log records
	 * @see returnData
	 */
	public function getplayerLogsForGameAndDateRange($gameId, $startDate, $endDate)
	{
		$startDate = urldecode($startDate);
		$endDate = urldecode($endDate);

		$query = "SELECT player_log.*, players.user_name FROM player_log 
			JOIN players ON player_log.player_id = players.player_id WHERE game_id = {$gameId} AND
			timestamp BETWEEN DATE('{$startDate}') AND DATE('{$endDate}')";
		//NetDebug::trace($query);
		$result = mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, mysql_error());
		return new returnData(0, $result, NULL);
	}

	public function getTabBarItemsForGame($intGameId)
	{
		$query = "SELECT * FROM game_tab_data WHERE game_id = '{$intGameId}' ORDER BY tab_index ASC";
		$result = mysql_query($query);

		if(mysql_num_rows($result) == 0){
			$query = "INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$intGameId}', 'QUESTS', '1')";
			@mysql_query($query);
			$query = "INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$intGameId}', 'GPS', '2')";
			@mysql_query($query);
			$query = "INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$intGameId}', 'INVENTORY', '3')";
			@mysql_query($query);
			$query = "INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$intGameId}', 'QR', '4')";
			@mysql_query($query);
			$query = "INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$intGameId}', 'PLAYER', '5')";
			@mysql_query($query);
			$query = "INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$intGameId}', 'NOTE',  '6')";
			@mysql_query($query);
			$query = "INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$intGameId}', 'STARTOVER', '998')";
			@mysql_query($query);
			$query = "INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$intGameId}', 'PICKGAME', '9999')";
			@mysql_query($query);
			$query = "SELECT * FROM game_tab_data WHERE game_id = '{$intGameId}' ORDER BY tab_index ASC";
			$result = mysql_query($query);
		}
		return new returnData(0, $result, NULL);
	}

	public function saveTab($intGameId, $stringTabType, $intIndex)
	{
		$query = "UPDATE game_tab_data SET tab_index = '{$intIndex}' WHERE game_id = '{$intGameId}' AND tab = '{$stringTabType}'";
		mysql_query($query);
		return new returnData(0);
	}

	/**
	 * Returns:
	 * a 'game' object consisting of Game's name, id, description, editors, location(lat/lon), distance, is_locational,
	 * ready_for_public, comments, rating, allow_player_created_locations, delete_player_locations_on_reset, numCompletedQuests, 
	 * totalQuests, on_launch_node_id, game_complete_node_id, game_icon_media_id, icon_media_id, icon_media_url, numPlayers, pc_media_id,
	 * lastUpdated, prefix 
	 * @param integer The Game to get info for
	 * @param integer The Id of the player requesting the info
	 * @param boolean If true, get all information relating to location. Otherwise, don't bother- saves time.
	 * @param integer Distance at which games further than should be ignored. Not necessary if 'boolGetLocationalInfo' = 0
	 * @param float Not necessary if 'boolGetLocationalInfo' = 0
	 * @param float Not necessary if 'boolGetLocationalInfo' = 0
	 * @returns a whole bunch of stuff. Returns NULL if $boolGetLocationalInfo is set (1) and game is at a distance further than $intSkipAtDistance.
	 */

	public function getFullGameObject($intGameId, $intPlayerId, $boolGetLocationalInfo = 0, $intSkipAtDistance = 99999999, $latitude = 0, $longitude = 0){
		$query = "SELECT * FROM games WHERE game_id = '{$intGameId}'";
		$result = mysql_query($query);
		$gameObj = mysql_fetch_object($result);

		//Check if Game Has Been Played
		$query = "SELECT * FROM player_log WHERE game_id = '{$intGameId}' AND player_id = '{$intPlayerId}' AND deleted = 0 LIMIT 1";
		$result = mysql_query($query);
		if(mysql_num_rows($result) > 0){
			$gameObj->has_been_played = true;
		}
		else{
			$gameObj->has_been_played = false;
		}

		//Get Locational Stuff
		if($boolGetLocationalInfo){
			if($gameObj->is_locational == true){
				$nearestLocation = Games::getNearestLocationOfGameToUser($latitude, $longitude, $intGameId);
				$gameObj->latitude = $nearestLocation->latitude;
				$gameObj->longitude = $nearestLocation->longitude;
				$gameObj->distance = $nearestLocation->distance;
				if($gameObj->distance == NULL || $gameObj->distance > $intSkipAtDistance) return NULL;
			}
			else{
				$gameObj->latitude = 0;
				$gameObj->longitude = 0;
				$gameObj->distance = 0;
			}
		}

		//Get Quest Stuff
		//$questsReturnData = Quests::getQuestsForPlayer($intGameId, $intPlayerId);
		//$gameObj->totalQuests = $questsReturnData->data->totalQuests;
		//$gameObj->completedQuests = count($questsReturnData->data->completed);

		//Get Editors
		$query = "SELECT editors.* FROM editors, game_editors
			WHERE game_editors.editor_id = editors.editor_id
			AND game_editors.game_id = {$intGameId}";
		$editorsRs = @mysql_query($query);
		$editor = @mysql_fetch_array($editorsRs);
		$editorsString = $editor['name'];
		while ($editor = @mysql_fetch_array($editorsRs)) {
			$editorsString .= ', ' . $editor['name'];
		}
		$gameObj->editors = $editorsString;

		//Get Num Players
		$query = "SELECT * FROM players
			WHERE last_game_id = {$intGameId}";
		$playersRs = @mysql_query($query);
		$gameObj->numPlayers = @mysql_num_rows($playersRs);

		//Get the media URLs
		//NetDebug::trace("Fetch Media for game_id='{$intGameId}' media_id='{$gameObj->icon_media_id}'");	
		//Icon
		$icon_media_data = Media::getMediaObject($intGameId, $gameObj->icon_media_id);
		$icon_media = $icon_media_data->data; 
		$gameObj->icon_media_url = $icon_media->url_path . $icon_media->file_name;
		//Media
		$media_data = Media::getMediaObject($intGameId, $gameObj->media_id);
		$media = $media_data->data; 
		$gameObj->media_url = $media->url_path . $media->file_name;

		//Calculate the rating
		$query = "SELECT AVG(rating) AS rating FROM game_comments WHERE game_id = {$intGameId}";
		$avRs = @mysql_query($query);
		$avRecord = @mysql_fetch_object($avRs);
		$gameObj->rating = $avRecord->rating;
		if($gameObj->rating == NULL) $gameObj->rating = 0;

		//Getting Comments
		$query = "SELECT * FROM game_comments WHERE game_id = {$intGameId}";
		$result = mysql_query($query);
		$comments = array();
		$x = 0;
		while($row = mysql_fetch_assoc($result)){
			$comments[$x]->playerId = $row['player_id'];
			$query = "SELECT user_name FROM players WHERE player_id = '{$comments[$x]->playerId}'";
			$player = mysql_query($query);
			$playerOb = mysql_fetch_assoc($player);
			$comments[$x]->username = $playerOb['user_name'];
			$comments[$x]->rating = $row['rating'];
			$comments[$x]->text = $row['comment'] == 'Comment' ? "" : $row['comment'];
			$x++;
		}
		$gameObj->comments = $comments;

		//Calculate score
		$gameObj->calculatedScore = ($gameObj->rating - 3) * $x;
		$gameObj->numComments = $x;

		return $gameObj;
	}


	/**
	 * Fetch the games an editor may edit
	 * @returns Object Recordset for each Game.
	 */
	public function getGamesForEditor($intEditorID)
	{
		$query = "SELECT super_admin FROM editors 
			WHERE editor_id = '$intEditorID' LIMIT 1";
		$editor = mysql_fetch_array(mysql_query($query));

		if ($editor['super_admin'] == 1)  {
			NetDebug::trace("getGames: User is super admin, load all games");
			$query = "SELECT * FROM games";
			NetDebug::trace($query);

		}
		else {
			NetDebug::trace("getGames: User is NOT a super admin");

			$query = "SELECT g.* from games g, game_editors ge 
				WHERE g.game_id = ge.game_id AND ge.editor_id = '$intEditorID'";

			NetDebug::trace($query);
		}

		$rs = @mysql_query($query);
		if (mysql_error())  return new returnData(3, NULL, 'SQL error');

		return new returnData(0, $rs, NULL);		

	}


	/**
	 * Create a new game
	 * @returns an integer of the newly created game_id
	 */	
	public function createGame($intEditorID, $strFullName, $strDescription, $intPCMediaID, $intIconMediaID, $intMediaID,
			$boolIsLocational, $boolReadyForPublic, 
			$boolShareToMap, $boolShareToBook, $playerCreateTag, $playerCreateComments, $playerLikeNotes,
			$intIntroNodeId, $intCompleteNodeId, $intInventoryCap, $boolAllowTrading = true)
	{
		$strFullName = addslashes($strFullName);	
		$strDescription = addslashes($strDescription);

		//Check if a game with this name has already been created
		$query = "SELECT * FROM games WHERE name = '{$strFullName}'";
		NetDebug::trace($query);
		if (mysql_num_rows($result = mysql_query($query)) > 0) 
			return new returnData(4, mysql_fetch_object($result)->game_id, 'duplicate name');


		//Create the game record in SQL
		$query = "INSERT INTO games (name, description, pc_media_id, icon_media_id, media_id,
			is_locational, ready_for_public,
			allow_share_note_to_map, allow_share_note_to_book, allow_player_tags, allow_note_comments, allow_note_likes,
			on_launch_node_id, game_complete_node_id, inventory_weight_cap, created, allow_trading)
				VALUES ('{$strFullName}','{$strDescription}','{$intPCMediaID}','{$intIconMediaID}', '{$intMediaID}',
						'{$boolIsLocational}', '{$boolReadyForPublic}', 
						'{$boolShareToMap}', '{$boolShareToBook}', '{$playerCreateTag}', '{$playerCreateComments}','{$playerLikeNotes}',
						'{$intIntroNodeId}','{$intCompleteNodeId}','{$intInventoryCap}', NOW(), '{$boolAllowTrading}')";
		@mysql_query($query);
		if (mysql_error())  return new returnData(6, NULL, "cannot create game record using SQL: $query");
		$newGameID = mysql_insert_id();
		$strShortName = mysql_insert_id();

		//HACK: We should change the engine to look at the game_id, but for now we will just set the
		//short name to the new game id
		$query = "UPDATE games SET prefix = '{$strShortName}_' WHERE game_id = '{$newGameID}'";
		@mysql_query($query);
		if (mysql_error())  return new returnData(6, NULL, 'cannot update game record');


		//Make the creator an editor of the game
		$query = "INSERT INTO game_editors (game_id,editor_id) VALUES ('{$newGameID}','{$intEditorID}')";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create game_editors record');


		//Create the SQL tables

		$query = "CREATE TABLE {$strShortName}_items (
			item_id int(11) unsigned NOT NULL auto_increment,
				name varchar(255) NOT NULL,
				description text NOT NULL,
				is_attribute ENUM(  '0',  '1' ) NOT NULL DEFAULT  '0',
				icon_media_id int(10) unsigned NOT NULL default '0',
				media_id int(10) unsigned NOT NULL default '0',
				dropable enum('0','1') NOT NULL default '0',
				destroyable enum('0','1') NOT NULL default '0',
				max_qty_in_inventory INT NOT NULL DEFAULT  '-1' COMMENT  '-1 for infinite, 0 if it can''t be picked up',
				creator_player_id int(10) unsigned NOT NULL default '0',
				origin_latitude double NOT NULL default '0',
				origin_longitude double NOT NULL default '0',
				origin_timestamp timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
				weight INT UNSIGNED NOT NULL DEFAULT  '0',
				url TINYTEXT NOT NULL,
				type ENUM(  'NORMAL',  'ATTRIB',  'URL', 'NOTE') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT  'NORMAL',
				tradeable TINYINT(1) NOT NULL DEFAULT 1,
				PRIMARY KEY  (item_id)
					)ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create items table' . mysql_error());

		$query = "CREATE TABLE {$strShortName}_player_state_changes (
			id int(10) unsigned NOT NULL auto_increment,
			   event_type enum('VIEW_ITEM', 'VIEW_NODE', 'VIEW_NPC', 'VIEW_WEBPAGE', 'VIEW_AUGBUBBLE', 'RECEIVE_WEBHOOK' ) NOT NULL,
			   event_detail INT UNSIGNED NOT NULL,
			   action enum('GIVE_ITEM','TAKE_ITEM') NOT NULL,
			   action_detail int(10) unsigned NOT NULL,
			   action_amount INT NOT NULL DEFAULT  '1',
			   PRIMARY KEY  (id),
			   KEY `action_amount` (`action_amount`),
			   KEY `event_lookup` (`event_type`,`event_detail`)
				   )ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create player_state_changes table' . mysql_error());



		$query = "CREATE TABLE {$strShortName}_requirements (
			requirement_id int(11) NOT NULL auto_increment,
				       content_type enum('Node','QuestDisplay','QuestComplete','Location','OutgoingWebHook','Spawnable', 'CustomMap') NOT NULL,
				       content_id int(10) unsigned NOT NULL,
				       requirement ENUM('PLAYER_HAS_ITEM','PLAYER_VIEWED_ITEM','PLAYER_VIEWED_NODE','PLAYER_VIEWED_NPC','PLAYER_VIEWED_WEBPAGE','PLAYER_VIEWED_AUGBUBBLE','PLAYER_HAS_UPLOADED_MEDIA_ITEM', 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE','PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO','PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO','PLAYER_HAS_COMPLETED_QUEST','PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK', 'PLAYER_HAS_NOTE', 'PLAYER_HAS_NOTE_WITH_TAG', 'PLAYER_HAS_NOTE_WITH_LIKES', 'PLAYER_HAS_NOTE_WITH_COMMENTS', 'PLAYER_HAS_GIVEN_NOTE_COMMENTS') NOT NULL,
				       boolean_operator enum('AND','OR') NOT NULL DEFAULT 'AND',	
				       not_operator ENUM(  'DO',  'NOT' ) NOT NULL DEFAULT 'DO',
				       group_operator ENUM(  'SELF',  'GROUP' ) NOT NULL DEFAULT 'SELF',
				       requirement_detail_1 VARCHAR(30) NULL,
				       requirement_detail_2 VARCHAR(30) NULL,
				       requirement_detail_3 VARCHAR(30) NULL,
				       requirement_detail_4 VARCHAR(30) NULL,
				       PRIMARY KEY  (requirement_id),
				       KEY `contentIndex` (  `content_type` ,  `content_id` )
					       )ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create requirments table' . mysql_error());

		$query = "CREATE TABLE {$strShortName}_locations (
			location_id int(11) NOT NULL auto_increment,
				    name varchar(255) NOT NULL,
				    description tinytext NOT NULL,
				    latitude double NOT NULL default '43.0746561',
				    longitude double NOT NULL default '-89.384422',
				    error double NOT NULL default '5',
				    type enum('Node','Event','Item','Npc','WebPage','AugBubble', 'PlayerNote') NOT NULL DEFAULT 'Node',
				    type_id int(11) NOT NULL,
				    icon_media_id int(10) unsigned NOT NULL default '0',
				    item_qty int(11) NOT NULL default '0' COMMENT  '-1 for infinite. Only effective for items',
				    hidden enum('0','1') NOT NULL default '0',
				    force_view enum('0','1') NOT NULL default '0',
				    allow_quick_travel enum('0','1') NOT NULL default '0',
				    wiggle TINYINT(1) NOT NULL default '0',
				    show_title TINYINT(1) NOT NULL default '0',
				    spawnstamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				    PRIMARY KEY  (location_id)
					    )ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		NetDebug::trace($query);	
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create locations table: ' . mysql_error());

		$query = "CREATE TABLE {$strShortName}_quests (
			quest_id int(11) unsigned NOT NULL auto_increment,
				 name tinytext NOT NULL,
				 description text NOT NULL,
				 text_when_complete tinytext NOT NULL COMMENT 'This is the txt that displays on the completed quests screen',
				 icon_media_id int(10) unsigned NOT NULL default '0',
				 sort_index int(10) unsigned NOT NULL default '0',
				 PRIMARY KEY  (quest_id)
					 )ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create quests table');

		$query = "CREATE TABLE {$strShortName}_nodes (
			node_id int(11) unsigned NOT NULL auto_increment,
				title varchar(255) NOT NULL,
				text text NOT NULL,
				opt1_text varchar(100) default NULL,
				opt1_node_id int(11) unsigned NOT NULL default '0',
				opt2_text varchar(100) default NULL,
				opt2_node_id int(11) unsigned NOT NULL default '0',
				opt3_text varchar(100) default NULL,
				opt3_node_id int(11) unsigned NOT NULL default '0',
				require_answer_incorrect_node_id int(11) unsigned NOT NULL default '0',
				require_answer_string varchar(50) default NULL,
				require_answer_correct_node_id int(10) unsigned NOT NULL default '0',
				media_id int(10) unsigned NOT NULL default '0',
				icon_media_id int(10) unsigned NOT NULL default '0',
				PRIMARY KEY  (node_id)
					)ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create nodes table');

		$query = "CREATE TABLE {$strShortName}_npc_conversations (
			conversation_id int(11) NOT NULL auto_increment,
					npc_id int(10) unsigned NOT NULL default '0',
					node_id int(10) unsigned NOT NULL default '0',
					text tinytext NOT NULL,
					sort_index int(10) unsigned NOT NULL default '0',
					PRIMARY KEY  (conversation_id)
						)ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create conversations table');

		$query = "CREATE TABLE {$strShortName}_npcs (
			npc_id int(10) unsigned NOT NULL auto_increment,
			       name varchar(255) NOT NULL default '',
			       description TEXT NOT NULL,
			       text TEXT NOT NULL,
			       closing TEXT NOT NULL,
			       media_id int(10) unsigned NOT NULL default '0',
			       icon_media_id int(10) unsigned NOT NULL default '0',
			       PRIMARY KEY  (npc_id)
				       )ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create npcs table');


		$query = "CREATE TABLE {$strShortName}_player_items (
			`id` int(11) NOT NULL auto_increment,
			`player_id` int(11) unsigned NOT NULL default '0',
			`item_id` int(11) unsigned NOT NULL default '0',
			`qty` int(11) NOT NULL default '0',
			`timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
                        `viewed` tinyint(1) NOT NULL default '0',
			PRIMARY KEY  (`id`),
			UNIQUE KEY `unique` (`player_id`,`item_id`),
			KEY `player_id` (`player_id`),
			KEY `item_id` (`item_id`),
			KEY `qty` (`qty`)
				)ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create player_items table');


		$query = "CREATE TABLE {$strShortName}_qrcodes (
			qrcode_id int(11) NOT NULL auto_increment,
				  link_type enum('Location') NOT NULL default 'Location',
				  link_id int(11) NOT NULL,
				  code varchar(255) NOT NULL,
				  match_media_id INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0',
				  fail_text varchar(256) NOT NULL DEFAULT \"This code doesn't mean anything right now. You should come back later.\",
				  PRIMARY KEY  (qrcode_id),
				  UNIQUE KEY `code` (`code`)
					  )ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create qrcodes table');							

		$query = "CREATE TABLE {$strShortName}_folders (
			folder_id int(10) unsigned NOT NULL auto_increment,
				  name varchar(50) collate utf8_unicode_ci NOT NULL,
				  parent_id int(11) NOT NULL default '0',
				  previous_id int(11) NOT NULL default '0',
				  is_open ENUM('0','1') NOT NULL DEFAULT  '0',
				  PRIMARY KEY  (folder_id)
					  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create folders table');	



		$query = "CREATE TABLE {$strShortName}_folder_contents (
			object_content_id int(10) unsigned NOT NULL auto_increment,
					  folder_id int(10) NOT NULL default '0',
					  content_type enum('Node','Item','Npc','WebPage','AugBubble', 'PlayerNote', 'CustomMap') collate utf8_unicode_ci NOT NULL default 'Node',
					  content_id int(10) unsigned NOT NULL default '0',
					  previous_id int(10) unsigned NOT NULL default '0',
					  PRIMARY KEY  (object_content_id)
						  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create folder contents table: ' . mysql_error());	

		$query = "INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$strShortName}', 'QUESTS', '1')";
		@mysql_query($query);
		$query = "INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$strShortName}', 'GPS', '2')";
		@mysql_query($query);
		$query = "INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$strShortName}', 'INVENTORY', '3')";
		@mysql_query($query);
		$query = "INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$strShortName}', 'QR', '4')";
		@mysql_query($query);
		$query = "INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$strShortName}', 'PLAYER', '5')";
		@mysql_query($query);
		$query = "INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$strShortName}', 'NOTE', '6')";
		@mysql_query($query);
		$query = "INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$strShortName}', 'STARTOVER', '998')";
		@mysql_query($query);
		$query = "INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$strShortName}', 'PICKGAME', '9999')";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create game_tab_data table- ' . mysql_error());	

		$media = new Media();
		$returnObject = $media->getMediaDirectory($newGameID);
		$newGameDirectory = $returnObject->data;

		mkdir($newGameDirectory,0777);

		return new returnData(0, $newGameID, NULL);


	}


	/**
	 * Updates a game's information
	 * @returns true if a record was updated, false otherwise
	 */	
	public function updateGame($intGameID, $strName, $strDescription, $intPCMediaID, $intIconMediaID, $intMediaID,
			$boolIsLocational, $boolReadyForPublic,
			$boolShareToMap, $boolShareToBook, $playerCreateTag, $playerCreateComments, $playerLikeNotes,
			$intIntroNodeId, $intCompleteNodeId, $intInventoryCap, $boolAllowTrading = true)
	{
		$strName = addslashes($strName);	
		$strDescription = addslashes($strDescription);

		$query = "UPDATE games 
			SET 
			name = '{$strName}',
			     description = '{$strDescription}',
			     pc_media_id = '{$intPCMediaID}',
			     icon_media_id = '{$intIconMediaID}',
			     media_id = '{$intMediaID}',
			     allow_share_note_to_map = '{$boolShareToMap}',
			     allow_share_note_to_book = '{$boolShareToBook}',
			     allow_player_tags = '{$playerCreateTag}',
			     allow_note_comments = '{$playerCreateComments}',
			     allow_note_likes = '{$playerLikeNotes}',
			     is_locational = '{$boolIsLocational}',
			     ready_for_public = '{$boolReadyForPublic}',
			     on_launch_node_id = '{$intIntroNodeId}',
			     game_complete_node_id = '{$intCompleteNodeId}',
			     inventory_weight_cap = '{$intInventoryCap}',
                             allow_trading = '{$boolAllowTrading}'
				     WHERE game_id = {$intGameID}";
		mysql_query($query);
		if (mysql_error()) return new returnData(3, false, "SQL Error: " . mysql_error());

		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);		
	}		


	/**
	 * Updates a game's Player Character Media
	 * @returns true if a record was updated, false otherwise
	 */	
	public function setPCMediaID($intGameID, $intPCMediaID)
	{

		$query = "UPDATE games 
			SET pc_media_id = '{$intPCMediaID}'
			WHERE game_id = {$intGameID}";
		mysql_query($query);
		if (mysql_error()) return new returnData(3, false, "SQL Error");

		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);		
	}			


	/**
	 * Updates all game databases using upgradeGameDatabase
	 */	
	public function upgradeGameDatabases($startingGameIndex = 0) 
	{		
		NetDebug::trace("Upgrading Game Databases:\n");

		//Create 'spawnables' table
		$query = "CREATE TABLE spawnables (
			spawnable_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				     game_id INT NOT NULL,
				     type ENUM('Node', 'Item', 'Npc', 'WebPage', 'AugBubble', 'PlayerNote') NOT NULL,
				     type_id INT NOT NULL,
				     location_name TINYTEXT NOT NULL DEFAULT '',
				     amount INT NOT NULL DEFAULT 1,
				     min_area INT NOT NULL DEFAULT 0,
				     max_area INT NOT NULL DEFAULT 10,
				     amount_restriction ENUM('PER_PLAYER', 'TOTAL') NOT NULL DEFAULT 'PER_PLAYER',
				     location_bound_type ENUM('PLAYER', 'LOCATION') NOT NULL DEFAULT 'PLAYER',
				     latitude DOUBLE NOT NULL default 0,
				     longitude DOUBLE NOT NULL default 0,
				     spawn_probability DOUBLE NOT NULL default 1.0,
				     spawn_rate INT NOT NULL DEFAULT 10,
				     delete_when_viewed TINYINT(1) NOT NULL DEFAULT 0,
				     last_spawned TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				     error_range INT NOT NULL DEFAULT 10,
				     force_view TINYINT(1) NOT NULL DEFAULT 0,
				     hidden TINYINT(1) NOT NULL DEFAULT 0,
				     allow_quick_travel TINYINT(1) NOT NULL DEFAULT 0,
				     wiggle TINYINT(1) NOT NULL DEFAULT 1,
				     active TINYINT(1) NOT NULL DEFAULT 1,
				     show_title TINYINT(1) NOT NULL DEFAULT 0,
				     time_to_live INT NOT NULL DEFAULT 100);";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());  

		$query = "ALTER TABLE player_log CHANGE timestamp timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP;";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());  

		$query = "ALTER TABLE games ADD COLUMN allow_note_likes TINYINT(1) NOT NULL DEFAULT 1";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());  

		$query = "ALTER TABLE spawnables ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());  

		$query = "ALTER TABLE spawnables ADD COLUMN location_name TINYTEXT NOT NULL DEFAULT ''";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());  

		$query = "ALTER TABLE spawnables ADD COLUMN show_title TINYINT(1) NOT NULL DEFAULT 1";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());  

		$query = "ALTER TABLE spawnables ADD COLUMN min_area INT NOT NULL DEFAULT 0";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());  

		$query = "ALTER TABLE spawnables CHANGE area max_area INT NOT NULL DEFAULT 5";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());  

		$query = "ALTER TABLE spawnables CHANGE last_spawn last_spawned TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());  

		//add stuff to spawnables
		$query = "ALTER TABLE game_tab_data CHANGE tab tab ENUM('GPS','NEARBY','QUESTS','INVENTORY','PLAYER','QR','NOTE','STARTOVER','PICKGAME') NOT NULL;";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());

		//make PICKGAME last tab
		$query = "UPDATE game_tab_data SET tab_index ='9999' WHERE tab='PICKGAME'";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());  

		//restructure table
		$query = "ALTER TABLE game_tab_data CHANGE tab tab ENUM('GPS','NEARBY','QUESTS','INVENTORY','PLAYER','QR','NOTE','STARTOVER','PICKGAME') NOT NULL;";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());

		//Deletes all 'STARTOVER' tabs (to be re-added by the next function. Necessary to prevent dups)
		$query = "DELETE FROM game_tab_data WHERE tab = 'STARTOVER'";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());  

		$query = "SELECT game_id FROM game_tab_data WHERE game_id > $startingGameIndex GROUP BY game_id";
		$result = mysql_query($query);
		while($gid = mysql_fetch_object($result))
		{
			$query = "INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ($gid->game_id, 'STARTOVER', 998);";
			$res = mysql_query($query);
		}

		$query = "SELECT * FROM games WHERE game_id > $startingGameIndex ORDER BY game_id";
		$rs = mysql_query($query);
		if (mysql_error())  return new returnData(3, NULL, 'SQL error');

		//add notebook-icon
		$query = "INSERT INTO media (media_id,game_id,name,file_name,is_icon) VALUES (94,0,\"Default Note\",\"Notebook-icon.png\", 1);";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());

		$query = "ALTER TABLE games ADD COLUMN allow_trading TINYINT(1) NOT NULL DEFAULT 1;";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());
        

		$query = "CREATE TABLE `overlays` {
			
        `overlay_id` int(11) NOT NULL AUTO_INCREMENT,
        `game_id` int(11) DEFAULT NULL,
        `sort_order` int(11) DEFAULT NULL,
        `alpha` decimal(3,2) DEFAULT NULL,
        `num_tiles` int(11) DEFAULT NULL,
        `game_overlay_id` int(11) DEFAULT NULL,
        `name` varchar(100) DEFAULT NULL,
        `description` varchar(500) DEFAULT NULL,
        `icon_media_id` int(11) DEFAULT NULL,
        `sort_index` int(11) DEFAULT NULL,
        `folder_name` varchar(200) DEFAULT NULL,
        `file_uploaded` int(11) DEFAULT NULL,
        PRIMARY KEY (`overlay_id`)
        
        }";
	mysql_query($query);
	NetDebug::trace("$query" . ":" . mysql_error());

	$query = "CREATE TABLE `overlay_tiles` {
		`overlay_id` int(11) DEFAULT NULL,
		`media_id` int(11) DEFAULT NULL,
		`zoom` int(11) DEFAULT NULL,
		`x` int(11) DEFAULT NULL,
		`x_max` int(11) DEFAULT NULL,
		`y` int(11) DEFAULT NULL,
		`y_max` int(11) DEFAULT NULL
	}";
	mysql_query($query);
	NetDebug::trace("$query" . ":" . mysql_error());

	$query = "CREATE TABLE fountains
		(fountain_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
		 game_id INT NOT NULL, 
		 type ENUM('Location', 'Spawnable') NOT NULL, 
		 location_id INT NOT NULL, 
		 spawn_probability double NOT NULL DEFAULT 50, 
		 spawn_rate INT NOT NULL DEFAULT 10, 
		 max_amount INT NOT NULL DEFAULT 10, 
		 last_spawned TIMESTAMP NOT NULL,
		 active TINYINT(1) NOT NULL DEFAULT 1);";
	mysql_query($query);
	NetDebug::trace("$query" . ":" . mysql_error());

	while ($game = mysql_fetch_object($rs)) 
	{
		NetDebug::trace("Upgrade Game: {$game->game_id}");
		$upgradeResult = Games::upgradeGameDatabase($game->game_id);
	}

	return new returnData(0);
	}



	/**
	 * Updates a game's database to the most current version
	 */	
	public function upgradeGameDatabase($intGameID)
	{	
		set_time_limit(30);

		Module::serverErrorLog("Upgrade Game $intGameID");

		$prefix = Module::getPrefix($intGameID);                

		$query = "ALTER TABLE ".$prefix."_locations ADD COLUMN spawnstamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());  

		$query = "ALTER TABLE ".$prefix."_locations ADD COLUMN wiggle TINYINT(1) NOT NULL DEFAULT 0";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error()); 
		//add show_title
		$query = "ALTER TABLE ".$prefix."_locations ADD column show_title tinyint(1) NOT NULL DEFAULT 1;";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error()); 

		$query = "ALTER TABLE ".$prefix."_requirements CHANGE content_type content_type ENUM('Node','QuestDisplay','QuestComplete','Location','OutgoingWebHook','Spawnable', 'CustomMap')";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());  

		$query = "ALTER TABLE ".$prefix."_items ADD COLUMN tradeable TINYINT(1) NOT NULL DEFAULT 1;";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());
        
        $query = "ALTER TABLE ".$prefix."_player_items ADD COLUMN viewed TINYINT(1) NOT NULL DEFAULT 0;";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());

		/* MAKE SURE TO REFLECT ANY CHANGES IN HERE IN createGame AS WELL!!!!! */
	}

	/**
	 * Sets a game's name
	 * @returns true if a record was updated, false otherwise
	 */	
	public function setGameName($intGameID, $strNewName)
	{
		$returnData = new returnData(0, mysql_query($query), NULL);

		$strNewGameName = addslashes($strNewGameName);	

		$query = "UPDATE games SET name = '{$strNewName}' WHERE game_id = {$intGameID}";
		mysql_query($query);
		if (mysql_error()) return new returnData(3, false, "SQL Error");

		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);		
	}		

	/**
	 * Copy a game to a new game
	 * Not yet implemented
	 * @returns true on success
	 */	
	public function copyGame($intSourceGameID, $strNewShortName, $strNewFullName)
	{
		return new returnData(5, NULL, "Copy Game Not Implemented on Server");
	}	

	/**
	 * Delete a new game
	 * @returns returnCode = 0 on success
	 */	
	public function deleteGame($intGameID)
	{
		$query = "SELECT * FROM games ORDER BY game_id";
		$rs = mysql_query($query);
		while ($game = mysql_fetch_object($rs))
		{
			$i = $game->game_id;
			if($i != 159 && $i != 2625 && $i != 3795 && $i != 344 && $i != 4301 &&  $i != 4305 ){
				NetDebug::trace("Delete Game: {$i}");
				Games::oldDeleteGame($i);
			}
		}
		return 0;
	}

	/**
	 * Perform table migration
	 * Used to migrate tables from tables starting with game_id prefixes to general tables
	 * @returns returnCode = 0 on success
	 */
	public function migrateTables()
	{
		set_time_limit(100000000);

	//	Test::killOrphansBeforeMigration();
		Games::createNewTablesForMigration();
		$query = "SELECT * FROM games ORDER BY game_id";
		$rs = mysql_query($query);
		while ($game = mysql_fetch_object($rs)) 
		{
			NetDebug::trace("Migrate Game: {$game->game_id}");
			Module::serverErrorLog("Migrating Game ID: {$game->game_id}");
			$newIdsArray = Games::migrateGame($game->game_id);
			Games::updateXMLAfterMigration($newIdsArray, $game->game_id); 

			//IMPORTANT RECOMMENT IN BELOW LINES
			Games::oneTimeTableUpdate($newIdsArray, $game->game_id, $game->on_launch_node_id);
			//Fetch the table names for this game
			           $query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='" . Config::dbSchema . "' AND TABLE_NAME LIKE '{$game->game_id}\_%'";
			              NetDebug::trace($query);
			              $result = mysql_query($query);
			              if (mysql_error()) return new returnData(3, NULL, 'SQL Error');	

			//Delete all tables for this game
			                 while ($table = mysql_fetch_array($result)) {
			                  $query = "DROP TABLE {$table['TABLE_NAME']}";
			                 NetDebug::trace($query);
			                 mysql_query($query);
			                 if (mysql_error()) return new returnData(3, NULL, 'SQL Error');	
			                 }
		}
		$query = "ALTER TABLE media CHANGE file_name file_path VARCHAR(255) NOT NULL;";
		@mysql_query($query);

		$query = "UPDATE media SET file_path = CONCAT(game_id,'/',file_path);";
		@mysql_query($query);
		
		$query = "ALTER TABLE game_tab_data ADD COLUMN tab_detail_1 INT DEFAULT 0";
          	@mysql_query($query);	

		$query = "ALTER TABLE quests ADD COLUMN exit_to_tab ENUM('NONE', 'GPS','NEARBY','QUESTS','INVENTORY','PLAYER','QR','NOTE','STARTOVER','PICKGAME');";
		@mysql_query($query);

		$query = "ALTER TABLE media ADD COLUMN display_name VARCHAR(32) DEFAULT '';";
		@mysql_query($query);

		return 0;
	}

	/**
	 * Create new tables for table migration
	 * Used to migrate tables from tables starting with game_id prefixes to general tables
	 */
	public function createNewTablesForMigration()
	{
		//Create the SQL tables
		$query = "CREATE TABLE items (
			item_id int(11) unsigned NOT NULL auto_increment,
				game_id INT NOT NULL,
				name varchar(255) NOT NULL,
				description text NOT NULL,
				is_attribute ENUM(  '0',  '1' ) NOT NULL DEFAULT  '0',
				icon_media_id int(10) unsigned NOT NULL default '0',
				media_id int(10) unsigned NOT NULL default '0',
				dropable enum('0','1') NOT NULL default '0',
				destroyable enum('0','1') NOT NULL default '0',
				max_qty_in_inventory INT NOT NULL DEFAULT  '-1' COMMENT  '-1 for infinite, 0 if it can''t be picked up',
				creator_player_id int(10) unsigned NOT NULL default '0',
				origin_latitude double NOT NULL default '0',
				origin_longitude double NOT NULL default '0',
				origin_timestamp timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
				weight INT UNSIGNED NOT NULL DEFAULT  '0',
				url TINYTEXT NOT NULL,
				type ENUM(  'NORMAL',  'ATTRIB',  'URL', 'NOTE') NOT NULL DEFAULT  'NORMAL',
				tradeable TINYINT(1) NOT NULL DEFAULT 1,
				PRIMARY KEY  (item_id),
				KEY game_id (game_id)
					)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create items table' . mysql_error());

		$query = "CREATE TABLE player_state_changes (
			id int(10) unsigned NOT NULL auto_increment,
			   game_id INT NOT NULL,
			   event_type enum('VIEW_ITEM', 'VIEW_NODE', 'VIEW_NPC', 'VIEW_WEBPAGE', 'VIEW_AUGBUBBLE', 'RECEIVE_WEBHOOK' ) NOT NULL,
			   event_detail INT UNSIGNED NOT NULL,
			   action enum('GIVE_ITEM','TAKE_ITEM') NOT NULL,
			   action_detail int(10) unsigned NOT NULL,
			   action_amount INT NOT NULL DEFAULT  '1',
			   PRIMARY KEY  (id),
			   KEY game_event_lookup (game_id, event_type, event_detail)
				   )ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create player_state_changes table' . mysql_error());

		$query = "CREATE TABLE requirements (
			requirement_id int(11) NOT NULL auto_increment,
				       game_id INT NOT NULL,
				       content_type enum('Node','QuestDisplay','QuestComplete','Location','OutgoingWebHook','Spawnable') NOT NULL,
				       content_id int(10) unsigned NOT NULL,
				       requirement ENUM('PLAYER_HAS_ITEM','PLAYER_VIEWED_ITEM','PLAYER_VIEWED_NODE','PLAYER_VIEWED_NPC','PLAYER_VIEWED_WEBPAGE','PLAYER_VIEWED_AUGBUBBLE','PLAYER_HAS_UPLOADED_MEDIA_ITEM', 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE','PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO','PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO','PLAYER_HAS_COMPLETED_QUEST','PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK', 'PLAYER_HAS_NOTE', 'PLAYER_HAS_NOTE_WITH_TAG', 'PLAYER_HAS_NOTE_WITH_LIKES', 'PLAYER_HAS_NOTE_WITH_COMMENTS', 'PLAYER_HAS_GIVEN_NOTE_COMMENTS') NOT NULL,
				       boolean_operator enum('AND','OR') NOT NULL DEFAULT 'AND',	
				       not_operator ENUM(  'DO',  'NOT' ) NOT NULL DEFAULT 'DO',
				       group_operator ENUM(  'SELF',  'GROUP' ) NOT NULL DEFAULT 'SELF',
				       requirement_detail_1 VARCHAR(30) NULL,
				       requirement_detail_2 VARCHAR(30) NULL,
				       requirement_detail_3 VARCHAR(30) NULL,
				       requirement_detail_4 VARCHAR(30) NULL,
				       PRIMARY KEY  (requirement_id),
				       KEY game_content_index (game_id, content_type, content_id)
					       )ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create requirments table' . mysql_error());

		$query = "CREATE TABLE locations (
			location_id int(11) NOT NULL auto_increment,
				    game_id INT NOT NULL,
				    name varchar(255) NOT NULL,
				    description tinytext NOT NULL,
				    latitude double NOT NULL default '43.0746561',
				    longitude double NOT NULL default '-89.384422',
				    error double NOT NULL default '5',
				    type enum('Node','Event','Item','Npc','WebPage','AugBubble', 'PlayerNote') NOT NULL DEFAULT 'Node',
				    type_id int(11) NOT NULL,
				    icon_media_id int(10) unsigned NOT NULL default '0',
				    item_qty int(11) NOT NULL default '0' COMMENT  '-1 for infinite. Only effective for items',
				    hidden enum('0','1') NOT NULL default '0',
				    force_view enum('0','1') NOT NULL default '0',
				    allow_quick_travel enum('0','1') NOT NULL default '0',
				    wiggle TINYINT(1) NOT NULL default '0',
				    show_title TINYINT(1) NOT NULL default '0',
				    spawnstamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				    PRIMARY KEY  (location_id),
			            KEY game_latitude (game_id, latitude),
                                    KEY game_longitude (game_id, longitude)
					    )ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		NetDebug::trace($query);	
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create locations table: ' . mysql_error());

		$query = "CREATE TABLE quests (
			quest_id int(11) unsigned NOT NULL auto_increment,
				 game_id INT NOT NULL,
				 name tinytext NOT NULL,
				 description text NOT NULL,
				 text_when_complete tinytext NOT NULL COMMENT 'This is the txt that displays on the completed quests screen',
				 icon_media_id int(10) unsigned NOT NULL default '0',
				 sort_index int(10) unsigned NOT NULL default '0',
				 PRIMARY KEY  (quest_id),
             			 KEY game_id (game_id)
					 )ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create quests table');

		$query = "CREATE TABLE nodes (
			node_id int(11) unsigned NOT NULL auto_increment,
				game_id INT NOT NULL,
				title varchar(255) NOT NULL,
				text text NOT NULL,
				opt1_text varchar(100) default NULL,
				opt1_node_id int(11) unsigned NOT NULL default '0',
				opt2_text varchar(100) default NULL,
				opt2_node_id int(11) unsigned NOT NULL default '0',
				opt3_text varchar(100) default NULL,
				opt3_node_id int(11) unsigned NOT NULL default '0',
				require_answer_incorrect_node_id int(11) unsigned NOT NULL default '0',
				require_answer_string varchar(50) default NULL,
				require_answer_correct_node_id int(10) unsigned NOT NULL default '0',
				media_id int(10) unsigned NOT NULL default '0',
				icon_media_id int(10) unsigned NOT NULL default '0',
				PRIMARY KEY  (node_id),
				KEY game_id (game_id)
					)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create nodes table');

		$query = "CREATE TABLE npc_conversations (
			conversation_id int(11) NOT NULL auto_increment,
					npc_id int(10) unsigned NOT NULL default '0',
					node_id int(10) unsigned NOT NULL default '0',
					game_id INT NOT NULL,
					text tinytext NOT NULL,
					sort_index int(10) unsigned NOT NULL default '0',
					PRIMARY KEY  (conversation_id),
					KEY game_npc_node (game_id, npc_id, node_id),
					KEY game_node (game_id, node_id)
						)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create conversations table');

		$query = "CREATE TABLE npcs (
			npc_id int(10) unsigned NOT NULL auto_increment,
			       game_id INT NOT NULL,
			       name varchar(255) NOT NULL default '',
			       description TEXT NOT NULL,
			       text TEXT NOT NULL,
			       closing TEXT NOT NULL,
			       media_id int(10) unsigned NOT NULL default '0',
			       icon_media_id int(10) unsigned NOT NULL default '0',
			       PRIMARY KEY  (npc_id),
 			       KEY game_id (game_id)
				       )ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create npcs table');

		$query = "CREATE TABLE player_items (
			id int(11) NOT NULL auto_increment,
			player_id int(11) unsigned NOT NULL default '0',
			game_id INT NOT NULL,
			item_id int(11) unsigned NOT NULL default '0',
			qty int(11) NOT NULL default '0',
			viewed tinyint(1) NOT NULL default '0',
			timestamp timestamp NOT NULL default CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY game_player_item (game_id, player_id, item_id)
				)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create player_items table');

		$query = "CREATE TABLE qrcodes (
			qrcode_id int(11) NOT NULL auto_increment,
				  game_id INT NOT NULL,
				  link_type enum('Location') NOT NULL default 'Location',
				  link_id int(11) NOT NULL,
				  code varchar(255) NOT NULL,
				  match_media_id INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0',
				  fail_text varchar(256) NOT NULL DEFAULT \"This code doesn't mean anything right now. You should come back later.\",
				  PRIMARY KEY  (qrcode_id),
 			          KEY game_link_id (game_id, link_type, link_id)
					  )ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create qrcodes table');							

		$query = "CREATE TABLE folders (
			folder_id int(10) unsigned NOT NULL auto_increment,
				  game_id INT NOT NULL,
				  name varchar(50) collate utf8_unicode_ci NOT NULL,
				  parent_id int(11) NOT NULL default '0',
				  previous_id int(11) NOT NULL default '0',
				  is_open ENUM('0','1') NOT NULL DEFAULT  '0',
				  PRIMARY KEY  (folder_id),
				  KEY game_parent (game_id, parent_id),
			    	  KEY game_previous (game_id, previous_id)
					  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create folders table');	

		$query = "CREATE TABLE folder_contents (
			object_content_id int(10) unsigned NOT NULL auto_increment,
					  folder_id int(10) NOT NULL default '0',
					  game_id INT NOT NULL,
					  content_type enum('Node','Item','Npc','WebPage','AugBubble', 'PlayerNote') collate utf8_unicode_ci NOT NULL default 'Node',
					  content_id int(10) unsigned NOT NULL default '0',
					  previous_id int(10) unsigned NOT NULL default '0',
					  PRIMARY KEY  (object_content_id),
					  KEY game_content (game_id, content_type, content_id),
            				  KEY game_folder (game_id, folder_id),
				          KEY game_previous (game_id, previous_id)
						  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		@mysql_query($query);

		mysql_query("ALTER TABLE aug_bubble_media ENGINE = InnoDB;");
                mysql_query("ALTER TABLE aug_bubbles ENGINE = InnoDB;");
                mysql_query("ALTER TABLE editors ENGINE = InnoDB;");
                mysql_query("ALTER TABLE fountains ENGINE = InnoDB;");
                mysql_query("ALTER TABLE game_comments ENGINE = InnoDB;");
                mysql_query("ALTER TABLE game_editors ENGINE = InnoDB;");
                mysql_query("ALTER TABLE game_tab_data ENGINE = InnoDB;");
                mysql_query("ALTER TABLE game_tags ENGINE = InnoDB;");
                mysql_query("ALTER TABLE games ENGINE = InnoDB;");
                mysql_query("ALTER TABLE groups ENGINE = InnoDB;");
                mysql_query("ALTER TABLE media ENGINE = InnoDB;");
                mysql_query("ALTER TABLE note_content ENGINE = InnoDB;");
                mysql_query("ALTER TABLE note_likes ENGINE = InnoDB;");
                mysql_query("ALTER TABLE note_tags ENGINE = InnoDB;");
                mysql_query("ALTER TABLE notes ENGINE = InnoDB;");
                mysql_query("ALTER TABLE overlay_tiles ENGINE = InnoDB;");
                mysql_query("ALTER TABLE overlays ENGINE = InnoDB;");
                mysql_query("ALTER TABLE player_group ENGINE = InnoDB;");
                mysql_query("ALTER TABLE player_log ENGINE = InnoDB;");
                mysql_query("ALTER TABLE players ENGINE = InnoDB;");
                mysql_query("ALTER TABLE spawnables ENGINE = InnoDB;");
                mysql_query("ALTER TABLE web_hooks ENGINE = InnoDB;");
                mysql_query("ALTER TABLE web_pages ENGINE = InnoDB;");

		if (mysql_error()) return new returnData(6, NULL, 'cannot create folder contents table: ' . mysql_error());
	}

	public function deleteNewTables(){
		$query = "DROP TABLE folder_contents, folders, items, locations, nodes, npc_conversations, npcs, player_items, player_state_changes, qrcodes, quests, requirements";
		mysql_query($query);
	}

	/**
	 * Migrate a game to new table set
	 * @returns returnCode = 0 on success
	 */	
	public function migrateGame($intGameId)
	{
		$newItemIds = array();
		$query = "SELECT * FROM {$intGameId}_items";
		$result = mysql_query($query);
		while($result &&  $row = mysql_fetch_object($result)){
			$query = "INSERT INTO items (name, description, is_attribute, icon_media_id, media_id, dropable, destroyable, max_qty_in_inventory, creator_player_id, origin_latitude, origin_longitude, origin_timestamp, weight, url, type) SELECT name, description, is_attribute, icon_media_id, media_id, dropable, destroyable, max_qty_in_inventory, creator_player_id, origin_latitude, origin_longitude, origin_timestamp, weight, url, type FROM {$intGameId}_items WHERE item_id = {$row->item_id}";
			mysql_query($query);
			$newItemIds[$row->item_id] = mysql_insert_id();
			$query = "UPDATE items SET game_id = '{$intGameId}' WHERE item_id = '{$newItemIds[$row->item_id]}'";
			mysql_query($query);
		}

		$newNpcIds = array();
		$query = "SELECT * FROM {$intGameId}_npcs";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)){
			$query = "INSERT INTO npcs (name, description, text, closing, media_id, icon_media_id) SELECT name, description, text, closing, media_id, icon_media_id FROM {$intGameId}_npcs WHERE npc_id = {$row->npc_id}";
			mysql_query($query);
			$newNpcIds[$row->npc_id] = mysql_insert_id();
			$query = "UPDATE npcs SET game_id = '{$intGameId}' WHERE npc_id = '{$newNpcIds[$row->npc_id]}'";
			mysql_query($query);
		}

		$newNodeIds = array();
		$query = "SELECT * FROM {$intGameId}_nodes";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)){
			$query = "INSERT INTO nodes (title, text, opt1_text, opt1_node_id, opt2_text, opt2_node_id, opt3_text, opt3_node_id, require_answer_incorrect_node_id, require_answer_string, require_answer_correct_node_id, media_id, icon_media_id) SELECT title, text, opt1_text, opt1_node_id, opt2_text, opt2_node_id, opt3_text, opt3_node_id, require_answer_incorrect_node_id, require_answer_string, require_answer_correct_node_id, media_id, icon_media_id FROM {$intGameId}_nodes WHERE node_id = {$row->node_id}";
			mysql_query($query);
			$newNodeIds[$row->node_id] = mysql_insert_id();
			$query = "UPDATE nodes SET game_id = '{$intGameId}' WHERE node_id = '{$newNodeIds[$row->node_id]}'";
			mysql_query($query);
		}

		$newFolderContentIds = array();
		$query = "SELECT * FROM {$intGameId}_folder_contents";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)){
			$query = "INSERT INTO folder_contents (folder_id, content_type, content_id, previous_id) SELECT folder_id, content_type, content_id, previous_id FROM {$intGameId}_folder_contents WHERE object_content_id = {$row->object_content_id}";
			mysql_query($query);
			$newFolderContentIds[$row->object_content_id] = mysql_insert_id();
			$query = "UPDATE folder_contents SET game_id = '{$intGameId}' WHERE object_content_id = '{$newFolderContentIds[$row->object_content_id]}'";
			mysql_query($query);
		}

		$newFolderIds = array();
		$query = "SELECT * FROM {$intGameId}_folders";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)){
			$query = "INSERT INTO folders (name, parent_id, previous_id, is_open) SELECT name, parent_id, previous_id, is_open FROM {$intGameId}_folders WHERE folder_id = {$row->folder_id}";
			mysql_query($query);
			$newFolderIds[$row->folder_id] = mysql_insert_id();
			$query = "UPDATE folders SET game_id = '{$intGameId}' WHERE folder_id = '{$newFolderIds[$row->folder_id]}'";
			mysql_query($query);
		}

		$query = "SELECT * FROM folder_contents WHERE game_id = {$intGameId}";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)){
			if($row->folder_id != 0)
			{
				$query = "UPDATE folder_contents SET folder_id = {$newFolderIds[$row->folder_id]} WHERE game_id = '{$intGameId}' AND object_content_id = {$row->object_content_id}";
				mysql_query($query);
			} 
			if($row->content_type == "Node"){
				$query = "UPDATE folder_contents SET content_id = {$newNodeIds[$row->content_id]} WHERE game_id = '{$intGameId}' AND object_content_id = {$row->object_content_id}";
				mysql_query($query);
			}
			else if($row->content_type == "Item"){
				$query = "UPDATE folder_contents SET content_id = {$newItemIds[$row->content_id]} WHERE game_id = '{$intGameId}' AND object_content_id = {$row->object_content_id}";
				mysql_query($query);
			}
			else if($row->content_type == "Npc"){
				$query = "UPDATE folder_contents SET content_id = {$newNpcIds[$row->content_id]} WHERE game_id = '{$intGameId}' AND object_content_id = {$row->object_content_id}";
				mysql_query($query);
			}
		}

		$query = "SELECT * FROM folders WHERE game_id = {$intGameId}";
		$result = mysql_query($query);
		while($row = mysql_fetch_object($result)) {
			if($row->parent_id != 0 && is_numeric($newFolderIds[$row->parent_id])) {
				$query = "UPDATE folders SET parent_id = {$newFolderIds[$row->parent_id]}  WHERE game_id = '{$intGameId}' AND folder_id = {$row->folder_id}";
				mysql_query($query);
			}
		}

		$newLocationIds = array();
		$query = "SELECT * FROM {$intGameId}_locations";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)){
			$query = "INSERT INTO locations (name, description, latitude, longitude, error, type, type_id, icon_media_id, item_qty, hidden, force_view, allow_quick_travel, wiggle, show_title) SELECT name, description, latitude, longitude, error, type, type_id, icon_media_id, item_qty, hidden, force_view, allow_quick_travel, wiggle, show_title FROM {$intGameId}_locations WHERE location_id = {$row-> location_id}";
			mysql_query($query);
			$newLocationIds[$row->location_id] = mysql_insert_id();
			$query = "UPDATE locations SET game_id = '{$intGameId}' WHERE location_id = '{$newLocationIds[$row->location_id]}'";
			mysql_query($query);
			if($row->type == "Node"){
				$query = "UPDATE locations SET type_id = {$newNodeIds[$row->type_id]} WHERE game_id = '{$intGameId}' AND location_id = {$newLocationIds[$row->location_id]}";
				mysql_query($query);
			}
			else if($row->type == "Item"){
				$query = "UPDATE locations SET type_id = {$newItemIds[$row->type_id]} WHERE game_id = '{$intGameId}' AND location_id = {$newLocationIds[$row->location_id]}";
				mysql_query($query);
			}
			else if($row->type == "Npc"){
				$query = "UPDATE locations SET type_id = {$newNpcIds[$row->type_id]} WHERE game_id = '{$intGameId}' AND location_id = {$newLocationIds[$row->location_id]}";
				mysql_query($query);
			}
		}

		$newNpcConversationIds = array();
		$query = "SELECT * FROM {$intGameId}_npc_conversations";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)){
			$query = "INSERT INTO npc_conversations (npc_id, node_id, text, sort_index) SELECT npc_id, node_id, text, sort_index FROM {$intGameId}_npc_conversations WHERE conversation_id = {$row->conversation_id}";
			mysql_query($query);
			$newNpcConversationIds[$row->conversation_id] = mysql_insert_id();
			$query = "UPDATE npc_conversations SET game_id = '{$intGameId}' WHERE conversation_id = {$newNpcConversationIds[$row->conversation_id]}";
			mysql_query($query);
			$query = "UPDATE npc_conversations SET node_id = {$newNodeIds[$row->node_id]} WHERE game_id = '{$intGameId}' AND conversation_id = {$newNpcConversationIds[$row->conversation_id]}";
			mysql_query($query);
			$query = "UPDATE npc_conversations SET npc_id = {$newNpcIds[$row->npc_id]} WHERE game_id = '{$intGameId}' AND conversation_id = {$newNpcConversationIds[$row->conversation_id]}";
			mysql_query($query);

		} 

		$newPlayerItemIds = array();
		$query = "SELECT * FROM {$intGameId}_player_items";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)){
			$query = "INSERT INTO player_items (player_id, item_id, qty, timestamp) SELECT player_id, item_id, qty, timestamp FROM {$intGameId}_player_items WHERE id = {$row->id}";
			mysql_query($query);
			$newPlayerItemIds[$row->id] = mysql_insert_id();
			$query = "UPDATE player_items SET game_id = '{$intGameId}' WHERE id = {$newPlayerItemIds[$row->id]}";
			mysql_query($query);
			$query = "UPDATE player_items SET item_id = {$newItemIds[$row->item_id]} WHERE game_id = '{$intGameId}' AND id = {$newPlayerItemIds[$row->id]}";
			mysql_query($query);
		}

		$newPlayerStateChangesIds = array();
		$query = "SELECT * FROM {$intGameId}_player_state_changes";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)){
			$query = "INSERT INTO player_state_changes (event_type, event_detail, action, action_detail, action_amount) SELECT event_type, event_detail, action, action_detail, action_amount FROM {$intGameId}_player_state_changes WHERE id = {$row->id}";
			mysql_query($query);
			$newPlayerStateChangesIds[$row->id] = mysql_insert_id();
			$query = "UPDATE player_state_changes SET game_id = '{$intGameId}' WHERE id = {$newPlayerStateChangesIds[$row->id]}";
			mysql_query($query);
			$query = "UPDATE player_state_changes SET action_detail = {$newItemIds[$row->action_detail]} WHERE game_id = '{$intGameId}' AND id = {$newPlayerStateChangesIds[$row->id]}";
			mysql_query($query);
			if($row->event_type == "VIEW_ITEM"){
				$query = "UPDATE player_state_changes SET event_detail = {$newItemIds[$row->event_detail]} WHERE game_id = '{$intGameId}' AND id = {$newPlayerStateChangesIds[$row->id]}";
				mysql_query($query);
			}
			else if($row->event_type == "VIEW_NODE"){
				$query = "UPDATE player_state_changes SET event_detail = {$newNodeIds[$row->event_detail]} WHERE game_id = '{$intGameId}' AND id = {$newPlayerStateChangesIds[$row->id]}";
				mysql_query($query);
			}
			else if($row->event_type == "VIEW_NPC"){
				$query = "UPDATE player_state_changes SET event_detail = {$newNpcIds[$row->event_detail]} WHERE game_id = '{$intGameId}' AND id = {$newPlayerStateChangesIds[$row->id]}";
				mysql_query($query);
			}
		}

		$newQrcodeIds = array();
		$query = "SELECT * FROM {$intGameId}_qrcodes";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)){
			$query = "INSERT INTO qrcodes (link_type, link_id, code, match_media_id) SELECT link_type, link_id, code, match_media_id FROM {$intGameId}_qrcodes WHERE qrcode_id = {$row->qrcode_id}";
			mysql_query($query);
			$newQrcodeIds[$row->qrcode_id] = mysql_insert_id();
			$query = "UPDATE qrcodes SET game_id = '{$intGameId}' WHERE qrcode_id = {$newQrcodeIds[$row->qrcode_id]}";
			mysql_query($query);
			$query = "UPDATE qrcodes SET link_id = {$newLocationIds[$row->link_id]} WHERE game_id = '{$intGameId}' AND qrcode_id = {$newQrcodeIds[$row->qrcode_id]}";
			mysql_query($query);
		}

		$newQuestIds = array();
		$query = "SELECT * FROM {$intGameId}_quests";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)){
			$query = "INSERT INTO quests (name, description, text_when_complete, icon_media_id, sort_index) SELECT name, description, text_when_complete, icon_media_id, sort_index FROM {$intGameId}_quests WHERE quest_id = {$row->quest_id}";
			mysql_query($query);
			$newQuestIds[$row->quest_id] = mysql_insert_id();
			$query = "UPDATE quests SET game_id = '{$intGameId}' WHERE quest_id = {$newQuestIds[$row->quest_id]}";
			mysql_query($query);
		}

		$newRequirementIds = array();
		$query = "SELECT * FROM {$intGameId}_requirements";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)){
			$query = "INSERT INTO requirements (content_type, content_id, requirement, not_operator, boolean_operator, requirement_detail_1, requirement_detail_2, requirement_detail_3, requirement_detail_4) SELECT content_type, content_id, requirement, not_operator, boolean_operator, requirement_detail_1, requirement_detail_2, requirement_detail_3, requirement_detail_4 FROM {$intGameId}_requirements WHERE requirement_id = {$row->requirement_id}";
			mysql_query($query);
			$newRequirementIds[$row->requirement_id] = mysql_insert_id();
			$query = "UPDATE requirements SET game_id = '{$intGameId}' WHERE requirement_id = {$newRequirementIds[$row->requirement_id]}";
			mysql_query($query);
			if($row->content_type == "Node"){
				$query = "UPDATE requirements SET content_id = {$newNodeIds[$row->content_id]} WHERE game_id = '{$intGameId}' AND requirement_id = {$newRequirementIds[$row->requirement_id]}";
				mysql_query($query);
			}
			else if($row->content_type == "QuestDisplay" || $row->content_type == "QuestComplete"){
				$query = "UPDATE requirements SET content_id = {$newQuestIds[$row->content_id]} WHERE game_id = '{$intGameId}' AND requirement_id = {$newRequirementIds[$row->requirement_id]}";
				mysql_query($query);
			}
			else if($row->content_type == "Location"){
				$query = "UPDATE requirements SET content_id = {$newLocationIds[$row->content_id]} WHERE game_id = '{$intGameId}' AND requirement_id = {$newRequirementIds[$row->requirement_id]}";
				mysql_query($query);
			}

			if($row->requirement == "PLAYER_HAS_ITEM" || $row->requirement == "PLAYER_VIEWED_ITEM"){
				$query = "UPDATE requirements SET requirement_detail_1 = {$newItemIds[$row->requirement_detail_1]} WHERE game_id = '{$intGameId}' AND requirement_id = {$newRequirementIds[$row->requirement_id]}";
				mysql_query($query);
			}
			else if($row->requirement == "PLAYER_VIEWED_NODE"){
				$query = "UPDATE requirements SET requirement_detail_1 = {$newNodeIds[$row->requirement_detail_1]} WHERE game_id = '{$intGameId}' AND requirement_id = {$newRequirementIds[$row->requirement_id]}";
				mysql_query($query);
			}
			else if($row->requirement == "PLAYER_VIEWED_NPC"){
				$query = "UPDATE requirements SET requirement_detail_1 = {$newNpcIds[$row->requirement_detail_1]} WHERE game_id = '{$intGameId}' AND requirement_id = {$newRequirementIds[$row->requirement_id]}";
				mysql_query($query);
			}
			else if($row->requirement == "PLAYER_HAS_COMPLETED_QUEST"){
				$query = "UPDATE requirements SET requirement_detail_1 = {$newQuestIds[$row->requirement_detail_1]} WHERE game_id = '{$intGameId}' AND requirement_id = {$newRequirementIds[$row->requirement_id]}";
				mysql_query($query);
			}
		}

		$newIdsArray = array($newFolderIds, $newFolderContentIds, $newItemIds, $newLocationIds, $newNodeIds, $newNpcConversationIds, $newNpcIds, $newPlayerItemIds, $newPlayerStateChangesIds, $newQrcodeIds, $newQuestIds, $newRequirementIds);
		return $newIdsArray;
	}

	public function oneTimeTableUpdate($newIdsArray, $intGameId, $onLaunchNodeId)
	{
		$newNodeIds = $newIdsArray[4];
		$newItemIds = $newIdsArray[2];
		$newNpcIds = $newIdsArray[6];
		$newLocationsIds = $newIdsArray[3];
		$newQuestIds = $newIdsArray[10];

		$query = "UPDATE games SET on_launch_node_id = {$newNodeIds[$onLaunchNodeId]} WHERE game_id = {$intGameId}";
		mysql_query($query);

		$query = "SELECT * FROM spawnables WHERE game_id = {$intGameId}";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)){
			if($row->type == "Node"){
				$query = "UPDATE spawnables SET type_id = {$newNodeIds[$row->type_id]} WHERE game_id = '{$intGameId}' AND spawnable_id = {$row->spawnable_id}";
				mysql_query($query);
			}
			else if($row->type == "Item"){
				$query = "UPDATE spawnables SET type_id = {$newItemIds[$row->type_id]} WHERE game_id = '{$intGameId}' AND spawnable_id = {$row->spawnable_id}";
				mysql_query($query);
			}
			else if($row->type == "Npc"){
				$query = "UPDATE spawnables SET type_id = {$newNpcIds[$row->type_id]} WHERE game_id = '{$intGameId}' AND spawnable_id = {$row->spawnable_id}";
				mysql_query($query);
			}
		}

		$query = "SELECT * FROM fountains WHERE game_id = {$intGameId}";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)){
			if($row->type == "Location"){
				$query = "UPDATE fountians SET location_id = {$newLocationIds[$row->location_id]} WHERE game_id = '{$intGameId}' AND fountain_id = {$row->fountain_id}";
				mysql_query($query);
			}
		}

		$query = "SELECT * FROM player_log WHERE game_id = {$intGameId}";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)){
			if($row->content_type == "VIEW_NODE"){
				$query = "UPDATE player_log SET event_detail_1 = {$newNodeIds[$row->event_detail_1]} WHERE game_id = '{$intGameId}' AND id = {$row->id}";
				mysql_query($query);
			}
			else if($row->event_type == "PICKUP_ITEM" || $row->event_type == "DROP_ITEM"|| $row->event_type == "DESTROY_ITEM" || $row->event_type == "VIEW_ITEM"){
				$query = "UPDATE player_log SET event_detail_1 = {$newItemIds[$row->event_detail_1]} WHERE game_id = '{$intGameId}' AND id = {$row->id}";
				mysql_query($query);
			}
			else if($row->event_type == "VIEW_NPC"){
				$query = "UPDATE player_log SET event_detail_1 = {$newNpcIds[$row->event_detail_1]} WHERE game_id = '{$intGameId}' AND id = {$row->id}";
				mysql_query($query);
			}
			else if($row->event_type == "VIEW_QUESTS" || $row->event_type == "COMPLETE_QUEST"){
				$query = "UPDATE player_log SET event_detail_1 = {$newQuestIds[$row->event_detail_1]} WHERE game_id = '{$intGameId}' AND id = {$row->id}";
				mysql_query($query);
			}
		}
	}

	public function checkXMLBeforeMigration()    
	{
		set_time_limit(30);
		$query = "SELECT * FROM games ORDER BY game_id";
		$rs = mysql_query($query);
		while ($rs &&  $game = mysql_fetch_object($rs)) 
		{
		$intGameId = $game->game_id;
		//NOTE: substr removes <?xml version="1.0" ? //> from the beginning of the text
		$query = "SELECT * FROM {$intGameId}_nodes";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)) {
			if($row->text){
				$inputString = $row->text;
				if((strspn($inputString,"<>") > 0) && ((substr_count($inputString, "<npc>") > 0) || (substr_count($inputString, "<pc>") > 0) || (substr_count($inputString, "<dialog>") > 0)) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0)){
                     		@$output = simplexml_load_string($inputString);
					if(!$output) Module::serverErrorLog("Problem with game {$intGameId} with node {$row->node_id}");
                                }
			}
		}

		$query = "SELECT * FROM {$prefix}_npcs";
		$result = mysql_query($query);
		while($result && $row = mysql_fetch_object($result)) {
			if($row->text){
				$inputString = $row->text;
				if((strspn($inputString,"<>") > 0) && ((substr_count($inputString, "<npc>") > 0) || (substr_count($inputString, "<pc>") > 0) || (substr_count($inputString, "<dialog>") > 0)) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0)){
                     		@$output = simplexml_load_string($inputString);
					if(!$output) Module::serverErrorLog("Problem with game {$intGameId} with npc {$row->npc_id}");
                              }
			}
			if($row->closing){
				$inputString = $row->closing;
				if((strspn($inputString,"<>") > 0) && ((substr_count($inputString, "<npc>") > 0) || (substr_count($inputString, "<pc>") > 0) || (substr_count($inputString, "<dialog>") > 0)) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0)){
                     		@$output = simplexml_load_string($inputString);
					if(!$output) Module::serverErrorLog("Problem with game {$intGameId} with npc {$row->npc_id}");
                          }
			}
		}    
             }
	}

	public function updateXMLAfterMigration($newIdsArray, $intGameId)    
	{
		//NOTE: substr removes <?xml version="1.0" ? //> from the beginning of the text
		$query = "SELECT * FROM nodes WHERE game_id = {$intGameId}";
		$result = mysql_query($query);
		while($row = mysql_fetch_object($result)) {
			if($row->text){
				$inputString = $row->text;
				if((strspn($inputString,"<>") > 0) && ((substr_count($inputString, "<npc>") > 0) || (substr_count($inputString, "<pc>") > 0) || (substr_count($inputString, "<dialog>") > 0)) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0)){
					$output = Games::replaceXMLIdsForMigration($inputString, $newIdsArray);
                                 	if(!$output) Module::serverErrorLog("Problem with game {$intGameId} with node {$row->node_id}");
					else{
					$output = substr($output,22);
					$updateQuery = "UPDATE nodes SET text = '".addslashes($output)."' WHERE node_id = {$row->node_id} AND game_id = {$intGameId}";
					mysql_query($updateQuery);
					}
				}
			}
		}

		$query = "SELECT * FROM npcs WHERE game_id = {$intGameId}";
		$result = mysql_query($query);
		while($row = mysql_fetch_object($result)) {
			if($row->text){
				$inputString = $row->text;
				if((strspn($inputString,"<>") > 0) && ((substr_count($inputString, "<npc>") > 0) || (substr_count($inputString, "<pc>") > 0) || (substr_count($inputString, "<dialog>") > 0)) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0)){ 
					$output = Games::replaceXMLIdsForMigration($inputString, $newIdsArray);
					if(!$output) Module::serverErrorLog("Problem with game {$intGameId} with npc {$row->npc_id}");
					else{
					$output = substr($output,22);
					$updateQuery = "UPDATE npcs SET text = '".addslashes($output)."' WHERE npc_id = {$row->npc_id} AND game_id = {$intGameId}";
					mysql_query($updateQuery);
					}
				}
			}
			if($row->closing){
				$inputString = $row->closing;
				if((strspn($inputString,"<>") > 0) && ((substr_count($inputString, "<npc>") > 0) || (substr_count($inputString, "<pc>") > 0) || (substr_count($inputString, "<dialog>") > 0)) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0)){
					$output = Games::replaceXMLIdsForMigration($inputString, $newIdsArray);
					if(!$output) Module::serverErrorLog("Problem with game {$intGameId} with npc {$row->npc_id}");
					else{
					$output = substr($output,22);
					$updateQuery = "UPDATE npcs SET closing = '".addslashes($output)."' WHERE npc_id = {$row->npc_id} AND game_id = {$intGameId}";
					mysql_query($updateQuery);
					}
				}
			}
		}    
	}

	static function replaceXMLIdsForMigration($inputString, $newIdsArray)
	{
		$kTagExitToPlaque = "exitToPlaque";
		$kTagExitToCharacter = "exitToCharacter";
		$kTagExitToItem = "exitToItem";
		$kTagPlaque = "plaque";
		$kTagItem = "item";
		$kTagId = "id";
		//& sign will break xml parser, so this is necessary
		$inputString = str_replace("&", "&#x26;", $inputString);

		@$xml = simplexml_load_string($inputString);
		if(!$xml) return false; 

		foreach($xml->attributes() as $attributeTitle => $attributeValue)
		{ 
			if(strcmp($attributeTitle, $kTagExitToPlaque) == 0){
				$xml[$attributeTitle] = Games::getNewId($attributeValue, $newIdsArray[4]);
			}
			else if(strcmp($attributeTitle, $kTagExitToCharacter) == 0){
				$xml[$attributeTitle] = Games::getNewId($attributeValue, $newIdsArray[6]);
			}
			else if(strcmp($attributeTitle, $kTagExitToItem) == 0){
				$xml[$attributeTitle] = Games::getNewId($attributeValue, $newIdsArray[2]);
			}
		}

		foreach($xml->children() as $child)
		{
			foreach($child->attributes() as $attributeTitle => $attributeValue)
			{ 
				if(strcmp($attributeTitle, $kTagExitToPlaque) == 0){
					$child[$attributeTitle] = Games::getNewId($attributeValue, $newIdsArray[4]);
				}
				else if(strcmp($attributeTitle, $kTagExitToCharacter) == 0){
					$child[$attributeTitle] = Games::getNewId($attributeValue, $newIdsArray[6]);
				}
				else if(strcmp($attributeTitle, $kTagExitToItem) == 0){
					$child[$attributeTitle] = Games::getNewId($attributeValue, $newIdsArray[2]);
				}
				else if(strcmp($child->getName(), $kTagPlaque) == 0 && strcmp($attributeTitle, $kTagId) == 0){
					$child[$attributeTitle] = Games::getNewId($attributeValue, $newIdsArray[4]);
				}
				else if(strcmp($child->getName(), $kTagItem) == 0 && strcmp($attributeTitle, $kTagId) == 0){
					$child[$attributeTitle] = Games::getNewId($attributeValue, $newIdsArray[2]);
				}
			}
		}
		$output = $xml->asXML();
		$output = str_replace("&#x2019;", "'", $output);
		$output = str_replace("&amp;", "&", $output);
		$output = str_replace("&#x2014;", "-", $output);
		$output = str_replace("&#x201C;", "\"", $output);
		$output = str_replace("&#x201D;", "\"", $output);
		$output = str_replace("&#xB0;", "°", $output);
		$output = str_replace("&#xAE;", "®", $output);
		$output = str_replace("&#x2122;", "™", $output);
		$output = str_replace("&#xA9;", "©", $output);
		return $output;
	}

	public function oldDeleteGame($intGameID)
	{
		$returnData = new returnData(0, NULL, NULL);

		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "game does not exist");

		//Delete the files
		$command = 'rm -rf '. Config::gamedataFSPath . "/{$prefix}";
		NetDebug::trace("deleteFiles command: $command");		

		exec($command, $output, $return);
		if ($return) return new returnData(4, NULL, "unable to delete game directory");

		//Delete the editor_games record
		$query = "DELETE FROM game_editors WHERE game_id IN (SELECT game_id FROM games WHERE prefix = '{$prefix}_')";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');	

		//Fetch the table names for this game
		$query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='" . Config::dbSchema . "' AND TABLE_NAME LIKE '{$prefix}\_%'";
		NetDebug::trace($query);
		$result = mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');	

		//Delete any media records
		$query = "DELETE FROM media WHERE game_id = '{$intGameID}'";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');	

		//Delete all tables for this game
		while ($table = mysql_fetch_array($result)) {
			$query = "DROP TABLE {$table['TABLE_NAME']}";
			NetDebug::trace($query);
			mysql_query($query);
			if (mysql_error()) return new returnData(3, NULL, 'SQL Error');	
		}

		//Delete the game record
		$query = "DELETE FROM games WHERE prefix = '{$prefix}_'";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');	

		//Delete Web Pages
		$query = "DELETE FROM web_pages WHERE game_id = '{$intGameID}'";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');	

		//Delete Aug Bubbles
		$query = "DELETE FROM aug_bubbles WHERE game_id = '{$intGameID}'";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');	
		//And AugBubble Media
		$query = "DELETE FROM aug_bubble_media WHERE game_id = '{$intGameID}'";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');	

		//Delete Overlays
		$query = "DELETE FROM overlays WHERE game_id = '{$intGameID}'";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');	
		//And Overlay_tiles
		$query = "DELETE FROM overlay_tiles WHERE game_id = '{$intGameID}'";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');	

		//Delete WebHooks
		$query = "DELETE FROM web_hooks WHERE game_id = '{$intGameID}'";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');

		//Delete Tab Bar information
		$query = "DELETE FROM game_tab_data WHERE game_id = '{$intGameID}'";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');

		//Delete Note stuff
		$query = "DELETE FROM notes WHERE game_id = '{$intGameID}'";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');
		//Delete Note Media
		$query = "DELETE FROM note_content WHERE game_id = '{$intGameID}'";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');

		return new returnData(0);	
	}	

	/**
	 * Creates a game archive package
	 * @returns the path to the file
	 */		
	public function backupGame($intGameID)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$tmpDir = "{$prefix}_backup_" . date('Y_m_d');

		//Delete a previous backup with the same name
		$rmCommand = "rm -rf ". Config::gamedataFSPath . "/backups/{$tmpDir}";
		exec($rmCommand, $output, $return);
		if ($return) return new returnData(5, NULL, "cannot remove existing backup, check file permissions");

		//Set up a tmp directory
		$mkdirCommand = "mkdir ". Config::gamedataFSPath . "/backups/{$tmpDir}";
		exec($mkdirCommand, $output, $return);
		if ($return) return new returnData(5, NULL, "cannot create backup dir, check file permissions");

		//Create SQL File
		$sqlFile = 'database.sql';

		$getTablesCommand = Config::mysqlBinPath . "/mysql --user=" . Config::dbUser . " --password=". Config::dbPass ." -B --skip-column-names INFORMATION_SCHEMA -e \"SELECT TABLE_NAME FROM TABLES WHERE TABLE_SCHEMA='" .  Config::dbSchema . "' AND TABLE_NAME LIKE '{$prefix}\_%'\"";

		exec($getTablesCommand, $output, $return);

		if ($output == 127) return new returnData(6, NULL, "cannot get tables, check mysql bin path in config");

		$tables = '';
		foreach ($output as $table) {
			$tables .= $table;
			$tables .= ' ';
		}


		$createSQLCommand = Config::mysqlBinPath ."/mysqldump -u " . Config::dbUser . " --password=" . Config::dbPass . " " . Config::dbSchema . " $tables > ". Config::gamedataFSPath . "/Backups/{$tmpDir}/{$sqlFile}";
		//echo "<p>Running: $createSQLCommand </p>";
		exec($createSQLCommand, $output, $return);
		if ($return) return new returnData(6, NULL, "cannot create SQL, check mysql bin path in config");


		$copyCommand = "cp -R ". Config::gamedataFSPath . "/{$prefix} ". Config::gamedataFSPath . "/backups/{$tmpDir}/{$prefix}";
		//echo "<p>Running: $copyCommand </p>";
		exec($copyCommand, $output, $return);
		if ($return) return new returnData(5, NULL, "cannot copy game dir to backup dir, check file permissions");


		//Zip up the whole directory
		$zipFile = "{$prefix}_backup_" . date('Y_m_d') . ".tar";
		$newWd = Config::gamedataFSPath . "/backups";
		chdir($newWd);
		$createZipCommand = "tar -cf ". Config::gamedataFSPath . "/backups/{$zipFile} {$tmpDir}/";
		exec($createZipCommand, $output, $return);
		if ($return) return new returnData(7, NULL, "cannot compress backup dir, check that tar command is avaialbe");

		//Delete the Temp
		$rmCommand = "rm -rf ". Config::gamedataFSPath . "/backups/{$tmpDir}";
		exec($rmCommand, $output, $return);
		if ($return) return new returnData(5, NULL, "cannot delete backup dir, check file permissions");

		return new returnData(0, Config::gamedataWWWPath . "/backups/{$zipFile}");		
	}	



	/**
	 * Restore a game from a file
	 * Not yet implemented
	 * @returns the game ID of the newly created game
	 */		
	public function restoreGame($file)
	{
		return new returnData(4, NULL, "restore Game Not Implemented on Server");

	}


	/**
	 * Retrieve all editors
	 *
	 * @returns a recordset of the editor records
	 */		
	public function getEditors()
	{
		$query = "SELECT * FROM editors";
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');
		return new returnData(0, $rsResult);
	}


	/**
	 * Retrieve editors of a specifc game
	 *
	 * @returns a recordset of the editor records
	 */	
	public function getGameEditors($intGameID)
	{
		$query = "SELECT game_editors.*, editors.* FROM game_editors LEFT JOIN editors ON game_editors.editor_id=editors.editor_id WHERE game_editors.game_id = {$intGameID}";
		$rsResult = mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');
		return new returnData(0, $rsResult);
	}


	/**
	 * Add an editor to a game
	 *
	 * @returns true or an error string
	 */	
	public function addEditorToGame($intEditorID, $intGameID)
	{
		$query = "INSERT INTO game_editors (editor_id, game_id) VALUES ('{$intEditorID}','{$intGameID}')";
		$rsResult = @mysql_query($query);

		if (mysql_errno() == 1062) return new returnData(4, NULL, 'duplicate');
		if (mysql_error()) return new returnData(3, NULL, 'sql error');

		$query = "SELECT email FROM editors WHERE editor_id = $intEditorID";
		$result = mysql_query($query);
		$emailObj = mysql_fetch_object($result);
		$email = $emailObj->email;

		$query = "SELECT name FROM games WHERE game_id = $intGameID";
		$result = mysql_query($query);
		$gameObj = mysql_fetch_object($result);
		$game = $gameObj->name;

		$body = "An owner of ARIS Game \"".$game."\" has promoted you to editor. Go to ".Config::WWWPath."/editor and log in to begin collaborating!";
		Module::sendEmail($email, "You are now an editor of ARIS Game \"$game\"", $body);

		return new returnData(0);	
	}	

	/**
	 * Remove an editor from a game
	 *
	 * @returns true or an error string
	 */		
	public function removeEditorFromGame($intEditorID, $intGameID)
	{
		$query = "DELETE FROM game_editors WHERE editor_id = '{$intEditorID}' AND game_id = '{$intGameID}'";
		$rsResult = mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');

		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);
	}


	/**
	 * Saves a user comment on a game from client
	 * @param integer $intPlayerId The player identifier
	 * @param integer $intGameId The game identifier
	 * @param integer $intRating 1-5
	 * @param String $comment The user's comment
	 * @return void
	 */

	public function saveComment($intPlayerId, $intGameId, $intRating, $comment) {
                if($comment == 'Comment') $comment = "";
		$query = "SELECT * FROM game_comments WHERE game_id = '{$intGameId}' AND player_id = '{$intPlayerId}'";
		$result = mysql_query($query);
		if(mysql_num_rows($result) > 0) $query = "UPDATE game_comments SET rating='{$intRating}', comment='{$comment}' WHERE game_id = '{$intGameId}' AND player_id = '{$intPlayerId}'";
		else $query = "INSERT INTO game_comments (game_id, player_id, rating, comment) VALUES ('{$intGameId}', '{$intPlayerId}', '{$intRating}', '{$comment}')";
		mysql_query($query);

                if (mysql_error()) return new returnData(3, NULL, 'SQL Error');

                $query = "SELECT editors.email FROM (SELECT * FROM game_editors WHERE game_id = ".$intGameId.") AS ge LEFT JOIN editors ON ge.editor_id = editors.editor_id";
                $result = mysql_query($query);
                if(mysql_num_rows($result) > 0)
                {
                    $gameName = mysql_fetch_object(mysql_query("SELECT name FROM games WHERE game_id = $intGameId"))->name;
                    $playerName = mysql_fetch_object(mysql_query("SELECT user_name FROM players WHERE player_id = $intPlayerId"))->user_name;
                    $sub = "New Rating for '".$gameName."'";
                    $body = "Congratulations! People are playing your ARIS game! \n".$playerName." Recently gave your game ".$intRating." stars out of 5" . (($comment && $comment != 'Comment') ? ", commenting \"".$comment."\"" : ".");
                }
                while($ob = mysql_fetch_object($result))
                    Module::sendEmail($ob->email,$sub,$body);

		return new returnData(0);
	}


	/**
	 * Gets a lightweight game list to populate map on client- ONLY RETURNS GAMES MARKED 'is_locational'
	 * @param float User's current latitude
	 * @param float User's current longitude
	 * @param 1:Include games in development 0:restrict list to polished games
	 * @returns gameId, rating, and lat/lon location.
	 */

	public function getGamesWithLocations($latitude, $longitude, $boolIncludeDevGames = 0) {
		$games = array();

		if($boolIncludeDevGames) $query = "SELECT game_id, name FROM games WHERE is_locational = 1";
		else $query = "SELECT game_id, name FROM games WHERE ready_for_public = 1 AND is_locational = 1";
		$idResult = mysql_query($query);


		while($gameId = mysql_fetch_assoc($idResult)){
			$game = new stdClass;
			$game->game_id = $gameId['game_id']; 
			$game->name = $gameId['name'];

			$query = "SELECT AVG(rating) AS rating FROM game_comments WHERE game_id = {$gameId['game_id']}";
			$ratingResult = mysql_query($query);


			$rating = mysql_fetch_assoc($ratingResult);
			if($rating['rating'] != NULL){
				$query = "SELECT rating FROM game_comments WHERE game_id = {$gameId['game_id']}";
				$result = mysql_query($query);
				$game->rating = $rating['rating'];
				$game->calculatedScore = (($rating['rating']-3) * mysql_num_rows($result));
			}
			else {
				$game->rating = 0;
				$game->calculatedScore = 0;
			}

			//Get locations
			$nearestLocation = Games::getNearestLocationOfGameToUser($latitude, $longitude, $gameId['game_id']);
			$game->latitude = $nearestLocation->latitude;
			$game->longitude = $nearestLocation->longitude;

			if($game->latitude != NULL){
				$games[] = $game;
			}
		}

		return new returnData(0, $games);
	}


	/**
	 * Gets a game's nearest location to user
	 * @param integer $latitude User's current latitude
	 * @param integer $longitude User's current longitud
	 * @param integer $gameId Game to find nearest location of
	 * @returns nearestLocation distance, latitude, and longitude
	 */

	protected function getNearestLocationOfGameToUser($latitude, $longitude, $gameId){
		$query = "SELECT latitude, longitude,((ACOS(SIN($latitude * PI() / 180) * SIN(latitude * PI() / 180) + 
			COS($latitude * PI() / 180) * COS(latitude * PI() / 180) * 
			COS(($longitude - longitude) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) * 1609.344
			AS `distance`
			FROM {$gameId}_locations
			WHERE type != 'Item' OR (item_qty > 0 OR item_qty = -1)
			ORDER BY distance ASC";

		if (!$nearestLocationRs = mysql_query($query)) {
			Module::serverErrorLog("Games: getNearestLocationOfGameToUser failed. Called for a game that doesn't seem to exist: {$gameId}");
			return null;
		}
		$nearestLocation = mysql_fetch_object($nearestLocationRs);
		return $nearestLocation;
	}


	/**
	 * Gets a set of games that contain the input string
	 * @param integer Player Id
	 * @param float Players Current Latitude
	 * @param float Players Current Longitude
	 * @param String Search String
	 * @param boolean Search all games or just the polished ones
	 * @returns array of gameId's who's corresponding games contain the search string
	 */

	public function getGamesContainingText($intPlayerId, $latitude, $longitude, $textToFind, $boolIncludeDevGames = 1, $page = 0){
		$textToFind = addSlashes($textToFind);
		$textToFind = urldecode($textToFind);
		if($boolIncludeDevGames) $query = "SELECT game_id, name FROM games WHERE (name LIKE '%{$textToFind}%' OR description LIKE '%{$textToFind}%') ORDER BY name ASC LIMIT ".($page*25).", 25";
		else $query = "SELECT game_id, name FROM games WHERE (name LIKE '%{$textToFind}%' OR description LIKE '%{$textToFind}%') AND ready_for_public = 1 ORDER BY name ASC LIMIT ".($page*25).", 25";

		$result = mysql_query($query);
		$games = array();
		while($game = mysql_fetch_object($result)){
			$gameObj = new stdClass;
			$gameObj = Games::getFullGameObject($game->game_id, $intPlayerId, 1, 9999999999, $latitude, $longitude);
			if($gameObj != NULL){
				$games[] = $gameObj;
			}
			else{
				$gameObj = Games::getFullGameObject($game->game_id, $intPlayerId, 0, 9999999999, $latitude, $longitude);
				if($gameObj != NULL){
					$games[] = $gameObj;
				}
			}
		}
		return new returnData(0, $games);
	}


	/**
	 * Gets a player's 10 most recently played games
	 * @param integer The player to look for
	 * @param float Players Current Latitude
	 * @param float Players Current Longitude
	 * @param boolean Search all games or just the polished ones
	 * @returns array of up to 10 gameId's that the player has most recently played
	 */

	public function getRecentGamesForPlayer($intPlayerId, $latitude, $longitude, $boolIncludeDevGames = 1){
		$query = "SELECT p_log.*, games.ready_for_public FROM (SELECT player_id, game_id, timestamp FROM player_log WHERE player_id = {$intPlayerId} AND game_id != 0 ORDER BY timestamp DESC) as p_log LEFT JOIN games ON p_log.game_id = games.game_id ".($boolIncludeDevGames ? "" : "WHERE games.ready_for_public = 1 ")."GROUP BY game_id ORDER BY timestamp DESC LIMIT 10"; 
		$result = mysql_query($query);

		$games = array();
		while($game = mysql_fetch_object($result))
		{
			$gameObj = Games::getFullGameObject($game->game_id, $intPlayerId, 1, 9999999999, $latitude, $longitude);
			if($gameObj != NULL) $games[] = $gameObj;
		}

		return new returnData(0, $games);
	}

	/**
	 * Fetch the top 20 most popular games
	 * @param integer The player identifier
	 * @param float The player's current latitude
	 * @param float The player's current longitude
	 * @param float 0, 1, or 2 specifying day, week, or month 
	 * @param bool Include locational or non-locational games  
	 * @param bool Include Games in Development 
	 * @return returnData
	 * @returns a returnData object containing an array of games
	 * @see returnData
	 */

	public function getPopularGames($playerId, $time, $includeGamesinDevelopment)
	{
		if ($time == 0) $queryInterval = '1 DAY';
		else if ($time == 1) $queryInterval = '7 DAY';
		else if ($time == 2) $queryInterval = '1 MONTH';

		if ($includeGamesinDevelopment) $query = "SELECT media.file_name as file_name, temp.game_id, temp.name, temp.description, temp.count FROM (SELECT games.game_id, games.name, games.description, games.icon_media_id, COUNT(DISTINCT player_id) AS count FROM games INNER JOIN player_log ON games.game_id = player_log.game_id WHERE player_log.timestamp BETWEEN DATE_SUB(NOW(), INTERVAL ".$queryInterval.") AND NOW() GROUP BY games.game_id HAVING count > 1) as temp LEFT JOIN media ON temp.icon_media_id = media.media_id GROUP BY game_id HAVING count > 1 ORDER BY count DESC LIMIT 20";

		else $query = "SELECT media.file_name as file_name, temp.game_id, temp.name, temp.description, temp.count FROM (SELECT games.game_id, games.name, games.description, games.icon_media_id, COUNT(DISTINCT player_id) AS count FROM games INNER JOIN player_log ON games.game_id = player_log.game_id WHERE ready_for_public = TRUE AND player_log.timestamp BETWEEN DATE_SUB(NOW(), INTERVAL ".$queryInterval.") AND NOW() GROUP BY games.game_id HAVING count > 1) as temp LEFT JOIN media ON temp.icon_media_id = media.media_id GROUP BY game_id HAVING count > 1 ORDER BY count DESC LIMIT 20";

		$gamesRs = @mysql_query($query);
		NetDebug::trace(mysql_error());

		$games = array();
		while ($game = @mysql_fetch_object($gamesRs)) {
			$gameObj = new stdClass;
			$gameObj = Games::getFullGameObject($game->game_id, $playerId, 0, 9999999999, 0, 0);
			if($gameObj != NULL){
				$gameObj->count = $game->count;
				$games[] = $gameObj;
			}
		}
		return new returnData(0, $games, NULL);

	}		

	/**
	 * Duplicate game in database
	 * @param The game Id to be duplicated
	 *
	 */
	public function duplicateGame($intGameID, $intEditorID = 0) {

		Module::serverErrorLog("Duplicating Game ID:".$intGameID);
		$prefix = Module::getPrefix($intGameID);

		$query = "SELECT * FROM games WHERE game_id = {$intGameID} LIMIT 1";
		$rs = @mysql_query($query);
		if (mysql_error())  return new returnData(3, NULL, 'SQL error');

		$game = @mysql_fetch_object($rs);
		if (!$game) return new returnData(2, NULL, "invalid game id");

		$compatibleName = false;
		$appendNo = 1;
		while(!$compatibleName)
		{
			$query = "SELECT * FROM games WHERE name = '".addslashes($game->name)."_copy".$appendNo."'";
			$result = mysql_query($query);
			if(mysql_fetch_object($result))
				$appendNo++;
			else
				$compatibleName = true;
		}
		$game->name = $game->name."_copy".$appendNo;

		$newGameID = new stdClass();
		$newGameID->data = 0;
		if($intEditorID != 0)
		{
			$newGameId = Games::createGame($intEditorID, $game->name, $game->description, 
					$game->pc_media_id, $game->icon_media_id, $game->media_id,
					$game->is_locational, $game->ready_for_public, 
					$game->allow_share_note_to_map, $game->allow_share_note_to_book, $game->allow_player_tags, $game->allow_player_comments, $game->allow_note_likes,
					$game->on_launch_node_id, $game->game_complete_node_id, $game->inventory_weight_cap, $game->allow_trading);
		}
		else
		{
			$query = "SELECT editor_id FROM game_editors WHERE game_id = {$intGameID}";
			$rs = mysql_query($query);
			$editors = mysql_fetch_object($rs);

			$newGameId = Games::createGame($editors->editor_id, $game->name, $game->description, 
					$game->pc_media_id, $game->icon_media_id, $game->media_id,
					$game->is_locational, $game->ready_for_public, 
					$game->allow_share_note_to_map, $game->allow_share_note_to_book, $game->allow_player_tags, $game->allow_player_comments, $game->allow_note_likes,
					$game->on_launch_node_id, $game->game_complete_node_id, $game->inventory_weight_cap, $game->allow_trading);

			while($editors = mysql_fetch_object($rs)){
				Games::addEditorToGame($editors->editor_id, $newGameId->data);
			}
		}

		$newPrefix = Module::getPrefix($newGameId->data);
		if(!$newPrefix || $newPrefix == 0) return new returnData(2, NULL, "Error Duplicating Game");

		$query = "INSERT INTO {$newPrefix}_folders (folder_id, name, parent_id, previous_id, is_open) SELECT folder_id, name, parent_id, previous_id, is_open FROM {$prefix}_folders";
		mysql_query($query);

		$query = "INSERT INTO {$newPrefix}_folder_contents (object_content_id, folder_id, content_type, content_id, previous_id) SELECT object_content_id, folder_id, content_type, content_id, previous_id FROM {$prefix}_folder_contents";
		mysql_query($query);

		$query = "INSERT INTO {$newPrefix}_items (item_id, name, description, is_attribute, icon_media_id, media_id, dropable, tradeable, destroyable, max_qty_in_inventory, creator_player_id, origin_latitude, origin_longitude, origin_timestamp, weight, url, type) SELECT item_id, name, description, is_attribute, icon_media_id, media_id, dropable, tradeable, destroyable, max_qty_in_inventory, creator_player_id, origin_latitude, origin_longitude, origin_timestamp, weight, url, type FROM {$prefix}_items";
		mysql_query($query);

		$query = "INSERT INTO {$newPrefix}_locations (location_id, name, description, latitude, longitude, error, type, type_id, icon_media_id, item_qty, hidden, force_view, allow_quick_travel) SELECT location_id, name, description, latitude, longitude, error, type, type_id, icon_media_id, item_qty, hidden, force_view, allow_quick_travel FROM {$prefix}_locations";
		mysql_query($query);

		$query = "INSERT INTO {$newPrefix}_nodes (node_id, title, text, opt1_text, opt1_node_id, opt2_text, opt2_node_id, opt3_text, opt3_node_id, require_answer_incorrect_node_id, require_answer_string, require_answer_correct_node_id, media_id, icon_media_id) SELECT node_id, title, text, opt1_text, opt1_node_id, opt2_text, opt2_node_id, opt3_text, opt3_node_id, require_answer_incorrect_node_id, require_answer_string, require_answer_correct_node_id, media_id, icon_media_id FROM {$prefix}_nodes";
		mysql_query($query);

		$query = "INSERT INTO {$newPrefix}_npcs (npc_id, name, description, text, closing, media_id, icon_media_id) SELECT npc_id, name, description, text, closing, media_id, icon_media_id FROM {$prefix}_npcs";
		mysql_query($query);

		$query = "INSERT INTO {$newPrefix}_npc_conversations (conversation_id, npc_id, node_id, text) SELECT conversation_id, npc_id, node_id, text FROM {$prefix}_npc_conversations";
		mysql_query($query);

		$query = "INSERT INTO {$newPrefix}_player_state_changes (id, event_type, event_detail, action, action_detail, action_amount) SELECT id, event_type, event_detail, action, action_detail, action_amount FROM {$prefix}_player_state_changes";
		mysql_query($query);

		$query = "INSERT INTO {$newPrefix}_qrcodes (qrcode_id, link_type, link_id, code, match_media_id) SELECT qrcode_id, link_type, link_id, code, match_media_id FROM {$prefix}_qrcodes";
		mysql_query($query);

		$query = "INSERT INTO {$newPrefix}_quests (quest_id, name, description, text_when_complete, icon_media_id) SELECT quest_id, name, description, text_when_complete, icon_media_id FROM {$prefix}_quests";
		mysql_query($query);

		$query = "INSERT INTO {$newPrefix}_requirements (requirement_id, content_type, content_id, requirement, not_operator, boolean_operator, requirement_detail_1, requirement_detail_2, requirement_detail_3, requirement_detail_4) SELECT requirement_id, content_type, content_id, requirement, not_operator, boolean_operator, requirement_detail_1, requirement_detail_2, requirement_detail_3, requirement_detail_4 FROM {$prefix}_requirements";
		mysql_query($query);

		//Remove the tabs created by createGame
		$query = "DELETE FROM game_tab_data WHERE game_id = {$newPrefix}";
		$result = mysql_query($query);

		$query = "SELECT * FROM game_tab_data WHERE game_id = {$prefix}";
		$result = mysql_query($query);
		while($row = mysql_fetch_object($result)){
			$query = "INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ('{$newPrefix}', '{$row->tab}', '{$row->tab_index}')";
			mysql_query($query);
		}

		$query = "SELECT * FROM aug_bubble_media WHERE game_id = {$prefix}";
		$result = mysql_query($query);
		while($row = mysql_fetch_object($result)){
			$query = "INSERT INTO aug_bubble_media (game_id, aug_bubble_id, media_id, text, index) VALUES ('{$newPrefix}', '{$row->aug_bubble_id}', '{$row->media_id}', '{$row->text}', '{$row->index}')";
			mysql_query($query);
		}

		$originalAugBubbleId = array();
		$newAugBubbleId = array();
		$query = "SELECT * FROM aug_bubbles WHERE game_id = {$prefix}";
		$result = mysql_query($query);
		while($row = mysql_fetch_object($result)){
			array_push($originalAugBubbleId, $row->aug_bubble_id);

			$query = "INSERT INTO aug_bubbles (game_id, name, description, icon_media_id) VALUES ('{$newPrefix}', '{$row->name}', '{$row->description}', '{$row->icon_media_id}')";
			mysql_query($query);
			$newID = mysql_insert_id();
			array_push($newAugBubbleId, $newID);

			$query = "UPDATE aug_bubble_media SET aug_bubble_id = {$newID} WHERE aug_bubble_id = {$row->aug_bubble_id}";
			mysql_query($query);
			$query = "UPDATE {$newPrefix}_locations SET type_id = {$newID} WHERE type = 'AugBubble' AND type_id = {$row->aug_bubble_id}";
			mysql_query($query);
			$query = "UPDATE {$newPrefix}_folder_contents SET content_id = {$newID} WHERE content_type = 'AugBubble' AND content_id = {$row->aug_bubble_id}";
			mysql_query($query);
			$query = "UPDATE {$newPrefix}_requirements SET requirement_detail_1 = {$newID} WHERE (requirement = 'PLAYER_HAS_NOT_VIEWED_AUGBUBBLE' OR requirement = 'PLAYER_VIEWED_AUGBUBBLE') AND requirement_detail_1 = {$row->aug_bubble_id}";
			mysql_query($query);
		}

		$originalWebPageId = array();
		$newWebPageId = array();
		$query = "SELECT * FROM web_pages WHERE game_id = {$prefix}";
		$result = mysql_query($query);
		while($row = mysql_fetch_object($result)){
			array_push($originalWebPageId, $row->web_page_id);

			$query = "INSERT INTO web_pages (game_id, name, url, icon_media_id) VALUES ('{$newPrefix}', '{$row->name}', '{$row->url}', '{$row->icon_media_id}')";
			mysql_query($query);
			$newID = mysql_insert_id();
			array_push($newWebPageId, $newID);

			$query = "UPDATE {$newPrefix}_locations SET type_id = {$newID} WHERE type = 'WebPage' AND type_id = {$row->web_page_id}";
			mysql_query($query);
			$query = "UPDATE {$newPrefix}_folder_contents SET content_id = {$newID} WHERE content_type = 'WebPage' AND content_id = {$row->web_page_id}";
			mysql_query($query);
			$query = "UPDATE {$newPrefix}_requirements SET requirement_detail_1 = {$newID} WHERE (requirement = 'PLAYER_HAS_NOT_VIEWED_WEBPAGE' OR requirement = 'PLAYER_VIEWED_WEBPAGE') AND requirement_detail_1 = {$row->web_page_id}";
			mysql_query($query);
		}

		$query = "SELECT * FROM web_hooks WHERE game_id = {$prefix}";
		$result = mysql_query($query);
		while($row = mysql_fetch_object($result)){
			$query = "INSERT INTO web_hooks (game_id, name, url, incoming) VALUES ('{$newPrefix}', '{$row->name}', '".addSlashes($row->url)."', '{$row->incoming}')";
			mysql_query($query);
			$newID = mysql_insert_id();

			$query = "UPDATE {$newPrefix}_requirements SET content_id = {$newID} WHERE content_type = 'OutgoingWebHook' AND content_id = {$row->web_hook_id}";
			mysql_query($query);
		}

        $originalOverlayId = array();
		$newOverlayId = array();
		$query = "SELECT * FROM overlays WHERE game_id = {$prefix}";
		$result = mysql_query($query);
		while($row = mysql_fetch_object($result)){
			array_push($originalOverlayId, $row->overlay_id);
            $origOverlayId = $row->overlay_id;
            
			$query = "INSERT INTO overlays (game_id, game_overlay_id, name, sort_index, file_uploaded) VALUES ('{$newPrefix}', '{$row->game_overlay_id}', '{$row->name}', '{$row->sort_index}', '{$row->file_uploaded}')";
			mysql_query($query);
			$newID = mysql_insert_id();
			array_push($newOverlayId, $newID);
            
            $query2 = "SELECT * FROM overlay_tiles WHERE overlay_id = {$origOverlayId}";
            $result2 = mysql_query($query2);
            while($row2 = mysql_fetch_object($result2)){
                $query3 = "INSERT INTO overlay_tiles (overlay_id, media_id, zoom, x, y) VALUES ('{$newID}', '{$row2->media_id}', '{$row2->zoom}', '{$row2->x}', '{$row2->y}')";
                mysql_query($query3);
            }
            
            
			$query = "UPDATE {$newPrefix}_requirements SET content_id = {$newID} WHERE content_type = 'CustomMap' AND content_id = {$row->overlay_id}";
			mysql_query($query);
            
		}
        
		$originalMediaId = array();
		$newMediaId = array();
		$query = "SELECT * FROM media WHERE game_id = {$prefix}";
		$result = mysql_query($query);
		while($row = mysql_fetch_object($result)){
			array_push($originalMediaId, $row->media_id);

			$query = "INSERT INTO media (game_id, name, file_name, is_icon) VALUES ('{$newPrefix}', '{$row->name}', '{$row->file_name}', '{$row->is_icon}')";
			mysql_query($query);
			$newID = mysql_insert_id();
			array_push($newMediaId, $newID);

			if($row->file_name != "") copy(("../../gamedata/" . $prefix . "/" . $row->file_name),("../../gamedata/" . $newPrefix . "/" . $row->file_name));

			$query = "UPDATE {$newPrefix}_items SET icon_media_id = {$newID} WHERE icon_media_id = $row->media_id";
			mysql_query($query);
			$query = "UPDATE {$newPrefix}_items SET media_id = {$newID} WHERE media_id = $row->media_id";
			mysql_query($query);
			$query = "UPDATE {$newPrefix}_locations SET icon_media_id = {$newID} WHERE icon_media_id = $row->media_id";
			mysql_query($query);
			$query = "UPDATE {$newPrefix}_nodes SET icon_media_id = {$newID} WHERE icon_media_id = $row->media_id";
			mysql_query($query);
			$query = "UPDATE {$newPrefix}_nodes SET media_id = {$newID} WHERE media_id = $row->media_id";
			mysql_query($query);
			$query = "UPDATE {$newPrefix}_npcs SET icon_media_id = {$newID} WHERE icon_media_id = $row->media_id";
			mysql_query($query);
			$query = "UPDATE {$newPrefix}_npcs SET media_id = {$newID} WHERE media_id = $row->media_id";
			mysql_query($query);
			$query = "UPDATE {$newPrefix}_qrcodes SET match_media_id = {$newID} WHERE match_media_id = $row->media_id";
			mysql_query($query);
			$query = "UPDATE {$newPrefix}_quests SET icon_media_id = {$newID} WHERE icon_media_id = $row->media_id";
			mysql_query($query);
			$query = "UPDATE aug_bubbles SET icon_media_id = {$newID} WHERE icon_media_id = $row->media_id AND game_id = {$newPrefix}";
			mysql_query($query);
			$query = "UPDATE aug_bubble_media SET media_id = {$newID} WHERE media_id = $row->media_id AND game_id = {$newPrefix}";
			mysql_query($query);
			$query = "UPDATE games SET icon_media_id = {$newID} WHERE icon_media_id = $row->media_id AND game_id = {$newPrefix}";
			mysql_query($query);
			$query = "UPDATE games SET media_id = {$newID} WHERE media_id = $row->media_id AND game_id = {$newPrefix}";
			mysql_query($query);
			$query = "UPDATE games SET pc_media_id = {$newID} WHERE pc_media_id = $row->media_id AND game_id = {$newPrefix}";
			mysql_query($query);
			$query = "UPDATE web_pages SET icon_media_id = {$newID} WHERE icon_media_id = $row->media_id AND game_id = {$newPrefix}";
			mysql_query($query);
            $query = "UPDATE overlay_tiles, overlays SET overlay_tiles.media_id = {$newID} WHERE overlay_tiles.media_id = $row->media_id AND overlays.game_id = {$newPrefix} AND overlay_tiles.overlay_id = overlays.overlay_id";
			mysql_query($query);

		}


        


		//NOTE: substr removes <?xml version="1.0" ? //> from the beginning of the text
		$query = "SELECT * FROM {$newPrefix}_nodes";
		$result = mysql_query($query);
		while($row = mysql_fetch_object($result)) {
			if($row->text){
				$inputString = $row->text;
				if((strspn($inputString,"<>") > 0) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0)){
					$output = Games::replaceXMLIds($inputString, $originalAugBubbleId, $newAugBubbleId, $originalWebPageId, $newWebPageId, $originalMediaId, $newMediaId);
					if($output === false) return new returnData(1, NULL, "Problem reading the text of Node {$row->node_id}\nwith title:\n{$row->title}\nand text:\n{$row->text}\nPlease make sure all your xml tags have been properly opened and closed.");
					$output = substr($output,22);
					$updateQuery = "UPDATE {$newPrefix}_nodes SET text = '".addslashes($output)."' WHERE node_id = {$row->node_id}";
					mysql_query($updateQuery);
				}
			}
		}

		$query = "SELECT * FROM {$newPrefix}_npcs";
		$result = mysql_query($query);
		while($row = mysql_fetch_object($result)) {
			if($row->text)
			{
				$inputString = $row->text;
				if((strspn($inputString,"<>") > 0) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0))
				{
					$output = Games::replaceXMLIds($inputString, $originalAugBubbleId, $newAugBubbleId, $originalWebPageId, $newWebPageId, $originalMediaId, $newMediaId);
					if($output === false) return new returnData(1, NULL, "Problem reading the text of NPC {$row->npc_id}\nwith name:\n{$row->name}\nand text:\n{$row->text}\nPlease make sure all your xml tags have been properly opened and closed.");
					$output = substr($output,22);
					$updateQuery = "UPDATE {$newPrefix}_npcs SET text = '".addslashes($output)."' WHERE npc_id = {$row->npc_id}";
					mysql_query($updateQuery);
				}
			}
			if($row->closing){
				$inputString = $row->closing;
				if((strspn($inputString,"<>") > 0) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0))
				{
					$output = Games::replaceXMLIds($inputString, $originalAugBubbleId, $newAugBubbleId, $originalWebPageId, $newWebPageId, $originalMediaId, $newMediaId);
					if($output === false) return new returnData(1, NULL, "Problem reading the text of NPC {$row->npc_id}\nwith name:\n{$row->name}\nand closing:\n{$row->closing}\nPlease make sure all your xml tags have been properly opened and closed.");
					$output = substr($output,22);
					$updateQuery = "UPDATE {$newPrefix}_npcs SET closing = '".addslashes($output)."' WHERE npc_id = {$row->npc_id}";
					mysql_query($updateQuery);
				}
			}
		}
		return new returnData(0, $newPrefix, NULL);
	}

	static function replaceXMLIds($inputString, $originalAugBubbleId, $newAugBubbleId, $originalWebPageId, $newWebPageId, $originalMediaId, $newMediaId)
	{
		//      $kTagExitToPlaque = "exitToPlaque";
		$kTagExitToWebPage = "exitToWebPage";
		//      $kTagExitToCharacter = "exitToCharacter";
		$kTagExitToPanoramic = "exitToPanoramic";
		//      $kTagExitToItem = "exitToItem";
		$kTagVideo = "video";
		$kTagId = "id";
		$kTagPanoramic = "panoramic";
		$kTagWebpage = "webpage";
		//      $kTagPlaque = "plaque";
		//      $kTagItem = "item";
		$kTagMedia = "mediaId";

		//& sign will break xml parser, so this is necessary
		$inputString = str_replace("&", "&#x26;", $inputString);

		@$xml = simplexml_load_string($inputString);
		if($xml === false){
			return false;
		}

		foreach($xml->attributes() as $attributeTitle => $attributeValue)
		{ 
			if(strcmp($attributeTitle, $kTagExitToWebPage) == 0){
				$xml[$attributeTitle] = Games::getNewId($attributeValue, $newWebPageIds);
			}

			else if(strcmp($attributeTitle, $kTagExitToPanoramic) == 0){
				$xml[$attributeTitle] = Games::getNewId($attributeValue, $newAugBubbleIds);
			}
		}


		foreach($xml->children() as $child)
		{
			foreach($child->attributes() as $attributeTitle => $attributeValue)
			{ 
				if(strcmp($attributeTitle, $kTagExitToWebPage) == 0){
					$child[$attributeTitle] = Games::getNewId($attributeValue, $originalWebPageId, $newWebPageId);
				}

				else if(strcmp($attributeTitle, $kTagExitToPanoramic) == 0){
					$child[$attributeTitle] = Games::getNewId($attributeValue, $originalAugBubbleId, $newAugBubbleId);
				}

				else if(strcmp($attributeTitle, $kTagMedia) == 0){
					$child[$attributeTitle] = Games::getNewId($attributeValue, $originalMediaId, $newMediaId);
				}
				else if(strcmp($child->getName(), $kTagVideo) == 0 && strcmp($attributeTitle, $kTagId) == 0){
					$child[$attributeTitle] = Games::getNewId($attributeValue, $originalMediaId, $newMediaId);
				}
				else if(strcmp($child->getName(), $kTagPanoramic) == 0 && strcmp($attributeTitle, $kTagId) == 0){
					$child[$attributeTitle] = Games::getNewId($attributeValue, $originalAugBubbleId, $newAugBubbleId);
				}
				else if(strcmp($child->getName(), $kTagWebpage) == 0 && strcmp($attributeTitle, $kTagId) == 0){
					$child[$attributeTitle] = Games::getNewId($attributeValue, $originalWebPageId, $newWebPageId);
				}
			}
		}
		$output = $xml->asXML();
		$output = str_replace("&#x2019;", "'", $output);
		$output = str_replace("&amp;", "&", $output);
		$output = str_replace("&#x2014;", "-", $output);
		$output = str_replace("&#x201C;", "\"", $output);
		$output = str_replace("&#x201D;", "\"", $output);
		$output = str_replace("&#xB0;", "°", $output);
		$output = str_replace("&#xAE;", "®", $output);
		$output = str_replace("&#x2122;", "™", $output);
		$output = str_replace("&#xA9;", "©", $output);
		return $output;
	}

	static function getNewId($id, $oldIdList, $newIdList)
	{
		return $newIdList[array_search($id,$oldIdList)];
	}

	function addNoteTagToGame($gameId, $tag)
	{
		$query = "INSERT INTO game_tags (game_id, tag) VALUES ('{$gameId}', '{$tag}')";
		$rs = @mysql_query($query);
		if (mysql_error())  return new returnData(3, NULL, 'SQL error');
		return new returnData(0);
	}

	public static function getDetailedGameInfo($gameId)
	{
		$query = "SELECT games.*, pcm.name as pc_media_name, pcm.file_name as pc_media_url, m.name as media_name, m.file_name as media_url, im.name as icon_name, im.file_name as icon_url FROM games LEFT JOIN media as m ON games.media_id = m.media_id LEFT JOIN media as im ON games.icon_media_id = im.media_id LEFT JOIN media as pcm on games.pc_media_id = pcm.media_id WHERE games.game_id = '{$gameId}'";

		$result = mysql_query($query);
		$game = mysql_fetch_object($result);
		if(!$game) return "Invalid Game ID";

		if($game->media_url) $game->media_url = Media::getMediaDirectoryURL($gameId)->data . '/' . $game->media_url;
		if($game->icon_url) $game->icon_url = Media::getMediaDirectoryURL($gameId)->data . '/' . $game->icon_url;


		$query = "SELECT editors.name FROM game_editors JOIN editors ON editors.editor_id = game_editors.editor_id WHERE game_editors.game_id = '{$gameId}'";
		$result = mysql_query($query);
		$auth = array();

		while($a = mysql_fetch_object($result))
			$auth[] = $a;

		$game->authors = $auth;

		return $game;
	}
}
?>
