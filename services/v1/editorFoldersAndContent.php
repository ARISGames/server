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
	public function getFoldersAndContent($intGameID)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		//Get the folders
		$query = "SELECT * FROM {$prefix}_folders";
		NetDebug::trace($query);
		$folders = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());

		//Get the Contents with some of the content's data
		$query = "SELECT * FROM {$prefix}_folder_contents";
		NetDebug::trace($query);
		$rsContents = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());

		//Walk the rs adding the corresponding name and icon and saving to a new array
		$arrayContents = array();

		while ($content = mysql_fetch_object($rsContents)) {
			//Save the modified copy to the array
			$arrayContents[] = self::hydrateContent($content, $intGameID);
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
	public function getContent($intGameID, $intObjectContentID)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(3, NULL, "invalid game id");

		//Get the Contents with some of the content's data
		$query = "SELECT * FROM {$prefix}_folder_contents 
			WHERE object_content_id = '{$intObjectContentID}' LIMIT 1";
		NetDebug::trace($query);
		$rsContents = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());

		$content = @mysql_fetch_object($rsContents);
		if (!$content) return new returnData(2, NULL, "invalid object content id for this game");

		$content = self::hydrateContent($content, $intGameID);
		return new returnData(0, $content);
	}

	public function duplicateObject($intGameID, $objContentId)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(3, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_folder_contents WHERE object_content_id = '{$objContentId}'";
		$result = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());
		$row = mysql_fetch_object($result);

		if($row->content_type == "Npc") {
			$query = "INSERT INTO {$prefix}_npcs (name, description, text, closing, media_id, icon_media_id) SELECT name, description, text, closing, media_id, icon_media_id FROM {$prefix}_npcs WHERE npc_id = '{$row->content_id}'";
			@mysql_query($query);
			if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());
			$newContentId = mysql_insert_id();
			$query = "SELECT * FROM {$prefix}_npc_conversations WHERE npc_id = '{$row->content_id}'";
			$result = @mysql_query($query);
			if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());
			while($npcConvo = mysql_fetch_object($result))
			{
				$query = "INSERT INTO {$prefix}_nodes (title, text, opt1_text, opt1_node_id, opt2_text, opt2_node_id, opt3_text, opt3_node_id, require_answer_incorrect_node_id, require_answer_string, require_answer_correct_node_id, media_id, icon_media_id) SELECT title, text, opt1_text, opt1_node_id, opt2_text, opt2_node_id, opt3_text, opt3_node_id, require_answer_incorrect_node_id, require_answer_string, require_answer_correct_node_id, media_id, icon_media_id FROM {$prefix}_nodes WHERE node_id = '{$npcConvo->node_id}'";
				@mysql_query($query);
				if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());
				$newNodeId = mysql_insert_id();

				$query = "INSERT INTO {$prefix}_npc_conversations (npc_id, node_id, text, sort_index) VALUES ('{$newContentId}', '{$newNodeId}', '{$npcConvo->text}', '{$npcConvo->sort_index}')";
				@mysql_query($query);
				if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());
			}
		}
		else if($row->content_type == "Item") {
			$query = "INSERT INTO {$prefix}_items (name, description, is_attribute, icon_media_id, media_id, dropable, destroyable, max_qty_in_inventory, creator_player_id, origin_latitude, origin_longitude, origin_timestamp, weight, url, type) SELECT name, description, is_attribute, icon_media_id, media_id, dropable, destroyable, max_qty_in_inventory, creator_player_id, origin_latitude, origin_longitude, origin_timestamp, weight, url, type FROM {$prefix}_items WHERE item_id = '{$row->content_id}'";
			mysql_query($query);
			$newContentId = mysql_insert_id();
		}
		else if($row->content_type == "Node") {
			$query = "INSERT INTO {$prefix}_nodes (title, text, opt1_text, opt1_node_id, opt2_text, opt2_node_id, opt3_text, opt3_node_id, require_answer_incorrect_node_id, require_answer_string, require_answer_correct_node_id, media_id, icon_media_id) SELECT title, text, opt1_text, opt1_node_id, opt2_text, opt2_node_id, opt3_text, opt3_node_id, require_answer_incorrect_node_id, require_answer_string, require_answer_correct_node_id, media_id, icon_media_id FROM {$prefix}_nodes WHERE node_id = '{$row->content_id}'";
			mysql_query($query);
			$newContentId = mysql_insert_id();
		}
		else if($row->content_type == "WebPage") {
			$query = "INSERT INTO web_pages (game_id, name, url, icon_media_id) SELECT game_id, name, url, icon_media_id FROM web_pages WHERE web_page_id = '{$row->content_id}'";
			mysql_query($query);
			$newContentId = mysql_insert_id();
		}
		else if($row->content_type == "AugBubble") {
			$query = "INSERT INTO aug_bubbles (game_id, name, description, icon_media_id) SELECT game_id, name, description, icon_media_id FROM aug_bubbles WHERE aug_bubble_id = '{$row->content_id}'";
			mysql_query($query);
			$newContentId = mysql_insert_id();
			$query = "SELECT * FROM aug_bubble_media WHERE aug_bubble_id = '{$row->content_id}'";
			$result = mysql_query($query);
			while($augMedia = mysql_fetch_object($result))
			{
				$query = "INSERT INTO aug_bubble_media (aug_bubble_id, media_id, text, game_id) VALUES ('{$newContentId}', '{$augMedia->media_id}', '{$augMedia->text}', '{$prefix}')";
				mysql_query($query);
			}
		}
		// NEED TO DO THIS FOR CUSTOM MAPS else if($row->content_type == "CustomMap") {
		//	$query = "INSERT INTO overlays (game_id, name, url, icon_media_id) SELECT game_id, name, url, icon_media_id FROM web_pages WHERE web_page_id = '{$row->content_id}'";
		//	mysql_query($query);
		//	$newContentId = mysql_insert_id();
		//}

		$query = "INSERT INTO {$prefix}_folder_contents (folder_id, content_type, content_id, previous_id) VALUES ('{$row->folder_id}', '{$row->content_type}', '{$newContentId}', '{$row->previous_id}')";
		mysql_query($query);

		return new returnData(0);
	}

	/**
	 * Helper Function to lookup the details of the node/npc/item including media details
	 * @returns the content object with additional data integrated
	 */	

	private function hydrateContent($folderContentObject, $intGameID) {
		$content = $folderContentObject;

		if ($content->content_type == 'Node') {
			//Fetch the corresponding node
			$contentDetails = Nodes::getNode($intGameID,$content->content_id)->data;
			$content->name = $contentDetails->title;
		}
		else if ($content->content_type == 'Item') {
			$contentDetails = Items::getItem($intGameID,$content->content_id)->data;
			$content->name = $contentDetails->name;
		}
		else if ($content->content_type == 'Npc') {
			$contentDetails = Npcs::getNpc($intGameID,$content->content_id)->data;
			$content->name = $contentDetails->name;
		}
		else if ($content->content_type == 'WebPage') {
			$contentDetails = WebPages::getWebPage($intGameID,$content->content_id)->data;
			$content->name = $contentDetails->name;
			$content->media = NULL;
			$content->media_id = NULL;
		}
		else if ($content->content_type == 'AugBubble') {
			$contentDetails = AugBubbles::getAugBubble($intGameID,$content->content_id)->data;
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
		$mediaReturnObject = $mediaHelper->getMediaObject($intGameID, $contentDetails->icon_media_id);
		$media = $mediaReturnObject->data;
		$content->icon_media = $media;
		$content->icon_media_id = $contentDetails->icon_media_id;
                $content->is_spawnable = Spawnables::hasActiveSpawnable($intGameID, $content->content_type, $content->content_id);

		if ($content->content_type != 'WebPage' && $content->content_type != 'PlayerNote' && $content->content_type != 'AugBubble' && $content->content_type != 'CustomMap'){
			//Get the Media
			$mediaHelper = new Media;
			$mediaReturnObject = $mediaHelper->getMediaObject($intGameID, $contentDetails->media_id);
			$media = $mediaReturnObject->data;
			$content->media = $media;
			$content->media_id = $contentDetails->media_id;
		}
		/* Depricated
		   if ($content->content_type == 'AugBubble'){
		//Get the Alignment Media
		$mediaHelper = new Media;
		$mediaReturnObject = $mediaHelper->getMediaObject($intGameID, $contentDetails->alignment_media_id);
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
	public function saveFolder($intGameID, $intFolderID, $strName, $intParentID, $intSortOrder, $boolIsOpen )
	{
		$strName = addslashes($strName);	

		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		if ($intFolderID) {
			//This is an update

			$query = "UPDATE {$prefix}_folders
				SET 
				name = '{$strName}',
				     parent_id = '{$intParentID}',
				     previous_id = '{$intSortOrder}',
				     is_open = '{$boolIsOpen}'
					     WHERE 
					     folder_id = {$intFolderID}
			";

			NetDebug::trace($query);
			@mysql_query($query);
			if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());
			else return new returnData(0, NULL, NULL);
		}	
		else {		
			//This is an insert

			$query = "INSERT INTO {$prefix}_folders (name, parent_id, previous_id, is_open)
				VALUES ('{$strName}', '{$intParentID}', '{$intSortOrder}', '{$boolIsOpen}')";

			@mysql_query($query);
			$newFolderID = mysql_insert_id();

			if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());
			else return new returnData(0, $newFolderID, NULL);
		}
	}

	/**
	 * Create or update content object to be displayed in navigation. Use 0 or null in intObjectContentID to create new.  If update, it will also update the sorting info
	 * @returns the new folderContentID on insert
	 */
	public static function saveContent($intGameID, $intObjectContentID, $intFolderID, $strContentType, $intContentID, $intSortOrder )
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		if ($intObjectContentID) {
			//This is an update
			$query = "UPDATE {$prefix}_folder_contents
				SET 
				folder_id = '{$intFolderID}',
					  content_type = '{$strContentType}',
					  content_id = '{$intContentID}',
					  previous_id = '{$intSortOrder}'
						  WHERE 
						  object_content_id = {$intObjectContentID}
			";

			NetDebug::trace($query);
			@mysql_query($query);
			if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());
			else return new returnData(0, NULL, NULL);
		}	
		else {		
			//This is an insert
			$query = "INSERT INTO {$prefix}_folder_contents 
				(folder_id, content_type, content_id, previous_id)
				VALUES 
				('{$intFolderID}', '{$strContentType}', '{$intContentID}', '{$intSortOrder}')";

			@mysql_query($query);
			$newContentID = mysql_insert_id();

			if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());
			else return new returnData(0, $newContentID, NULL);
		}
	}

	/**
	 * Delete a Folder, updating the sort order
	 * @returns 0 on success
	 */
	public function deleteFolder($intGameID, $intFolderID)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");		

		$query = "DELETE FROM {$prefix}_folders WHERE folder_id = {$intFolderID}";
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

		if (mysql_affected_rows()) return new returnData(0);
		else return new returnData(2, 'invalid folder id');
	}	

	/**
	 * Delete a content record, updating the sort order, not touching the actual item
	 * @returns 0 on success
	 */
	public static function deleteContent($intGameID, $intContentID)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");		

		//Lookup the object
		$query = "SELECT content_type,content_id FROM {$prefix}_folder_contents WHERE object_content_id = {$intContentID} LIMIT 1";
		NetDebug::trace($query);
		$contentQueryResult = @mysql_query($query);
		NetDebug::trace(mysql_error());
		$content = @mysql_fetch_object($contentQueryResult);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

                Spawnables::deleteSpawnablesOfObject($intGameID, $content->content_type, $content->content_id);

		//Delete the content record
		$query = "DELETE FROM {$prefix}_folder_contents WHERE object_content_id = {$intContentID}";
		NetDebug::trace($query);
		@mysql_query($query);
		NetDebug::trace(mysql_error());
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

		//Delete the object
		if ($content->content_type == "Node") Nodes::deleteNode($intGameID, $content->content_id);
		else if ($content->content_type == "Item") Items::deleteItem($intGameID, $content->content_id);
		else if ($content->content_type == "Npc") Npcs::deleteNpc($intGameID, $content->content_id);
		else if ($content->content_type == "WebPage") WebPages::deleteWebPage($intGameID, $content->content_id);
		else if ($content->content_type == "AugBubble") AugBubbles::deleteAugBubble($intGameID, $content->content_id);
		else if ($content->content_type == "PlayerNote") Notes::deleteNote($content->content_id);
		//else if ($content->content_type == "CustomMap") Overlays::deleteOverlay($intGameID,$content->content_id);  // NEED TO IMPLEMENT THIS IN OVERLAYS.PHP

		if (mysql_affected_rows()) return new returnData(0);
		else return new returnData(2, 'invalid folder id');
	}	
}
