<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

class state_changes extends dbconnection
{	
    //Takes in state_change JSON, all fields optional except game_id + user_id + key
    public static function createStateChange($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return state_changes::createStateChangePack($glob); }
    public static function createStateChangePack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->state_change_id = dbconnection::queryInsert(
            "INSERT INTO state_changes (".
            "game_id,".
            (isset($pack->action)      ? "action,"      : "").
            (isset($pack->amount)      ? "amount,"      : "").
            (isset($pack->object_type) ? "object_type," : "").
            (isset($pack->object_id)   ? "object_id,"   : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->action)      ? "'".addslashes($pack->action)."',"      : "").
            (isset($pack->amount)      ? "'".addslashes($pack->amount)."',"      : "").
            (isset($pack->object_type) ? "'".addslashes($pack->object_type)."'," : "").
            (isset($pack->object_id)   ? "'".addslashes($pack->object_id)."',"   : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return state_changes::getStateChangePack($pack);
    }

    //Takes in game JSON, all fields optional except state_change_id + user_id + key
    public static function updateStateChange($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return state_changes::updateStateChangePack($glob); }
    public static function updateStateChangePack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM state_changes WHERE state_change_id = '{$pack->state_change_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE state_changes SET ".
            (isset($pack->action)      ? "action      = '".addslashes($pack->action)."', "      : "").
            (isset($pack->amount)      ? "amount      = '".addslashes($pack->amount)."', "      : "").
            (isset($pack->object_type) ? "object_type = '".addslashes($pack->object_type)."', " : "").
            (isset($pack->object_id)   ? "object_id   = '".addslashes($pack->object_id)."', "   : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE state_change_id = '{$pack->state_change_id}'"
        );

        return state_changes::getStateChangePack($pack);
    }

    private static function stateChangeObjectFromSQL($sql_stateChange)
    {
        if(!$sql_stateChange) return $sql_stateChange;
        $stateChange = new stdClass();
        $stateChange->state_change_id = $sql_stateChange->state_change_id;
        $stateChange->game_id         = $sql_stateChange->game_id;
        $stateChange->action          = $sql_stateChange->action;
        $stateChange->amount          = $sql_stateChange->amount;
        $stateChange->object_type     = $sql_stateChange->object_type;
        $stateChange->object_id       = $sql_stateChange->object_id;

        return $stateChange;
    }

    public static function getStateChange($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return state_changes::getStateChangePack($glob); }
    public static function getStateChangePack($pack)
    {
        $sql_stateChange = dbconnection::queryObject("SELECT * FROM state_changes WHERE state_change_id = '{$pack->state_change_id}' LIMIT 1");
        return new return_package(0,state_changes::stateChangeObjectFromSQL($sql_stateChange));
    }

    public static function getStateChangesForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return state_changes::getStateChangesForGamePack($glob); }
    public static function getStateChangesForGamePack($pack)
    {
        $sql_stateChanges = dbconnection::queryArray("SELECT * FROM state_changes WHERE game_id = '{$pack->game_id}'");
        $stateChanges = array();
        for($i = 0; $i < count($sql_stateChanges); $i++)
            if($ob = state_changes::stateChangeObjectFromSQL($sql_stateChanges[$i])) $stateChanges[] = $ob;

        return new return_package(0,$stateChanges);
    }

    public static function getStateChangesForObject($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return state_changes::getStateChangesForObjectPack($glob); }
    public static function getStateChangesForObjectPack($pack)
    {
        $sql_stateChanges = dbconnection::queryArray("SELECT * FROM state_changes WHERE object_type = '{$pack->object_type}' AND object_id = '{$pack->object_id}'");
        $stateChanges = array();
        for($i = 0; $i < count($sql_stateChanges); $i++)
            $stateChanges[] = state_changes::stateChangeObjectFromSQL($sql_stateChanges[$i]);

        return new return_package(0,$stateChanges);
    }

    public static function deleteStateChange($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return state_changes::deleteStateChangePack($glob); }
    public static function deleteStateChangePack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM state_changes WHERE state_change_id = '{$pack->state_change_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM state_changes WHERE state_change_id = '{$pack->state_change_id}' LIMIT 1");
        return new return_package(0);
    }
}
?>
