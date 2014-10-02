<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("return_package.php");

class editors extends dbconnection
{
    //Used by other services
    public function authenticateGameEditor($pack)
    {
        if(!users::authenticateUser($pack)) return false;
        if(dbconnection::queryObject("SELECT * FROM user_games WHERE user_id = '{$pack->user_id}' AND game_id = '{$pack->game_id}'")) return true;
        util::serverErrorLog("Failed Game Editor Authentication!"); return false;
    }

    public static function addEditorToGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return editors::addEditorToGamePack($glob); }
    public static function addEditorToGamePack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        //note $pack->user_id is DIFFERENT than $pack->auth->user_id
        dbconnection::queryInsert("INSERT INTO user_games (game_id, user_id, created) VALUES ('{$pack->game_id}','{$pack->user_id}',CURRENT_TIMESTAMP)");
        return new return_package(0);	
    }

    public static function removeEditorFromGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return editors::removeEditorFromGamePack($glob); }
    public static function removeEditorFromGamePack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        //note $pack->user_id is DIFFERENT than $pack->auth->user_id
        dbconnection::query("DELETE FROM user_games WHERE user_id = '{$pack->user_id}' AND game_id = '{$pack->game_id}'");
        return new return_package(0);
    }

    public static function getEditorsForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return editors::getEditorsForGamePack($glob); }
    public static function getEditorsForGamePack($pack)
    {
        $editors = dbconnection::queryArray("SELECT user_id FROM user_games WHERE game_id = '{$pack->game_id}'");
        return new return_package(0,$editors);
    }
}
?>
