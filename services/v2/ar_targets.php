<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("games.php");
require_once("return_package.php");

require_once("triggers.php");

class ar_targets extends dbconnection
{
    //Takes in ar_target JSON, all fields optional except game_id, user_id, key
    public static function createARTarget($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->ar_target_id = dbconnection::queryInsert(
            "INSERT INTO ar_targets (".
            "game_id,".
            (isset($pack->name)          ? "name,"          : "").
            (isset($pack->vuforia_index) ? "vuforia_index," : "").
            "created".
            ") VALUES (".
            "'".$pack->game_id."',".
            (isset($pack->name)          ? "'".addslashes($pack->name)."',"          : "").
            (isset($pack->vuforia_index) ? "'".addslashes($pack->vuforia_index)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        games::bumpGameVersion($pack);
        return ar_targets::getARTarget($pack);
    }

    //Takes in ar_target JSON, all fields optional except ar_target_id, user_id, key
    public static function updateARTarget($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM ar_targets WHERE ar_target_id = '{$pack->ar_target_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE ar_targets SET ".
            (isset($pack->name)          ? "name          = '".addslashes($pack->name)."', "          : "").
            (isset($pack->vuforia_index) ? "vuforia_index = '".addslashes($pack->vuforia_index)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE ar_target_id = '{$pack->ar_target_id}'"
        );

        games::bumpGameVersion($pack);
        return ar_targets::getARTarget($pack);
    }

    private static function arTargetObjectFromSQL($sql_ar_target)
    {
        if(!$sql_ar_target) return $sql_ar_target;
        $ar_target = new stdClass();
        $ar_target->ar_target_id  = $sql_ar_target->ar_target_id;
        $ar_target->game_id       = $sql_ar_target->game_id;
        $ar_target->name          = $sql_ar_target->name;
        $ar_target->vuforia_index = $sql_ar_target->vuforia_index;

        return $ar_target;
    }

    public static function getARTarget($pack)
    {
        $sql_ar_target = dbconnection::queryObject("SELECT * FROM ar_targets WHERE ar_target_id = '{$pack->ar_target_id}' LIMIT 1");
        return new return_package(0,ar_targets::arTargetObjectFromSQL($sql_ar_target));
    }

    public static function getARTargetsForGame($pack)
    {
        $sql_ar_targets = dbconnection::queryArray("SELECT * FROM ar_targets WHERE game_id = '{$pack->game_id}'");
        $ar_targets = array();
        for($i = 0; $i < count($sql_ar_targets); $i++)
            if($ob = ar_targets::arTargetObjectFromSQL($sql_ar_targets[$i])) $ar_targets[] = $ob;

        return new return_package(0,$ar_targets);
    }

    public static function deleteARTarget($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM ar_targets WHERE ar_target_id = '{$pack->ar_target_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM ar_targets WHERE ar_target_id = '{$pack->ar_target_id}' LIMIT 1");

        $triggers = dbconnection::queryArray("SELECT * FROM triggers WHERE type = 'AR' AND ar_target_id  = '{$pack->ar_target_id}'");
        for($i = 0; $i < count($triggers); $i++)
        {
            $pack->trigger_id = $triggers[$i]->trigger_id;
            triggers::deleteTrigger($pack);
        }

        games::bumpGameVersion($pack);
        return new return_package(0);
    }
}
?>
