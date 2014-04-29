<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

class state_changes extends dbconnection
{	
    //Takes in state_change JSON, all fields optional except game_id + user_id + key
    public static function createStateChangeJSON($glob)
    {
        $data = file_get_contents("php://input");
        $glob = json_decode($data);
        return state_changes::createStateChange($glob);
    }

    public static function createStateChange($pack)
    {
        if(!editors::authenticateGameEditor($pack->game_id, $pack->auth->user_id, $pack->auth->key, "read_write"))
            return new return_package(6, NULL, "Failed Authentication");

        $stateChangeId = dbconnection::queryInsert(
            "INSERT INTO state_changes (".
            "game_id,".
            ($pack->action      ? "action,"      : "").
            ($pack->amount      ? "amount,"      : "").
            ($pack->object_type ? "object_type," : "").
            ($pack->object_id   ? "object_id,"   : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            ($pack->action      ? "'".addslashes($pack->action)."',"      : "").
            ($pack->amount      ? "'".addslashes($pack->amount)."',"      : "").
            ($pack->object_type ? "'".addslashes($pack->object_type)."'," : "").
            ($pack->object_id   ? "'".addslashes($pack->object_id)."',"   : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return state_changes::getStateChange($stateChangeId);
    }

    //Takes in game JSON, all fields optional except state_change_id + user_id + key
    public static function updateStateChangeJSON($glob)
    {
        $data = file_get_contents("php://input");
        $glob = json_decode($data);
        return state_changes::updateStateChange($glob);
    }

    public static function updateStateChange($pack)
    {
        $gameId = dbconnection::queryObject("SELECT * FROM state_changes WHERE state_change_id = '{$pack->state_change_id}'")->game_id;
        if(!editors::authenticateGameEditor($gameId, $pack->auth->user_id, $pack->auth->key, "read_write"))
            return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE state_changes SET ".
            ($pack->action      ? "action      = '".addslashes($pack->action)."', "      : "").
            ($pack->amount      ? "amount      = '".addslashes($pack->amount)."', "      : "").
            ($pack->object_type ? "object_type = '".addslashes($pack->object_type)."', " : "").
            ($pack->object_id   ? "object_id   = '".addslashes($pack->object_id)."', "   : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE state_change_id = '{$pack->state_change_id}'"
        );

        return state_changes::getStateChange($pack->state_change_id);
    }

    public static function getStateChange($stateChangeId)
    {
        $sql_stateChange = dbconnection::queryObject("SELECT * FROM state_changes WHERE state_change_id = '{$stateChangeId}' LIMIT 1");

        $stateChange = new stdClass();
        $stateChange->state_change_id = $sql_stateChange->state_change_id;
        $stateChange->game_id         = $sql_stateChange->game_id;
        $stateChange->action          = $sql_stateChange->action;
        $stateChange->amount          = $sql_stateChange->amount;
        $stateChange->object_type     = $sql_stateChange->object_type;
        $stateChange->object_id       = $sql_stateChange->object_id;

        return new return_package(0,$stateChange);
    }

    public static function deleteStateChange($stateChangeId, $userId, $key)
    {
        $gameId = dbconnection::queryObject("SELECT * FROM state_changes WHERE state_change_id = '{$stateChangeId}'")->game_id;
        if(!editors::authenticateGameEditor($gameId, $userId, $key, "read_write")) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM state_changes WHERE state_change_id = '{$stateChangeId}' LIMIT 1");
        return new return_package(0);
    }
}
?>
