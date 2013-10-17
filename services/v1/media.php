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

    public function parseRawMedia($media)
    {
        $media->file_name = $media->file_path; //this is for legacy reasons... Phil 10/12/2012
        $media->thumb_file_path = substr($media->file_path,0,strrpos($media->file_path,'.')).'_128'.substr($media->file_path,strrpos($media->file_path,'.'));
        $media->url_path = Config::gamedataWWWPath . "/" . Config::gameMediaSubdir;
        $media->url       = $media->url_path."/".$media->file_path;
        $media->thumb_url = $media->url_path."/".$media->thumb_file_path;

        if($media->is_icon == '1') $media->type = self::MEDIA_ICON;
        else $media->type = Media::getMediaType($media->file_path);

        if($media->game_id == 0) $media->is_default = 1;
        else $media->is_default = 0;

        return $media;
    }

    public function getMedia($gameId)
    {
        $medias = Module::query("SELECT * FROM media WHERE (game_id = '{$gameId}' OR game_id = 0) AND SUBSTRING(file_path,1,1) != 'p'");

        $data = array();
        while($media = mysql_fetch_object($medias))
            $data[] = Media::parseRawMedia($media);
        return new returnData(0, $data);
    }

    public function getMediaObject($gameId, $intMediaId)
    {
        //apparently, "is_numeric(NAN)" returns 'true'. NAN literally means "Not A Number". Think about that one for a sec.
        if(!$intMediaId || !is_numeric($intMediaId) || $intMediaId == NAN //return new returnData(2, NULL, "No matching media");
        || !($media = Module::queryObject("SELECT * FROM media WHERE media_id = {$intMediaId} LIMIT 1")))
	{
		$media = new stdClass;
                $media->game_id = 0;
        	$media->media_id = $intMediaId;
        	$media->name = "Default NPC";
        	$media->file_path = "0/npc.png";
        	return new returnData(0, Media::parseRawMedia($media));
	}

        return new returnData(0, Media::parseRawMedia($media));
    }	

    public function getValidAudioExtensions()
    {
        return new returnData(0, $this->validAudioTypes);
    }

    public function getValidVideoExtensions()
    {
        return new returnData(0, $this->validVideoTypes);
    }

    public function getValidImageAndIconExtensions()
    {
        return new returnData(0, $this->validImageAndIconTypes);
    }

    public function createMedia($gameId, $strName, $strFileName, $boolIsIcon)
    {
        if($gameId == "player")
        {
            $gameId = 0;
            $strFileName = "player/".$strFileName;
        }
        else
        {
            $strFileName = $gameId."/".$strFileName;
        }

        $strName = addslashes($strName);

        if ($boolIsIcon && $this->getMediaType($strFileName) != self::MEDIA_IMAGE)
            return new returnData(4, NULL, "Icons must have a valid Image file extension");

        $query = "INSERT INTO media 
            (game_id, name, file_path, is_icon)
            VALUES ('{$gameId}','{$strName}', '".$strFileName."',{$boolIsIcon})";

        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:".mysql_error());

        $media = new stdClass();
        $media->media_id = mysql_insert_id();
        $media->game_id = $gameId;
        $media->name = $strName;
        $media->file_path = $strFileName;

        return new returnData(0,Media::parseRawMedia($media));
    }

    public function renameMedia($gameId, $intMediaId, $strName, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        if($gameId == 'player') $gameId = '';

        $strName = addslashes($strName);

        //Update this record
        $query = "UPDATE media 
            SET name = '{$strName}' 
            WHERE media_id = '{$intMediaId}' and game_id = '{$gameId}'";

        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);	
    }

    public function deleteMedia($gameId, $intMediaId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $query = "SELECT * FROM media 
            WHERE media_id = {$intMediaId}";
        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());

        $mediaRow = mysql_fetch_array($rsResult);
        if($mediaRow === FALSE) return new returnData(2, NULL, "Invalid Media Record");

        //Delete the Record
        $query = "DELETE FROM media 
            WHERE media_id = {$intMediaId}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error());

        //Delete the file		
        $fileToDelete = Config::gamedataFSPath . "/" . $mediaRow['file_path'];
        if (!@unlink($fileToDelete)) 
            return new returnData(4, NULL, "Record Deleted but file was not: $fileToDelete");

        //Done
        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);	
    }	

    public function getMediaDirectory($gameId)
    {
        return new returnData(0, Config::gamedataFSPath . "/{$gameId}" . Config::gameMediaSubdir);
    }

    public function getMediaDirectoryURL($gameId)
    {
        return new returnData(0, Config::gamedataWWWPath . "/{$gameId}". Config::gameMediaSubdir);
    }	

    public function getMediaType($strMediaFileName)
    {
        $mediaParts = pathinfo($strMediaFileName);
        $mediaExtension = $mediaParts['extension'];

        $validImageAndIconTypes = array('jpg','png','gif');
        $validAudioTypes = array('mp3','m4a','caf');
        $validVideoTypes = array('mp4','m4v','3gp','mov');

        if (in_array($mediaExtension, $validImageAndIconTypes )) return Media::MEDIA_IMAGE;
        else if (in_array($mediaExtension, $validAudioTypes )) return Media::MEDIA_AUDIO;
        else if (in_array($mediaExtension, $validVideoTypes )) return Media::MEDIA_VIDEO;

        return '';
    }	
}
?>
