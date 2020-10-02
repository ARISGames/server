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
            (isset($pack->stars)                                ? "stars,"                                : "").
            (isset($pack->published)                            ? "published,"                            : "").
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
            (isset($pack->stars)                                ? "'".addslashes($pack->stars)."',"                                : "").
            (isset($pack->published)                            ? "'".addslashes($pack->published)."',"                            : "").
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
                (isset($plaque->media_id_2)          ? "media_id_2,"          : "").
                (isset($plaque->media_id_3)          ? "media_id_3,"          : "").
                "event_package_id,".
                "quest_id,".
                "created".
                ") VALUES (".
                "'".addslashes($pack->game_id)."',".
                (isset($plaque->name)                ? "'".addslashes($plaque->name)."',"        : "").
                (isset($plaque->description)         ? "'".addslashes($plaque->description)."'," : "").
                (isset($plaque->media_id)            ? intval($plaque->media_id).","             : "").
                (isset($plaque->media_id_2)          ? intval($plaque->media_id_2).","           : "").
                (isset($plaque->media_id_3)          ? intval($plaque->media_id_3).","           : "").
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
        $temp_option_to_item_mapping = array(); // mapping from temporary field note id to created item id
        foreach ($fields as $field) {
            $field_id = dbconnection::queryInsert
                ( "INSERT INTO fields (game_id, field_type, label, required, quest_id, instruction, sort_index) VALUES ("
                .          $game_id
                . ",\""  . addslashes($field->field_type) . "\""
                . ",\""  . addslashes($field->label) . "\""
                . ","    . ($field->required ? 1 : 0)
                . ","    . intval($quest_id)
                . ",\""  . addslashes($field->instruction) . "\""
                . ","    . intval($field->sort_index)
                . ")"
                );
            if (isset($field->options)) {
                $collectFieldNotes = !(isset($field->noFieldNote) && $field->noFieldNote);
                foreach ($field->options as $option) {
                    if ($collectFieldNotes) {
                        $item_id = dbconnection::queryInsert
                            ( "INSERT INTO items (game_id, name, description, media_id, icon_media_id, media_id_2, media_id_3) VALUES ("
                            .         $game_id
                            . ",\"" . addslashes($option->option) . "\""
                            . ",\"" . addslashes($option->description) . "\""
                            . ","   . intval($option->media_id)
                            . ","   . intval($option->media_id)
                            . ","   . intval($option->media_id_2)
                            . ","   . intval($option->media_id_3)
                            . ")"
                            );
                    } else {
                        $item_id = 0;
                    }
                    $temp_option_to_item_mapping[$option->field_option_id] = $item_id;
                    $option_id = dbconnection::queryInsert
                        ( "INSERT INTO field_options (field_id, game_id, `option`, color, remnant_id, sort_index) VALUES ("
                        .         intval($field_id)
                        . ","   . $game_id
                        . ",\"" . addslashes($option->option) . "\""
                        . ",\"#000000\""
                        . ","   . intval($item_id)
                        . ","   . intval($option->sort_index)
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

        // manually placed caches, just triggers for items
        $caches = (isset($pack->caches) ? $pack->caches : array());
        foreach ($caches as $cache) {
            // $cache has latitude, longitude, field_option_id
            $item_id = $temp_option_to_item_mapping[$cache->field_option_id];
            // make req (root, and, atom) for player to not have the item already
            $root_id = dbconnection::queryInsert
                ( "INSERT INTO requirement_root_packages (game_id) VALUES ({$game_id})"
                );
            $and_id = dbconnection::queryInsert
                ( "INSERT INTO requirement_and_packages (game_id, requirement_root_package_id) VALUES ("
                .       $game_id
                . "," . intval($root_id)
                . ")"
                );
            dbconnection::queryInsert
                ( "INSERT INTO requirement_atoms (game_id, requirement_and_package_id, bool_operator, requirement, content_id, qty) VALUES ("
                .       $game_id
                . "," . intval($and_id)
                . "," . "0"
                . "," . "'PLAYER_HAS_ITEM'"
                . "," . $item_id
                . "," . "1"
                . ")"
                );
            // make instance (point to item) and trigger
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
                "'ITEM',".
                "'".intval($item_id)."',".
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
                (isset($cache->latitude)  ? "latitude,"  : "").
                (isset($cache->longitude) ? "longitude," : "").
                "requirement_root_package_id,".
                "created".
                ") VALUES (".
                "'".$game_id."',".
                "'".intval($instance_id)."',".
                "'LOCATION',".
                (isset($cache->latitude)  ? "'".addslashes($cache->latitude)."',"  : "").
                (isset($cache->longitude) ? "'".addslashes($cache->longitude)."'," : "").
                "'".$root_id."',".
                "CURRENT_TIMESTAMP".
                ")"
            );
        }

        if ($existing_quest_id) {
            // finish the update by removing the old quest stuff and replacing our new rows' quest_id
            $tables = array('quests', 'plaques', 'fields', 'field_guides');
            foreach ($tables as $table) {
                if ($table === 'fields') {
                    dbconnection::query("DELETE fields, field_options, items, instances, triggers, requirement_root_packages, requirement_and_packages, requirement_atoms
                        FROM fields
                        LEFT JOIN field_options ON field_options.field_id = fields.field_id
                        LEFT JOIN items ON field_options.remnant_id = items.item_id
                        LEFT JOIN instances ON instances.object_type = 'ITEM' AND instances.object_id = items.item_id
                        LEFT JOIN triggers ON instances.instance_id = triggers.instance_id
                        LEFT JOIN requirement_root_packages ON triggers.requirement_root_package_id = requirement_root_packages.requirement_root_package_id
                        LEFT JOIN requirement_and_packages ON requirement_root_packages.requirement_root_package_id = requirement_and_packages.requirement_root_package_id
                        LEFT JOIN requirement_atoms ON requirement_and_packages.requirement_and_package_id = requirement_atoms.requirement_and_package_id
                        WHERE fields.quest_id = '${existing_quest_id}'"
                    );
                } else if ($table === 'plaques') {
                    dbconnection::query("DELETE plaques, instances, triggers, event_packages, events
                        FROM plaques
                        LEFT JOIN instances ON instances.object_type = 'PLAQUE' AND instances.object_id = plaques.plaque_id
                        LEFT JOIN triggers ON instances.instance_id = triggers.instance_id
                        LEFT JOIN event_packages ON event_packages.event_package_id = plaques.event_package_id
                        LEFT JOIN events ON events.event_package_id = event_packages.event_package_id
                        WHERE plaques.quest_id = '${existing_quest_id}'"
                    );
                } else {
                    dbconnection::query("DELETE FROM ${table} WHERE quest_id = '${existing_quest_id}'");
                }
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
            (isset($pack->published)                            ? "published,"                            : "").
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
            (isset($pack->published)                            ? "'".addslashes($pack->published)."',"                            : "").
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
            (isset($pack->published)                            ? "published                            = '".addslashes($pack->published)."', "                            : "").
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
        $quest->published                            = $sql_quest->published;
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
        $user_id = null;
        if ($path->auth) {
            $pack->auth->permission = "read_write";
            if (users::authenticateUser($pack->auth)) {
                $user_id = intval($pack->auth->user_id);
            }
        }

        $game_id = intval($pack->game_id);

        if ($user_id === 75) {
            // stemports master editor account
            $sql_quests = dbconnection::queryArray("
                SELECT * FROM quests
                WHERE game_id = '{$game_id}'
                ORDER BY sort_index
            ");
        } else if (!is_null($user_id)) {
            // authenticated user, they can see their private quests
            $sql_quests = dbconnection::queryArray("
                SELECT quests.* FROM quests
                LEFT JOIN user_games ON quests.game_id = user_games.game_id AND user_games.user_id = '{$user_id}'
                WHERE quests.game_id = '{$game_id}'
                AND (quests.published OR user_games.user_id IS NOT NULL)
                GROUP BY quests.quest_id
                ORDER BY quests.sort_index
            ");
        } else {
            // only public quests
            $sql_quests = dbconnection::queryArray("
                SELECT * FROM quests
                WHERE game_id = '{$game_id}'
                AND published
                ORDER BY sort_index
            ");
        }

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
