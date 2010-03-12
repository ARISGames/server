<?php
require_once("module.php");
require_once("nodes.php");
require_once("items.php");
require_once("npcs.php");

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

			$content->icon_media_id = $contentDetails->icon_media_id;
			
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
	public function saveFolder($intGameID, $intFolderID, $strName, $intParentID, $intPreviousFolderID )
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		
		if ($intFolderID) {
			//This is an update
			
			$this->spliceOut($prefix, self::EDITORFOLDER, $intFolderID);
			$this->spliceIn($prefix, self::EDITORFOLDER, $intFolderID, $intPreviousFolderID, $intParentID);
			
			$query = "UPDATE {$prefix}_folders
						SET 
						name = '{$strName}',
						parent_id = '{$intParentID}'
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
					VALUES ('{$strName}', '{$intParentID}', '{$intPreviousFolderID}')";
					
			@mysql_query($query);
			$newFolderID = mysql_insert_id();
			$this->placeAtBegining($prefix, self::EDITORFOLDER, $newFolderID, $intParentID);
			
			if (mysql_error()) return new returnData(1, NULL, "SQL Error:" . mysql_error());
			else return new returnData(0, $newFolderID, NULL);
		}
		
		

	}

	/**
     * Create or update content object to be displayed in navigation. Use 0 or null in intObjectContentID to create new.  If update, it will also update the sorting info
     * @returns the new folderContentID on insert
     */
	public function saveContent($intGameID, $intObjectContentID, $intFolderID, 
								$strContentType, $intContentID, $intPreviousObjectContentID )
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		if ($intFolderContentID) {
			//This is an update
			
			$this->spliceOut($prefix, self::EDITORCONTENT, $intFolderContentID);
			$this->spliceIn($prefix, self::EDITORCONTENT, $intFolderContentID, $intPreviousContentID, $intFolderID);
			
			$query = "UPDATE {$prefix}_folder_contents
						SET 
						folder_id = '{$intFolderID}',
						content_type = '{$strContentType}',
						content_id = '{$intContentID}'
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
					('{$intFolderID}', '{$strContentType}', '{$intContentID}', '{$intPreviousFolderID}')";
					
			@mysql_query($query);
			$newContentID = mysql_insert_id();
			
			$this->placeAtBegining($prefix, self::EDITORCONTENT, $newContentID, $intFolderID);
			
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

		$this->spliceOut($prefix, self::EDITORFOLDER, $intFolderID);
				
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

		$this->spliceOut($prefix, self::EDITORCONTENT, $intContentID);
		
		$query = "DELETE FROM {$prefix}_folder_contents WHERE object_content_id = {$intContentID}";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		if (mysql_affected_rows()) return new returnData(0);
		else return new returnData(2, 'invalid folder id');
		
	}	
	
	
	private function spliceOut($strPrefix, $strFolderOrContent, $IDToRemove) {
		
		if ($strFolderOrContent == self::EDITORCONTENT) { 
			NetDebug::trace("Splice out some content");
			$table = "folder_contents";
			$idField ="object_content_id";
		}
		else if ($strFolderOrContent == self::EDITORFOLDER) {
			NetDebug::trace("Splice out a folder");
			$table = "folders";
			$idField ="folder_id";
		}
		
		//Find a following folder/content (if it exists)
		$query = "SELECT * FROM {$strPrefix}_{$table} 
				WHERE previous_id = '{$IDToRemove}'";
		NetDebug::trace($query);
		$rs = @mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, "SQL Error:". mysql_error());
		$follower = mysql_fetch_object($rs);
		
		if ($follower) {
			$followerID = $follower->$idField;
			NetDebug::trace("Record $followerID is a follower of this record");
		
			//Fetch this folder/content's previous id
			$query = "SELECT * FROM {$strPrefix}_{$table} WHERE {$idField} = '{$IDToRemove}'";
			NetDebug::trace($query);
			$rs= @mysql_query($query);
			if (mysql_error()) return new returnData(1, NULL, "SQL Error:". mysql_error());
			$thisObject = mysql_fetch_object($rs);
			$thisObjectsPrevious = $thisObject->previous_id;
			NetDebug::trace("This record uses $thisObjectsPrevious for its previous");

			
			//Set the following folder/content to this folder/content's previous
			$query = "UPDATE {$strPrefix}_{$table} 
						SET previous_id = {$thisObjectsPrevious}
						WHERE {$idField} = {$followerID}";
			NetDebug::trace($query);
			@mysql_query($query);
			if (mysql_error()) return new returnData(1, NULL, "SQL Error:". mysql_error());
			NetDebug::trace("Done");
		}
	}

	private function spliceIn($strPrefix, $strFolderOrContent, $IDToInsert, $previousID, $parentID) {
		if ($previousID == NULL) $previousID = 0;

		
		if ($strFolderOrContent == self::EDITORCONTENT) { 
			NetDebug::trace("Splice in some content");
			$table = "folder_contents";
			$idField ="object_content_id";
			$parentIDField = "folder_id";
		}
		else if ($strFolderOrContent == self::EDITORFOLDER) {
			NetDebug::trace("Splice in a folder");
			$table = "folders";
			$idField ="folder_id";
			$parentIDField = "parent_id";
		}
		
		//Check who is following the the previous within this folder (if anyone)
		$query = "SELECT * FROM {$strPrefix}_{$table} 
				WHERE previous_id = '{$previousID}' AND $parentIDField = {$parentID}";
		NetDebug::trace($query);
		$rs = @mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, "SQL Error:". mysql_error());
		$newFollower = mysql_fetch_object($rs);
		
		if($newFollower) {
			$newFollowerID = $newFollower->$idField;
		
			//Point them to us
			$query = "UPDATE {$strPrefix}_{$table} 
							SET previous_id = {$IDToInsert}
							WHERE {$idField} = '{$newFollowerID}'";
			NetDebug::trace($query);
			@mysql_query($query);
			if (mysql_error()) return new returnData(1, NULL, "SQL Error:". mysql_error());
		}
		
			
		//Point our record to the previous
		$query = "UPDATE {$strPrefix}_{$table} 
						SET previous_id = {$previousID}
						WHERE {$idField} = '{$IDToInsert}'";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, "SQL Error:". mysql_error());		
	}
	
	
	private function placeAtBegining($strPrefix, $strFolderOrContent, $IDToInsert, $parentFolderID){
		if ($parentFolderID == NULL) $parentFolderID = 0;
		
		if ($strFolderOrContent == self::EDITORCONTENT) { 
			NetDebug::trace("Add some content");
			$table = "folder_contents";
			$idField ="object_content_id";
			$parentIDField = "folder_id";
		}
		else if ($strFolderOrContent == self::EDITORFOLDER) {
			NetDebug::trace("Add a folder");
			$table = "folders";
			$idField ="folder_id";
			$parentIDField = "parent_id";
		}
		
		//Check who is first (if anyone) in this folder
		
		$query = "SELECT * FROM {$strPrefix}_{$table} 
					WHERE previous_id = 0 AND 
						{$parentIDField} = {$parentFolderID} AND
						{$idField} != $IDToInsert";
		NetDebug::trace($query);
		$rs = @mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, "SQL Error:". mysql_error());
		$newFollower = mysql_fetch_object($rs);
		$newFollowerID = $newFollower->$idField;
		NetDebug::trace("FollowerID =" . $newFollowerID);

		if(!$newFollower) return;

		//Point them to us
		$query = "UPDATE {$strPrefix}_{$table} 
						SET previous_id = {$IDToInsert}
						WHERE {$idField} = {$newFollowerID}";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, "SQL Error:". mysql_error());
			
		//Point our record to the previous
		$query = "UPDATE {$strPrefix}_{$table} 
						SET previous_id = 0
						WHERE {$idField} = {$IDToInsert}";
		NetDebug::trace($query);
		@mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, "SQL Error:". mysql_error());		
	}
}