<?php
require_once("module.php");

class PlayerStateChanges extends Module
{	
    public function getPlayerStateChangesForObject($intGameID, $strEventType, $strEventDetail)
    {
        $prefix = Module::getPrefix($intGameID);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        if (!$this->isValidEventType($strEventType)) return new returnData(4, NULL, "Invalid event type");

        $query = "SELECT * FROM player_state_changes
            WHERE game_id = {$prefix} AND event_type = '{$strEventType}' and event_detail = '{$strEventDetail}'";

        $rsResult = Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        return new returnData(0, $rsResult);
    }

    public function getPlayerStateChange($intGameID, $intPlayerStateChangeID)
    {
        $prefix = Module::getPrefix($intGameID);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "SELECT * FROM player_state_changes WHERE game_id = {$prefix} AND id = {$intPlayerStateChangeID} LIMIT 1";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $row = @mysql_fetch_object($rsResult);
        if (!$row) return new returnData(2, NULL, "invalid player state change id");

        return new returnData(0, $row);	
    }

    public function createPlayerStateChange($intGameID, $strEventType, $intEventDetail, 
            $strActionType, $strActionDetail, $intActionAmount)
    {
        $prefix = Module::getPrefix($intGameID);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        //test the object type 
        if (!$this->isValidEventType($strEventType)) return new returnData(4, NULL, "Invalid event type");

        //test the requirement type
        if (!$this->isValidActionType($strActionType)) return new returnData(5, NULL, "Invalid action type");


        $query = "INSERT INTO player_state_changes 
            (game_id, event_type, event_detail, action, action_detail, action_amount)
            VALUES ('{$prefix}','{$strEventType}','{$intEventDetail}','{$strActionType}','{$strActionDetail}','{$intActionAmount}')";


        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        return new returnData(0, mysql_insert_id());
    }

    public function updatePlayerStateChange($intGameID, $intPlayerStateChangeID, $strEventType, 
            $intEventDetail, $strActionType, $strActionDetail, $intActionAmount)
    {
        $prefix = Module::getPrefix($intGameID);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        //test the object type 
        if (!$this->isValidEventType($strEventType)) return new returnData(4, NULL, "Invalid object type");

        //test the requirement type
        if (!$this->isValidActionType($strActionType)) return new returnData(5, NULL, "Invalid action type");



        $query = "UPDATE player_state_changes 
            SET 
            event_type = '{$strEventType}',
                       event_detail = '{$intEventDetail}',
                       action = '{$strActionType}',
                       action_detail = '{$strActionDetail}',
                       action_amount = '{$intActionAmount}'
                           WHERE game_id = '{$prefix}' AND id = '{$intPlayerStateChangeID}'";


        Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);
    }

    public function deletePlayerStateChange($intGameID, $intPlayerStateChangeID)
    {
        $prefix = Module::getPrefix($intGameID);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "DELETE FROM player_state_changes WHERE game_id = {$prefix} AND id = {$intPlayerStateChangeID}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) {
            return new returnData(0);
        }
        else {
            return new returnData(2, NULL, 'invalid player state change id');
        }

    }

    public function deletePlayerStateChangesThatRefrenceObject($intGameID, $strObjectType, $intObjectId)
    {
        $prefix = Module::getPrefix($intGameID);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $whereClause = '';

        switch ($strObjectType) {
            case 'Node':
                $whereClause = "event_type = 'VIEW_NODE' AND event_detail = '{$intObjectId}'";
                break;			
            case 'Item':
                $whereClause = "(event_type = 'VIEW_ITEM' AND event_detail = '{$intObjectId}') OR
                    ((action = 'GIVE_ITEM' OR action = 'TAKE_ITEM') AND action_detail = '{$intObjectId}')";
                break;
            case 'Npc':
                $whereClause = "event_type = 'VIEW_NPC' AND event_detail = '{$intObjectId}'";
                break;
            default:
                return new returnData(4, NULL, "invalid object type");
        }

        //Delete the Locations and related QR Codes
        $query = "DELETE FROM player_state_changes WHERE game_id = {$prefix} AND {$whereClause}";

        Module::query($query);



        if (mysql_error()) return new returnData(3, NULL, "SQL Error");


        if (mysql_affected_rows()) {
            return new returnData(0, TRUE);
        }
        else {
            return new returnData(0, FALSE);
        }	
    }			

    public function eventTypeOptions()
    {	
        $options = PlayerStateChanges::lookupEventTypeOptionsFromSQL();
        return new returnData(0, $options);
    }

    public function actionTypeOptions()
    {	
        $options = PlayerStateChanges::lookupActionTypeOptionsFromSQL();
        return new returnData(0, $options);	
    }

    private function lookupEventTypeOptionsFromSQL()
    {
        $query = "SHOW COLUMNS FROM player_state_changes LIKE 'event_type'";

        $result = Module::query( $query );
        $row = @mysql_fetch_array( $result , MYSQL_NUM );
        $regex = "/'(.*?)'/";
        preg_match_all( $regex , $row[1], $enum_array );
        $enum_fields = $enum_array[1];
        return( $enum_fields );
    }

    private function lookupActionTypeOptionsFromSQL()
    {
        $query = "SHOW COLUMNS FROM player_state_changes LIKE 'action'";
        $result = Module::query( $query );
        $row = mysql_fetch_array( $result , MYSQL_NUM );
        $regex = "/'(.*?)'/";
        preg_match_all( $regex , $row[1], $enum_array );
        $enum_fields = $enum_array[1];
        return( $enum_fields );
    }	

    private function isValidEventType($strObjectType) {
        $validTypes = $this->lookupEventTypeOptionsFromSQL();
        return in_array($strObjectType, $validTypes);
    }

    private function isValidActionType($strActionType) {
        $validTypes = $this->lookupActionTypeOptionsFromSQL();
        return in_array($strActionType, $validTypes);
    }	
}
