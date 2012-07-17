<?php
require_once("module.php");


class Overlays extends Module
{



	/**
	 * Fetch all Overlays and tiles for game
	 * @returns the media
	 */
	public function getOverlays($intGameID)
	{
		$prefix = Module::getPrefix($intGameID);
		if (!$prefix && $intGameID != 0) return new returnData(1, NULL, "invalid game id");

		if ($intGameID == 0) $query = "SELECT * FROM overlays, overlay_tiles, media WHERE game_id = 0 AND overlays.overlay_id = overlay_tiles.overlay_id AND overlay_tiles.media_id = media.media_id";
		else $query = "SELECT * FROM overlays, overlay_tiles, media WHERE (overlays.game_id = {$prefix}) AND overlays.overlay_id = overlay_tiles.overlay_id AND overlay_tiles.media_id = media.media_id";

		//NetDebug::trace($query);


		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

		$returnData = new returnData(0, array());

		//For each overlay, get tiles and associated info
		while ($overlayRow = mysql_fetch_array($rsResult)) {

			$tile = array();
			$tile['overlay_id'] = $overlayRow['overlay_id'];
			$tile['sort_order'] = $overlayRow['sort_order'];
			$tile['alpha'] = $overlayRow['alpha'];
            $tile['zoom'] = $overlayRow['zoom'];
            $tile['file_name'] = $overlayRow['file_name'];
            $tile['media_id'] = $overlayRow['media_id'];
            $tile['x'] = $overlayRow['x'];
            $tile['y'] = $overlayRow['y'];

			array_push($returnData->data, $tile);
		}

		//NetDebug::trace($rsResult);

		return $returnData;
	}

	/**
	 * Fetch one Tile
	 * @returns the media item
	 */
	public function getTiles($intOverlayID)
	{
       $query = "SELECT * FROM overlay_tiles, media WHERE (overlays.overlay_id = {$intOverlayID} AND overlay_tiles.media_id = media.media_id";
        
		//NetDebug::trace($query);
        
        
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        
		$returnData = new returnData(0, array());
        
		//For each overlay, get tiles and associated info
		while ($tileRow = mysql_fetch_array($rsResult)) {
            
			$tile = array();
			$tile['overlay_id'] = $tileRow['overlay_id'];
			$tile['sort_order'] = $tileRow['sort_order'];
			$tile['alpha'] = $tileRow['alpha'];
            $tile['zoom'] = $tileRow['zoom'];
            $tile['file_name'] = $tileRow['file_name'];
            $tile['media_id'] = $tileRow['media_id'];
            $tile['x'] = $tileRow['x'];
            $tile['y'] = $tileRow['y'];
            
			array_push($returnData->data, $tile);
		}
        
		//NetDebug::trace($rsResult);
        
		return $returnData;
		
		return new returnData(0, $tile);
	}	


    /**
	 * Reads directory structure of overlays and populates overlay tables in database
	 * @returns 0 on success
	 */
// To test::  http://dev.arisgames.org/server/json.php/v1.overlays.writeOverlaysToDatabase/3069

     public function writeOverlaysToDatabase($intGameID)
     {
         // go to folder for game ID: /var/www/html/server/gamedata/{game_id}/MapOverlays/0
         $sGameDir = "/var/www/html/server/gamedata/".$intGameID."/";
         $sOverlayDir = $sGameDir ."MapOverlays";
         $dirGame = new DirectoryIterator($sOverlayDir);
         $intOverlayID = 0;
         
         // step through overlay folders for game
           foreach ($dirGame as $dirOverlay) {
            if ($dirOverlay->isDir() && !$dirOverlay->isDot()) {
                
                 // check if there's already a row in overlays table for this overlay.  if not, create one
                 $query = "INSERT IGNORE INTO overlays SET game_id = {$intGameID}, game_overlay_id={$intOverlayID}";
                 $rsResult = @mysql_query($query);
                 if (mysql_error()) return new returnData(3, NULL, "SQL Error at Overlay Level: " . $query);   
                 
                 $overlay_id = mysql_insert_id();
                 
                 $diOverlay = new DirectoryIterator($dirOverlay->getPath()."/".$dirOverlay->getFilename());
                
                 // step through zoom level folders
                 foreach ($diOverlay as $dirZoom) {
                        if ($dirZoom->isDir() && !$dirZoom->isDot()) {   
                            
                            // step through y folders
                            $diZoom = new DirectoryIterator($dirZoom->getPath()."/".$dirZoom->getFilename());
                            foreach ($diZoom as $dirX) {
                            if ($dirX->isDir() && !$dirX->isDot()) {
                                    
                               // step through image files (with x value for filename)
                                $diX = new DirectoryIterator($dirX->getPath()."/".$dirX->getFilename());
                               foreach ($diX as $fileY) {
                                   if (!$fileY->isDot()) {
                                   $fileYName = $fileY->getFilename();
                                   $fileYShortName = substr($fileYName, 0, -4);
                                   $dirZoomName = $dirZoom->getFilename();
                                   $dirXName = $dirX->getFilename();
                                   $fullFileName = $intOverlayID . "_" . $dirZoomName . "_" . $dirXName . "_" . $fileYName;
                                   $fullNewDirAndFileName = $sGameDir . $fullFileName;
                                   $fullOldDirAndFileName = $sOverlayDir. "/" . $intOverlayID . "/" . $dirZoomName . "/" . $dirXName . "/" . $fileYName;
                                    $query3 = "INSERT INTO media SET game_id = {$intGameID}, name = '{$fullFileName}', file_name = '{$fullFileName}'";
                                    $rsResult3 = @mysql_query($query3);
                                    if (mysql_error()) return new returnData(3, NULL, "SQL Error inserting Media: ". $query3);   
                               
                                    $media_id = mysql_insert_id();
                               
                                    $query4 = "REPLACE INTO overlay_tiles SET overlay_id = {$overlay_id}, media_id={$media_id}, zoom={$dirZoomName}, x={$dirXName}, y={$fileYShortName}";
                                    $rsResult4 = @mysql_query($query4);
                                    if (mysql_error()) return new returnData(3, NULL, "SQL Error inserting tiles: ". $query4);  
                                       
                                    // copy file into root game directory
                                    copy($fullOldDirAndFileName, $fullNewDirAndFileName);
                                    
                                   }
                               }
                               
                                    
                            }
                            }
                        }
                 }
                $intOverlayID = $intOverlayID + 1;
            }
            }
     
         return $fullOldDirAndFileName . "->" . $fullNewDirAndFileName;
     }	


    public function unzip($intGameID, $file){ 
        // to test: http://dev.arisgames.org/server/json.php/v1.overlays.unzip/3069/1.zip
        $sGameDir = "/var/www/html/server/gamedata/{$intGameID}/";
        $sOverlayDir = "{$sGameDir}MapOverlays/";
        $fullFile = $sOverlayDir . $file;
        $zip = zip_open($fullFile); 
        if(is_resource($zip)){ 
            $tree = ""; 
            while(($zip_entry = zip_read($zip)) !== false){ 
                echo "Unpacking ".zip_entry_name($zip_entry)."\n"; 
                if(strpos(zip_entry_name($zip_entry), DIRECTORY_SEPARATOR) !== false){ 
                    $last = strrpos(zip_entry_name($zip_entry), DIRECTORY_SEPARATOR); 
                    $dir = $sOverlayDir . substr(zip_entry_name($zip_entry), 0, $last); 
                    $file = substr(zip_entry_name($zip_entry), strrpos(zip_entry_name($zip_entry), DIRECTORY_SEPARATOR)+1); 
                    if(!is_dir($dir)){ 
                        @mkdir($dir, 0755, true) or die("Unable to create $dir\n"); 
                    } 
                    if(strlen(trim($file)) > 0){ 
                        $return = @file_put_contents($dir."/".$file, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry))); 
                        if($return === false){ 
                            die("Unable to write file $dir/$file\n"); 
                        } 
                    } 
                }else{ 
                    file_put_contents($file, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry))); 
                } 
            } 
        }else{ 
            echo "Unable to open zip file\n"; 
        } 
       
        
    }
        
    public function createTiles($intGameID, $intOverlayID, $imageFileName, $minLat, $maxLat, $minLon, $maxLon)
    {
        // to test: http://dev.arisgames.org/server/json.php/v1.overlays.createTiles/3069/0/MapOutline.png/43.0401/43.0445/-89.2458/-89.2333
        // http://dev.arisgames.org/server/json.php/v1.overlays.createTiles/3069/0/NewMapOverlay.png/43.0329/43.0452/-89.2513/-89.2318
        
        // see https://developers.google.com/kml/articles/raster for details on gdal commands
        
        // Get info about the image
        // -- gdalinfo $imageFileName 
        // -- look for Upper Left ( 0.0, 0.0)  and Lower Right   (21600.0, 10800.0)
           // ----- need to figure out where to send the output and how to parse it
        $sGameDir = "/var/www/html/server/gamedata/{$intGameID}/";
        $sOverlayDir = "{$sGameDir}MapOverlays/{$intOverlayID}/";
        $cmd = "gdalinfo {$sGameDir}{$imageFileName}";
        $exit = exec($cmd,$fileInfo, $stderr);  
        echo $cmd;
        
        //parse the output to get the upper left and lower rioght coordinates  
        foreach ($fileInfo as $line)  
        {  
            //echo "$line" . PHP_EOL;  
            
            $upperLeftPos = strpos($line, "Upper Left");
            $lowerRightPos = strpos($line, "Lower Right");
            
            
            if ($upperLeftPos !== false) {
                $openParenPos = strpos($line, "(", $upperLeftPos);
                $commaPos = strpos($line, ",", $openParenPos);
                $closedParenPos = strpos($line, ")", $commaPos - 1);
                $minX = trim(substr($line, $openParenPos + 1, (strlen($line) - $openParenPos) - (strlen($line) - $commaPos) - 1));
                $minY = trim(substr($line, $commaPos + 1, (strlen($line) - $commaPos) - (strlen($line) - $closedParenPos) - 1));
                //echo $minX . "      ";
                //echo $minY . "      ";
            }
            if ($lowerRightPos !== false) {
                $openParenPos = strpos($line, "(", $lowerRightPos);
                $commaPos = strpos($line, ",", $openParenPos);
                $closedParenPos = strpos($line, ")", $commaPos);
                $maxX = trim(substr($line, $openParenPos + 1, (strlen($line) - $openParenPos) - (strlen($line) - $commaPos) - 1));
                $maxY = trim(substr($line, $commaPos + 1, (strlen($line) - $commaPos) - (strlen($line) - $closedParenPos) - 1));
                //echo $maxX . "      ";
                //echo $maxY . "      ";
            }


        }  
        
        
        
        // Georeference  the image
        $cmd = "gdal_translate -of VRT -gcp {$minX} {$minY} {$minLon} {$minLat} -gcp {$minX} {$maxY} {$minLon} {$maxLat} -gcp {$maxX} {$maxY} {$maxLon} {$maxLat} {$sGameDir}{$imageFileName} {$sGameDir}{$imageFileName}.vrt";
        $exit = exec($cmd,$stdout, $stderr);  
        echo $cmd;
        echo $stderr;  

        
        // warp the image
        //$cmd = "gdalwarp -of VRT {$sGameDir}{$imageFileName}.vrt {$sGameDir}{$imageFileName}2.vrt";  
        //$exit = exec($cmd,$stdout, $stderr); 
        //echo $cmd;
        //echo $stderr;
        
        // Create the tiles    
        $cmd = "gdal2tiles.py -p geodetic -k --s_srs=WGS84 {$sGameDir}{$imageFileName}.vrt {$sOverlayDir}";
        $exit = exec($cmd,$stdout, $stderr);  
        echo $cmd;
        echo $stdout;
               

    }
    
	/**
	 * Create a media record
	 * @returns the new mediaID on success
	 */
    /*
	public function createMedia($intGameID, $strName, $strFileName, $boolIsIcon)
	{

		$prefix = Module::getPrefix($intGameID);
		if (!$prefix || $intGameID == 0) return new returnData(1, NULL, "invalid game id");

		$strName = addslashes($strName);

		if ($boolIsIcon && $this->getMediaType($strFileName) != self::MEDIA_IMAGE)
			return new returnData(4, NULL, "Icons must have a valid Image file extension");

		$query = "INSERT INTO media 
			(game_id, name, file_name, is_icon)
			VALUES ('{$intGameID}','{$strName}', '{$strFileName}',{$boolIsIcon})";

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
	}*/



	/**
	 * Update a specific Media
	 * @returns true if edit was done, false if no changes were made
	 */
    /*
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
	}*/


	/**
	 * Delete a Media Item
	 * @returns true if delete was done, false if no changes were made
	 */
    /*
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


	}	*/





}
?>
