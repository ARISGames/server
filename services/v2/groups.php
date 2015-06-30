<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

class groups extends dbconnection
{
    //Takes in group JSON, all fields optional except game_id + user_id + key
    public static function createGroup($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->group_id = dbconnection::queryInsert(
            "INSERT INTO groups (".
            "game_id,".
            (isset($pack->name)        ? "name,"        : "").
            (isset($pack->description) ? "description," : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->name)        ? "'".addslashes($pack->name)."',"        : "").
            (isset($pack->description) ? "'".addslashes($pack->description)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return groups::getGroup($pack);
    }

    //Takes in game JSON, all fields optional except group_id + user_id + key
    public static function updateGroup($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM groups WHERE group_id = '{$pack->group_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE groups SET ".
            (isset($pack->name)             ? "name             = '".addslashes($pack->name)."', "             : "").
            (isset($pack->description)      ? "description      = '".addslashes($pack->description)."', "      : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE group_id = '{$pack->group_id}'"
        );

        return groups::getGroup($pack);
    }

    private static function groupObjectFromSQL($sql_group)
    {
        if(!$sql_group) return $sql_group;
        $group = new stdClass();
        $group->group_id        = $sql_group->group_id;
        $group->game_id          = $sql_group->game_id;
        $group->name             = $sql_group->name;
        $group->description      = $sql_group->description;

        return $group;
    }

    public static function getGroup($pack)
    {
        $sql_group = dbconnection::queryObject("SELECT * FROM groups WHERE group_id = '{$pack->group_id}' LIMIT 1");
        return new return_package(0,groups::groupObjectFromSQL($sql_group));
    }

    public static function getGroupsForGame($pack)
    {
        $sql_groups = dbconnection::queryArray("SELECT * FROM groups WHERE game_id = '{$pack->game_id}'");
        $groups = array();
        for($i = 0; $i < count($sql_groups); $i++)
            if($ob = groups::groupObjectFromSQL($sql_groups[$i])) $groups[] = $ob;

        return new return_package(0,$groups);
    }

    public static function deleteGroup($pack)
    {
        $group = dbconnection::queryObject("SELECT * FROM groups WHERE group_id = '{$pack->group_id}'");
        $pack->auth->game_id = $group->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM groups WHERE group_id = '{$pack->group_id}' LIMIT 1");

        //cleanup
        dbconnection::query("UPDATE game_user_groups SET group_id = 0 WHERE game_id = '{$group->game_id}' AND group_id = '{$group->group_id}';");

        return new return_package(0);
    }
}
?>
