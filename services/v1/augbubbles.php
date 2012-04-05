<?php
require_once("module.php");
require_once("media.php");
require_once("games.php");
require_once("locations.php");
require_once("playerStateChanges.php");
require_once("editorFoldersAndContent.php");

class AugBubbles extends Module
{


	/**
	 * Gets the augbubbles within a game
	 * @param integer $gameID The game identifier
	 * @return returnData
	 * @returns a returnData object containing an array of augbubbles
	 * @see returnData
	 */
	public static function getAugBubbles($gameId)
	{

		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");


		$query = "SELECT * FROM aug_bubbles WHERE game_id = '{$gameId}'";
		NetDebug::trace($query);
		$augBubblesRS = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" .mysql_error());

		$augBubbles = array();

		while ($augBubble = mysql_fetch_object($augBubblesRS)) {
			$query = "SELECT * FROM aug_bubble_media WHERE aug_bubble_id = '{$augBubble->aug_bubble_id}'";
			NetDebug::trace($query);
			$mediaRS = @mysql_query($query);
			if (mysql_error()) return new returnData(3, NULL, "SQL Error:".mysql_error());

			//extract the recordset
			$augBubble->media = array();
			while ($media = mysql_fetch_object($mediaRS)) $augBubble->media[] = $media;

			//Add it to the array
			$augBubbles[] = $augBubble;
		}


		return new returnData(0, $augBubbles);
	}



	/**
	 * Gets a single aug bubble from a game
	 *
	 * @param integer $gameID The game identifier
	 * @param integer $augBubbleId The augBubble identifier
	 * @return returnData
	 * @returns a returnData object containing an augBubble
	 * @see returnData
	 */
	public static function getAugBubble($gameId, $augBubbleId)
	{

		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "SELECT * FROM aug_bubbles WHERE game_id = '{$gameId}' AND aug_bubble_id = '{$augBubbleId}' LIMIT 1";

		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

		$augBubble = @mysql_fetch_object($rsResult);
		if (!$augBubble) return new returnData(2, NULL, "invalid aug bubble id");

		$query = "SELECT * FROM aug_bubble_media WHERE aug_bubble_id = '{$augBubble->aug_bubble_id}'";
		NetDebug::trace($query);
		$mediaRS = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:".mysql_error());

		//extract the recordset
		$augBubble->media = array();
		while ($media = mysql_fetch_object($mediaRS)) $augBubble->media[] = $media;


		return new returnData(0, $augBubble);

	}

	public static function getAugBubbleMedia($gameId, $augBubbleId){


		$query = "SELECT * FROM aug_bubble_media WHERE aug_bubble_id = '{$augBubbleId}'";
		$result = mysql_query($query);

		return new returnData(0, $result);
	}

	/**
	 * Creates a single Aug Bubble from a game
	 * 
	 * @param integer $gameId The game identifier
	 * @param string $name The name
	 * @param string $desc The augBubble to reach
	 * @param integer $iconMediaId The augBubble's icon media identifier
	 * @param integer $mediaId The augBubble's media identifier
	 * @param integer $alignMediaId The augBubble's align media identifier
	 * @return returnData
	 * @returns a returnData object containing the new augBubble identifier
	 * @see returnData
	 */
	public static function createAugBubble($gameId, $name, $desc, $iconMediaId)
	{
		$name = addslashes($name);	

		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "INSERT INTO aug_bubbles 
			(game_id, name, description, icon_media_id)
			VALUES ('{$gameId}', '{$name}', '{$desc}', '{$iconMediaId}')";

		NetDebug::trace("createAugBubble: Running a query = $query");	

		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);		

		return new returnData(0, mysql_insert_id());
	}



	/**
	 * Updates an AugBubble's properties
	 *
	 * @param integer $gameId The game identifier
	 * @param integer $augBubbleId The augbubble identifier
	 * @param string $name The new name
	 * @param string $desc The augBubble to reach
	 * @param integer $iconMediaId The new icon media identifier
	 * @return returnData
	 * @returns a returnData object containing a TRUE if an change was made, FALSE otherwise
	 * @see returnData
	 */
	public static function updateAugBubble($gameId, $augBubbleId, $name, $desc, $iconMediaId)
	{
		$prefix = Module::getPrefix($gameId);

		$name = addslashes($name);	

		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		$query = "UPDATE aug_bubbles 
			SET name = '{$name}', 
			    description = '{$desc}', 
			    icon_media_id = '{$iconMediaId}'
				    WHERE aug_bubble_id = '{$augBubbleId}'";

		NetDebug::trace("updateAugBubble: Running a query = $query");	

		@mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error:" . mysql_error() . "while running query:" . $query);

		if (mysql_affected_rows()) return new returnData(0, TRUE, "Success Running:" . $query);
		else return new returnData(0, FALSE, "Success Running:" . $query);


	}

	public static function removeAugBubbleMediaIndex($intAugId, $intMediaId, $intIndex)
	{
		$prefix = Module::getPrefix($gameId);
		$query = "DELETE FROM aug_bubble_media WHERE aug_bubble_id = '{$intAugId}' AND media_id = '{$intMediaId}'";
		mysql_query($query);

		return new returnData(0);
	}

	public static function updateAugBubbleMediaIndex($intAugId, $intMediaId, $stringName, $intGameId, $intIndex)
	{
		$prefix = Module::getPrefix($gameId);

		/* This will be for when index is implemented
		   $query = "SELECT * FROM aug_bubble_media WHERE aug_bubble_id = '{$intAugId}' AND media_id = '{$intMediaId}'";
		   $result = mysql_query($query);

		   if(mysql_num_rows($result)>0){
		   $query = "UPDATE aug_bubble_media SET";
		   }
		 */

		$query = "INSERT INTO aug_bubble_media (aug_bubble_id, media_id, text, game_id) VALUES ('{$intAugId}', '{$intMediaId}', '{$stringName}', '{$intGameId}')";
		NetDebug::trace($query);
		mysql_query($query);

		if (mysql_error()) return new returnData(1, NULL, mysql_error());

		return new returnData(0);
	}

	/**
	 * Deletes an Aug Bubble from a game, removing any refrence made to it in the rest of the game
	 *
	 * When this service runs, locations, requirements, and playerStatechanges
	 * are updated to remove any refrence to the deleted augBubble.
	 *
	 * @param integer $gameId The game identifier
	 * @param integer $augBubbleId The aug bubble identifier
	 * @return returnData
	 * @returns a returnData object containing a TRUE if an change was made, FALSE otherwise
	 * @see returnData
	 */
	public static function deleteAugBubble($gameId, $augBubbleId)
	{
		$prefix = Module::getPrefix($gameId);
		if (!$prefix) return new returnData(1, NULL, "invalid game id");

		Locations::deleteLocationsForObject($gameId, 'AugBubble', $augBubbleId);
		Requirements::deleteRequirementsForRequirementObject($gameId, 'AugBubble', $augBubbleId);
		PlayerStateChanges::deletePlayerStateChangesThatRefrenceObject($gameId, 'AugBubble', $augBubbleId);

		$query = "DELETE FROM aug_bubbles WHERE game_id = '{$gameId}' AND aug_bubble_id = {$augBubbleId}";

		$rsResult = @mysql_query($query);
		if (mysql_error()) return new returnData(3, NULL, "SQL Error");

		if (mysql_affected_rows()) {
			return new returnData(0, TRUE);
		}
		else {
			return new returnData(0, FALSE);
		}

	}	
}
