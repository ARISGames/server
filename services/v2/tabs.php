<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("return_package.php");

class tabs extends dbconnection
{	
    //Takes in tab JSON, all fields optional except user_id + key
    public static function createTab($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return tabs::createTabPack($glob); }
    public static function createTabPack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->tab_id = dbconnection::queryInsert(
            "INSERT INTO tabs (".
            "game_id,".
            (isset($pack->type)                        ? "type,"                        : "").
            (isset($pack->name)                        ? "name,"                        : "").
            (isset($pack->icon_media_id)               ? "icon_media_id,"               : "").
            (isset($pack->content_id)                  ? "content_id,"                  : "").
            (isset($pack->info)                        ? "info,"                        : "").
            (isset($pack->requirement_root_package_id) ? "requirement_root_package_id," : "").
            (isset($pack->sort_index)                  ? "sort_index,"                  : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->type)                        ? "'".addslashes($pack->type)."',"                        : "").
            (isset($pack->name)                        ? "'".addslashes($pack->name)."',"                        : "").
            (isset($pack->icon_media_id)               ? "'".addslashes($pack->icon_media_id)."',"               : "").
            (isset($pack->content_id)                  ? "'".addslashes($pack->content_id)."',"                  : "").
            (isset($pack->info)                        ? "'".addslashes($pack->info)."',"                        : "").
            (isset($pack->requirement_root_package_id) ? "'".addslashes($pack->requirement_root_package_id)."'," : "").
            (isset($pack->sort_index)                  ? "'".addslashes($pack->sort_index)."',"                  : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return tabs::getTabPack($pack);
    }

    public static function updateTab($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return tabs::updateTabPack($glob); }
    public static function updateTabPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM tabs WHERE tab_id = '{$pack->tab_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE tabs SET ".
            (isset($pack->type)                        ? "type                        = '".addslashes($pack->type)."', "                        : "").
            (isset($pack->name)                        ? "name                        = '".addslashes($pack->name)."', "                        : "").
            (isset($pack->icon_media_id)               ? "icon_media_id               = '".addslashes($pack->icon_media_id)."', "               : "").
            (isset($pack->content_id)                  ? "content_id                  = '".addslashes($pack->content_id)."', "                  : "").
            (isset($pack->info)                        ? "info                        = '".addslashes($pack->info)."', "                        : "").
            (isset($pack->requirement_root_package_id) ? "requirement_root_package_id = '".addslashes($pack->requirement_root_package_id)."', " : "").
            (isset($pack->sort_index)                  ? "sort_index                  = '".addslashes($pack->sort_index)."', "                  : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE tab_id = '{$pack->tab_id}'"
        );

        return tabs::getTabPack($pack);
    }

    private static function tabObjectFromSQL($sql_tab)
    {
        if(!$sql_tab) return $sql_tab;
        $tab = new stdClass();
        $tab->tab_id                      = $sql_tab->tab_id;
        $tab->game_id                     = $sql_tab->game_id;
        $tab->type                        = $sql_tab->type;
        $tab->name                        = $sql_tab->name;
        $tab->icon_media_id               = $sql_tab->icon_media_id;
        $tab->content_id                  = $sql_tab->content_id;
        $tab->info                        = $sql_tab->info;
        $tab->requirement_root_package_id = $sql_tab->requirement_root_package_id;
        $tab->sort_index                  = $sql_tab->sort_index;

        return $tab;
    }

    public static function getTab($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return tabs::getTabPack($glob); }
    public static function getTabPack($pack)
    {
        $sql_tab = dbconnection::queryObject("SELECT * FROM tabs WHERE tab_id = '{$pack->tab_id}' LIMIT 1");
        if(!$sql_tab) return new return_package(2, NULL, "The tab you've requested does not exist");
        return new return_package(0,tabs::tabObjectFromSQL($sql_tab));
    }

    public static function getTabsForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return tabs::getTabsForGamePack($glob); }
    public static function getTabsForGamePack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $sql_tabs = dbconnection::queryArray("SELECT * FROM tabs WHERE game_id = '{$pack->game_id}'");
        $tabs = array();
        for($i = 0; $i < count($sql_tabs); $i++)
            if($ob = tabs::tabObjectFromSQL($sql_tabs[$i])) $tabs[] = $ob;

        return new return_package(0,$tabs);

    }

    public static function deleteTab($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return tabs::deleteTabPack($glob); }
    public static function deleteTabPack($pack)
    {
        $tab = dbconnection::queryObject("SELECT * FROM tabs WHERE tab_id = '{$pack->tab_id}'");
        $pack->auth->game_id = $tab->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM tabs WHERE tab_id = '{$pack->tab_id}' LIMIT 1");
        //cleanup
        $options = dbconnection::queryArray("SELECT * FROM dialog_options WHERE link_type = 'EXIT_TO_TAB' AND link_id = '{$pack->tab_id}'");
        for($i = 0; $i < count($options); $i++)
        {
            $pack->dialog_option_id = $options[$i]->dialog_option_id;
            dialogs::deleteDialogOptionPack($pack);
        }

        $reqPack = dbconnection::queryObject("SELECT * FROM requirement_root_packages WHERE requirement_root_package_id = '{$tab->requirement_root_package_id}'");
        if($reqPack)
        {
            $pack->requirement_root_package_id = $reqPack->requirement_root_package_id;
            requirements::deleteRequirementPackagePack($pack);
        }
        
        return new return_package(0);
    }
}
?>
