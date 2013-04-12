<?php
require_once("module.php");
require_once("spawnables.php");
require_once("nodes.php");
require_once("items.php");
require_once("npcs.php");
require_once("media.php");
require_once("webpages.php");
require_once("augbubbles.php");
require_once("notes.php");
require_once("overlays.php");

class EditorFoldersAndContent extends Module
{
    const EDITORCONTENT = 1;
    const EDITORFOLDER = 2;

    public function getFoldersAndContent($gameId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        //Get the folders
        $query = "SELECT * FROM folders WHERE game_id = '{$gameId}'";
        $folders = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());

        //Get the Contents with some of the content's data
        $query = "SELECT * FROM folder_contents WHERE game_id = '{$gameId}'";
        $rsContents = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());

        //Walk the rs adding the corresponding name and icon and saving to a new array
        $arrayContents = array();

        while ($content = mysql_fetch_object($rsContents)) {
            //Save the modified copy to the array
            $arrayContents[] = self::hydrateContent($content, $gameId);
        }

        //fake out amfphp to package this array as a flex array collection
        $arrayCollectionContents = (object) array('_explicitType' => "flex.messaging.io.ArrayCollection",
                'source' => $arrayContents);

        $foldersAndContents = (object) array('folders' => $folders, 'contents' => $arrayCollectionContents);
        return new returnData(0, $foldersAndContents);
    }

    public function getContent($gameId, $intObjectContentID)
    {
        //Get the Contents with some of the content's data
        $query = "SELECT * FROM folder_contents WHERE game_id = '{$gameId}' AND object_content_id = '{$intObjectContentID}' LIMIT 1";
        $rsContents = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());

        $content = @mysql_fetch_object($rsContents);
        if (!$content) return new returnData(2, NULL, "invalid object content id for this game");

        $content = self::hydrateContent($content, $gameId);
        return new returnData(0, $content);
    }

    public function duplicateObject($gameId, $objContentId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $query = "SELECT * FROM folder_contents WHERE game_id = '{$gameId}' AND object_content_id = '{$objContentId}'";
        $result = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());
        $row = mysql_fetch_object($result);

        if($row->content_type == "Npc") {
            $query = "INSERT INTO npcs (game_id, name, description, text, closing, media_id, icon_media_id) SELECT game_id, name, description, text, closing, media_id, icon_media_id FROM npcs WHERE game_id = '{$gameId}' AND  npc_id = '{$row->content_id}'";
            Module::query($query);
            if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());
            $newContentId = mysql_insert_id();
            $query = "SELECT * FROM npc_conversations WHERE npc_id = '{$row->content_id}' AND game_id = '{$gameId}'";
            $result = Module::query($query);
            if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());
            while($npcConvo = mysql_fetch_object($result))
            {
                $query = "INSERT INTO nodes (game_id, title, text, opt1_text, opt1_node_id, opt2_text, opt2_node_id, opt3_text, opt3_node_id, require_answer_incorrect_node_id, require_answer_string, require_answer_correct_node_id, media_id, icon_media_id) SELECT game_id, title, text, opt1_text, opt1_node_id, opt2_text, opt2_node_id, opt3_text, opt3_node_id, require_answer_incorrect_node_id, require_answer_string, require_answer_correct_node_id, media_id, icon_media_id FROM nodes WHERE game_id = {$gameId} AND node_id = '{$npcConvo->node_id}'";
                Module::query($query);
                if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());
                $newNodeId = mysql_insert_id();

                $query = "INSERT INTO npc_conversations (game_id, npc_id, node_id, text, sort_index) VALUES ('{$gameId}', '{$newContentId}', '{$newNodeId}', '{$npcConvo->text}', '{$npcConvo->sort_index}')";
                Module::query($query);
                if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());
            }
        }
        else if($row->content_type == "Item") {
            $query = "INSERT INTO items (game_id, name, description, is_attribute, icon_media_id, media_id, dropable, destroyable, max_qty_in_inventory, creator_player_id, origin_latitude, origin_longitude, origin_timestamp, weight, url, type) SELECT game_id, name, description, is_attribute, icon_media_id, media_id, dropable, destroyable, max_qty_in_inventory, creator_player_id, origin_latitude, origin_longitude, origin_timestamp, weight, url, type FROM items WHERE item_id = '{$row->content_id}' AND game_id = '{$gameId}'";
            Module::query($query);
            $newContentId = mysql_insert_id();
        }
        else if($row->content_type == "Node") {
            $query = "INSERT INTO nodes (game_id, title, text, opt1_text, opt1_node_id, opt2_text, opt2_node_id, opt3_text, opt3_node_id, require_answer_incorrect_node_id, require_answer_string, require_answer_correct_node_id, media_id, icon_media_id) SELECT game_id, title, text, opt1_text, opt1_node_id, opt2_text, opt2_node_id, opt3_text, opt3_node_id, require_answer_incorrect_node_id, require_answer_string, require_answer_correct_node_id, media_id, icon_media_id FROM nodes WHERE node_id = '{$row->content_id}' AND game_id = '{$gameId}'";
            Module::query($query);
            $newContentId = mysql_insert_id();
        }
        else if($row->content_type == "WebPage") {
            $query = "INSERT INTO web_pages (game_id, name, url, icon_media_id) SELECT game_id, name, url, icon_media_id FROM web_pages WHERE web_page_id = '{$row->content_id}'";
            Module::query($query);
            $newContentId = mysql_insert_id();
        }
        else if($row->content_type == "AugBubble") {
            $query = "INSERT INTO aug_bubbles (game_id, name, description, icon_media_id) SELECT game_id, name, description, icon_media_id FROM aug_bubbles WHERE aug_bubble_id = '{$row->content_id}'";
            Module::query($query);
            $newContentId = mysql_insert_id();
            $query = "SELECT * FROM aug_bubble_media WHERE aug_bubble_id = '{$row->content_id}'";
            $result = Module::query($query);
            while($augMedia = mysql_fetch_object($result))
            {
                $query = "INSERT INTO aug_bubble_media (aug_bubble_id, media_id, text, game_id) VALUES ('{$newContentId}', '{$augMedia->media_id}', '{$augMedia->text}', '{$gameId}')";
                Module::query($query);
            }
        }

        $query = "INSERT INTO folder_contents (game_id, folder_id, content_type, content_id, previous_id) VALUES ('{$gameId}', '{$row->folder_id}', '{$row->content_type}', '{$newContentId}', '{$row->previous_id}')";
        Module::query($query);

        return new returnData(0);
    }

    private function hydrateContent($folderContentObject, $gameId) {
        $content = $folderContentObject;

        if ($content->content_type == 'Node') {
            //Fetch the corresponding node
            $contentDetails = Nodes::getNode($gameId,$content->content_id)->data;
            $content->name = $contentDetails->title;
        }
        else if ($content->content_type == 'Item') {
            $contentDetails = Items::getItem($gameId,$content->content_id)->data;
            $content->name = $contentDetails->name;
        }
        else if ($content->content_type == 'Npc') {
            $contentDetails = Npcs::getNpc($gameId,$content->content_id)->data;
            $content->name = $contentDetails->name;
        }
        else if ($content->content_type == 'WebPage') {
            $contentDetails = WebPages::getWebPage($gameId,$content->content_id)->data;
            $content->name = $contentDetails->name;
            $content->media = NULL;
            $content->media_id = NULL;
        }
        else if ($content->content_type == 'AugBubble') {
            $contentDetails = AugBubbles::getAugBubble($gameId,$content->content_id)->data;
            $content->name = $contentDetails->name;
            $content->media = NULL;
            $content->media_id = NULL;
        }
        else if ($content->content_type == 'CustomMap') {
            $contentDetails = Overlays::getOverlay($gameId,$content->content_id)->data;
            $content->name = $contentDetails->name;
        }
        else if ($content->content_type == 'PlayerNote') {
            $contentDetails = Notes::getNoteById($content->content_id)->data;
            $content->name = $contentDetails->title;
            $content->icon_media_id = 5;
            $content->media = NULL;
            $content->media_id = NULL;
        }

        //Get the Icon Media
        $mediaHelper = new Media;
        $mediaReturnObject = $mediaHelper->getMediaObject($gameId, $contentDetails->icon_media_id);
        $media = $mediaReturnObject->data;
        $content->icon_media = $media;
        $content->icon_media_id = $contentDetails->icon_media_id;
        $content->is_spawnable = Spawnables::hasActiveSpawnable($gameId, $content->content_type, $content->content_id);

        if ($content->content_type != 'WebPage' && $content->content_type != 'PlayerNote' && $content->content_type != 'AugBubble' && $content->content_type != 'CustomMap'){
            //Get the Media
            $mediaHelper = new Media;
            $mediaReturnObject = $mediaHelper->getMediaObject($gameId, $contentDetails->media_id);
            $media = $mediaReturnObject->data;
            $content->media = $media;
            $content->media_id = $contentDetails->media_id;
        }
        /* Depricated
           if ($content->content_type == 'AugBubble'){
        //Get the Alignment Media
        $mediaHelper = new Media;
        $mediaReturnObject = $mediaHelper->getMediaObject($gameId, $contentDetails->alignment_media_id);
        $alignmentMedia = $mediaReturnObject->data;
        $content->alignment_media = $alignmentMedia;
        $content->alignment_media_id = $alignmentMedia->media_id;
        }
         */

        return $content;
    }

    public function saveFolder($gameId, $intFolderID, $strName, $intParentID, $intSortOrder, $boolIsOpen, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $strName = addslashes($strName);	

        if ($intFolderID) {
            //This is an update

            $query = "UPDATE folders
                SET 
                name = '{$strName}',
                     parent_id = '{$intParentID}',
                     previous_id = '{$intSortOrder}',
                     is_open = '{$boolIsOpen}'
                         WHERE 
                         folder_id = {$intFolderID} AND
                         game_id = '{$gameId}'
                         ";

            Module::query($query);
            if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());
            else return new returnData(0, NULL, NULL);
        }	
        else {		
            //This is an insert

            $query = "INSERT INTO folders (game_id, name, parent_id, previous_id, is_open)
                VALUES ('{$gameId}','{$strName}', '{$intParentID}', '{$intSortOrder}', '{$boolIsOpen}')";

            Module::query($query);
            $newFolderID = mysql_insert_id();

            if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());
            else return new returnData(0, $newFolderID, NULL);
        }
    }

    public static function saveContent($gameId, $intObjectContentID, $intFolderID, $strContentType, $intContentID, $intSortOrder, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        if ($intObjectContentID) {
            //This is an update
            $query = "UPDATE folder_contents
                SET 
                folder_id = '{$intFolderID}',
                          content_type = '{$strContentType}',
                          content_id = '{$intContentID}',
                          previous_id = '{$intSortOrder}'
                              WHERE 
                              object_content_id = {$intObjectContentID} AND
                              game_id = {$gameId}
            ";

            Module::query($query);
            if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());
            else return new returnData(0, NULL, NULL);
        }	
        else {		
            //This is an insert
            $query = "INSERT INTO folder_contents 
                (game_id, folder_id, content_type, content_id, previous_id)
                VALUES 
                ('{$gameId}','{$intFolderID}', '{$strContentType}', '{$intContentID}', '{$intSortOrder}')";

            Module::query($query);
            $newContentID = mysql_insert_id();

            if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());
            else return new returnData(0, $newContentID, NULL);
        }
    }

    public function deleteFolder($gameId, $intFolderID, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $query = "DELETE FROM folders WHERE folder_id = {$intFolderID} AND game_id = '{$gameId}'";
        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0);
        else return new returnData(2, 'invalid folder id');
    }	

    public static function deleteContent($gameId, $intContentID, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        //Lookup the object
        $query = "SELECT content_type,content_id FROM folder_contents WHERE object_content_id = {$intContentID} AND game_id = '{$gameId}' LIMIT 1";
        $contentQueryResult = Module::query($query);
        $content = @mysql_fetch_object($contentQueryResult);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        Spawnables::deleteSpawnablesOfObject($gameId, $content->content_type, $content->content_id, $editorId, $editorToken);

        //Delete the content record
        $query = "DELETE FROM folder_contents WHERE object_content_id = {$intContentID} AND game_id = '{$gameId}'";
        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        //Delete the object
        if ($content->content_type == "Node") Nodes::deleteNode($gameId, $content->content_id, $editorId, $editorToken);
        else if ($content->content_type == "Item") Items::deleteItem($gameId, $content->content_id, $editorId, $editorToken);
        else if ($content->content_type == "Npc") Npcs::deleteNpc($gameId, $content->content_id, $editorId, $editorToken);
        else if ($content->content_type == "WebPage") WebPages::deleteWebPage($gameId, $content->content_id, $editorId, $editorToken);
        else if ($content->content_type == "AugBubble") AugBubbles::deleteAugBubble($gameId, $content->content_id, $editorId, $editorToken);
        else if ($content->content_type == "PlayerNote") Notes::deleteNote($content->content_id, $editorId, $editorToken);

        if (mysql_affected_rows()) return new returnData(0);
        else return new returnData(2, 'invalid folder id');
    }	
}
