<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

class npc_scripts extends dbconnection
{	
    //Takes in npc_script JSON, all fields optional except game_id + user_id + key
    public static function createNpcScript($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return npc_scripts::createNpcScriptPack($glob); }
    public static function createNpcScriptPack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->npc_script_id = dbconnection::queryInsert(
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

        return npc_scripts::getNpcScriptPack($pack);
    }

    //Takes in game JSON, all fields optional except npc_script_id + user_id + key
    public static function updateNpcScript($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return npc_scripts::updateNpcScriptPack($glob); }
    public static function updateNpcScriptPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM npc_scripts WHERE npc_script_id = '{$pack->npc_script_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE npc_scripts SET ".
            ($pack->npc_id     ? "npc_id     = '".addslashes($pack->npc_id)."', "     : "").
            ($pack->title      ? "title      = '".addslashes($pack->title)."', "      : "").
            ($pack->text       ? "text       = '".addslashes($pack->text)."', "       : "").
            ($pack->sort_index ? "sort_index = '".addslashes($pack->sort_index)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE npc_script_id = '{$pack->npc_script_id}'"
        );

        return npc_scripts::getNpcScriptPack($pack);
    }

    private static function npcScriptObjectFromSQL($sql_npcScript)
    {
        if(!$sql_npcScript) return $sql_npcScript;
        $npcScript = new stdClass();
        $npcScript->npc_script_id = $sql_npcScript->npc_script_id;
        $npcScript->game_id       = $sql_npcScript->game_id;
        $npcScript->npc_id        = $sql_npcScript->npc_id;
        $npcScript->title         = $sql_npcScript->title;
        $npcScript->text          = $sql_npcScript->text;
        $npcScript->sort_index    = $sql_npcScript->sort_index;

        return $npcScript;
    }

    public static function getNpcScript($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return npc_scripts::getNpcScriptPack($glob); }
    public static function getNpcScriptPack($pack)
    {
        $sql_npcScript = dbconnection::queryObject("SELECT * FROM npc_scripts WHERE npc_script_id = '{$pack->npc_script_id}' LIMIT 1");
        return new return_package(0,npc_scripts::npcScriptObjectFromSQL($sql_npcScript));
    }

    public static function getNpcScriptsForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return npc_scripts::getNpcScriptsForGamePack($glob); }
    public static function getNpcScriptsForGamePack($pack)
    {
        $sql_npcScripts = dbconnection::queryArray("SELECT * FROM npc_scripts WHERE game_id = '{$pack->game_id}'");
        $npcScripts = array();
        for($i = 0; $i < count($sql_npcScripts); $i++)
            $npcScripts[] = npc_scripts::npcScriptObjectFromSQL($sql_npcScripts[$i]);
        return new return_package(0,$npcScripts);
    }

    public static function getNpcScriptsForNpc($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return npc_scripts::getNpcScriptsForNpcPack($glob); }
    public static function getNpcScriptsForNpcPack($pack)
    {
        $sql_npcScripts = dbconnection::queryArray("SELECT * FROM npc_scripts WHERE npc_id = '{$pack->npc_id}'");
        $npcScripts = array();
        for($i = 0; $i < count($sql_npcScripts); $i++)
            $npcScripts[] = npc_scripts::npcScriptObjectFromSQL($sql_npcScripts[$i]);
        return new return_package(0,$npcScripts);
    }

    public static function deleteNpcScript($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return npc_scripts::deleteNpcScriptPack($glob); }
    public static function deleteNpcScriptPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM npc_scripts WHERE npc_script_id = '{$pack->npc_script_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM npc_scripts WHERE npc_script_id = '{$pack->npc_script_id}' LIMIT 1");
        return new return_package(0);
    }
}
?>
