<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

class plaques extends dbconnection
{	
    //Takes in plaque JSON, all fields optional except game_id + user_id + key
    public static function createPlaque($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return plaques::createPlaquePack($glob); }
    public static function createPlaquePack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->plaque_id = dbconnection::queryInsert(
            "INSERT INTO plaques (".
            "game_id,".
            (isset($pack->name)             ? "name,"             : "").
            (isset($pack->description)      ? "description,"      : "").
            (isset($pack->icon_media_id)    ? "icon_media_id,"    : "").
            (isset($pack->media_id)         ? "media_id,"         : "").
            (isset($pack->event_package_id) ? "event_package_id," : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->name)             ? "'".addslashes($pack->name)."',"             : "").
            (isset($pack->description)      ? "'".addslashes($pack->description)."',"      : "").
            (isset($pack->icon_media_id)    ? "'".addslashes($pack->icon_media_id)."',"    : "").
            (isset($pack->media_id)         ? "'".addslashes($pack->media_id)."',"         : "").
            (isset($pack->event_package_id) ? "'".addslashes($pack->event_package_id)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return plaques::getPlaquePack($pack);
    }

    //Takes in game JSON, all fields optional except plaque_id + user_id + key
    public static function updatePlaque($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return plaques::updatePlaquePack($glob); }
    public static function updatePlaquePack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM plaques WHERE plaque_id = '{$pack->plaque_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE plaques SET ".
            (isset($pack->name)             ? "name             = '".addslashes($pack->name)."', "             : "").
            (isset($pack->description)      ? "description      = '".addslashes($pack->description)."', "      : "").
            (isset($pack->icon_media_id)    ? "icon_media_id    = '".addslashes($pack->icon_media_id)."', "    : "").
            (isset($pack->media_id)         ? "media_id         = '".addslashes($pack->media_id)."', "         : "").
            (isset($pack->event_package_id) ? "event_package_id = '".addslashes($pack->event_package_id)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE plaque_id = '{$pack->plaque_id}'"
        );

        return plaques::getPlaquePack($pack);
    }

    private static function plaqueObjectFromSQL($sql_plaque)
    {
        if(!$sql_plaque) return $sql_plaque;
        $plaque = new stdClass();
        $plaque->plaque_id        = $sql_plaque->plaque_id;
        $plaque->game_id          = $sql_plaque->game_id;
        $plaque->name             = $sql_plaque->name;
        $plaque->description      = $sql_plaque->description;
        $plaque->icon_media_id    = $sql_plaque->icon_media_id;
        $plaque->media_id         = $sql_plaque->media_id;
        $plaque->event_package_id = $sql_plaque->event_package_id;

        return $plaque;
    }

    public static function getPlaque($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return plaques::getPlaquePack($glob); }
    public static function getPlaquePack($pack)
    {
        $sql_plaque = dbconnection::queryObject("SELECT * FROM plaques WHERE plaque_id = '{$pack->plaque_id}' LIMIT 1");
        return new return_package(0,plaques::plaqueObjectFromSQL($sql_plaque));
    }

    public static function getPlaquesForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return plaques::getPlaquesForGamePack($glob); }
    public static function getPlaquesForGamePack($pack)
    {
        $sql_plaques = dbconnection::queryArray("SELECT * FROM plaques WHERE game_id = '{$pack->game_id}'");
        $plaques = array();
        for($i = 0; $i < count($sql_plaques); $i++)
            if($ob = plaques::plaqueObjectFromSQL($sql_plaques[$i])) $plaques[] = $ob;
        
        return new return_package(0,$plaques);
    }

    public static function deletePlaque($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return plaques::deletePlaquePack($glob); }
    public static function deletePlaquePack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM plaques WHERE plaque_id = '{$pack->plaque_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM plaques WHERE plaque_id = '{$pack->plaque_id}' LIMIT 1");
        return new return_package(0);
    }
}
?>
