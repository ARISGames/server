<?php
require_once("module.php");
require_once("media.php");
require_once("games.php");
require_once("locations.php");
require_once("playerStateChanges.php");
require_once("editorFoldersAndContent.php");

class AugBubbles extends Module
{
    public static function getAugBubbles($gameId)
    {
        $query = "SELECT * FROM aug_bubbles WHERE game_id = '{$gameId}'";
        $augBubblesRS = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:" .mysql_error());

        $augBubbles = array();

        while($augBubble = mysql_fetch_object($augBubblesRS))
        {
            $query = "SELECT * FROM aug_bubble_media WHERE aug_bubble_id = '{$augBubble->aug_bubble_id}'";
            $mediaRS = Module::query($query);
            if (mysql_error()) return new returnData(3, NULL, "SQL Error:".mysql_error());

            $augBubble->media = array();
            while ($media = mysql_fetch_object($mediaRS)) $augBubble->media[] = $media;

            $augBubbles[] = $augBubble;
        }

        return new returnData(0, $augBubbles);
    }

    public static function getAugBubble($gameId, $augBubbleId)
    {
        $query = "SELECT * FROM aug_bubbles WHERE game_id = '{$gameId}' AND aug_bubble_id = '{$augBubbleId}' LIMIT 1";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $augBubble = @mysql_fetch_object($rsResult);
        if (!$augBubble) return new returnData(2, NULL, "invalid aug bubble id");

        $query = "SELECT * FROM aug_bubble_media WHERE aug_bubble_id = '{$augBubble->aug_bubble_id}'";
        $mediaRS = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:".mysql_error());

        //extract the recordset
        $augBubble->media = array();
        while ($media = mysql_fetch_object($mediaRS)) $augBubble->media[] = $media;

        return new returnData(0, $augBubble);
    }

    public static function getAugBubbleMedia($gameId, $augBubbleId)
    {
        $query = "SELECT * FROM aug_bubble_media WHERE aug_bubble_id = '{$augBubbleId}'";
        $result = Module::query($query);

        return new returnData(0, $result);
    }

    public static function createAugBubble($gameId, $name, $desc, $iconMediaId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $name = addslashes($name);	

        $query = "INSERT INTO aug_bubbles 
            (game_id, name, description, icon_media_id)
            VALUES ('{$gameId}', '{$name}', '{$desc}', '{$iconMediaId}')";

        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);		

        return new returnData(0, mysql_insert_id());
    }

    public static function updateAugBubble($gameId, $augBubbleId, $name, $desc, $iconMediaId)
    {
        $name = addslashes($name);	

        $query = "UPDATE aug_bubbles 
            SET name = '{$name}', 
                description = '{$desc}', 
                icon_media_id = '{$iconMediaId}'
                    WHERE aug_bubble_id = '{$augBubbleId}'";

        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);

        if (mysql_affected_rows()) return new returnData(0, TRUE, "Success Running:" . $query);
        else return new returnData(0, FALSE, "Success Running:" . $query);


    }

    public static function removeAugBubbleMediaIndex($intAugId, $intMediaId, $intIndex, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $query = "DELETE FROM aug_bubble_media WHERE aug_bubble_id = '{$intAugId}' AND media_id = '{$intMediaId}'";
        Module::query($query);

        return new returnData(0);
    }

    public static function updateAugBubbleMediaIndex($intAugId, $intMediaId, $stringName, $intGameId, $intIndex, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        /* This will be for when index is implemented
           $query = "SELECT * FROM aug_bubble_media WHERE aug_bubble_id = '{$intAugId}' AND media_id = '{$intMediaId}'";
           $result = Module::query($query);

           if(mysql_num_rows($result)>0){
           $query = "UPDATE aug_bubble_media SET";
           }
         */

        $query = "INSERT INTO aug_bubble_media (aug_bubble_id, media_id, text, game_id) VALUES ('{$intAugId}', '{$intMediaId}', '{$stringName}', '{$intGameId}')";
        Module::query($query);

        if (mysql_error()) return new returnData(1, NULL, mysql_error());

        return new returnData(0);
    }

    public static function deleteAugBubble($gameId, $augBubbleId)
    {
        Locations::deleteLocationsForObject($gameId, 'AugBubble', $augBubbleId);
        Requirements::deleteRequirementsForRequirementObject($gameId, 'AugBubble', $augBubbleId);
        PlayerStateChanges::deletePlayerStateChangesThatRefrenceObject($gameId, 'AugBubble', $augBubbleId);

        $query = "DELETE FROM aug_bubbles WHERE game_id = '{$gameId}' AND aug_bubble_id = {$augBubbleId}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) {
            return new returnData(0, TRUE);
        }
        else {
            return new returnData(0, FALSE);
        }
    }	
}
