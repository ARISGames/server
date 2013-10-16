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

    public function getQuestsForPlayer($gameId,$intPlayerId)
    {
        $query = "SELECT * FROM quests WHERE game_id = {$gameId} ORDER BY sort_index";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $activeQuests = array();
        $completedQuests = array();

        //Walk the rs add each quest to the correct array
        while ($quest = mysql_fetch_object($rsResult)) {
            $display = Module::objectMeetsRequirements ($gameId, $intPlayerId, "QuestDisplay", $quest->quest_id);
            $complete = Module::playerHasLog($gameId, $intPlayerId, Module::kLOG_COMPLETE_QUEST, $quest->quest_id);

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

    public function getQuest($gameId, $intQuestId)
    {
        $query = "SELECT * FROM quests WHERE game_id = {$gameId} AND quest_id = {$intQuestId} LIMIT 1";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $event = @mysql_fetch_object($rsResult);
        if (!$event) return new returnData(2, NULL, "invalid quest id");

        return new returnData(0, $event);
    }

    public function createQuest($gameId, $strName, $index, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $strName = addslashes($strName);	

        $query = "INSERT INTO quests 
            (game_id, name, sort_index)
            VALUES ('{$gameId}','{$strName}','{$index}')";


        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        return new returnData(0, mysql_insert_id());
    }

    public function updateQuest($gameId, $questId,
        $name,
        $activeText, $completeText, 
        $activeNotifText, $completeNotifText,
        $activeMediaId, $completeMediaId, 
        $activeIconMediaId, $completeIconMediaId, 
        $activeNotifMediaId, $completeNotifMediaId,
        $activeFunction, $completeFunction,
        $activeNotifFunction, $completeNotifFunction, 
        $activeFullScreenNotify, $completeFullScreenNotify,
        $activeShowDismiss, $completeShowDismiss
        $sortIndex,
        $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        Module::query("UPDATE quests SET
            name                            = '"+addslashes($name)+"',
            description                     = '"+addslashes($activeText)+"',
            text_when_complete              = '"+addslashes($completeText)+"',
            description_notification        = '"+addslashes($activeNotifText)+"',
            text_when_complete_notification = '"+addslashes($completeNotifText)+"',
            active_media_id                 = '"+intval($activeMediaId)+"',
            complete_media_id               = '"+intval($completeMediaId)+"',
            active_icon_media_id            = '"+intval($activeIconMediaId)+"',
            complete_icon_media_id          = '"+intval($completeIconMediaId)+"',
            active_notification_media_id    = '"+intval($activeNotifMediaId)+"',
            complete_notification_media_id  = '"+intval($completeNotifMediaId)+"',
            go_function                     = '"+addslashes($activeFunction)+"',
            complete_go_function            = '"+addslashes($completeFunction)+"',
            notif_go_function               = '"+addslashes($activeNotifFunction)+"',
            complete_notif_go_function      = '"+addslashes($completeNotifFunction)+"',
            full_screen_notify              = '"+intval($activeFullScreenNotify)+"',
            complete_full_screen_notify     = '"+intval($completeFullScreenNotify)+"',
            active_notif_show_dismiss       = '"+intval($activeShowDismiss)+"',
            complete_notif_show_dismiss     = '"+intval($completeShowDismiss)+"',
            sort_index                      = '"+intval($sortIndex)+"'
            WHERE
            game_id = '"+intval($gameId)+"' AND quest_id = '"+$questId+"';");

        if(mysql_error()) return new returnData(3, NULL, "SQL Error");

        if(mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);
    }

    public function deleteQuest($gameId, $intQuestId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $query = "DELETE FROM quests WHERE game_id = {$gameId} AND quest_id = {$intQuestId}";

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
