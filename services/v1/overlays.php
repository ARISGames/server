<?php
require_once("module.php");
/*
+-----------------------+------------------+------+-----+---------+----------------+
| Field                 | Type             | Null | Key | Default | Extra          |
+-----------------------+------------------+------+-----+---------+----------------+
| overlay_id            | int(11) unsigned | NO   | PRI | NULL    | auto_increment |
| game_id               | int(11) unsigned | NO   |     | NULL    |                |
| name                  | varchar(100)     | NO   |     |         |                |
| media_id              | int(11) unsigned | NO   |     | NULL    |                |
| top_left_latitude     | double           | NO   |     | 0       |                |
| top_left_longitude    | double           | NO   |     | 0       |                |
| top_right_latitude    | double           | NO   |     | 0       |                |
| top_right_longitude   | double           | NO   |     | 0       |                |
| bottom_left_latitude  | double           | NO   |     | 0       |                |
| bottom_left_longitude | double           | NO   |     | 0       |                |
+-----------------------+------------------+------+-----+---------+----------------+
*/

class Overlays extends Module
{

    public function getOverlays()
    {
        return new returnData(0, Module::queryArray("SELECT * FROM overlays")); 			
    }

    public function getOverlaysForGame($gameId)
    {
        return new returnData(0, Module::queryArray("SELECT * FROM overlays WHERE game_id = {$gameId};"));
    }

    public function getOverlaysForPlayer($gameId, $playerId)
    {
        $overlays = Module::queryArray("SELECT * FROM overlays WHERE game_id = {$gameId};");
        $overlayIds = array();
        for($i = 0; $i < count($overlays); $i++){
            $overlay = $overlays[$i];
            $display = Module::objectMeetsRequirements ($gameId, $playerId, "CustomMap", $overlay->overlay_id);
            if($display){
               $overlayObj = new stdClass();
               $overlayObj->overlay_id = $overlay->overlay_id;
               $overlayIds[] = $overlayObj;
            }
        }
        return new returnData(0, $overlayIds);
    }

    public function createOverlay($gameId, $name, $mediaId, $topLeftLat, $topLeftLong, $topRightLat, $topRightLong, $bottomLeftLat, $bottomLeftLong, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $name = addslashes($name);
        Module::query("INSERT INTO overlays (overlay_id, game_id, name, media_id, top_left_latitude, top_left_longitude, top_right_latitude, top_right_longitude, bottom_left_latitude, bottom_left_longitude) 
                                     VALUES (0, {$gameId}, '{$name}', {$mediaId}, {$topLeftLat}, {$topLeftLong}, {$topRightLat}, {$topRightLong}, {$bottomLeftLat}, {$bottomLeftLong});");    
        return new returnData(0);
    }

    public function deleteOverlay($overlayId, $gameId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        Module::query("DELETE FROM overlays WHERE overlay_id = {$overlayId};");
        return new returnData(0);
    }

    public function deleteOverlaysFromGame($gameId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");
        
        Module::query("DELETE FROM overlays WHERE game_id = {$gameId};");
        return new returnData(0);
    }

    public function updateOverlay($overlayId, $gameId, $name, $mediaId, $topLeftLat, $topLeftLong, $topRightLat, $topRightLong, $bottomLeftLat, $bottomLeftLong, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $newName = addslashes($newName);
        Module::query("UPDATE overlays SET game_id = {$gameId}, name = '{$name}', media_id = {$mediaId}, top_left_latitude = {$topLeftLat}, top_left_longitude = {$topLeftLong}, top_right_latitude = {$topRightLat}, top_right_longitude = {$topRightLong}, bottom_left_latitude = {$bottomLeftLat}, bottom_left_longitude = {$bottomLeftLong} WHERE overlay_id = {$overlayId};");
        return new returnData(0);
    }
    
    /*
    public function createOverlay($gameId, $name, $index, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $name = addslashes($name);	

        $overlayIdRow = Module::queryObject("SELECT max(overlay_id) as max_overlay_id FROM overlays");
        if(!$overlayIdRow->max_overlay_id) $overlayId = 0;
        $overlayId = $overlayIdRow->max_overlay_id + 1;

        $gameOverlayIdRow = Module::queryObject("SELECT  max(game_overlay_id) as max_game_overlay_id FROM overlays where game_id = {$gameId}");
        if(!$gameOverlayIdRow->max_game_overlay_id) $gameOverlayId = 0;
        $gameOverlayId = $gameOverlayIdRow->max_game_overlay_id + 1;

        $Module::query("REPLACE INTO overlays SET game_id = {$gameId}, overlay_id={$overlayId}, game_overlay_id={$gameOverlayId}, name='{$name}', sort_index={$index}");
        return new returnData(0, $overlayId); 			
    }

    public function updateOverlay($gameId, $overlayId, $strName, $index, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");
        $strName = addslashes($strName);	
        Module::query("UPDATE overlays SET name = '{$strName}', sort_index='{$index}' WHERE overlay_id = '{$overlayId}'");
        return new returnData(0);
    }

    public function deleteOverlay($gameId, $overlayId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        Module::query("DELETE FROM overlays WHERE overlay_id = {$overlayId}");
        Module::query("DELETE FROM overlay_tiles WHERE overlay_id = {$overlayId}");
        Module::query("DELETE FROM requirements WHERE content_type = 'CustomMap' AND content_id = '{$overlayId}' AND game_id = {$gameId}");

        return new returnData(0, TRUE);
    }	

    public function getOverlay($gameId, $overlayId)
    {
        $overlay = Module::queryObject("SELECT * FROM overlays WHERE game_id = {$gameId} AND overlay_id = {$overlayId}");
        $returnData = new returnData(0, $overlay);
        return $returnData;
    }

    public function getOverlaysForEditor($gameId)
    {
        $overlays = Module::queryArray("SELECT * FROM overlays WHERE overlays.game_id = {$gameId} ORDER BY sort_index");
        return new returnData(0, $overlays);
    }

    public function getOverlays($gameId)
    {
        $overlays = Module::queryArray("SELECT * FROM overlays, overlay_tiles, media WHERE (overlays.game_id = {$gameId}) AND overlays.overlay_id = overlay_tiles.overlay_id AND overlay_tiles.media_id = media.media_id ORDER BY overlays.sort_index");

        $overlayData = [];
        for($i = 0; $i < count($overlays); $i++)
        {
            $tile = new stdClass();
            $tile->overlay_id = $overlays[$i]->overlay_id;
            $tile->sort_order = $overlays[$i]->sort_order;
            $tile->alpha      = $overlays[$i]->alpha;
            $tile->zoom       = $overlays[$i]->zoom;
            $tile->file_path  = $overlays[$i]->file_path;
            $tile->file_name  = $overlays[$i]->file_path; //For legacy reasons... Phil 10/12/2012
            $tile->media_id   = $overlays[$i]->media_id;
            $tile->x          = $overlays[$i]->x;
            $tile->y          = $overlays[$i]->y;

            $overlayData[] = $tile;
        }

        return new returnData(0,$overlayData);
    }

    public function getCurrentOverlaysForPlayer($gameId, $intPlayerId)
    {
        $overlays = Module::queryArray("SELECT * FROM overlays, overlay_tiles, media WHERE (overlays.game_id = {$gameId}) AND overlays.overlay_id = overlay_tiles.overlay_id AND overlay_tiles.media_id = media.media_id ORDER BY overlays.sort_index");

        $overlayData = [];
        for($i = 0; $i < count($overlays); $i++)
        {
            if(!$this->objectMeetsRequirements($gameId, $intPlayerId, 'CustomMap', $overlay->overlay_id]))
                continue;

            $tile = new stdClass();
            $tile->overlay_id = $overlays[$i]->overlay_id;
            $tile->sort_order = $overlays[$i]->sort_order;
            $tile->alpha      = $overlays[$i]->alpha;
            $tile->zoom       = $overlays[$i]->zoom;
            $tile->file_path  = $overlays[$i]->file_path;
            $tile->file_name  = $overlays[$i]->file_path; //For legacy reasons... Phil 10/12/2012
            $tile->media_id   = $overlays[$i]->media_id;
            $tile->x          = $overlays[$i]->x;
            $tile->y          = $overlays[$i]->y;

            $overlayData[] = $tile;
        }

        return new returnData(0,$overlayData);
    }

    public function getTiles($overlayId)
    {
        $tiles = Module::query("SELECT * FROM overlay_tiles, media WHERE (overlays.overlay_id = {$overlayId} AND overlay_tiles.media_id = media.media_id");

        $tileData = [];
        for($i = 0; $i < count($tiles); $i++)
        {
            $tile = array();
            $tile->overlay_id = $tiles[$i]->overlay_id;
            $tile->sort_order = $tiles[$i]->sort_order;
            $tile->alpha      = $tiles[$i]->alpha;
            $tile->zoom       = $tiles[$i]->zoom;
            $tile->file_path  = $tiles[$i]->file_path;
            $tile->file_name  = $tiles[$i]->file_path; //For legacy reasons... Phil 10/12/2012
            $tile->media_id   = $tiles[$i]->media_id;
            $tile->x          = $tiles[$i]->x;
            $tile->y          = $tiles[$i]->y;

            $tileData[] = $tile;
        }

        return new returnData(0, $tileData);
    }	

    public function swapSortIndex($gameId, $overlayIdA, $overlayIdB, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $overlays = Module::queryArray("SELECT * FROM overlays WHERE overlay_id = '{$overlayIdA}' OR overlay_id = '{$overlayIdB}'");
        Module::query("UPDATE overlays SET sort_index = '{$overlays[0]->sort_index}' WHERE overlay_id = '{$overlays[1]->sort_index}'");
        Module::query("UPDATE overlays SET sort_index = '{$overlays[1]->sort_index}' WHERE overlay_id = '{$overlays[0]->sort_index}'");

        return new returnData(0);
    }

    //NO IDEA IF THIS WORKS- Phil 2/28/14
    public function writeOverlaysToDatabase($gameId)
    {
        // go to folder for game Id: /var/www/html/server/gamedata/{game_id}/MapOverlays/0
        $sGameDir = Config::gamedataFSPath."/".$gameId."/";
        $sOverlayDir = $sGameDir ."MapOverlays";
        $dirGame = new DirectoryIterator($sOverlayDir);
        $overlayId = 0;

        // step through overlay folders for game
        foreach ($dirGame as $dirOverlay)
        {
            if ($dirOverlay->isDir() && !$dirOverlay->isDot())
            {
                // check if there's already a row in overlays table for this overlay.  if not, create one
                Module::query("INSERT IGNORE INTO overlays SET game_id = {$gameId}, game_overlay_id={$overlayId}");
                $overlay_id = mysql_insert_id();

                $diOverlay = new DirectoryIterator($dirOverlay->getPath()."/".$dirOverlay->getFilename());

                // step through zoom level folders
                foreach($diOverlay as $dirZoom)
                {
                    if($dirZoom->isDir() && !$dirZoom->isDot())
                    {   
                        // step through y folders
                        $diZoom = new DirectoryIterator($dirZoom->getPath()."/".$dirZoom->getFilename());
                        foreach ($diZoom as $dirX)
                        {
                            if($dirX->isDir() && !$dirX->isDot())
                            {
                                // step through image files (with x value for filename)
                                $diX = new DirectoryIterator($dirX->getPath()."/".$dirX->getFilename());
                                foreach($diX as $fileY)
                                {
                                    if (!$fileY->isDot())
                                    {
                                        $fileYName = $fileY->getFilename();
                                        $fileYShortName = substr($fileYName, 0, -4);
                                        $dirZoomName = $dirZoom->getFilename();
                                        $dirXName = $dirX->getFilename();
                                        $fullFileName = $overlayId . "_" . $dirZoomName . "_" . $dirXName . "_" . $fileYName . "_" . time();
                                        $fullNewDirAndFileName = $sGameDir . $fullFileName;
                                        $fullOldDirAndFileName = $sOverlayDir. "/" . $overlayId . "/" . $dirZoomName . "/" . $dirXName . "/" . $fileYName;
                                        $filePath = $gameId . "/" . $fullFileName;
                                        Module::query("INSERT INTO media SET game_id = {$gameId}, name = '{$fullFileName}', file_path = '{$filePath}'");
                                        $media_id = mysql_insert_id();
                                        Module::query("REPLACE INTO overlay_tiles SET overlay_id = {$overlay_id}, media_id={$media_id}, zoom={$dirZoomName}, x={$dirXName}, y={$fileYShortName}");

                                        // copy file into root game directory
                                        copy($fullOldDirAndFileName, $fullNewDirAndFileName);
                                    }
                                }
                            }
                        }
                    }
                }
                $overlayId = $overlayId + 1;
            }
        }
        return $fullOldDirAndFileName . "->" . $fullNewDirAndFileName;
    }	

    //to test: http://arisgames.org/server/json.php/v1.overlays.writeOverlayToDatabase/3289/1/aris218f403f29adc83670ba6ccc2833b996 
    //^ THIS EXAMPLE URL IS NOW INVALID- NEED EDITOR ID AND AUTH TOKEN
    public function writeOverlayToDatabase($gameId, $overlayId, $folderName, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        // go to folder for game ID: /var/www/html/server/gamedata/{game_id}/
        $sGameDir = Config::gamedataFSPath."/".$gameId."/";
        $sOverlayDir = $sGameDir . $folderName;
        $diOverlay = new DirectoryIterator($sOverlayDir);
        $i=0;

        $rsResult = Module::query("DELETE FROM overlay_tiles WHERE overlay_id = {$overlayId}");

        foreach($diOverlay as $dirMain1)
        { 
            if($dirMain1->isDir() && !$dirMain1->isDot() && $i!=0 ) //&& !(strpos($dirMain1->getFilename(), "__") > 0) 
            { 
                $diMain1 = new DirectoryIterator($dirMain1->getPath()."/".$dirMain1->getFilename());
                foreach($diMain1 as $dirZoom)
                { 
                    //go to last one
                    if($dirZoom->isDir() && !$dirZoom->isDot()) //&& !(strpos($dirMain2->getFilename(), "__") > 0)
                    { 
                        // step through zoom level folders
                        foreach($diZoom as $dirX)
                        {
                            if($dirX->isDir() && !$dirX->isDot() )
                            { 
                                // step through y folders
                                $diX = new DirectoryIterator($dirX->getPath()."/".$dirX->getFilename());
                                foreach ($diX as $fileY)
                                {
                                    if (strpos($fileY->getFilename(), ".png" ) > 0 )
                                    {
                                        $fileYName = $fileY->getFilename();
                                        $fileYShortName = substr($fileYName, 0, -4);
                                        $dirMain1Name = $dirMain1->getFilename();
                                        $dirZoomName = $dirZoom->getFilename();
                                        $dirXName = $dirX->getFilename();
                                        $fullFileName = $overlayId . "_" . $dirZoomName . "_" . $dirXName . "_" . $fileYName;
                                        $fullNewDirAndFileName = $sGameDir . $fullFileName;
                                        $fullOldDirAndFileName = $sOverlayDir. "/" . $dirMain1Name . "/"  . $dirZoomName . "/" . $dirXName . "/" . $fileYName;
                                        $filePath = $gameId . "/" . $fullFileName;
                                        Module::query("INSERT INTO media SET game_id = {$gameId}, name = '{$fullFileName}', file_path = '{$filePath}'");
                                        $media_id = mysql_insert_id();
                                        Module::query("REPLACE INTO overlay_tiles SET overlay_id = {$overlayId}, media_id={$media_id}, zoom={$dirZoomName}, x={$dirXName}, y={$fileYShortName}");

                                        // copy file into root game directory
                                        copy($fullOldDirAndFileName, $fullNewDirAndFileName);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $i = $i + 1;
        }

        Module::query("UPDATE overlays SET file_uploaded = 1 WHERE overlay_id = {$overlayId} and game_id = {$gameId}");

        // delete overlay folder now that it is no longer needed
        $this->recursiveRemoveDirectory($sOverlayDir);

        return new returnData(0, $fullOldDirAndFileName . "->" . $fullNewDirAndFileName);
    }

    public function recursiveRemoveDirectory($dir) 
    {
        $files = glob($dir . '*', GLOB_MARK); 
        foreach($files as $file)
        { 
            if(substr($file, -1) == '/') 
                $this->recursiveRemoveDirectory($file); 
            else 
                unlink($file); 
        } 

        $files = glob($dir . '.*', GLOB_MARK); //do it again for hidden files
        foreach($files as $file)
        { 
            if((substr($file, -3) == '/./') || (substr($file, -4) == '/../')) continue;
            if(substr($file, -1) == '/') 
                $this->recursiveRemoveDirectory($file); 
            else 
                unlink($file); 
        } 

        if(is_dir($dir)) rmdir( $dir );      
    }

    public function unzipOverlay($gameId, $origFile)
    { 
        // to test: http://arisgames.org/server/json.php/v1.overlays.unzipOverlay/3279/arisd1a796a25517386d80a8da5a91a05a61.zip
        $sOverlayDir = Config::gamedataFSPath."/{$gameId}/";
        $fullFile = $sOverlayDir.$origFile;
        $zip = zip_open($fullFile); 
        $i=0;
        if(is_resource($zip))
        { 
            $tree = ""; 
            while(($zip_entry = zip_read($zip)) !== false)
            { 
                if(strpos(zip_entry_name($zip_entry), DIRECTORY_SEPARATOR) !== false)
                { 
                    $first  = strpos(zip_entry_name($zip_entry), DIRECTORY_SEPARATOR); 
                    $last   = strrpos(zip_entry_name($zip_entry), DIRECTORY_SEPARATOR); 
                    $newdir = substr($origFile,0,strlen($origFile)-4) . "/";
                    $dir    = $sOverlayDir . $newdir .  substr(zip_entry_name($zip_entry), 0, $last); 
                    $file   = substr(zip_entry_name($zip_entry), strrpos(zip_entry_name($zip_entry), DIRECTORY_SEPARATOR)+1); 
                    if(!is_dir($dir))
                    { 
                        $return = @mkdir($dir, 0755, true);

                        if($return === false)
                        { 
                            $returnData = new returnData(1, "Unable to create {$dir}");
                            return $returnData;
                        }
                    } 
                    if(strlen(trim($file)) > 0)
                    { 
                        $return = @file_put_contents($dir."/".$file, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry))); 
                        if($return === false)
                        { 
                            $returnData = new returnData(1, "Unable to write file $dir/$file\n");
                            return $returnData;
                        } 
                    } 
                }
                else
                { 
                    file_put_contents($file, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry))); 
                    $returnData = new returnData(0, $file);
                } 
                $i = $i +1;
            } 
        }
        else
        { 
            $returnData = new returnData(1, "Unable to open zip file: {$fullFile}");
            return $returnData;
        } 

        return new returnData(0, $file);
    }

    //to test: http://arisgames.org/server/json.php/v1.overlays.createTiles/3069/0/MapOutline.png/43.0401/43.0445/-89.2458/-89.2333
    public function createTiles($gameId, $overlayId, $imageFileName, $minLat, $maxLat, $minLon, $maxLon)
    {
        // see https://developers.google.com/kml/articles/raster for details on gdal commands

        // Get info about the image
        // -- gdalinfo $imageFileName 
        // -- look for Upper Left ( 0.0, 0.0)  and Lower Right   (21600.0, 10800.0)
        // ----- need to figure out where to send the output and how to parse it
        $sGameDir = Config::gamedataFSPath . "/{$gameId}/";
        $sOverlayDir = "{$sGameDir}MapOverlays/{$overlayId}/";
        $cmd = "gdalinfo {$sGameDir}{$imageFileName}";
        $exit = exec($cmd,$fileInfo, $stderr);  
        //echo $cmd;

        //parse the output to get the upper left and lower rioght coordinates  
        foreach($fileInfo as $line)  
        {  
            //echo "$line" . PHP_EOL;  
            $upperLeftPos = strpos($line, "Upper Left");
            $lowerRightPos = strpos($line, "Lower Right");

            if($upperLeftPos !== false)
            {
                $openParenPos = strpos($line, "(", $upperLeftPos);
                $commaPos = strpos($line, ",", $openParenPos);
                $closedParenPos = strpos($line, ")", $commaPos - 1);
                $minX = trim(substr($line, $openParenPos + 1, (strlen($line) - $openParenPos) - (strlen($line) - $commaPos) - 1));
                $minY = trim(substr($line, $commaPos + 1, (strlen($line) - $commaPos) - (strlen($line) - $closedParenPos) - 1));
                //echo $minX . "      ";
                //echo $minY . "      ";
            }
            if($lowerRightPos !== false)
            {
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
        // echo $cmd;
        //echo $stderr;  

        // warp the image
        //$cmd = "gdalwarp -of VRT {$sGameDir}{$imageFileName}.vrt {$sGameDir}{$imageFileName}2.vrt";  
        //$exit = exec($cmd,$stdout, $stderr); 
        //echo $cmd;
        //echo $stderr;

        // Create the tiles    
        $cmd = "gdal2tiles.py -p geodetic -k --s_srs=WGS84 {$sGameDir}{$imageFileName}.vrt {$sOverlayDir}";
        $exit = exec($cmd,$stdout, $stderr);  
        //echo $cmd;
        //echo $stdout;
    }
    */
}
?>
