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

	public function getGamesForPlayerAtLocation($playerId, $latitude, $longitude, $maxDistance, $locational, $includeGamesinDevelopment)
	{
	    if ($includeGamesinDevelopment) $query = "SELECT games.* FROM games WHERE is_locational = $locational";
        else $query = "SELECT games.* FROM games WHERE is_locational = $locational AND ready_for_public = TRUE";
        
		$gamesRs = @mysql_query($query);
		NetDebug::trace(mysql_error());
        
		$games = array();
		
		while ($game = @mysql_fetch_object($gamesRs)) {
			
            //Calculate the game distances from the player position
            $query = "SELECT latitude, longitude,((ACOS(SIN($latitude * PI() / 180) * SIN(latitude * PI() / 180) + 
            COS($latitude * PI() / 180) * COS(latitude * PI() / 180) * 
            COS(($longitude - longitude) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) * 1609.344
            AS `distance`
            FROM {$game->game_id}_locations
            WHERE type != 'Item' OR item_qty > 0
            ORDER BY distance ASC";
            //NetDebug::trace($query);	
            $nearestLocationRs = @mysql_query($query);
            NetDebug::trace(mysql_error());	
            $nearestLocation = @mysql_fetch_object($nearestLocationRs);
                        
            NetDebug::trace("Game " . $game->game_id . "'s nearest location is: " . $nearestLocation->distance);
            
            //Check for distance requirement
            if (!$nearestLocation || $nearestLocation->distance > $maxDistance) {
                NetDebug::trace("Skip");	
                continue;
            }
            
            
            NetDebug::trace("Select");	            
            $game->distance = $nearestLocation->distance;
            $game->latitude = $nearestLocation->latitude;
            $game->longitude = $nearestLocation->longitude;
            
            //Calculate the quest info
            $questsReturnData = Quests::getQuestsForPlayer($game->game_id,$playerId);
            $game->totalQuests = $questsReturnData->data->totalQuests;
            $game->completedQuests = count($questsReturnData->data->completed);

			//Calculate the editors
			$query = "SELECT editors.* FROM editors, game_editors
            WHERE game_editors.editor_id = editors.editor_id
            AND game_editors.game_id = {$game->game_id}";
			$editorsRs = @mysql_query($query);
			
			$editor = @mysql_fetch_array($editorsRs);
			$editorsString = $editor['name'];
			while ($editor = @mysql_fetch_array($editorsRs)) {
				$editorsString .= ', ' . $editor['name'];
			}
			
			//NetDebug::trace("GameID {$game->game_id} has editors: {$editorsString}");
			$game->editors = $editorsString;
			
			//Calculate the Number of Players
			$query = "SELECT * FROM players
            WHERE last_game_id = {$game->game_id}";
			$playersRs = @mysql_query($query);
			$game->numPlayers = @mysql_num_rows($playersRs);
            
            //Calculate the media URLs
            NetDebug::trace("Fetch Media for game_id=" . $game->game_id . " media_id=" . $game->icon_media_id);	
            $icon_media_data = Media::getMediaObject($game->game_id, $game->icon_media_id);
            $icon_media = $icon_media_data->data; 
            $game->icon_media_url = $icon_media->url_path . $icon_media->file_name;
            
            $media_data = Media::getMediaObject($game->game_id, $game->media_id);
            $media = $media_data->data; 
            $game->media_url = $media->url_path . $media->file_name;
            
            
            //Calculate the rating
            $game->rating = 0;
            $query = "SELECT SUM(rating) FROM game_comments WHERE game_id = {$game->game_id} GROUP BY game_id";
			$sumRs = @mysql_query($query);
            $sumRecord = @mysql_fetch_object($sumRs);
            $game->rating = $sumRecord->rating;
        
            
			$games[] = $game;
		}
        
		return new returnData(0, $games, NULL);		
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
								$boolAllowPlayerCreatedLocations, $boolResetDeletesPlayerCreatedLocations,
								$intIntroNodeId, $intCompleteNodeId)
	{

		$strFullName = addslashes($strFullName);	
		$strDescription = addslashes($strDescription);
        
                
		//Check if a game with this name has already been created
		$query = "SELECT * FROM games WHERE name = '{$strFullName}'";
		NetDebug::trace($query);
		if (mysql_num_rows(mysql_query($query)) > 0) 
		    return new returnData(4, NULL, 'duplicate name');
		
		
		//Create the game record in SQL
		$query = "INSERT INTO games (name, description, pc_media_id, icon_media_id, media_id,
									is_locational, ready_for_public,
									allow_player_created_locations, delete_player_locations_on_reset,
									on_launch_node_id, game_complete_node_id)
					VALUES ('{$strFullName}','{$strDescription}','{$intPCMediaID}','{$intIconMediaID}', '{$intMediaID}',
							'{$boolIsLocational}', '{$boolReadyForPublic}', 
							'{$boolAllowPlayerCreatedLocations}','{$boolResetDeletesPlayerCreatedLocations}',
							'{$intIntroNodeId}','{$intCompleteNodeId}')";
		@mysql_query($query);
		if (mysql_error())  return new returnData(6, NULL, 'cannot create game record');
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
			icon_media_id int(10) unsigned NOT NULL default '0',
			media_id int(10) unsigned NOT NULL default '0',
			dropable enum('0','1') NOT NULL default '0',
			destroyable enum('0','1') NOT NULL default '0',
			max_qty_in_inventory INT NOT NULL DEFAULT  '-1' COMMENT  '-1 for infinite, 0 if it can''t be picked up',
			creator_player_id int(10) unsigned NOT NULL default '0',
  			origin_latitude double NOT NULL default '0',
  			origin_longitude double NOT NULL default '0',
  			origin_timestamp timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			PRIMARY KEY  (item_id)
			)ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create items table' . mysql_error());
				
		
		$query = "CREATE TABLE {$strShortName}_player_state_changes (
			id int(10) unsigned NOT NULL auto_increment,
			event_type enum('VIEW_ITEM', 'VIEW_NODE', 'VIEW_NPC' ) NOT NULL,
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
			content_type enum('Node','QuestDisplay','QuestComplete','Location') NOT NULL,
			content_id int(10) unsigned NOT NULL,
			requirement enum( 'PLAYER_HAS_ITEM', 'PLAYER_DOES_NOT_HAVE_ITEM', 'PLAYER_VIEWED_ITEM',
							'PLAYER_HAS_NOT_VIEWED_ITEM', 'PLAYER_VIEWED_NODE', 'PLAYER_HAS_NOT_VIEWED_NODE',
							'PLAYER_VIEWED_NPC', 'PLAYER_HAS_NOT_VIEWED_NPC', 
							'PLAYER_HAS_UPLOADED_MEDIA_ITEM',  'PLAYER_HAS_COMPLETED_QUEST'  ) NOT NULL,
			boolean_operator enum('AND','OR') NOT NULL DEFAULT 'AND',				
			requirement_detail_1 VARCHAR(30) NULL,
			requirement_detail_2 VARCHAR(30) NULL,
			requirement_detail_3 VARCHAR(30) NULL,
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
			type enum('Node','Event','Item','Npc') NOT NULL DEFAULT 'Node',
			type_id int(11) NOT NULL,
			icon_media_id int(10) unsigned NOT NULL default '0',
			item_qty int(11) NOT NULL default '0' COMMENT  '-1 for infinite. Only effective for items',
			hidden enum('0','1') NOT NULL default '0',
			force_view enum('0','1') NOT NULL default '0',
			allow_quick_travel enum('0','1') NOT NULL default '0',
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
			PRIMARY KEY  (conversation_id)
			)ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create conversations table');


		$query = "CREATE TABLE  `{$strShortName}_npc_greetings` (
			`npc_greeting_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`npc_id` INT UNSIGNED NOT NULL ,
			`script` TEXT NOT NULL
			) ENGINE = INNODB;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create npc greetings table');

	
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
  			content_type enum('Node','Item','Npc') collate utf8_unicode_ci NOT NULL default 'Node',
  			content_id int(10) unsigned NOT NULL default '0',
  			previous_id int(10) unsigned NOT NULL default '0',
  			PRIMARY KEY  (object_content_id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create folder contents table: ' . mysql_error());	

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
								$boolAllowPlayerCreatedLocations, $boolResetDeletesPlayerCreatedLocations,
								$intIntroNodeId, $intCompleteNodeId)
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
				allow_player_created_locations = '{$boolAllowPlayerCreatedLocations}',
				delete_player_locations_on_reset = '{$boolResetDeletesPlayerCreatedLocations}',
				is_locational = '{$boolIsLocational}',
				ready_for_public = '{$boolReadyForPublic}',
				on_launch_node_id = '{$intIntroNodeId}',
				game_complete_node_id = '{$intCompleteNodeId}'
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
	public function upgradeGameDatabases() 
	{		
		$query = "SELECT * FROM games";
		$rs = @mysql_query($query);
		if (mysql_error())  return new returnData(3, NULL, 'SQL error');
		
		while ($game = mysql_fetch_object($rs)) {
			NetDebug::trace("Upgrade Game: {$game->game_id}");
			$upgradeResult = Games::upgradeGameDatabase($game->game_id);
		}
        
        //System wide changes below
		
		$query = "ALTER TABLE `games` ADD `delete_player_locations_on_reset` BOOLEAN NOT NULL DEFAULT '0'";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());
			
		$query = "ALTER TABLE `games` ADD `on_launch_node_id` INT UNSIGNED NOT NULL DEFAULT '0'";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());
		
		$query = "ALTER TABLE  `games` ADD  `game_complete_node_id` INT UNSIGNED NOT NULL DEFAULT  '0'";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());
		
		$query = "ALTER TABLE  `player_log` ADD INDEX  `check_for_log` (  `player_id` ,  `game_id` ,  `event_type` ,  `event_detail_1` ,  `deleted` )";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());
		
		$query = "ALTER TABLE  `games` ADD INDEX  `prefixKey` (  `prefix` )";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());

		$query = "ALTER TABLE  `games` ADD  `ready_for_public` TINYINT( 1 ) NOT NULL DEFAULT  '0'";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());
		
		$query = "ALTER TABLE  `games` ADD  `is_locational` TINYINT( 1 ) NOT NULL DEFAULT  '0'";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());

		$query = "ALTER TABLE  `games` ADD  `media_id` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `icon_media_id`";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());
        
        
        $query = "CREATE TABLE `game_comments` (
                                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                      `game_id` int(10) unsigned NOT NULL,
                                      `player_id` int(10) unsigned NOT NULL,
                                      `time_stamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                      `rating` enum('1','-1') NOT NULL,
                                      `comment` tinyint(4) NOT NULL,
                                      PRIMARY KEY (`id`),
                                      KEY `game_id` (`game_id`),
                                      KEY `player_id` (`player_id`),
                                      KEY `time_stamp` (`time_stamp`)
                                      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());
        
        
		return new returnData(0, FALSE);
	}
	
	
	
	
	
	/**
     * Updates a game's database to the most current version
     */	
	public function upgradeGameDatabase($intGameID)
	{	
		$prefix = Module::getPrefix($intGameID);


		$query = "ALTER TABLE  {$prefix}_locations ADD allow_quick_travel enum('0','1') NOT NULL default '0'";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());
		
		$query = "ALTER TABLE  `{$prefix}_requirements` ADD  `boolean_operator` ENUM(  'AND',  'OR' ) NOT NULL DEFAULT  'AND' AFTER  `requirement`,
					ADD INDEX (  `boolean_operator` )";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());
		
		$query = "ALTER TABLE  `{$prefix}_player_items` ADD  `qty` INT NOT NULL DEFAULT  '0' AFTER  `item_id` ,
					ADD INDEX (  `qty` )";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());
		
		$query = "ALTER TABLE  `{$prefix}_player_state_changes` ADD  `action_amount` INT NOT NULL DEFAULT  '1',
					ADD INDEX (  `action_amount` )";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());
				
		$query = "ALTER TABLE  `{$prefix}_player_state_changes` CHANGE  `event_detail`  `event_detail` INT UNSIGNED NOT NULL";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());

		$query = "ALTER TABLE {$prefix}_player_state_changes ADD INDEX  event_lookup ( event_type , event_detail )";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());

		$query = "ALTER TABLE  `{$prefix}_items` ADD  `max_qty_in_inventory` INT NOT NULL DEFAULT  '-1' COMMENT  '-1 for infinite, 0 if it can''t be picked up' AFTER  `destroyable`";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());

		$query = "ALTER TABLE  `{$prefix}_npcs` ADD  `closing` TEXT NOT NULL AFTER `text`";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());
	
		$query = "ALTER TABLE `{$prefix}_requirements` ADD INDEX  `contentIndex` (  `content_type` ,  `content_id` )";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());	
		
				
		$query = "ALTER TABLE  `{$prefix}_folders` ADD  `is_open` ENUM(  '0',  '1' ) NOT NULL DEFAULT  '0';";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());

		$query = "ALTER TABLE  `{$prefix}_folder_contents` CHANGE  `folder_id`  `folder_id` INT( 10 ) NOT NULL DEFAULT  '0'";
		mysql_query($query);
		NetDebug::trace("$query" . ":" . mysql_error());
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
     * Create a new game
     * @returns returnCode = 0 on success
     */	
	public function deleteGame($intGameID)
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
		$query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='" . Config::dbSchema . "' AND TABLE_NAME LIKE '{$prefix}_%'";
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
		
		$getTablesCommand = Config::mysqlBinPath . "/mysql --user=" . Config::dbUser . " --password=". Config::dbPass ." -B --skip-column-names INFORMATION_SCHEMA -e \"SELECT TABLE_NAME FROM TABLES WHERE TABLE_SCHEMA='" .  Config::dbSchema . "' AND TABLE_NAME LIKE '{$prefix}_%'\"";
		
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

	
}
?>