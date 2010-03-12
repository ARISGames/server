<?php
require("module.php");


class Quests extends Module
{	
	
	/**
     * Fetch all Quests
     * @returns the quests
     */
	public function getQuests($intGameID)
	{
		
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		
		$query = "SELECT * FROM {$prefix}_quests";
		//NetDebug::trace($query);

		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, "SQL Error");
		
		return new returnData(0, $rsResult);
	}
	
	/**
     * Fetch all Quests for a paticular player
     * @returns a returnData object with two arrays, active and completed
     */
	public function getQuestsForPlayer($intGameID,$intPlayerID)
	{
		
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_quests";
		//NetDebug::trace($query);

		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, "SQL Error");
		
		$activeQuests = array();
		$completedQuests = array();
		
		//Walk the rs add each quest to the correct array
		while ($quest = mysql_fetch_object($rsResult)) {
			
			$display = $this->objectMeetsRequirements ($prefix, 
												$intPlayerID, 
												"QuestDisplay", 
												$quest->quest_id);
			$complete = $this->objectMeetsRequirements ($prefix, 
												$intPlayerID, 
												"QuestComplete", 
												$quest->quest_id);	
												
			//NetDebug::trace("Quest " . $quest->quest_id . ": display = $display complete = $complete");									
			
			if ($display && !$complete) $activeQuests[] = $quest;
			if ($display && $complete) $completedQuests[] = $quest;
			


		}	
		$quests = (object) array('active' => $activeQuests, 'completed' => $completedQuests);
	
		return new returnData(0, $quests);
	}	
	
	
	/**
     * Fetch a specific event
     * @returns a single event
     */
	public function getQuest($intGameID, $intQuestID)
	{
		
		$prefix = $this->getPrefix($intGameID);
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
	public function createQuest($intGameID, $strName, $strIncompleteDescription, $strCompleteDescription, $intIconMediaID)
	{
		
		$strName = addslashes($strName);	
		$strIncompleteDescription = addslashes($strIncompleteDescription);	
		$strCompleteDescription = addslashes($strCompleteDescription);	
		
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "INSERT INTO {$prefix}_quests 
					(name, description, text_when_complete, icon_media_id)
					VALUES ('{$strName}','{$strIncompleteDescription}','{$strCompleteDescription}','{$intIconMediaID}')";
		
		NetDebug::trace("Running a query = $query");	
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		return new returnData(0, mysql_insert_id());
	}

	
	
	/**
     * Update a specific Event
     * @returns true if edit was done, false if no changes were made
     */
	public function updateQuest($intGameID, $intQuestID, $strName, $strIncompleteDescription, $strCompleteDescription, $intIconMediaID)
	{
		
		$strName = addslashes($strName);	
		$strIncompleteDescription = addslashes($strIncompleteDescription);	
		$strCompleteDescription = addslashes($strCompleteDescription);	
		
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "UPDATE {$prefix}_quests 
					SET 
					name = '{$strName}',
					description = '{$strIncompleteDescription}',
					text_when_complete = '{$strCompleteDescription}',
					media_id = '{$intIconMediaID}'
					WHERE quest_id = '{$intQuestID}'";
		
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
	public function deleteQuest($intGameID, $intQuestID)
	{
		$prefix = $this->getPrefix($intGameID);
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
	
	
}