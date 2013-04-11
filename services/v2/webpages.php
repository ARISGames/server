<?php
require_once("module.php");
require_once("media.php");
require_once("games.php");
require_once("locations.php");
require_once("playerStateChanges.php");
require_once("editorFoldersAndContent.php");

class WebPages extends Module
{
    public static function getWebPages($gameId)
    {
        $query = "SELECT * FROM web_pages WHERE game_id = '{$gameId}'";

        $rsResult = Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        return new returnData(0, $rsResult);
    }

    public static function getWebPage($gameId, $webPageId)
    {
        $prefix = Module::getPrefix($gameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "SELECT * FROM web_pages WHERE game_id = '{$gameId}' AND web_page_id = '{$webPageId}' LIMIT 1";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $webPage = @mysql_fetch_object($rsResult);
        if (!$webPage) return new returnData(2, NULL, "invalid web page id");

        return new returnData(0, $webPage);
    }

    public static function createWebPage($gameId, $name, $url, $iconMediaId)
    {
        $name = addslashes($name);	

        $prefix = Module::getPrefix($gameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "INSERT INTO web_pages 
            (game_id, name, url, icon_media_id)
            VALUES ('{$gameId}', '{$name}', 
                    '{$url}',
                    '{$iconMediaId}')";

        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);		

        return new returnData(0, mysql_insert_id());
    }

    public static function updateWebPage($gameId, $webPageId, $name, $url, $iconMediaId)
    {
        $prefix = Module::getPrefix($gameId);

        $name = addslashes($name);	

        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "UPDATE web_pages 
            SET name = '{$name}', 
                url = '{$url}', 
                icon_media_id = '{$iconMediaId}' 
                    WHERE web_page_id = '{$webPageId}'";

        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);

        if (mysql_affected_rows()) return new returnData(0, TRUE, "Success Running:" . $query);
        else return new returnData(0, FALSE, "Success Running:" . $query);
    }

    public static function deleteWebPage($gameId, $webPageId)
    {
        $prefix = Module::getPrefix($gameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        Locations::deleteLocationsForObject($gameId, 'WebPage', $webPageId);
        Requirements::deleteRequirementsForRequirementObject($gameId, 'WebPage', $webPageId);
        PlayerStateChanges::deletePlayerStateChangesThatRefrenceObject($gameId, 'WebPage', $webPageId);

        $query = "DELETE FROM web_pages WHERE game_id = '{$gameId}' AND web_page_id = {$webPageId}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else                       return new returnData(0, FALSE);
    }	
}
