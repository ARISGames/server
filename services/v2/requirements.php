<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

class requirements extends dbconnection
{	
    //Takes in requirementPackage JSON, all fields optional except game_id.
    //all individual ids (requirement_root_package_id, etc...) ignored if present ( = easy duplication)
    public function createRequirementPackage($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return requirements::createRequirementPackagePack($glob); }
    public function createRequirementPackagePack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->requirement_root_package_id = dbconnection::queryInsert(
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

        for($i = 0; $pack->and_packages && $i < count($pack->and_packages); $i++)
        {
            $pack->and_packages[$i]->requirement_root_package_id = $pack->requirement_root_package_id;
            $pack->and_packages[$i]->game_id = $pack->game_id;
            $pack->and_packages[$i]->auth = $pack->auth;
            requirements::createRequirementAndPackagePack($pack->and_packages[$i]);
        }

        return requirements::getRequirementPackagePack($pack);
    }

    //requires game_id and requirement_root_package_id
    public function createRequirementAndPackage($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return requirements::createRequirementAndPackagePack($glob); }
    public function createRequirementAndPackagePack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        if(!$pack->requirement_root_package_id) return;

        $requirementAndPackageId = dbconnection::queryInsert(
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
        
        for($i = 0; $pack->atoms && $i < count($pack->atoms); $i++)
        {
            $pack->atoms[$i]->requirement_and_package_id = $requirementAndPackageId;
            $pack->atoms[$i]->game_id = $pack->game_id;
            $pack->atoms[$i]->auth = $pack->auth;
            requirements::createRequirementAtomPack($pack->atoms[$i]);
        }
    }

    //requires game_id and requirement_and_package_id
    public function createRequirementAtom($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return requirements::createRequirementAtomPack($glob); }
    public function createRequirementAtomPack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        if(!$pack->requirement_and_package_id) return;

        dbconnection::query(
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

    public function updateRequirementPackage($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return requirements::updateRequirementPackagePack($glob); }
    public function updateRequirementPackagePack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM requirement_root_packages WHERE requirement_root_package_id = '{$pack->requirement_root_package_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        if(!$pack->requirement_root_package_id) return;

        dbconnection::query(
            "UPDATE requirement_root_packages SET ".
            "game_id = '".addslashes($pack->game_id)."'".
            ($pack->name ? ", name = '".addslashes($pack->name)."'" : "").
            " WHERE requirement_root_package_id = '".addslashes($pack->requirement_root_package_id)."'"
        );

        $sql_currentAndPacks = dbconnection::queryArray("SELECT * FROM requirement_and_packages WHERE requirement_root_package_id = '{$pack->requirement_root_package_id}'");
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
                $matchingAndPack->game_id                     = $pack->game_id;
                $matchingAndPack->auth                        = $pack->auth;
                requirements::updateRequirementAndPackagePack($matchingAndPack);
            }
            else
                requirements::deleteRequirementAndPackagePack($sql_currentAndPacks[$i]->requirement_and_package_id);
        }
        for($i = 0; $pack->and_packages && $i < count($pack->and_packages); $i++)
        {
            $pack->and_packages[$i]->requirement_root_package_id = $pack->requirement_root_package_id;
            $pack->and_packages[$i]->game_id                     = $pack->game_id;
            $pack->and_packages[$i]->auth                        = $pack->auth;
            requirements::createRequirementAndPackagePack($pack->and_packages[$i]);
        }

        return requirements::getRequirementPackagePack($pack);
    }

    public function updateRequirementAndPackage($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return requirements::updateRequirementAndPackagePack($glob); }
    public function updateRequirementAndPackagePack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM requirement_and_packages WHERE requirement_and_package_id = '{$pack->requirement_and_package_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        if(!$pack->requirement_and_package_id) return new return_package(1,NULL,"Insufficient data");

        dbconnection::query(
            "UPDATE requirement_and_packages SET ".
            "game_id = '".addslashes($pack->game_id)."'".
            ($pack->name ? ", name = '".addslashes($pack->name)."'" : "").
            " WHERE requirement_and_package_id = '".addslashes($pack->requirement_and_package_id)."'"
        );

        $sql_currentAtoms = dbconnection::queryArray("SELECT * FROM requirement_atoms WHERE requirement_and_package_id = '{$pack->requirement_and_package_id}'");
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
                $matchingAtom->game_id             = $pack->game_id;
                $matchingAtom->auth                = $pack->auth;
                requirements::updateRequirementAtomPack($matchingAtom);
            }
            else
                requirements::deleteRequirementAtomPack($sql_currentAtoms[$i]->requirement_atom_id);
        }
        for($i = 0; $pack->atoms && $i < count($pack->atoms); $i++)
        {
            $pack->atoms[$i]->requirement_atom_id = $pack->atoms[$j]->requirement_atom_id;
            $pack->atoms[$i]->game_id             = $pack->game_id;
            $pack->atoms[$i]->auth                = $pack->auth;
            requirements::createRequirementAtomPack($pack->atoms[$i]);
        }
    }

    public function updateRequirementAtom($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return requirements::updateRequirementAtomPack($glob); }
    public function updateRequirementAtomPack($pack)
    {
        $gameId = dbconnection::queryObject("SELECT * FROM requirement_atoms WHERE requirement_atom_id = '{$pack->requirement_atom_id}'")->game_id;
        if(!editors::authenticateGameEditor($gameId, $pack->auth->user_id, $pack->auth->key, "read_write"))
            return new return_package(6, NULL, "Failed Authentication");
        if(!$pack->requirement_atom_id) return new return_package(1,NULL,"Insufficient data");

        dbconnection::query(
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


    public function getRequirementPackage($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return requirements::getRequirementPackagePack($glob); }
    public function getRequirementPackagePack($pack)
    {
        $sql_root = dbconnection::queryObject("SELECT * FROM requirement_root_packages WHERE requirement_root_package_id = '{$pack->requirement_root_package_id}'");
        $pack->requirement_root_package_id = $sql_root->requirement_root_package_id;
        $pack->game_id = $sql_root->game_id;
        $pack->name = $sql_root->name;

        $sql_andPacks = dbconnection::queryArray("SELECT * FROM requirement_and_packages WHERE requirement_root_package_id = '{$pack->requirement_root_package_id}'");
        $pack->and_packages = array();

        for($i = 0; $i < count($sql_andPacks); $i++)
        {
            $pack->and_packages[$i] = requirements::getRequirementAndPackagePack($sql_andPacks[$i])->data;
            //makes for cleaner return object, as game_id,requirement_and_package_id is already in parent
            unset($pack->and_packages[$i]->game_id);
            unset($pack->and_packages[$i]->requirement_root_package_id);
        }

        return new return_package(0,$pack);
    }
    public function getRequirementAndPackage($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return requirements::getRequirementAndPackagePack($glob); }
    public function getRequirementAndPackagePack($pack)
    {
        $sql_andPack = dbconnection::queryObject("SELECT * FROM requirement_and_packages WHERE requirement_and_package_id = '{$pack->requirement_and_package_id}'");
        $andPack = new stdClass();
        $andPack->requirement_and_package_id  = $sql_andPack->requirement_and_package_id;
        $andPack->game_id                     = $sql_andPack->game_id;
        $andPack->requirement_root_package_id = $sql_andPack->requirement_root_package_id;
        $andPack->name                        = $sql_andPack->name;

        $sql_packAtoms = dbconnection::queryArray("SELECT * FROM  requirement_atoms WHERE requirement_and_package_id = '{$sql_andPack->requirement_and_package_id}'");
        $andPack->atoms = array();
        for($i = 0; $i < count($sql_packAtoms); $i++)
        {
            $andPack->atoms[$i] = requirements::getRequirementAtomPack($sql_packAtoms[$i])->data;
            //makes for cleaner return object, as game_id,requirement_and_package_id is already in parent
            unset($andPack->atoms[$i]->game_id);
            unset($andPack->atoms[$i]->requirement_and_package_id);
        }

        return new return_package(0,$andPack);
    }
    public function getRequirementAtom($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return requirements::getRequirementAtomPack($glob); }
    public function getRequirementAtomPack($pack)
    {
        $sql_atom = dbconnection::queryObject("SELECT * FROM requirement_atoms WHERE requirement_atom_id = '{$pack->requirement_atom_id}'");
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
        return new return_package(0,$atom);
    }

    public function deleteRequirementPackage($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return requirements::deleteRequirementPackagePack($glob); }
    public function deleteRequirementPackagePack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT game_id FROM requirement_root_packages WHERE requirement_root_package_id = '{$pack->requirement_root_package_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
         
        $sql_andPacks = dbconnection::queryArray("SELECT * FROM requirement_and_packages WHERE requirement_root_package_id = '{$pack->requirement_root_package_id}'");
        for($i = 0; $i < count($sql_andPacks); $i++)
            requirements::deleteRequirementAndPackagePack($sql_andPacks[$i]->requirement_and_package_id);
        dbconnection::query("DELETE FROM requirement_root_packages WHERE requirement_root_package_id = '{$pack->requirement_root_package_id}'");

        dbconnection::query("UPDATE quests SET complete_requirement_package_id = 0 WHERE game_id = '{$gameId}' AND complete_requirement_package_id = '{$requirementPackageId}'");
        dbconnection::query("UPDATE quests SET display_requirement_package_id = 0 WHERE game_id = '{$gameId}' AND display_requirement_package_id = '{$requirementPackageId}'");
        dbconnection::query("UPDATE locations SET requirement_package_id = 0 WHERE game_id = '{$gameId}' AND requirement_package_id = '{$requirementPackageId}'");
        dbconnection::query("UPDATE web_hooks SET requirement_package_id = 0 WHERE game_id = '{$gameId}' AND requirement_package_id = '{$requirementPackageId}'");
        dbconnection::query("UPDATE overlays SET requirement_package_id = 0 WHERE game_id = '{$gameId}' AND requirement_package_id = '{$requirementPackageId}'");
    }

    public function deleteRequirementAndPackage($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return requirements::deleteRequirementAndPackagePack($glob); }
    public function deleteRequirementAndPackagePack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT game_id FROM requirement_and_packages WHERE requirement_and_package_id = '{$pack->requirement_and_package_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $sql_packAtoms = dbconnection::queryArray("SELECT * FROM requirement_atoms WHERE requirement_and_package_id = '{$pack->requirement_and_package_id}'");
        for($i = 0; $i < count($sql_packAtoms); $i++)
            requirements::deleteRequirementAtomPack($sql_packAtoms[$i]->requirement_atom_id);
        dbconnection::query("DELETE FROM requirement_and_packages WHERE requirement_and_package_id = '{$pack->requirement_and_package_id}'");
    }

    public function deleteRequirementAtom($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return requirements::deleteRequirementAtomPack($glob); }
    public function deleteRequirementAtomPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT game_id FROM requirement_atoms WHERE requirement_atom_id = '{$pack->requirement_atom_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM requirement_atoms WHERE requirement_atom_id = '{$requirementAtomId}'");
    }

    public function evaluateRequirementPackage($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return requirements::evaluateRequirementPackagePack($glob); }
    public function evaluateRequirementPackagePack($pack)
    {
        $andPackages = dbconnection::queryArray("SELECT requirement_and_package_id FROM requirement_and_packages WHERE requirement_root_package_id= '{$pack->requirement_root_package_id}'");

        for($i = 0; $i < count($andPackages); $i++)
            if(requirements::evaluateRequirementAndPackagePack($andPackages[$i]->requirement_and_package_id, $pack->auth->user_id)) return true;
        return false;
    }

    public function evaluateRequirementAndPackage($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return requirements::evaluateRequirementAndPackagePack($glob); }
    public function evaluateRequirementAndPackagePack($pack)
    {
        $atoms = dbconnection::queryArray("SELECT requirement_atom_id FROM requirement_atoms WHERE requirement_and_package_id= '{$pack->requirement_and_package_id}'");

        for($i = 0; $i < count($atoms); $i++)
            if(!requirements::evaluateRequirementAtom($atoms[$i]->requirement_atom_id, $pack->user_id)) return false;
        return true;
    }

    public function evaluateRequirementAtom($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return requirements::evaluateRequirementAtomPack($glob); }
    public function evaluateRequirementAtomPack($pack)
    {
        $atom = dbconnection::queryObject("SELECT * FROM requirement_atoms WHERE requirement_atom_id = '{$pack->requirement_atom_id}'");

        //these functions need to be defined for new schema
        switch($atom->requirement)
        {
        /*
            case 'PLAYER_HAS_ITEM':                       return dbconnection::playerHasItem($gameId, $userId, $atom->content_id, $atom->qty); break;
            case 'PLAYER_HAS_TAGGED_ITEM':                return dbconnection::playerHasTaggedItem($gameId, $userId, $atom->content_id, $atom->qty); break;
            case 'PLAYER_VIEWED_ITEM':                    return dbconnection::playerHasLog($gameId, $userId, dbconnection::kLOG_VIEW_ITEM, $atom->content_id); break;
            case 'PLAYER_VIEWED_NODE':                    return dbconnection::playerHasLog($gameId, $userId, dbconnection::kLOG_VIEW_NODE, $atom->content_id); break;
            case 'PLAYER_VIEWED_NPC':                     return dbconnection::playerHasLog($gameId, $userId, dbconnection::kLOG_VIEW_NPC, $atom->content_id); break;
            case 'PLAYER_VIEWED_WEBPAGE':                 return dbconnection::playerHasLog($gameId, $userId, dbconnection::kLOG_VIEW_WEBPAGE, $atom->content_id); break;
            case 'PLAYER_VIEWED_AUGBUBBLE':               return dbconnection::playerHasLog($gameId, $userId, dbconnection::kLOG_VIEW_AUGBUBBLE, $atom->content_id); break;
            case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM':        return dbconnection::playerHasUploadedMediaItemWithinDistance($gameId, $userId, $atom->latitude, $atom->longitude, $atom->distance, $atom->qty, dbconnection::kLOG_UPLOAD_MEDIA_ITEM); break;
            case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE':  return dbconnection::playerHasUploadedMediaItemWithinDistance($gameId, $userId, $atom->latitude, $atom->longitude, $atom->distance, $atom->qty, dbconnection::kLOG_UPLOAD_MEDIA_ITEM_IMAGE); break;
            case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO':  return dbconnection::playerHasUploadedMediaItemWithinDistance($gameId, $userId, $atom->latitude, $atom->longitude, $atom->distance, $atom->qty, dbconnection::kLOG_UPLOAD_MEDIA_ITEM_AUDIO); break;
            case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO':  return dbconnection::playerHasUploadedMediaItemWithinDistance($gameId, $userId, $atom->latitude, $atom->longitude, $atom->distance, $atom->qty, dbconnection::kLOG_UPLOAD_MEDIA_ITEM_VIDEO); break;
            case 'PLAYER_HAS_COMPLETED_QUEST':            return dbconnection::playerHasLog($gameId, $userId, dbconnection::kLOG_COMPLETE_QUEST, $atom->content_id); break;
            case 'PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK': return dbconnection::playerHasLog($gameId, $userId, dbconnection::kLOG_RECEIVE_WEBHOOK, $atom->content_id); break;
            case 'PLAYER_HAS_NOTE':                       return dbconnection::playerHasNote($gameId, $userId, $atom->qty); break;
            case 'PLAYER_HAS_NOTE_WITH_TAG':              return dbconnection::playerHasNoteWithTag($gameId, $userId, $atom->content_id, $atom->qty); break;
            case 'PLAYER_HAS_NOTE_WITH_LIKES':            return dbconnection::playerHasNoteWithLikes($gameId, $userId, $atom->qty); break;
            case 'PLAYER_HAS_NOTE_WITH_COMMENTS':         return dbconnection::playerHasNoteWithComments($gameId, $userId, $atom->qty); break;
            case 'PLAYER_HAS_GIVEN_NOTE_COMMENTS':        return dbconnection::playerHasGivenNoteComments($gameId, $userId, $atom->qty); break;
        */
        }
        return false;
    }
}
?>
