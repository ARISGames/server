<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("games.php");
require_once("return_package.php");

require_once("events.php");
require_once("events.php");
require_once("requirements.php");

class quests extends dbconnection
{
    public static function createStemportsQuest($pack)
    {
        $game_id = intval($pack->game_id);
        if($game_id <= 0) return new return_package(6, NULL, "Invalid game ID");
        $pack->auth->game_id = $game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        // replace a quest if it already has a valid quest ID
        $existing_quest_id = intval($pack->quest_id);
        $matching_quests = dbconnection::queryArray("SELECT * FROM quests WHERE quest_id = '{$existing_quest_id}' AND game_id = '{$game_id}'");
        if (empty($matching_quests) || !$pack->do_update) {
            $existing_quest_id = null;
        }

        $quest_id = dbconnection::queryInsert(
            "INSERT INTO quests (".
            "game_id,".
            (isset($pack->name)                                 ? "name,"                                 : "").
            (isset($pack->description)                          ? "description,"                          : "").
            (isset($pack->prompt)                               ? "prompt,"                               : "").
            (isset($pack->tutorial_1_title)                     ? "tutorial_1_title,"                     : "").
            (isset($pack->tutorial_1)                           ? "tutorial_1,"                           : "").
            (isset($pack->tutorial_1_media_id)                  ? "tutorial_1_media_id,"                  : "").
            (isset($pack->tutorial_2_title)                     ? "tutorial_2_title,"                     : "").
            (isset($pack->tutorial_2)                           ? "tutorial_2,"                           : "").
            (isset($pack->tutorial_2_media_id)                  ? "tutorial_2_media_id,"                  : "").
            (isset($pack->tutorial_3_title)                     ? "tutorial_3_title,"                     : "").
            (isset($pack->tutorial_3)                           ? "tutorial_3,"                           : "").
            (isset($pack->tutorial_3_media_id)                  ? "tutorial_3_media_id,"                  : "").
            (isset($pack->active_icon_media_id)                 ? "active_icon_media_id,"                 : "").
            "quest_type,".
            "created".
            ") VALUES (".
            "'".$game_id."',".
            (isset($pack->name)                                 ? "'".addslashes($pack->name)."',"                                 : "").
            (isset($pack->description)                          ? "'".addslashes($pack->description)."',"                          : "").
            (isset($pack->prompt)                               ? "'".addslashes($pack->prompt)."',"                               : "").
            (isset($pack->tutorial_1_title)                     ? "'".addslashes($pack->tutorial_1_title)."',"                     : "").
            (isset($pack->tutorial_1)                           ? "'".addslashes($pack->tutorial_1)."',"                           : "").
            (isset($pack->tutorial_1_media_id)                  ? "'".addslashes($pack->tutorial_1_media_id)."',"                  : "").
            (isset($pack->tutorial_2_title)                     ? "'".addslashes($pack->tutorial_2_title)."',"                     : "").
            (isset($pack->tutorial_2)                           ? "'".addslashes($pack->tutorial_2)."',"                           : "").
            (isset($pack->tutorial_2_media_id)                  ? "'".addslashes($pack->tutorial_2_media_id)."',"                  : "").
            (isset($pack->tutorial_3_title)                     ? "'".addslashes($pack->tutorial_3_title)."',"                     : "").
            (isset($pack->tutorial_3)                           ? "'".addslashes($pack->tutorial_3)."',"                           : "").
            (isset($pack->tutorial_3_media_id)                  ? "'".addslashes($pack->tutorial_3_media_id)."',"                  : "").
            (isset($pack->active_icon_media_id)                 ? "'".addslashes($pack->active_icon_media_id)."',"                 : "").
            "'COMPOUND',".
            "CURRENT_TIMESTAMP".
            ")"
        );

        $plaques = (isset($pack->plaques) ? $pack->plaques : array());
        $pickup_mapping = array(); // mapping from temporary field note id to event package id
        foreach ($plaques as $plaque) {
            $event_package_id = dbconnection::queryInsert(
                "INSERT INTO event_packages (".
                "game_id,".
                "created".
                ") VALUES (".
                "'".$game_id."',".
                "CURRENT_TIMESTAMP".
                ")"
            );
            $fieldNotes = (isset($plaque->fieldNotes) ? $plaque->fieldNotes : array());
            foreach ($fieldNotes as $fieldNote) {
                $pickup_mapping[$fieldNote] = $event_package_id;
            }
            $plaque_id = dbconnection::queryInsert(
                "INSERT INTO plaques (".
                "game_id,".
                (isset($plaque->name)                ? "name,"                : "").
                (isset($plaque->description)         ? "description,"         : "").
                (isset($plaque->media_id)            ? "media_id,"            : "").
                "event_package_id,".
                "quest_id,".
                "created".
                ") VALUES (".
                "'".addslashes($pack->game_id)."',".
                (isset($plaque->name)                ? "'".addslashes($plaque->name)."',"        : "").
                (isset($plaque->description)         ? "'".addslashes($plaque->description)."'," : "").
                (isset($plaque->media_id)            ? intval($plaque->media_id).","             : "").
                "'".intval($event_package_id)."',".
                "'".intval($quest_id)."',".
                "CURRENT_TIMESTAMP".
                ")"
            );
            $instance_id = dbconnection::queryInsert(
                "INSERT INTO instances (".
                "game_id,".
                "object_type,".
                "object_id,".
                "qty,".
                "infinite_qty,".
                "created".
                ") VALUES (".
                "'".$game_id."',".
                "'PLAQUE',".
                "'".intval($plaque_id)."',".
                "1,".
                "1,".
                "CURRENT_TIMESTAMP".
                ")"
            );
            $trigger_id = dbconnection::queryInsert(
                "INSERT INTO triggers (".
                "game_id,".
                "instance_id,".
                "type,".
                (isset($plaque->latitude)  ? "latitude,"  : "").
                (isset($plaque->longitude) ? "longitude," : "").
                "created".
                ") VALUES (".
                "'".$game_id."',".
                "'".intval($instance_id)."',".
                "'LOCATION',".
                (isset($plaque->latitude)  ? "'".addslashes($plaque->latitude)."',"  : "").
                (isset($plaque->longitude) ? "'".addslashes($plaque->longitude)."'," : "").
                "CURRENT_TIMESTAMP".
                ")"
            );
        }

        $fields = (isset($pack->fields) ? $pack->fields : array());
        foreach ($fields as $field) {
            $field_id = dbconnection::queryInsert
                ( "INSERT INTO fields (game_id, field_type, label, required, quest_id, instruction) VALUES ("
                .          $game_id
                . ",\""  . addslashes($field->field_type) . "\""
                . ",\""  . addslashes($field->label) . "\""
                . ","    . ($field->required ? 1 : 0)
                . ","    . intval($quest_id)
                . ",\""  . addslashes($field->instruction) . "\""
                . ")"
                );
            if (isset($field->options)) {
                $collectFieldNotes = !(isset($field->noFieldNote) && $field->noFieldNote);
                foreach ($field->options as $option) {
                    if ($collectFieldNotes) {
                        $item_id = dbconnection::queryInsert
                            ( "INSERT INTO items (game_id, name, description, media_id, icon_media_id) VALUES ("
                            .         $game_id
                            . ",\"" . addslashes($option->option) . "\""
                            . ",\"" . addslashes($option->description) . "\""
                            . ","   . intval($option->media_id)
                            . ","   . intval($option->media_id)
                            . ")"
                            );
                    } else {
                        $item_id = 0;
                    }
                    $option_id = dbconnection::queryInsert
                        ( "INSERT INTO field_options (field_id, game_id, `option`, color, remnant_id) VALUES ("
                        .         intval($field_id)
                        . ","   . $game_id
                        . ",\"" . addslashes($option->option) . "\""
                        . ",\"#000000\""
                        . ","   . intval($item_id)
                        . ")"
                        );
                    if ($collectFieldNotes && isset($pickup_mapping[$option->field_option_id])) {
                        $event_package_id = $pickup_mapping[$option->field_option_id];
                        $event_id = dbconnection::queryInsert(
                            "INSERT INTO events (".
                            "game_id,".
                            "event_package_id,".
                            "event,".
                            "qty,".
                            "content_id,".
                            "created".
                            ") VALUES (".
                            "'".$game_id."',".
                            "'".intval($event_package_id)."',".
                            "'GIVE_ITEM_PLAYER',".
                            "1,".
                            "'".intval($item_id)."',".
                            "CURRENT_TIMESTAMP".
                            ")"
                        );
                    }
                }
                if ($collectFieldNotes) {
                    $guide_id = dbconnection::queryInsert
                        ( "INSERT INTO field_guides (game_id, quest_id, field_id) VALUES ("
                        .       $game_id
                        . "," . intval($quest_id)
                        . "," . intval($field_id)
                        . ")"
                        );
                }
            }
        }

        if ($existing_quest_id) {
            // finish the update by removing the old quest stuff and replacing our new rows' quest_id
            $tables = array('quests', 'plaques', 'fields', 'field_guides');
            foreach ($tables as $table) {
                dbconnection::query("DELETE FROM ${table} WHERE quest_id = '${existing_quest_id}'");
                dbconnection::query("UPDATE ${table} SET quest_id = '${existing_quest_id}' WHERE quest_id = '${quest_id}'");
            }
        }

        games::bumpGameVersion($pack);
        return new return_package(0);
    }

    //Takes in quest JSON, all fields optional except user_id + key
    public static function createQuest($pack)
    {
        if(intval($pack->game_id) <= 0) return new return_package(6, NULL, "Invalid game ID");
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->quest_id = dbconnection::queryInsert(
            "INSERT INTO quests (".
            "game_id,".
            (isset($pack->name)                                 ? "name,"                                 : "").
            (isset($pack->description)                          ? "description,"                          : "").
            (isset($pack->prompt)                               ? "prompt,"                               : "").
            (isset($pack->tutorial_1_title)                     ? "tutorial_1_title,"                     : "").
            (isset($pack->tutorial_1)                           ? "tutorial_1,"                           : "").
            (isset($pack->tutorial_1_media_id)                  ? "tutorial_1_media_id,"                  : "").
            (isset($pack->tutorial_2_title)                     ? "tutorial_2_title,"                     : "").
            (isset($pack->tutorial_2)                           ? "tutorial_2,"                           : "").
            (isset($pack->tutorial_2_media_id)                  ? "tutorial_2_media_id,"                  : "").
            (isset($pack->tutorial_3_title)                     ? "tutorial_3_title,"                     : "").
            (isset($pack->tutorial_3)                           ? "tutorial_3,"                           : "").
            (isset($pack->tutorial_3_media_id)                  ? "tutorial_3_media_id,"                  : "").
            (isset($pack->stars)                                ? "stars,"                                : "").
            (isset($pack->quest_type)                           ? "quest_type,"                           : "").
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
            (isset($pack->parent_quest_id)                      ? "parent_quest_id,"                      : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->name)                                 ? "'".addslashes($pack->name)."',"                                 : "").
            (isset($pack->description)                          ? "'".addslashes($pack->description)."',"                          : "").
            (isset($pack->prompt)                               ? "'".addslashes($pack->prompt)."',"                               : "").
            (isset($pack->tutorial_1_title)                     ? "'".addslashes($pack->tutorial_1_title)."',"                     : "").
            (isset($pack->tutorial_1)                           ? "'".addslashes($pack->tutorial_1)."',"                           : "").
            (isset($pack->tutorial_1_media_id)                  ? "'".addslashes($pack->tutorial_1_media_id)."',"                  : "").
            (isset($pack->tutorial_2_title)                     ? "'".addslashes($pack->tutorial_2_title)."',"                     : "").
            (isset($pack->tutorial_2)                           ? "'".addslashes($pack->tutorial_2)."',"                           : "").
            (isset($pack->tutorial_2_media_id)                  ? "'".addslashes($pack->tutorial_2_media_id)."',"                  : "").
            (isset($pack->tutorial_3_title)                     ? "'".addslashes($pack->tutorial_3_title)."',"                     : "").
            (isset($pack->tutorial_3)                           ? "'".addslashes($pack->tutorial_3)."',"                           : "").
            (isset($pack->tutorial_3_media_id)                  ? "'".addslashes($pack->tutorial_3_media_id)."',"                  : "").
            (isset($pack->stars)                                ? "'".addslashes($pack->stars)."',"                                : "").
            (isset($pack->quest_type)                           ? "'".addslashes($pack->quest_type)."',"                           : "").
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
            (isset($pack->parent_quest_id)                      ? "'".addslashes($pack->parent_quest_id)."',"                      : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        games::bumpGameVersion($pack);
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
            (isset($pack->prompt)                               ? "prompt                               = '".addslashes($pack->prompt)."', "                               : "").
            (isset($pack->tutorial_1_title)                     ? "tutorial_1_title                     = '".addslashes($pack->tutorial_1_title)."', "                     : "").
            (isset($pack->tutorial_1)                           ? "tutorial_1                           = '".addslashes($pack->tutorial_1)."', "                           : "").
            (isset($pack->tutorial_1_media_id)                  ? "tutorial_1_media_id                  = '".addslashes($pack->tutorial_1_media_id)."', "                  : "").
            (isset($pack->tutorial_2_title)                     ? "tutorial_2_title                     = '".addslashes($pack->tutorial_2_title)."', "                     : "").
            (isset($pack->tutorial_2)                           ? "tutorial_2                           = '".addslashes($pack->tutorial_2)."', "                           : "").
            (isset($pack->tutorial_2_media_id)                  ? "tutorial_2_media_id                  = '".addslashes($pack->tutorial_2_media_id)."', "                  : "").
            (isset($pack->tutorial_3_title)                     ? "tutorial_3_title                     = '".addslashes($pack->tutorial_3_title)."', "                     : "").
            (isset($pack->tutorial_3)                           ? "tutorial_3                           = '".addslashes($pack->tutorial_3)."', "                           : "").
            (isset($pack->tutorial_3_media_id)                  ? "tutorial_3_media_id                  = '".addslashes($pack->tutorial_3_media_id)."', "                  : "").
            (isset($pack->stars)                                ? "stars                                = '".addslashes($pack->stars)."', "                                : "").
            (isset($pack->quest_type)                           ? "quest_type                           = '".addslashes($pack->quest_type)."', "                           : "").
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
            (isset($pack->parent_quest_id)                      ? "parent_quest_id                      = '".addslashes($pack->parent_quest_id)."', "                      : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE quest_id = '{$pack->quest_id}'"
        );

        games::bumpGameVersion($pack);
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
        $quest->prompt                               = $sql_quest->prompt;
        $quest->tutorial_1_title                     = $sql_quest->tutorial_1_title;
        $quest->tutorial_1                           = $sql_quest->tutorial_1;
        $quest->tutorial_1_media_id                  = $sql_quest->tutorial_1_media_id;
        $quest->tutorial_2_title                     = $sql_quest->tutorial_2_title;
        $quest->tutorial_2                           = $sql_quest->tutorial_2;
        $quest->tutorial_2_media_id                  = $sql_quest->tutorial_2_media_id;
        $quest->tutorial_3_title                     = $sql_quest->tutorial_3_title;
        $quest->tutorial_3                           = $sql_quest->tutorial_3;
        $quest->tutorial_3_media_id                  = $sql_quest->tutorial_3_media_id;
        $quest->stars                                = $sql_quest->stars;
        $quest->quest_type                           = $sql_quest->quest_type;
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
        $quest->parent_quest_id                      = $sql_quest->parent_quest_id;

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
        $reqAtoms = dbconnection::queryArray("SELECT * FROM requirement_atoms WHERE requirement IN ('PLAYER_HAS_COMPLETED_QUEST', 'PLAYER_HAS_QUEST_STARS') AND content_id = '{$pack->quest_id}'");
        for($i = 0; $i < count($reqAtoms); $i++)
        {
            $pack->requirement_atom_id = $reqAtoms[$i]->requirement_atom_id;
            requirements::deleteRequirementAtom($pack);
        }

        /* Comment out until we've decided on desired behavior...
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
        */

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

        games::bumpGameVersion($pack);
        return new return_package(0);
    }
}
?>
