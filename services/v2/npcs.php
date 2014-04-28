<?php
require_once("dbconnection.php");
require_once("editors.php");

class npcs extends dbconnection
{
    //Takes in npc JSON, all fields optional except game_id + user_id + key
    public static function createNpcJSON($glob)
    {
        $data = file_get_contents("php://input");
        $glob = json_decode($data);
        return npcs::createNpc($glob);
    }

    public static function createNpc($pack)
    {
        if(!editors::authenticateGameEditor($pack->game_id, $pack->auth->user_id, $pack->auth->key, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $npcId = dbconnection::queryInsert(
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

        return npcs::getNpc($npcId);
    }

    //Takes in game JSON, all fields optional except npc_id + user_id + key
    public static function updateNpcJSON($glob)
    {
        $data = file_get_contents("php://input");
        $glob = json_decode($data);
        return npcs::updateNpc($glob);
    }

    public static function updateNpc($pack)
    {
        $gameId = dbconnection::queryObject("SELECT * FROM npcs WHERE npc_id = '{$pack->npc_id}'")->game_id;
        if(!editors::authenticateGameEditor($gameId, $pack->auth->user_id, $pack->auth->key, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

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

        return npcs::getNpc($pack->npc_id);
    }

    public static function getNpc($npcId)
    {
        $sql_npc = dbconnection::queryObject("SELECT * FROM npcs WHERE npc_id = '{$npcId}' LIMIT 1");

        $npc = new stdClass();
        $npc->npc_id            = $sql_npc->npc_id;
        $npc->game_id           = $sql_npc->game_id;
        $npc->name              = $sql_npc->name;
        $npc->description       = $sql_npc->description;
        $npc->icon_media_id     = $sql_npc->icon_media_id;
        $npc->media_id          = $sql_npc->media_id;
        $npc->opening_script_id = $sql_npc->opening_script_id;
        $npc->closing_script_id = $sql_npc->closing_script_id;

        return new returnData(0,$npc);
    }

    public static function deleteNpc($npcId, $userId, $key)
    {
        $gameId = dbconnection::queryObject("SELECT * FROM npcs WHERE npc_id = '{$npcId}'")->game_id;
        if(!editors::authenticateGameEditor($gameId, $userId, $key, "read_write")) return new returnData(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM npcs WHERE npc_id = '{$npcId}' LIMIT 1");
        return new returnData(0);
    }
}
?>
