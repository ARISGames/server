<?php
require_once("module.php");

class Requirements extends Module
{	
    public function getRequirementsForObject($gameId, $objectType, $objectId)
    {
        $prefix = Module::getPrefix($gameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        if (!$this->isValidObjectType($objectType)) return new returnData(4, NULL, "Invalid object type");

        $query = "SELECT * FROM requirements
            WHERE game_id = {$prefix} AND content_type = '{$objectType}' and content_id = '{$objectId}'";

        $rsResult = Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        return new returnData(0, $rsResult);
    }

    public function getRequirement($gameId, $requirementId)
    {
        $prefix = Module::getPrefix($gameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "SELECT * FROM requirements WHERE game_id = {$prefix} AND requirement_id = {$requirementId} LIMIT 1";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $requirement = @mysql_fetch_object($rsResult);
        if (!$requirement) return new returnData(2, NULL, "invalid requirement id");

        return new returnData(0, $requirement);	
    }

    public function createRequirement($gameId, $objectType, $objectId, 
            $requirementType, $requirementDetail1, $requirementDetail2, $requirementDetail3, $requirementDetail4, $booleanOperator, $notOperator)
    {
        $prefix = Module::getPrefix($gameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

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
            VALUES ('{$prefix}','{$objectType}','{$objectId}','{$requirementType}',
                    '{$requirementDetail1}', '{$requirementDetail2}', '{$requirementDetail3}', '{$requirementDetail4}', '{$booleanOperator}','{$notOperator}')";

        Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error:".mysql_error());

        return new returnData(0, mysql_insert_id());
    }

    public function updateRequirement($gameId, $requirementId, $objectType, $objectId, 
            $requirementType, $requirementDetail1, $requirementDetail2,$requirementDetail3,$requirementDetail4,
            $booleanOperator,$notOperator)
    {
        $prefix = Module::getPrefix($gameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

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
                             WHERE game_id = {$prefix} AND requirement_id = '{$requirementId}'";

        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);
    }

    public function deleteRequirement($gameId, $requirementId)
    {
        $prefix = Module::getPrefix($gameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "DELETE FROM requirements WHERE game_id = {$prefix} AND requirement_id = {$requirementId}";

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
        $prefix = Module::getPrefix($gameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

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
            WHERE game_id = {$prefix} AND ({$requirementString}) AND requirement_detail_1 = '{$objectId}'";

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
