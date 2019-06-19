<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("games.php");
require_once("return_package.php");
require_once("../../libraries/geolocation/GeoLocation.php");

class requirements extends dbconnection
{
    //Takes in requirementPackage JSON, all fields optional except game_id.
    //all individual ids (requirement_root_package_id, etc...) ignored if present ( = easy duplication)
    public static function createRequirementPackage($pack)
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

        games::bumpGameVersion($pack);
        return requirements::getRequirementPackage($pack);
    }

    //requires game_id and requirement_root_package_id
    public static function createRequirementAndPackage($pack)
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
        games::bumpGameVersion($pack);
    }

    //requires game_id and requirement_and_package_id
    public static function createRequirementAtom($pack)
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
        games::bumpGameVersion($pack);
    }

    public static function updateRequirementPackage($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM requirement_root_packages WHERE requirement_root_package_id = '{$pack->requirement_root_package_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        if(!$pack->requirement_root_package_id) return;

        dbconnection::query(
            "UPDATE requirement_root_packages SET ".
            (isset($pack->name) ? "name = '".addslashes($pack->name)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
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

        games::bumpGameVersion($pack);
        return requirements::getRequirementPackage($pack);
    }

    public static function updateRequirementAndPackage($pack)
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
        games::bumpGameVersion($pack);
    }

    public static function updateRequirementAtom($pack)
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
        games::bumpGameVersion($pack);
    }


    public static function getRequirementPackage($pack)
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
    public static function getRequirementAndPackage($pack)
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
    public static function getRequirementAtom($pack)
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

    private static function requirementRootPackageObjectFromSQL($sql_rrp)
    {
        if(!$sql_rrp) return $sql_rrp;
        $rrp = new stdClass();
        $rrp->requirement_root_package_id = $sql_rrp->requirement_root_package_id;
        $rrp->game_id                     = $sql_rrp->game_id;
        $rrp->name                        = $sql_rrp->name;

        return $rrp;
    }

    private static function requirementAndPackageObjectFromSQL($sql_rap)
    {
        if(!$sql_rap) return $sql_rap;
        $rap = new stdClass();
        $rap->requirement_and_package_id  = $sql_rap->requirement_and_package_id;
        $rap->requirement_root_package_id = $sql_rap->requirement_root_package_id;
        $rap->game_id                     = $sql_rap->game_id;
        $rap->name                        = $sql_rap->name;

        return $rap;
    }

    private static function requirementAtomObjectFromSQL($sql_atom)
    {
        if(!$sql_atom) return $sql_atom;
        $atom = new stdClass();
        $atom->requirement_atom_id        = $sql_atom->requirement_atom_id;
        $atom->requirement_and_package_id = $sql_atom->requirement_and_package_id;
        $atom->game_id                    = $sql_atom->game_id;
        $atom->bool_operator              = $sql_atom->bool_operator;
        $atom->requirement                = $sql_atom->requirement;
        $atom->content_id                 = $sql_atom->content_id;
        $atom->distance                   = $sql_atom->distance;
        $atom->qty                        = $sql_atom->qty;
        $atom->latitude                   = $sql_atom->latitude;
        $atom->longitude                  = $sql_atom->longitude;
        $atom->name                       = $sql_atom->name;

        return $atom;
    }


    public static function getRequirementRootPackagesForGame($pack)
    {
      $sql_rrps = dbconnection::queryArray("SELECT * FROM requirement_root_packages WHERE game_id = '{$pack->game_id}'");
      $rrps = array();
      for($i = 0; $i < count($sql_rrps); $i++)
        if($ob = requirements::requirementRootPackageObjectFromSQL($sql_rrps[$i])) $rrps[] = $ob;

      return new return_package(0,$rrps);
    }

    public static function getRequirementAndPackagesForGame($pack)
    {
      $sql_raps = dbconnection::queryArray("SELECT * FROM requirement_and_packages WHERE game_id = '{$pack->game_id}'");
      $raps = array();
      for($i = 0; $i < count($sql_raps); $i++)
        if($ob = requirements::requirementAndPackageObjectFromSQL($sql_raps[$i])) $raps[] = $ob;

      return new return_package(0,$raps);
    }

    public static function getRequirementAtomsForGame($pack)
    {
      $sql_as = dbconnection::queryArray("SELECT * FROM requirement_atoms WHERE game_id = '{$pack->game_id}'");
      $as = array();
      for($i = 0; $i < count($sql_as); $i++)
        if($ob = requirements::requirementAtomObjectFromSQL($sql_as[$i])) $as[] = $ob;

      return new return_package(0,$as);
    }


    public static function deleteRequirementPackage($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT game_id FROM requirement_root_packages WHERE requirement_root_package_id = '{$pack->requirement_root_package_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        games::bumpGameVersion($pack);
        return requirements::noauth_deleteRequirementPackage($pack);
    }

    //this is a security risk...
    public static function noauth_deleteRequirementPackage($pack)
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
        dbconnection::query("UPDATE quests SET active_requirement_root_package_id = 0 WHERE game_id = '{$gameId}' AND active_requirement_root_package_id = '{$requirementPackageId}'");
        dbconnection::query("UPDATE locations SET requirement_root_package_id = 0 WHERE game_id = '{$gameId}' AND requirement_root_package_id = '{$requirementPackageId}'");
        dbconnection::query("UPDATE web_hooks SET requirement_root_package_id = 0 WHERE game_id = '{$gameId}' AND requirement_root_package_id = '{$requirementPackageId}'");
        dbconnection::query("UPDATE overlays SET requirement_root_package_id = 0 WHERE game_id = '{$gameId}' AND requirement_root_package_id = '{$requirementPackageId}'");
        games::bumpGameVersion($pack);
        return new return_package(0);
    }

    public static function deleteRequirementAndPackage($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT game_id FROM requirement_and_packages WHERE requirement_and_package_id = '{$pack->requirement_and_package_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        games::bumpGameVersion($pack);
        return requirements::noauth_deleteRequirementAndPackage($pack);
    }

    //this is a security risk...
    public static function noauth_deleteRequirementAndPackage($pack)
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
        games::bumpGameVersion($pack);
        return new return_package(0);
    }

    public static function deleteRequirementAtom($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT game_id FROM requirement_atoms WHERE requirement_atom_id = '{$pack->requirement_atom_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        games::bumpGameVersion($pack);
        return requirements::noauth_deleteRequirementAtom($pack);
    }

    //this is a security risk...
    public static function noauth_deleteRequirementAtom($pack)
    {
        //and this "fixes" the security risk...
        if(strpos($_server['request_uri'],'noauth') !== false) return new return_package(6, null, "attempt to bypass authentication externally.");

        dbconnection::query("DELETE FROM requirement_atoms WHERE requirement_atom_id = '{$pack->requirement_atom_id}'");
        games::bumpGameVersion($pack);
        return new return_package(0);
    }

    public static function getRequirementAndPackagesForRootPackage($pack)
    {
        $sql_andPacks = dbconnection::queryObject("SELECT * FROM requirement_and_packages WHERE requirement_root_package_id = '{$pack->requirement_root_package_id}'");
        $andPackages = array();
        for($i = 0; $i < count($sql_andPacks); $i++)
            $andPackages[] = requirements::getRequirementAndPackage($sql_andPacks[$i])->data;

        return new return_package(0,$andPackages);
    }

    public static function getRequirementAtomsForAndPackage($pack)
    {
        $sql_atoms = dbconnection::queryObject("SELECT * FROM requirement_atoms WHERE requirement_and_package_id = '{$pack->requirement_and_package_id}'");
        $atoms = array();
        for($i = 0; $i < count($sql_atoms); $i++)
            $atoms[] = requirements::getRequirementAtom($sql_atoms[$i])->data;

        return new return_package(0,$atoms);
    }

    public static function evaluateRequirementPackage($pack)
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

    public static function evaluateRequirementAndPackage($pack)
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

    public static function evaluateRequirementAtom($pack)
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
            case 'GAME_HAS_ITEM':                         return $atom->bool_operator == requirements::gameHasItem($atom);
            case 'GAME_HAS_TAGGED_ITEM':                  return $atom->bool_operator == requirements::gameHasTaggedItem($atom);
            case 'GROUP_HAS_ITEM':                        return $atom->bool_operator == requirements::groupHasItem($atom);
            case 'GROUP_HAS_TAGGED_ITEM':                 return $atom->bool_operator == requirements::groupHasTaggedItem($atom);
            case 'PLAYER_VIEWED_ITEM':                    return $atom->bool_operator == requirements::playerViewed($atom,"ITEM");
            case 'PLAYER_VIEWED_PLAQUE':                  return $atom->bool_operator == requirements::playerViewed($atom,"PLAQUE");
            case 'PLAYER_VIEWED_DIALOG':                  return $atom->bool_operator == requirements::playerViewed($atom,"DIALOG");
            case 'PLAYER_VIEWED_DIALOG_SCRIPT':           return $atom->bool_operator == requirements::playerViewed($atom,"DIALOG_SCRIPT");
            case 'PLAYER_VIEWED_WEB_PAGE':                return $atom->bool_operator == requirements::playerViewed($atom,"WEB_PAGE");
            case 'PLAYER_RAN_EVENT_PACKAGE':              return $atom->bool_operator == requirements::playerRanEvent($atom);
            case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM':        return $atom->bool_operator == requirements::playerUploadedAnyNear($atom);
            case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE':  return $atom->bool_operator == requirements::playerUploadedTypeNear($atom,"IMAGE");
            case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO':  return $atom->bool_operator == requirements::playerUploadedTypeNear($atom,"AUDIO");
            case 'PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO':  return $atom->bool_operator == requirements::playerUploadedTypeNear($atom,"VIDEO");
            case 'PLAYER_HAS_COMPLETED_QUEST':            return $atom->bool_operator == requirements::playerCompletedQuest($atom);
            case 'PLAYER_HAS_QUEST_STARS':                return $atom->bool_operator == requirements::playerHasQuestStars($atom);
            case 'PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK': return $atom->bool_operator == requirements::playerReceivedWebHook($atom);
            case 'PLAYER_HAS_NOTE':                       return $atom->bool_operator == requirements::playerHasNote($atom);
            case 'PLAYER_HAS_NOTE_WITH_TAG':              return $atom->bool_operator == requirements::playerHasNoteWithTag($atom);
            case 'PLAYER_HAS_NOTE_WITH_LIKES':            return $atom->bool_operator == requirements::playerHasNoteWithLikes($atom);
            case 'PLAYER_HAS_NOTE_WITH_COMMENTS':         return $atom->bool_operator == requirements::playerHasNoteWithComments($atom);
            case 'PLAYER_HAS_GIVEN_NOTE_COMMENTS':        return $atom->bool_operator == requirements::playerHasGivenNoteComments($atom);
        }
        return false;
    }

    private static function playerHasItem($pack)
    {
        $item = dbconnection::queryObject("SELECT * FROM instances WHERE game_id = '{$pack->game_id}' AND owner_type = 'USER' AND owner_id = '{$pack->user_id}' AND object_type = 'ITEM' AND object_id = '{$pack->content_id}' AND qty >= '{$pack->qty}'");
        return $item ? true : false;
    }

    private static function playerHasTaggedItem($pack)
    {
        $query = "SELECT SUM(instances.qty) AS qty FROM instances JOIN object_tags ON object_tags.object_id = instances.object_id WHERE instances.game_id = '{$pack->game_id}' AND instances.owner_type = 'USER' AND instances.owner_id = '{$pack->user_id}' AND instances.object_type = 'ITEM' AND object_tags.object_Type = 'ITEM' AND object_tags.tag_id = '{$pack->content_id}'";
        $item = dbconnection::queryObject($query);
        return intval($item->qty) >= intval($pack->qty);
    }

    private static function gameHasItem($pack)
    {
        $item = dbconnection::queryObject("SELECT * FROM instances WHERE game_id = '{$pack->game_id}' AND owner_type = 'GAME' AND object_type = 'ITEM' AND object_id = '{$pack->content_id}' AND qty >= '{$pack->qty}'");
        return $item ? true : false;
    }

    private static function gameHasTaggedItem($pack)
    {
        $query = "SELECT SUM(instances.qty) AS qty FROM instances JOIN object_tags ON object_tags.object_id = instances.object_id WHERE instances.game_id = '{$pack->game_id}' AND instances.owner_type = 'GAME' AND instances.object_type = 'ITEM' AND object_tags.object_Type = 'ITEM' AND object_tags.tag_id = '{$pack->content_id}'";
        $item = dbconnection::queryObject($query);
        return intval($item->qty) >= intval($pack->qty);
    }

    private static function groupHasItem($pack)
    {
        $group = dbconnection::queryObject("SELECT * FROM user_game_groups WHERE game_id = '{$pack->game_id}' AND user_id = '{$pack->user_id}';");
        if(!$group) return false;
        $item = dbconnection::queryObject("SELECT * FROM instances WHERE game_id = '{$pack->game_id}' AND owner_type = 'GROUP' AND owner_id = '{$group->group_id}' AND object_type = 'ITEM' AND object_id = '{$pack->content_id}' AND qty >= '{$pack->qty}'");
        return $item ? true : false;
    }

    private static function groupHasTaggedItem($pack)
    {
        $query = "SELECT SUM(instances.qty) AS qty FROM instances JOIN object_tags ON object_tags.object_id = instances.object_id WHERE instances.game_id = '{$pack->game_id}' AND instances.owner_type = 'GROUP' AND instances.owner_id = '{$pack->group_id}' AND instances.object_type = 'ITEM' AND object_tags.object_Type = 'ITEM' AND object_tags.tag_id = '{$pack->content_id}'";
        $item = dbconnection::queryObject($query);
        return intval($item->qty) >= intval($pack->qty);
    }

    private static function playerViewed($pack,$type)
    {
        $entry = dbconnection::queryObject("SELECT * FROM user_log WHERE game_id = '{$pack->game_id}' AND user_id = '{$pack->user_id}' AND event_type = 'VIEW_{$type}' AND content_id = '{$pack->content_id}' AND deleted = 0");
        return $entry ? true : false;
    }

    private static function playerRanEvent($pack)
    {
        $entry = dbconnection::queryObject("SELECT * FROM user_log WHERE game_id = '{$pack->game_id}' AND user_id = '{$pack->user_id}' AND event_type = 'RUN_EVENT_PACKAGE' AND content_id = '{$pack->content_id}' AND deleted = 0");
        return $entry ? true : false;
    }

    // TODO second pass using radius ie http://www.movable-type.co.uk/scripts/latlong-db.html and http://janmatuschek.de/LatitudeLongitudeBoundingCoordinates
    private static function playerUploadedAnyNear($pack)
    {
        $geo = AnthonyMartin\GeoLocation\GeoLocation::fromDegrees($pack->latitude, $pack->longitude);
        $bounds = $geo->boundingCoordinates($pack->distance * 0.001, 'km');

        $location_conditions = "triggers.latitude BETWEEN {$bounds->min->getLatitudeInDegrees()} and {$bounds->max->getLatitudeInDegrees()} AND triggers.longitude BETWEEN {$bounds->min->getLongitudeInDegrees()} and {$bounds->max->getLongitudeInDegrees()}";

        $joins = "JOIN notes ON notes.note_id = user_log.content_id JOIN instances ON instances.object_id = notes.note_id JOIN triggers ON triggers.instance_id = instances.instance_id";
        $conditions = "WHERE user_log.game_id = '{$pack->game_id}' AND user_log.user_id = '{$pack->user_id}' AND user_log.event_type = 'CREATE_NOTE' AND user_log.deleted = '0' AND notes.media_id != '0' AND instances.object_type = 'NOTE' AND {$location_conditions}";
        $query = "SELECT count(*) as qty FROM user_log {$joins} {$conditions}";

        $result = dbconnection::queryObject($query);

        return $result->qty >= $pack->qty ? true : false;
    }

    private static function playerUploadedTypeNear($pack,$type)
    {
        // Compare with list of types in media.php
        switch($type)
        {
            case 'IMAGE': $filetype_conditions = "media.file_name LIKE '%.jpg' OR media.file_name LIKE '%.png' OR media.file_name LIKE '%.gif'"; break;
            case 'VIDEO': $filetype_conditions = "media.file_name LIKE '%.mp4' OR media.file_name LIKE '%.mov' OR media.file_name LIKE '%.m4v' OR media.file_name LIKE '%.3gp'"; break;
            case 'AUDIO': $filetype_conditions = "media.file_name LIKE '%.caf' OR media.file_name LIKE '%.mp3' OR media.file_name LIKE '%.aac' OR media.file_name LIKE '%.m4a'"; break;
        }

        $geo = AnthonyMartin\GeoLocation\GeoLocation::fromDegrees($pack->latitude, $pack->longitude);
        $bounds = $geo->boundingCoordinates($pack->distance * 0.001, 'km');

        $location_conditions = "triggers.latitude BETWEEN {$bounds->min->getLatitudeInDegrees()} and {$bounds->max->getLatitudeInDegrees()} AND triggers.longitude BETWEEN {$bounds->min->getLongitudeInDegrees()} and {$bounds->max->getLongitudeInDegrees()}";

        $joins = "JOIN notes ON notes.note_id = user_log.content_id JOIN media ON media.media_id = notes.media_id JOIN instances ON instances.object_id = notes.note_id JOIN triggers ON triggers.instance_id = instances.instance_id";
        $conditions = "WHERE user_log.game_id = '{$pack->game_id}' AND user_log.user_id = '{$pack->user_id}' AND user_log.event_type = 'CREATE_NOTE' AND user_log.deleted = '0' AND notes.media_id != '0' AND instances.object_type = 'NOTE' AND ({$filetype_conditions}) AND {$location_conditions}";
        $query = "SELECT count(*) as qty FROM user_log {$joins} {$conditions}";

        $result = dbconnection::queryObject($query);

        return $result->qty >= $pack->qty ? true : false;
    }

    private static function playerCompletedQuest($pack)
    {
        $entry = dbconnection::queryObject("SELECT * FROM user_log WHERE game_id = '{$pack->game_id}' AND user_id = '{$pack->user_id}' AND event_type = 'COMPLETE_QUEST' AND content_id = '{$pack->content_id}' AND deleted = 0");
        return $entry ? true : false;
    }

    private static function playerHasQuestStars($pack) {
        $game_id = intval($pack->game_id);
        $user_id = intval($pack->user_id);
        $compound_id = intval($pack->content_id);
        $results = dbconnection::queryArray("SELECT quests.stars FROM quests JOIN user_log ON user_log.content_id = quests.quest_id WHERE quests.game_id = '{$game_id}' AND quests.parent_quest_id = '{$compound_id}' AND user_log.user_id = '{$user_id}' AND user_log.game_id = '{$game_id}' AND user_log.event_type = 'COMPLETE_QUEST' AND user_log.deleted = 0 GROUP BY quests.quest_id");
        $stars = 0;
        foreach ($results as $row) {
            $stars += $row->stars;
        }
        return $stars >= intval($pack->qty);
    }

    // There are no web hooks in v2
    private static function playerReceivedWebHook($pack)
    {
        return false;
    }

    private static function playerHasNote($pack)
    {
        $result = dbconnection::queryObject("SELECT count(*) as qty FROM user_log WHERE game_id = '{$pack->game_id}' AND user_id = '{$pack->user_id}' AND event_type = 'CREATE_NOTE' AND deleted = 0");

        return $result->qty >= $pack->qty ? true : false;
    }

    private static function playerHasNoteWithTag($pack)
    {
        $tag_id = intval($pack->content_id);
        if ($tag_id > 10000000) {
            // siftr select field id
            $field_option_id = $tag_id - 10000000;
            $result = dbconnection::queryObject("
                SELECT count(*) as qty
                FROM user_log
                JOIN notes ON notes.note_id = user_log.content_id
                JOIN field_data ON field_data.note_id = notes.note_id
                JOIN games ON notes.game_id = games.game_id
                WHERE user_log.game_id = '{$pack->game_id}'
                AND user_log.user_id = '{$pack->user_id}'
                AND user_log.event_type = 'CREATE_NOTE'
                AND user_log.deleted = '0'
                AND field_data.field_id = games.field_id_pin
                AND field_data.field_option_id = '{$field_option_id}'");
        } else {
            // real tag id
            $result = dbconnection::queryObject("SELECT count(*) as qty FROM user_log JOIN notes ON notes.note_id = user_log.content_id JOIN object_tags ON object_tags.object_id = notes.note_id WHERE user_log.game_id = '{$pack->game_id}' AND user_log.user_id = '{$pack->user_id}' AND user_log.event_type = 'CREATE_NOTE' AND user_log.deleted = '0' AND object_tags.tag_id = '{$pack->content_id}' AND object_tags.object_type = 'NOTE'");
        }

        return $result->qty >= $pack->qty ? true : false;
    }

    // There are no likes in v2
    private static function playerHasNoteWithLikes($pack)
    {
        return false;
    }

    private static function playerHasNoteWithComments($pack)
    {
        $query = "SELECT count(note_comments.note_id) as qty FROM user_log JOIN notes ON notes.note_id = user_log.content_id JOIN note_comments ON note_comments.note_id = notes.note_id WHERE user_log.game_id = '{$pack->game_id}' AND user_log.user_id = '{$pack->user_id}' AND user_log.event_type = 'CREATE_NOTE' AND user_log.deleted = 0 GROUP BY note_comments.note_id ORDER BY qty DESC LIMIT 1";
        $result = dbconnection::queryObject($query);

        return $result->qty >= $pack->qty ? true : false;
    }

    private static function playerHasGivenNoteComments($pack)
    {
        $result = dbconnection::queryObject("SELECT count(*) as qty FROM user_log WHERE game_id = '{$pack->game_id}' AND user_id = '{$pack->user_id}' AND event_type = 'GIVE_NOTE_COMMENT' AND deleted = 0");

        return $result->qty >= $pack->qty ? true : false;
    }
}
?>
