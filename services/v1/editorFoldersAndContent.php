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

    /**
     * Fetch all Folders and Content Refrences
     * @returns the folders and folder contents rs as arrays
     */
    public function getFoldersAndContent($intGameId)
    {
        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        //Get the folders
        $query = "SELECT * FROM folders WHERE game_id = '{$prefix}'";
        $folders = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());

        //Get the Contents with some of the content's data
        $query = "SELECT * FROM folder_contents WHERE game_id = '{$prefix}'";
        $rsContents = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());

        //Walk the rs adding the corresponding name and icon and saving to a new array
        $arrayContents = array();

        while ($content = mysql_fetch_object($rsContents)) {
            //Save the modified copy to the array
            $arrayContents[] = self::hydrateContent($content, $intGameId);
        }

        //fake out amfphp to package this array as a flex array collection
        $arrayCollectionContents = (object) array('_explicitType' => "flex.messaging.io.ArrayCollection",
                'source' => $arrayContents);

        $foldersAndContents = (object) array('folders' => $folders, 'contents' => $arrayCollectionContents);
        return new returnData(0, $foldersAndContents);
    }



    /**
     * Fetch a single content object
     * @returns a content object with additional details from the game object it refrences
     */
    public function getContent($intGameId, $intObjectContentID)
    {
        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(3, NULL, "invalid game id");

        //Get the Contents with some of the content's data
        $query = "SELECT * FROM folder_contents WHERE game_id = '{$prefix}' AND object_content_id = '{$intObjectContentID}' LIMIT 1";
        $rsContents = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());

        $content = @mysql_fetch_object($rsContents);
        if (!$content) return new returnData(2, NULL, "invalid object content id for this game");

        $content = self::hydrateContent($content, $intGameId);
        return new returnData(0, $content);
    }

    public function duplicateObject($intGameId, $objContentId)
    {
        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(3, NULL, "invalid game id");

        $query = "SELECT * FROM folder_contents WHERE game_id = '{$prefix}' AND object_content_id = '{$objContentId}'";
        $result = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());
        $row = mysql_fetch_object($result);

        if($row->content_type == "Npc") {
            $query = "INSERT INTO npcs (game_id, name, description, text, closing, media_id, icon_media_id) SELECT game_id, name, description, text, closing, media_id, icon_media_id FROM npcs WHERE game_id = '{$prefix}' AND  npc_id = '{$row->content_id}'";
            Module::query($query);
            if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());
            $newContentId = mysql_insert_id();
            $query = "SELECT * FROM npc_conversations WHERE npc_id = '{$row->content_id}' AND game_id = '{$prefix}'";
            $result = Module::query($query);
            if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());
            while($npcConvo = mysql_fetch_object($result))
            {
                $query = "INSERT INTO nodes (game_id, title, text, opt1_text, opt1_node_id, opt2_text, opt2_node_id, opt3_text, opt3_node_id, require_answer_incorrect_node_id, require_answer_string, require_answer_correct_node_id, media_id, icon_media_id) SELECT game_id, title, text, opt1_text, opt1_node_id, opt2_text, opt2_node_id, opt3_text, opt3_node_id, require_answer_incorrect_node_id, require_answer_string, require_answer_correct_node_id, media_id, icon_media_id FROM nodes WHERE game_id = {$prefix} AND node_id = '{$npcConvo->node_id}'";
                Module::query($query);
                if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());
                $newNodeId = mysql_insert_id();

                $query = "INSERT INTO npc_conversations (game_id, npc_id, node_id, text, sort_index) VALUES ('{$intGameId}', '{$newContentId}', '{$newNodeId}', '{$npcConvo->text}', '{$npcConvo->sort_index}')";
                Module::query($query);
                if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());
            }
        }
        else if($row->content_type == "Item") {
            $query = "INSERT INTO items (game_id, name, description, is_attribute, icon_media_id, media_id, dropable, destroyable, max_qty_in_inventory, creator_player_id, origin_latitude, origin_longitude, origin_timestamp, weight, url, type) SELECT game_id, name, description, is_attribute, icon_media_id, media_id, dropable, destroyable, max_qty_in_inventory, creator_player_id, origin_latitude, origin_longitude, origin_timestamp, weight, url, type FROM items WHERE item_id = '{$row->content_id}' AND game_id = '{$intGameId}'";
            Module::query($query);
            $newContentId = mysql_insert_id();
        }
        else if($row->content_type == "Node") {
            $query = "INSERT INTO nodes (game_id, title, text, opt1_text, opt1_node_id, opt2_text, opt2_node_id, opt3_text, opt3_node_id, require_answer_incorrect_node_id, require_answer_string, require_answer_correct_node_id, media_id, icon_media_id) SELECT game_id, title, text, opt1_text, opt1_node_id, opt2_text, opt2_node_id, opt3_text, opt3_node_id, require_answer_incorrect_node_id, require_answer_string, require_answer_correct_node_id, media_id, icon_media_id FROM nodes WHERE node_id = '{$row->content_id}' AND game_id = '{$intGameId}'";
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
                $query = "INSERT INTO aug_bubble_media (aug_bubble_id, media_id, text, game_id) VALUES ('{$newContentId}', '{$augMedia->media_id}', '{$augMedia->text}', '{$prefix}')";
                Module::query($query);
            }
        }

        $query = "INSERT INTO folder_contents (game_id, folder_id, content_type, content_id, previous_id) VALUES ('{$intGameId}', '{$row->folder_id}', '{$row->content_type}', '{$newContentId}', '{$row->previous_id}')";
        Module::query($query);

        return new returnData(0);
    }

    /**
     * Helper Function to lookup the details of the node/npc/item including media details
     * @returns the content object with additional data integrated
     */	

    private function hydrateContent($folderContentObject, $intGameId) {
        $content = $folderContentObject;

        if ($content->content_type == 'Node') {
            //Fetch the corresponding node
            $contentDetails = Nodes::getNode($intGameId,$content->content_id)->data;
            $content->name = $contentDetails->title;
        }
        else if ($content->content_type == 'Item') {
            $contentDetails = Items::getItem($intGameId,$content->content_id)->data;
            $content->name = $contentDetails->name;
        }
        else if ($content->content_type == 'Npc') {
            $contentDetails = Npcs::getNpc($intGameId,$content->content_id)->data;
            $content->name = $contentDetails->name;
        }
        else if ($content->content_type == 'WebPage') {
            $contentDetails = WebPages::getWebPage($intGameId,$content->content_id)->data;
            $content->name = $contentDetails->name;
            $content->media = NULL;
            $content->media_id = NULL;
        }
        else if ($content->content_type == 'AugBubble') {
            $contentDetails = AugBubbles::getAugBubble($intGameId,$content->content_id)->data;
            $content->name = $contentDetails->name;
            $content->media = NULL;
            $content->media_id = NULL;
        }
        else if ($content->content_type == 'CustomMap') {
            $contentDetails = Overlays::getOverlay($intGameID,$content->content_id)->data;
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
        $mediaReturnObject = $mediaHelper->getMediaObject($intGameId, $contentDetails->icon_media_id);
        $media = $mediaReturnObject->data;
        $content->icon_media = $media;
        $content->icon_media_id = $contentDetails->icon_media_id;
        $content->is_spawnable = Spawnables::hasActiveSpawnable($intGameId, $content->content_type, $content->content_id);

        if ($content->content_type != 'WebPage' && $content->content_type != 'PlayerNote' && $content->content_type != 'AugBubble' && $content->content_type != 'CustomMap'){
            //Get the Media
            $mediaHelper = new Media;
            $mediaReturnObject = $mediaHelper->getMediaObject($intGameId, $contentDetails->media_id);
            $media = $mediaReturnObject->data;
            $content->media = $media;
            $content->media_id = $contentDetails->media_id;
        }
        /* Depricated
           if ($content->content_type == 'AugBubble'){
        //Get the Alignment Media
        $mediaHelper = new Media;
        $mediaReturnObject = $mediaHelper->getMediaObject($intGameId, $contentDetails->alignment_media_id);
        $alignmentMedia = $mediaReturnObject->data;
        $content->alignment_media = $alignmentMedia;
        $content->alignment_media_id = $alignmentMedia->media_id;
        }
         */

        return $content;
    }


    /**
     * Create or Update a Folder. Use 0 or null for FolderID to create a new record. If update, it will also update the sorting info
     * @returns the new folderID on insert	
     */
    public function saveFolder($intGameId, $intFolderID, $strName, $intParentID, $intSortOrder, $boolIsOpen )
    {
        $strName = addslashes($strName);	

        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

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
                         game_id = '{$intGameId}'
                         ";

            Module::query($query);
            if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());
            else return new returnData(0, NULL, NULL);
        }	
        else {		
            //This is an insert

            $query = "INSERT INTO folders (game_id, name, parent_id, previous_id, is_open)
                VALUES ('{$intGameId}','{$strName}', '{$intParentID}', '{$intSortOrder}', '{$boolIsOpen}')";

            Module::query($query);
            $newFolderID = mysql_insert_id();

            if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());
            else return new returnData(0, $newFolderID, NULL);
        }
    }

    /**
     * Create or update content object to be displayed in navigation. Use 0 or null in intObjectContentID to create new.  If update, it will also update the sorting info
     * @returns the new folderContentID on insert
     */
    public static function saveContent($intGameId, $intObjectContentID, $intFolderID, $strContentType, $intContentID, $intSortOrder )
    {
        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

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
                              game_id = {$intGameId}
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
                ('{$intGameId}','{$intFolderID}', '{$strContentType}', '{$intContentID}', '{$intSortOrder}')";

            Module::query($query);
            $newContentID = mysql_insert_id();

            if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());
            else return new returnData(0, $newContentID, NULL);
        }
    }

    /**
     * Delete a Folder, updating the sort order
     * @returns 0 on success
     */
    public function deleteFolder($intGameId, $intFolderID)
    {
        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");		

        $query = "DELETE FROM folders WHERE folder_id = {$intFolderID} AND game_id = '{$intGameId}'";
        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0);
        else return new returnData(2, 'invalid folder id');
    }	

    /**
     * Delete a content record, updating the sort order, not touching the actual item
     * @returns 0 on success
     */
    public static function deleteContent($intGameId, $intContentID)
    {
        $prefix = Module::getPrefix($intGameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");		

        //Lookup the object
        $query = "SELECT content_type,content_id FROM folder_contents WHERE object_content_id = {$intContentID} AND game_id = '{$intGameId}' LIMIT 1";
        $contentQueryResult = Module::query($query);
        $content = @mysql_fetch_object($contentQueryResult);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        Spawnables::deleteSpawnablesOfObject($intGameId, $content->content_type, $content->content_id);

        //Delete the content record
        $query = "DELETE FROM folder_contents WHERE object_content_id = {$intContentID} AND game_id = '{$intGameId}'";
        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        //Delete the object
        if ($content->content_type == "Node") Nodes::deleteNode($intGameId, $content->content_id);
        else if ($content->content_type == "Item") Items::deleteItem($intGameId, $content->content_id);
        else if ($content->content_type == "Npc") Npcs::deleteNpc($intGameId, $content->content_id);
        else if ($content->content_type == "WebPage") WebPages::deleteWebPage($intGameId, $content->content_id);
        else if ($content->content_type == "AugBubble") AugBubbles::deleteAugBubble($intGameId, $content->content_id);
        else if ($content->content_type == "PlayerNote") Notes::deleteNote($content->content_id);

        if (mysql_affected_rows()) return new returnData(0);
        else return new returnData(2, 'invalid folder id');
    }	
}
