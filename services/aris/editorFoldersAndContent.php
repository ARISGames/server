<?php
require_once("module.php");
require_once("nodes.php");
require_once("items.php");
require_once("npcs.php");
require_once("media.php");

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
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		//Get the folders
		$query = "SELECT * FROM {$prefix}_folders";
		NetDebug::trace($query);
		$folders = @mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, "SQL Error:". mysql_error());

		//Get the Contents with some of the content's data
		$query = "SELECT * FROM {$prefix}_folder_contents";
		NetDebug::trace($query);
		$rsContents = @mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, "SQL Error:". mysql_error());
		
		//Walk the rs adding the corresponding name and icon and saving to a new array
		$arrayContents = array();
		
		while ($content = mysql_fetch_object($rsContents)) {
			
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

			
			//Get the Media
			$content->icon_media_id = $contentDetails->icon_media_id;
			$mediaHelper = new Media;
			$mediaReturnObject = $mediaHelper->getMediaObject($intGameID, $contentDetails->icon_media_id);
			$media = $mediaReturnObject->data;
			$content->icon_media = $media;
			
			
			//Save the modified copy to the array
			$arrayContents[] = $content;
		}	
		
		//fake out amfphp to package this array as a flex array collection
		$arrayCollectionContents = (object) array('_explicitType' => "flex.messaging.io.ArrayCollection",
												'source' => $arrayContents);
												
		$foldersAndContents = (object) array('folders' => $folders, 'contents' => $arrayCollectionContents);
		return new returnData(0, $foldersAndContents);
		
	}
	
	
	/**
     * Create or Update a Folder. Use 0 or null for FolderID to create a new record. If update, it will also update the sorting info
     * @returns the new folderID on insert	
     */
	public function saveFolder($intGameID, $intFolderID, $strName, $intParentID, $intSortOrder )
	{
		$strName = addslashes($strName);	

		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		
		if ($intFolderID) {
			//This is an update
			
			$query = "UPDATE {$prefix}_folders
						SET 
						name = '{$strName}',
						parent_id = '{$intParentID}',
						previous_id = '{$intSortOrder}'
						WHERE 
						folder_id = {$intFolderID}
						";
						
			NetDebug::trace($query);
			@mysql_query($query);
			if (mysql_error()) return new returnData(1, NULL, "SQL Error:" . mysql_error());
			else return new returnData(0, NULL, NULL);
		}	
		else {		
			//This is an insert
				
			$query = "INSERT INTO {$prefix}_folders (name, parent_id, previous_id)
					VALUES ('{$strName}', '{$intParentID}', '{$intSortOrder}')";
					
			@mysql_query($query);
			$newFolderID = mysql_insert_id();
			
			if (mysql_error()) return new returnData(1, NULL, "SQL Error:" . mysql_error());
			else return new returnData(0, $newFolderID, NULL);
		}
		
		

	}

	/**
     * Create or update content object to be displayed in navigation. Use 0 or null in intObjectContentID to create new.  If update, it will also update the sorting info
     * @returns the new folderContentID on insert
     */
	public function saveContent($intGameID, $intObjectContentID, $intFolderID, 
								$strContentType, $intContentID, $intSortOrder )
	{
		
		$prefix = $this->getPrefix($intGameID);
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
			if (mysql_error()) return new returnData(1, NULL, "SQL Error:" . mysql_error());
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
						
			if (mysql_error()) return new returnData(1, NULL, "SQL Error:" . mysql_error());
			else return new returnData(0, $newContentID, NULL);
		}

	}
	
	/**
     * Delete a Folder, updating the sort order
     * @returns 0 on success
     */
	public function deleteFolder($intGameID, $intFolderID)
	{
		$prefix = $this->getPrefix($intGameID);
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
	public function deleteContent($intGameID, $intContentID)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");		
		
		$query = "DELETE FROM {$prefix}_folder_contents WHERE object_content_id = {$intContentID}";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		if (mysql_affected_rows()) return new returnData(0);
		else return new returnData(2, 'invalid folder id');
		
	}	
	
	
}