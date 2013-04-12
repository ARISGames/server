<?php
require_once("module.php");

class PlayerStateChanges extends Module
{	
    public function getPlayerStateChangesForObject($gameId, $strEventType, $strEventDetail, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        if (!$this->isValidEventType($strEventType)) return new returnData(4, NULL, "Invalid event type");

        $query = "SELECT * FROM player_state_changes
            WHERE game_id = {$gameId} AND event_type = '{$strEventType}' and event_detail = '{$strEventDetail}'";

        $rsResult = Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        return new returnData(0, $rsResult);
    }

    public function getPlayerStateChange($gameId, $intPlayerStateChangeID)
    {
        $query = "SELECT * FROM player_state_changes WHERE game_id = {$gameId} AND id = {$intPlayerStateChangeID} LIMIT 1";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $row = @mysql_fetch_object($rsResult);
        if (!$row) return new returnData(2, NULL, "invalid player state change id");

        return new returnData(0, $row);	
    }

    public function createPlayerStateChange($gameId, $strEventType, $intEventDetail, 
            $strActionType, $strActionDetail, $intActionAmount, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");
            
        //test the object type 
        if (!$this->isValidEventType($strEventType)) return new returnData(4, NULL, "Invalid event type");

        //test the requirement type
        if (!$this->isValidActionType($strActionType)) return new returnData(5, NULL, "Invalid action type");


        $query = "INSERT INTO player_state_changes 
            (game_id, event_type, event_detail, action, action_detail, action_amount)
            VALUES ('{$gameId}','{$strEventType}','{$intEventDetail}','{$strActionType}','{$strActionDetail}','{$intActionAmount}')";


        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        return new returnData(0, mysql_insert_id());
    }

    public function updatePlayerStateChange($gameId, $intPlayerStateChangeID, $strEventType, 
            $intEventDetail, $strActionType, $strActionDetail, $intActionAmount, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

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
                           WHERE game_id = '{$gameId}' AND id = '{$intPlayerStateChangeID}'";


        Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);
    }

    public function deletePlayerStateChange($gameId, $intPlayerStateChangeID, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $query = "DELETE FROM player_state_changes WHERE game_id = {$gameId} AND id = {$intPlayerStateChangeID}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) {
            return new returnData(0);
        }
        else {
            return new returnData(2, NULL, 'invalid player state change id');
        }

    }

    public function deletePlayerStateChangesThatRefrenceObject($gameId, $strObjectType, $intObjectId)
    {
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
        $query = "DELETE FROM player_state_changes WHERE game_id = {$gameId} AND {$whereClause}";

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
