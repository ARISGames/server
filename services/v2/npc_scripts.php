<?php
require_once("dbconnection.php");
require_once("editors.php");

class npc_scripts extends dbconnection
{	
    //Takes in npc_script JSON, all fields optional except game_id + user_id + key
    public static function createNpcScriptJSON($glob)
    {
        $data = file_get_contents("php://input");
        $glob = json_decode($data);
        return npc_scripts::createNpcScript($glob);
    }

    public static function createNpcScript($pack)
    {
        if(!editors::authenticateGameEditor($pack->game_id, $pack->auth->user_id, $pack->auth->key, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $npcScriptId = dbconnection::queryInsert(
            "INSERT INTO npc_scripts (".
            "game_id,".
            ($pack->npc_id     ? "npc_id,"     : "").
            ($pack->title      ? "title,"      : "").
            ($pack->text       ? "text,"       : "").
            ($pack->sort_index ? "sort_index," : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            ($pack->npc_id     ? "'".addslashes($pack->npc_id)."',"     : "").
            ($pack->title      ? "'".addslashes($pack->title)."',"      : "").
            ($pack->text       ? "'".addslashes($pack->text)."',"       : "").
            ($pack->sort_index ? "'".addslashes($pack->sort_index)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return npc_scripts::getNpcScript($npcScriptId);
    }

    //Takes in game JSON, all fields optional except npc_script_id + user_id + key
    public static function updateNpcScriptJSON($glob)
    {
        $data = file_get_contents("php://input");
        $glob = json_decode($data);
        return npc_scripts::updateNpcScript($glob);
    }

    public static function updateNpcScript($pack)
    {
        $gameId = dbconnection::queryObject("SELECT * FROM npc_scripts WHERE npc_script_id = '{$pack->npc_script_id}'")->game_id;
        if(!editors::authenticateGameEditor($gameId, $pack->auth->user_id, $pack->auth->key, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE npc_scripts SET ".
            ($pack->npc_id     ? "npc_id     = '".addslashes($pack->npc_id)."', "     : "").
            ($pack->title      ? "title      = '".addslashes($pack->title)."', "      : "").
            ($pack->text       ? "text       = '".addslashes($pack->text)."', "       : "").
            ($pack->sort_index ? "sort_index = '".addslashes($pack->sort_index)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE npc_script_id = '{$pack->npc_script_id}'"
        );

        return npc_scripts::getNpcScript($pack->npc_script_id);
    }

    public static function getNpcScript($npcScriptId)
    {
        $sql_npcScript = dbconnection::queryObject("SELECT * FROM npc_scripts WHERE npc_script_id = '{$npcScriptId}' LIMIT 1");

        $npcScript = new stdClass();
        $npcScript->npc_script_id = $sql_npcScript->npc_script_id;
        $npcScript->game_id       = $sql_npcScript->game_id;
        $npcScript->npc_id        = $sql_npcScript->npc_id;
        $npcScript->title         = $sql_npcScript->title;
        $npcScript->text          = $sql_npcScript->text;
        $npcScript->sort_index    = $sql_npcScript->sort_index;

        return new returnData(0,$npcScript);
    }

    public static function deleteNpcScript($npcScriptId, $userId, $key)
    {
        $gameId = dbconnection::queryObject("SELECT * FROM npc_scripts WHERE npc_script_id = '{$npcScriptId}'")->game_id;
        if(!editors::authenticateGameEditor($gameId, $userId, $key, "read_write")) return new returnData(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM npc_scripts WHERE npc_script_id = '{$npcScriptId}' LIMIT 1");
        return new returnData(0);
    }
}
?>
