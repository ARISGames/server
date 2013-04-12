<?php
require_once("module.php");
require_once("media.php");
require_once("quests.php");

class Games extends Module
{	
    public function getGame($gameId)
    {
        $query = "SELECT * FROM games WHERE game_id = {$gameId} LIMIT 1";
        $rs = Module::query($query);
        if (mysql_error())  return new returnData(3, NULL, 'SQL error');

        $game = @mysql_fetch_object($rs);
        if (!$game) return new returnData(2, NULL, "invalid game id");

        return new returnData(0, $game);
    }

    public function getGamesForPlayerAtLocation($playerId, $latitude, $longitude, $maxDistance=99999999, $locational, $includeGamesinDevelopment)
    {
        if ($includeGamesinDevelopment) $query = "
            SELECT games.game_id FROM games JOIN locations ON games.game_id = locations.game_id 
                WHERE locations.latitude BETWEEN {$latitude}-.5 AND {$latitude}+.5
                AND locations.longitude BETWEEN {$longitude}-.5 AND {$longitude}+.5
                AND is_locational = '{$locational}'
                GROUP BY games.game_id
                LIMIT 50";
        else $query = "
            SELECT games.game_id FROM games JOIN locations ON games.game_id = locations.game_id 
                WHERE locations.latitude BETWEEN {$latitude}-.5 AND {$latitude}+.5
                AND locations.longitude BETWEEN {$longitude}-.5 AND {$longitude}+.5
                AND is_locational = '{$locational}'
                AND ready_for_public = TRUE
                GROUP BY games.game_id
                LIMIT 50";

        $gamesRs = Module::query($query);

        $games = array();
        while ($game = @mysql_fetch_object($gamesRs)) {
            $gameObj = new stdClass;
            $gameObj = Games::getFullGameObject($game->game_id, $playerId, 1, $maxDistance, $latitude, $longitude);
            if($gameObj != NULL) $games[] = $gameObj;
        }
        return new returnData(0, $games, NULL);
    }		

    public function getOneGame($gameId, $intPlayerId, $boolGetLocationalInfo = 0, $intSkipAtDistance = 99999999, $latitude = 0, $longitude = 0)
    {
        $games = array();

        $gameObj = new stdClass;
        $gameObj = Games::getFullGameObject($gameId, $intPlayerId, $boolGetLocationalInfo, $intSkipAtDistance, $latitude, $longitude);

        if($gameObj != NULL)
            $games[] = $gameObj;
        return new returnData(0, $games, NULL);
    }	

    public function getplayerLogsForGameAndDateRange($gameId, $startDate, $endDate)
    {
        $startDate = urldecode($startDate);
        $endDate = urldecode($endDate);

        $query = "SELECT player_log.*, players.user_name FROM player_log 
            JOIN players ON player_log.player_id = players.player_id WHERE game_id = {$gameId} AND
            timestamp BETWEEN DATE('{$startDate}') AND DATE('{$endDate}')";
        $result = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, mysql_error());
        return new returnData(0, $result, NULL);
    }

    public function getTabBarItemsForGame($gameId)
    {
        $result = Module::query("SELECT * FROM game_tab_data WHERE game_id = '{$gameId}' ORDER BY tab_index ASC");

        if(mysql_num_rows($result) == 0){
            Module::query("INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$gameId}', 'QUESTS', '1')");
            Module::query("INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$gameId}', 'GPS', '2')");
            Module::query("INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$gameId}', 'INVENTORY', '3')");
            Module::query("INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$gameId}', 'QR', '4')");
            Module::query("INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$gameId}', 'PLAYER', '5')");
            Module::query("INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$gameId}', 'NOTE',  '6')");
            Module::query("INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$gameId}', 'STARTOVER', '998')");
            Module::query("INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$gameId}', 'PICKGAME', '9999')");
            $result = Module::query("SELECT * FROM game_tab_data WHERE game_id = '{$gameId}' ORDER BY tab_index ASC");
        }
        return new returnData(0, $result, NULL);
    }

    public function saveTab($gameId, $stringTabType, $intIndex, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        Module::query("UPDATE game_tab_data SET tab_index = '{$intIndex}' WHERE game_id = '{$gameId}' AND tab = '{$stringTabType}'");
        return new returnData(0);
    }

    public function getFullGameObject($gameId, $intPlayerId, $boolGetLocationalInfo = 0, $intSkipAtDistance = 99999999, $latitude = 0, $longitude = 0)
    {
        $gameObj = Module::queryObject("SELECT * FROM games WHERE game_id = '{$gameId}' LIMIT 1");

        //Check if Game Has Been Played
        $query = "SELECT * FROM player_log WHERE game_id = '{$gameId}' AND player_id = '{$intPlayerId}' AND deleted = 0 LIMIT 1";
        $result = Module::query($query);
        if(mysql_num_rows($result) > 0) $gameObj->has_been_played = true;
        else                            $gameObj->has_been_played = false;

        //Get Locational Stuff
        if($boolGetLocationalInfo)
        {
            if($gameObj->is_locational == true)
            {
                $nearestLocation = Games::getNearestLocationOfGameToUser($latitude, $longitude, $gameId);
                $gameObj->latitude = $nearestLocation->latitude;
                $gameObj->longitude = $nearestLocation->longitude;
                $gameObj->distance = $nearestLocation->distance;
                if($gameObj->distance == NULL || $gameObj->distance > $intSkipAtDistance) return NULL;
            }
            else
            {
                $gameObj->latitude = 0;
                $gameObj->longitude = 0;
                $gameObj->distance = 0;
            }
        }
        //Get Quest Stuff
        //$questsReturnData = Quests::getQuestsForPlayer($gameId, $intPlayerId);
        //$gameObj->totalQuests = $questsReturnData->data->totalQuests;
        //$gameObj->completedQuests = count($questsReturnData->data->completed);

        //Get Editors
        $query = "SELECT editors.* FROM editors, game_editors
            WHERE game_editors.editor_id = editors.editor_id
            AND game_editors.game_id = {$gameId}";
        $editorsRs = Module::query($query);
        $editor = @mysql_fetch_array($editorsRs);
        $editorsString = $editor['name'];
        while ($editor = @mysql_fetch_array($editorsRs)) {
            $editorsString .= ', ' . $editor['name'];
        }
        $gameObj->editors = $editorsString;

        //Get Num Players
        $query = "SELECT * FROM players
            WHERE last_game_id = {$gameId}";
        $playersRs = Module::query($query);
        $gameObj->numPlayers = @mysql_num_rows($playersRs);

        //Get the media URLs

        //Icon
        $icon_media_data = Media::getMediaObject($gameId, $gameObj->icon_media_id);
        $icon_media = $icon_media_data->data; 
        $gameObj->icon_media_url = $icon_media->url_path . $icon_media->file_path;

        //Media
        $media_data = Media::getMediaObject($gameId, $gameObj->media_id);
        $media = $media_data->data; 
        $gameObj->media_url = $media->url_path . $media->file_path;

        //Calculate the rating
        $query = "SELECT AVG(rating) AS rating FROM game_comments WHERE game_id = {$gameId}";
        $avRs = Module::query($query);
        $avRecord = @mysql_fetch_object($avRs);
        $gameObj->rating = $avRecord->rating;
        if($gameObj->rating == NULL) $gameObj->rating = 0;

        //Getting Comments
        $query = "SELECT * FROM game_comments WHERE game_id = {$gameId}";
        $result = Module::query($query);
        $comments = array();
        $x = 0;
        while($row = mysql_fetch_assoc($result)){
            $comments[$x]->playerId = $row['player_id'];
            $query = "SELECT user_name FROM players WHERE player_id = '{$comments[$x]->playerId}'";
            $player = Module::query($query);
            $playerOb = mysql_fetch_assoc($player);
            $comments[$x]->username = $playerOb['user_name'];
            $comments[$x]->rating = $row['rating'];
            $comments[$x]->text = $row['comment'] == 'Comment' ? "" : $row['comment'];
            $x++;
        }
        $gameObj->comments = $comments;

        //Calculate score
        $gameObj->calculatedScore = ($gameObj->rating - 3) * $x;
        $gameObj->numComments = $x;
        return $gameObj;
    }

    public function getGamesForEditor($editorId, $editorToken)
    {
        if(!Module::authenticateEditor($editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $query = "SELECT super_admin FROM editors 
            WHERE editor_id = '$editorId' LIMIT 1";
        $editor = mysql_fetch_array(Module::query($query));

        if ($editor['super_admin'] == 1)
            $query = "SELECT * FROM games";
        else
            $query = "SELECT g.* from games g, game_editors ge 
                WHERE g.game_id = ge.game_id AND ge.editor_id = '$editorId'";

        $rs = Module::query($query);
        if (mysql_error())  return new returnData(3, NULL, 'SQL error');
        return new returnData(0, $rs, NULL);		
    }

    public function createGame($name, $description, 
            $iconMediaId, $mediaId,
            $readyForPublic, $isLocational, 
            $introNodeId, $completeNodeId,
            $shareToMap, $shareToBook, $allowPlayerTags, $allowNoteComments, $allowNoteLikes,
            $pcMediaId, $usePlayerPic, 
            $mapType, $showPlayerOnMap, 
            $allLocQuickTravel, 
            $inventoryWeightCap, $allowTrading,  
            $editorId, $editorToken)
    {
        if(!Module::authenticateEditor($editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $name        = addslashes($name);	
        $description = addslashes($description);

        if($g = Module::queryObject("SELECT * FROM games WHERE name = '".$name."'"))
            return new returnData(4, $g->game_id, 'duplicate name');

        $query = "INSERT INTO games (
            name, description,
            icon_media_id, media_id,
            ready_for_public, is_locational,
            on_launch_node_id, game_complete_node_id,
            allow_share_note_to_map, allow_share_note_to_book, allow_player_tags, allow_note_comments, allow_note_likes,
            pc_media_id, use_player_pic,
            map_type, show_player_location,
            full_quick_travel,
            inventory_weight_cap, allow_trading,
            created
                ) VALUES (
                    '".$name."', '".$description."',
                    '".$iconMediaId."', '".$mediaId."',
                    '".$readyForPublic."', '".$isLocational."',
                    '".$introNodeId."', '".$completeNodeId."',
                    '".$shareToMap."', '".$shareToBook."', '".$allowPlayerTags."', '".$allowNoteComments."', '".$allowNoteLikes."',
                    '".$pcMediaId."', '".$usePlayerPic."',
                    '".$mapType."', '".$showPlayerOnMap."',
                    '".$allLocQuickTravel."',
                    '".$inventoryWeightCap."', '".$allowTrading."',
                    NOW())";

        Module::query($query);
        if (mysql_error())  return new returnData(6, NULL, "cannot create game record using SQL: $query");
        $newGameId = mysql_insert_id();

        Module::query("INSERT INTO game_editors (game_id,editor_id) VALUES ('{$newGameId}','{$editorId}')");
        if (mysql_error()) return new returnData(6, NULL, 'cannot create game_editors record');

        Module::query("INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$newGameId}', 'QUESTS', '1')");
        Module::query("INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$newGameId}', 'GPS', '2')");
        Module::query("INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$newGameId}', 'INVENTORY', '3')");
        Module::query("INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$newGameId}', 'QR', '4')");
        Module::query("INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$newGameId}', 'PLAYER', '5')");
        Module::query("INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$newGameId}', 'NOTE', '6')");
        Module::query("INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$newGameId}', 'STARTOVER', '998')");
        Module::query("INSERT INTO `game_tab_data` (`game_id` ,`tab` ,`tab_index`) VALUES ('{$newGameId}', 'PICKGAME', '9999')");

        $newGameDirectory = Media::getMediaDirectory($newGameId)->data;

        mkdir($newGameDirectory,0777);

        return new returnData(0, $newGameId, NULL);
    }

    public function updateGame($gameId, $name, $description, 
            $iconMediaId, $mediaId,
            $readyForPublic, $isLocational, 
            $introNodeId, $completeNodeId,
            $shareToMap, $shareToBook, $allowPlayerTags, $allowNoteComments, $allowNoteLikes,
            $pcMediaId, $usePlayerPic, 
            $mapType, $showPlayerOnMap, 
            $allLocQuickTravel, 
            $inventoryWeightCap, $allowTrading,  
            $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $name        = addslashes($name);	
        $description = addslashes($description);

        $query = "UPDATE games SET 
            name                     = '".$name."',
                                     description              = '".$description."',
                                     icon_media_id            = '".$iconMediaId."',
                                     media_id                 = '".$mediaId."',
                                     ready_for_public         = '".$readyForPublic."',
                                     is_locational            = '".$isLocational."',
                                     on_launch_node_id        = '".$introNodeId."',
                                     game_complete_node_id    = '".$completeNodeId."',
                                     allow_share_note_to_map  = '".$shareToMap."',
                                     allow_share_note_to_book = '".$shareToBook."',
                                     allow_player_tags        = '".$allowPlayerTags."',
                                     allow_note_comments      = '".$allowNoteComments."',
                                     allow_note_likes         = '".$allowNoteLikes."',
                                     pc_media_id              = '".$pcMediaId."',
                                     use_player_pic           = '".$usePlayerPic."',
                                     map_type                 = '".$mapType."',
                                     show_player_location     = '".$showPlayerOnMap."',
                                     full_quick_travel        = '".$allLocQuickTravel."',
                                     inventory_weight_cap     = '".$inventoryWeightCap."',
                                     allow_trading            = '".$allowTrading."'
                                         WHERE game_id            = '".$gameId."'";
        Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, "SQL Error: " . mysql_error());

        return new returnData(0);		
    }		

    public function setPCMediaId($gameId, $intPCMediaId)
    {
        $query = "UPDATE games 
            SET pc_media_id = '{$intPCMediaId}'
            WHERE game_id = {$gameId}";
        Module::query($query);
        if (mysql_error()) return new returnData(3, false, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);		
    }			

    /**
     * Upgrades the database schema
     * NOTE- 
     *  There isn't really a formal versioning system in place for this.
     *  Be cautious about running this; check current schema before to ensure things don't get weird.
     *  Also, in editing this function, try to be as clean and verbose as possible.
     */	
    public function upgradeDatabase() 
    {		
        $version = 1;  //Arbitrary version. Increment on edit. Should be able to grep the log to see what last run upgrade was. (Unreliable)
        Module::serverErrorLog("Upgrading database. Version ".$version);

        /* Version 1 Upgrades */
        $query = "";

        return new returnData(0);
    }

    public function setGameName($gameId, $strNewName)
    {
        $returnData = new returnData(0, Module::query($query), NULL);

        $strNewGameName = addslashes($strNewGameName);	

        $query = "UPDATE games SET name = '{$strNewName}' WHERE game_id = {$gameId}";
        Module::query($query);
        if (mysql_error()) return new returnData(3, false, "SQL Error");

        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);		
    }		

    public function deleteGame($gameId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        Module::serverErrorLog("Deleting Game Id: {$gameId}");

        $command = 'rm -rf '. Config::gamedataFSPath . "/{$gameId}";
        exec($command, $output, $return);
        if($return) return new returnData(4, NULL, "unable to delete game directory");

        Module::query("DELETE FROM games                WHERE game_id = '{$gameId}_'");
        Module::query("DELETE FROM game_editors         WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM media                WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM web_pages            WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM aug_bubbles          WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM aug_bubble_media     WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM web_hooks            WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM game_tab_data        WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM notes                WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM note_content         WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM npcs                 WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM folder_contents      WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM folders              WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM items                WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM locations            WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM nodes                WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM npc_conversations    WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM player_items         WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM player_state_changes WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM qrcodes              WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM quests               WHERE game_id = '{$gameId}'");
        Module::query("DELETE FROM requirements         WHERE game_id = '{$gameId}'");

        //Delete Overlays //SHOULD HAVE A GAME_ID COLUMN!!!
        $result = Module::query("SELECT * FROM overlays WHERE game_id = '{$gameId}'");
        while($result && $row = mysql_fetch_object($result))
        {
            Module::query("DELETE FROM overlay_tiles WHERE overlay_id = '{$row->overlay_id}'");
            Module::query("DELETE FROM overlays      WHERE overlay_id = '{$row->overlay_id}'");
        }

        return new returnData(0);	
    }

    public function getGameEditors($gameId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $query = "SELECT game_editors.*, editors.* FROM game_editors LEFT JOIN editors ON game_editors.editor_id=editors.editor_id WHERE game_editors.game_id = {$gameId}";
        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, 'SQL Error');
        return new returnData(0, $rsResult);
    }

    public function addEditorToGame($newEditorId, $gameId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $query = "INSERT INTO game_editors (editor_id, game_id) VALUES ('{$newEditorId}','{$gameId}')";
        $rsResult = Module::query($query);

        if (mysql_errno() == 1062) return new returnData(4, NULL, 'duplicate');
        if (mysql_error()) return new returnData(3, NULL, 'sql error');

        $query = "SELECT email FROM editors WHERE editor_id = $newEditorId";
        $result = Module::query($query);
        $emailObj = mysql_fetch_object($result);
        $email = $emailObj->email;

        $query = "SELECT name FROM games WHERE game_id = $gameId";
        $result = Module::query($query);
        $gameObj = mysql_fetch_object($result);
        $game = $gameObj->name;

        $body = "An owner of ARIS Game \"".$game."\" has promoted you to editor. Go to ".Config::WWWPath."/editor and log in to begin collaborating!";
        Module::sendEmail($email, "You are now an editor of ARIS Game \"$game\"", $body);

        return new returnData(0);	
    }	

    public function removeEditorFromGame($newEditorId, $gameId, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $query = "DELETE FROM game_editors WHERE editor_id = '{$newEditorId}' AND game_id = '{$gameId}'";
        $rsResult = Module::query($query);
        if (mysql_error()) return new returnData(3, NULL, 'SQL Error');

        if (mysql_affected_rows()) return new returnData(0, TRUE);
        else return new returnData(0, FALSE);
    }

    public function saveComment($intPlayerId, $gameId, $intRating, $comment)
    {
        if($comment == 'Comment') $comment = "";
        $query = "SELECT * FROM game_comments WHERE game_id = '{$gameId}' AND player_id = '{$intPlayerId}'";
        $result = Module::query($query);
        if(mysql_num_rows($result) > 0) $query = "UPDATE game_comments SET rating='{$intRating}', comment='{$comment}' WHERE game_id = '{$gameId}' AND player_id = '{$intPlayerId}'";
        else $query = "INSERT INTO game_comments (game_id, player_id, rating, comment) VALUES ('{$gameId}', '{$intPlayerId}', '{$intRating}', '{$comment}')";
        Module::query($query);

        if (mysql_error()) return new returnData(3, NULL, 'SQL Error');
        $query = "SELECT editors.email FROM (SELECT * FROM game_editors WHERE game_id = ".$gameId.") AS ge LEFT JOIN editors ON ge.editor_id = editors.editor_id";
        $result = Module::query($query);
        if(mysql_num_rows($result) > 0)
        {
            $gameName = mysql_fetch_object(Module::query("SELECT name FROM games WHERE game_id = $gameId"))->name;
            $playerName = mysql_fetch_object(Module::query("SELECT user_name FROM players WHERE player_id = $intPlayerId"))->user_name;
            $sub = "New Rating for '".$gameName."'";
            $body = "Congratulations! People are playing your ARIS game! \n".$playerName." Recently gave your game ".$intRating." stars out of 5" . (($comment && $comment != 'Comment') ? ", commenting \"".$comment."\"" : ".");
        }
        while($ob = mysql_fetch_object($result))
            Module::sendEmail($ob->email,$sub,$body);

        return new returnData(0);
    }

    public function getGamesWithLocations($latitude, $longitude, $boolIncludeDevGames = 0)
    {
        $games = array();

        if($boolIncludeDevGames) $query = "SELECT game_id, name FROM games WHERE is_locational = 1";
        else $query = "SELECT game_id, name FROM games WHERE ready_for_public = 1 AND is_locational = 1";
        $idResult = Module::query($query);

        while($gameId = mysql_fetch_assoc($idResult))
        {
            $game = new stdClass;
            $game->game_id = $gameId['game_id']; 
            $game->name = $gameId['name'];

            $query = "SELECT AVG(rating) AS rating FROM game_comments WHERE game_id = {$gameId['game_id']}";
            $ratingResult = Module::query($query);

            $rating = mysql_fetch_assoc($ratingResult);
            if($rating['rating'] != NULL){
                $query = "SELECT rating FROM game_comments WHERE game_id = {$gameId['game_id']}";
                $result = Module::query($query);
                $game->rating = $rating['rating'];
                $game->calculatedScore = (($rating['rating']-3) * mysql_num_rows($result));
            }
            else {
                $game->rating = 0;
                $game->calculatedScore = 0;
            }

            //Get locations
            $nearestLocation = Games::getNearestLocationOfGameToUser($latitude, $longitude, $gameId['game_id']);
            $game->latitude = $nearestLocation->latitude;
            $game->longitude = $nearestLocation->longitude;

            if($game->latitude != NULL){
                $games[] = $game;
            }
        }

        return new returnData(0, $games);
    }

    protected function getNearestLocationOfGameToUser($latitude, $longitude, $gameId)
    {
        $query = "SELECT latitude, longitude,((ACOS(SIN($latitude * PI() / 180) * SIN(latitude * PI() / 180) + 
            COS($latitude * PI() / 180) * COS(latitude * PI() / 180) * 
            COS(($longitude - longitude) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) * 1609.344
            AS `distance`
            FROM locations
            WHERE game_id = {$gameId} AND (type != 'Item' OR item_qty > 0)
            ORDER BY distance ASC";

        if (!$nearestLocationRs = Module::query($query)) return null;
        $nearestLocation = mysql_fetch_object($nearestLocationRs);
        return $nearestLocation;
    }

    public function getGamesContainingText($intPlayerId, $latitude, $longitude, $textToFind, $boolIncludeDevGames = 1, $page = 0)
    {
        $textToFind = addSlashes($textToFind);
        $textToFind = urldecode($textToFind);
        if($boolIncludeDevGames) $query = "SELECT game_id, name FROM games WHERE (name LIKE '%{$textToFind}%' OR description LIKE '%{$textToFind}%') ORDER BY name ASC LIMIT ".($page*25).", 25";
        else $query = "SELECT game_id, name FROM games WHERE (name LIKE '%{$textToFind}%' OR description LIKE '%{$textToFind}%') AND ready_for_public = 1 ORDER BY name ASC LIMIT ".($page*25).", 25";

        $result = Module::query($query);
        $games = array();
        while($game = mysql_fetch_object($result)){
            $gameObj = new stdClass;
            $gameObj = Games::getFullGameObject($game->game_id, $intPlayerId, 1, 9999999999, $latitude, $longitude);
            if($gameObj != NULL){
                $games[] = $gameObj;
            }
            else{
                $gameObj = Games::getFullGameObject($game->game_id, $intPlayerId, 0, 9999999999, $latitude, $longitude);
                if($gameObj != NULL){
                    $games[] = $gameObj;
                }
            }
        }
        return new returnData(0, $games);
    }

    public function getRecentGamesForPlayer($intPlayerId, $latitude, $longitude, $boolIncludeDevGames = 1)
    {
        $query = "SELECT p_log.*, games.ready_for_public FROM (SELECT player_id, game_id, timestamp FROM player_log WHERE player_id = {$intPlayerId} AND game_id != 0 ORDER BY timestamp DESC) as p_log LEFT JOIN games ON p_log.game_id = games.game_id ".($boolIncludeDevGames ? "" : "WHERE games.ready_for_public = 1 ")."GROUP BY game_id ORDER BY timestamp DESC LIMIT 10"; 
        $result = Module::query($query);
        $x = 0;
        $games = array();
        while($game = mysql_fetch_object($result))
        {
            $gameObj = Games::getFullGameObject($game->game_id, $intPlayerId, 1, 9999999999, $latitude, $longitude);
            if($gameObj != NULL) $games[] = $gameObj;
        }

        return new returnData(0, $games);
    }

    public function getPopularGames($playerId, $time, $includeGamesinDevelopment)
    {
        if ($time == 0) $queryInterval = '1 DAY';
        else if ($time == 1) $queryInterval = '7 DAY';
        else if ($time == 2) $queryInterval = '1 MONTH';

        if ($includeGamesinDevelopment) $query = "SELECT media.file_path as file_path, temp.game_id, temp.name, temp.description, temp.count FROM (SELECT games.game_id, games.name, games.description, games.icon_media_id, COUNT(DISTINCT player_id) AS count FROM games INNER JOIN player_log ON games.game_id = player_log.game_id WHERE player_log.timestamp BETWEEN DATE_SUB(NOW(), INTERVAL ".$queryInterval.") AND NOW() GROUP BY games.game_id HAVING count > 1) as temp LEFT JOIN media ON temp.icon_media_id = media.media_id GROUP BY game_id HAVING count > 1 ORDER BY count DESC LIMIT 20";

        else $query = "SELECT media.file_path as file_path, temp.game_id, temp.name, temp.description, temp.count FROM (SELECT games.game_id, games.name, games.description, games.icon_media_id, COUNT(DISTINCT player_id) AS count FROM games INNER JOIN player_log ON games.game_id = player_log.game_id WHERE ready_for_public = TRUE AND player_log.timestamp BETWEEN DATE_SUB(NOW(), INTERVAL ".$queryInterval.") AND NOW() GROUP BY games.game_id HAVING count > 1) as temp LEFT JOIN media ON temp.icon_media_id = media.media_id GROUP BY game_id HAVING count > 1 ORDER BY count DESC LIMIT 20";

        $gamesRs = Module::query($query);

        $games = array();
        while ($game = @mysql_fetch_object($gamesRs)) {
            $gameObj = new stdClass;
            $gameObj = Games::getFullGameObject($game->game_id, $playerId, 0, 9999999999, 0, 0);
            if($gameObj != NULL){
                $gameObj->count = $game->count;
                $games[] = $gameObj;
            }
        }
        return new returnData(0, $games, NULL);
    }		

    public function duplicateGame($gameId, $editorId, $editorToken)
    {
        if(!Module::authenticateEditor($editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        Module::serverErrorLog("Duplicating Game Id:".$gameId);

        $g = Module::queryObject("SELECT * FROM games WHERE game_id = {$gameId} LIMIT 1");
        if (!$g) return new returnData(2, NULL, "invalid game id");

        $compatibleName = false;
        $appendNo = 1;
        while(!$compatibleName)
        {
            $query = "SELECT * FROM games WHERE name = '".addslashes($game->name)."_copy".$appendNo."'";
            $result = Module::query($query);
            if(mysql_fetch_object($result))
                $appendNo++;
            else
                $compatibleName = true;
        }
        $game->name = $game->name."_copy".$appendNo;

        $newGameId = Games::createGame($game->name, $game->description, 
                $game->icon_media_id, $game->media_id,
                $game->ready_for_public, $game->is_locational,
                $game->on_launch_node_id, $game->game_complete_node_id,
                $game->allow_share_note_to_map, $game->allow_share_note_to_book, $game->allow_player_tags, $game->allow_player_comments, $game->allow_note_likes,
                $game->pc_media_id, $game->use_player_pic,
                $game->map_type, $game->show_player_location,
                $game->full_quick_travel,
                $game->inventory_weight_cap, $game->allow_trading, 
                $editorId, $editorToken);

        //Remove the tabs created by createGame
        Module::query("DELETE FROM game_tab_data WHERE game_id = {$newGameId}");

        $result = Module::query("SELECT * FROM game_tab_data WHERE game_id = {$gameId}");
        while($result && $row = mysql_fetch_object($result))
            Module::query("INSERT INTO game_tab_data (game_id, tab, tab_index, tab_detail_1) VALUES ('{$newGameId}', '{$row->tab}', '{$row->tab_index}', '{$row->tab_detail_1}')");

        $query = "SELECT * FROM requirements WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO requirements (game_id, content_type, content_id, requirement, not_operator, boolean_operator, requirement_detail_1, requirement_detail_2, requirement_detail_3, requirement_detail_4) VALUES ('{$newGameId}', '{$row->content_type}', '{$row->content_id}', '{$row->requirement}', '{$row->not_operator}', '{$row->boolean_operator}', '{$row->requirement_detail_1}', '{$row->requirement_detail_2}', '{$row->requirement_detail_3}', '{$row->requirement_detail_4}')";
            Module::query($query);
        }

        $query = "SELECT * FROM quests WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO quests (game_id, name, description, text_when_complete, sort_index, exit_to_tab, active_media_id, complete_media_id, active_icon_media_id, complete_icon_media_id) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '".addSlashes($row->description)."', '".addSlashes($row->text_when_complete)."', '{$row->sort_index}', '{$row->exit_to_tab}', '{$row->active_media_id}', '{$row->complete_media_id}', '{$row->active_icon_media_id}', '{$row->complete_icon_media_id}')";

            Module::query($query);
            $newId = mysql_insert_id();

            $query = "UPDATE requirements SET requirement_detail_1 = {$newId} WHERE game_id = '{$newGameId}' AND requirement = 'PLAYER_HAS_COMPLETED_QUEST' AND requirement_detail_1 = '{$row->quest_id}'";
            Module::query($query);


            $query = "UPDATE requirements SET content_id = {$newId} WHERE game_id = '{$newGameId}' AND (content_type = 'QuestDisplay' OR content_type = 'QuestComplete') AND content_id = '{$row->quest_id}'";
            Module::query($query);
        }

        $newFolderIds = array();
        $query = "SELECT * FROM folders WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO folders (game_id, name, parent_id, previous_id, is_open) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '{$row->parent_id}', '{$row->previous_id}', '{$row->is_open}')";
            Module::query($query);
            $newFolderIds[($row->folder_id)] = mysql_insert_id();
        }

        $query = "SELECT * FROM folders WHERE game_id = {$newGameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){
            if($row->folder_id != 0){
                $query = "UPDATE folders SET parent_id = {$newFolderIds[($row->parent_id)]} WHERE game_id = '{$newGameId}' AND folder_id = {$row->folder_id}";
                Module::query($query);
            }
        }

        $query = "SELECT * FROM folder_contents WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO folder_contents (game_id, folder_id, content_type, content_id, previous_id) VALUES ('{$newGameId}', '{$newFolderIds[($row->folder_id)]}', '{$row->content_type}', '{$row->content_id}', '{$row->previous_id}')";
            Module::query($query);

            if($row->folder_id != 0){
                $query = "UPDATE folder_contents SET folder_id = {$newFolderIds[($row->folder_id)]} WHERE game_id = '{$newGameId}' AND object_content_id = {$row->object_content_id}";
                Module::query($query); 
            }
        }

        $query = "SELECT * FROM qrcodes WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO qrcodes (game_id, link_type, link_id, code, match_media_id) VALUES ('{$newGameId}', '{$row->link_type}', '{$row->link_id}', '{$row->code}', '{$row->match_media_id}')";
            Module::query($query);
        }

        $query = "SELECT * FROM overlays WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO overlays (game_id, sort_order, alpha, num_tiles, game_overlay_id) VALUES ('{$newGameId}', '{$row->sort_order}', '{$row->alpha}', '{$row->num_tiles}', '{$row->game_overlay_id}')";
            Module::query($query);
            $newId = mysql_insert_id();
            $query = "SELECT * FROM overlay_tiles WHERE overlay_id = '{$row->overlay_id}'";
            $result = Module::query($query);
            while($result && $row = mysql_fetch_object($result)){
                $query = "INSERT INTO overlay_tiles (overlay_id, media_id, zoom, x, x_max, y, y_max) VALUES ('{$newId}', '{$row->media_id}', '{$row->zoom}', '{$row->x}', '{$row->x_max}',  '{$row->y}',  '{$row->y_max}')";
                Module::query($query);
            }
        }

        $query = "SELECT * FROM fountains WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO fountains (game_id, type, location_id, spawn_probability, spawn_rate, max_amount, last_spawned, active) VALUES ('{$newGameId}', '{$row->type}', '{$row->location_id}', '{$row->spawn_probability}', '{$row->spawn_rate}', '{$row->max_amount}', '{$row->last_spawned}', '{$row->active}')";
            Module::query($query);
        }

        $query = "SELECT * FROM spawnables WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO spawnables (game_id, type, type_id, amount, max_area, amount_restriction, location_bound_type, latitude, longitude, spawn_probability, spawn_rate, delete_when_viewed, last_spawned, error_range, force_view, hidden, allow_quick_travel, wiggle, time_to_live, active, location_name, show_title, min_area) VALUES ('{$newGameId}', '{$row->type}', '{$row->type_id}', '{$row->amount}', '{$row->max_area}', '{$row->amount_restriction}', '{$row->location_bound_type}', '{$row->latitude}', '{$row->longitude}', '{$row->spawn_probability}', '{$row->spawn_rate}', '{$row->delete_when_viewed}', '{$row->last_spawned}', '{$row->error_range}', '{$row->force_view}', '{$row->hidden}', '{$row->allow_quick_travel}', '{$row->wiggle}', '{$row->time_to_live}', '{$row->active}', '{$row->location_name}', '{$row->show_title}', '{$row->min_area}')";
            Module::query($query);
            $newId = mysql_insert_id();

            $query = "UPDATE fountains SET location_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Spawnable' AND location_id = {$row->spawnable_id}";
            Module::query($query);
        }

        $query = "SELECT * FROM locations WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO locations (game_id, name, description, latitude, longitude, error, type, type_id, icon_media_id, item_qty, hidden, force_view, allow_quick_travel) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '".addSlashes($row->description)."', '{$row->latitude}', '{$row->longitude}', '{$row->error}', '{$row->type}', '{$row->type_id}', '{$row->icon_media_id}', '{$row->item_qty}', '{$row->hidden}', '{$row->force_view}', '{$row->allow_quick_travel}')";
            Module::query($query);
            $newId = mysql_insert_id();

            $query = "UPDATE fountains SET location_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Location' AND location_id = {$row->location_id}";
            Module::query($query);

            $query = "UPDATE qrcodes SET link_id = {$newId} WHERE game_id = '{$newGameId}' AND link_type = 'Location' AND link_id = {$row->location_id}";
            Module::query($query);

            $query = "UPDATE requirements SET content_id = {$newId} WHERE game_id = '{$newGameId}' AND content_type = 'Location' AND content_id = {$row->location_id}";
            Module::query($query);
        }

        $query = "SELECT * FROM npc_conversations WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO npc_conversations (game_id, npc_id, node_id, text, sort_index) VALUES ('{$newGameId}', '{$row->npc_id}', '{$row->node_id}', '".addSlashes($row->text)."', '{$row->sort_index}')";
            Module::query($query);
        }

        $query = "SELECT * FROM player_state_changes WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO player_state_changes (game_id, event_type, event_detail, action, action_detail, action_amount) VALUES ('{$newGameId}', '{$row->event_type}', '{$row->event_detail}', '{$row->action}', '{$row->action_detail}', '{$row->action_amount}')";
            Module::query($query);
        }

        $newNpcIds = array();
        $query = "SELECT * FROM npcs WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){

            $query = "INSERT INTO npcs (game_id, name, description, text, closing, media_id, icon_media_id) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '".addSlashes($row->description)."', '".addSlashes($row->text)."', '".addSlashes($row->closing)."', '{$row->media_id}', '{$row->icon_media_id}')";
            Module::query($query);
            $newId = mysql_insert_id();
            $newNpcIds[($row->npc_id)] = $newId;

            $query = "UPDATE npc_conversations SET npc_id = {$newId} WHERE game_id = '{$newGameId}' AND npc_id = {$row->npc_id}";
            Module::query($query);

            $query = "UPDATE folder_contents SET content_id = {$newId} WHERE game_id = '{$newGameId}' AND content_type = 'Npc' AND content_id = {$row->npc_id}";
            Module::query($query);

            $query = "UPDATE locations SET type_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Npc' AND type_id = {$row->npc_id}";
            Module::query($query);

            $query = "UPDATE player_state_changes SET event_detail = {$newId} WHERE game_id = '{$newGameId}' AND event_type = 'VIEW_NPC' AND event_detail = {$row->npc_id}";
            Module::query($query);

            $query = "UPDATE requirements SET requirement_detail_1 = {$newId} WHERE game_id = '{$newGameId}' AND requirement = 'PLAYER_VIEWED_NPC' AND requirement_detail_1 = {$row->npc_id}";
            Module::query($query);

            $query = "UPDATE spawnables SET type_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Npc' AND type_id = {$row->npc_id}";
            Module::query($query);
        }

        $newNodeIds = array();
        $query = "SELECT * FROM nodes WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO nodes (game_id, title, text, opt1_text, opt1_node_id, opt2_text, opt2_node_id, opt3_text, opt3_node_id, require_answer_incorrect_node_id, require_answer_string, require_answer_correct_node_id, media_id, icon_media_id) VALUES ('{$newGameId}', '".addSlashes($row->title)."', '".addSlashes($row->text)."', '{$row->opt1_text}', '{$row->opt1_node_id}', '{$row->opt2_text}', '{$row->opt2_node_id}', '{$row->opt3_text}', '{$row->opt3_node_id}', '{$row->require_answer_incorrect_node_id}', '{$row->require_answer_string}', '{$row->require_answer_correct_node_id}', '{$row->media_id}', '{$row->icon_media_id}')";
            Module::query($query);
            $newId = mysql_insert_id();
            $newNodeIds[($row->node_id)] = $newId;

            $query = "UPDATE folder_contents SET content_id = {$newId} WHERE game_id = '{$newGameId}' AND content_type = 'Node' AND content_id = {$row->node_id}";
            Module::query($query);

            $query = "UPDATE locations SET type_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Node' AND type_id = {$row->node_id}";
            Module::query($query);

            $query = "UPDATE npc_conversations SET node_id = {$newId} WHERE game_id = '{$newGameId}' AND node_id = {$row->node_id}";
            Module::query($query);

            $query = "UPDATE player_state_changes SET event_detail = {$newId} WHERE game_id = '{$newGameId}' AND event_type = 'VIEW_NODE' AND event_detail = {$row->node_id}";
            Module::query($query);

            $query = "UPDATE requirements SET content_id = {$newId} WHERE game_id = '{$newGameId}' AND content_type = 'Node' AND content_id = {$row->node_id}";
            Module::query($query);

            $query = "UPDATE requirements SET requirement_detail_1 = {$newId} WHERE game_id = '{$newGameId}' AND requirement = 'PLAYER_VIEWED_NODE' AND requirement_detail_1 = {$row->node_id}";
            Module::query($query);

            $query = "UPDATE spawnables SET type_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Node' AND type_id = {$row->node_id}";
            Module::query($query);
        }

        $newItemIds = array();
        $query = "SELECT * FROM items WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO items (game_id, name, description, is_attribute, icon_media_id, media_id, dropable, destroyable, max_qty_in_inventory, creator_player_id, origin_latitude, origin_longitude, origin_timestamp, weight, url, type, tradeable) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '".addSlashes($row->description)."', '{$row->is_attribute}', '{$row->icon_media_id}', '{$row->media_id}', '{$row->dropable}', '{$row->destroyable}', '{$row->max_qty_in_inventory}', '{$row->creator_player_id}', '{$row->origin_latitude}', '{$row->origin_longitude}', '{$row->origin_timestamp}', '{$row->weight}', '{$row->url}', '{$row->type}', '{$row->tradeable}')";
            Module::query($query);
            $newId = mysql_insert_id();
            $newItemIds[($row->item_id)] = $newId;

            $query = "UPDATE folder_contents SET content_id = {$newId} WHERE game_id = '{$newGameId}' AND content_type = 'Item' AND content_id = {$row->item_id}";
            Module::query($query);

            $query = "UPDATE locations SET type_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Item' AND type_id = {$row->item_id}";
            Module::query($query);

            $query = "UPDATE player_state_changes SET event_detail = {$newId} WHERE game_id = '{$newGameId}' AND event_type = 'VIEW_ITEM' AND event_detail = {$row->item_id}";
            Module::query($query);

            $query = "UPDATE player_state_changes SET action_detail = {$newId} WHERE game_id = '{$newGameId}' AND action_detail = {$row->item_id}";
            Module::query($query);

            $query = "UPDATE requirements SET requirement_detail_1 = {$newId} WHERE game_id = '{$newGameId}' AND (requirement = 'PLAYER_HAS_ITEM' OR requirement = 'PLAYER_VIEWED_ITEM') AND requirement_detail_1 = {$row->item_id}";
            Module::query($query);

            $query = "UPDATE spawnables SET type_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Item' AND type_id = {$row->item_id}";
            Module::query($query);
        }

        $query = "SELECT * FROM aug_bubble_media WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO aug_bubble_media (game_id, aug_bubble_id, media_id, text, index) VALUES ('{$newGameId}', '{$row->aug_bubble_id}', '{$row->media_id}', '{$row->text}', '{$row->index}')";
            Module::query($query);
        }

        $newAugBubbleIds = array();
        $query = "SELECT * FROM aug_bubbles WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO aug_bubbles (game_id, name, description, icon_media_id) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '".addSlashes($row->description)."', '{$row->icon_media_id}')).";
            Module::query($query);
            $newId = mysql_insert_id();
            $newAugBubbleIds[($row->aug_bubble_id)] = $newId;

            $query = "UPDATE aug_bubble_media SET aug_bubble_id = {$newId} WHERE aug_bubble_id = {$row->aug_bubble_id}";
            Module::query($query);
            $query = "UPDATE locations SET type_id = {$newId} WHERE type = 'AugBubble' AND type_id = {$row->aug_bubble_id} AND game_id = '{$newGameId}'";
            Module::query($query);
            $query = "UPDATE folder_contents SET content_id = {$newId} WHERE content_type = 'AugBubble' AND content_id = {$row->aug_bubble_id} AND game_id = '{$newGameId}'";
            Module::query($query);
            $query = "UPDATE requirements SET requirement_detail_1 = {$newId} WHERE (requirement = 'PLAYER_HAS_NOT_VIEWED_AUGBUBBLE' OR requirement = 'PLAYER_VIEWED_AUGBUBBLE') AND requirement_detail_1 = {$row->aug_bubble_id}  AND game_id = '{$newGameId}'";
            Module::query($query);
        }

        $newWebPageIds = array();
        $query = "SELECT * FROM web_pages WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO web_pages (game_id, name, url, icon_media_id) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '{$row->url}', '{$row->icon_media_id}')";
            Module::query($query);
            $newId = mysql_insert_id();
            $newWebPageIds[($row->web_page_id)] = $newId;

            $query = "UPDATE locations SET type_id = {$newId} WHERE type = 'WebPage' AND type_id = {$row->web_page_id} AND game_id = '{$newGameId}'";
            Module::query($query);
            $query = "UPDATE folder_contents SET content_id = {$newId} WHERE content_type = 'WebPage' AND content_id = {$row->web_page_id} AND game_id = '{$newGameId}'";
            Module::query($query);
            $query = "UPDATE requirements SET requirement_detail_1 = {$newId} WHERE (requirement = 'PLAYER_HAS_NOT_VIEWED_WEBPAGE' OR requirement = 'PLAYER_VIEWED_WEBPAGE') AND requirement_detail_1 = {$row->web_page_id} AND game_id = '{$newGameId}'";
            Module::query($query);
        }

        $query = "SELECT * FROM web_hooks WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO web_hooks (game_id, name, url, incoming) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '".addSlashes($row->url)."', '{$row->incoming}')";
            Module::query($query);
            $newId = mysql_insert_id();

            $query = "UPDATE requirements SET content_id = {$newId} WHERE content_type = 'OutgoingWebHook' AND content_id = {$row->web_hook_id}  AND game_id = '{$newGameId}'";
            Module::query($query);
        }

        $originalOverlayId = array();
        $newOverlayId = array();
        $query = "SELECT * FROM overlays WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($row = mysql_fetch_object($result)){
            array_push($originalOverlayId, $row->overlay_id);
            $origOverlayId = $row->overlay_id;

            $query = "INSERT INTO overlays (game_id, game_overlay_id, name, sort_index, file_uploaded) VALUES ('{$newGameId}', '{$row->game_overlay_id}', '{$row->name}', '{$row->sort_index}', '{$row->file_uploaded}')";
            Module::query($query);
            $newId = mysql_insert_id();
            array_push($newOverlayId, $newId);

            $query2 = "SELECT * FROM overlay_tiles WHERE overlay_id = {$origOverlayId}";
            $result2 = Module::query($query2);
            while($row2 = mysql_fetch_object($result2)){
                $query3 = "INSERT INTO overlay_tiles (overlay_id, media_id, zoom, x, y) VALUES ('{$newId}', '{$row2->media_id}', '{$row2->zoom}', '{$row2->x}', '{$row2->y}')";
                Module::query($query3);
            }


            $query = "UPDATE requirements SET content_id = {$newId} WHERE content_type = 'CustomMap' AND content_id = {$row->overlay_id}";
            Module::query($query);

        }

        $originalMediaId = array();
        $newMediaId = array();
        $query = "SELECT * FROM media WHERE game_id = {$gameId}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $newMediaFilePath = $newGameId.substr($row->file_path,strpos($row->file_path,'/'));
            $query = "INSERT INTO media (game_id, name, file_path, is_icon) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '{$newMediaFilePath}', '{$row->is_icon}')";
            Module::query($query);
            $newId = mysql_insert_id();
            $newMediaIds[($row->media_id)] = $newId;

            if($row->file_path != "" && substr($row->file_path,-1) != "/" && file_exists("../../gamedata/" . $row->file_path)) copy(("../../gamedata/" . $row->file_path),("../../gamedata/" . $newMediaFilePath));

            $query = "UPDATE items SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = '{$newGameId}'";
            Module::query($query);
            $query = "UPDATE items SET media_id = {$newId} WHERE media_id = $row->media_id AND game_id = '{$newGameId}'";
            Module::query($query);
            $query = "UPDATE locations SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = '{$newGameId}'";
            Module::query($query);
            $query = "UPDATE nodes SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = '{$newGameId}'";
            Module::query($query);
            $query = "UPDATE nodes SET media_id = {$newId} WHERE media_id = $row->media_id AND game_id = '{$newGameId}'";
            Module::query($query);
            $query = "UPDATE npcs SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = '{$newGameId}'";
            Module::query($query);
            $query = "UPDATE npcs SET media_id = {$newId} WHERE media_id = $row->media_id AND game_id = '{$newGameId}'";
            Module::query($query);
            $query = "UPDATE qrcodes SET match_media_id = {$newId} WHERE match_media_id = $row->media_id AND game_id = '{$newGameId}'";
            Module::query($query);
            $query = "UPDATE quests SET active_icon_media_id = {$newId} WHERE active_icon_media_id = $row->media_id AND game_id = '{$newGameId}'";
            Module::query($query);
            $query = "UPDATE quests SET complete_icon_media_id = {$newId} WHERE complete_icon_media_id = $row->media_id AND game_id = '{$newGameId}'";
            Module::query($query);
            $query = "UPDATE quests SET active_media_id = {$newId} WHERE active_media_id = $row->media_id AND game_id = '{$newGameId}'";
            Module::query($query);
            $query = "UPDATE quests SET complete_media_id = {$newId} WHERE complete_media_id = $row->media_id AND game_id = '{$newGameId}'";
            Module::query($query);
            $query = "UPDATE aug_bubbles SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = {$newGameId}";
            Module::query($query);
            $query = "UPDATE aug_bubble_media SET media_id = {$newId} WHERE media_id = $row->media_id AND game_id = {$newGameId}";
            Module::query($query);
            $query = "UPDATE games SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = {$newGameId}";
            Module::query($query);
            $query = "UPDATE games SET media_id = {$newId} WHERE media_id = $row->media_id AND game_id = {$newGameId}";
            Module::query($query);
            $query = "UPDATE games SET pc_media_id = {$newId} WHERE pc_media_id = $row->media_id AND game_id = {$newGameId}";
            Module::query($query);
            $query = "UPDATE web_pages SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = {$newGameId}";
            Module::query($query);
            $query = "UPDATE overlay_tiles, overlays SET overlay_tiles.media_id = {$newId} WHERE overlay_tiles.media_id = $row->media_id AND overlays.game_id = {$newGameId} AND overlay_tiles.overlay_id = overlays.overlay_id";
            Module::query($query);

        }

        //NOTE: substr removes <?xml version="1.0" ? //> from the beginning of the text
        $query = "SELECT node_id FROM npc_conversations WHERE game_id = {$newGameId}";
        $result = Module::query($query);
        while($result && ($npcConvo = mysql_fetch_object($result))) {
            $query = "SELECT node_id, text FROM nodes WHERE node_id = {$npcConvo->node_id}";
            $resultNode = Module::query($query);
            if($result && ($node = mysql_fetch_object($resultNode))){
                $inputString = $node->text;
                $output = Games::replaceXMLIds($inputString, $newNpcIds, $newNodeIds, $newItemIds, $newAugBubbleIds, $newWebPageIds, $newMediaIds);
                if($output){
                    $output = substr($output,22);
                    $updateQuery = "UPDATE nodes SET text = '".addslashes($output)."' WHERE node_id = {$node->node_id} AND game_id = {$newGameId}";
                    Module::query($updateQuery);
                }
            }
        }

        $query = "SELECT * FROM npcs WHERE game_id = {$newGameId}";
        $result = Module::query($query);
        while($result && ($row = mysql_fetch_object($result))) {
            if($row->text){
                $inputString = $row->text;
                $output = Games::replaceXMLIds($inputString, $newNpcIds, $newNodeIds, $newItemIds, $newAugBubbleIds, $newWebPageIds, $newMediaIds);
                if($output){
                    $output = substr($output,22);
                    $updateQuery = "UPDATE npcs SET text = '".addslashes($output)."' WHERE npc_id = {$row->npc_id} AND game_id = {$newGameId}";
                    Module::query($updateQuery);
                }
            }
            if($row->closing){
                $inputString = $row->closing;
                $output = Games::replaceXMLIds($inputString, $newNpcIds, $newNodeIds, $newItemIds, $newAugBubbleIds, $newWebPageIds, $newMediaIds);
                if($output){
                    $output = substr($output,22);
                    $updateQuery = "UPDATE npcs SET closing = '".addslashes($output)."' WHERE npc_id = {$row->npc_id} AND game_id = {$newGameId}";
                    Module::query($updateQuery);
                }
            }
        }

        return new returnData(0, $newGameId, NULL);
    }

    static function replaceXMLIds($inputString, $newNpcIds, $newNodeIds, $newItemIds, $newAugBubbleIds, $newWebPageIds, $newMediaIds)
    {
        $kTagExitToPlaque = "exitToPlaque";
        $kTagExitToWebPage = "exitToWebPage";
        $kTagExitToCharacter = "exitToCharacter";
        $kTagExitToPanoramic = "exitToPanoramic";
        $kTagExitToItem = "exitToItem";
        $kTagVideo = "video";
        $kTagId = "id";
        $kTagPanoramic = "panoramic";
        $kTagWebpage = "webpage";
        $kTagPlaque = "plaque";
        $kTagItem = "item";
        $kTagMedia = "mediaId";

        //& sign will break xml parser, so this is necessary
        $inputString = str_replace("&", "&#x26;", $inputString);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($inputString);
        libxml_clear_errors();
        libxml_use_internal_errors(false);
        if($xml){

            foreach($xml->attributes() as $attributeTitle => $attributeValue)
            { 
                if(strcmp($attributeTitle, $kTagExitToWebPage) == 0)
                    $xml[$attributeTitle] = $newWebPageIds[intval($attributeValue)];
                else if(strcmp($attributeTitle, $kTagExitToPanoramic) == 0)
                    $xml[$attributeTitle] = $newAugBubbleIds[intval($attributeValue)];
                else if(strcmp($attributeTitle, $kTagMedia) == 0)
                    $xml[$attributeTitle] = $newMediaIds[intval($attributeValue)];
                else if(strcmp($attributeTitle, $kTagExitToPlaque) == 0)
                    $xml[$attributeTitle] = $newNodeIds[intval($attributeValue)];
                else if(strcmp($attributeTitle, $kTagExitToCharacter) == 0)
                    $xml[$attributeTitle] = $newNpcIds[intval($attributeValue)];
                else if(strcmp($attributeTitle, $kTagExitToItem) == 0)
                    $xml[$attributeTitle] = $newItemIds[intval($attributeValue)];
            }

            foreach($xml->children() as $child)
            {
                foreach($child->attributes() as $attributeTitle => $attributeValue)
                { 
                    if(strcmp($attributeTitle, $kTagExitToWebPage) == 0)
                        $child[$attributeTitle] = $newWebPageIds[intval($attributeValue)];
                    else if(strcmp($attributeTitle, $kTagExitToPanoramic) == 0)
                        $child[$attributeTitle] = $newAugBubbleIds[intval($attributeValue)];
                    else if(strcmp($attributeTitle, $kTagMedia) == 0)
                        $child[$attributeTitle] = $newMediaIds[intval($attributeValue)];
                    else if(strcmp($child->getName(), $kTagVideo) == 0 && strcmp($attributeTitle, $kTagId) == 0)
                        $child[$attributeTitle] = $newMediaIds[intval($attributeValue)];
                    else if(strcmp($child->getName(), $kTagPanoramic) == 0 && strcmp($attributeTitle, $kTagId) == 0)
                        $child[$attributeTitle] = $newAugBubbleIds[intval($attributeValue)];
                    else if(strcmp($child->getName(), $kTagWebpage) == 0 && strcmp($attributeTitle, $kTagId) == 0)
                        $child[$attributeTitle] = $newWebPageIds[intval($attributeValue)];
                    else if(strcmp($attributeTitle, $kTagExitToPlaque) == 0)
                        $child[$attributeTitle] = $newNodeIds[intval($attributeValue)];
                    else if(strcmp($attributeTitle, $kTagExitToCharacter) == 0)
                        $child[$attributeTitle] = $newNpcIds[intval($attributeValue)];
                    else if(strcmp($attributeTitle, $kTagExitToItem) == 0)
                        $child[$attributeTitle] = $newItemIds[intval($attributeValue)];
                    else if(strcmp($child->getName(), $kTagPlaque) == 0 && strcmp($attributeTitle, $kTagId) == 0)
                        $child[$attributeTitle] = $newNodeIds[intval($attributeValue)];
                    else if(strcmp($child->getName(), $kTagItem) == 0 && strcmp($attributeTitle, $kTagId) == 0)
                        $child[$attributeTitle] = $newItemIds[intval($attributeValue)];
                }
            }
            $output = $xml->asXML();
            $output = str_replace("&#x2019;", "'", $output);
            $output = str_replace("&amp;", "&", $output);
            $output = str_replace("&#x2014;", "-", $output);
            $output = str_replace("&#x201C;", "\"", $output);
            $output = str_replace("&#x201D;", "\"", $output);
            $output = str_replace("&#xB0;", "°", $output);
            $output = str_replace("&#xAE;", "®", $output);
            $output = str_replace("&#x2122;", "™", $output);
            $output = str_replace("&#xA9;", "©", $output);
            return $output;
        }
        return false;
    }

    function addNoteTagToGame($gameId, $tag)
    {
        $query = "INSERT INTO game_tags (game_id, tag) VALUES ('{$gameId}', '{$tag}')";
        $rs = Module::query($query);
        if (mysql_error())  return new returnData(3, NULL, 'SQL error');
        return new returnData(0);
    }

    public static function getDetailedGameInfo($gameId)
    {
        $query = "SELECT games.*, pcm.name as pc_media_name, pcm.file_path as pc_media_url, m.name as media_name, m.file_path as media_url, im.name as icon_name, im.file_path as icon_url FROM games LEFT JOIN media as m ON games.media_id = m.media_id LEFT JOIN media as im ON games.icon_media_id = im.media_id LEFT JOIN media as pcm on games.pc_media_id = pcm.media_id WHERE games.game_id = '{$gameId}'";

        $result = Module::query($query);
        $game = mysql_fetch_object($result);
        if(!$game) return "Invalid Game Id";

        if($game->media_url) $game->media_url = Config::gamedataWWWPath . '/' . $game->media_url;
        if($game->icon_url) $game->icon_url = Config::gamedataWWWPath . '/' . $game->icon_url;

        $query = "SELECT editors.name FROM game_editors JOIN editors ON editors.editor_id = game_editors.editor_id WHERE game_editors.game_id = '{$gameId}'";
        $result = Module::query($query);
        $auth = array();

        while($a = mysql_fetch_object($result))
            $auth[] = $a;

        $game->authors = $auth;

        return $game;
    }
}
?>
