<?php
require_once("module.php");

class Requirements extends Module
{	
    /*
    example requirementPackage JSON. used as inputs and outputs for requirements API

    {
        "game_id":123,
        "requirement_root_package_id":321,
        "name":"requirementPackageName",
        "and_packages": 
            [
                {
                    "requirement_and_package_id":231,
                    "name":"andPackageName",
                    "atoms":
                        [
                            {
                                "requirement_atom_id":132,
                                "bool_operator":0,
                                "requirement":"PLAYER_HAS_ITEM",
                                "content_id":42,
                                "qty":4,
                                "latitude":86.75309,
                                "longitude":3.141592
                            },
                            ...
                        ]
                },
                ...
            ]
    }
    */

    //Takes in requirementPackage JSON, all fields optional except game_id.
    //all individual ids (requirement_root_package_id, etc...) ignored if present ( = easy duplication)
    public function createRequirementPackageJSON($glob)
    {
	$data = file_get_contents("php://input");
        $glob = json_decode($data);
        return Requirements::createRequirementPackage($glob);
    }
    public function createRequirementPackage($pack)
    {
        if(!$pack || !$pack->game_id) return "nope";

        Module::query(
            "INSERT INTO requirement_root_packages (".
            "game_id,".
            ($pack->name ? "name," : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            ($pack->name ? "'".addslashes($pack->name)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );
        $requirementPackageId = mysql_insert_id();

        for($i = 0; $pack->and_packages && $i < count($pack->and_packages); $i++)
        {
            $pack->and_packages[$i]->requirement_root_package_id = $requirementPackageId;
            $pack->and_packages[$i]->game_id = $pack->game_id;
            Requirements::createRequirementAndPackage($pack->and_packages[$i]);
        }

        return Requirements::getRequirementPackage($requirementPackageId);
    }

    //requires game_id and requirement_root_package_id
    public function createRequirementAndPackage($pack)
    {
        if(!$pack || !$pack->game_id || !$pack->requirement_root_package_id) return;

        Module::query(
            "INSERT INTO requirement_and_packages (".
            "game_id,".
            "requirement_root_package_id,".
            ($pack->name ? "name," : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            "'".addslashes($pack->requirement_root_package_id)."',".
            ($pack->name ? "'".addslashes($pack->name)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );
        $requirementAndPackageId = mysql_insert_id();

        for($i = 0; $pack->atoms && $i < count($pack->atoms); $i++)
        {
            $pack->atoms[$i]->requirement_and_package_id = $requirementAndPackageId;
            $pack->atoms[$i]->game_id = $pack->game_id;
            Requirements::createRequirementAtom($pack->atoms[$i]);
        }
    }

    //requires game_id and requirement_and_package_id
    public function createRequirementAtom($pack)
    {
        if(!$pack || !$pack->game_id || !$pack->requirement_and_package_id) return;

        Module::query(
            "INSERT INTO requirement_atoms (".
            "game_id,".
            "requirement_and_package_id,".
            ($pack->bool_operator ? "bool_operator," : "").
            ($pack->requirement   ? "requirement,"   : "").
            ($pack->content_id    ? "content_id,"    : "").
            ($pack->qty           ? "qty,"           : "").
            ($pack->latitude      ? "latitude,"      : "").
            ($pack->longitude     ? "longitude,"     : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            "'".addslashes($pack->requirement_and_package_id)."',".
            ($pack->bool_operator ? "'".addslashes($pack->bool_operator)."'," : "").
            ($pack->requirement   ? "'".addslashes($pack->requirement  )."'," : "").
            ($pack->content_id    ? "'".addslashes($pack->content_id   )."'," : "").
            ($pack->qty           ? "'".addslashes($pack->qty          )."'," : "").
            ($pack->latitude      ? "'".addslashes($pack->latitude     )."'," : "").
            ($pack->longitude     ? "'".addslashes($pack->longitude    )."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );
    }

    public function updateRequirementPackageJSON($glob)
    {
	$data = file_get_contents("php://input");
        $glob = json_decode($data);
        return Requirements::updateRequirementPackage($glob);
    }

    public function updateRequirementPackage($pack)
    {
        if(!$pack || !$pack->game_id || !$pack->requirement_root_package_id) return;

        Module::query(
            "UPDATE requirement_root_packages SET ".
            "game_id = '".addslashes($pack->game_id)."'".
            ($pack->name ? ", name = '".addslashes($pack->name)."'" : "").
            " WHERE requirement_root_package_id = '".addslashes($pack->requirement_root_package_id)."'"
        );

        $sql_currentAndPacks = Module::queryArray("SELECT * FROM requirement_and_packages WHERE requirement_root_package_id = '{$pack->requirement_root_package_id}'");
        for($i = 0; $i < count($sql_currentAndPacks); $i++)
        {
            $matchingAndPack = null;
            for($j = 0; $pack->and_packages && $j < count($pack->and_packages); $j++)
            {
                if($sql_currentAndPacks[$i]->requirement_and_package_id == $pack->and_packages[$j]->requirement_and_package_id)
                {
                    $matchingAndPack = $pack->and_packages[$j];
                    //remove from array so I can just add all remaining later
                    array_splice($pack->and_packages, $j, 1);
                    $j--;
                }
            }
            if($matchingAndPack)
            {
                $matchingAndPack->requirement_root_package_id = $pack->requirement_root_package_id;
                $matchingAndPack->game_id = $pack->game_id;
                Requirements::updateRequirementAndPackage($matchingAndPack);
            }
            else
                Requirements::deleteRequirementAndPackage($sql_currentAndPacks[$i]->requirement_and_package_id);
        }
        for($i = 0; $pack->and_packages && $i < count($pack->and_packages); $i++)
        {
            $pack->and_packages[$i]->requirement_root_package_id = $pack->requirement_root_package_id;
            $pack->and_packages[$i]->game_id = $pack->game_id;
            Requirements::createRequirementAndPackage($pack->and_packages[$i]);
        }

        return Requirements::getRequirementPackage($pack->requirement_root_package_id);
    }

    public function updateRequirementAndPackage($pack)
    {
        if(!$pack || !$pack->game_id || !$pack->requirement_and_package_id) return;

        Module::query(
            "UPDATE requirement_and_packages SET ".
            "game_id = '".addslashes($pack->game_id)."'".
            ($pack->name ? ", name = '".addslashes($pack->name)."'" : "").
            " WHERE requirement_and_package_id = '".addslashes($pack->requirement_and_package_id)."'"
        );

        $sql_currentAtoms = Module::queryArray("SELECT * FROM requirement_atoms WHERE requirement_and_package_id = '{$pack->requirement_and_package_id}'");
        for($i = 0; $i < count($sql_currentAtoms); $i++)
        {
            $matchingAtom = null;
            for($j = 0; $pack->atoms && $j < count($pack->atoms); $j++)
            {
                if($sql_currentAtoms[$i]->requirement_atom_id == $pack->atoms[$j]->requirement_atom_id)
                {
                    $matchingAtom = $pack->atoms[$j];
                    //remove from array so I can just add all remaining later
                    array_splice($pack->atoms, $j, 1);
                    $j--;
                }
            }
            if($matchingAtom)
            {
                $matchingAtom->requirement_atom_id = $pack->atoms[$j]->requirement_atom_id;
                $matchingAtom->game_id = $pack->game_id;
                Requirements::updateRequirementAtom($matchingAtom);
            }
            else
                Requirements::deleteRequirementAtom($sql_currentAtoms[$i]->requirement_atom_id);
        }
        for($i = 0; $pack->atoms && $i < count($pack->atoms); $i++)
        {
            $pack->atoms[$i]->requirement_atom_id = $pack->atoms[$j]->requirement_atom_id;
            $pack->atoms[$i]->game_id = $pack->game_id;
            Requirements::createRequirementAtom($pack->atoms[$i]);
        }
    }

    public function updateRequirementAtom($pack)
    {
        if(!$pack || !$pack->game_id || !$pack->requirement_atom_id) return;

        Module::query(
            "UPDATE requirement_atoms SET ".
            "game_id = '".addslashes($pack->game_id)."'".
            ($pack->bool_operator ? ", bool_operator = '".addslashes($pack->bool_operator)."'" : "").
            ($pack->requirement   ? ", requirement   = '".addslashes($pack->requirement  )."'" : "").
            ($pack->content_id    ? ", content_id    = '".addslashes($pack->content_id   )."'" : "").
            ($pack->qty           ? ", qty           = '".addslashes($pack->qty          )."'" : "").
            ($pack->latitude      ? ", latitude      = '".addslashes($pack->latitude     )."'" : "").
            ($pack->longitude     ? ", longitude     = '".addslashes($pack->longitude    )."'" : "").
            " WHERE requirement_atom_id = '".addslashes($pack->requirement_atom_id)."'"
        );
    }


    public function getRequirementPackage($requirementPackageId)
    {
        $pack = new stdClass();

        $sql_root = Module::queryObject("SELECT * FROM requirement_root_packages WHERE requirement_root_package_id = '{$requirementPackageId}'");
        $pack->requirement_root_package_id = $sql_root->requirement_root_package_id;
        $pack->game_id = $sql_root->game_id;
        $pack->name = $sql_root->name;

        $sql_andPacks = Module::queryArray("SELECT * FROM requirement_and_packages WHERE requirement_root_package_id = '{$requirementPackageId}'");
        $pack->and_packages = array();

        for($i = 0; $i < count($sql_andPacks); $i++)
        {
            $pack->and_packages[$i] = Requirements::getRequirementAndPackage($sql_andPacks[$i]->requirement_and_package_id);
            //makes for cleaner return object, as game_id,requirement_and_package_id is already in parent
            unset($pack->and_packages[$i]->game_id);
            unset($pack->and_packages[$i]->requirement_root_package_id);
        }

        return $pack;
    }
    public function getRequirementAndPackage($requirementAndPackageId)
    {
        $sql_andPack = Module::queryObject("SELECT * FROM requirement_and_packages WHERE requirement_and_package_id = '{$requirementAndPackageId}'");
        $andPack = new stdClass();
        $andPack->requirement_and_package_id  = $sql_andPack->requirement_and_package_id;
        $andPack->game_id                     = $sql_andPack->game_id;
        $andPack->requirement_root_package_id = $sql_andPack->requirement_root_package_id;
        $andPack->name                        = $sql_andPack->name;

        $sql_packAtoms = Module::queryArray("SELECT * FROM  requirement_atoms WHERE requirement_and_package_id = '{$sql_andPack->requirement_and_package_id}'");
        $andPack->atoms = array();
        for($i = 0; $i < count($sql_packAtoms); $i++)
        {
            $andPack->atoms[$i] = Requirements::getRequirementAtom($sql_packAtoms[$i]->requirement_atom_id);
            //makes for cleaner return object, as game_id,requirement_and_package_id is already in parent
            unset($andPack->atoms[$i]->game_id);
            unset($andPack->atoms[$i]->requirement_and_package_id);
        }

        return $andPack;
    }
    public function getRequirementAtom($requirementAtomId)
    {
        $sql_atom = Module::queryObject("SELECT * FROM requirement_atoms WHERE requirement_atom_id = '{$requirementAtomId}'");
        $atom = new stdClass();
        $atom->requirement_atom_id        = $sql_atom->requirement_atom_id;
        $atom->game_id                    = $sql_atom->game_id;
        $atom->requirement_and_package_id = $sql_atom->requirement_and_package_id;
        $atom->bool_operator              = $sql_atom->bool_operator;
        $atom->requirement                = $sql_atom->requirement;
        $atom->content_id                 = $sql_atom->content_id;
        $atom->qty                        = $sql_atom->qty;
        $atom->latitude                   = $sql_atom->latitude;
        $atom->longitude                  = $sql_atom->longitude;
        return $atom;
    }

    public function deleteRequirementPackage($requirementPackageId)
    {
        $gameId = Module::queryObject("SELECT game_id FROM requirement_root_packages WHERE requirement_root_package_id = '{$requirementPackageId}'")->game_id;
        $sql_andPacks = Module::queryArray("SELECT * FROM requirement_and_packages WHERE requirement_root_package_id = '{$requirementPackageId}'");
        for($i = 0; $i < count($sql_andPacks); $i++)
            Requirements::deleteRequirementAndPackage($sql_andPacks[$i]->requirement_and_package_id);
        Module::query("DELETE FROM requirement_root_packages WHERE requirement_root_package_id = '{$requirementPackageId}'");

        Module::query("UPDATE quests SET complete_requirement_package_id = 0 WHERE game_id = '{$gameId}' AND complete_requirement_package_id = '{$requirementPackageId}'");
        Module::query("UPDATE quests SET display_requirement_package_id = 0 WHERE game_id = '{$gameId}' AND display_requirement_package_id = '{$requirementPackageId}'");
        Module::query("UPDATE locations SET requirement_package_id = 0 WHERE game_id = '{$gameId}' AND requirement_package_id = '{$requirementPackageId}'");
        Module::query("UPDATE web_hooks SET requirement_package_id = 0 WHERE game_id = '{$gameId}' AND requirement_package_id = '{$requirementPackageId}'");
        Module::query("UPDATE overlays SET requirement_package_id = 0 WHERE game_id = '{$gameId}' AND requirement_package_id = '{$requirementPackageId}'");
    }

    public function deleteRequirementAndPackage($requirementAndPackageId)
    {
        $sql_packAtoms = Module::queryArray("SELECT * FROM requirement_atoms WHERE requirement_and_package_id = '{$requirementAndPackageId}'");
        for($i = 0; $i < count($sql_packAtoms); $i++)
            Requirements::deleteRequirementAtom($sql_packAtoms[$i]->requirement_atom_id);
        Module::query("DELETE FROM requirement_and_packages WHERE requirement_and_package_id = '{$requirementAndPackageId}'");
    }

    public function deleteRequirementAtom($requirementAtomId)
    {
        Module::query("DELETE FROM requirement_atoms WHERE requirement_atom_id = '{$requirementAtomId}'");
    }

    public function evaluateRequirementPackage($requirementPackageId, $playerId)
    {
        $andPackages = Module::queryArray("SELECT requirement_and_package_id FROM requirement_and_packages WHERE requirement_root_package_id= '{$requirementPackageId}'");

        for($i = 0; $i < count($andPackages); $i++)
            if(Requirements::evaluateRequirementAndPackage($andPackages[$i]->requirement_and_package_id, $playerId)) return true;
        return false;
    }

    public function evaluateRequirementAndPackage($requirementAndPackageId, $playerId)
    {
        $atoms = Module::queryArray("SELECT requirement_atom_id FROM requirement_atoms WHERE requirement_and_package_id= '{$requirementAndPackageId}'");

        for($i = 0; $i < count($atoms); $i++)
            if(!Requirements::evaluateRequirementAtom($atoms[$i]->requirement_atom_id, $playerId)) return false;
        return true;
    }

    public function evaluateRequirementAtom($requirementAtomId, $playerId)
    {
        $atom = Module::queryObject("SELECT * FROM requirement_atoms WHERE requirement_atom_id = '{$requirementAtomId}'");

        switch($atom->requirement)
        {
            case 'PLAYER_HAS_ITEM':                       return Module::playerHasItem($gameId, $playerId, $atom->content_id, $atom->qty); break;
            case 'PLAYER_HAS_TAGGED_ITEM':                return Module::playerHasTaggedItem($gameId, $playerId, $atom->content_id, $atom->qty); break;
            case 'PLAYER_VIEWED_ITEM':                    return Module::playerHasLog($gameId, $playerId, Module::kLOG_VIEW_ITEM, $atom->content_id); break;
            case 'PLAYER_VIEWED_NODE':                    return Module::playerHasLog($gameId, $playerId, Module::kLOG_VIEW_NODE, $atom->content_id); break;
            case 'PLAYER_VIEWED_NPC':                     return Module::playerHasLog($gameId, $playerId, Module::kLOG_VIEW_NPC, $atom->content_id); break;
            case 'PLAYER_VIEWED_WEBPAGE':                 return Module::playerHasLog($gameId, $playerId, Module::kLOG_VIEW_WEBPAGE, $atom->content_id); break;
            case 'PLAYER_VIEWED_AUGBUBBLE':               return Module::playerHasLog($gameId, $playerId, Module::kLOG_VIEW_AUGBUBBLE, $atom->content_id); break;
            case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM':        return Module::playerHasUploadedMediaItemWithinDistance($gameId, $playerId, $atom->latitude, $atom->longitude, $atom->distance, $atom->qty, Module::kLOG_UPLOAD_MEDIA_ITEM); break;
            case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE':  return Module::playerHasUploadedMediaItemWithinDistance($gameId, $playerId, $atom->latitude, $atom->longitude, $atom->distance, $atom->qty, Module::kLOG_UPLOAD_MEDIA_ITEM_IMAGE); break;
            case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO':  return Module::playerHasUploadedMediaItemWithinDistance($gameId, $playerId, $atom->latitude, $atom->longitude, $atom->distance, $atom->qty, Module::kLOG_UPLOAD_MEDIA_ITEM_AUDIO); break;
            case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO':  return Module::playerHasUploadedMediaItemWithinDistance($gameId, $playerId, $atom->latitude, $atom->longitude, $atom->distance, $atom->qty, Module::kLOG_UPLOAD_MEDIA_ITEM_VIDEO); break;
            case 'PLAYER_HAS_COMPLETED_QUEST':            return Module::playerHasLog($gameId, $playerId, Module::kLOG_COMPLETE_QUEST, $atom->content_id); break;
            case 'PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK': return Module::playerHasLog($gameId, $playerId, Module::kLOG_RECEIVE_WEBHOOK, $atom->content_id); break;
            case 'PLAYER_HAS_NOTE':                       return Module::playerHasNote($gameId, $playerId, $atom->qty); break;
            case 'PLAYER_HAS_NOTE_WITH_TAG':              return Module::playerHasNoteWithTag($gameId, $playerId, $atom->content_id, $atom->qty); break;
            case 'PLAYER_HAS_NOTE_WITH_LIKES':            return Module::playerHasNoteWithLikes($gameId, $playerId, $atom->qty); break;
            case 'PLAYER_HAS_NOTE_WITH_COMMENTS':         return Module::playerHasNoteWithComments($gameId, $playerId, $atom->qty); break;
            case 'PLAYER_HAS_GIVEN_NOTE_COMMENTS':        return Module::playerHasGivenNoteComments($gameId, $playerId, $atom->qty); break;
        }
        return false;
    }









    public function nonDestructivelyMigrateOldRequirementsToNewForGame($gameId)
    {
        $reqs = Requirements::getPackagedRequirementsForGame($gameId);
        for($i = 0; $i < count($reqs); $i++)
            Requirements::migrateReqPack($reqs[$i], $gameId);
    }
    private function getPackagedRequirementsForGame($gameId)
    {
        $nodereqs          = Requirements::getPackagedRequirementsForGameForType($gameId, 'Node');
        $questdisplayreqs  = Requirements::getPackagedRequirementsForGameForType($gameId, 'QuestDisplay');
        $questcompletereqs = Requirements::getPackagedRequirementsForGameForType($gameId, 'QuestComplete');
        $locationreqs      = Requirements::getPackagedRequirementsForGameForType($gameId, 'Location');
        $webhookreqs       = Requirements::getPackagedRequirementsForGameForType($gameId, 'OutgoingWebHook');
        $spawnablereqs     = Requirements::getPackagedRequirementsForGameForType($gameId, 'Spawnable');
        return array_merge($nodereqs, $questdisplayreqs, $questcompletereqs, $locationreqs, $webhookreqs, $spawnablereqs);
    }
    private function getPackagedRequirementsForGameForType($gameId, $type)
    {
        $ids = Module::queryArray("SELECT * FROM requirements WHERE game_id = '{$gameId}' AND content_type = '{$type}' GROUP BY content_id;");

        $reqs = array();
        for($i = 0; $i < count($ids); $i++)
            $reqs[] = Requirements::getPackagedRequirementsForGameForTypeForId($gameId, $type, $ids[$i]->content_id);

        return $reqs;
    }
    private function getPackagedRequirementsForGameForTypeForId($gameId, $type, $id)
    {
        $pack = new stdClass();
        $pack->type = $type;
        $pack->type_id = $id;
        $pack->and_reqs = Module::queryArray("SELECT * FROM requirements WHERE game_id = '{$gameId}' AND content_type = '{$type}' AND content_id = '{$id}' AND boolean_operator = 'AND'");
        $pack->or_reqs  = Module::queryArray("SELECT * FROM requirements WHERE game_id = '{$gameId}' AND content_type = '{$type}' AND content_id = '{$id}' AND boolean_operator = 'OR'");
        return $pack;
    }
    private function migrateReqPack($pack, $gameId)
    {
        Module::query("INSERT INTO requirement_root_packages (game_id, name, created) VALUES ('{$gameId}','', CURRENT_TIMESTAMP)");
        $requirement_root_id = mysql_insert_id();

        for($i = 0; $i < count($pack->or_reqs); $i++)
        {
            Module::query("INSERT INTO requirement_and_packages (game_id, requirement_root_package_id, name, created) VALUES ('{$gameId}','{$requirement_root_id}','', CURRENT_TIMESTAMP)");
            $requirement_and_id = mysql_insert_id();
            Requirements::migrateReqAtom($pack->or_reqs[$i], $gameId, $requirement_and_id);
        }
        if(count($pack->and_reqs) > 0)
        {
            Module::query("INSERT INTO requirement_and_packages (game_id, requirement_root_package_id, name, created) VALUES ('{$gameId}','{$requirement_root_id}','', CURRENT_TIMESTAMP)");
            $requirement_and_id = mysql_insert_id();
            for($i = 0; $i < count($pack->and_reqs); $i++)
                Requirements::migrateReqAtom($pack->and_reqs[$i], $gameId, $requirement_and_id);
        }

        switch($pack->type)
        {
            case "Node":
                Module::query("UPDATE nodes SET requirement_package_id = '{$requirement_root_id}' WHERE node_id = '{$pack->type_id}'");
                break;
            case "QuestDisplay":
                Module::query("UPDATE quests SET display_requirement_package_id = '{$requirement_root_id}' WHERE quest_id = '{$pack->type_id}'");
                break;
            case "QuestComplete":
                Module::query("UPDATE quests SET complete_requirement_package_id = '{$requirement_root_id}' WHERE quest_id = '{$pack->type_id}'");
                break;
            case "Location":
                Module::query("UPDATE locations SET requirement_package_id = '{$requirement_root_id}' WHERE location_id = '{$pack->type_id}'");
                break;
            case "OutgoingWebHook":
                Module::query("UPDATE web_hooks SET requirement_package_id = '{$requirement_root_id}' WHERE web_hook_id = '{$pack->type_id}'");
                break;
            case "Spawnable":
                Module::query("UPDATE spawnables SET requirement_package_id = '{$requirement_root_id}' WHERE spawnable_id = '{$pack->type_id}'");
                break;
        }
    }
    private function migrateReqAtom($atom, $gameId, $req_and_pack_id)
    {
        $content_id = 0;$distance = 0; //often requirement_detail_1
        $qty = 0;                      //often requirement_detail_2
        $latitude = 0.0;               //often requirement_detail_3
        $longitude = 0.0;              //often requirement_detail_4
        $bool_operator = $atom->not_operator == 'DO' ? 1 : 0;
        switch($atom->requirement)
        {
            case "PLAYER_HAS_ITEM":
                $content_id = $atom->requirement_detail_1;
                $qty = $atom->requirement_detail_2;
                break;
            case "PLAYER_HAS_TAGGED_ITEM":
                $content_id = $atom->requirement_detail_1;
                $qty = $atom->requirement_detail_2;
                break;
            case "PLAYER_VIEWED_ITEM":
            case "PLAYER_VIEWED_NODE":
            case "PLAYER_VIEWED_NPC":
            case "PLAYER_VIEWED_WEBPAGE":
            case "PLAYER_VIEWED_AUGBUBBLE":
                $content_id = $atom->requirement_detail_1;
                break;
            case "PLAYER_HAS_UPLOADED_MEDIA_ITEM":
            case "PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE":
            case "PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO":
            case "PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO":
                $distance = $atom->requirement_detail_1;
                $qty = $atom->requirement_detail_2;
                $latitude = $atom->requirement_detail_3;
                $longitude = $atom->requirement_detail_4;
                break;
            case "PLAYER_HAS_COMPLETED_QUEST":
                $content_id = $atom->requirement_detail_1;
                break;
            case "PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK":
                $content_id = $atom->requirement_detail_1;
                break;
            case "PLAYER_HAS_NOTE":
                $qty = $atom->requirement_detail_2;
                break;
            case "PLAYER_HAS_NOTE_WITH_TAG":
                $content_id = $atom->requirement_detail_1;
                $qty = $atom->requirement_detail_2;
                break;
            case "PLAYER_HAS_NOTE_WITH_LIKES":
                $qty = $atom->requirement_detail_2;
                break;
            case "PLAYER_HAS_NOTE_WITH_COMMENTS":
                $qty = $atom->requirement_detail_2;
                break;
            case "PLAYER_HAS_GIVEN_NOTE_COMMENTS":
                $qty = $atom->requirement_detail_2;
                break;
        }
        Module::query("INSERT INTO requirement_atoms (game_id, requirement_and_package_id, bool_operator, requirement, content_id, qty, distance, latitude, longitude, created) VALUES ('{$gameId}','{$req_and_pack_id}','{$bool_operator}','{$atom->requirement}','{$content_id}','{$qty}','{$distance}','{$latitude}','{$longitude}', CURRENT_TIMESTAMP)");
    }






    //
    //HACKED OLD OUTDATED API \/ \/ \/ \/
    //

    public function getRequirementsForObject($gameId, $objectType, $objectId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        //Old tables
        $rsResult = Module::query("SELECT * FROM requirements WHERE game_id = {$gameId} AND content_type = '{$objectType}' and content_id = '{$objectId}'");
        return new returnData(0, $rsResult);

/*
        //New tables
        switch($objectType)
        {
            case 'Node'     : $reqId = Module::queryObject("SELECT requirement_package_id FROM nodes      WHERE node_id      = '{$objectId}'")->requirement_package_id; break;
            case 'Item'     : $reqId = Module::queryObject("SELECT requirement_package_id FROM items      WHERE item_id      = '{$objectId}'")->requirement_package_id; break;
            case 'Npc'      : $reqId = Module::queryObject("SELECT requirement_package_id FROM npcs       WHERE npc_id       = '{$objectId}'")->requirement_package_id; break;
            case 'AugBubble': $reqId = Module::queryObject("SELECT requirement_package_id FROM augbubbles WHERE augbubble_id = '{$objectId}'")->requirement_package_id; break;
            case 'WebPage'  : $reqId = Module::queryObject("SELECT requirement_package_id FROM webpages   WHERE webpage_id   = '{$objectId}'")->requirement_package_id; break;
            case 'WebHook'  : $reqId = Module::queryObject("SELECT requirement_package_id FROM webhooks   WHERE webhook_id   = '{$objectId}'")->requirement_package_id; break;
            case 'Quest'    : $reqId = Module::queryObject("SELECT complete_requirement_package_id FROM quests     WHERE quest_id     = '{$objectId}'")->complete_requirement_package_id; break;
            default: return new returnData(4, NULL, "invalid object type");
        }
        $rpack = Requirements::getRequirementPackage($reqId);

        //now, we flatten to resemble old structure
        $returnObj = new stdClass();
        $returnObj->data = new stdClass();
        //Ok, this is so ridiculous
        $returnObj->data->columns = array("requirement_id","game_id","content_type","content_id","requirement","boolean_operator","not_operator","group_operator","requirement_detail_1","requirement_detail_2","requirement_detail_3","requirement_detail_4");
        $returnObj->data->rows = array();
        $returnDataRowIndex = 0;
        $returnObj->returnCode = 0; //fake it
        $returnObj->returnCodeDescription = null; //fake it

        //Make sure requirement package expressable in old model
        $multiAndPackCount = 0;
        for($i = 0; $i < count($rpack->and_packages); $i++)
            if(count($rpack->and_packages[$i]->atoms) > 1) $multiAndPackCount++;
        if($multiAndPackCount > 1) return new returnData(7, "Requirement data corrupted- use new editor");

        for($i = 0; $i < count($rpack->and_packages); $i++)
        {
            $BOOL = "AND";
            if(count($rpack->and_packages[$i]->atoms) == 1) $BOOL = "OR";

            for($j = 0; $j < count($rpack->and_packages[$i]->atoms); $j++)
            {
                $returnObj->data->rows[$returnDataRowIndex] = array();
                $returnObj->data->rows[$returnDataRowIndex][] = $rpack->and_packages[$i]->atoms[$j]->requirement_atom_id;
                $returnObj->data->rows[$returnDataRowIndex][] = $rpack->game_id;
                $returnObj->data->rows[$returnDataRowIndex][] = $objectType;
                $returnObj->data->rows[$returnDataRowIndex][] = $objectId;
                $returnObj->data->rows[$returnDataRowIndex][] = $rpack->and_packages[$i]->atoms[$j]->requirement;
                $returnObj->data->rows[$returnDataRowIndex][] = $BOOL;
                $returnObj->data->rows[$returnDataRowIndex][] = $rpack->and_packages[$i]->atoms[$j]->bool_operator ? "DO" : "NOT";
                $returnObj->data->rows[$returnDataRowIndex][] = "SELF";
                $returnObj->data->rows[$returnDataRowIndex][] = $rpack->and_packages[$i]->atoms[$j]->content_id+$rpack->and_packages[$i]->atoms[$j]->distance; //this is me being clever.
                $returnObj->data->rows[$returnDataRowIndex][] = $rpack->and_packages[$i]->atoms[$j]->qty;
                $returnObj->data->rows[$returnDataRowIndex][] = $rpack->and_packages[$i]->atoms[$j]->latitude;
                $returnObj->data->rows[$returnDataRowIndex][] = $rpack->and_packages[$i]->atoms[$j]->longitude;
                $returnDataRowIndex++;
            }
        }

        return $returnObj;
*/
    }

    public function getRequirement($gameId, $requirementId)
    {
        //Old tables
        $requirement = Module::queryObject("SELECT * FROM requirements WHERE game_id = {$gameId} AND requirement_id = {$requirementId} LIMIT 1");
        return new returnData(0, $requirement);	

/*
        //New tables
        //assume by requirement, they mean "requirement atom"
        $rAtom = Requirements::getRequirementAtom($requirementId);
        $returnObj = new stdClass();
        $returnObj->data = new stdClass();
        $returnObj->data->requirement_id = $rAtom->requirement_atom_id;
        $returnObj->data->game_id = $rAtom->game_id;
        $returnObj->data->requirement = $rAtom->requirement;
        $bool = Module::queryArray("SELECT * FROM requirement_atoms WHERE requirement_and_package_id = '{$rAtom->requirement_and_package_id}'");
        $returnObj->data->boolean_operator = count($bool) > 1 ? "AND" : "OR";
        $returnObj->data->not_operator = $rAtom->bool_operator ? "DO" : "NOT";
        $returnObj->data->group_operator = "SELF";
        $returnObj->data->requirement_detail_1 = $rAtom->content_id;
        $returnObj->data->requirement_detail_2 = $rAtom->qty;
        $returnObj->data->requirement_detail_3 = $rAtom->latitude;
        $returnObj->data->requirement_detail_4 = $rAtom->longitude;

        $packId = Module::queryObject("SELECT * FROM requirement_and_packages WHERE requirement_and_package_id = '{$rAtom->requirement_and_package_id}'")->requirement_root_package_id;
        
        if(     $content = Module::queryObject("SELECT * FROM locations WHERE requirement_package_id          = '{$packId}'")) { $returnObj->data->content_type = 'Location';      $returnObj->data->content_id = $content->location_id; }
        else if($content = Module::queryObject("SELECT * FROM nodes     WHERE requirement_package_id          = '{$packId}'")) { $returnObj->data->content_type = 'Node';          $returnObj->data->content_id = $content->node_id;     }
        else if($content = Module::queryObject("SELECT * FROM quests    WHERE display_requirement_package_id  = '{$packId}'")) { $returnObj->data->content_type = 'QuestDisplay';  $returnObj->data->content_id = $content->quest_id;    }
        else if($content = Module::queryObject("SELECT * FROM quests    WHERE complete_requirement_package_id = '{$packId}'")) { $returnObj->data->content_type = 'QuestComplete'; $returnObj->data->content_id = $content->quest_id;    }

        $returnObj->returnCode = 0;
        $returnObj->returnCodeDescription = null;
        return $returnObj;
*/
    }

    public function createRequirement($gameId, $objectType, $objectId, $requirementType, $requirementDetail1, $requirementDetail2, $requirementDetail3, $requirementDetail4, $booleanOperator, $notOperator, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");
        //if the requirement type refers to an item, make sure the QTY is set to 1 or more
        if(($requirementType == "PLAYER_HAS_ITEM") && $requirementDetail2 < 1) $requirementDetail2 = 1;

        //Old tables
        Module::query("INSERT INTO requirements (game_id, content_type, content_id, requirement, requirement_detail_1,requirement_detail_2,requirement_detail_3,requirement_detail_4,boolean_operator,not_operator) VALUES ('{$gameId}','{$objectType}','{$objectId}','{$requirementType}', '{$requirementDetail1}', '{$requirementDetail2}', '{$requirementDetail3}', '{$requirementDetail4}', '{$booleanOperator}','{$notOperator}')");
        return new returnData(0, mysql_insert_id());

/*
        switch($objectType)
        {
            case "Node":          $rPackId = Module::queryObject("SELECT * FROM nodes     WHERE node_id     = '{$objectId}'")->requirement_package_id; break;
            case "Location":      $rPackId = Module::queryObject("SELECT * FROM locations WHERE location_id = '{$objectId}'")->requirement_package_id; break;
            case "DisplayQuest":  $rPackId = Module::queryObject("SELECT * FROM quests    WHERE quest_id    = '{$objectId}'")->display_requirement_package_id; break;
            case "CompleteQuest": $rPackId = Module::queryObject("SELECT * FROM quests    WHERE quest_id    = '{$objectId}'")->complete_requirement_package_id; break;
        }
        if(!$rPackId)
        {
            $pack = new stdClass();
            $pack->game_id = $gameId;
            $pack->and_packages = array();
            $pack->and_packages[0] = new stdClass();
            $pack->and_packages[0]->atoms = array();
            $pack->and_packages[0]->atoms[0] = new stdClass();
            $pack->and_packages[0]->atoms[0]->bool_operator = $notOperator == "DO" ? 1 : 0;
            $pack->and_packages[0]->atoms[0]->requirement   = $requirement;
            $pack->and_packages[0]->atoms[0]->content_id    = $requirementDetail1;
            $pack->and_packages[0]->atoms[0]->distance      = $requirementDetail1;
            $pack->and_packages[0]->atoms[0]->qty           = $requirementDetail2;
            $pack->and_packages[0]->atoms[0]->latitude      = $requirementDetail3;
            $pack->and_packages[0]->atoms[0]->longitude     = $requirementDetail4;
            return new returnData(0,Requirements::createRequirementPackage($pack)->and_packages[0]->atoms[0]->requirement_atom_id);
        }
        else
        {
            $pack = Requirements::getRequirementPackage($rPackId);
            if($booleanOperator == "OR")
            {
                $pack->and_packages[] = new stdClass();
                $pack->and_packages[count($pack->and_packages)-1]->atoms = array();
                $pack->and_packages[count($pack->and_packages)-1]->atoms[0] = new stdClass();
                $pack->and_packages[count($pack->and_packages)-1]->atoms[0]->bool_operator = $notOperator == "DO" ? 1 : 0;
                $pack->and_packages[count($pack->and_packages)-1]->atoms[0]->requirement   = $requirement;
                $pack->and_packages[count($pack->and_packages)-1]->atoms[0]->content_id    = $requirementDetail1;
                $pack->and_packages[count($pack->and_packages)-1]->atoms[0]->distance      = $requirementDetail1;
                $pack->and_packages[count($pack->and_packages)-1]->atoms[0]->qty           = $requirementDetail2;
                $pack->and_packages[count($pack->and_packages)-1]->atoms[0]->latitude      = $requirementDetail3;
                $pack->and_packages[count($pack->and_packages)-1]->atoms[0]->longitude     = $requirementDetail4;

                $newPack = Requirements::updateRequirementPackage($pack);
                $andPackIndexThatDidntExistBefore = -1;
                for($i = 0; $i < count($newPack->and_packages); $i++)
                {
                    $found = false;
                    for($j = 0; $j < count($pack->and_packages); $j++)
                        if($newPack->and_packages[$i]->requirement_and_package_id == $pack->and_packages[$j]->requirement_package_id) $found = true;
                    if(!$found) $andPackIndexThatDidntExistBefore = $i;
                }
                if($andPackIndexThatDidntExistBefore > -1)
                    return new returnData(0,$newPack->and_packages[$andPackIndexThatDidntExistBefore]->atoms[0]->requirement_atom_id);
            }
            else
            {
                $indexOfFirstAndPackWithMultipleAtoms = 0;
                for($i = 0; $i < count($pack->and_packages); $i++)
                    if(count($pack->and_packages[$i]->atoms) > 0) $indexOfFirstAndPackWithMultipleAtoms = $i;

                $pack->and_packages[$indexOfFirstAndPackWithMultipleAtoms]->atoms[count($pack->and_packages[$indexOfFirstAndPackWithMultipleAtoms]->atoms)] = new stdClass();
                $pack->and_packages[$indexOfFirstAndPackWithMultipleAtoms]->atoms[count($pack->and_packages[$indexOfFirstAndPackWithMultipleAtoms]->atoms)]->bool_operator = $notOperator == "DO" ? 1 : 0;
                $pack->and_packages[$indexOfFirstAndPackWithMultipleAtoms]->atoms[count($pack->and_packages[$indexOfFirstAndPackWithMultipleAtoms]->atoms)]->requirement   = $requirement;
                $pack->and_packages[$indexOfFirstAndPackWithMultipleAtoms]->atoms[count($pack->and_packages[$indexOfFirstAndPackWithMultipleAtoms]->atoms)]->content_id    = $requirementDetail1;
                $pack->and_packages[$indexOfFirstAndPackWithMultipleAtoms]->atoms[count($pack->and_packages[$indexOfFirstAndPackWithMultipleAtoms]->atoms)]->distance      = $requirementDetail1;
                $pack->and_packages[$indexOfFirstAndPackWithMultipleAtoms]->atoms[count($pack->and_packages[$indexOfFirstAndPackWithMultipleAtoms]->atoms)]->qty           = $requirementDetail2;
                $pack->and_packages[$indexOfFirstAndPackWithMultipleAtoms]->atoms[count($pack->and_packages[$indexOfFirstAndPackWithMultipleAtoms]->atoms)]->latitude      = $requirementDetail3;
                $pack->and_packages[$indexOfFirstAndPackWithMultipleAtoms]->atoms[count($pack->and_packages[$indexOfFirstAndPackWithMultipleAtoms]->atoms)]->longitude     = $requirementDetail4;

                $newPack = Requirements::updateRequirementPackage($pack);
                $packAndId = $pack->and_packages[$indexOfFirstAndPackWithMultipleAtoms]->requirement_and_package_id;
                $andPackIndexThatWasChanged = -1;
                for($i = 0; $i < count($newPack->and_packages); $i++)
                    if($newPack->and_packages[$i]->requirement_and_package_id == $packAndId) $andPackIndexThatWasChanged = $i;
                if($andPackIndexThatWasChanged > -1)
                {
                    for($i = 0; $i < count($newPack->and_packages[$andPackIndexThatWasChanged]->atoms); $i++)
                    {
                        $found = false;
                        for($j = 0; $j < count($pack->and_packages[$indexOfFirstAndPackWithMultipleAtoms]->atoms); $j++)
                            if($newPack->and_packages[$andPackIndexThatWasChanged]->atoms[$i]->requirement_atom_id == $pack->and_packages[$indexOfFirstAndPackWithMultipleAtoms]->atoms[$j]->requirement_atom_id) $found = true;
                        if(!$found) $atomIndexThatDidntExistBefore = $i;
                    }
                    if($atomIndexThatDidntExistBefore > -1)
                        return new returnData(0,$newPack->and_packages[$andPackIndexThatWasChanged]->atoms[$atomIndexThatDidntExistBefore]->requirement_atom_id);
                }
            }
        }
        return new returnData(5,null,"Something went wrong");
*/
    }

    public function updateRequirement($gameId, $requirementId, $objectType, $objectId, $requirementType, $requirementDetail1, $requirementDetail2,$requirementDetail3,$requirementDetail4, $booleanOperator,$notOperator, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        //Old Tables
        $query = "UPDATE requirements 
            SET 
            content_type = '{$objectType}',
            content_id = '{$objectId}',
            requirement = '{$requirementType}',
            requirement_detail_1 = '{$requirementDetail1}',
            requirement_detail_2 = '{$requirementDetail2}',
            requirement_detail_3 = '{$requirementDetail3}',
            requirement_detail_4 = '{$requirementDetail4}',
            boolean_operator = '{$booleanOperator}',
            not_operator = '{$notOperator}'
            WHERE game_id = {$gameId} AND requirement_id = '{$requirementId}'";

        Module::query($query);
        return new returnData(0);

/*
        $atomPack = Module::queryObject("SELECT * FROM requirement_atoms WHERE requirement_atom_id = '{$requirementId}'");
        $andPack = Module::queryObject("SELECT * FROM requirement_and_packages WHERE requirement_and_package_id = '{$atomPack->requirement_and_package_id}'");
        $pack = Requirements::getRequirementPackage($andPack->requirement_root_package_id);
        $indexOfAndPack = -1;
        for($i = 0; $i < count($pack->and_packages); $i++)
        {
            if(count($pack->and_packages[$i]->atoms) > 1)
            {
                if($indexOfAndPack == -1) $indexOfAndPack = $i;
                else return new returnData(7, "Requirement data acorrupted- use new editor");
            }
        }
        if($indexOfAndPack == -1) $indexOfAndPack = 0;
        for($i = 0; $i < count($pack->and_packages); $i++)
        {
            if($pack->and_packages[$i]->requirement_and_package_id == $andPack->requirement_and_package_id)
            {
                if($booleanOperator == "AND")
                {
                    $indexOfAtom = -1;
                    for($j = 0; $j < count($pack->and_packages[$i]->atoms); $j++)
                    {
                        if($pack->and_packages[$i]->atoms[$j]->requirement_atom_id == $atomPack->requirement_atom_id)
                        {
                            if($indexOfAtom == -1) $indexOfAtom = $j;
                            else return new returnData(7, "Requirement data bcorrupted- use new editor");
                        }
                    }
                    if($i == $indexOfAndPack)
                    {
                        $pack->and_packages[$indexOfAndPack]->atoms[$indexOfAtom]->requirement   = $requirementType;
                        $pack->and_packages[$indexOfAndPack]->atoms[$indexOfAtom]->content_id    = $requirementDetail1;
                        $pack->and_packages[$indexOfAndPack]->atoms[$indexOfAtom]->distance      = $requirementDetail1;
                        $pack->and_packages[$indexOfAndPack]->atoms[$indexOfAtom]->qty           = $requirementDetail2;
                        $pack->and_packages[$indexOfAndPack]->atoms[$indexOfAtom]->latitude      = $requirementDetail3;
                        $pack->and_packages[$indexOfAndPack]->atoms[$indexOfAtom]->longitude     = $requirementDetail4;
                        $pack->and_packages[$indexOfAndPack]->atoms[$indexOfAtom]->bool_operator = $notOperator == "DO" ? 1 : 0;
                    }
                    else
                    {
                        $pack->and_packages[$indexOfAndPack]->atoms[] = $pack->and_packages[$i]->atoms[0];
                        $newIndex = count($pack->and_packages[$indexOfAndPack]->atoms)-1;
                        $pack->and_packages[$indexOfAndPack]->atoms[$newIndex]->requirement   = $requirementType;
                        $pack->and_packages[$indexOfAndPack]->atoms[$newIndex]->content_id    = $requirementDetail1;
                        $pack->and_packages[$indexOfAndPack]->atoms[$newIndex]->distance      = $requirementDetail1;
                        $pack->and_packages[$indexOfAndPack]->atoms[$newIndex]->qty           = $requirementDetail2;
                        $pack->and_packages[$indexOfAndPack]->atoms[$newIndex]->latitude      = $requirementDetail3;
                        $pack->and_packages[$indexOfAndPack]->atoms[$newIndex]->longitude     = $requirementDetail4;
                        $pack->and_packages[$indexOfAndPack]->atoms[$newIndex]->bool_operator = $notOperator == "DO" ? 1 : 0;
                        unset($pack->and_packages[$i]->atoms[0]);
                        unset($pack->and_packages[$i]->atoms);
                        unset($pack->and_packages[$i]);
                    }
                }
                else if($booleanOperator == "OR")
                {
                    if($i != $indexOfAndPack)
                    {
                        $pack->and_packages[$i]->atoms[0]->requirement   = $requirementType;
                        $pack->and_packages[$i]->atoms[0]->content_id    = $requirementDetail1;
                        $pack->and_packages[$i]->atoms[0]->distance      = $requirementDetail1;
                        $pack->and_packages[$i]->atoms[0]->qty           = $requirementDetail2;
                        $pack->and_packages[$i]->atoms[0]->latitude      = $requirementDetail3;
                        $pack->and_packages[$i]->atoms[0]->longitude     = $requirementDetail4;
                        $pack->and_packages[$i]->atoms[0]->bool_operator = $notOperator == "DO" ? 1 : 0;
                    }
                    else
                    {
                        $indexOfAtom = -1;
                        for($j = 0; $j < count($pack->and_packages[$i]->atoms); $j++)
                        {
                            if($pack->and_packages[$i]->atoms[$j]->requirement_atom_id == $atomPack->requirement_atom_id)
                            {
                                if($indexOfAtom == -1) $indexOfAtom = $j;
                                else return new returnData(7, "Requirement data ccorrupted- use new editor");
                            }
                        }

                        $pack->and_packages[] = new stdClass();
                        $newIndex = count($pack->and_packages)-1;
                        $pack->and_packages[$newIndex]->atoms = array();
                        $pack->and_packages[$newIndex]->atoms[0] = $pack->and_packages[$i]->atoms[$indexOfAtom];
                        $pack->and_packages[$newIndex]->atoms[0]->requirement   = $requirementType;
                        $pack->and_packages[$newIndex]->atoms[0]->content_id    = $requirementDetail1;
                        $pack->and_packages[$newIndex]->atoms[0]->distance      = $requirementDetail1;
                        $pack->and_packages[$newIndex]->atoms[0]->qty           = $requirementDetail2;
                        $pack->and_packages[$newIndex]->atoms[0]->latitude      = $requirementDetail3;
                        $pack->and_packages[$newIndex]->atoms[0]->longitude     = $requirementDetail4;
                        $pack->and_packages[$newIndex]->atoms[0]->bool_operator = $notOperator == "DO" ? 1 : 0;
                        unset($pack->and_packages[$i]->atoms[$indexOfAtom]);
                    }
                }
                Requirements::updateRequirementPackage($pack);
                return new returnData(0);
            }
        }
        return new returnData(7, "Requirement data dcorrupted- use new editor");
*/
    }

    public function deleteRequirement($gameId, $requirementId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");
        Module::query("DELETE FROM requirements WHERE game_id = {$gameId} AND requirement_id = {$requirementId}");
        return new returnData(0);
    }	

    public function deleteRequirementsForRequirementObject($gameId, $objectType, $objectId)
    {
        $requirementString = '';

        switch ($objectType)
        {
            case 'Node'     : $requirementString = "requirement = 'PLAYER_VIEWED_NODE'"; break;			
            case 'Item'     : $requirementString = "requirement = 'PLAYER_HAS_ITEM' OR requirement = 'PLAYER_VIEWED_ITEM'"; break;
            case 'Npc'      : $requirementString = "requirement = 'PLAYER_VIEWED_NPC'"; break;
            case 'AugBubble': $requirementString = "requirement = 'PLAYER_VIEWED_AUGBUBBLE'"; break;
            case 'WebPage'  : $requirementString = "requirement = 'PLAYER_VIEWED_WEBPAGE'"; break;
            case 'WebHook'  : $requirementString = "requirement = 'PLAYER_HAS_RECEIVED_INCOMING_WEBHOOK'"; break;
            case 'Quest'    : $requirementString = "requirement = 'PLAYER_HAS_COMPLETED_QUEST'"; break;
            default: return new returnData(4, NULL, "invalid object type");
        }

        Module::query("DELETE FROM requirements WHERE game_id = {$gameId} AND ({$requirementString}) AND requirement_detail_1 = '{$objectId}'");
        return new returnData(0);
    }		

    public function contentTypeOptions()
    {	
        $options = $this->lookupContentTypeOptionsFromSQL();
        return new returnData(0, $options);
    }

    public function requirementTypeOptions()
    {	
        $options = $this->lookupRequirementTypeOptionsFromSQL();
        return new returnData(0, $options);	
    }

    private function lookupContentTypeOptionsFromSQL()
    {
        $result = Module::query("SHOW COLUMNS FROM requirements LIKE 'content_type'");
        $row = @mysql_fetch_array( $result , MYSQL_NUM );
        $regex = "/'(.*?)'/";
        preg_match_all( $regex , $row[1], $enum_array );
        $enum_fields = $enum_array[1];
        return( $enum_fields );
    }

    private function lookupRequirementTypeOptionsFromSQL()
    {
        $result = Module::query("SHOW COLUMNS FROM requirements LIKE 'requirement'");
        $row = mysql_fetch_array( $result , MYSQL_NUM );
        $regex = "/'(.*?)'/";
        preg_match_all( $regex , $row[1], $enum_array );
        $enum_fields = $enum_array[1];
        return( $enum_fields );
    }	
}
?>
