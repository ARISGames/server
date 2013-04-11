<?php
require_once("module.php");

class Quests extends Module
{	
    public function getQuests($intGameId)
    {
        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "SELECT * FROM quests WHERE game_id = {$prefix} ORDER BY sort_index";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        return new returnData(0, $rsResult);
    }

    public function getQuestsForPlayer($intGameId,$intPlayerID)
    {
        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "SELECT * FROM quests WHERE game_id = {$prefix} ORDER BY sort_index";

        $rsResult = Module::query($query);
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

        $query = "SELECT count(quest_id) as `count` FROM (SELECT * FROM quests WHERE game_id = {$prefix}) AS game_quests";
        $countRs = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        $count = @mysql_fetch_object($countRs);

        $quests = (object) array('totalQuests' => $count->count, 'active' => $activeQuests, 'completed' => $completedQuests);

        return new returnData(0, $quests);
    }	

    public function getQuest($intGameId, $intQuestID)
    {
        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "SELECT * FROM quests WHERE game_id = {$prefix} AND quest_id = {$intQuestID} LIMIT 1";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $event = @mysql_fetch_object($rsResult);
        if (!$event) return new returnData(2, NULL, "invalid quest id");

        return new returnData(0, $event);

    }

    public function createQuest($intGameId, $strName, $strIncompleteDescription, $strCompleteDescription, $boolFullScreenNotification, $intActiveMediaId = 0, $intCompleteMediaId = 0, $intActiveIconMediaId = 0, $intCompleteIconMediaID = 0, $exitToTab = 'NONE', $index = 0)
    {

        $strName = addslashes($strName);	
        $strIncompleteDescription = addslashes($strIncompleteDescription);	
        $strCompleteDescription = addslashes($strCompleteDescription);	

        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "INSERT INTO quests 
            (game_id, name, description, text_when_complete, sort_index, exit_to_tab, full_screen_notify)
            VALUES ('{$prefix}','{$strName}','{$strIncompleteDescription}','{$strCompleteDescription}','{$index}','{$exitToTab}','{$boolFullScreenNotification}')";


        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        return new returnData(0, mysql_insert_id());
    }

    public function updateQuest($intGameId, $intQuestID, $strName, $strIncompleteDescription, $strCompleteDescription, $boolFullScreenNotification, $intActiveMediaId = 0, $intCompleteMediaId = 0, $intActiveIconMediaId = 0, $intCompleteIconMediaID = 0, $exitToTab = 'NONE', $index = 0)
    {
        $strName = addslashes($strName);	
        $strIncompleteDescription = addslashes($strIncompleteDescription);	
        $strCompleteDescription = addslashes($strCompleteDescription);	

        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "UPDATE quests 
            SET 
            name = '{$strName}',
                 description = '{$strIncompleteDescription}',
                 text_when_complete = '{$strCompleteDescription}',
                 sort_index = '{$index}',
                 exit_to_tab = '{$exitToTab}',
                 full_screen_notify = '{$boolFullScreenNotification}'
                     WHERE game_id = {$prefix} AND quest_id = '{$intQuestID}'";

        Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);

    }

    public function deleteQuest($intGameId, $intQuestID)
    {
        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "DELETE FROM quests WHERE game_id = {$prefix} AND quest_id = {$intQuestID}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else                       return new returnData(2, NULL, 'invalid event id');
    }	

    public function swapSortIndex($gameId, $a, $b)
    {
        $prefix = Module::getPrefix($gameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "SELECT * FROM quests WHERE game_id = {$prefix} AND (quest_id = '{$a}' OR quest_id = '{$b}')";
        $result = Module::query($query);

        $quests = array();
        while($quest = mysql_fetch_object($result))
            $quests[$quest->quest_id] = $quest;

        $query = "UPDATE quests SET sort_index = '{$quests[$a]->sort_index}' WHERE game_id = {$prefix} AND quest_id = '{$b}'";
        Module::query($query);
        $query = "UPDATE quests SET sort_index = '{$quests[$b]->sort_index}' WHERE game_id = {$prefix} AND quest_id = '{$a}'";
        Module::query($query);

        return new returnData(0);
    }
}
