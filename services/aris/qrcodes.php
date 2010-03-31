<?php
require_once('locations.php');
require_once('nodes.php');
require_once('npcs.php');
require_once('items.php');
require_once("module.php");

class QRCodes extends Module
{

	/**
     * Fetch all Events
     * @returns the events
     */
	public function getQRCodes($intGameID)
	{
		
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		
		$query = "SELECT * FROM {$prefix}_qrcodes";
		NetDebug::trace($query);

		
		$rsResult = @mysql_query($query);
		
		if (mysql_error()) return new returnData(1, NULL, "SQL Error");
		return new returnData(0, $rsResult);
	}
	
	/**
     * Fetch a specific event
     * @returns a single event
     */
	public function getQRCode($intGameID, $intQRCodeID)
	{
		
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_qrcodes WHERE qrcode_id = {$intQRCodeID} LIMIT 1";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		$event = @mysql_fetch_object($rsResult);
		if (!$event) return new returnData(2, NULL, "invalid QRCode id");
		
		return new returnData(0, $event);
		
	}
	
	/**
	 * Download QR Code Package
	 * @returns the URL of the file to download
	 */
	 public function getQRCodePackageURL($intGameID)
	 {
	 	$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_qrcodes";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		//Set up a tmp directory
		$relDir = "{$prefix}_qrcodes_" . date('Y_m_d_h_i_s');
		$tmpDir = Config::gamedataFSPath . "/backups/{$relDir}";
		$command = "mkdir {$tmpDir}";
		NetDebug::trace($command);
		exec($command, $output, $return);
		if ($return) return new returnData(4, NULL, "cannot create backup dir, check file permissions");
		
		//Get all the images
		while ($qrCode = mysql_fetch_object($rsResult)) {
			//Look up the item to get a good file name
			$fileNameType = '';
			$fileNameId = '';
			$fileNameName = '';
			NetDebug::trace("QR Code Type:" . $qrCode->link_type);

			switch ($qrCode->link_type) {
				case 'Location':
					NetDebug::trace("It is Location " . $qrCode->link_id);
					$fileNameType = "Location";
					$fileNameId = $qrCode->link_id;
					
					$locationReturnData = Locations::getLocation($intGameID, $qrCode->link_id);
					$location = $locationReturnData->data;				
					NetDebug::trace("Location Found. Type:" . $location->type .". Look up the Object" );
					switch ($location->type) {
						case 'Npc': 
							NetDebug::trace("It is an NPC");
							$object = Npcs::getNpc($intGameID, $location->type_id);
							$fileNameName = $object->data->name;
							break;
						case 'Node': 
							NetDebug::trace("It is an NPC");
							$object = Nodes::getNode($intGameID, $location->type_id);
							$fileNameName = $object->data->title;
							break;
						case 'Item':
							NetDebug::trace("It is an Item");
							$object = Items::getItem($intGameID, $location->type_id);
							$fileNameName = $object->data->name;
							break;	
					}
	
					break;
				
				default:
					$returnResult = new returnData(5, NULL, "Invalid QR Code Found.");
			}
	
			$fileName = "{$fileNameType}{$fileNameId}-{$fileNameName}.jpg";
			NetDebug::trace("The file name will be {$fileName}");
			
			$command = "curl -s -o /{$tmpDir}/{$fileName} 'http://chart.apis.google.com/chart?chs=300x300&cht=qr&choe=UTF-8&chl={$qrCode->code}'";
			exec($command, $output, $return);
			NetDebug::trace($command);
			if ($return) return new returnData(4, NULL, "cannot download and save qr code image, check file permissions and url in console");
		}
		
		//Zip up the whole directory
		$zipFileName = "aris_qr_codes.tar";
		$cwd = Config::gamedataFSPath . "/backups";
		chdir($cwd);
		NetDebug::trace("cd $cwd");

		$command = "tar -cf {$zipFileName} {$relDir}/";
		exec($command, $output, $return);
		NetDebug::trace($command);
		if ($return) return new returnData(5, NULL, "cannot compress backup dir, check that tar command is availabe.");
		
		//Delete the Temp
		/*
		$rmCommand = "rm -rf {$tmpDir}";
		exec($rmCommand, $output, $return);
		if ($return) return new returnData(5, NULL, "cannot delete backup dir, check file permissions");
		*/
		
		return new returnData(0, Config::gamedataWWWPath . "/backups/{$zipFileName}");		
	 }
	
	/**
     * Fetch a QRCode object
     * @returns a 0 with an NPC, Node or Item in addition to the latitute and longitude in the data, 2 for an invalid code, 4 for reqs not met or 5 for a data error
     */
	public function getQRCodeObjectForPlayer($intGameID, $strCode, $intPlayerID)
	{
		
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$query = "SELECT * FROM {$prefix}_qrcodes WHERE code = '{$strCode}' LIMIT 1";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error: ". mysql_error());
		
		$qrcode = @mysql_fetch_object($rsResult);
		
		//Check for a valid QR Code
		if (!$qrcode) { 
			Module::appendLog($intPlayerID, $intGameID, Module::kLOG_ENTER_QRCODE, $strCode, 'INVALID');
			return new returnData(2, NULL, "invalid QRCode code");
		}
			
		
		//Check the requirements of the QR Code's link object
		if (!$this->objectMeetsRequirements ($prefix, $intPlayerID, $qrcode->link_type, $qrcode->link_id)) {
			Module::appendLog($intPlayerID, $intGameID, Module::kLOG_ENTER_QRCODE, $strCode, 'REQS_OR_QTY_NOT_MET');
			return new returnData(4, NULL, "QRCode requirements not met");
		}
		
		Module::appendLog($intPlayerID, $intGameID, Module::kLOG_ENTER_QRCODE, $strCode, 'SUCCESSFUL');

		//Get the data
		switch ($qrcode->link_type) {
			case 'Location':
				NetDebug::trace("It is Location " . $qrcode->link_id);
				$locationReturnData = Locations::getLocation($intGameID, $qrcode->link_id);
				$location = $locationReturnData->data;
				if (!$location) return new returnData(5, NULL, "bad link in qr code, no matching location found");
				
				NetDebug::trace("Location Found. Type:" . $location->type .". Look up the Object" );
				switch ($location->type) {
					case 'Npc': 
						NetDebug::trace("It is an NPC");
						$returnResult = Npcs::getNpcWithConversationsForPlayer($intGameID, $location->type_id, $intPlayerID);
						$returnResult->data->type = "Npc";
						break;
					case 'Node': 
						NetDebug::trace("It is an NPC");
						$returnResult = Nodes::getNode($intGameID, $location->type_id);
						$returnResult->data->type = "Node";
						break;
					case 'Item':
						NetDebug::trace("It is an Item");
						
						//Check Item Quantity
						if ($location->item_qty == 0) return new returnData(4, NULL, "Item Quantity is 0");

						$returnResult = Items::getItem($intGameID, $location->type_id);
						$returnResult->data->type = "Item";
						break;	
					default:
						$returnResult = new returnData(5, NULL, "Invalid Location Record. type not recognized");
				}
				
				//No matter the object type, tack on the position
				$returnResult->data->latitude = $location->latitude;
				$returnResult->data->longitude = $location->longitude;
				break;
			
			default:
				$returnResult = new returnData(5, NULL, "Invalid QR Code Record. link_type not recognized");
		}
		
		return $returnResult;
		
	}
	

	
	/**
     * Create a QR Code
     * If no code is provided, a random 4 digit number will be generated
     * @returns the new QR Code ID on success.
     */
	public function createQRCode($intGameID, $strLinkType, $intLinkID, $strCode = '')
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		if (!QRCodes::isValidObjectType($intGameID, $strLinkType)) return new returnData(4, NULL, "Invalid link type");

		//generate a random code if one is not provided
		if (strlen($strCode) < 1) {
			$charSet = "123456789";
			$strCode = '';
			for ($i=0; $i<4; $i++) $strCode .= substr($charSet,rand(0,strlen(charSet)-1),1);
			NetDebug::trace("New Code was Created: " . $strCode);	
		}
		
		$query = "INSERT INTO {$prefix}_qrcodes 
					(link_type, link_id, code)
					VALUES ('{$strLinkType}','{$intLinkID}','{$strCode}')";
		
		NetDebug::trace("Running a query = $query");	
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error: ". mysql_error());
		
		return new returnData(0, mysql_insert_id());
	}

	
	
	/**
     * Update a QR Code
     * @returns true if edit was done, false if no changes were made
     */
	public function updateQRCode($intGameID, $intQRCodeID, $strLinkType, $intLinkID, $strCode)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		if (!$this->isValidObjectType($intGameID, $strLinkType)) return new returnData(4, NULL, "Invalid link type");


		$query = "UPDATE {$prefix}_qrcodes
					SET 
					link_type = '{$strLinkType}',
					link_id = '{$intLinkID}',
					code = '{$strCode}'
					WHERE qrcode_id = '{$intQRCodeID}'";
		
		NetDebug::trace("Running a query = $query");	
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);
		

	}
			
	
	/**
     * Delete a QR Code
     * @returns true if delete was done, false if no changes were made
     */
	public function deleteQRCode($intGameID, $intQRCodeID)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");		
		
		$query = "DELETE FROM {$prefix}_qrcodes WHERE qrcode_id = {$intQRCodeID}";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		if (mysql_affected_rows()) {
			return new returnData(0, TRUE);
		}
		else {
			return new returnData(2, NULL, 'invalid qrcode id');
		}
	}	
	
	/**
     * Delete all QR Codes that link to a specified object
     * @returns true if delete was done, false if no changes were made
     */
	public function deleteQRCodeCodesForLink($intGameID, $strLinkType, $intLinkID)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");		
		
		$query = "DELETE FROM {$prefix}_qrcodes WHERE 
			link_type = '{$strLinkType}' AND link_id = '{$intLinkID}'";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		if (mysql_affected_rows()) {
			return new returnData(0, TRUE);
		}
		else {
			return new returnData(0, FALSE);
		}
	}		
	
	
	/**
     * Fetch the valid content types from the requirements table
     * @returns an array of strings
     */
	public function contentTypeOptions($intGameID){	
		$options = $this->lookupContentTypeOptionsFromSQL($intGameID);
		if (!$options) return new returnData(1, NULL, "invalid game id");
		return new returnData(0, $options);
	}	
	
	
	/**
     * Fetch the valid content types from the requirements table
     * @returns an array of strings
     */
	private function lookupContentTypeOptionsFromSQL($intGameID){
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return FALSE;
		
		$query = "SHOW COLUMNS FROM {$prefix}_qrcodes LIKE 'link_type'";
		NetDebug::trace($query);
		
		$result = @mysql_query( $query );
		$row = @mysql_fetch_array( $result , MYSQL_NUM );
		$regex = "/'(.*?)'/";
		preg_match_all( $regex , $row[1], $enum_array );
		$enum_fields = $enum_array[1];
		return( $enum_fields );
	}

	
	
	/**
     * Check if a content type is valid
     * @returns TRUE if valid
     */
	private function isValidObjectType($intGameID, $strObjectType) {
		$validTypes = QRCodes::lookupContentTypeOptionsFromSQL($intGameID);
		return in_array($strObjectType, $validTypes);
	}


		
	
	
}