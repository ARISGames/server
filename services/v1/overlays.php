<?php
require_once("module.php");


class Overlays extends Module
{


    /* Create overlay in database so it shows up as an object in the edior */
    public function createOverlay($gameId, $name, $index) {

        $name = addslashes($name);	

        $queryOverlayID = "SELECT max( overlay_id ) as max_overlay_id FROM server.overlays"; 
        $rsResultOverlayID = Module::query($queryOverlayID);
        $queryGameOverlayID =  "SELECT  max( game_overlay_id ) as max_game_overlay_id FROM server.overlays where game_id = {$gameId}"; 
        $rsResultGameOverlayID = Module::query($queryGameOverlayID);
        $overlayIDRow = mysql_fetch_array($rsResultOverlayID);
        if (is_null($overlayIDRow['max_overlay_id'] )) {
            $overlayId = 1;
        } else {
            $overlayId = $overlayIDRow['max_overlay_id'] + 1;
        }

        $gameOverlayIDRow = mysql_fetch_array($rsResultGameOverlayID);
        if (is_null($gameOverlayIDRow['max_game_overlay_id'] )) {
            $gameOverlayId = 1;
        } else {
            $gameOverlayId = $gameOverlayIDRow['max_game_overlay_id'] + 1;
        }


        $query = "REPLACE INTO server.overlays SET game_id = {$gameId}, overlay_id={$overlayId}, game_overlay_id={$gameOverlayId}, name='{$name}', sort_index={$index}";

        $rsResult = Module::query($query);
        if (mysql_error()) 
            return new returnData(3, NULL, "SQL Error at Overlay Level: " . $query);   
        else
            return new returnData(0, $overlayId); 			
    }

    /**
     * Update a specific Overlay
     * @returns true if edit was done, false if no changes were made
     */
    public function updateOverlay($intGameID, $intOverlayID, $strName, $index)
    {

        $strName = addslashes($strName);	

        $prefix = Module::getPrefix($intGameID);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "UPDATE overlays
            SET 
            name = '{$strName}',
                 sort_index='{$index}'
                     WHERE overlay_id = '{$intOverlayID}'";


        Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);

    }

    /**
     * Delete an overlay
     * @returns true if delete was done, false if no changes were made
     */
    public function deleteOverlay($intGameID, $intOverlayID)
    {
        $prefix = Module::getPrefix($intGameID);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "DELETE FROM overlays WHERE overlay_id = {$intOverlayID}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (!mysql_affected_rows()) {
            return new returnData(2, NULL, 'invalid event id');
        }

        $query2 = "DELETE FROM overlay_tiles WHERE overlay_id = {$intOverlayID}";

        $rsResult2 = Module::query($query2);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");



        $query = "DELETE FROM requirements WHERE content_type = 'CustomMap' AND content_id = '{$intOverlayID}' AND game_id = {$intGameID}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "{$query} SQL Error");

        return new returnData(0, TRUE);

    }	
    /**
     * Fetch specific Overlay for game
     * @returns the media
     */
    public function getOverlay($intGameID, $intOverlayID)
    {
        $prefix = Module::getPrefix($intGameID);
        if (!$prefix && $intGameID != 0) return new returnData(1, NULL, "invalid game id");

        $query = "SELECT * FROM server.overlays WHERE game_id = {$intGameID} AND overlay_id = {$intOverlayID}";
        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error 2");

        $returnData = new returnData(0, mysql_fetch_object($rsResult));

        return $returnData;
    }

    public function getOverlaysForEditor($intGameID)
    {
        $query = "SELECT * FROM overlays WHERE overlays.game_id = {$intGameID} ORDER BY sort_index";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $returnData = new returnData(0, $rsResult);

        return $returnData;
    }

    /**
     * Fetch all Overlays and tiles for game
     * @returns the media
     */
    public function getOverlays($intGameID)
    {
        /*
           $prefix = Module::getPrefix($intGameID);
           if (!$prefix && $intGameID != 0) return new returnData(1, NULL, "invalid game id");

           if ($intGameID == 0) $query = "SELECT * FROM overlays, overlay_tiles, media WHERE overlays.game_id = 0 AND overlays.overlay_id = overlay_tiles.overlay_id AND overlay_tiles.media_id = media.media_id ORDER BY overlays.sort_index";
           else $query = "SELECT * FROM overlays, overlay_tiles, media WHERE (overlays.game_id = {$prefix}) AND overlays.overlay_id = overlay_tiles.overlay_id AND overlay_tiles.media_id = media.media_id ORDER BY overlays.sort_index";


           $rsResult = Module::query($query);
           if (mysql_error()) return new returnData(3, NULL, "SQL Error");
         */
        $returnData = new returnData(0, array());
        /*
        //For each overlay, get tiles and associated info
        while ($overlayRow = mysql_fetch_array($rsResult)) {

        $tile = array();
        $tile['overlay_id'] = $overlayRow['overlay_id'];
        $tile['sort_order'] = $overlayRow['sort_order'];
        $tile['alpha'] = $overlayRow['alpha'];
        $tile['zoom'] = $overlayRow['zoom'];
        $tile['file_path'] = $overlayRow['file_path'];
        $tile['file_name'] = $overlayRow['file_path']; //For legacy reasons... Phil 10/12/2012
        $tile['media_id'] = $overlayRow['media_id'];
        $tile['x'] = $overlayRow['x'];
        $tile['y'] = $overlayRow['y'];

        array_push($returnData->data, $tile);
        }

         */
        return $returnData;

    }

    /**
     * Fetch all Overlays and tiles for game
     * @returns the media
     */
    public function getCurrentOverlaysForPlayer($intGameID, $intPlayerID)
    {
        $prefix = Module::getPrefix($intGameID);
        if (!$prefix && $intGameID != 0) return new returnData(1, NULL, "invalid game id");

        if ($intGameID == 0) $query = "SELECT * FROM overlays, overlay_tiles, media WHERE overlays.game_id = 0 AND overlays.overlay_id = overlay_tiles.overlay_id AND overlay_tiles.media_id = media.media_id ORDER BY overlays.sort_index";
        else $query = "SELECT * FROM overlays, overlay_tiles, media WHERE (overlays.game_id = {$prefix}) AND overlays.overlay_id = overlay_tiles.overlay_id AND overlay_tiles.media_id = media.media_id ORDER BY overlays.sort_index";



        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $returnData = new returnData(0, array());

        //For each overlay, get tiles and associated info
        while ($overlayRow = mysql_fetch_array($rsResult)) {

            //Does it meet it's requirements?
            if (!$this->objectMeetsRequirements($prefix, $intPlayerID, 'CustomMap', $overlayRow['overlay_id'])) {
                continue;
            }

            $tile = array();
            $tile['overlay_id'] = $overlayRow['overlay_id'];
            $tile['sort_order'] = $overlayRow['sort_order'];
            $tile['alpha'] = $overlayRow['alpha'];
            $tile['zoom'] = $overlayRow['zoom'];
            $tile['file_path'] = $overlayRow['file_path'];
            $tile['file_name'] = $overlayRow['file_path']; //For legacy reasons... Phil 10/12/2012
            $tile['media_id'] = $overlayRow['media_id'];
            $tile['x'] = $overlayRow['x'];
            $tile['y'] = $overlayRow['y'];

            array_push($returnData->data, $tile);
        }


        return $returnData;
    }

    /**
     * Fetch one Tile
     * @returns the media item
     */
    public function getTiles($intOverlayID)
    {
        $query = "SELECT * FROM overlay_tiles, media WHERE (overlays.overlay_id = {$intOverlayID} AND overlay_tiles.media_id = media.media_id";



        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $returnData = new returnData(0, array());

        //For each overlay, get tiles and associated info
        while ($tileRow = mysql_fetch_array($rsResult)) {

            $tile = array();
            $tile['overlay_id'] = $tileRow['overlay_id'];
            $tile['sort_order'] = $tileRow['sort_order'];
            $tile['alpha'] = $tileRow['alpha'];
            $tile['zoom'] = $tileRow['zoom'];
            $tile['file_path'] = $tileRow['file_path'];
            $tile['file_name'] = $tileRow['file_path']; //For legacy reasons... Phil 10/12/2012
            $tile['media_id'] = $tileRow['media_id'];
            $tile['x'] = $tileRow['x'];
            $tile['y'] = $tileRow['y'];

            array_push($returnData->data, $tile);
        }


        return $returnData;

        return new returnData(0, $tile);
    }	

    public function swapSortIndex($gameId, $overlayIdA, $overlayIdB){
        $prefix = Module::getPrefix($gameId);
        if (!$prefix) return new returnData(1, NULL, "invalid game id");

        $query = "SELECT * FROM overlays WHERE overlay_id = '{$overlayIdA}' OR overlay_id = '{$overlayIdB}'";
        $result = Module::query($query);
        $overlays = array();
        $i=0;
        while($overlay = mysql_fetch_object($result)){
            $overlays[$i] = $overlay;
            $i = $i+1;
        }

        $query = "UPDATE overlays SET sort_index = '{$overlays[0]->sort_index}' WHERE overlay_id = '{$overlays[1]->sort_index}'";
        Module::query($query);
        $query = "UPDATE overlays SET sort_index = '{$overlays[1]->sort_index}' WHERE overlay_id = '{$overlays[0]->sort_index}'";
        Module::query($query);

        return new returnData(0);
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
                $rsResult = Module::query($query);
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
                                        $fullFileName = $intOverlayID . "_" . $dirZoomName . "_" . $dirXName . "_" . $fileYName . "_" . time();
                                        $fullNewDirAndFileName = $sGameDir . $fullFileName;
                                        $fullOldDirAndFileName = $sOverlayDir. "/" . $intOverlayID . "/" . $dirZoomName . "/" . $dirXName . "/" . $fileYName;
                                        $filePath = $intGameID . "/" . $fullFileName;
                                        $query3 = "INSERT INTO media SET game_id = {$intGameID}, name = '{$fullFileName}', file_path = '{$filePath}'";
                                        $rsResult3 = Module::query($query3);
                                        if (mysql_error()) return new returnData(3, NULL, "SQL Error inserting Media: ". $query3);   

                                        $media_id = mysql_insert_id();

                                        $query4 = "REPLACE INTO overlay_tiles SET overlay_id = {$overlay_id}, media_id={$media_id}, zoom={$dirZoomName}, x={$dirXName}, y={$fileYShortName}";
                                        $rsResult4 = Module::query($query4);
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



    public function writeOverlayToDatabase($intGameID, $overlayId, $folderName)
    {
        // to test:http://dev.arisgames.org/server/json.php/v1.overlays.writeOverlayToDatabase/3289/1/aris218f403f29adc83670ba6ccc2833b996 
        // go to folder for game ID: /var/www/html/server/gamedata/{game_id}/
        $sGameDir = "/var/www/html/server/gamedata/".$intGameID."/";
        $sOverlayDir = $sGameDir . $folderName;
        $diOverlay = new DirectoryIterator($sOverlayDir);
        $i=0;

        $query = "DELETE FROM overlay_tiles WHERE overlay_id = {$overlayId}";
        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");


        foreach ($diOverlay as $dirMain1) { 

            if ($dirMain1->isDir() && !$dirMain1->isDot() && $i!=0 ) { //&& !(strpos($dirMain1->getFilename(), "__") > 0) 
                //echo "dirMain1:" . $dirMain1->getFilename() ."---- ";   
                $diMain1 = new DirectoryIterator($dirMain1->getPath()."/".$dirMain1->getFilename());

                foreach ($diMain1 as $dirZoom) { 

                    //go to last one
                    if ($dirZoom->isDir() && !$dirZoom->isDot()) { //&& !(strpos($dirMain2->getFilename(), "__") > 0)
                        $diZoom = new DirectoryIterator($dirZoom->getPath()."/".$dirZoom->getFilename());

                        // step through zoom level folders
                        foreach ($diZoom as $dirX) {

                            if ($dirX->isDir() && !$dirX->isDot() ) { 

                                // step through y folders
                                $diX = new DirectoryIterator($dirX->getPath()."/".$dirX->getFilename());
                                foreach ($diX as $fileY) {

                                    if (strpos($fileY->getFilename(), ".png" ) > 0 ) {
                                        $fileYName = $fileY->getFilename();
                                        $fileYShortName = substr($fileYName, 0, -4);
                                        $dirMain1Name = $dirMain1->getFilename();
                                        $dirZoomName = $dirZoom->getFilename();
                                        $dirXName = $dirX->getFilename();
                                        $fullFileName = $overlayId . "_" . $dirZoomName . "_" . $dirXName . "_" . $fileYName;
                                        $fullNewDirAndFileName = $sGameDir . $fullFileName;
                                        $fullOldDirAndFileName = $sOverlayDir. "/" . $dirMain1Name . "/"  . $dirZoomName . "/" . $dirXName . "/" . $fileYName;
                                        $filePath = $intGameID . "/" . $fullFileName;
                                        $query3 = "INSERT INTO media SET game_id = {$intGameID}, name = '{$fullFileName}', file_path = '{$filePath}'";
                                        $rsResult3 = Module::query($query3);
                                        if (mysql_error()) return new returnData(3, NULL, "SQL Error inserting Media: ". $query3);   

                                        $media_id = mysql_insert_id();

                                        $query4 = "REPLACE INTO overlay_tiles SET overlay_id = {$overlayId}, media_id={$media_id}, zoom={$dirZoomName}, x={$dirXName}, y={$fileYShortName}";
                                        $rsResult4 = Module::query($query4);
                                        if (mysql_error()) return new returnData(3, NULL, "SQL Error inserting tiles: ". $query4);  

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


        $query5 = "UPDATE overlays SET file_uploaded = 1 WHERE overlay_id = {$overlayId} and game_id = {$intGameID}";
        $rsResult5 = Module::query($query5);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error setting zip file uploaded flag: ". $query5);  

        // delete overlay folder now that it is no longer needed
        $this->recursiveRemoveDirectory($sOverlayDir);

        return new returnData(0, $fullOldDirAndFileName . "->" . $fullNewDirAndFileName);
    }


    public function recursiveRemoveDirectory($dir) {
        $files = glob( $dir . '*', GLOB_MARK ); 
        foreach( $files as $file ){ 
            if( substr( $file, -1 ) == '/' ) 
                $this->recursiveRemoveDirectory( $file ); 
            else 
                unlink( $file ); 
        } 

        if (is_dir($dir)) rmdir( $dir );      
    }


    public function unzipOverlay($intGameID, $origFile){ 
        // to test: http://dev.arisgames.org/server/json.php/v1.overlays.unzipOverlay/3279/arisd1a796a25517386d80a8da5a91a05a61.zip
        $sGameDir = "/var/www/html/server/gamedata/{$intGameID}/";
        $sOverlayDir = $sGameDir;
        $fullFile = $sOverlayDir . $origFile;
        $zip = zip_open($fullFile); 
        $i=0;
        if(is_resource($zip)){ 
            $tree = ""; 
            while(($zip_entry = zip_read($zip)) !== false){ 
                //echo "Unpacking ".zip_entry_name($zip_entry)."\n"; 
                if(strpos(zip_entry_name($zip_entry), DIRECTORY_SEPARATOR) !== false){ 
                    $first = strpos(zip_entry_name($zip_entry), DIRECTORY_SEPARATOR); 
                    $last = strrpos(zip_entry_name($zip_entry), DIRECTORY_SEPARATOR); 
                    $newdir = substr($origFile,0,strlen($origFile)-4) . "/";
                    $dir = $sOverlayDir . $newdir .  substr(zip_entry_name($zip_entry), 0, $last); 
                    $file = substr(zip_entry_name($zip_entry), strrpos(zip_entry_name($zip_entry), DIRECTORY_SEPARATOR)+1); 
                    if(!is_dir($dir)){ 

                        $return = @mkdir($dir, 0755, true);

                        if($return === false){ 
                            $returnData = new returnData(1, "Unable to create {$dir}");
                            return $returnData;
                        }
                    } 
                    if(strlen(trim($file)) > 0){ 
                        $return = @file_put_contents($dir."/".$file, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry))); 
                        if($return === false){ 
                            $returnData = new returnData(1, "Unable to write file $dir/$file\n");
                            return $returnData;
                        } 
                    } 
                }else{ 
                    file_put_contents($file, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry))); 
                    $returnData = new returnData(0, $file);
                } 
                $i = $i +1;
            } 
        }else{ 
            $returnData = new returnData(1, "Unable to open zip file: {$fullFile}");
            return $returnData;
        } 



        $returnData = new returnData(0, $file);
        return $returnData;
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
        //echo $cmd;

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
}
?>
