<?php

require_once("dbconnection.php");
require_once("returnData.php");
require_once("editors.php");

class triggers extends dbconnection
{	
    //Takes in game JSON, all fields optional except user_id + token
    public static function createTriggerJSON($glob)
    {
        $data = file_get_contents("php://input");
        $glob = json_decode($data);
        return games::createTrigger($glob);
    }

    public static function createTrigger($pack)
    {
        if(!editors::authenticateGameEditor($pack->game_id, $pack->auth->user_id, $pack->auth->token, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $triggerId = dbconnection::queryInsert(
            "INSERT INTO triggers (".
            "game_id,".
            ($pack->name ? "name," : "").
            "created".
            ") VALUES (".
            "'".$pack->game_id."',".
            ($pack->name ? "'".addslashes($pack->name)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return triggers::getTrigger($triggerId);
    }

    //Takes in game JSON, all fields optional except user_id + token
    public static function updateTriggerJSON($glob)
    {
        $data = file_get_contents("php://input");
        $glob = json_decode($data);
        return games::updateTrigger($glob);
    }

    public static function updateTrigger($pack)
    {
        $gameId = dbconnection::queryObject("SELECT * FROM triggers WHERE trigger_id = '{$pack->trigger_id}'")->game_id;
        if(!editors::authenticateGameEditor($gameId, $pack->auth->user_id, $pack->auth->token, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $triggerId = dbconnection::queryInsert(
            "UPDATE triggers SET ".
            ($pack->name ? "name = '{$pack->name}', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE trigger_id = '{$pack->trigger_id}'".
        );

        return triggers::getTrigger($triggerId);
    }

    public static function getTrigger($triggerId)
    {
        $sql_trigger = dbconnection::queryObject("SELECT * FROM triggers WHERE trigger_id = '{$triggerId}' LIMIT 1");

        $trigger = new stdClass();
        $trigger->trigger_id = $sql_trigger->trigger_id;
        $trigger->game_id = $sql_trigger->game_id;
        $trigger->name = $sql_trigger->name;

        return new returnData(0,$trigger);
    }

    public static function deleteTrigger($triggerId, $userId, $token)
    {
        $gameId = dbconnection::queryObject("SELECT * FROM triggers WHERE trigger_id = '{$triggerId}'")->game_id;
        if(!editors::authenticateGameEditor($gameId, $userId, $token, "read_write")) return new returnData(6, NULL, "Failed Authentication");

        dbconnection::queryObject("DELETE FROM triggers WHERE trigger_id = '{$triggerId}' LIMIT 1");
    }
}
?>
