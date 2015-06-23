<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("return_package.php");

require_once("events.php");
require_once("events.php");
require_once("requirements.php");

class quests extends dbconnection
{
    //Takes in quest JSON, all fields optional except user_id + key
    public static function createQuest($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->quest_id = dbconnection::queryInsert(
            "INSERT INTO quests (".
            "game_id,".
            (isset($pack->name)                                 ? "name,"                                 : "").
            (isset($pack->description)                          ? "description,"                          : "").
            (isset($pack->active_icon_media_id)                 ? "active_icon_media_id,"                 : "").
            (isset($pack->active_media_id)                      ? "active_media_id,"                      : "").
            (isset($pack->active_description)                   ? "active_description,"                   : "").
            (isset($pack->active_notification_type)             ? "active_notification_type,"             : "").
            (isset($pack->active_function)                      ? "active_function,"                      : "").
            (isset($pack->active_requirement_root_package_id)   ? "active_requirement_root_package_id,"   : "").
            (isset($pack->active_event_package_id)              ? "active_event_package_id,"              : "").
            (isset($pack->complete_icon_media_id)               ? "complete_icon_media_id,"               : "").
            (isset($pack->complete_media_id)                    ? "complete_media_id,"                    : "").
            (isset($pack->complete_description)                 ? "complete_description,"                 : "").
            (isset($pack->complete_notification_type)           ? "complete_notification_type,"           : "").
            (isset($pack->complete_function)                    ? "complete_function,"                    : "").
            (isset($pack->complete_requirement_root_package_id) ? "complete_requirement_root_package_id," : "").
            (isset($pack->complete_event_package_id)            ? "complete_event_package_id,"            : "").
            (isset($pack->sort_index)                           ? "sort_index,"                           : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->name)                                 ? "'".addslashes($pack->name)."',"                                 : "").
            (isset($pack->description)                          ? "'".addslashes($pack->description)."',"                          : "").
            (isset($pack->active_icon_media_id)                 ? "'".addslashes($pack->active_icon_media_id)."',"                 : "").
            (isset($pack->active_media_id)                      ? "'".addslashes($pack->active_media_id)."',"                      : "").
            (isset($pack->active_description)                   ? "'".addslashes($pack->active_description)."',"                   : "").
            (isset($pack->active_notification_type)             ? "'".addslashes($pack->active_notification_type)."',"             : "").
            (isset($pack->active_function)                      ? "'".addslashes($pack->active_function)."',"                      : "").
            (isset($pack->active_requirement_root_package_id)   ? "'".addslashes($pack->active_requirement_root_package_id)."',"   : "").
            (isset($pack->active_event_package_id)              ? "'".addslashes($pack->active_event_package_id)."',"              : "").
            (isset($pack->complete_icon_media_id)               ? "'".addslashes($pack->complete_icon_media_id)."',"               : "").
            (isset($pack->complete_media_id)                    ? "'".addslashes($pack->complete_media_id)."',"                    : "").
            (isset($pack->complete_description)                 ? "'".addslashes($pack->complete_description)."',"                 : "").
            (isset($pack->complete_notification_type)           ? "'".addslashes($pack->complete_notification_type)."',"           : "").
            (isset($pack->complete_function)                    ? "'".addslashes($pack->complete_function)."',"                    : "").
            (isset($pack->complete_requirement_root_package_id) ? "'".addslashes($pack->complete_requirement_root_package_id)."'," : "").
            (isset($pack->complete_event_package_id)            ? "'".addslashes($pack->complete_event_package_id)."',"            : "").
            (isset($pack->sort_index)                           ? "'".addslashes($pack->sort_index)."',"                           : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return quests::getQuest($pack);
    }

    public static function updateQuest($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM quests WHERE quest_id = '{$pack->quest_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE quests SET ".
            (isset($pack->name)                                 ? "name                                 = '".addslashes($pack->name)."', "                                 : "").
            (isset($pack->description)                          ? "description                          = '".addslashes($pack->description)."', "                          : "").
            (isset($pack->active_icon_media_id)                 ? "active_icon_media_id                 = '".addslashes($pack->active_icon_media_id)."', "                 : "").
            (isset($pack->active_media_id)                      ? "active_media_id                      = '".addslashes($pack->active_media_id)."', "                      : "").
            (isset($pack->active_description)                   ? "active_description                   = '".addslashes($pack->active_description)."', "                   : "").
            (isset($pack->active_notification_type)             ? "active_notification_type             = '".addslashes($pack->active_notification_type)."', "             : "").
            (isset($pack->active_function)                      ? "active_function                      = '".addslashes($pack->active_function)."', "                      : "").
            (isset($pack->active_requirement_root_package_id)   ? "active_requirement_root_package_id   = '".addslashes($pack->active_requirement_root_package_id)."', "   : "").
            (isset($pack->active_event_package_id)              ? "active_event_package_id              = '".addslashes($pack->active_event_package_id)."', "              : "").
            (isset($pack->complete_icon_media_id)               ? "complete_icon_media_id               = '".addslashes($pack->complete_icon_media_id)."', "               : "").
            (isset($pack->complete_media_id)                    ? "complete_media_id                    = '".addslashes($pack->complete_media_id)."', "                    : "").
            (isset($pack->complete_description)                 ? "complete_description                 = '".addslashes($pack->complete_description)."', "                 : "").
            (isset($pack->complete_notification_type)           ? "complete_notification_type           = '".addslashes($pack->complete_notification_type)."', "           : "").
            (isset($pack->complete_function)                    ? "complete_function                    = '".addslashes($pack->complete_function)."', "                    : "").
            (isset($pack->complete_requirement_root_package_id) ? "complete_requirement_root_package_id = '".addslashes($pack->complete_requirement_root_package_id)."', " : "").
            (isset($pack->complete_event_package_id)            ? "complete_event_package_id            = '".addslashes($pack->complete_event_package_id)."', "            : "").
            (isset($pack->sort_index)                           ? "sort_index                           = '".addslashes($pack->sort_index)."', "                           : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE quest_id = '{$pack->quest_id}'"
        );

        return quests::getQuest($pack);
    }

    private static function questObjectFromSQL($sql_quest)
    {
        if(!$sql_quest) return $sql_quest;
        $quest = new stdClass();
        $quest->quest_id                             = $sql_quest->quest_id;
        $quest->game_id                              = $sql_quest->game_id;
        $quest->name                                 = $sql_quest->name;
        $quest->description                          = $sql_quest->description;
        $quest->active_icon_media_id                 = $sql_quest->active_icon_media_id;
        $quest->active_media_id                      = $sql_quest->active_media_id;
        $quest->active_description                   = $sql_quest->active_description;
        $quest->active_notification_type             = $sql_quest->active_notification_type;
        $quest->active_function                      = $sql_quest->active_function;
        $quest->active_requirement_root_package_id   = $sql_quest->active_requirement_root_package_id;
        $quest->active_event_package_id              = $sql_quest->active_event_package_id;
        $quest->complete_icon_media_id               = $sql_quest->complete_icon_media_id;
        $quest->complete_media_id                    = $sql_quest->complete_media_id;
        $quest->complete_description                 = $sql_quest->complete_description;
        $quest->complete_notification_type           = $sql_quest->complete_notification_type;
        $quest->complete_function                    = $sql_quest->complete_function;
        $quest->complete_requirement_root_package_id = $sql_quest->complete_requirement_root_package_id;
        $quest->complete_event_package_id            = $sql_quest->complete_event_package_id;
        $quest->sort_index                           = $sql_quest->sort_index;

        return $quest;
    }

    public static function getQuest($pack)
    {
        $sql_quest = dbconnection::queryObject("SELECT * FROM quests WHERE quest_id = '{$pack->quest_id}' LIMIT 1");
        if(!$sql_quest) return new return_package(2, NULL, "The quest you've requested does not exist");
        return new return_package(0,quests::questObjectFromSQL($sql_quest));
    }

    public static function getQuestsForGame($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $sql_quests = dbconnection::queryArray("SELECT * FROM quests WHERE game_id = '{$pack->game_id}' ORDER BY sort_index");
        $quests = array();
        for($i = 0; $i < count($sql_quests); $i++)
            if($ob = quests::questObjectFromSQL($sql_quests[$i])) $quests[] = $ob;

        return new return_package(0,$quests);

    }

    public static function deleteQuest($pack)
    {
        $quest = dbconnection::queryObject("SELECT * FROM quests WHERE quest_id = '{$pack->quest_id}'");
        $pack->auth->game_id = $quest->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM quests WHERE quest_id = '{$pack->quest_id}' LIMIT 1");
        //cleanup
        $reqAtoms = dbconnection::queryArray("SELECT * FROM requirement_atoms WHERE requirement = 'PLAYER_HAS_COMPLETED_QUEST' AND content_id = '{$pack->quest_id}'");
        for($i = 0; $i < count($reqAtoms); $i++)
        {
            $pack->requirement_atom_id = $reqAtoms[$i]->requirement_atom_id;
            requirements::deleteRequirementAtom($pack);
        }

        $eventpack = dbconnection::queryObject("SELECT * FROM event_packages WHERE event_package_id = '{$quest->active_event_package_id}'");
        if($eventpack)
        {
            $pack->event_package_id = $eventpack->event_package_id;
            events::deleteEventPackage($pack);
        }
        $eventpack = dbconnection::queryObject("SELECT * FROM event_packages WHERE event_package_id = '{$quest->complete_event_package_id}'");
        if($eventpack)
        {
            $pack->event_package_id = $eventpack->event_package_id;
            events::deleteEventPackage($pack);
        }

        $reqPack = dbconnection::queryObject("SELECT * FROM requirement_root_packages WHERE requirement_root_package_id = '{$quest->active_requirement_root_package_id}'");
        if($reqPack)
        {
            $pack->requirement_root_package_id = $reqPack->requirement_root_package_id;
            requirements::deleteRequirementPackage($pack);
        }
        $reqPack = dbconnection::queryObject("SELECT * FROM requirement_root_packages WHERE requirement_root_package_id = '{$quest->complete_requirement_root_package_id}'");
        if($reqPack)
        {
            $pack->requirement_root_package_id = $reqPack->requirement_root_package_id;
            requirements::deleteRequirementPackage($pack);
        }

        return new return_package(0);
    }
}
?>
