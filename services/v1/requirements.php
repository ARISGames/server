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
    public function createRequirementPackage($glob)
    {
        Module::query(
            "INSERT INTO requirement_root_packages (".
            "'game_id',".
            ($glob->name ? "'name'," : "").
            "'created'".
            ") VALUES (".
            "'".addslashes($glob->game_id)."',".
            ($glob->name ? "'".addslashes($glob->name)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );
        $requirementPackageId = mysql_insert_id();

        for($i = 0; $glob->and_packages && $i < count($glob->and_packages); $i++)
        {
            $glob->and_packages[$i]->requirement_root_package_id = $requirementPackageId;
            $glob->and_packages[$i]->game_id = $glob->game_id;
            Requirements::createRequirementAndPackage($glob->and_packages[$i]);
        }

        return Requirements::getRequirementPackage($requirementPackageId);
    }

    public function createRequirementAndPackage($glob)
    {
        Module::query(
            "INSERT INTO requirement_and_packages (".
            "'game_id',".
            "'requirement_root_package_id',".
            ($glob->name ? "'name'," : "").
            "'created'".
            ") VALUES (".
            "'".addslashes($glob->game_id)."',".
            "'".addslashes($glob->requirement_root_package_id)."',".
            ($glob->name ? "'".addslashes($glob->name)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );
        $requirementAndPackageId = mysql_insert_id();

        for($i = 0; $glob->atoms && $i < count($glob->atoms); $i++)
        {
            $glob->atoms[$i]->requirement_and_package_id = $requirementAndPackageId;
            $glob->atoms[$i]->game_id = $glob->game_id;
            Requirements::createRequirementAtom($glob->atoms[$i]);
        }

    }

    public function createRequirementAtom($glob)
    {
        Module::query(
            "INSERT INTO requirement_atoms (".
            "'game_id',".
            "'requirement_and_package_id',".
            ($glob->bool_operator ? "'bool_operator'," : "").
            ($glob->requirement   ? "'requirement',"   : "").
            ($glob->content_id    ? "'content_id',"    : "").
            ($glob->qty           ? "'qty',"           : "").
            ($glob->latitude      ? "'latitude',"      : "").
            ($glob->longitude     ? "'longitude',"     : "").
            "'created'",
            ") VALUES (".
            "'".addslashes($glob->game_id)."',".
            "'".addslashes($glob->requirement_and_package_id)."',".
            ($glob->bool_operator ? "'".addslashes($glob->bool_operator)."'," : "").
            ($glob->requirement   ? "'".addslashes($glob->requirement  )."'," : "").
            ($glob->content_id    ? "'".addslashes($glob->content_id   )."'," : "").
            ($glob->qty           ? "'".addslashes($glob->qty          )."'," : "").
            ($glob->latitude      ? "'".addslashes($glob->latitude     )."'," : "").
            ($glob->longitude     ? "'".addslashes($glob->longitude    )."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );
    }

    public function updateRequirementPackage($glob)
    {
        Module::query(
            "UPDATE requirement_root_packages SET ".
            "'game_id' = '".addslashes($glob->game_id).
            ($glob->name ? ", 'name' = '".addslashes($glob->name)."'" : "").
            "WHERE requirement_root_package_id = '".addslashes($glob->requirement_root_package_id)."'"
        );

        $sql_currentAndPacks = Module::queryArray("SELECT * FROM requirement_and_packages WHERE requirement_root_package_id = '{$glob->requirement_root_package_id}'");
        for($i = 0; $i < count($sql_currentAndPacks); $i)
        {
            $matchingGlobAndPack = null;
            for($j = 0; $j < $glob->and_packages && count($glob->and_packages); $j++)
            {
                if($sql_currentAndPacks[$i]->requirement_and_package_id == $glob->and_packages[$j]->requirement_and_package_id)
                {
                    $matchingGlobAndPack = $glob->and_packages[$j];
                    //remove from array so I can just add all remaining later
                    array_splice($glob->and_packages, $j, 1);
                    $j--;
                }
            }
            if($matchingGlobAndPack)
            {
                $matchingGlobAndPack->requirement_root_package_id = $glob->requirement_root_package_id;
                $matchingGlobAndPack->game_id = $glob->game_id;
                Requirements::updateRequirementAndPack($matchingGlobAndPack);
            }
            else
                Requirements::deleteRequirementAndPack($sql_currentAndPacks->requirement_and_package_id);
        }
        for($i = 0; $glob->and_packages && $i < count($glob->and_packages); $i++)
        {
            $glob->and_packages[$i]->requirement_root_package_id = $glob->requirement_root_package_id;
            $glob->and_packages[$i]->game_id = $glob->game_id;
            Requirements::createRequirementAndPackage($glob->and_packages[$i]);
        }

        return Requirements::getRequirementPackage($glob->requirement_root_package_id);
    }

    public function updateRequirementAndPackage($glob)
    {
        Module::query(
            "UPDATE requirement_and_packages SET ".
            "'game_id' = '".addslashes($glob->game_id).
            ($glob->name ? ", 'name' = '".addslashes($glob->name)."'" : "").
            "WHERE requirement_and_package_id = '".addslashes($glob->requirement_and_package_id)."'"
        );

        $sql_currentAtoms = Module::queryArray("SELECT * FROM requirement_atoms WHERE requirement_and_package_id = '{$glob->requirement_and_package_id}'");
        for($i = 0; $i < count($sql_currentAndPacks); $i)
        {
            $matchingGlobAtom = null;
            for($j = 0; $j < $glob->atoms && count($glob->atoms); $j++)
            {
                if($sql_currentAtoms[$i]->requirement_atom_id == $glob->atoms[$j]->requirement_atom_id)
                {
                    $matchingGlobAtom = $glob->atoms[$j];
                    //remove from array so I can just add all remaining later
                    array_splice($glob->atoms, $j, 1);
                    $j--;
                }
            }
            if($matchingGlobAtom)
            {
                $matchingGlobAtom->requirement_atom_id = $glob->requirement_atom_id;
                $matchingGlobAtom->game_id = $glob->game_id;
                Requirements::updateRequirementAtom($matchingGlobAtom);
            }
            else
                Requirements::deleteRequirementAtom($sql_currentAtoms->requirement_atom_id);
        }
        for($i = 0; $glob->atoms && $i < count($glob->atoms); $i++)
        {
            $glob->atoms[$i]->requirement_atom_id = $glob->requirement_atom_id;
            $glob->atoms[$i]->game_id = $glob->game_id;
            Requirements::createRequirementAtom($glob->atoms[$i]);
        }

    }

    public function updateRequirementAtom($glob)
    {
        Module::query(
            "UPDATE requirement_atoms SET ".
            "'game_id' = '".addslashes($glob->game_id).
            ($glob->bool_operator ? ", 'bool_operator' = '".addslashes($glob->bool_operator)."'" : "").
            ($glob->requirement   ? ", 'requirement'   = '".addslashes($glob->requirement  )."'" : "").
            ($glob->content_id    ? ", 'content_id'    = '".addslashes($glob->content_id   )."'" : "").
            ($glob->qty           ? ", 'qty'           = '".addslashes($glob->qty          )."'" : "").
            ($glob->latitude      ? ", 'latitude'      = '".addslashes($glob->latitude     )."'" : "").
            ($glob->longitude     ? ", 'longitude'     = '".addslashes($glob->longitude    )."'" : "").
            "WHERE requirement_atom_id = '".addslashes($glob->requirement_atom_id)."'"
        );
    }


    public function getRequirementPackage($requirementPackageId)
    {
        $pack = new stdClass();

        $sql_root = Module::queryObject("SELECT * FROM requirement_root_packages WHERE requirement_root_package_id = '{$requirementPackageId}'");
        $pack->requirement_root_package_id = $sql_root->requirement_root_package_id;
        $pack->name = $sql_root->name;

        $sql_andPacks = Module::queryArray("SELECT * FROM requirement_and_packages WHERE requirement_root_package_id = '{$requirementPackageId}'");
        $pack->and_packages = array();

        for($i = 0; $i < count($sql_andPacks); $i++)
        {
            $pack->and_packages[$i] = new stdClass();
            $pack->and_packages[$i]->requirement_and_package_id = $sql_andPacks[$i]->requirement_and_package_id;

            $sql_packAtoms = Module::queryArray("SELECT * FROM  requirement_atoms WHERE requirement_and_package_id = '{$sql_andPacks[$i]->requirement_and_package_id}'");
            $pack->and_packages[$i]->atoms = array();
            for($j = 0; $j < count($sql_packAtoms); $j++)
            {
                $pack->and_packages[$i]->atoms[$j] = new stdClass();
                $pack->and_packages[$i]->atoms[$j]->requirement_atom_id = $sql_packAtoms[$j]->requirement_atom_id;
                $pack->and_packages[$i]->atoms[$j]->bool_operator       = $sql_packAtoms[$j]->bool_operator;
                $pack->and_packages[$i]->atoms[$j]->requirement         = $sql_packAtoms[$j]->requirement;
                $pack->and_packages[$i]->atoms[$j]->content_id          = $sql_packAtoms[$j]->content_id;
                $pack->and_packages[$i]->atoms[$j]->qty                 = $sql_packAtoms[$j]->qty;
                $pack->and_packages[$i]->atoms[$j]->latitude;           = $sql_packAtoms[$j]->latitude;
                $pack->and_packages[$i]->atoms[$j]->longitude;          = $sql_packAtoms[$j]->longitude;
            }
        }
    }

    public function deleteRequirementPackage($requirementPackageId)
    {
        $gameId = Module::queryObject("SELECT game_id FROM requirement_root_packages WHERE requirement_root_package_id = '{$requirementPackageId}'")->game_id;
        $sql_andPacks = Module::queryArray("SELECT * FROM requirement_and_packages WHERE requirement_root_package_id = '{$requirementPackageId}'");
        for($i = 0; $i < count($sql_andPacks); $i++)
            Requirements::deleteRequirementAtom($sql_andPacks[$i]->requirement_and_package_id);
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


















    public function getRequirementsForObject($gameId, $objectType, $objectId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        if (!$this->isValidObjectType($objectType)) return new returnData(4, NULL, "Invalid object type");

        $query = "SELECT * FROM requirements
            WHERE game_id = {$gameId} AND content_type = '{$objectType}' and content_id = '{$objectId}'";

        $rsResult = Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        return new returnData(0, $rsResult);
    }

    public function getRequirement($gameId, $requirementId)
    {
        $query = "SELECT * FROM requirements WHERE game_id = {$gameId} AND requirement_id = {$requirementId} LIMIT 1";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $requirement = @mysql_fetch_object($rsResult);
        if (!$requirement) return new returnData(2, NULL, "invalid requirement id");

        return new returnData(0, $requirement);	
    }

    public function createRequirement($gameId, $objectType, $objectId, 
            $requirementType, $requirementDetail1, $requirementDetail2, $requirementDetail3, $requirementDetail4, $booleanOperator, $notOperator, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        //test the object type 
        if (!$this->isValidObjectType($objectType)) return new returnData(4, NULL, "Invalid object type");

        //test the requirement type
        if (!$this->isValidRequirementType($requirementType)) return new returnData(5, NULL, "Invalid requirement type");

        //if the requirement type refers to an item, make sure the QTY is set to 1 or more
        if (($requirementType == "PLAYER_HAS_ITEM") && $requirementDetail2 < 1) 
            $requirementDetail2 = 1;

        $query = "INSERT INTO requirements 
            (game_id, content_type, content_id, requirement, 
             requirement_detail_1,requirement_detail_2,requirement_detail_3,requirement_detail_4,boolean_operator,not_operator)
            VALUES ('{$gameId}','{$objectType}','{$objectId}','{$requirementType}',
                    '{$requirementDetail1}', '{$requirementDetail2}', '{$requirementDetail3}', '{$requirementDetail4}', '{$booleanOperator}','{$notOperator}')";

        Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error:".mysql_error());

        return new returnData(0, mysql_insert_id());
    }

    public function updateRequirement($gameId, $requirementId, $objectType, $objectId, 
            $requirementType, $requirementDetail1, $requirementDetail2,$requirementDetail3,$requirementDetail4,
            $booleanOperator,$notOperator, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        //test the object type 
        if (!$this->isValidObjectType($objectType)) return new returnData(4, NULL, "Invalid object type");

        //test the requirement type
        if (!$this->isValidRequirementType($requirementType)) return new returnData(5, NULL, "Invalid requirement type");

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
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);
    }

    public function deleteRequirement($gameId, $requirementId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $query = "DELETE FROM requirements WHERE game_id = {$gameId} AND requirement_id = {$requirementId}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) {
            return new returnData(0);
        }
        else {
            return new returnData(2, NULL, 'invalid requirement id');
        }

    }	

    public function deleteRequirementsForRequirementObject($gameId, $objectType, $objectId)
    {
        $requirementString = '';

        switch ($objectType) {
            case 'Node':
                $requirementString = "requirement = 'PLAYER_VIEWED_NODE'";
                break;			
            case 'Item':
                $requirementString = "requirement = 'PLAYER_HAS_ITEM' OR
                    requirement = 'PLAYER_VIEWED_ITEM'";
                break;
            case 'Npc':
                $requirementString = "requirement = 'PLAYER_VIEWED_NPC'";
                break;
            case 'AugBubble':
                $requirementString = "requirement = 'PLAYER_VIEWED_AUGBUBBLE'";
                break;
            case 'WebPage':
                $requirementString = "requirement = 'PLAYER_VIEWED_WEBPAGE'";
                break;
            case 'WebHook':
                $requirementString = "requirement = 'PLAYER_HAS_RECEIVED_INCOMING_WEBHOOK'";
                break;
            case 'Quest':
                $requirementString = "requirement = 'PLAYER_HAS_COMPLETED_QUEST'";
                break;
            default:
                return new returnData(4, NULL, "invalid object type");
        }

        //Delete the Locations and related QR Codes
        $query = "DELETE FROM requirements
            WHERE game_id = {$gameId} AND ({$requirementString}) AND requirement_detail_1 = '{$objectId}'";

        Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) {
            return new returnData(0, TRUE);
        }
        else {
            return new returnData(0, FALSE);
        }	
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
        $query = "SHOW COLUMNS FROM requirements LIKE 'content_type'";

        $result = Module::query( $query );
        $row = @mysql_fetch_array( $result , MYSQL_NUM );
        $regex = "/'(.*?)'/";
        preg_match_all( $regex , $row[1], $enum_array );
        $enum_fields = $enum_array[1];
        return( $enum_fields );
    }

    private function lookupRequirementTypeOptionsFromSQL()
    {
        $query = "SHOW COLUMNS FROM requirements LIKE 'requirement'";
        $result = Module::query( $query );
        $row = mysql_fetch_array( $result , MYSQL_NUM );
        $regex = "/'(.*?)'/";
        preg_match_all( $regex , $row[1], $enum_array );
        $enum_fields = $enum_array[1];
        return( $enum_fields );
    }	

    private function isValidObjectType($objectType)
    {
        $validTypes = $this->lookupContentTypeOptionsFromSQL();
        return in_array($objectType, $validTypes);
    }

    private function isValidRequirementType($requirementType)
    {
        $validTypes = $this->lookupRequirementTypeOptionsFromSQL();
        return in_array($requirementType, $validTypes);
    }	
}
