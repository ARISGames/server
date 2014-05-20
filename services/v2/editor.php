<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("triggers.php");
require_once("return_package.php");

class editor extends dbconnection
{
    //NOT ACTUALLY USING THIS- STILL HERE JUST FOR EXAMPLE
    public static function createTriggerAndInstance($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return triggers::createTriggerAndInstancePack($glob); }
    public static function createTriggerAndInstancePack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

    }
}
?>
