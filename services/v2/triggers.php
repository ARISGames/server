<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

class triggers extends dbconnection
{	
    //Takes in trigger JSON, all fields optional except user_id + key
    public static function createTrigger($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return triggers::createTriggerPack($glob); }
    public static function createTriggerPack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");
    
        $pack->trigger_id = dbconnection::queryInsert(
            "INSERT INTO triggers (".
            "game_id,".
            ($pack->name                        ? "name,"                        : "").
            ($pack->instance_id                 ? "instance_id,"                 : "").
            ($pack->scene_id                    ? "scene_id,"                    : "").
            ($pack->requirement_root_package_id ? "requirement_root_package_id," : "").
            ($pack->type                        ? "type,"                        : "").
            ($pack->latitude                    ? "latitude,"                    : "").
            ($pack->longitude                   ? "longitude,"                   : "").
            ($pack->distance                    ? "distance,"                    : "").
            ($pack->wiggle                      ? "wiggle,"                      : "").
            ($pack->show_title                  ? "show_title,"                  : "").
            ($pack->qr_code                     ? "qr_code,"                     : "").
            "created".
            ") VALUES (".
            "'".$pack->game_id."',".
            ($pack->name                        ? "'".addslashes($pack->name)."',"                        : "").
            ($pack->instance_id                 ? "'".addslashes($pack->instance_id)."',"                 : "").
            ($pack->scene_id                    ? "'".addslashes($pack->scene_id)."',"                    : "").
            ($pack->requirement_root_package_id ? "'".addslashes($pack->requirement_root_package_id)."'," : "").
            ($pack->type                        ? "'".addslashes($pack->type)."',"                        : "").
            ($pack->latitude                    ? "'".addslashes($pack->latitude)."',"                    : "").
            ($pack->longitude                   ? "'".addslashes($pack->longitude)."',"                   : "").
            ($pack->distance                    ? "'".addslashes($pack->distance)."',"                    : "").
            ($pack->wiggle                      ? "'".addslashes($pack->wiggle)."',"                      : "").
            ($pack->show_title                  ? "'".addslashes($pack->show_title)."',"                  : "").
            ($pack->qr_code                     ? "'".addslashes($pack->qr_code)."',"                     : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return triggers::getTriggerPack($pack);
    }

    //Takes in game JSON, all fields optional except user_id + key
    public static function updateTrigger($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return triggers::updateTriggerPack($glob); }
    public static function updateTriggerPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM triggers WHERE trigger_id = '{$pack->trigger_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE triggers SET ".
            ($pack->name                        ? "name                        = '".addslashes($pack->name)."', "                        : "").
            ($pack->instance_id                 ? "instance_id                 = '".addslashes($pack->instance_id)."', "                 : "").
            ($pack->scene_id                    ? "scene_id                    = '".addslashes($pack->scene_id)."', "                    : "").
            ($pack->requirement_root_package_id ? "requirement_root_package_id = '".addslashes($pack->requirement_root_package_id)."', " : "").
            ($pack->type                        ? "type                        = '".addslashes($pack->type)."', "                        : "").
            ($pack->latitude                    ? "latitude                    = '".addslashes($pack->latitude)."', "                    : "").
            ($pack->longitude                   ? "longitude                   = '".addslashes($pack->longitude)."', "                   : "").
            ($pack->distance                    ? "distance                    = '".addslashes($pack->distance)."', "                    : "").
            ($pack->wiggle                      ? "wiggle                      = '".addslashes($pack->wiggle)."', "                      : "").
            ($pack->show_title                  ? "show_title                  = '".addslashes($pack->show_title)."', "                  : "").
            ($pack->qr_code                     ? "qr_code                     = '".addslashes($pack->qr_code)."', "                     : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE trigger_id = '{$pack->trigger_id}'"
        );

        return triggers::getTriggerPack($pack);
    }

    private static function triggerObjectFromSQL($sql_trigger)
    {
        $trigger = new stdClass();
        $trigger->trigger_id                  = $sql_trigger->trigger_id;
        $trigger->game_id                     = $sql_trigger->game_id;
        $trigger->name                        = $sql_trigger->name;
        $trigger->instance_id                 = $sql_trigger->instance_id;
        $trigger->scene_id                    = $sql_trigger->scene_id;
        $trigger->requirement_root_package_id = $sql_trigger->requirement_root_package_id;
        $trigger->type                        = $sql_trigger->type;
        $trigger->latitude                    = $sql_trigger->latitude;
        $trigger->longitude                   = $sql_trigger->longitude;
        $trigger->distance                    = $sql_trigger->distance;
        $trigger->wiggle                      = $sql_trigger->wiggle;
        $trigger->show_title                  = $sql_trigger->show_title;
        $trigger->qr_code                     = $sql_trigger->qr_code;

        return $trigger;
    }

    public static function getTrigger($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return triggers::getTriggerPack($glob); }
    public static function getTriggerPack($pack)
    {
        $sql_trigger = dbconnection::queryObject("SELECT * FROM triggers WHERE trigger_id = '{$pack->trigger_id}' LIMIT 1");
        return new return_package(0,triggers::triggerObjectFromSQL($sql_trigger));
    }

    public static function getTriggersForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return triggers::getTriggersForGamePack($glob); }
    public static function getTriggersForGamePack($pack)
    {
        $sql_triggers = dbconnection::queryArray("SELECT * FROM triggers WHERE game_id = '{$pack->game_id}'");
        $triggers = array();
        for($i = 0; $i < count($sql_triggers); $i++)
            $triggers[] = triggers::triggerObjectFromSQL($sql_triggers[$i]);

        return new return_package(0,$triggers);
    }

    public static function getTriggersForScene($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return triggers::getTriggersForScenePack($glob); }
    public static function getTriggersForScenePack($pack)
    {
        $sql_triggers = dbconnection::queryArray("SELECT * FROM triggers WHERE scene_id = '{$pack->scene_id}'");
        $triggers = array();
        for($i = 0; $i < count($sql_triggers); $i++)
            $triggers[] = triggers::triggerObjectFromSQL($sql_triggers[$i]);

        return new return_package(0,$triggers);
    }

    public static function getTriggersForInstance($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return triggers::getTriggersForInstancePack($glob); }
    public static function getTriggersForInstancePack($pack)
    {
        $sql_triggers = dbconnection::queryArray("SELECT * FROM triggers WHERE instance_id = '{$instanceId}'");
        $triggers = array();
        for($i = 0; $i < count($sql_triggers); $i++)
            $triggers[] = triggers::triggerObjectFromSQL($sql_trigger);

        return new return_package(0,$triggers);
    }

    public static function deleteTrigger($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return triggers::deleteTriggerPack($glob); }
    public static function deleteTriggerPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM triggers WHERE trigger_id = '{$pack->trigger_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM triggers WHERE trigger_id = '{$pack->trigger_id}' LIMIT 1");
        return new return_package(0);
    }
}
?>
