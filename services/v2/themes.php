<?php
require_once("dbconnection.php");
require_once("return_package.php");

class themes extends dbconnection
{
    public static function getTheme($pack)
    {
        $theme_id = intval($pack->theme_id);
        $sql_theme = dbconnection::queryObject("SELECT * FROM themes WHERE theme_id = '{$theme_id}' LIMIT 1");
        if(!$sql_theme) return new return_package(2, NULL, "The theme you've requested does not exist");
        return new return_package(0, themes::themeObjectFromSQL($sql_theme));
    }

    public static function themeObjectFromSQL($sql_theme)
    {
        if(!$sql_theme) return $sql_theme;
        $theme = new stdClass();
        $theme->theme_id     = $sql_theme->theme_id    ;
        $theme->name         = $sql_theme->name        ;
        $theme->gmaps_styles = $sql_theme->gmaps_styles;

        return $theme;
    }
}
?>
