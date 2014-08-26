<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("instances.php");
require_once("requirements.php");
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
            (isset($pack->instance_id)                 ? "instance_id,"                 : "").
            (isset($pack->scene_id)                    ? "scene_id,"                    : "").
            (isset($pack->requirement_root_package_id) ? "requirement_root_package_id," : "").
            (isset($pack->type)                        ? "type,"                        : "").
            (isset($pack->name)                        ? "name,"                        : "").
            (isset($pack->title)                       ? "title,"                       : "").
            (isset($pack->icon_media_id)               ? "icon_media_id,"               : "").
            (isset($pack->latitude)                    ? "latitude,"                    : "").
            (isset($pack->longitude)                   ? "longitude,"                   : "").
            (isset($pack->distance)                    ? "distance,"                    : "").
            (isset($pack->wiggle)                      ? "wiggle,"                      : "").
            (isset($pack->show_title)                  ? "show_title,"                  : "").
            (isset($pack->hidden)                      ? "hidden,"                      : "").
            (isset($pack->trigger_on_enter)            ? "trigger_on_enter,"            : "").
            (isset($pack->qr_code)                     ? "qr_code,"                     : "").
            "created".
            ") VALUES (".
            "'".$pack->game_id."',".
            (isset($pack->instance_id)                 ? "'".addslashes($pack->instance_id)."',"                 : "").
            (isset($pack->scene_id)                    ? "'".addslashes($pack->scene_id)."',"                    : "").
            (isset($pack->requirement_root_package_id) ? "'".addslashes($pack->requirement_root_package_id)."'," : "").
            (isset($pack->type)                        ? "'".addslashes($pack->type)."',"                        : "").
            (isset($pack->name)                        ? "'".addslashes($pack->name)."',"                        : "").
            (isset($pack->title)                       ? "'".addslashes($pack->title)."',"                       : "").
            (isset($pack->icon_media_id)               ? "'".addslashes($pack->icon_media_id)."',"               : "").
            (isset($pack->latitude)                    ? "'".addslashes($pack->latitude)."',"                    : "").
            (isset($pack->longitude)                   ? "'".addslashes($pack->longitude)."',"                   : "").
            (isset($pack->distance)                    ? "'".addslashes($pack->distance)."',"                    : "").
            (isset($pack->wiggle)                      ? "'".addslashes($pack->wiggle)."',"                      : "").
            (isset($pack->show_title)                  ? "'".addslashes($pack->show_title)."',"                  : "").
            (isset($pack->hidden)                      ? "'".addslashes($pack->hidden)."',"                      : "").
            (isset($pack->trigger_on_enter)            ? "'".addslashes($pack->trigger_on_enter)."',"            : "").
            (isset($pack->qr_code)                     ? "'".addslashes($pack->qr_code)."',"                     : "").
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
            (isset($pack->instance_id)                 ? "instance_id                 = '".addslashes($pack->instance_id)."', "                 : "").
            (isset($pack->scene_id)                    ? "scene_id                    = '".addslashes($pack->scene_id)."', "                    : "").
            (isset($pack->requirement_root_package_id) ? "requirement_root_package_id = '".addslashes($pack->requirement_root_package_id)."', " : "").
            (isset($pack->type)                        ? "type                        = '".addslashes($pack->type)."', "                        : "").
            (isset($pack->name)                        ? "name                        = '".addslashes($pack->name)."', "                        : "").
            (isset($pack->title)                       ? "title                       = '".addslashes($pack->title)."', "                       : "").
            (isset($pack->icon_media_id)               ? "icon_media_id               = '".addslashes($pack->icon_media_id)."', "               : "").
            (isset($pack->latitude)                    ? "latitude                    = '".addslashes($pack->latitude)."', "                    : "").
            (isset($pack->longitude)                   ? "longitude                   = '".addslashes($pack->longitude)."', "                   : "").
            (isset($pack->distance)                    ? "distance                    = '".addslashes($pack->distance)."', "                    : "").
            (isset($pack->wiggle)                      ? "wiggle                      = '".addslashes($pack->wiggle)."', "                      : "").
            (isset($pack->show_title)                  ? "show_title                  = '".addslashes($pack->show_title)."', "                  : "").
            (isset($pack->hidden)                      ? "hidden                      = '".addslashes($pack->hidden)."', "                      : "").
            (isset($pack->trigger_on_enter)            ? "trigger_on_enter            = '".addslashes($pack->trigger_on_enter)."', "            : "").
            (isset($pack->qr_code)                     ? "qr_code                     = '".addslashes($pack->qr_code)."', "                     : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE trigger_id = '{$pack->trigger_id}'"
        );

        return triggers::getTriggerPack($pack);
    }

    private static function triggerObjectFromSQL($sql_trigger)
    {
        if(!$sql_trigger) return $sql_trigger;
        $trigger = new stdClass();
        $trigger->trigger_id                  = $sql_trigger->trigger_id;
        $trigger->game_id                     = $sql_trigger->game_id;
        $trigger->instance_id                 = $sql_trigger->instance_id;
        $trigger->scene_id                    = $sql_trigger->scene_id;
        $trigger->requirement_root_package_id = $sql_trigger->requirement_root_package_id;
        $trigger->type                        = $sql_trigger->type;
        $trigger->name                        = $sql_trigger->name;
        $trigger->title                       = $sql_trigger->title;
        $trigger->icon_media_id               = $sql_trigger->icon_media_id;
        $trigger->latitude                    = $sql_trigger->latitude;
        $trigger->longitude                   = $sql_trigger->longitude;
        $trigger->distance                    = $sql_trigger->distance;
        $trigger->wiggle                      = $sql_trigger->wiggle;
        $trigger->show_title                  = $sql_trigger->show_title;
        $trigger->hidden                      = $sql_trigger->hidden;
        $trigger->trigger_on_enter            = $sql_trigger->trigger_on_enter;
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
            if($ob = triggers::triggerObjectFromSQL($sql_triggers[$i])) $triggers[] = $ob;

        return new return_package(0,$triggers);
    }

    public static function getTriggersForScene($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return triggers::getTriggersForScenePack($glob); }
    public static function getTriggersForScenePack($pack)
    {
        $sql_triggers = dbconnection::queryArray("SELECT * FROM triggers WHERE scene_id = '{$pack->scene_id}'");
        $triggers = array();
        for($i = 0; $i < count($sql_triggers); $i++)
            {
            $ob = triggers::triggerObjectFromSQL($sql_triggers[$i]);
            if($ob) $triggers[] = $ob;
            }

        return new return_package(0,$triggers);
    }

    public static function getTriggersForInstance($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return triggers::getTriggersForInstancePack($glob); }
    public static function getTriggersForInstancePack($pack)
    {
        $sql_triggers = dbconnection::queryArray("SELECT * FROM triggers WHERE instance_id = '{$instanceId}'");
        $triggers = array();
        for($i = 0; $i < count($sql_triggers); $i++)
            if($ob = triggers::triggerObjectFromSQL($sql_trigger)) $triggers[] = $ob;

        return new return_package(0,$triggers);
    }

    public static function deleteTrigger($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return triggers::deleteTriggerPack($glob); }
    public static function deleteTriggerPack($pack)
    {
        $trigger = dbconnection::queryObject("SELECT * FROM triggers WHERE trigger_id = '{$pack->trigger_id}'");
        $pack->auth->game_id = $trigger->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM triggers WHERE trigger_id = '{$pack->trigger_id}' LIMIT 1");
        //cleanup
        $instances = dbconnection::queryArray("SELECT * FROM instances WHERE instance_id = '{$trigger->instance_id}'");
        for($i = 0; $i < count($instances); $i++)
        {
            $pack->instance_id = $instances[$i]->instance_id;
            instances::deleteInstancePack($pack);
        }

        $reqPack = dbconnection::queryObject("SELECT * FROM requirement_root_packages WHERE requirement_root_package_id = '{$trigger->requirement_root_package_id}'");
        if($reqPack)
        {
            $pack->requirement_root_package_id = $reqPack->requirement_root_package_id;
            requirements::deleteRequirementPackagePack($pack);
        }

        return new return_package(0);
    }
}
?>
