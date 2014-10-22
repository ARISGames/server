<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

require_once("instances.php");
require_once("triggers.php");

class scenes extends dbconnection
{	
    //Takes in game JSON, all fields optional except user_id + key
    public static function createScene($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return scenes::createScenePack($glob); }
    public static function createScenePack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->scene_id = dbconnection::queryInsert(
            "INSERT INTO scenes (".
            "game_id,".
            (isset($pack->name)        ? "name,"        : "").
            (isset($pack->description) ? "description," : "").
            (isset($pack->editor_x)    ? "editor_x,"    : "").
            (isset($pack->editor_y)    ? "editor_y,"    : "").
            "created".
            ") VALUES (".
            "'".$pack->game_id."',".
            (isset($pack->name)        ? "'".addslashes($pack->name)."',"        : "").
            (isset($pack->description) ? "'".addslashes($pack->description)."'," : "").
            (isset($pack->eitor_x)     ? "'".addslashes($pack->eitor_x)."',"     : "").
            (isset($pack->eitor_y)     ? "'".addslashes($pack->eitor_y)."',"     : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        //Update game's intro scene if it doesn't yet exist
        $game = dbconnection::queryObject("SELECT * FROM games WHERE game_id = '{$pack->game_id}'");
        if(!dbconnection::queryObject("SELECT * FROM scenes WHERE scene_id = '{$game->intro_scene_id}' AND game_id = '{$game->game_id}'"))
            dbconnection::query("UPDATE games SET intro_scene_id = '{$pack->scene_id}' WHERE game_id = '{$pack->game_id}'");

        return scenes::getScenePack($pack);
    }

    //Takes in game JSON, all fields optional except user_id + key
    public static function updateScene($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return scenes::updateScenePack($glob); }
    public static function updateScenePack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM scenes WHERE scene_id = '{$pack->scene_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE scenes SET ".
            (isset($pack->name)        ? "name        = '".addslashes($pack->name)."', "        : "").
            (isset($pack->description) ? "description = '".addslashes($pack->description)."', " : "").
            (isset($pack->editor_x)    ? "editor_x    = '".addslashes($pack->editor_x)."', "    : "").
            (isset($pack->editor_y)    ? "editor_y    = '".addslashes($pack->editor_y)."', "    : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE scene_id = '{$pack->scene_id}'"
        );

        return scenes::getScenePack($pack);
    }

    private static function sceneObjectFromSQL($sql_scene)
    {
        if(!$sql_scene) return $sql_scene;
        $scene = new stdClass();
        $scene->scene_id    = $sql_scene->scene_id;
        $scene->game_id     = $sql_scene->game_id;
        $scene->name        = $sql_scene->name;
        $scene->description = $sql_scene->description;
        $scene->editor_x    = $sql_scene->editor_x;
        $scene->editor_y    = $sql_scene->editor_y;

        return $scene;
    }

    public static function getScene($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return scenes::getScenePack($glob); }
    public static function getScenePack($pack)
    {
        $sql_scene = dbconnection::queryObject("SELECT * FROM scenes WHERE scene_id = '{$pack->scene_id}' LIMIT 1");
        return new return_package(0,scenes::sceneObjectFromSQL($sql_scene));
    }

    public static function getScenesForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return scenes::getScenesForGamePack($glob); }
    public static function getScenesForGamePack($pack)
    {
        $sql_scenes = dbconnection::queryArray("SELECT * FROM scenes WHERE game_id = '{$pack->game_id}'");
        $scenes = array();
        for($i = 0; $i < count($sql_scenes); $i++)
            if($ob = scenes::sceneObjectFromSQL($sql_scenes[$i])) $scenes[] = $ob;

        return new return_package(0,$scenes);
    }

    public static function deleteScene($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return scenes::deleteScenePack($glob); }
    public static function deleteScenePack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM scenes WHERE scene_id = '{$pack->scene_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM scenes WHERE scene_id = '{$pack->scene_id}' LIMIT 1");
        //cleanup
        dbconnection::query("UPDATE games SET intro_scene_id = 0 WHERE intro_scene_id = '{$pack->scene_id}'");

        $triggers = dbconnection::queryArray("SELECT * FROM triggers WHERE scene_id  = '{$pack->scene_id}'");
        for($i = 0; $i < count($triggers); $i++)
        {
            $pack->trigger_id = $triggers[$i]->trigger_id;
            triggers::deleteTriggerPack($pack);
        }

        $instances = dbconnection::queryArray("SELECT * FROM instances WHERE object_type = 'SCENE' AND object_id = '{$pack->scene_id}'");
        for($i = 0; $i < count($instances); $i++)
        {
            $pack->instance_id = $instances[$i]->instance_id;
            instances::deleteInstancePack($pack);
        }

        return new return_package(0);
    }
}
?>
