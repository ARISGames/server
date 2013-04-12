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

    public function getMedia($gameId)
    {
        if ($gameId == 0) $query = "SELECT * FROM media WHERE game_id = 0 AND SUBSTRING(file_path,1,1) != 'p'";
        else $query = "SELECT * FROM media WHERE game_id = {$gameId} OR (game_id = 0 AND SUBSTRING(file_path,1,1) != 'p')";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $returnData = new returnData(0, array());

        //Calculate the media types
        while ($mediaRow = mysql_fetch_array($rsResult)) {

            $mediaItem = array();
            $mediaItem['media_id'] = $mediaRow['media_id'];
            $mediaItem['game_id'] = $mediaRow['game_id'];
            $mediaItem['name'] = $mediaRow['name'];
            $mediaItem['file_path'] = $mediaRow['file_path'];
            $mediaItem['file_name'] = $mediaRow['file_path']; //this is for legacy reasons... Phil 10/12/2012
            $mediaItem['url_path'] = Config::gamedataWWWPath . "/" . Config::gameMediaSubdir;

            if ($mediaRow['is_icon'] == '1') $mediaItem['type'] = self::MEDIA_ICON;
            else $mediaItem['type'] = $this->getMediaType($mediaRow['file_path']);

            if ($mediaRow['game_id'] == 0) $mediaItem['is_default'] = 1;
            else $mediaItem['is_default'] = 0;

            array_push($returnData->data, $mediaItem);
        }
        return $returnData;
    }

    public function getMediaObject($gameId, $intMediaID)
    {
        //apparently, "is_numeric(NAN)" returns 'true'. NAN literally means "Not A Number". Think about that one for a sec.
        if(!$intMediaID || !is_numeric($intMediaID) || $intMediaID == NAN) return new returnData(2, NULL, "No matching media");

        $query = "SELECT * FROM media WHERE media_id = {$intMediaID} LIMIT 1";
        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $mediaRow = mysql_fetch_object($rsResult);
        if (!$mediaRow) return new returnData(2, NULL, "No matching media");

        $mediaItem = new stdClass;
        $mediaItem->media_id = $mediaRow->media_id;
        $mediaItem->name = $mediaRow->name;
        $mediaItem->file_path = $mediaRow->file_path;
        $mediaItem->file_name = $mediaRow->file_path; //this is for legacy reasons... Phil 10/12/2012

        $mediaItem->url_path = Config::gamedataWWWPath . "/" . Config::gameMediaSubdir;

        if ($mediaRow->is_icon == '1') $mediaItem->type = self::MEDIA_ICON;
        else $mediaItem->type = Media::getMediaType($mediaRow->file_path);

        if ($mediaRow->game_id == 0) $mediaItem->is_default = 1;
        else $mediaItem->is_default = 0;

        return new returnData(0, $mediaItem);
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
        if($gameId == 'player') $gameId = '';//gameId column = int, so this will conform for sql query

        $strName = addslashes($strName);

        if ($boolIsIcon && $this->getMediaType($strFileName) != self::MEDIA_IMAGE)
            return new returnData(4, NULL, "Icons must have a valid Image file extension");

        $query = "INSERT INTO media 
            (game_id, name, file_path, is_icon)
            VALUES ('{$gameId}','{$strName}', '".$gameId."/".$strFileName."',{$boolIsIcon})";

        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:".mysql_error());

        $media->media_id = mysql_insert_id();
        $media->name = $strName;
        $media->file_path = $gameId."/".$strFileName;
        $media->file_name = $gameId."/".$strFileName; //this is for legacy reasons... Phil 10/12/2012
        $media->is_icon = $boolIsIcon;
        $media->url_path = Config::gamedataWWWPath . "/" . Config::gameMediaSubdir;

        if ($media->is_icon == '1') $media->type = self::MEDIA_ICON;
        else $media->type = Media::getMediaType($media->file_path);

        return new returnData(0,$media);
    }

    public function renameMedia($gameId, $intMediaID, $strName, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        if($gameId == 'player') $gameId = '';

        $strName = addslashes($strName);

        //Update this record
        $query = "UPDATE media 
            SET name = '{$strName}' 
            WHERE media_id = '{$intMediaID}' and game_id = '{$gameId}'";

        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);	
    }

    public function deleteMedia($gameId, $intMediaID, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $query = "SELECT * FROM media 
            WHERE media_id = {$intMediaID}";
        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error:". mysql_error());

        $mediaRow = mysql_fetch_array($rsResult);
        if ($mediaRow === FALSE) return new returnData(2, NULL, "Invalid Media Record");

        //Delete the Record
        $query = "DELETE FROM media 
            WHERE media_id = {$intMediaID}";

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

    public function getMediaDirectory($gameID)
    {
        return new returnData(0, Config::gamedataFSPath . "/{$gameID}" . Config::gameMediaSubdir);
    }

    public function getMediaDirectoryURL($gameID)
    {
        return new returnData(0, Config::gamedataWWWPath . "/{$gameID}". Config::gameMediaSubdir);
    }	

    public function getMediaType($strMediaFileName)
    {
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
