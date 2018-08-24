<?php
require_once("dbconnection.php");
require_once("return_package.php");

class colors extends dbconnection
{
    public static function getColors($pack)
    {
        $colors_id = intval($pack->colors_id);
        $sql_colors = dbconnection::queryObject("SELECT * FROM colors WHERE colors_id = '{$colors_id}' LIMIT 1");
        if(!$sql_colors) return new return_package(2, NULL, "The colors you've requested do not exist");
        return new return_package(0, colors::colorsObjectFromSQL($sql_colors));
    }

    public static function colorsObjectFromSQL($sql_colors)
    {
        if(!$sql_colors) return $sql_colors;
        $colors = new stdClass();
        $colors->colors_id = $sql_colors->colors_id;
        $colors->name      = $sql_colors->name     ;
        $colors->tag_1     = $sql_colors->tag_1    ;
        $colors->tag_2     = $sql_colors->tag_2    ;
        $colors->tag_3     = $sql_colors->tag_3    ;
        $colors->tag_4     = $sql_colors->tag_4    ;
        $colors->tag_5     = $sql_colors->tag_5    ;
        $colors->tag_6     = $sql_colors->tag_6    ;
        $colors->tag_7     = $sql_colors->tag_7    ;
        $colors->tag_8     = $sql_colors->tag_8    ;

        return $colors;
    }
}
?>
