<?php
require_once("module.php");

class Quests extends Module
{	
    public function getQuests($gameId)
    {
        $query = "SELECT * FROM quests WHERE game_id = {$gameId} ORDER BY sort_index";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        return new returnData(0, $rsResult);
    }

    public function getQuestsForPlayer($gameId,$intPlayerID)
    {
        $query = "SELECT * FROM quests WHERE game_id = {$gameId} ORDER BY sort_index";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $activeQuests = array();
        $completedQuests = array();

        //Walk the rs add each quest to the correct array
        while ($quest = mysql_fetch_object($rsResult)) {
            $display = Module::objectMeetsRequirements ($gameId, $intPlayerID, "QuestDisplay", $quest->quest_id);
            $complete = Module::playerHasLog($gameId, $intPlayerID, Module::kLOG_COMPLETE_QUEST, $quest->quest_id);

            if ($display && !$complete) $activeQuests[] = $quest;
            if ($display && $complete) $completedQuests[] = $quest;
        }	

        $query = "SELECT count(quest_id) as `count` FROM (SELECT * FROM quests WHERE game_id = {$gameId}) AS game_quests";
        $countRs = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        $count = @mysql_fetch_object($countRs);

        $quests = (object) array('totalQuests' => $count->count, 'active' => $activeQuests, 'completed' => $completedQuests);

        return new returnData(0, $quests);
    }	

    public function getQuest($gameId, $intQuestID)
    {
        $query = "SELECT * FROM quests WHERE game_id = {$gameId} AND quest_id = {$intQuestID} LIMIT 1";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $event = @mysql_fetch_object($rsResult);
        if (!$event) return new returnData(2, NULL, "invalid quest id");

        return new returnData(0, $event);

    }

    public function createQuest($gameId, $strName, $strIncompleteDescription, $strCompleteDescription, $boolFullScreenNotification, $intActiveMediaId, $intCompleteMediaId, $intActiveIconMediaId, $intCompleteIconMediaID, $exitToTab, $index, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $strName = addslashes($strName);	
        $strIncompleteDescription = addslashes($strIncompleteDescription);	
        $strCompleteDescription = addslashes($strCompleteDescription);	

        $query = "INSERT INTO quests 
            (game_id, name, description, text_when_complete, sort_index, exit_to_tab, full_screen_notify)
            VALUES ('{$gameId}','{$strName}','{$strIncompleteDescription}','{$strCompleteDescription}','{$index}','{$exitToTab}','{$boolFullScreenNotification}')";


        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        return new returnData(0, mysql_insert_id());
    }

    public function updateQuest($gameId, $intQuestID, $strName, $strIncompleteDescription, $strCompleteDescription, $boolFullScreenNotification, $intActiveMediaId, $intCompleteMediaId, $intActiveIconMediaId, $intCompleteIconMediaID, $exitToTab, $index, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $strName = addslashes($strName);	
        $strIncompleteDescription = addslashes($strIncompleteDescription);	
        $strCompleteDescription = addslashes($strCompleteDescription);	

        $query = "UPDATE quests 
            SET 
            name = '{$strName}',
                 description = '{$strIncompleteDescription}',
                 text_when_complete = '{$strCompleteDescription}',
                 sort_index = '{$index}',
                 exit_to_tab = '{$exitToTab}',
                 full_screen_notify = '{$boolFullScreenNotification}'
                     WHERE game_id = {$gameId} AND quest_id = '{$intQuestID}'";

        Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);

    }

    public function deleteQuest($gameId, $intQuestID, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $query = "DELETE FROM quests WHERE game_id = {$gameId} AND quest_id = {$intQuestID}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else                       return new returnData(2, NULL, 'invalid event id');
    }	

    public function swapSortIndex($gameId, $a, $b, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $query = "SELECT * FROM quests WHERE game_id = {$gameId} AND (quest_id = '{$a}' OR quest_id = '{$b}')";
        $result = Module::query($query);

        $quests = array();
        while($quest = mysql_fetch_object($result))
            $quests[$quest->quest_id] = $quest;

        $query = "UPDATE quests SET sort_index = '{$quests[$a]->sort_index}' WHERE game_id = {$gameId} AND quest_id = '{$b}'";
        Module::query($query);
        $query = "UPDATE quests SET sort_index = '{$quests[$b]->sort_index}' WHERE game_id = {$gameId} AND quest_id = '{$a}'";
        Module::query($query);

        return new returnData(0);
    }
}
