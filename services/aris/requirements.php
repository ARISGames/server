<?php
require("module.php");


class Requirements extends Module
{	
	
	/**
     * Fetch all Requirements for a Game Object
     * @returns the requirements
     */
	public function getRequirementsForObject($intGameID, $strObjectType, $intObjectID)
	{
		
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		if (!$this->isValidObjectType($intGameID, $strObjectType)) return new returnData(4, NULL, "Invalid object type");
		
		$query = "SELECT * FROM {$prefix}_requirements
					WHERE content_type = '{$strObjectType}' and content_id = '{$intObjectID}'";
		NetDebug::trace($query);

		
		$rsResult = @mysql_query($query);
		
		if (mysql_error()) return new returnData(1, NULL, "SQL Error");
		return new returnData(0, $rsResult);
	}
	
	/**
     * Fetch a specific requirement
     * @returns a single requirement
     */
	public function getRequirement($intGameID, $intRequirementID)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_requirements WHERE requirement_id = {$intRequirementID} LIMIT 1";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		$requirement = @mysql_fetch_object($rsResult);
		if (!$requirement) return new returnData(2, NULL, "invalid requirement id");
		
		return new returnData(0, $requirement);	
	}
	
	/**
     * Create a Requirement
     * @returns the new requirementID on success
     */
	public function createRequirement($intGameID, $strObjectType, $intObjectID, 
		$strRequirementType, $strRequirementDetail1 = null, $strRequirementDetail2 = null,$strRequirementDetail3 = null)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		//test the object type 
		if (!$this->isValidObjectType($intGameID, $strObjectType)) return new returnData(4, NULL, "Invalid object type");
				
		//test the requirement type
		if (!$this->isValidRequirementType($intGameID, $strRequirementType)) return new returnData(5, NULL, "Invalid requirement type");
		
		
		$query = "INSERT INTO {$prefix}_requirements 
					(content_type, content_id, requirement, 
					requirement_detail_1,requirement_detail_2,requirement_detail_3)
				VALUES ('{$strObjectType}','{$intObjectID}','{$strRequirementType}',
					'{$strRequirementDetail1}', '{$strRequirementDetail2}', '{$strRequirementDetail3}')";
		
		NetDebug::trace("Running a query = $query");	
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		return new returnData(0, mysql_insert_id());
	}

	
	
	/**
     * Update a specific Requirement
     * @returns true if edit was done, false if no changes were made
     */
	public function updateRequirement($intGameID, $intRequirementID, $strObjectType, $intObjectID, 
		$strRequirementType, $strRequirementDetail1 = null, $strRequirementDetail2 = null,$strRequirementDetail3 = null)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		//test the object type 
		if (!$this->isValidObjectType($intGameID, $strObjectType)) return new returnData(4, NULL, "Invalid object type");
				
		//test the requirement type
		if (!$this->isValidRequirementType($intGameID, $strRequirementType)) return new returnData(5, NULL, "Invalid requirement type");
		
		

		$query = "UPDATE {$prefix}_requirements 
					SET 
					content_type = '{$strObjectType}',
					content_id = '{$intObjectID}',
					requirement = '{$strRequirementType}',
					requirement_detail_1 = '{$strRequirementDetail1}',
					requirement_detail_2 = '{$strRequirementDetail2}',
					requirement_detail_3 = '{$strRequirementDetail3}'
					WHERE requirement_id = '{$intRequirementID}'";
		
		NetDebug::trace("Running a query = $query");	
		
		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		if (mysql_affected_rows()) return new returnData(0, TRUE);
		else return new returnData(0, FALSE);
	}
			
	
	/**
     * Delete an Requirement
     * @returns 0 on success
     */
	public function deleteRequirement($intGameID, $intRequirementID)
	{
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$query = "DELETE FROM {$prefix}_requirements WHERE requirement_id = {$intRequirementID}";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		if (mysql_affected_rows()) {
			return new returnData(0);
		}
		else {
			return new returnData(2, NULL, 'invalid requirement id');
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
	public function requirementTypeOptions($intGameID){	
		$options = $this->lookupRequirementTypeOptionsFromSQL($intGameID);
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
		
		$query = "SHOW COLUMNS FROM {$prefix}_requirements LIKE 'content_type'";
		NetDebug::trace($query);
		
		$result = @mysql_query( $query );
		$row = @mysql_fetch_array( $result , MYSQL_NUM );
		$regex = "/'(.*?)'/";
		preg_match_all( $regex , $row[1], $enum_array );
		$enum_fields = $enum_array[1];
		return( $enum_fields );
	}

	/**
     * Fetch the valid requirement types from the requirements table
     * @returns an array of strings
     */
	private function lookupRequirementTypeOptionsFromSQL($intGameID){
		$prefix = $this->getPrefix($intGameID);
		if (!$prefix) return FALSE;
		
		$query = "SHOW COLUMNS FROM {$prefix}_requirements LIKE 'requirement'";
		$result = mysql_query( $query );
		$row = mysql_fetch_array( $result , MYSQL_NUM );
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
		$validTypes = $this->lookupContentTypeOptionsFromSQL($intGameID);
		return in_array($strObjectType, $validTypes);
	}

	/**
     * Check if a requirement type is valid
     * @returns TRUE if valid
     */
	private function isValidRequirementType($intGameID, $strRequirementType) {
		$validTypes = $this->lookupRequirementTypeOptionsFromSQL($intGameID);
		NetDebug::trace($validTypes);

		return in_array($strRequirementType, $validTypes);
	}	
	



}