<?php
require_once("module.php");


class Media extends Module
{
	
	const MEDIA_IMAGE = 'Image';
	const MEDIA_ICON = 'Icon';
	const MEDIA_VIDEO = 'Video';
	const MEDIA_AUDIO = 'Audio';
	protected $validImageAndIconTypes = array('jpg','png');
	protected $validAudioTypes = array('mp3','m4a','caf');
	protected $validVideoTypes = array('mp4','m4v','3gp');
	
	
	/**
     * Fetch all Media
     * @returns the media
     */
	public function getMedia($intGameID)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM media WHERE game_id = {$prefix} or game_id = 0";
		NetDebug::trace($query);

		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(1, NULL, "SQL Error");
		
		$returnData = new returnData(0, array());
		
		//Calculate the media types
		while ($mediaRow = mysql_fetch_array($rsResult)) {

			$mediaItem = array();
			$mediaItem['media_id'] = $mediaRow['media_id'];
			$mediaItem['name'] = $mediaRow['name'];
			$mediaItem['file_name'] = $mediaRow['file_name'];

			$mediaItem['url_path'] = Config::gamedataWWWPath . "/{$mediaRow['game_id']}/" . Config::gameMediaSubdir;
			
			if ($mediaRow['is_icon'] == '1') $mediaItem['type'] = self::MEDIA_ICON;
			else $mediaItem['type'] = $this->getMediaType($mediaRow['file_name']);
			
			if ($mediaRow['game_id'] == 0) $mediaItem['is_default'] = 1;
			else $mediaItem['is_default'] = 0;
			
			array_push($returnData->data, $mediaItem);
		}
		
		NetDebug::trace($rsResult);

		return $returnData;
	}
	
	/**
     * Fetch the valid file extensions
     * @returns the extensions
     */
	public function getValidAudioExtensions()
	{
		return new returnData(0, $this->validAudioTypes);
	}
	
	/**
     * Fetch the valid file extensions
     * @returns the extensions
     */
	public function getValidVideoExtensions()
	{
		return new returnData(0, $this->validVideoTypes);
	}

	/**
     * Fetch the valid file extensions
     * @returns the extensions
     */
	public function getValidImageAndIconExtensions()
	{
		return new returnData(0, $this->validImageAndIconTypes);
	}

		
	
	/**
     * Create a media record
     * @returns the new mediaID on success
     */
	public function createMedia($intGameID, $strName, $strFileName, $boolIsIcon)
	{
		
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		if ($boolIsIcon && $this->getMediaType($strFileName) != self::MEDIA_IMAGE)
			return new returnData(4, NULL, "Icons must have a valid Image file extension");
		            	
		$query = "INSERT INTO media 
					(game_id, name, file_name, is_icon)
					VALUES ('{$intGameID}','{$strName}', '{$strFileName}',{$boolIsIcon})";
		
		NetDebug::trace("Running a query = $query");	
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		return new returnData(0, mysql_insert_id());
	}

	
	
	/**
     * Update a specific Media
     * @returns true if edit was done, false if no changes were made
     */
	public function renameMedia($intGameID, $intMediaID, $strName)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		//Update this record
		$query = "UPDATE {$prefix}_media 
					SET name = '{$strName}' 
					media = '{$strFileName}'
					WHERE media_id = '{$intMediaID}' and game_id = '{$intGameID}'";
		
		NetDebug::trace("updateNpc: Running a query = $query");	
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);	
	}
		
	
	/**
     * Delete a Media Item
     * @returns true if delete was done, false if no changes were made
     */
	public function deleteMedia($intGameID, $intMediaID)
	{
		
		$query = "SELECT * FROM {$prefix}_media 
					WHERE media_id = {$intMediaID} and game_id = {$intGameID}";
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());
		
		$mediaRow = mysql_fetch_array($rsResult);
		if ($mediaRow === FALSE) return new returnData(2, NULL, "Invalid Media Record");


		//Delete the file		
		$fileToDelete = Config::gamedataFSPath . "/{$intGameID}/" . $mediaRow['file_name'];
		if (!@unlink($fileToDelete)) 
			return new returnData(4, NULL, "Could not delete: $fileToDelete");
		
		
		//Delete the Record
		$query = "DELETE FROM {$prefix}_media 
					WHERE media_id = {$intMediaID} and game_id = {$intGameID}";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());
		
		if (mysql_affected_rows()) {
			return new returnData(0, TRUE);
		}
		else {
			return new returnData(0, FALSE);
		}
		
	}	
	
	
	/**
	* @returns path to the media directory on the file system
	*/
	public function getMediaDirectory($gameID){
		return new returnData(0, Config::gamedataFSPath . "/{$gameID}/" . Config::gameMediaSubdir);
	}
	
	/**
	* @returns path to the media directory URL
	*/
	public function getMediaDirectoryURL($gameID){
		return new returnData(0, Config::gamedataFSPath . "/{$gameID}/". Config::gameMediaSubdir);
	}	

	/**
     * Determine the Item Type
     * @returns "Audio", "Video" or "Image"
     */
	protected function getMediaType($strMediaFileName) {
		$mediaParts = pathinfo($strMediaFileName);
 		$mediaExtension = $mediaParts['extension'];
 		
 		if (in_array($mediaExtension, $this->validImageAndIconTypes )) return self::MEDIA_IMAGE;
 		else if (in_array($mediaExtension, $this->validAudioTypes )) return self::MEDIA_AUDIO;
		else if (in_array($mediaExtension, $this->validVideoTypes )) return self::MEDIA_VIDEO;
 		
 		return FALSE;
 	}	
	
}