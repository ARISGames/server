<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("games.php");
require_once("return_package.php");

class editors extends dbconnection
{
    public static function authenticateGameAccess($pack)
    {
        if (editors::authenticateGameEditor($pack)) {
            return true; // a user doesn't need to supply password for their own game
        }
        $game_id = intval($pack->game_id);
        $game = dbconnection::queryObject("SELECT password FROM games WHERE game_id = '{$game_id}' LIMIT 1");
        if(!$game) return false;
        return $game->password === NULL
            || $game->password === ''
            || (isset($pack->password) && $game->password === $pack->password);
    }

    //Used by other services
    public static function authenticateGameEditor($pack)
    {
        if(!users::authenticateUser($pack)) return false;
        $user_id = intval($pack->user_id);
        $game_id = intval($pack->game_id);
        if(dbconnection::queryObject("SELECT * FROM user_games WHERE user_id = '{$user_id}' AND game_id = '{$game_id}'")) return true;
        if($user_id === 75) return true; // stemports master editor account
        // util::errorLog("Failed Game Editor Authentication!");
        return false;
    }

    public static function addEditorToGame($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        //note $pack->user_id is DIFFERENT than $pack->auth->user_id
        $game_id = intval($pack->game_id);
        $user_id = intval($pack->user_id);
        dbconnection::queryInsert("INSERT INTO user_games (game_id, user_id, created) VALUES ('{$game_id}','{$user_id}',CURRENT_TIMESTAMP)");
        games::bumpGameVersion($pack);
        return new return_package(0);
    }

    public static function removeEditorFromGame($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        //note $pack->user_id is DIFFERENT than $pack->auth->user_id
        $game_id = intval($pack->game_id);
        $user_id = intval($pack->user_id);
        dbconnection::query("DELETE FROM user_games WHERE user_id = '{$user_id}' AND game_id = '{$game_id}'");
        games::bumpGameVersion($pack);
        return new return_package(0);
    }

    public static function getEditorsForGame($pack)
    {
        return users::getUsersForGame($pack);
    }
}
?>
