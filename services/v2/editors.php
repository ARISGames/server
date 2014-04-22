<?php

require_once("dbconnection.php");
require_once("users.php");

class editors extends dbconnection
{
    public function authenticateGameEditor($gameId, $userId, $token, $permission)
    {
        if(!users::authenticateUser($userId, $token, $permission)) return false;
        if(dbconnection::queryObject("SELECT * FROM user_games WHERE user_id = '{$userId}' AND game_id = '{$gameId}'")) return true;
        util::serverErrorLog("Failed Game Editor Authentication!"); return false;
    }

    public function getGamesForEditor($userId, $token)
    {
        if(!users::authenticateUser($userId, $token, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");
        $games = dbconnection::queryArray("SELECT games.* FROM (SELECT * FROM game_editors WHERE editor_id = '$userId') as ge LEFT JOIN games ON ge.game_id = games.game_id");

        return new returnData(0, $games, NULL);		
    }

    public function addEditorToGame($newEditorId, $gameId, $userId, $token)
    {
        if(!editors::authenticateGameEditor($gameId, $userId, $token, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        dbconnection::query("INSERT INTO game_editors (editor_id, game_id) VALUES ('{$newEditorId}','{$gameId}')");
        $email = dbconnection::queryObject("SELECT email FROM editors WHERE editor_id = '{$newEditorId}'")->email;
        $name = dbconnection::queryObject("SELECT name FROM games WHERE game_id = $gameId")->name;

        $body = "An owner of ARIS Game \"".$name."\" has promoted you to editor. Go to ".Config::WWWPath."/editor and log in to begin collaborating!";
        Module::sendEmail($email, "You are now an editor of ARIS Game \"$name\"", $body);

        return new returnData(0);	
    }	

    public function removeEditorFromGame($newEditorId, $gameId, $userId, $token)
    {
        if(!editors::authenticateGameEditor($gameId, $userId, $token, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM game_editors WHERE editor_id = '{$newEditorId}' AND game_id = '{$gameId}'");
        return new returnData(0, TRUE);
    }


}
