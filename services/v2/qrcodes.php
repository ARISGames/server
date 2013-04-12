<?php
require_once('locations.php');
require_once('nodes.php');
require_once('npcs.php');
require_once('items.php');
require_once("module.php");

class QRCodes extends Module
{
    public function getQRCodes($gameId)
    {
        $query = "SELECT * FROM qrcodes WHERE game_id = {$gameId}";

        $rsResult = Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, "SQL Error");
        return new returnData(0, $rsResult);
    }

    public function getQRCode($gameId, $intQRCodeID)
    {
        $query = "SELECT * FROM qrcodes WHERE game_id = {$gameId} AND qrcode_id = {$intQRCodeID} LIMIT 1";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        $event = @mysql_fetch_object($rsResult);
        if (!$event) return new returnData(2, NULL, "invalid QRCode id");

        return new returnData(0, $event);
    }

    public function getQRCodePackageURL($gameId)
    {
        $query = "SELECT * FROM qrcodes WHERE game_id = {$gameId}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        //Set up a tmp directory
        $relDir = "{$gameId}_qrcodes_" . date('Y_m_d_h_i_s');
        $tmpDir = Config::gamedataFSPath . "/backups/{$relDir}";
        $command = "mkdir {$tmpDir}";
        exec($command, $output, $return);
        if ($return) return new returnData(4, NULL, "cannot create backup dir, check file permissions");

        //Get all the images
        while ($qrCode = mysql_fetch_object($rsResult)) {
            //Look up the item to get a good file name
            $fileNameType = '';
            $fileNameId = '';
            $fileNameName = '';

            switch ($qrCode->link_type) {
                case 'Location':
                    $fileNameType = "Location";
                    $fileNameId = $qrCode->link_id;

                    $locationReturnData = Locations::getLocation($gameId, $qrCode->link_id);
                    $location = $locationReturnData->data;				
                    switch ($location->type) {
                        case 'Npc': 
                            $object = Npcs::getNpc($gameId, $location->type_id);
                            $fileNameName = $object->data->name;
                            break;
                        case 'Node': 
                            $object = Nodes::getNode($gameId, $location->type_id);
                            $fileNameName = $object->data->title;
                            break;
                        case 'Item':
                            $object = Items::getItem($gameId, $location->type_id);
                            $fileNameName = $object->data->name;
                            break;	
                    }

                    break;

                default:
                    $returnResult = new returnData(5, NULL, "Invalid QR Code Found.");
            }

            $fileName = "{$fileNameType}{$fileNameId}-{$fileNameName}.jpg";

            $command = "curl -s -o /{$tmpDir}/{$fileName} 'http://chart.apis.google.com/chart?chs=300x300&cht=qr&choe=UTF-8&chl={$qrCode->code}'";
            exec($command, $output, $return);
            if ($return) return new returnData(4, NULL, "cannot download and save qr code image, check file permissions and url in console");
        }

        //Zip up the whole directory
        $zipFileName = "aris_qr_codes.tar";
        $cwd = Config::gamedataFSPath . "/backups";
        chdir($cwd);

        $command = "tar -cf {$zipFileName} {$relDir}/";
        exec($command, $output, $return);
        if ($return) return new returnData(5, NULL, "cannot compress backup dir, check that tar command is availabe.");

        //Delete the Temp
        /*
           $rmCommand = "rm -rf {$tmpDir}";
           exec($rmCommand, $output, $return);
           if ($return) return new returnData(5, NULL, "cannot delete backup dir, check file permissions");
         */

        return new returnData(0, Config::gamedataWWWPath . "/backups/{$zipFileName}");		
    }

    public function getBestImageMatchNearbyObjectForPlayer($gameId, $intPlayerId, $strFileName)
    {    
        $gameMediaAndDescriptorsPath = Media::getMediaDirectory($gameId)->data;
        $execCommand = '../../ImageMatcher/ImageMatcher match ' . $gameMediaAndDescriptorsPath . $strFileName . ' ' . $gameMediaAndDescriptorsPath;

        $console = exec($execCommand); //Run it
        Module::serverErrorLog('getBestImageMatchNearbyObjectForPlayer Console:' . $console);

        $consoleJSON = json_decode($console,true);
        $fileName = $consoleJSON['filename'];        
        $pathParts = pathinfo($fileName);
        $fileName =  $pathParts['filename']; // requires PHP 5.2.0        

        $similarity = $consoleJSON['similarity'];
        if ($similarity > 0.2) return new returnData(0, NULL, "No match found. Best simularity was {$similarity}");

        $query = "SELECT game_qrcodes.* 
            FROM (SELECT * FROM qrcodes WHERE game_id = {$gameId}) AS game_qrcodes 
            JOIN media 
            ON (game_qrcodes.match_media_id = media.media_id)
            WHERE media.file_path = '{$fileName}.jpg'
            OR media.file_path = '{$fileName}.png'
            LIMIT 1";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error: ". mysql_error());

        $qrcode = @mysql_fetch_object($rsResult);

        //Check for a valid QR Code
        if (!$qrcode) { 
            Module::appendLog($intPlayerId, $gameId, Module::kLOG_ENTER_QRCODE, $fileName, 'INVALID');
            return new returnData(0, NULL, "invalid QRCode code");
        }

        //Check the requirements of the QR Code's link object
        if (!$this->objectMeetsRequirements ($gameId, $intPlayerId, $qrcode->link_type, $qrcode->link_id)) {
            Module::appendLog($intPlayerId, $gameId, Module::kLOG_ENTER_QRCODE, $fileName, 'REQS_OR_QTY_NOT_MET');
            return new returnData(0, NULL, "QRCode requirements not met");
        }

        Module::appendLog($intPlayerId, $gameId, Module::kLOG_ENTER_QRCODE, $fileName, 'SUCCESSFUL');

        $returnResult = new returnData(0, $qrcode);

        //Get the data

        switch ($qrcode->link_type) {
            case 'Location':
                $returnResult->data->object = Locations::getLocation($gameId, $qrcode->link_id)->data;
                if (!$returnResult->data->object) return new returnData(5, NULL, "bad link in qr code, no matching location found");
                break;
            default:
                return new returnData(5, NULL, "Invalid QR Code Record. link_type not recognized");
        }

        return $returnResult;

        //Delete the file since we will never use it again
        //unlink($strFileName);
    }

    public function getQRCodeNearbyObjectForPlayer($gameId, $strCode, $intPlayerID)
    {
        $strCode = urldecode($strCode);	

        $query = "SELECT * FROM qrcodes WHERE game_id = {$gameId} AND code = '{$strCode}'";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error: ". mysql_error());

        $rData = new returnData(0, NULL, "invalid QRCode code");

        while($qrcode = @mysql_fetch_object($rsResult)){
            //Check for a valid QR Code
            if (!$qrcode) { 
                Module::appendLog($intPlayerID, $gameId, Module::kLOG_ENTER_QRCODE, $strCode, 'INVALID');
                $rData = new returnData(0, NULL, "invalid QRCode code");
            }

            //Check the requirements of the QR Code's link object
            else if (!$this->objectMeetsRequirements ($gameId, $intPlayerID, $qrcode->link_type, $qrcode->link_id)) {
                Module::appendLog($intPlayerID, $gameId, Module::kLOG_ENTER_QRCODE, $strCode, 'REQS_OR_QTY_NOT_MET');
                $rData = new returnData(0, $qrcode->fail_text, "QRCode requirements not met");
            }

            else{
                Module::appendLog($intPlayerID, $gameId, Module::kLOG_ENTER_QRCODE, $strCode, 'SUCCESSFUL');

                $rData = new returnData(0, $qrcode);

                //Get the data

                switch ($qrcode->link_type) {
                    case 'Location':
                        $rData->data->object = Locations::getLocation($gameId, $qrcode->link_id)->data;
                        if (!$rData->data->object) return new returnData(5, NULL, "bad link in qr code, no matching location found");
                        return $rData;
                        break;
                    default:
                        return new returnData(5, NULL, "Invalid QR Code Record. link_type not recognized");
                }

            }
        }
        return $rData;

    }	

    public function createQRCode($gameId, $strLinkType, $intLinkID, $strCode = '', $imageMatchId='0', $errorText="This code doesn't mean anything right now. You should come back later.")
    {
        $errorText = addslashes($errorText);

        if (!QRCodes::isValidObjectType($strLinkType)) return new returnData(4, NULL, "Invalid link type");

        //generate a random code if one is not provided
        if (strlen($strCode) < 1) {
            $charSet = "123456789";
            $strCode = '';
            for ($i=0; $i<4; $i++) $strCode .= substr($charSet,rand(0,strlen(charSet)-1),1);
        }

        $query = "INSERT INTO qrcodes 
            (game_id, link_type, link_id, code, match_media_id, fail_text)
            VALUES ('{$gameId}','{$strLinkType}','{$intLinkID}','{$strCode}','{$imageMatchId}', '{$errorText}')";

        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error: ". mysql_error());

        return new returnData(0, mysql_insert_id());
    }

    public function updateQRCode($gameId, $intQRCodeID, $strLinkType, $intLinkID, $strCode, $imageMatchId, $errorText="")
    {
        $strCode = addslashes($strCode);
        $errorText = addslashes($errorText);

        if (!$this->isValidObjectType($strLinkType)) return new returnData(4, NULL, "Invalid link type");

        $query = "UPDATE qrcodes
            SET 
            link_type = '{$strLinkType}',
                      link_id = '{$intLinkID}',
                      code = '{$strCode}',
                      match_media_id = '{$imageMatchId}',
                      fail_text = '{$errorText}'
                          WHERE game_id = {$gameId} AND qrcode_id = '{$intQRCodeID}'";


        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);


    }

    public function deleteQRCode($gameId, $intQRCodeID)
    {
        $query = "DELETE FROM qrcodes WHERE game_id = {$gameId} AND qrcode_id = {$intQRCodeID}";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) {
            return new returnData(0, TRUE);
        }
        else {
            return new returnData(2, NULL, 'invalid qrcode id');
        }
    }	

    public function deleteQRCodeCodesForLink($gameId, $strLinkType, $intLinkID)
    {
        $query = "DELETE FROM qrcodes WHERE game_id = {$gameId} AND
            link_type = '{$strLinkType}' AND link_id = '{$intLinkID}'";

        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error");

        if (mysql_affected_rows()) {
            return new returnData(0, TRUE);
        }
        else {
            return new returnData(0, FALSE);
        }
    }		

    public function contentTypeOptions()
    {	
        $options = $this->lookupContentTypeOptionsFromSQL();
        if (!$options) return new returnData(1, NULL, "invalid game id");
        return new returnData(0, $options);
    }	

    private function lookupContentTypeOptionsFromSQL()
    {
        $query = "SHOW COLUMNS FROM qrcodes LIKE 'link_type'";

        $result = Module::query( $query );
        $row = @mysql_fetch_array( $result , MYSQL_NUM );
        $regex = "/'(.*?)'/";
        preg_match_all( $regex , $row[1], $enum_array );
        $enum_fields = $enum_array[1];
        return( $enum_fields );
    }

    private function isValidObjectType($strObjectType)
    {
        $validTypes = QRCodes::lookupContentTypeOptionsFromSQL();
        return in_array($strObjectType, $validTypes);
    }
}
