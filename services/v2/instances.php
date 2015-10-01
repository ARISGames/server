<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

require_once("triggers.php");

class instances extends dbconnection
{
    //Takes in instance JSON, all fields optional except user_id + key
    public static function createInstance($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->instance_id = dbconnection::queryInsert(
            "INSERT INTO instances (".
            "game_id,".
            (isset($pack->object_type)  ? "object_type,"  : "").
            (isset($pack->object_id)    ? "object_id,"    : "").
            (isset($pack->qty)          ? "qty,"          : "").
            (isset($pack->infinite_qty) ? "infinite_qty," : "").
            (isset($pack->factory_id)   ? "factory_id,"   : "").
            (isset($pack->owner_type)   ? "owner_type,"     : "").
            (isset($pack->owner_id)     ? "owner_id,"     : "").
            "created".
            ") VALUES (".
            "'".$pack->game_id."',".
            (isset($pack->object_type)  ? "'".addslashes($pack->object_type)."',"  : "").
            (isset($pack->object_id)    ? "'".addslashes($pack->object_id)."',"    : "").
            (isset($pack->qty)          ? "'".addslashes($pack->qty)."',"          : "").
            (isset($pack->infinite_qty) ? "'".addslashes($pack->infinite_qty)."'," : "").
            (isset($pack->factory_id)   ? "'".addslashes($pack->factory_id)."',"   : "").
            (isset($pack->owner_type)   ? "'".addslashes($pack->owner_type)."',"   : "").
            (isset($pack->owner_id)     ? "'".addslashes($pack->owner_id)."',"     : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return instances::getInstance($pack);
    }

    //Takes in game JSON, all fields optional except user_id + key
    public static function updateInstance($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM instances WHERE instance_id = '{$pack->instance_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE instances SET ".
            (isset($pack->object_type)  ? "object_type  = '".addslashes($pack->object_type)."', "  : "").
            (isset($pack->object_id)    ? "object_id    = '".addslashes($pack->object_id)."', "    : "").
            (isset($pack->qty)          ? "qty          = '".addslashes($pack->qty)."', "          : "").
            (isset($pack->infinite_qty) ? "infinite_qty = '".addslashes($pack->infinite_qty)."', " : "").
            (isset($pack->factory_id)   ? "factory_id   = '".addslashes($pack->factory_id)."', "   : "").
            (isset($pack->owner_type)   ? "owner_type   = '".addslashes($pack->owner_type)."', "   : "").
            (isset($pack->owner_id)     ? "owner_id     = '".addslashes($pack->owner_id)."', "     : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE instance_id = '{$pack->instance_id}'"
        );

        return instances::getInstance($pack);
    }

    private static function instanceObjectFromSQL($sql_instance)
    {
        if(!$sql_instance) return $sql_instance;
        $instance = new stdClass();
        $instance->instance_id  = $sql_instance->instance_id;
        $instance->game_id      = $sql_instance->game_id;
        $instance->object_type  = $sql_instance->object_type;
        $instance->object_id    = $sql_instance->object_id;
        $instance->qty          = $sql_instance->qty;
        $instance->infinite_qty = $sql_instance->infinite_qty;
        $instance->factory_id   = $sql_instance->factory_id;
        $instance->owner_type   = $sql_instance->owner_type;
        $instance->owner_id     = $sql_instance->owner_id;
        $instance->created      = $sql_instance->created; //needed for local factory invalidation
        return $instance;
    }

    public static function getInstance($pack)
    {
        $sql_instance = dbconnection::queryObject("SELECT * FROM instances WHERE instance_id = '{$pack->instance_id}' LIMIT 1");
        return new return_package(0,instances::instanceObjectFromSQL($sql_instance));
    }

    // Added for MHS role distribution. Would be nice as 'takeQtyFromGame' these succeed even if would not give amount required.
    public static function takeQtyFromInstance($pack)
    {
        $query = "UPDATE instances set qty = if(CAST(qty AS SIGNED) - '{$pack->qty}' < 0, 0, qty - '{$pack->qty}') where instance_id = '{$pack->instance_id}'";
        dbconnection::query($query);

        $sql_instance = dbconnection::queryObject("SELECT * FROM instances WHERE instance_id = '{$pack->instance_id}' LIMIT 1");
        return new return_package(0,instances::instanceObjectFromSQL($sql_instance));
    }

    public static function giveQtyToInstance($pack)
    {
        $query = "UPDATE instances set qty = qty + '{$pack->qty}' where instance_id = '{$pack->instance_id}'";
        dbconnection::query($query);

        $sql_instance = dbconnection::queryObject("SELECT * FROM instances WHERE instance_id = '{$pack->instance_id}' LIMIT 1");
        return new return_package(0,instances::instanceObjectFromSQL($sql_instance));
    }

    public static function getInstancesForGame($pack)
    {
        // Return game owned, game_content owned, or specific player owned.
        $sql_instances = dbconnection::queryArray("SELECT * FROM instances WHERE game_id = '{$pack->game_id}' AND ((owner_type = 'USER' AND owner_id = '".(isset($pack->owner_id) ? $pack->owner_id : 0)."') OR owner_type != 'USER')");
        $instances = array();
        for($i = 0; $i < count($sql_instances); $i++)
            if($ob = instances::instanceObjectFromSQL($sql_instances[$i])) $instances[] = $ob;

        return new return_package(0,$instances);
    }

    public static function getInstancesForObject($pack)
    {
        $sql_instances = dbconnection::queryArray("SELECT * FROM instances WHERE game_id = '{$pack->game_id}' AND object_type = '{$pack->object_type}' AND object_id = '{$pack->object_id}'");
        $instances = array();
        for($i = 0; $i < count($sql_instances); $i++)
            if($ob = instances::instanceObjectFromSQL($sql_instances[$i])) $instances[] = $ob;

        return new return_package(0,$instances);
    }

    public static function deleteInstance($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM instances WHERE instance_id = '{$pack->instance_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        return instances::noauth_deleteInstance($pack);
    }

    //this is a security risk...
    public static function noauth_deleteInstance($pack)
    {
        //and this "fixes" the security risk...
        if(strpos($_SERVER['REQUEST_URI'],'noauth') !== false) return new return_package(6, NULL, "Attempt to bypass authentication externally.");

        dbconnection::query("DELETE FROM instances WHERE instance_id = '{$pack->instance_id}' LIMIT 1");
        //cleanup
        $triggers = dbconnection::queryArray("SELECT * FROM triggers WHERE instance_id = '{$pack->instance_id}'");
        for($i = 0; $i < count($triggers); $i++)
        {
            $pack->trigger_id = $triggers[$i]->trigger_id;
            triggers::noauth_deleteTrigger($pack);
        }

        return new return_package(0);
    }
}

?>
