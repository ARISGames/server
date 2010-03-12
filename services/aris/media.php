<?php
require("module.php");


class Media extends Module
{
	
	const MEDIA_IMAGE = 'Image';
	const MEDIA_VIDEO = 'Video';
	const MEDIA_AUDIO = 'Audio';
	protected $validImageTypes = array('jpg','png');
	protected $validAudioTypes = array('mp3','m4a');
	protected $validVideoTypes = array('mp4','m4v','3gp');
	
	
	/**
     * Fetch all Media
     * @returns the media
     */
	public function getMedia($intGameID)
	{
		
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		
		$query = "SELECT * FROM {$prefix}_media";
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
			$mediaItem['url_path'] = Config::gamedataWWWPath . "/{$prefix}/" . Config::gameMediaSubdir;
			$mediaItem['type'] = $this->getMediaType($mediaRow['file_name']);
			$mediaItem['is_default'] = $this->getMediaType($mediaRow['is_default']);
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
	public function getValidImageExtensions()
	{
		return new returnData(0, $this->validImageTypes);
	}

		
	
	/**
     * Create a media record
     * @returns the new mediaID on success
     */
	public function createMedia($intGameID, $strName, $strFileName)
	{
		
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		            	
		$query = "INSERT INTO {$prefix}_media 
					(name, file_name, is_default)
					VALUES ('{$strName}', '{$strFileName}',0)";
		
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
					WHERE media_id = '{$intMediaID}'";
		
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
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$query = "SELECT * FROM {$prefix}_media WHERE media_id = {$intMediaID}";
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());
		
		$mediaRow = mysql_fetch_array($rsResult);
		if ($mediaRow === FALSE) return new returnData(2, NULL, "Invalid Media ID");


		//Delete the file		
		$fileToDelete = Config::gamedataFSPath . "/{$prefix}/" . $mediaRow['file_name'];
		if (!@unlink($fileToDelete)) 
			return new returnData(4, NULL, "Could not delete: $fileToDelete");
		
		
		//Delete the Record
		$query = "DELETE FROM {$prefix}_media WHERE media_id = {$intMediaID}";
		
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
	public function getMediaDirectory($prefix){
		return new returnData(0, Config::gamedataFSPath . "/{$prefix}/" . Config::gameMediaSubdir);
	}
	
	/**
	* @returns path to the media directory URL
	*/
	public function getMediaDirectoryURL($prefix){
		return new returnData(0, Config::gamedataFSPath . "/{$prefix}/". Config::gameMediaSubdir);
	}	

	/**
     * Determine the Item Type
     * @returns "Audio", "Video" or "Image"
     */
	protected function getMediaType($strMediaFileName) {
		$mediaParts = pathinfo($strMediaFileName);
 		$mediaExtension = $mediaParts['extension'];
 		
 		if (in_array($mediaExtension, $this->validImageTypes )) return self::MEDIA_IMAGE;
 		else if (in_array($mediaExtension, $this->validAudioTypes )) return self::MEDIA_AUDIO;
		else if (in_array($mediaExtension, $this->validVideoTypes )) return self::MEDIA_VIDEO;
 		
 		return FALSE;
 	}	
	
}