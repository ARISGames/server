<?php
require_once("dbconnection.php");
require_once("editors.php");

class plaques extends dbconnection
{	
    //Takes in plaque JSON, all fields optional except game_id + user_id + key
    public static function createPlaqueJSON($glob)
    {
        $data = file_get_contents("php://input");
        $glob = json_decode($data);
        return plaques::createPlaque($glob);
    }

    public static function createPlaque($pack)
    {
        if(!editors::authenticateGameEditor($pack->game_id, $pack->auth->user_id, $pack->auth->key, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $plaqueId = dbconnection::queryInsert(
            "INSERT INTO plaques (".
            "game_id,".
            ($pack->name          ? "name,"          : "").
            ($pack->description   ? "description,"   : "").
            ($pack->icon_media_id ? "icon_media_id," : "").
            ($pack->media_id      ? "media_id,"      : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            ($pack->name          ? "'".addslashes($pack->name)."',"          : "").
            ($pack->description   ? "'".addslashes($pack->description)."',"   : "").
            ($pack->icon_media_id ? "'".addslashes($pack->icon_media_id)."'," : "").
            ($pack->media_id      ? "'".addslashes($pack->media_id)."',"      : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return plaques::getPlaque($plaqueId);
    }

    //Takes in game JSON, all fields optional except plaque_id + user_id + key
    public static function updatePlaqueJSON($glob)
    {
        $data = file_get_contents("php://input");
        $glob = json_decode($data);
        return plaques::updatePlaque($glob);
    }

    public static function updatePlaque($pack)
    {
        $gameId = dbconnection::queryObject("SELECT * FROM plaques WHERE plaque_id = '{$pack->plaque_id}'")->game_id;
        if(!editors::authenticateGameEditor($gameId, $pack->auth->user_id, $pack->auth->key, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE plaques SET ".
            ($pack->name                 ? "name                 = '".addslashes($pack->name)."', "                 : "").
            ($pack->description          ? "description          = '".addslashes($pack->description)."', "          : "").
            ($pack->icon_media_id        ? "icon_media_id        = '".addslashes($pack->icon_media_id)."', "        : "").
            ($pack->media_id             ? "media_id             = '".addslashes($pack->media_id)."', "             : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE plaque_id = '{$pack->plaque_id}'"
        );

        return plaques::getPlaque($pack->plaque_id);
    }

    public static function getPlaque($plaqueId)
    {
        $sql_plaque = dbconnection::queryObject("SELECT * FROM plaques WHERE plaque_id = '{$plaqueId}' LIMIT 1");

        $plaque = new stdClass();
        $plaque->plaque_id              = $sql_plaque->plaque_id;
        $plaque->game_id              = $sql_plaque->game_id;
        $plaque->name                 = $sql_plaque->name;
        $plaque->description          = $sql_plaque->description;
        $plaque->icon_media_id        = $sql_plaque->icon_media_id;
        $plaque->media_id             = $sql_plaque->media_id;

        return new returnData(0,$plaque);
    }

    public static function deletePlaque($plaqueId, $userId, $key)
    {
        $gameId = dbconnection::queryObject("SELECT * FROM plaques WHERE plaque_id = '{$plaqueId}'")->game_id;
        if(!editors::authenticateGameEditor($gameId, $userId, $key, "read_write")) return new returnData(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM plaques WHERE plaque_id = '{$plaqueId}' LIMIT 1");
        return new returnData(0);
    }
}
?>
