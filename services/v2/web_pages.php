<?php
require_once("dbconnection.php");
require_once("editors.php");
require_once("return_package.php");

class web_pages extends dbconnection
{	
    //Takes in web_page JSON, all fields optional except game_id + user_id + key
    public static function createWebPage($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return web_pages::createWebPagePack($glob); }
    public static function createWebPagePack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->web_page_id = dbconnection::queryInsert(
            "INSERT INTO web_pages (".
            "game_id,".
            (isset($pack->name)          ? "name,"          : "").
            (isset($pack->url)           ? "url,"           : "").
            (isset($pack->icon_media_id) ? "icon_media_id," : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->name)          ? "'".addslashes($pack->name)."',"          : "").
            (isset($pack->url)           ? "'".addslashes($pack->url)."',"           : "").
            (isset($pack->icon_media_id) ? "'".addslashes($pack->icon_media_id)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return web_pages::getWebPagePack($pack);
    }

    //Takes in game JSON, all fields optional except web_page_id + user_id + key
    public static function updateWebPage($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return web_pages::updateWebPagePack($glob); }
    public static function updateWebPagePack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM web_pages WHERE web_page_id = '{$pack->web_page_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE web_pages SET ".
            (isset($pack->name)                 ? "name                 = '".addslashes($pack->name)."', "          : "").
            (isset($pack->url)                  ? "url                  = '".addslashes($pack->url)."', "           : "").
            (isset($pack->icon_media_id)        ? "icon_media_id        = '".addslashes($pack->icon_media_id)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE web_page_id = '{$pack->web_page_id}'"
        );

        return web_pages::getWebPagePack($pack);
    }

    private static function webPageObjectFromSQL($sql_webPage)
    {
        if(!$sql_webPage) return $sql_webPage;
        $webPage = new stdClass();
        $webPage->web_page_id              = $sql_webPage->web_page_id;
        $webPage->game_id              = $sql_webPage->game_id;
        $webPage->name                 = $sql_webPage->name;
        $webPage->url                  = $sql_webPage->url;
        $webPage->icon_media_id        = $sql_webPage->icon_media_id;

        return $webPage;
    }

    public static function getWebPage($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return web_pages::getWebPagePack($glob); }
    public static function getWebPagePack($pack)
    {
        $sql_webPage = dbconnection::queryObject("SELECT * FROM web_pages WHERE web_page_id = '{$pack->web_page_id}' LIMIT 1");
        return new return_package(0,web_pages::webPageObjectFromSQL($sql_webPage));
    }

    public static function getWebPagesForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return web_pages::getWebPagesForGamePack($glob); }
    public static function getWebPagesForGamePack($pack)
    {
        $sql_webPages = dbconnection::queryArray("SELECT * FROM web_pages WHERE game_id = '{$pack->game_id}'");
        $webPages = array();
        for($i = 0; $i < count($sql_webPages); $i++)
            if($ob = web_pages::webPageObjectFromSQL($sql_webPages[$i])) $webPages[] = $ob;
        
        return new return_package(0,$webPages);
    }

    public static function deleteWebPage($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return web_pages::deleteWebPagePack($glob); }
    public static function deleteWebPagePack($pack)
    {
        $webpage = dbconnection::queryObject("SELECT * FROM web_pages WHERE web_page_id = '{$pack->web_page_id}'");
        $pack->auth->game_id = $webpage->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM web_pages WHERE web_page_id = '{$pack->web_page_id}' LIMIT 1");
        //cleanup
        $options = dbconnection::queryArray("SELECT * FROM dialog_options WHERE link_type = 'EXIT_TO_WEB_PAGE' AND link_id = '{$pack->web_page_id}'");
        for($i = 0; $i < count($options); $i++)
        {
            $pack->dialog_option_id = $options[$i]->dialog_option_id;
            dialogs::deleteDialogOptionPack($pack);
        }
    
        $tabs = dbconnection::queryArray("SELECT * FROM tabs WHERE type = 'WEB_PAGE' AND content_id = '{$pack->web_page_id}'");
        for($i = 0; $i < count($tabs); $i++)
        {
            $pack->tab_id = $tabs[$i]->tab_id;
            tabs::deleteTabPack($pack);
        }

        $tags = dbconnection::queryArray("SELECT * FROM object_tags WHERE object_type = 'WEB_PAGE' AND object_id = '{$pack->web_page_id}'");
        for($i = 0; $i < count($tags); $i++)
        {
            $pack->object_tag_id = $tags[$i]->object_tag_id;
            tags::deleteObjectTagPack($pack);
        }

        $instances = dbconnection::queryArray("SELECT * FROM instances WHERE object_type = 'WEB_PAGE' AND object_id = '{$pack->web_page_id}'");
        for($i = 0; $i < count($instances); $i++)
        {
            $pack->instance_id = $instances[$i]->instance_id;
            instances::deleteInstancePack($pack);
        }

        $factories = dbconnection::queryArray("SELECT * FROM factories WHERE object_type = 'WEB_PAGE' AND object_id = '{$pack->web_page_id}'");
        for($i = 0; $i < count($factories); $i++)
        {
            $pack->factory_id = $factories[$i]->factory_id;
            factories::deleteFactoryPack($pack);
        }

        $reqAtoms = dbconnection::queryArray("SELECT * FROM requirement_atoms WHERE requirement = 'PLAYER_VIEWED_WEB_PAGE' AND content_id = '{$pack->web_page_id}'");
        for($i = 0; $i < count($reqAtoms); $i++)
        {
            $pack->requirement_atom_id = $reqAtoms[$i]->requirement_atom_id;
            requirements::deleteRequirementAtomPack($pack);
        }

        return new return_package(0);
    }
}
?>
