<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("return_package.php");

class bogus extends dbconnection
{
    //Takes in item JSON, all fields optional except game_id + user_id + key
    public static function doBogusThing($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return bogus::doBogusThingPack($glob); }
    public static function doBogusThingPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $sql_games = dbconnection::queryArray("SELECT * FROM games");
        $games = array();
        for($i = 0; $i < count($sql_games); $i++)
            $games[] = $sql_games[$i];

        return new return_package(0,$games);
    }
}
?>
