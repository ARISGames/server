<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

class scenes extends dbconnection
{	
    //Takes in game JSON, all fields optional except user_id + key
    public static function createSceneJSON($glob)
    {
        $data = file_get_contents("php://input");
        $glob = json_decode($data);
        return scenes::createScene($glob);
    }

    public static function createScene($pack)
    {
        if(!editors::authenticateGameEditor($pack->game_id, $pack->auth->user_id, $pack->auth->key, "read_write"))
            return new return_package(6, NULL, "Failed Authentication");

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

    //Takes in game JSON, all fields optional except user_id + key
    public static function updateSceneJSON($glob)
    {
        $data = file_get_contents("php://input");
        $glob = json_decode($data);
        return scenes::updateScene($glob);
    }

    public static function updateScene($pack)
    {
        $gameId = dbconnection::queryObject("SELECT * FROM scenes WHERE scene_id = '{$pack->scene_id}'")->game_id;
        if(!editors::authenticateGameEditor($gameId, $pack->auth->user_id, $pack->auth->key, "read_write"))
            return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE scenes SET ".
            ($pack->name ? "name = '".addslashes($pack->name)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE scene_id = '{$pack->scene_id}'"
        );

        return scenes::getScene($pack->scene_id);
    }

    private static function sceneObjectFromSQL($sql_scene)
    {
        $scene = new stdClass();
        $scene->scene_id = $sql_scene->scene_id;
        $scene->game_id = $sql_scene->game_id;
        $scene->name = $sql_scene->name;

        return $scene;
    }

    public static function getScene($sceneId)
    {
        $sql_scene = dbconnection::queryObject("SELECT * FROM scenes WHERE scene_id = '{$sceneId}' LIMIT 1");
        return new return_package(0,scenes::sceneObjectFromSQL($sql_scene));
    }

    public static function getScenesForGame($gameId)
    {
        $sql_scenes = dbconnection::queryArray("SELECT * FROM scenes WHERE game_id = '{$gameId}'");
        $scenes = array();
        for($i = 0; $i < count($sql_scenes); $i++)
            $scenes[] = scenes::sceneObjectFromSQL($sql_scenes[$i]);

        return new return_package(0,$scenes);
    }

    public static function deleteScene($sceneId, $userId, $key)
    {
        $gameId = dbconnection::queryObject("SELECT * FROM scenes WHERE scene_id = '{$sceneId}'")->game_id;
        if(!editors::authenticateGameEditor($gameId, $userId, $key, "read_write")) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM scenes WHERE scene_id = '{$sceneId}' LIMIT 1");
    }
}
?>
