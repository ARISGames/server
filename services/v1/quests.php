<?php
require_once("module.php");

class Quests extends Module
{	
    public function getQuests($gameId)
    {
        $rsResult = Module::query("SELECT * FROM quests WHERE game_id = {$gameId} ORDER BY sort_index");
        if(mysql_error()) return new returnData(3, NULL, "SQL Error");

        return new returnData(0, $rsResult);
    }

    public function getQuestsForPlayer($gameId,$playerId)
    {
        $quests = Module::queryArray("SELECT * FROM quests WHERE game_id = {$gameId} ORDER BY sort_index");
        if(mysql_error()) return new returnData(3, NULL, "SQL Error");

        $activeQuests = array();
        $completedQuests = array();

        for($i = 0; $i < count($quests); $i++)
        {
            $quest = $quests[$i];
            $display = Module::objectMeetsRequirements ($gameId, $playerId, "QuestDisplay", $quest->quest_id);
            $complete = Module::playerHasLog($gameId, $playerId, Module::kLOG_COMPLETE_QUEST, $quest->quest_id);

            if ($display && !$complete) $activeQuests[] = $quest;
            if ($display && $complete) $completedQuests[] = $quest;
        }	

        $return = new stdClass();
        $return->totalQuests = count($quests);
        $return->active = $activeQuests;
        $return->completed = $completedQuests;

        return new returnData(0, $return);
    }	

    public function getQuest($gameId, $questId)
    {
        $quest = Module::queryObject("SELECT * FROM quests WHERE game_id = {$gameId} AND quest_id = {$questId} LIMIT 1");
        if(!$quest) return new returnData(2, NULL, "invalid quest id");

        return new returnData(0, $quest);
    }

    public function createQuest($gameId, $name, $index, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $name = addslashes($name);	
        Module::query("INSERT INTO quests (game_id, name, sort_index) VALUES ('{$gameId}','{$name}','{$index}')");

        return new returnData(0, mysql_insert_id());
    }

    public function updateQuest($gameId, $questId,
        $name,
        $activeText,             $completeText, 
        $activeNotifText,        $completeNotifText,
        $activeMediaId,          $completeMediaId, 
        $activeIconMediaId,      $completeIconMediaId, 
        $activeNotifMediaId,     $completeNotifMediaId,
        $activeFunction,         $completeFunction,
        $activeNotifFunction,    $completeNotifFunction, 
        $activeFullScreenNotify, $completeFullScreenNotify,
        $activeShowDismiss,      $completeShowDismiss,
        $sortIndex,
        $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        Module::query("UPDATE quests SET
            name                            = '".addslashes($name)."',
            description                     = '".addslashes($activeText)."',
            text_when_complete              = '".addslashes($completeText)."',
            description_notification        = '".addslashes($activeNotifText)."',
            text_when_complete_notification = '".addslashes($completeNotifText)."',
            active_media_id                 = '".intval($activeMediaId)."',
            complete_media_id               = '".intval($completeMediaId)."',
            active_icon_media_id            = '".intval($activeIconMediaId)."',
            complete_icon_media_id          = '".intval($completeIconMediaId)."',
            active_notification_media_id    = '".intval($activeNotifMediaId)."',
            complete_notification_media_id  = '".intval($completeNotifMediaId)."',
            go_function                     = '".addslashes($activeFunction)."',
            complete_go_function            = '".addslashes($completeFunction)."',
            notif_go_function               = '".addslashes($activeNotifFunction)."',
            complete_notif_go_function      = '".addslashes($completeNotifFunction)."',
            full_screen_notify              = '".intval($activeFullScreenNotify)."',
            complete_full_screen_notify     = '".intval($completeFullScreenNotify)."',
            active_notif_show_dismiss       = '".intval($activeShowDismiss)."',
            complete_notif_show_dismiss     = '".intval($completeShowDismiss)."',
            sort_index                      = '".intval($sortIndex)."'
            WHERE
            game_id = '".intval($gameId)."' AND quest_id = '".$questId."';");

        if(mysql_error()) return new returnData(3, NULL, "SQL Error");

        if(mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);
    }

    public function deleteQuest($gameId, $questId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        Module::query("DELETE FROM quests WHERE game_id = {$gameId} AND quest_id = {$questId}");
        return new returnData(0, TRUE);
    }	

    public function swapSortIndex($gameId, $a, $b, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $result = Module::query("SELECT * FROM quests WHERE game_id = {$gameId} AND (quest_id = '{$a}' OR quest_id = '{$b}')");

        $quests = array();
        while($quest = mysql_fetch_object($result))
            $quests[$quest->quest_id] = $quest;

        Module::query("UPDATE quests SET sort_index = '{$quests[$a]->sort_index}' WHERE game_id = {$gameId} AND quest_id = '{$b}'");
        Module::query("UPDATE quests SET sort_index = '{$quests[$b]->sort_index}' WHERE game_id = {$gameId} AND quest_id = '{$a}'");

        return new returnData(0);
    }
}

