<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

class requirements extends dbconnection
{
    //Takes in requirementPackage JSON, all fields optional except game_id.
    //all individual ids (requirement_root_package_id, etc...) ignored if present ( = easy duplication)
    public function createRequirementPackage($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->requirement_root_package_id = dbconnection::queryInsert(
            "INSERT INTO requirement_root_packages (".
            "game_id,".
            (isset($pack->name) ? "name," : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->name) ? "'".addslashes($pack->name)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        for($i = 0; $pack->and_packages && $i < count($pack->and_packages); $i++)
        {
            $pack->and_packages[$i]->requirement_root_package_id = $pack->requirement_root_package_id;
            $pack->and_packages[$i]->game_id = $pack->game_id;
            $pack->and_packages[$i]->auth = $pack->auth;
            requirements::createRequirementAndPackage($pack->and_packages[$i]);
        }

        return requirements::getRequirementPackage($pack);
    }

    //requires game_id and requirement_root_package_id
    public function createRequirementAndPackage($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        if(!$pack->requirement_root_package_id) return;

        $requirementAndPackageId = dbconnection::queryInsert(
            "INSERT INTO requirement_and_packages (".
            "game_id,".
            "requirement_root_package_id,".
            (isset($pack->name) ? "name," : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            "'".addslashes($pack->requirement_root_package_id)."',".
            (isset($pack->name) ? "'".addslashes($pack->name)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        for($i = 0; $pack->atoms && $i < count($pack->atoms); $i++)
        {
            $pack->atoms[$i]->requirement_and_package_id = $requirementAndPackageId;
            $pack->atoms[$i]->game_id = $pack->game_id;
            $pack->atoms[$i]->auth = $pack->auth;
            requirements::createRequirementAtom($pack->atoms[$i]);
        }
    }

    //requires game_id and requirement_and_package_id
    public function createRequirementAtom($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        if(!$pack->requirement_and_package_id) return;

        dbconnection::query(
            "INSERT INTO requirement_atoms (".
            "game_id,".
            "requirement_and_package_id,".
            (isset($pack->bool_operator) ? "bool_operator," : "").
            (isset($pack->requirement)   ? "requirement,"   : "").
            (isset($pack->content_id)    ? "content_id,"    : "").
            (isset($pack->distance)      ? "distance,"      : "").
            (isset($pack->qty)           ? "qty,"           : "").
            (isset($pack->latitude)      ? "latitude,"      : "").
            (isset($pack->longitude)     ? "longitude,"     : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            "'".addslashes($pack->requirement_and_package_id)."',".
            (isset($pack->bool_operator) ? "'".addslashes($pack->bool_operator)."'," : "").
            (isset($pack->requirement)   ? "'".addslashes($pack->requirement  )."'," : "").
            (isset($pack->content_id)    ? "'".addslashes($pack->content_id   )."'," : "").
            (isset($pack->distance)      ? "'".addslashes($pack->distance     )."'," : "").
            (isset($pack->qty)           ? "'".addslashes($pack->qty          )."'," : "").
            (isset($pack->latitude)      ? "'".addslashes($pack->latitude     )."'," : "").
            (isset($pack->longitude)     ? "'".addslashes($pack->longitude    )."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );
    }

    public function updateRequirementPackage($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM requirement_root_packages WHERE requirement_root_package_id = '{$pack->requirement_root_package_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        if(!$pack->requirement_root_package_id) return;

        dbconnection::query(
            "UPDATE requirement_root_packages SET ".
            (isset($pack->name) ? "name = '".addslashes($pack->name)."', " : "").
            "last_updated = CURRENT_TIMESTAMP ".
            "WHERE requirement_root_package_id = '".addslashes($pack->requirement_root_package_id)."'"
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
                requirements::updateRequirementAndPackage($matchingAndPack);
            }
            else
            {
                $sql_currentAndPacks[$i]->auth = $pack->auth;
                requirements::deleteRequirementAndPackage($sql_currentAndPacks[$i]);
            }
        }
        for($i = 0; $pack->and_packages && $i < count($pack->and_packages); $i++)
        {
            $pack->and_packages[$i]->requirement_root_package_id = $pack->requirement_root_package_id;
            $pack->and_packages[$i]->game_id                     = $pack->game_id;
            $pack->and_packages[$i]->auth                        = $pack->auth;
            requirements::createRequirementAndPackage($pack->and_packages[$i]);
        }

        return requirements::getRequirementPackage($pack);
    }

    public function updateRequirementAndPackage($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM requirement_and_packages WHERE requirement_and_package_id = '{$pack->requirement_and_package_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        if(!$pack->requirement_and_package_id) return new return_package(1,NULL,"Insufficient data");

        dbconnection::query(
            "UPDATE requirement_and_packages SET ".
            "game_id = '".addslashes($pack->game_id)."'".
            (isset($pack->name) ? ", name = '".addslashes($pack->name)."'" : "").
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
                $matchingAtom->requirement_and_package_id = $pack->requirement_and_package_id;
                $matchingAtom->game_id             = $pack->game_id;
                $matchingAtom->auth                = $pack->auth;
                requirements::updateRequirementAtom($matchingAtom);
            }
            else
            {
                $sql_currentAtoms[$i]->auth = $pack->auth;
                requirements::deleteRequirementAtom($sql_currentAtoms[$i]);
            }
        }
        for($i = 0; $pack->atoms && $i < count($pack->atoms); $i++)
        {
            $pack->atoms[$i]->requirement_and_package_id = $pack->requirement_and_package_id;
            $pack->atoms[$i]->game_id                    = $pack->game_id;
            $pack->atoms[$i]->auth                       = $pack->auth;
            requirements::createRequirementAtom($pack->atoms[$i]);
        }
    }

    public function updateRequirementAtom($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM requirement_atoms WHERE requirement_atom_id = '{$pack->requirement_atom_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        if(!$pack->requirement_atom_id) return new return_package(1,NULL,"Insufficient data");

        dbconnection::query(
            "UPDATE requirement_atoms SET ".
            "game_id = '".addslashes($pack->game_id)."'".
            (isset($pack->bool_operator) ? ", bool_operator = '".addslashes($pack->bool_operator)."'" : "").
            (isset($pack->requirement)   ? ", requirement   = '".addslashes($pack->requirement  )."'" : "").
            (isset($pack->content_id)    ? ", content_id    = '".addslashes($pack->content_id   )."'" : "").
            (isset($pack->distance)      ? ", distance      = '".addslashes($pack->distance     )."'" : "").
            (isset($pack->qty)           ? ", qty           = '".addslashes($pack->qty          )."'" : "").
            (isset($pack->latitude)      ? ", latitude      = '".addslashes($pack->latitude     )."'" : "").
            (isset($pack->longitude)     ? ", longitude     = '".addslashes($pack->longitude    )."'" : "").
            " WHERE requirement_atom_id = '".addslashes($pack->requirement_atom_id)."'"
        );
    }


    public function getRequirementPackage($pack)
    {
        $sql_root = dbconnection::queryObject("SELECT * FROM requirement_root_packages WHERE requirement_root_package_id = '{$pack->requirement_root_package_id}'");
        $pack->requirement_root_package_id = $sql_root->requirement_root_package_id;
        $pack->game_id = $sql_root->game_id;
        $pack->name = $sql_root->name;

        $sql_andPacks = dbconnection::queryArray("SELECT * FROM requirement_and_packages WHERE requirement_root_package_id = '{$pack->requirement_root_package_id}'");
        $pack->and_packages = array();

        for($i = 0; $i < count($sql_andPacks); $i++)
        {
            $pack->and_packages[$i] = requirements::getRequirementAndPackage($sql_andPacks[$i])->data;
            //makes for cleaner return object, as game_id,requirement_and_package_id is already in parent
            unset($pack->and_packages[$i]->game_id);
            unset($pack->and_packages[$i]->requirement_root_package_id);
        }

        unset($pack->auth);
        return new return_package(0,$pack);
    }
    public function getRequirementAndPackage($pack)
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
            $andPack->atoms[$i] = requirements::getRequirementAtom($sql_packAtoms[$i])->data;
            //makes for cleaner return object, as game_id,requirement_and_package_id is already in parent
            unset($andPack->atoms[$i]->game_id);
            unset($andPack->atoms[$i]->requirement_and_package_id);
        }

        unset($pack->auth);
        return new return_package(0,$andPack);
    }
    public function getRequirementAtom($pack)
    {
        $sql_atom = dbconnection::queryObject("SELECT * FROM requirement_atoms WHERE requirement_atom_id = '{$pack->requirement_atom_id}'");
        $atom = new stdClass();
        $atom->requirement_atom_id        = $sql_atom->requirement_atom_id;
        $atom->game_id                    = $sql_atom->game_id;
        $atom->requirement_and_package_id = $sql_atom->requirement_and_package_id;
        $atom->bool_operator              = $sql_atom->bool_operator;
        $atom->requirement                = $sql_atom->requirement;
        $atom->content_id                 = $sql_atom->content_id;
        $atom->distance                   = $sql_atom->distance;
        $atom->qty                        = $sql_atom->qty;
        $atom->latitude                   = $sql_atom->latitude;
        $atom->longitude                  = $sql_atom->longitude;

        unset($pack->auth);
        return new return_package(0,$atom);
    }

    public function deleteRequirementPackage($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT game_id FROM requirement_root_packages WHERE requirement_root_package_id = '{$pack->requirement_root_package_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        return requirements::noauth_deleteRequirementPackage($pack);
    }

    //this is a security risk...
    public function noauth_deleteRequirementPackage($pack)
    {
        //and this "fixes" the security risk...
        if(strpos($_server['request_uri'],'noauth') !== false) return new return_package(6, null, "attempt to bypass authentication externally.");

        dbconnection::query("DELETE FROM requirement_root_packages WHERE requirement_root_package_id = '{$pack->requirement_root_package_id}'");
        //cleanup
        $sql_andPacks = dbconnection::queryArray("SELECT * FROM requirement_and_packages WHERE requirement_root_package_id = '{$pack->requirement_root_package_id}'");
        for($i = 0; $i < count($sql_andPacks); $i++)
        {
            $sql_andPacks[$i]->auth = $pack->auth;
            requirements::noauth_deleteRequirementAndPackage($sql_andPacks[$i]);
        }
        dbconnection::query("UPDATE quests SET complete_requirement_root_package_id = 0 WHERE game_id = '{$gameId}' AND complete_requirement_root_package_id = '{$requirementPackageId}'");
        dbconnection::query("UPDATE quests SET display_requirement_root_package_id = 0 WHERE game_id = '{$gameId}' AND display_requirement_root_package_id = '{$requirementPackageId}'");
        dbconnection::query("UPDATE locations SET requirement_root_package_id = 0 WHERE game_id = '{$gameId}' AND requirement_root_package_id = '{$requirementPackageId}'");
        dbconnection::query("UPDATE web_hooks SET requirement_root_package_id = 0 WHERE game_id = '{$gameId}' AND requirement_root_package_id = '{$requirementPackageId}'");
        dbconnection::query("UPDATE overlays SET requirement_root_package_id = 0 WHERE game_id = '{$gameId}' AND requirement_root_package_id = '{$requirementPackageId}'");
        return new return_package(0);
    }

    public function deleteRequirementAndPackage($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT game_id FROM requirement_and_packages WHERE requirement_and_package_id = '{$pack->requirement_and_package_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        return requirements::noauth_deleteRequirementAndPackage($pack);
    }

    //this is a security risk...
    public function noauth_deleteRequirementAndPackage($pack)
    {
        //and this "fixes" the security risk...
        if(strpos($_server['request_uri'],'noauth') !== false) return new return_package(6, null, "attempt to bypass authentication externally.");

        dbconnection::query("DELETE FROM requirement_and_packages WHERE requirement_and_package_id = '{$pack->requirement_and_package_id}'");
        //cleanup
        $sql_packAtoms = dbconnection::queryArray("SELECT * FROM requirement_atoms WHERE requirement_and_package_id = '{$pack->requirement_and_package_id}'");
        for($i = 0; $i < count($sql_packAtoms); $i++)
        {
            $sql_packAtoms[$i]->auth = $pack->auth;
            requirements::deleteRequirementAtom($sql_packAtoms[$i]);
        }
        return new return_package(0);
    }

    public function deleteRequirementAtom($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT game_id FROM requirement_atoms WHERE requirement_atom_id = '{$pack->requirement_atom_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        return requirements::noauth_deleteRequirementAtom($pack);
    }

    //this is a security risk...
    public function noauth_deleteRequirementAtom($pack)
    {
        //and this "fixes" the security risk...
        if(strpos($_server['request_uri'],'noauth') !== false) return new return_package(6, null, "attempt to bypass authentication externally.");

        dbconnection::query("DELETE FROM requirement_atoms WHERE requirement_atom_id = '{$pack->requirement_atom_id}'");
        return new return_package(0);
    }

    public function getRequirementAndPackagesForRootPackage($pack)
    {
        $sql_andPacks = dbconnection::queryObject("SELECT * FROM requirement_and_packages WHERE requirement_root_package_id = '{$pack->requirement_root_package_id}'");
        $andPackages = array();
        for($i = 0; $i < count($sql_andPacks); $i++)
            $andPackages[] = requirements::getRequirementAndPackage($sql_andPacks[$i])->data;

        return new return_package(0,$andPackages);
    }

    public function getRequirementAtomsForAndPackage($pack)
    {
        $sql_atoms = dbconnection::queryObject("SELECT * FROM requirement_atoms WHERE requirement_and_package_id = '{$pack->requirement_and_package_id}'");
        $atoms = array();
        for($i = 0; $i < count($sql_atoms); $i++)
            $atoms[] = requirements::getRequirementAtom($sql_atoms[$i])->data;

        return new return_package(0,$atoms);
    }

    public function evaluateRequirementPackage($pack)
    {
        if($pack->requirement_root_package_id == 0) return true;

        $andPackages = dbconnection::queryArray("SELECT requirement_and_package_id FROM requirement_and_packages WHERE requirement_root_package_id= '{$pack->requirement_root_package_id}'");

        if(count($andPackages) == 0) return true;
        for($i = 0; $i < count($andPackages); $i++)
        {
            $andPackages[$i]->user_id = $pack->user_id;
            if(requirements::evaluateRequirementAndPackage($andPackages[$i])) return true;
        }
        return false;
    }

    public function evaluateRequirementAndPackage($pack)
    {
        $atoms = dbconnection::queryArray("SELECT requirement_atom_id FROM requirement_atoms WHERE requirement_and_package_id= '{$pack->requirement_and_package_id}'");

        if(count($atoms) == 0) return false;
        for($i = 0; $i < count($atoms); $i++)
        {
            $atoms[$i]->user_id = $pack->user_id;
            if(!requirements::evaluateRequirementAtom($atoms[$i])) return false;
        }
        return true;
    }

    public function evaluateRequirementAtom($pack)
    {
        $atom = dbconnection::queryObject("SELECT * FROM requirement_atoms WHERE requirement_atom_id = '{$pack->requirement_atom_id}'");
        if(!$atom) return false;
        $atom->user_id = $pack->user_id;
        if($atom->bool_operator == 0) $atom->bool_operator = false;
        if($atom->bool_operator == 1) $atom->bool_operator = true;

        //these functions need to be defined for new schema
        switch($atom->requirement)
        {
            case 'ALWAYS_TRUE':                           return $atom->bool_operator == true;
            case 'ALWAYS_FALSE':                          return $atom->bool_operator == false;
            case 'PLAYER_HAS_ITEM':                       return $atom->bool_operator == requirements::playerHasItem($atom);
            case 'PLAYER_HAS_TAGGED_ITEM':                return $atom->bool_operator == requirements::playerHasTaggedItem($atom);
            case 'PLAYER_VIEWED_ITEM':                    return $atom->bool_operator == requirements::playerViewed($atom,"ITEM");
            case 'PLAYER_VIEWED_PLAQUE':                  return $atom->bool_operator == requirements::playerViewed($atom,"PLAQUE");
            case 'PLAYER_VIEWED_DIALOG':                  return $atom->bool_operator == requirements::playerViewed($atom,"DIALOG");
            case 'PLAYER_VIEWED_DIALOG_SCRIPT':           return $atom->bool_operator == requirements::playerViewed($atom,"DIALOG_SCRIPT");
            case 'PLAYER_VIEWED_WEB_PAGE':                return $atom->bool_operator == requirements::playerViewed($atom,"WEB_PAGE");
            case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM':        return $atom->bool_operator == requirements::playerUploadedAnyNear($atom);
            case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE':  return $atom->bool_operator == requirements::playerUploadedTypeNear($atom,"IMAGE");
            case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO':  return $atom->bool_operator == requirements::playerUploadedTypeNear($atom,"AUDIO");
            case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO':  return $atom->bool_operator == requirements::playerUploadedTypeNear($atom,"VIDEO");
            case 'PLAYER_HAS_COMPLETED_QUEST':            return $atom->bool_operator == requirements::playerCompletedQuest($atom);
            case 'PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK': return $atom->bool_operator == requirements::playerReceivedWebHook($atom);
            case 'PLAYER_HAS_NOTE':                       return $atom->bool_operator == requirements::playerHasNote($atom);
            case 'PLAYER_HAS_NOTE_WITH_TAG':              return $atom->bool_operator == requirements::playerHasNoteWithTag($atom);
            case 'PLAYER_HAS_NOTE_WITH_LIKES':            return $atom->bool_operator == requirements::playerHasNoteWithLikes($atom);
            case 'PLAYER_HAS_NOTE_WITH_COMMENTS':         return $atom->bool_operator == requirements::playerHasNoteWithComments($atom);
            case 'PLAYER_HAS_GIVEN_NOTE_COMMENTS':        return $atom->bool_operator == requirements::playerHasGivenNoteComments($atom);
        }
        return false;
    }

    private function playerHasItem($pack)
    {
        $item = dbconnection::queryObject("SELECT * FROM instances WHERE game_id = '{$pack->game_id}' AND owner_id = '{$pack->user_id}' AND object_type = 'ITEM' AND object_id = '{$pack->content_id}' AND qty >= '{$pack->qty}'");
        return $item ? true : false;
    }

    private function playerHasTaggedItem($pack)
    {
        //NOT DONE!!
        $item = dbconnection::queryObject("SELECT * FROM instances WHERE game_id = '{$pack->game_id}' AND owner_id = '{$pack->user_id}' AND object_type = 'ITEM' AND object_id = '{$pack->content_id}' AND qty >= '{$pack->qty}'");
        return $item ? true : false;
    }

    private function playerViewed($pack,$type)
    {
        $entry = dbconnection::queryObject("SELECT * FROM user_log WHERE game_id = '{$pack->game_id}' AND user_id = '{$pack->user_id}' AND event_type = 'VIEW_{$type}' AND content_id = '{$pack->content_id}' AND deleted = 0");
        return $entry ? true : false;
    }

    // FIXME use location boundary
    private function playerUploadedAnyNear($pack)
    {
        $result = dbconnection::queryObject("SELECT count(*) as qty FROM user_log JOIN notes ON notes.note_id = user_log.content_id WHERE user_log.game_id = '{$pack->game_id}' AND user_log.user_id = '{$pack->user_id}' AND user_log.event_type = 'CREATE_NOTE' AND user_log.deleted = '0' AND notes.media_id != '0'");

        return $result->qty >= $pack->qty ? true : false;
    }

    // FIXME use location boundary
    private function playerUploadedTypeNear($pack,$type)
    {
        // Compare with list of types in media.php
        switch($type)
        {
            case 'IMAGE': $type_query = "media.file_name LIKE '%.jpg' OR media.file_name LIKE '%.png' OR media.file_name LIKE '%.gif'"; break;
            case 'VIDEO': $type_query = "media.file_name LIKE '%.mp4' OR media.file_name LIKE '%.mov' OR media.file_name LIKE '%.m4v OR media.file_name LIKE '%.3gp'"; break;
            case 'AUDIO': $type_query = "media.file_name LIKE '%.caf' OR media.file_name LIKE '%.mp3' OR media.file_name LIKE '%.aac OR media.file_name LIKE '%.m4a'"; break;
        }

        $query = "SELECT count(*) as qty FROM user_log JOIN notes ON notes.note_id = user_log.content_id JOIN media ON media.media_id = notes.media_id WHERE user_log.game_id = '{$pack->game_id}' AND user_log.user_id = '{$pack->user_id}' AND user_log.event_type = 'CREATE_NOTE' AND user_log.deleted = '0' AND notes.media_id != '0' AND ({$type_query})";
        $result = dbconnection::queryObject($query);

        return $result->qty >= $pack->qty ? true : false;
    }

    private function playerCompletedQuest($pack)
    {
        $entry = dbconnection::queryObject("SELECT * FROM user_log WHERE game_id = '{$pack->game_id}' AND user_id = '{$pack->user_id}' AND event_type = 'COMPLETE_QUEST' AND content_id = '{$pack->content_id}' AND deleted = 0");
        return $entry ? true : false;
    }

    private function playerReceivedWebHook($pack)
    {
        return false;
    }

    private function playerHasNote($pack)
    {
        $result = dbconnection::queryObject("SELECT count(*) as qty FROM user_log WHERE game_id = '{$pack->game_id}' AND user_id = '{$pack->user_id}' AND event_type = 'CREATE_NOTE' AND deleted = 0");

        return $result->qty >= $pack->qty ? true : false;
    }

    private function playerHasNoteWithTag($pack)
    {
        $result = dbconnection::queryObject("SELECT count(*) as qty FROM user_log JOIN notes ON notes.note_id = user_log.content_id JOIN object_tags ON object_tags.object_id = notes.note_id WHERE user_log.game_id = '{$pack->game_id}' AND user_log.user_id = '{$pack->user_id}' AND user_log.event_type = 'CREATE_NOTE' AND user_log.deleted = '0' AND object_tags.tag_id = '{$pack->content_id}'");

        return $result->qty >= $pack->qty ? true : false;
    }

    // FIXME There are no likes in v2
    private function playerHasNoteWithLikes($pack)
    {
        return false;
    }

    private function playerHasNoteWithComments($pack)
    {
        $query = "SELECT count(note_comments.note_id) as qty FROM user_log JOIN notes ON notes.note_id = user_log.content_id JOIN note_comments ON note_comments.note_id = notes.note_id WHERE user_log.game_id = '{$pack->game_id}' AND user_log.user_id = '{$pack->user_id}' AND user_log.event_type = 'CREATE_NOTE' AND user_log.deleted = 0 GROUP BY note_comments.note_id ORDER BY qty DESC LIMIT 1";
        $result = dbconnection::queryObject($query);

        return $result->qty >= $pack->qty ? true : false;
    }

    private function playerHasGivenNoteComments($pack)
    {
        $result = dbconnection::queryObject("SELECT count(*) as qty FROM user_log WHERE game_id = '{$pack->game_id}' AND user_id = '{$pack->user_id}' AND event_type = 'GIVE_NOTE_COMMENT' AND deleted = 0");

        return $result->qty >= $pack->qty ? true : false;
    }
}
?>
