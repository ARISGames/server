<?php
require("module.php");


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
	public function createGame($intEditorID, $strFullName, $strDescription)
	{

		$strFullName = addslashes($strFullName);	
		$strDescription = addslashes($strDescription);
        
                
		//Check if a game with this name has already been created
		$query = "SELECT * FROM games WHERE name = '{$strFullName}'";
		NetDebug::trace($query);
		if (mysql_num_rows(mysql_query($query)) > 0) 
		    return new returnData(4, NULL, 'duplicate name');
		
		
		//Create the game record in SQL
		$query = "INSERT INTO games (name, description) VALUES ('{$strFullName}','{$strDescription}')";
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
		
			
	

		//Copy the default site to the new name	
		$from = Config::gamedataFSPath . '/' . Config::defaultGameName;
		$to = Config::gamedataFSPath . "/{$strShortName}";
		$command = "cp -R -v -n $from $to";
		NetDebug::trace($command);
		exec($command, $output, $return);
		if ($return) return new returnData(5, NULL, 'cannot copy default site');
	
		//Create the SQL tables

		$query = "CREATE TABLE {$strShortName}_items (
			item_id int(11) unsigned NOT NULL auto_increment,
			name varchar(255) default NULL,
			description text,
			icon_media_id int(10) unsigned NOT NULL default '0',
			media_id int(10) unsigned NOT NULL default '0',
			dropable enum('0','1') NOT NULL default '0',
			destroyable enum('0','1') NOT NULL default '0',
			PRIMARY KEY  (item_id)
			)ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create items table');
				
		$query = "CREATE TABLE {$strShortName}_events (
			event_id int(10) unsigned NOT NULL auto_increment,
  			description tinytext,
 			 PRIMARY KEY  (event_id)
			)ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create events table');
		
		
		$query = "CREATE TABLE {$strShortName}_player_state_changes (
			id int(10) unsigned NOT NULL auto_increment,
			content_type enum('Node','Item','Npc') NOT NULL,
			content_id int(10) unsigned NOT NULL,
			action enum('GIVE_ITEM','GIVE_EVENT','TAKE_ITEM') NOT NULL,
			action_detail int(10) unsigned NOT NULL,
			PRIMARY KEY  (id)
			)ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create player_state_changes table');
		
		
		
		$query = "CREATE TABLE {$strShortName}_requirements (
			requirement_id int(11) NOT NULL auto_increment,
			content_type enum('Node','QuestDisplay','QuestComplete','Item','Npc','Location') NOT NULL,
			content_id int(10) unsigned NOT NULL,
			requirement enum('HAS_ITEM','HAS_EVENT','DOES_NOT_HAVE_ITEM','DOES_NOT_HAVE_EVENT') NOT NULL,
			requirement_detail int(11) NOT NULL,
			PRIMARY KEY  (requirement_id)
			)ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create requirments table');
		
	
		$query = "CREATE TABLE {$strShortName}_locations (
	  		location_id int(11) NOT NULL auto_increment,
			name varchar(255) default NULL,
			description tinytext,
			latitude double default '43.0746561',
			longitude double default '-89.384422',
			error double default '5',
			type enum('Node','Event','Item','Npc') NOT NULL,
			type_id int(11) NOT NULL,
			icon_media_id int(10) unsigned NOT NULL default '0',
			item_qty int(11) NOT NULL default '0',
			hidden enum('0','1') NOT NULL default '0',
			force_view enum('0','1') NOT NULL default '0',
			PRIMARY KEY  (location_id)
			)ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create locations table');
	
		$query = "CREATE TABLE {$strShortName}_quests (
			 quest_id int(11) unsigned NOT NULL auto_increment,
			 name tinytext,
			 description text,
			 text_when_complete tinytext NOT NULL COMMENT 'This is the txt that displays on the completed quests screen',
			 icon_media_id int(10) unsigned NOT NULL default '0',
			 PRIMARY KEY  (quest_id)
			)ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create quests table');
		
		$query = "CREATE TABLE {$strShortName}_nodes (
			  node_id int(11) unsigned NOT NULL auto_increment,
			  title varchar(255) default NULL,
			  text text,
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
		
	
		$query = "CREATE TABLE {$strShortName}_npcs (
			npc_id int(10) unsigned NOT NULL auto_increment,
			name varchar(255) NOT NULL default '',
			description tinytext,
			text tinytext,
			media_id int(10) unsigned NOT NULL default '0',
			PRIMARY KEY  (npc_id)
			)ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create npcs table');
		
		$query = "CREATE TABLE {$strShortName}_player_events (
			id int(11) NOT NULL auto_increment,
			player_id int(10) unsigned NOT NULL default '0',
			event_id int(10) unsigned NOT NULL default '0',
			timestamp timestamp NOT NULL default CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY `unique` (player_id,event_id)
			)ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create player_events table');
		
		$query = "CREATE TABLE {$strShortName}_player_items (
			id int(11) NOT NULL auto_increment,
			player_id int(11) unsigned NOT NULL default '0',
			item_id int(11) unsigned NOT NULL default '0',
			timestamp timestamp NOT NULL default CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY `unique` (player_id,item_id),
			KEY player_id (player_id),
			KEY item_id (item_id)
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
		
		
		
		$query = "CREATE TABLE {$strShortName}_media (
            media_id INT UNSIGNED NOT NULL AUTO_INCREMENT ,
            name VARCHAR( 255 ) NOT NULL,
            file_name VARCHAR( 255 ) NOT NULL,
            is_default tinyint(1) default '0',
            is_icon tinyint(1) default '0',
            PRIMARY KEY ( media_id )
            )ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create media table');	
		
		
		$query = "CREATE TABLE {$strShortName}_folders (
			folder_id int(10) unsigned NOT NULL auto_increment,
  			name varchar(50) collate utf8_unicode_ci NOT NULL,
 			parent_id int(11) NOT NULL default '0',
  			previous_id int(11) NOT NULL default '0',
  			PRIMARY KEY  (folder_id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create folders table');	



		$query = "CREATE TABLE {$strShortName}_folder_contents (
  			object_content_id int(10) unsigned NOT NULL auto_increment,
  			folder_id int(10) unsigned NOT NULL default '0',
  			content_type enum('Node','Item','Npc') collate utf8_unicode_ci NOT NULL default 'Node',
  			content_id int(10) unsigned NOT NULL default '0',
  			previous_id int(10) unsigned NOT NULL default '0',
  			PRIMARY KEY  (object_content_id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		@mysql_query($query);
		if (mysql_error()) return new returnData(6, NULL, 'cannot create folder contents table: ' . mysql_error());	



		return new returnData(0, $newGameID, NULL);

		
	}
	
	
	/**
     * Updates a game's information
     * @returns true if a record was updated, false otherwise
     */	
	public function updateGame($intGameID, $strNewName, $strNewDescription)
	{
	    $returnData = new returnData(0, mysql_query($query), NULL);

		$strNewGameName = addslashes($strNewGameName);	
	
		$query = "UPDATE games 
				SET name = '{$strNewName}',
				description = '{$strNewDescription}'
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
		
		return new returnData(0, FALSE);
	}
	
	
	
	
	
	/**
     * Updates a game's database to the most current version
     */	
	public function upgradeGameDatabase($intGameID)
	{	
		$prefix = $this->getPrefix($intGameID);
		
		$messages = array();
		
		$message = "Adding is_icon to Media Table";
		$query = "ALTER TABLE {$prefix}_media ADD is_icon BOOL NOT NULL DEFAULT '0'";
		mysql_query($query);
		$message .= ":" . mysql_error();
		$messages[] = $message;
		
		$message = "Changing Name to length 255 in Locations Table";
		$query = "ALTER TABLE {$prefix}_locations CHANGE name name VARCHAR( 255 ) NULL DEFAULT NULL";
		mysql_query($query);
		$message .= ":" . mysql_error();
		$messages[] = $message;
		
		$message = "Changing title to length 255 in Nodes Table";
		$query = "ALTER TABLE {$prefix}_nodes CHANGE title title VARCHAR( 255 ) NULL DEFAULT NULL";
		mysql_query($query);
		$message .= ":" . mysql_error();
		$messages[] = $message;

		$message = "Changing Name to length 255 in NPC Table";
		$query = "ALTER TABLE {$prefix}_npcs CHANGE name name VARCHAR( 255 ) NULL DEFAULT NULL";
		mysql_query($query);
		$message .= ":" . mysql_error();
		$messages[] = $message;
		
		$message = "Changing Name to length 255 in Items Table";
		$query = "ALTER TABLE {$prefix}_items CHANGE name name VARCHAR( 255 ) NULL DEFAULT NULL";
		mysql_query($query);
		$message .= ":" . mysql_error();
		$messages[] = $message;	
		
		$message = "Changing Name to length 255 in Locations Table";
		$query = "ALTER TABLE {$prefix}_locations CHANGE name name VARCHAR( 255 ) NULL DEFAULT NULL";
		mysql_query($query);
		$message .= ":" . mysql_error();
		$messages[] = $message;		
		
		$message = "Renaming field type to link_type in QRCode Table";
		$query = "ALTER TABLE {$prefix}_qrcodes CHANGE type link_type enum('Location') NOT NULL default 'Location'";
		mysql_query($query);
		$message .= ":" . mysql_error();
		$messages[] = $message;		
		
		$message = "Renaming field link_id in QRCode Table";
		$query = "ALTER TABLE {$prefix}_qrcodes CHANGE type_id link_id int(11) NOT NULL";
		mysql_query($query);
		$message .= ":" . mysql_error();
		$messages[] = $message;		
		
		$message = "Adding code field in QRCode Table";
		$query = "ALTER TABLE {$prefix}_qrcodes ADD code varchar(255) NOT NULL";
		mysql_query($query);
		$message .= ":" . mysql_error();
		$messages[] = $message;	
		
		$message = "Adding unique key to code field in QRCode Table";
		$query = "ALTER TABLE {$prefix}_qrcodes ADD UNIQUE code (code(255))";
		mysql_query($query);
		$message .= ":" . mysql_error();
		$messages[] = $message;	
			
		return new returnData(0, FALSE, $messages);	
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
	    return new returnData(1, NULL, "Copy Game Not Implemented on Server");
	}	
	
	
	
	
	/**
     * Create a new game
     * @returns returnCode = 0 on success
     */	
	public function deleteGame($intGameID)
	{
		$returnData = new returnData(0, NULL, NULL);
		
		$prefix = $this->getPrefix($intGameID);
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
		
		
		//Delete the game record
		$query = "DELETE FROM games WHERE prefix = '{$prefix}_'";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, 'SQL Error');	


		//Fetch the table names for this game
		$query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='" . Config::dbSchema . "' AND TABLE_NAME LIKE '{$prefix}_%'";
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
		
		return new returnData(0);	
	}	
	
	
	
	
	
	/**
     * Creates a game archive package
     * @returns the path to the file
     */		
	public function backupGame($intGameID)
	{
	
		$prefix = $this->getPrefix($intGameID);
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
		$query = "SELECT * FROM editors LEFT JOIN game_editors ON (editors.editor_id = game_editors.editor_id) WHERE editor_id = {$intGameID}";
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
		if (mysql_error()) return new returnData(1, NULL, 'SQL Error');
		
		if (mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);
	}

	
}
?>