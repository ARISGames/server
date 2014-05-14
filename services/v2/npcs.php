<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

class npcs extends dbconnection
{
    //Takes in npc JSON, all fields optional except game_id + user_id + key
    public static function createNpc($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return npcs::createNpcPack($glob); }
    public static function createNpcPack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->npc_id = dbconnection::queryInsert(
            "INSERT INTO npcs (".
            "game_id,".
            ($pack->name              ? "name,"              : "").
            ($pack->description       ? "description,"       : "").
            ($pack->icon_media_id     ? "icon_media_id,"     : "").
            ($pack->media_id          ? "media_id,"          : "").
            ($pack->opening_script_id ? "opening_script_id," : "").
            ($pack->closing_script_id ? "closing_script_id," : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            ($pack->name              ? "'".addslashes($pack->name)."',"              : "").
            ($pack->description       ? "'".addslashes($pack->description)."',"       : "").
            ($pack->icon_media_id     ? "'".addslashes($pack->icon_media_id)."',"     : "").
            ($pack->media_id          ? "'".addslashes($pack->media_id)."',"          : "").
            ($pack->opening_script_id ? "'".addslashes($pack->opening_script_id)."'," : "").
            ($pack->closing_script_id ? "'".addslashes($pack->closing_script_id)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return npcs::getNpcPack($pack);
    }

    //Takes in game JSON, all fields optional except npc_id + user_id + key
    public static function updateNpc($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return npcs::updateNpcPack($glob); }
    public static function updateNpcPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM npcs WHERE npc_id = '{$pack->npc_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE npcs SET ".
            ($pack->name              ? "name              = '".addslashes($pack->name)."', "              : "").
            ($pack->description       ? "description       = '".addslashes($pack->description)."', "       : "").
            ($pack->icon_media_id     ? "icon_media_id     = '".addslashes($pack->icon_media_id)."', "     : "").
            ($pack->media_id          ? "media_id          = '".addslashes($pack->media_id)."', "          : "").
            ($pack->opening_script_id ? "opening_script_id = '".addslashes($pack->opening_script_id)."', " : "").
            ($pack->closing_script_id ? "closing_script_id = '".addslashes($pack->closing_script_id)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE npc_id = '{$pack->npc_id}'"
        );

        return npcs::getNpcPack($pack);
    }

    private static function npcObjectFromSQL($sql_npc)
    {
        $npc = new stdClass();
        $npc->npc_id            = $sql_npc->npc_id;
        $npc->game_id           = $sql_npc->game_id;
        $npc->name              = $sql_npc->name;
        $npc->description       = $sql_npc->description;
        $npc->icon_media_id     = $sql_npc->icon_media_id;
        $npc->media_id          = $sql_npc->media_id;
        $npc->opening_script_id = $sql_npc->opening_script_id;
        $npc->closing_script_id = $sql_npc->closing_script_id;

        return $npc;
    }

    public static function getNpc($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return npcs::getNpcPack($glob); }
    public static function getNpcPack($pack)
    {
        $sql_npc = dbconnection::queryObject("SELECT * FROM npcs WHERE npc_id = '{$pack->npc_id}' LIMIT 1");
        return new return_package(0,npcs::npcObjectFromSQL($sql_npc));
    }

    public static function getNpcsForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return npcs::getNpcsForGamePack($glob); }
    public static function getNpcsForGamePack($pack)
    {
        $sql_npcs = dbconnection::queryArray("SELECT * FROM npcs WHERE game_id = '{$pack->game_id}'");
        $npcs = array();
        for($i = 0; $i < count($sql_npcs); $i++)
            $npcs[] = npcs::npcObjectFromSQL($sql_npcs[$i]);

        return new return_package(0,$npcs);
    }

    public static function deleteNpc($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return npcs::deleteNpcPack($glob); }
    public static function deleteNpcPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM npcs WHERE npc_id = '{$npcId}'")->game_id;
        $pack->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM npcs WHERE npc_id = '{$npcId}' LIMIT 1");
        return new return_package(0);
    }
}
?>
