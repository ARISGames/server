<?php
require_once("module.php");


class Requirements extends Module
{	
	
	/**
     * Fetch all Requirements for a Game Object
     * @returns the requirements
     */
	public function getRequirementsForObject($gameId, $objectType, $objectId)
	{
		
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		if (!$this->isValidObjectType($gameId, $objectType)) return new returnData(4, NULL, "Invalid object type");
		
		$query = "SELECT * FROM {$prefix}_requirements
					WHERE content_type = '{$objectType}' and content_id = '{$objectId}'";
		NetDebug::trace($query);

		
		$rsResult = @mysql_query($query);
		
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		return new returnData(0, $rsResult);
	}
	
	/**
     * Fetch a specific requirement
     * @returns a single requirement
     */
	public function getRequirement($gameId, $requirementId)
	{
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM {$prefix}_requirements WHERE requirement_id = {$requirementId} LIMIT 1";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		$requirement = @mysql_fetch_object($rsResult);
		if (!$requirement) return new returnData(2, NULL, "invalid requirement id");
		
		return new returnData(0, $requirement);	
	}
	
	/**
     * Creates a requirement
     *
     * @param integer $gameId The game identifier
     * @param string $objectType The object this req controls. Must be a valid object type (see objectTypeOptions())
     * @param integer $objectId 
     * @param string $requirementType The kind of requirement. Must be a valid requirement type (see requirementTypeOptions())
     * @param mixed $requirementDetail1 See http://code.google.com/p/arisgames/wiki/ServerTechnicalDocs
     * @param mixed $requirementDetail2 See http://code.google.com/p/arisgames/wiki/ServerTechnicalDocs
     * @param mixed $requirementDetail3 See http://code.google.com/p/arisgames/wiki/ServerTechnicalDocs
     * @param string $booleanOperator The bool operation to use when computing all reqs for this object. Either 'AND' or 'OR'
     * @return returnData
     * @returns a returnData object containing the newly created requirement's id
     * @see returnData
     */
	public function createRequirement($gameId, $objectType, $objectId, 
		$requirementType, $requirementDetail1, $requirementDetail2, $requirementDetail3, $booleanOperator)
	{
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		//test the object type 
		if (!$this->isValidObjectType($gameId, $objectType)) return new returnData(4, NULL, "Invalid object type");
				
		//test the requirement type
		if (!$this->isValidRequirementType($gameId, $requirementType)) return new returnData(5, NULL, "Invalid requirement type");
		
		//if the requirement type refers to an item, make sure the QTY is set to 1 or more
		if (($requirementType == "PLAYER_HAS_ITEM" || $requirementType == "PLAYER_DOES_NOT_HAVE_ITEM") && $requirementDetail2 < 1) 
			$requirementDetail2 = 1;
		
		$query = "INSERT INTO {$prefix}_requirements 
					(content_type, content_id, requirement, 
					requirement_detail_1,requirement_detail_2,requirement_detail_3,boolean_operator)
				VALUES ('{$objectType}','{$objectId}','{$requirementType}',
					'{$requirementDetail1}', '{$requirementDetail2}', '{$requirementDetail3}', '{$booleanOperator}')";
		
		NetDebug::trace("Running a query = $query");	
		
		@mysql_query($query);
				NetDebug::trace(mysql_error());	

		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		return new returnData(0, mysql_insert_id());
	}

	
	
	/**
     * Updates a requirement
     *
     * @param integer $gameId The game identifier
     * @param integer $requirementId The item identifier
     * @param string $objectType The object this req controls. Must be a valid object type (see objectTypeOptions())
     * @param integer $objectId 
     * @param string $requirementType The kind of requirement. Must be a valid requirement type (see requirementTypeOptions())
     * @param mixed $requirementDetail1 See http://code.google.com/p/arisgames/wiki/ServerTechnicalDocs
     * @param mixed $requirementDetail2 See http://code.google.com/p/arisgames/wiki/ServerTechnicalDocs
     * @param mixed $requirementDetail3 See http://code.google.com/p/arisgames/wiki/ServerTechnicalDocs
     * @param string $booleanOperator The bool operation to use when computing all reqs for this object. Either 'AND' or 'OR'
     * @return returnData
     * @returns a returnData object containing a TRUE if an change was made, FALSE otherwise
     * @see returnData
     */
	public function updateRequirement($gameId, $requirementId, $objectType, $objectId, 
		$requirementType, $requirementDetail1, $requirementDetail2,$requirementDetail3,
		$booleanOperator)
	{
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		//test the object type 
		if (!$this->isValidObjectType($gameId, $objectType)) return new returnData(4, NULL, "Invalid object type");
				
		//test the requirement type
		if (!$this->isValidRequirementType($gameId, $requirementType)) return new returnData(5, NULL, "Invalid requirement type");
		
		

		$query = "UPDATE {$prefix}_requirements 
					SET 
					content_type = '{$objectType}',
					content_id = '{$objectId}',
					requirement = '{$requirementType}',
					requirement_detail_1 = '{$requirementDetail1}',
					requirement_detail_2 = '{$requirementDetail2}',
					requirement_detail_3 = '{$requirementDetail3}',
					boolean_operator = '{$booleanOperator}'
					WHERE requirement_id = '{$requirementId}'";
		
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
	public function deleteRequirement($gameId, $requirementId)
	{
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");
		
		$query = "DELETE FROM {$prefix}_requirements WHERE requirement_id = {$requirementId}";
		
		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");
		
		if (mysql_affected_rows()) {
			return new returnData(0);
		}
		else {
			return new returnData(2, NULL, 'invalid requirement id');
		}
		
	}	
	
	
	public function deleteRequirementsForRequirementObject($gameId, $objectType, $objectId)
	{
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$requirementString = '';
		
		switch ($objectType) {
			case 'Node':
				$requirementString = "requirement = 'PLAYER_VIEWED_NODE' OR 
										requirement = 'PLAYER_HAS_NOT_VIEWED_NODE'";
				break;			
			case 'Item':
				$requirementString = "requirement = 'PLAYER_HAS_ITEM' OR
									requirement = 'PLAYER_DOES_NOT_HAVE_ITEM' OR
									requirement = 'PLAYER_VIEWED_ITEM' OR
									requirement = 'PLAYER_HAS_NOT_VIEWED_ITEM'";
				break;
			case 'Npc':
				$requirementString = "requirement = 'PLAYER_VIEWED_NPC' OR
									requirement = 'PLAYER_HAS_NOT_VIEWED_NPC'";
				break;
			default:
				return new returnData(4, NULL, "invalid object type");
		}
			
		//Delete the Locations and related QR Codes
		$query = "DELETE FROM {$prefix}_requirements
			WHERE ({$requirementString}) AND requirement_detail_1 = '{$objectId}'";
		
		@mysql_query($query);
		
		NetDebug::trace("Query: $query" . mysql_error());		

		
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
	public function contentTypeOptions($gameId){	
		$options = $this->lookupContentTypeOptionsFromSQL($gameId);
		if (!$options) return new returnData(1, NULL, "invalid game id");
		return new returnData(0, $options);
	}

	/**
     * Fetch the valid content types from the requirements table
     * @returns an array of strings
     */
	public function requirementTypeOptions($gameId){	
		$options = $this->lookupRequirementTypeOptionsFromSQL($gameId);
		if (!$options) return new returnData(1, NULL, "invalid game id");
		return new returnData(0, $options);	
	}


	
	/**
     * Fetch the valid content types from the requirements table
     * @returns an array of strings
     */
	private function lookupContentTypeOptionsFromSQL($gameId){
		$prefix = Module::getPrefix($gameId);
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
	private function lookupRequirementTypeOptionsFromSQL($gameId){
		$prefix = Module::getPrefix($gameId);
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
	private function isValidObjectType($gameId, $objectType) {
		$validTypes = $this->lookupContentTypeOptionsFromSQL($gameId);
		return in_array($objectType, $validTypes);
	}

	/**
     * Check if a requirement type is valid
     * @returns TRUE if valid
     */
	private function isValidRequirementType($gameId, $requirementType) {
		$validTypes = $this->lookupRequirementTypeOptionsFromSQL($gameId);
		NetDebug::trace($validTypes);

		return in_array($requirementType, $validTypes);
	}	
	



}