<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

class overlays extends dbconnection
{ 
    //Takes in overlay JSON, all fields optional except game_id + user_id + key
    public static function createOverlay($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return overlays::createOverlayPack($glob); }
    public static function createOverlayPack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->overlay_id = dbconnection::queryInsert(
            "INSERT INTO overlays (".
            "game_id,".
            (isset($pack->name)                        ? "name,"                        : "").
            (isset($pack->description)                 ? "description,"                 : "").
            (isset($pack->media_id)                    ? "media_id,"                    : "").
            (isset($pack->top_left_latitude)           ? "top_left_latitude,"           : "").
            (isset($pack->top_left_longitude)          ? "top_left_longitude,"          : "").
            (isset($pack->top_right_latitude)          ? "top_right_latitude,"          : "").
            (isset($pack->top_right_longitude)         ? "top_right_longitude,"         : "").
            (isset($pack->bottom_left_latitude)        ? "bottom_left_latitude,"        : "").
            (isset($pack->bottom_left_longitude)       ? "bottom_left_longitude,"       : "").
            (isset($pack->requirement_root_package_id) ? "requirement_root_package_id," : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->name)                        ? "'".addslashes($pack->name)."',"                        : "").
            (isset($pack->description)                 ? "'".addslashes($pack->description)."',"                 : "").
            (isset($pack->media_id)                    ? "'".addslashes($pack->media_id)."',"                    : "").
            (isset($pack->top_left_latitude)           ? "'".addslashes($pack->top_left_latitude)."',"           : "").
            (isset($pack->top_left_longitude)          ? "'".addslashes($pack->top_left_longitude)."',"          : "").
            (isset($pack->top_right_latitude)          ? "'".addslashes($pack->top_right_latitude)."',"          : "").
            (isset($pack->top_right_longitude)         ? "'".addslashes($pack->top_right_longitude)."',"         : "").
            (isset($pack->bottom_left_latitude)        ? "'".addslashes($pack->bottom_left_latitude)."',"        : "").
            (isset($pack->bottom_left_longitude)       ? "'".addslashes($pack->bottom_left_longitude)."',"       : "").
            (isset($pack->requirement_root_package_id) ? "'".addslashes($pack->requirement_root_package_id)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return overlays::getOverlayPack($pack);
    }

    //Takes in game JSON, all fields optional except overlay_id + user_id + key
    public static function updateOverlay($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return overlays::updateOverlayPack($glob); }
    public static function updateOverlayPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM overlays WHERE overlay_id = '{$pack->overlay_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE overlays SET ".
            (isset($pack->name)                        ? "name                        = '".addslashes($pack->name)."', "                        : "").
            (isset($pack->description)                 ? "description                 = '".addslashes($pack->description)."', "                 : "").
            (isset($pack->media_id)                    ? "media_id                    = '".addslashes($pack->media_id)."', "                    : "").
            (isset($pack->top_left_latitude)           ? "top_left_latitude           = '".addslashes($pack->top_left_latitude)."', "           : "").
            (isset($pack->top_left_longitude)          ? "top_left_longitude          = '".addslashes($pack->top_left_longitude)."', "          : "").
            (isset($pack->top_right_latitude)          ? "top_right_latitude          = '".addslashes($pack->top_right_latitude)."', "          : "").
            (isset($pack->top_right_longitude)         ? "top_right_longitude         = '".addslashes($pack->top_right_longitude)."', "         : "").
            (isset($pack->bottom_left_latitude)        ? "bottom_left_latitude        = '".addslashes($pack->bottom_left_latitude)."', "        : "").
            (isset($pack->bottom_left_longitude)       ? "bottom_left_longitude       = '".addslashes($pack->bottom_left_longitude)."', "       : "").
            (isset($pack->requirement_root_package_id) ? "requirement_root_package_id = '".addslashes($pack->requirement_root_package_id)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE overlay_id = '{$pack->overlay_id}'"
        );

        return overlays::getOverlayPack($pack);
    }

    private static function overlayObjectFromSQL($sql_overlay)
    {
        if(!$sql_overlay) return $sql_overlay;
        $overlay = new stdClass();
        $overlay->overlay_id                  = $sql_overlay->overlay_id;
        $overlay->game_id                     = $sql_overlay->game_id;
        $overlay->name                        = $sql_overlay->name;
        $overlay->description                 = $sql_overlay->description;
        $overlay->media_id                    = $sql_overlay->media_id;
        $overlay->top_left_latitude           = $sql_overlay->top_left_latitude;
        $overlay->top_left_longitude          = $sql_overlay->top_left_longitude;
        $overlay->top_right_latitude          = $sql_overlay->top_right_latitude;
        $overlay->top_right_longitude         = $sql_overlay->top_right_longitude;
        $overlay->bottom_left_latitude        = $sql_overlay->bottom_left_latitude;
        $overlay->bottom_left_longitude       = $sql_overlay->bottom_left_longitude;
        $overlay->requirement_root_package_id = $sql_overlay->requirement_root_package_id;

        return $overlay;
    }

    public static function getOverlay($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return overlays::getOverlayPack($glob); }
    public static function getOverlayPack($pack)
    {
        $sql_overlay = dbconnection::queryObject("SELECT * FROM overlays WHERE overlay_id = '{$pack->overlay_id}' LIMIT 1");
        return new return_package(0,overlays::overlayObjectFromSQL($sql_overlay));
    }

    public static function getOverlaysForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return overlays::getOverlaysForGamePack($glob); }
    public static function getOverlaysForGamePack($pack)
    {
        $sql_overlays = dbconnection::queryArray("SELECT * FROM overlays WHERE game_id = '{$pack->game_id}'");
        $overlays = array();
        for($i = 0; $i < count($sql_overlays); $i++)
            if($ob = overlays::overlayObjectFromSQL($sql_overlays[$i])) $overlays[] = $ob;
        
        return new return_package(0,$overlays);
    }

    public static function deleteOverlay($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return overlays::deleteOverlayPack($glob); }
    public static function deleteOverlayPack($pack)
    {
        $overlay = dbconnection::queryObject("SELECT * FROM overlays WHERE overlay_id = '{$pack->overlay_id}'");
        $pack->auth->game_id = $overlay->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM overlays WHERE overlay_id = '{$pack->overlay_id}' LIMIT 1");
        //cleanup
        $reqPack = dbconnection::queryObject("SELECT * FROM requirement_root_packages WHERE requirement_root_package_id = '{$overlay->requirement_root_package_id}'");
        if($reqPack)
        {
            $pack->requirement_root_package_id = $reqPack->requirement_root_package_id;
            requirements::deleteRequirementRootPackagePack($pack);
        }

        return new return_package(0);
    }
}

?>
