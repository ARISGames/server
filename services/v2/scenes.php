<?php

require_once("dbconnection.php");
require_once("returnData.php");
require_once("editors.php");

class scenes extends dbconnection
{	
    //Takes in game JSON, all fields optional except user_id + token
    public static function createSceneJSON($glob)
    {
        $data = file_get_contents("php://input");
        $glob = json_decode($data);
        return games::createScene($glob);
    }

    public static function createScene($pack)
    {
        if(!editors::authenticateGameEditor($pack->game_id, $pack->auth->user_id, $pack->auth->token, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $sceneId = dbconnection::queryInsert(
            "INSERT INTO scenes (".
            "game_id,".
            ($pack->name ? "name," : "").
            "created".
            ") VALUES (".
            "'".$pack->game_id."',".
            ($pack->name ? "'".addslashes($pack->name)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return scenes::getScene($sceneId);
    }

    //Takes in game JSON, all fields optional except user_id + token
    public static function updateSceneJSON($glob)
    {
        $data = file_get_contents("php://input");
        $glob = json_decode($data);
        return games::updateScene($glob);
    }

    public static function updateScene($pack)
    {
        $gameId = dbconnection::queryObject("SELECT * FROM scenes WHERE scene_id = '{$pack->scene_id}'")->game_id;
        if(!editors::authenticateGameEditor($gameId, $pack->auth->user_id, $pack->auth->token, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $sceneId = dbconnection::queryInsert(
            "UPDATE scenes SET ".
            ($pack->name ? "name = '{$pack->name}', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE scene_id = '{$pack->scene_id}'".
        );

        return scenes::getScene($sceneId);
    }

    public static function getScene($sceneId)
    {
        $sql_scene = dbconnection::queryObject("SELECT * FROM scenes WHERE scene_id = '{$sceneId}' LIMIT 1");

        $scene = new stdClass();
        $scene->scene_id = $sql_scene->scene_id;
        $scene->game_id = $sql_scene->game_id;
        $scene->name = $sql_scene->name;

        return new returnData(0,$scene);
    }

    public static function deleteScene($sceneId, $userId, $token)
    {
        $gameId = dbconnection::queryObject("SELECT * FROM scenes WHERE scene_id = '{$sceneId}'")->game_id;
        if(!editors::authenticateGameEditor($gameId, $userId, $token, "read_write")) return new returnData(6, NULL, "Failed Authentication");

        dbconnection::queryObject("DELETE FROM scenes WHERE scene_id = '{$sceneId}' LIMIT 1");
    }
}
?>
