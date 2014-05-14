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

    public function addEditorToGame($newEditorId, $gameId, $userId, $key)
    {
        if(!editors::authenticateGameEditor($gameId, $userId, $key, "read_write"))
            return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("INSERT INTO game_editors (editor_id, game_id) VALUES ('{$newEditorId}','{$gameId}')");
        $email = dbconnection::queryObject("SELECT email FROM editors WHERE editor_id = '{$newEditorId}'")->email;
        $name = dbconnection::queryObject("SELECT name FROM games WHERE game_id = $gameId")->name;

        $body = "An owner of ARIS Game \"".$name."\" has promoted you to editor. Go to ".Config::WWWPath."/editor and log in to begin collaborating!";
        Module::sendEmail($email, "You are now an editor of ARIS Game \"$name\"", $body);

        return new return_package(0);	
    }	

    public function removeEditorFromGame($newEditorId, $gameId, $userId, $key)
    {
        if(!editors::authenticateGameEditor($gameId, $userId, $key, "read_write"))
            return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM game_editors WHERE editor_id = '{$newEditorId}' AND game_id = '{$gameId}'");
        return new return_package(0, TRUE);
    }
}
?>
