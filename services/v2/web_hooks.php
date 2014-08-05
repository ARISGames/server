<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

class web_hooks extends dbconnection
{	
    //Takes in web_hook JSON, all fields optional except game_id + user_id + key
    public static function createWebHook($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return web_hooks::createWebHookPack($glob); }
    public static function createWebHookPack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->web_hook_id = dbconnection::queryInsert(
            "INSERT INTO web_hooks (".
            "game_id,".
            (isset($pack->name)                        ? "name,"                        : "").
            (isset($pack->url)                         ? "url,"                         : "").
            (isset($pack->incoming)                    ? "incoming,"                    : "").
            (isset($pack->requirement_root_package_id) ? "requirement_root_package_id," : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->name)                        ? "'".addslashes($pack->name)."',"                        : "").
            (isset($pack->url)                         ? "'".addslashes($pack->url)."',"                         : "").
            (isset($pack->incoming)                    ? "'".addslashes($pack->incoming)."',"                    : "").
            (isset($pack->requirement_root_package_id) ? "'".addslashes($pack->requirement_root_package_id)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return web_hooks::getWebHookPack($pack);
    }

    //Takes in game JSON, all fields optional except web_hook_id + user_id + key
    public static function updateWebHook($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return web_hooks::updateWebHookPack($glob); }
    public static function updateWebHookPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM web_hooks WHERE web_hook_id = '{$pack->web_hook_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE web_hooks SET ".
            (isset($pack->name)                        ? "name                        = '".addslashes($pack->name)."', "                        : "").
            (isset($pack->url)                         ? "url                         = '".addslashes($pack->url)."', "                         : "").
            (isset($pack->incoming)                    ? "incoming                    = '".addslashes($pack->incoming)."', "                    : "").
            (isset($pack->requirement_root_package_id) ? "requirement_root_package_id = '".addslashes($pack->requirement_root_package_id)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE web_hook_id = '{$pack->web_hook_id}'"
        );

        return web_hooks::getWebHookPack($pack);
    }

    private static function webHookObjectFromSQL($sql_webHook)
    {
        if(!$sql_webHook) return $sql_webHook;
        $webHook = new stdClass();
        $webHook->web_hook_id                 = $sql_webHook->web_hook_id;
        $webHook->game_id                     = $sql_webHook->game_id;
        $webHook->name                        = $sql_webHook->name;
        $webHook->url                         = $sql_webHook->url;
        $webHook->incoming                    = $sql_webHook->incoming;
        $webHook->requirement_root_package_id = $sql_webHook->requirement_root_package_id;

        return $webHook;
    }

    public static function getWebHook($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return web_hooks::getWebHookPack($glob); }
    public static function getWebHookPack($pack)
    {
        $sql_webHook = dbconnection::queryObject("SELECT * FROM web_hooks WHERE web_hook_id = '{$pack->web_hook_id}' LIMIT 1");
        return new return_package(0,web_hooks::webHookObjectFromSQL($sql_webHook));
    }

    public static function getWebHooksForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return web_hooks::getWebHooksForGamePack($glob); }
    public static function getWebHooksForGamePack($pack)
    {
        $sql_webHooks = dbconnection::queryArray("SELECT * FROM web_hooks WHERE game_id = '{$pack->game_id}'");
        $webHooks = array();
        for($i = 0; $i < count($sql_webHooks); $i++)
            if($ob = web_hooks::webHookObjectFromSQL($sql_webHooks[$i])) $webHooks[] = $ob;
        
        return new return_package(0,$webHooks);
    }

    public static function deleteWebHook($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return web_hooks::deleteWebHookPack($glob); }
    public static function deleteWebHookPack($pack)
    {
        $webhook = dbconnection::queryObject("SELECT * FROM web_hooks WHERE web_hook_id = '{$pack->web_hook_id}'");
        $pack->auth->game_id = $webhook->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM web_hooks WHERE web_hook_id = '{$pack->web_hook_id}' LIMIT 1");
        //cleanup
        $reqPack = dbconnection::queryObject("SELECT * FROM requirement_root_packages WHERE requirement_root_package_id = '{$webhook->requirement_root_package_id}'");
        if($reqPack)
        {
            $pack->requirement_root_package_id = $reqPack->requirement_root_package_id;
            requirements::deleteRequirementRootPackagePack($pack);
        }

        $reqAtoms = dbconnection::queryArray("SELECT * FROM requirement_atoms WHERE requirement = 'PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK' AND content_id = '{$pack->web_hook_id}'");
        for($i = 0; $i < count($reqAtoms); $i++)
        {
            $pack->requirement_atom_id = $reqAtoms[$i]->requirement_atom_id;
            requirements::deleteRequirementAtomPack($pack);
        }

        return new return_package(0);
    }
}
?>
