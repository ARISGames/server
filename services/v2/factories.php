<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

class factories extends dbconnection
{	
    //Takes in factory JSON, all fields optional except game_id + user_id + key
    public static function createFactory($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return factories::createFactoryPack($glob); }
    public static function createFactoryPack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->factory_id = dbconnection::queryInsert(
            "INSERT INTO factories (".
            "game_id,".
            (isset($pack->name)                                ? "name,"                                : "").
            (isset($pack->description)                         ? "description,"                         : "").
            (isset($pack->object_type)                         ? "object_type,"                         : "").
            (isset($pack->object_id)                           ? "object_id,"                           : "").
            (isset($pack->seconds_per_production)              ? "seconds_per_production,"              : "").
            (isset($pack->production_probability)              ? "production_probability,"              : "").
            (isset($pack->max_production)                      ? "max_production,"                      : "").
            (isset($pack->produce_expiration_time)             ? "produce_expiration_time,"             : "").
            (isset($pack->produce_expire_on_view)              ? "produce_expire_on_view,"              : "").
            (isset($pack->production_bound_type)               ? "production_bound_type,"               : "").
            (isset($pack->location_bound_type)                 ? "location_bound_type,"                 : "").
            (isset($pack->min_production_distance)             ? "min_production_distance,"             : "").
            (isset($pack->max_production_distance)             ? "max_production_distance,"             : "").
            (isset($pack->requirement_root_package_id)         ? "requirement_root_package_id,"         : "").
            (isset($pack->trigger_latitude)                    ? "trigger_latitude,"                    : "").
            (isset($pack->trigger_longitude)                   ? "trigger_longitude,"                   : "").
            (isset($pack->trigger_distance)                    ? "trigger_distance,"                    : "").
            (isset($pack->trigger_on_enter)                    ? "trigger_on_enter,"                    : "").
            (isset($pack->trigger_hidden)                      ? "trigger_hidden,"                      : "").
            (isset($pack->trigger_wiggle)                      ? "trigger_wiggle,"                      : "").
            (isset($pack->trigger_title)                       ? "trigger_title,"                       : "").
            (isset($pack->trigger_icon_media_id)               ? "trigger_icon_media_id,"               : "").
            (isset($pack->trigger_show_title)                  ? "trigger_show_title,"                  : "").
            (isset($pack->trigger_requirement_root_package_id) ? "trigger_requirement_root_package_id," : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->name)                                ? "'".addslashes($pack->name)."',"                                : "").
            (isset($pack->description)                         ? "'".addslashes($pack->description)."',"                         : "").
            (isset($pack->object_type)                         ? "'".addslashes($pack->object_type)."',"                         : "").
            (isset($pack->object_id)                           ? "'".addslashes($pack->object_id)."',"                           : "").
            (isset($pack->seconds_per_production)              ? "'".addslashes($pack->seconds_per_production)."',"               : "").
            (isset($pack->production_probability)              ? "'".addslashes($pack->production_probability)."',"              : "").
            (isset($pack->max_production)                      ? "'".addslashes($pack->max_production)."',"                      : "").
            (isset($pack->produce_expiration_time)             ? "'".addslashes($pack->produce_expiration_time)."',"             : "").
            (isset($pack->produce_expire_on_view)              ? "'".addslashes($pack->produce_expire_on_view)."',"              : "").
            (isset($pack->production_bound_type)               ? "'".addslashes($pack->production_bound_type)."',"               : "").
            (isset($pack->location_bound_type)                 ? "'".addslashes($pack->location_bound_type)."',"                 : "").
            (isset($pack->min_production_distance)             ? "'".addslashes($pack->min_production_distance)."',"             : "").
            (isset($pack->max_production_distance)             ? "'".addslashes($pack->max_production_distance)."',"             : "").
            (isset($pack->requirement_root_package_id)         ? "'".addslashes($pack->requirement_root_package_id)."',"         : "").
            (isset($pack->trigger_latitude)                    ? "'".addslashes($pack->trigger_latitude)."',"                    : "").
            (isset($pack->trigger_longitude)                   ? "'".addslashes($pack->trigger_longitude)."',"                   : "").
            (isset($pack->trigger_distance)                    ? "'".addslashes($pack->trigger_distance)."',"                    : "").
            (isset($pack->trigger_on_enter)                    ? "'".addslashes($pack->trigger_on_enter)."',"                    : "").
            (isset($pack->trigger_hidden)                      ? "'".addslashes($pack->trigger_hidden)."',"                      : "").
            (isset($pack->trigger_wiggle)                      ? "'".addslashes($pack->trigger_wiggle)."',"                      : "").
            (isset($pack->trigger_title)                       ? "'".addslashes($pack->trigger_title)."',"                       : "").
            (isset($pack->trigger_icon_media_id)               ? "'".addslashes($pack->trigger_icon_media_id)."',"               : "").
            (isset($pack->trigger_show_title)                  ? "'".addslashes($pack->trigger_show_title)."',"                  : "").
            (isset($pack->trigger_requirement_root_package_id) ? "'".addslashes($pack->trigger_requirement_root_package_id)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return factories::getFactoryPack($pack);
    }

    //Takes in game JSON, all fields optional except factory_id + user_id + key
    public static function updateFactory($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return factories::updateFactoryPack($glob); }
    public static function updateFactoryPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM factories WHERE factory_id = '{$pack->factory_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE factories SET ".
            (isset($pack->name)                                ? "name                                = '".addslashes($pack->name)."',"                                : "").
            (isset($pack->description)                         ? "description                         = '".addslashes($pack->description)."',"                         : "").
            (isset($pack->object_type)                         ? "object_type                         = '".addslashes($pack->object_type)."',"                         : "").
            (isset($pack->object_id)                           ? "object_id                           = '".addslashes($pack->object_id)."',"                           : "").
            (isset($pack->seconds_per_production)              ? "seconds_per_production               = '".addslashes($pack->seconds_per_production)."',"             : "").
            (isset($pack->production_probability)              ? "production_probability              = '".addslashes($pack->production_probability)."',"              : "").
            (isset($pack->max_production)                      ? "max_production                      = '".addslashes($pack->max_production)."',"                      : "").
            (isset($pack->produce_expiration_time)             ? "produce_expiration_time             = '".addslashes($pack->produce_expiration_time)."',"             : "").
            (isset($pack->produce_expire_on_view)              ? "produce_expire_on_view              = '".addslashes($pack->produce_expire_on_view)."',"              : "").
            (isset($pack->production_bound_type)               ? "production_bound_type               = '".addslashes($pack->production_bound_type)."',"               : "").
            (isset($pack->location_bound_type)                 ? "location_bound_type                 = '".addslashes($pack->location_bound_type)."',"                 : "").
            (isset($pack->min_production_distance)             ? "min_production_distance             = '".addslashes($pack->min_production_distance)."',"             : "").
            (isset($pack->max_production_distance)             ? "max_production_distance             = '".addslashes($pack->max_production_distance)."',"             : "").
            (isset($pack->requirement_root_package_id)         ? "requirement_root_package_id         = '".addslashes($pack->requirement_root_package_id)."',"         : "").
            (isset($pack->trigger_latitude)                    ? "trigger_latitude                    = '".addslashes($pack->trigger_latitude)."',"                    : "").
            (isset($pack->trigger_longitude)                   ? "trigger_longitude                   = '".addslashes($pack->trigger_longitude)."',"                   : "").
            (isset($pack->trigger_distance)                    ? "trigger_distance                    = '".addslashes($pack->trigger_distance)."',"                    : "").
            (isset($pack->trigger_on_enter)                    ? "trigger_on_enter                    = '".addslashes($pack->trigger_on_enter)."',"                    : "").
            (isset($pack->trigger_hidden)                      ? "trigger_hidden                      = '".addslashes($pack->trigger_hidden)."',"                      : "").
            (isset($pack->trigger_wiggle)                      ? "trigger_wiggle                      = '".addslashes($pack->trigger_wiggle)."',"                      : "").
            (isset($pack->trigger_title)                       ? "trigger_title                       = '".addslashes($pack->trigger_title)."',"                       : "").
            (isset($pack->trigger_icon_media_id)               ? "trigger_icon_media_id               = '".addslashes($pack->trigger_icon_media_id)."',"               : "").
            (isset($pack->trigger_show_title)                  ? "trigger_show_title                  = '".addslashes($pack->trigger_show_title)."',"                  : "").
            (isset($pack->trigger_requirement_root_package_id) ? "trigger_requirement_root_package_id = '".addslashes($pack->trigger_requirement_root_package_id)."'," : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE factory_id = '{$pack->factory_id}'"
        );

        return factories::getFactoryPack($pack);
    }

    private static function factoryObjectFromSQL($sql_factory)
    {
        if(!$sql_factory) return $sql_factory;
        $factory = new stdClass();
        $factory->factory_id                          = $sql_factory->factory_id;
        $factory->game_id                             = $sql_factory->game_id;
        $factory->name                                = $sql_factory->name;
        $factory->description                         = $sql_factory->description;
        $factory->object_type                         = $sql_factory->object_type;
        $factory->object_id                           = $sql_factory->object_id;
        $factory->seconds_per_production              = $sql_factory->seconds_per_production;
        $factory->production_probability              = $sql_factory->production_probability;
        $factory->max_production                      = $sql_factory->max_production;
        $factory->produce_expiration_time             = $sql_factory->produce_expiration_time;
        $factory->produce_expire_on_view              = $sql_factory->produce_expire_on_view;
        $factory->production_bound_type               = $sql_factory->production_bound_type;
        $factory->location_bound_type                 = $sql_factory->location_bound_type;
        $factory->min_production_distance             = $sql_factory->min_production_distance;
        $factory->max_production_distance             = $sql_factory->max_production_distance;
        $factory->requirement_root_package_id         = $sql_factory->requirement_root_package_id;
        $factory->trigger_latitude                    = $sql_factory->trigger_latitude;
        $factory->trigger_longitude                   = $sql_factory->trigger_longitude;
        $factory->trigger_distance                    = $sql_factory->trigger_distance;
        $factory->trigger_on_enter                    = $sql_factory->trigger_on_enter;
        $factory->trigger_hidden                      = $sql_factory->trigger_hidden;
        $factory->trigger_wiggle                      = $sql_factory->trigger_wiggle;
        $factory->trigger_title                       = $sql_factory->trigger_title;
        $factory->trigger_icon_media_id               = $sql_factory->trigger_icon_media_id;
        $factory->trigger_show_title                  = $sql_factory->trigger_show_title;
        $factory->trigger_requirement_root_package_id = $sql_factory->trigger_requirement_root_package_id;

        return $factory;
    }

    public static function getFactory($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return factories::getFactoryPack($glob); }
    public static function getFactoryPack($pack)
    {
        $sql_factory = dbconnection::queryObject("SELECT * FROM factories WHERE factory_id = '{$pack->factory_id}' LIMIT 1");
        return new return_package(0,factories::factoryObjectFromSQL($sql_factory));
    }

    public static function getFactoriesForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return factories::getFactoriesForGamePack($glob); }
    public static function getFactoriesForGamePack($pack)
    {
        $sql_factories = dbconnection::queryArray("SELECT * FROM factories WHERE game_id = '{$pack->game_id}'");
        $factories = array();
        for($i = 0; $i < count($sql_factories); $i++)
            if($ob = factories::factoryObjectFromSQL($sql_factories[$i])) $factories[] = $ob;
        
        return new return_package(0,$factories);
    }

    public static function deleteFactory($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return factories::deleteFactoryPack($glob); }
    public static function deleteFactoryPack($pack)
    {
        $factory = dbconnection::queryObject("SELECT * FROM factories WHERE factory_id = '{$pack->factory_id}'");
        $pack->auth->game_id = $factory->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM factories WHERE factory_id = '{$pack->factory_id}' LIMIT 1");
        //cleanup
        $instances = dbconnection::queryArray("SELECT * FROM instances WHERE factory_id = '{$pack->factory_id}'");
        for($i = 0; $i < count($instances); $i++)
        {
            $pack->instance_id = $instances[$i]->instance_id;
            instances::deleteInstancePack($pack);
        }

        $instances = dbconnection::queryArray("SELECT * FROM instances WHERE object_type = 'FACTORY' AND object_id = '{$pack->factory_id}'");
        for($i = 0; $i < count($instances); $i++)
        {
            $pack->instance_id = $instances[$i]->instance_id;
            instances::deleteInstancePack($pack);
        }

        $reqPack = dbconnection::queryObject("SELECT * FROM requirement_root_packages WHERE requirement_root_package_id = '{$factory->requirement_root_package_id}'");
        if($reqPack)
        {
            $pack->requirement_root_package_id = $reqPack->requirement_root_package_id;
            requirements::deleteRequirementPackagePack($pack);
        }

        $reqPack = dbconnection::queryObject("SELECT * FROM requirement_root_packages WHERE requirement_root_package_id = '{$factory->trigger_requirement_root_package_id}'");
        if($reqPack)
        {
            $pack->requirement_root_package_id = $reqPack->requirement_root_package_id;
            requirements::deleteRequirementPackagePack($pack);
        }

        return new return_package(0);
    }
}
?>
