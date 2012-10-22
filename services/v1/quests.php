<?php
require_once("module.php");


class Quests extends Module
{	
	
	/**
     * Fetch all Quests
     * @returns the quests
     */
	public function getQuests($intGameID)
	{
		
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_quests ORDER BY sort_index";

		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		return new returnData(0, $rsResult);
	}
	
	/**
     * Fetch all Quests for a paticular player
     * @returns a returnData object with two arrays, active and completed
     */
	public function getQuestsForPlayer($intGameID,$intPlayerID)
	{
		
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_quests ORDER BY sort_index";
		//NetDebug::trace($query);

		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		$activeQuests = array();
		$completedQuests = array();
		
		//Walk the rs add each quest to the correct array
		while ($quest = mysql_fetch_object($rsResult)) {
			$display = Module::objectMeetsRequirements ($prefix, $intPlayerID, "QuestDisplay", $quest->quest_id);
			$complete = Module::playerHasLog($prefix, $intPlayerID, Module::kLOG_COMPLETE_QUEST, $quest->quest_id);

			if ($display && !$complete) $activeQuests[] = $quest;
			if ($display && $complete) $completedQuests[] = $quest;
		}	

		$query = "SELECT count(quest_id) as `count` FROM {$prefix}_quests";
		$countRs = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		$count = @mysql_fetch_object($countRs);

		$quests = (object) array('totalQuests' => $count->count, 'active' => $activeQuests, 'completed' => $completedQuests);

		return new returnData(0, $quests);
	}	


	/**
	 * Fetch a specific event
	 * @returns a single event
	 */
	public function getQuest($intGameID, $intQuestID)
	{

		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_quests WHERE quest_id = {$intQuestID} LIMIT 1";

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
	public function createQuest($intGameId, $strName, $strIncompleteDescription, $strCompleteDescription, $intIconMediaID, $index, $exitToTab = 'NONE')
	{

		$strName = addslashes($strName);	
		$strIncompleteDescription = addslashes($strIncompleteDescription);	
		$strCompleteDescription = addslashes($strCompleteDescription);	

		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "INSERT INTO quests 
			(game_id, name, description, text_when_complete, icon_media_id, sort_index, exit_to_tab)
			VALUES ('{$prefix}','{$strName}','{$strIncompleteDescription}','{$strCompleteDescription}','{$intIconMediaID}','{$index}','{$exitToTab}')";

		NetDebug::trace("Running a query = $query");	

		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

		return new returnData(0, mysql_insert_id());
	}



	/**
	 * Update a specific Event
	 * @returns true if edit was done, false if no changes were made
	 */
	public function updateQuest($intGameId, $intQuestID, $strName, $strIncompleteDescription, $strCompleteDescription, $intIconMediaID, $index, $exitToTab = 'NONE')
	{

		$strName = addslashes($strName);	
		$strIncompleteDescription = addslashes($strIncompleteDescription);	
		$strCompleteDescription = addslashes($strCompleteDescription);	

		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "UPDATE {$prefix}_quests 
			SET 
			name = '{$strName}',
			     description = '{$strIncompleteDescription}',
			     text_when_complete = '{$strCompleteDescription}',
			     icon_media_id = '{$intIconMediaID}',
			     sort_index = '{$index}'
			     exit_to_tab = '{$exitToTab}'
				     WHERE game_id = {$prefix} AND quest_id = '{$intQuestID}'";

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
	public function deleteQuest($intGameID, $intQuestID)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "DELETE FROM {$prefix}_quests WHERE quest_id = {$intQuestID}";

		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

		if (mysql_affected_rows()) {
			return new returnData(0, TRUE);
		}
		else {
			return new returnData(2, NULL, 'invalid event id');
		}

	}	

	public function swapSortIndex($gameId, $a, $b){
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_quests WHERE quest_id = '{$a}' OR quest_id = '{$b}'";
		$result = mysql_query($query);
		$quests = array();
		while($quest = mysql_fetch_object($result)){
			$quests[$quest->quest_id] = $quest;
		}

		$query = "UPDATE {$prefix}_quests SET sort_index = '{$quests[$a]->sort_index}' WHERE quest_id = '{$b}'";
		mysql_query($query);
		$query = "UPDATE {$prefix}_quests SET sort_index = '{$quests[$b]->sort_index}' WHERE quest_id = '{$a}'";
		mysql_query($query);

		return new returnData(0);
	}

}
