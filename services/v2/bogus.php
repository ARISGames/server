<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

class bogus extends dbconnection
{
    //Takes in item JSON, all fields optional except game_id + user_id + key
    public static function doBogusThing($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return bogus::doBogusThingPack($glob); }
    public static function doBogusThingPack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        return new return_package(0);
    }
}
?>
