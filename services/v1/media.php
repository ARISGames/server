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
	protected $validVideoTypes = array('mp4','m4v','3gp','mov');


	/**
	 * Fetch all Media
	 * @returns the media
	 */
	public function getMedia($intGameID)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix && $intGameID != 0) return new returnData(1, NULL, "invalid game id");

		if ($intGameID == 0) $query = "SELECT * FROM media WHERE game_id = 0";
		else $query = "SELECT * FROM media WHERE game_id = {$prefix} or game_id = 0";

		//NetDebug::trace($query);


		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

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

		//NetDebug::trace($rsResult);

		return $returnData;
	}

	/**
	 * Fetch one Media Item
	 * @returns the media item
	 */
	public function getMediaObject($intGameID, $intMediaID)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM media WHERE (game_id = {$prefix} OR game_id = 0) AND media_id = {$intMediaID} LIMIT 1";
		//NetDebug::trace($query);
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

		$mediaRow = mysql_fetch_object($rsResult);
		if (!$mediaRow) {
			//NetDebug::trace("No matching media id within the game, checking for a default");
			$query = "SELECT * FROM media WHERE game_id = 0 AND media_id = {$intMediaID} LIMIT 1";
			//NetDebug::trace($query);
			$rsResult = @mysql_query($query);
			if (mysql_error()) return new returnData(3, NULL, "SQL Error");	
			$mediaRow = mysql_fetch_array($rsResult);
			if (!$mediaRow) return new returnData(2, NULL, "No matching media for game");
		}

		$mediaItem = new stdClass;
		$mediaItem->media_id = $mediaRow->media_id;
		$mediaItem->name = $mediaRow->name;
		$mediaItem->file_name = $mediaRow->file_name;

		$mediaItem->url_path = Config::gamedataWWWPath . "/" .$mediaRow->game_id . "/" . Config::gameMediaSubdir;

		if ($mediaRow->is_icon == '1') $mediaItem->type = self::MEDIA_ICON;
		else $mediaItem->type = Media::getMediaType($mediaRow->file_name);

		if ($mediaRow->game_id == 0) $mediaItem->is_default = 1;
		else $mediaItem->is_default = 0;


		return new returnData(0, $mediaItem);
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

		$prefix = Module::getPrefix($intGameID);
		if (!$prefix || $intGameID == 0) return new returnData(1, NULL, "invalid game id");

		$strName = addslashes($strName);

		if ($boolIsIcon && $this->getMediaType($strFileName) != self::MEDIA_IMAGE)
			return new returnData(4, NULL, "Icons must have a valid Image file extension");

		$query = "INSERT INTO media 
			(media_id, game_id, name, file_name, is_icon)
			VALUES ('".Module::findLowestIdFromTable('media','media_id')."','{$intGameID}','{$strName}', '{$strFileName}',{$boolIsIcon})";

		NetDebug::trace("Running a query = $query");	

		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:".mysql_error());

		$media->media_id = mysql_insert_id();
		$media->name = $strName;
		$media->file_name = $strFileName;
		$media->is_icon = $boolIsIcon;
		$media->url_path = Config::gamedataWWWPath . "/{$intGameID}/" . Config::gameMediaSubdir;

		if ($media->is_icon == '1') $media->type = self::MEDIA_ICON;
		else $media->type = Media::getMediaType($media->file_name);

		return new returnData(0,$media);
	}



	/**
	 * Update a specific Media
	 * @returns true if edit was done, false if no changes were made
	 */
	public function renameMedia($intGameID, $intMediaID, $strName)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$strName = addslashes($strName);

		//Update this record
		$query = "UPDATE media 
			SET name = '{$strName}' 
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

		$query = "SELECT * FROM media 
			WHERE media_id = {$intMediaID} and game_id = {$intGameID}";
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());

		$mediaRow = mysql_fetch_array($rsResult);
		if ($mediaRow === FALSE) return new returnData(2, NULL, "Invalid Media Record");

		//Delete the Record
		$query = "DELETE FROM media 
			WHERE media_id = {$intMediaID} and game_id = {$intGameID}";

		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());

		//Delete the file		
		$fileToDelete = Config::gamedataFSPath . "/{$intGameID}/" . $mediaRow['file_name'];
		if (!@unlink($fileToDelete)) 
			return new returnData(4, NULL, "Record Deleted but file was not: $fileToDelete");

		//Done
		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);	


	}	


	/**
	 * @returns path to the media directory on the file system
	 */
	public function getMediaDirectory($gameID){
		return new returnData(0, Config::gamedataFSPath . "/{$gameID}" . Config::gameMediaSubdir);
	}

	/**
	 * @returns path to the media directory URL
	 */
	public function getMediaDirectoryURL($gameID){
		return new returnData(0, Config::gamedataWWWPath . "/{$gameID}". Config::gameMediaSubdir);
	}	

	/**
	 * Determine the Item Type
	 * @returns "Audio", "Video" or "Image"
	 */
	public function getMediaType($strMediaFileName) {
		$mediaParts = pathinfo($strMediaFileName);
		$mediaExtension = $mediaParts['extension'];

		$validImageAndIconTypes = array('jpg','png');
		$validAudioTypes = array('mp3','m4a','caf');
		$validVideoTypes = array('mp4','m4v','3gp','mov');

		if (in_array($mediaExtension, $validImageAndIconTypes )) return Media::MEDIA_IMAGE;
		else if (in_array($mediaExtension, $validAudioTypes )) return Media::MEDIA_AUDIO;
		else if (in_array($mediaExtension, $validVideoTypes )) return Media::MEDIA_VIDEO;

		return '';
	}	

}
?>
