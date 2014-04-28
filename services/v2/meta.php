<?php
require_once("dbconnection.php");

class meta extends dbconnection
{
    /**
      Gets array of JSON encoded 'web backpacks', containing player information relating to items, attributes, and notes gained throughout a game. For an example of its use, see 'getBackPacksFromArray.html'.
      @param: bpReqObj- a JSON encoded object with two fields:
      gameId- An integer representing the game_id of the game information desired.
      playerArray- Either a JSON encoded array of integer player_ids of all the players whose information is desired, a single integer if only one player's information is desired, or nothing if all player information for an entire game is desired.
      @returns: On success, returns JSON encoded game object with a parameter containing an array of player objects with various parameters describing a player's information.
      If gameId is empty, returns 'Error- Empty Game' and aborts the function.
      If game with gameId does not exist, returns 'Error- Invalid Game Id' and aborts the function.
      If playerArray is anything other than the specified options, returns 'Error- Invalid Player Array' and aborts the function.
     **/
    public static function getPlayerBackpacksFromArray($bpReqObj)
    {
        //Module::serverErrorLog('Get Backpacks From Arrays Called: '.date_format(date_create(), 'H:i:s:u')."\n".$bpReqObj);
        $gameId        = $bpReqObj['gameId'];
        $playerArray   = $bpReqObj['playerArray'];
        $getItems      = (isset($bpReqObj['items'])      ? $bpReqObj['items']      : true); //Default true
        $getAttributes = (isset($bpReqObj['attributes']) ? $bpReqObj['attributes'] : true); //Default true
        $getNotes      = (isset($bpReqObj['notes'])      ? $bpReqObj['notes']      : true); //Default true

        if(is_numeric($gameId)) $gameId = intval($gameId);
        else return new returnData(1, "Error- Empty Game ".$gameId);

        if(($game = Games::getDetailedGameInfo($gameId)) == "Invalid Game Id")
            return new returnData(1, "Error- Empty Game ".$gameId);

        if(is_null($playerArray))
        {
            $game->backpacks = Players::getAllPlayerDataBP($gameId, $getItems, $getAttributes, $getNotes);
            return new returnData(0,$game);
        }
        else if(is_array($playerArray))
        {
            $game->backpacks =  Players::getPlayerArrayDataBP($gameId, $playerArray, $getItems, $getAttributes, $getNotes);
            return new returnData(0,$game);
        }
        else if(is_numeric($playerArray))
        {
            $game->backpacks = Players::getSinglePlayerDataBP($gameId, intval($playerArray), false, $getItems, $getAttributes, $getNotes);
            return new returnData(0,$game,true);
        }
        else return new returnData(1, "Error- Invalid Player Array");
    }

    private static function getAllPlayerDataBP($gameId, $getItems = true, $getAttributes = true, $getNotes = true)
    {
        $result = Module::query("SELECT DISTINCT player_id FROM player_log WHERE game_id='{$gameId}' AND player_id != 0");
        $players = array();
        while($player = mysql_fetch_object($result))
            $players[] = $player->player_id;
        return Players::getPlayerArrayDataBP($gameId, $players, $getItems, $getAttributes, $getNotes);
    }

    private static function getPlayerArrayDataBP($gameId, $playerArray, $getItems = true, $getAttributes = true, $getNotes = true)
    {
        Module::serverErrorLog('Get Player Array Data Called: '.date_format(date_create(), 'H:i:s:u')."\n".json_encode($playerArray));

        //preload data into memory for quick re-use
        $mediaA = Media::getMedia($gameId)->data;
        $mediaMap = array();
        $numMedia = count($mediaA); 
        for($i = 0; $i < $numMedia; $i++)
            $mediaMap[$mediaA[$i]->media_id] = $mediaA[$i];
        if($getItems)
        {
            $itemsMap = array();
            $itemsA = Module::queryArray("SELECT * FROM items WHERE game_id = '{$gameId}' AND (is_attribute = '0' OR is_attribute = '')");
            $numItems = count($itemsA);
            for($i = 0; $i < $numItems; $i++)
            {
                $itemsA[$i]->media_url       = $mediaMap[$itemsA[$i]->media_id]->url;
                $itemsA[$i]->media_thumb_url = $mediaMap[$itemsA[$i]->media_id]->thumb_url;
                $itemsA[$i]->icon_url        = $mediaMap[$itemsA[$i]->icon_media_id]->url;
                $itemsA[$i]->icon_thumb_url  = $mediaMap[$itemsA[$i]->icon_media_id]->thumb_url;
                $itemsMap[$itemsA[$i]->item_id] = $itemsA[$i];
            }
        }
        if($getAttributes)
        {
            $attributesMap = array();
            $attributesA = Module::queryArray("SELECT * FROM items WHERE game_id = '{$gameId}' AND is_attribute = '1'");
            $numAttributes = count($attributesA);
            for($i = 0; $i < $numAttributes; $i++)
            {
                $attributesA[$i]->media_url       = $mediaMap[$attributesA[$i]->media_id]->url;
                $attributesA[$i]->media_thumb_url = $mediaMap[$attributesA[$i]->media_id]->thumb_url;
                $attributesA[$i]->icon_url        = $mediaMap[$attributesA[$i]->icon_media_id]->url;
                $attributesA[$i]->icon_thumb_url  = $mediaMap[$attributesA[$i]->icon_media_id]->thumb_url;
                $attributesMap[$attributesA[$i]->media_id] = $attributesA[$i];
            }
        }
        if($getNotes)
        {
            $gameTagsMap = array();
            $gameTagsA = Module::queryArray("SELECT * FROM game_tags WHERE game_id = '{$gameId}'");
            $numGameTags = count($gameTagsA);
            for($i = 0; $i < $numGameTags; $i++)
                $gameTagsMap[$gameTagsA[$i]->tag_id] = $gameTagsA[$i];
        }

        $backpacks = array();
        $numPlayers = count($playerArray);
        for($i = 0; $i < $numPlayers; $i++)
        {
            $backpack = new stdClass();

            $backpack->owner = Module::queryObject("SELECT player_id, user_name, display_name, group_name, media_id FROM players WHERE player_id = '{$playerArray[$i]}'");
            if(!$backpack->owner) continue;
            $playerPic = Media::getMediaObject('player', $backpack->owner->media_id)->data;
            $backpack->owner->player_pic_url       = $playerPic->url;
            $backpack->owner->player_pic_thumb_url = $playerPic->thumb_url;

            $media->thumb_file_path = substr($media->file_path,0,strrpos($media->file_path,'.')).'_128'.substr($media->file_path,strrpos($media->file_path,'.'));
            $media->url_path = Config::gamedataWWWPath . "/";

            if($getItems || $getAttributes)
            {
                if($getItems)      $backpack->items      = array();
                if($getAttributes) $backpack->attributes = array();
                $playerItemData = Module::queryArray("SELECT item_id, qty FROM player_items WHERE game_id = '{$gameId}' AND player_id = '{$playerArray[$i]}'");
                $numItems = count($playerItemData);

                for($j = 0; $j < $numItems; $j++)
                {
                    if($getItems && isset($itemsMap[$playerItemData[$j]->item_id]))
                    {
                        $item = clone $itemsMap[$playerItemData[$j]->item_id];
                        $item->qty = $playerItemData[$j]->qty;
                        $backpack->items[] = $item;
                    }
                    else if($getAttributes && isset($attributesMap[$playerItemData[$j]->item_id]))
                    {
                        $attribute = clone $attributesMap[$playerItemData[$j]->item_id];
                        $attribute->qty = $playerItemData[$j]->qty;
                        $backpack->attributes[] = $attribute;
                    }
                }
            }

            if($getNotes)
            {
                $rawNotes = Module::query("SELECT * FROM notes WHERE owner_id = '{$playerArray[$i]}' AND game_id = '{$gameId}' AND parent_note_id = 0 ORDER BY sort_index ASC");
                $backpack->notes = array();
                while($note = mysql_fetch_object($rawNotes))
                {
                    $note->username = $backpack->owner->user_name;
                    if($backpack->owner->display_name && $backpack->owner->display_name != "") $note->username = $backpack->owner->display_name;
                    $rawContent = Module::query("SELECT * FROM note_content WHERE note_id = '{$note->note_id}'");
                    $note->contents = array();
                    while($content = mysql_fetch_object($rawContent))
                    {
                        $content->media_url       = $mediaMap[$content->media_id]->url;
                        $content->media_thumb_url = $mediaMap[$content->media_id]->thumb_url;
                        $note->contents[] = $content;
                    }
                    $note->likes = Notes::getNoteLikes($note->note_id);
                    $note->player_liked = Notes::playerLiked($playerId, $note->note_id);

                    $result = Module::query("SELECT * FROM note_tags WHERE note_id = '{$note->note_id}'");
                    $note->tags = array();
                    while($tag = mysql_fetch_object($result))	
                        $note->tags[] = $gameTagsMap[$tag->tag_id];

                    $note->dropped = 0;
                    if($location = Notes::noteDropped($note->note_id, $note->game_id))
                        $note->dropped = 1;
                    $note->lat = $location ? $location->latitude  : 0;
                    $note->lon = $location ? $location->longitude : 0;

                    $rawComments = Module::query("SELECT * FROM notes WHERE game_id = '{$gameId}' AND parent_note_id = {$note->note_id} ORDER BY sort_index ASC");
                    $note->comments = array();
                    while($comment = mysql_fetch_object($rawComments))
                    {
                        $player = Module::queryObject("SELECT user_name, display_name FROM players WHERE player_id = '{$comment->owner_id}' LIMIT 1");
                        $comment->username = $player->user_name;
                        $comment->displayname = $player->display_name;
                        $rawContent = Module::query("SELECT * FROM note_content WHERE note_id = '{$comment->note_id}'");
                        $comment->contents = array();
                        while($content = mysql_fetch_object($rawContent))
                        {
                            $content->media_url       = $mediaMap[$content->media_id]->url;
                            $content->media_thumb_url = $mediaMap[$content->media_id]->thumb_url;
                            $comment->contents[] = $content;
                        }
                        $comment->likes = Notes::getNoteLikes($comment->note_id);
                        $comment->player_liked = Notes::playerLiked($playerId, $comment->note_id);
                        $note->comments[] = $comment;
                    }

                    $backpack->notes[] = $note;
                }
            }

            $backpacks[] = $backpack;
        }
        return $backpacks;
    }

    /*
     * Gets information for web backpack for any player/game pair
     */
    private static function getSinglePlayerDataBP($gameId, $playerId, $individual=false, $getItems = true, $getAttributes = true, $getNotes = true)
    {
        //Module::serverErrorLog('Single Player Start: '.date_format(date_create(), 'H:i:s:u'));
        $backpack = new stdClass();

        //Get owner information
        $query = "SELECT user_name, display_name, group_name, media_id FROM players WHERE player_id = '{$playerId}'";
        $result = Module::query($query);
        $name = mysql_fetch_object($result);
        if(!$name) return "Invalid Player Id";
        $backpack->owner = new stdClass();
        $backpack->owner->user_name = $name->user_name;
        $backpack->owner->display_name = $name->display_name;
        $backpack->owner->group_name = $name->group_name;
        $backpack->owner->player_id = $playerId;
        $playerpic = Media::getMediaObject('player', $name->media_id)->data;
        if($playerpic)
        {
            $backpack->owner->player_pic_url       = $playerpic->url_path.$playerpic->file_path;
            $backpack->owner->player_pic_thumb_url = $playerpic->url_path.$playerpic->thumb_file_path;
        }
        else
        {
            $backpack->owner->player_pic_url       = null;
            $backpack->owner->player_pic_thumb_url = null;
        }

        /* ATTRIBUTES */
        //Module::serverErrorLog('Attributes    Start: '.date_format(date_create(), 'H:i:s:u'));
        if($getAttributes) $backpack->attributes = Items::getDetailedPlayerAttributes($playerId, $gameId);

        /* OTHER ITEMS */
        //Module::serverErrorLog('Items         Start: '.date_format(date_create(), 'H:i:s:u'));
        if($getItems) $backpack->items = Items::getDetailedPlayerItems($playerId, $gameId);

        /* NOTES */
        //Module::serverErrorLog('Notes         Start: '.date_format(date_create(), 'H:i:s:u'));
        if($getNotes) $backpack->notes = Notes::getDetailedPlayerNotes($playerId, $gameId, $individual);

        return $backpack;
    }

    /**
     * Create new accounts from an array of player objects
     * @param array $playerArrays JSON Object containing userNames and passwords as arrays {"userNames":["joey","mary"],"passwords":["fds2cd3","d3g5gg"]}
     * @return returnData
     * @returns a returnData object containing player objects with their assigned player ids
     * @see returnData
     */
    function createPlayerAccountsFromArrays($playerArrays)
    {		
        $usernameArray  = $playerArrays['userNames'];
        $passwordArray  = $playerArrays['passwords'];
        $firstnameArray = $playerArrays['firstNames'];
        $lastnameArray  = $playerArrays['lastNames'];
        $emailArray     = $playerArrays['emails'];

        if(count($usernameArray) == 0 || $usernameArray[0] == '' || count($usernameArray) != count($passwordArray))
            return new returnData(1, "", "Bad JSON or userNames and passwords arrays have different sizes");

        //Search for matching user names
        $query = "SELECT user_name FROM players WHERE ";
        for($i = 0; $i < count($usernameArray); $i++)
            $query = $query."user_name = '{$usernameArray[$i]}' OR ";
        $query = substr($query, 0, strlen($query)-4).";";

        $result = Module::query($query);

        $reterr = "username ";
        while($un = mysql_fetch_object($result))
            $reterr = $reterr.$un->user_name.", ";	
        if($reterr != "username ")
        {
            $reterr = substr($reterr, 0, strlen($query)-2)." already in database.";
            return new returnData(1, $reterr);
        }

        //Run the insert
        $query = "INSERT INTO players (user_name, password, first_name, last_name, email, created) VALUES ";
        for($i = 0; $i < count($usernameArray); $i++)
            $query = $query."('{$usernameArray[$i]}', MD5('$passwordArray[$i]'), '{$firstnameArray[$i]}','{$lastnameArray[$i]}','{$emailArray[$i]}', NOW()), ";
        $query = substr($query, 0, strlen($query)-2).";";
        $result = Module::query($query);
        if (mysql_error()) 	return new returnData(1, "","Error Inserting Records");


        //Generate the result
        $query = "SELECT player_id,user_name FROM players WHERE ";
        for($i = 0; $i < count($usernameArray); $i++)
            $query = $query."user_name = '{$usernameArray[$i]}' OR ";
        $query = substr($query, 0, strlen($query)-4).";";
        $result = Module::query($query);
        if (mysql_error()) 	return new returnData(1, "","Error Verifying Records");


        return new returnData(0,$result);
    }

    /**
     * Create new accounts from an array of player objects
     * @param array $playerArray Array of JSON formated player objects [{"username":"joey","password":"h5f3ad3","firstName":"joey","lastName":"smith","email":"joey@gmail.com"}]
     * @return returnData
     * @returns a returnData object containing player objects with their assigned player ids
     * @see returnData
     */
    function createPlayerAccountsFromObjectArray($playerArray)
    {
        //return new returnData($playerArray);
        if(count($playerArray) == 0)
            return new returnData(1, "Bad JSON or Empty Array");

        //Search for matching user names
        $query = "SELECT user_name FROM players WHERE ";
        for($i = 0; $i < count($playerArray); $i++)
            $query = $query."user_name = '{$playerArray[$i]["username"]}' OR ";
        $query = substr($query, 0, strlen($query)-4).";";
        //$query of form "SELECT user_name FROM players WHERE user_name = 'user1' OR user_name = 'user2' OR user_name = 'user3';"
        $result = Module::query($query);

        //Check if any duplicates exist
        $reterr = "Duplicate username(s): ";
        while($un = mysql_fetch_object($result))
            $reterr = $reterr.$un->user_name.", ";
        if($reterr != "Duplicate username(s): ")
        {
            $reterr = substr($reterr, 0, strlen($reterr)-2)." already in database.";
            return new returnData(4, "",$reterr);
        }

        //Run the insert
        $query = "INSERT INTO players (user_name, password, first_name, last_name, email, created) VALUES ";
        for($i = 0; $i < count($playerArray); $i++)
            $query = $query."('{$playerArray[$i]["username"]}', MD5('{$playerArray[$i]["password"]}'), '{$playerArray[$i]["firstName"]}','{$playerArray[$i]["lastName"]}','{$playerArray[$i]["email"]}', NOW()), ";
        $query = substr($query, 0, strlen($query)-2).";";
        $result = Module::query($query);
        if (mysql_error()) 	return new returnData(1, "","Error Inserting Records");

        //Generate the result
        $query = "SELECT player_id,user_name FROM players WHERE ";
        for($i = 0; $i < count($playerArray); $i++)
            $query = $query."user_name = '{$playerArray[$i]["username"]}' OR ";
        $query = substr($query, 0, strlen($query)-4).";";
        $result = Module::query($query);
        if (mysql_error()) 	return new returnData(1, "","Error Verifying Records");

        return new returnData(0,$result);
    }

    function getPlayerIdsForGroup($groupReqObj)
    {
        if(is_string($groupReqObj))
        {
            //Treat as string
            $query = "SELECT player_id FROM players WHERE group_name = '$groupReqObj';";

            $playersSQLObj = Module::query($query);
            $playersArray = array();
            while($playerId = mysql_fetch_object($playersSQLObj))
                $playersArray[] = $playerId->player_id;
            return new returnData(0,$playersArray);
        }
        else if($groupReqObj['group_name'])
        {
            $query = "SELECT player_id FROM players WHERE group_name = '{$groupReqObj['group_name']}';";

            $playersSQLObj = Module::query($query);
            $playersArray = array();
            while($playerId = mysql_fetch_object($playersSQLObj))
                $playersArray[] = $playerId->player_id;
            return new returnData(0,$playersArray);
        }
        else
            return new returnData(1,$groupReqObj,"Expecting JSON encoded string of form {'group_name':'theStringOfTheGroupYouAreLookingFor'}.");
    }

    public function getReadablePlayerLogsForGame($gameId, $seconds)
    {
        $logs = Module::queryArray("SELECT players.user_name, players.display_name, pl.timestamp, pl.event_type, pl.event_detail_1, pl.event_detail_2 FROM (SELECT * FROM player_log WHERE game_id = $gameId AND (timestamp BETWEEN NOW() - INTERVAL $seconds SECOND AND NOW()) AND event_type != 'MOVE') AS pl LEFT JOIN players ON pl.player_id = players.player_id");
        for($i = 0; $i < count($logs); $i++)
        {
            switch($logs[$i]->event_type)
            {
                case 'LOGIN': //ignore
                    break;
                case 'MOVE': //ignore
                    break;
                case 'PICKUP_ITEM':
                    $logs[$i]->event_detail_1 = Module::queryObject("SELECT name FROM items WHERE game_id = $gameId AND item_id = ".$logs[$i]->event_detail_1)->name;
                    break;
                case 'DROP_ITEM':
                    $logs[$i]->event_detail_1 = Module::queryObject("SELECT name FROM items WHERE game_id = $gameId AND item_id = ".$logs[$i]->event_detail_1)->name;
                    break;
                case 'DROP_NOTE':
                    $logs[$i]->event_detail_1 = Module::queryObject("SELECT title FROM notes WHERE game_id = $gameId AND note_id = ".$logs[$i]->event_detail_1)->title;
                    break;
                case 'DESTROY_ITEM':
                    $logs[$i]->event_detail_1 = Module::queryObject("SELECT name FROM items WHERE game_id = $gameId AND item_id = ".$logs[$i]->event_detail_1)->name;
                    break;
                case 'VIEW_ITEM':
                    $logs[$i]->event_detail_1 = Module::queryObject("SELECT name FROM items WHERE game_id = $gameId AND item_id = ".$logs[$i]->event_detail_1)->name;
                    break;
                case 'VIEW_NODE':
                    $logs[$i]->event_detail_1 = Module::queryObject("SELECT title FROM nodes WHERE game_id = $gameId AND node_id = ".$logs[$i]->event_detail_1)->name;
                    break;
                case 'VIEW_NPC':
                    $logs[$i]->event_detail_1 = Module::queryObject("SELECT name FROM npcs WHERE game_id = $gameId AND npc_id = ".$logs[$i]->event_detail_1)->name;
                    break;
                case 'VIEW_WEBPAGE':
                    $logs[$i]->event_detail_1 = Module::queryObject("SELECT name FROM web_pages WHERE game_id = $gameId AND web_page_id = ".$logs[$i]->event_detail_1)->name;
                    break;
                case 'VIEW_AUGBUBBLE':
                    $logs[$i]->event_detail_1 = Module::queryObject("SELECT name FROM aug_bubbles WHERE game_id = $gameId AND aug_bubble_id = ".$logs[$i]->event_detail_1)->name;
                    break;
                case 'VIEW_MAP': //no event details
                    break;
                case 'VIEW_QUESTS': //no event details
                    break;
                case 'VIEW_INVENTORY': //no event details
                    break;
                case 'ENTER_QRCODE': //no event details
                    break;
                case 'UPLOAD_MEDIA_ITEM': //no event details
                    break;
                case 'UPLOAD_MEDIA_ITEM_IMAGE': //no event details
                    break;
                case 'UPLOAD_MEDIA_ITEM_AUDIO': //no event details
                    break;
                case 'UPLOAD_MEDIA_ITEM_VIDEO': //no event details
                    break;
                case 'RECEIVE_WEBHOOK': //no event details
                    break;
                case 'SEND_WEBHOOK': //no event details
                    break;
                case 'COMPLETE_QUEST':
                    $logs[$i]->event_detail_1 = Module::queryObject("SELECT name FROM quests WHERE game_id = $gameId AND quest_id = ".$logs[$i]->event_detail_1)->name;
                    break;
                case 'GET_NOTE':
                    $logs[$i]->event_detail_1 = Module::queryObject("SELECT title FROM notes WHERE game_id = $gameId AND note_id = ".$logs[$i]->event_detail_1)->title;
                    break;
                case 'GIVE_NOTE_LIKE':
                    $logs[$i]->event_detail_1 = Module::queryObject("SELECT title FROM notes WHERE game_id = $gameId AND note_id = ".$logs[$i]->event_detail_1)->title;
                    break;
                case 'GET_NOTE_LIKE':
                    $logs[$i]->event_detail_1 = Module::queryObject("SELECT title FROM notes WHERE game_id = $gameId AND note_id = ".$logs[$i]->event_detail_1)->title;
                    break;
                case 'GIVE_NOTE_COMMENT':
                    $logs[$i]->event_detail_1 = Module::queryObject("SELECT title FROM notes WHERE game_id = $gameId AND note_id = ".$logs[$i]->event_detail_1)->title;
                    break;
                case 'GET_NOTE_COMMENT':
                    $logs[$i]->event_detail_1 = Module::queryObject("SELECT title FROM notes WHERE game_id = $gameId AND note_id = ".$logs[$i]->event_detail_1)->title;
                    break;
            }
        }
        return new returnData(0, $logs);
    }

    public static function getDetailedGameInfo($gameId)
    {
        $query = "SELECT games.*, pcm.name as pc_media_name, pcm.file_path as pc_media_url, m.name as media_name, m.file_path as media_url, im.name as icon_name, im.file_path as icon_url FROM games LEFT JOIN media as m ON games.media_id = m.media_id LEFT JOIN media as im ON games.icon_media_id = im.media_id LEFT JOIN media as pcm on games.pc_media_id = pcm.media_id WHERE games.game_id = '{$gameId}'";

        $result = dbconnection::query($query);
        $game = mysql_fetch_object($result);
        if(!$game) return "Invalid Game Id";

        if($game->media_url) $game->media_url = Config::gamedataWWWPath . '/' . $game->media_url;
        if($game->icon_url) $game->icon_url = Config::gamedataWWWPath . '/' . $game->icon_url;

        $query = "SELECT editors.name FROM game_editors JOIN editors ON editors.editor_id = game_editors.editor_id WHERE game_editors.game_id = '{$gameId}'";
        $result = dbconnection::query($query);
        $auth = array();

        while($a = mysql_fetch_object($result))
            $auth[] = $a;

        $game->authors = $auth;

        return $game;
    }

    public static function getDetailedPlayerAttributes($playerId, $gameId)
    {
        /* ATTRIBUTES */
        $query = "SELECT DISTINCT i.item_id, i.name, i.description, i.max_qty_in_inventory, i.weight, i.type, i.url, pi.qty, m.file_path as media_url, m.game_id as media_game_id, im.file_path as icon_url, im.game_id as icon_game_id FROM (SELECT * FROM player_items WHERE game_id = {$gameId} AND player_id = {$playerId}) as pi LEFT JOIN (SELECT * FROM items WHERE game_id = {$gameId}) as i ON pi.item_id = i.item_id LEFT JOIN media as m ON i.media_id = m.media_id LEFT JOIN media as im ON i.icon_media_id = im.media_id WHERE i.type = 'ATTRIB' GROUP BY i.item_id";

        $result = Module::query($query);
        $contents = array();
        while($content = mysql_fetch_object($result)) {
            if($content->media_url)
            {
                $content->media_url       = Config::gamedataWWWPath . '/' . $content->media_url;
                $content->media_thumb_url = substr($content->media_url,0,strrpos($content->media_url,'.')).'_128'.substr($content->media_url,strrpos($content->media_url,'.'));
            }
            if($content->icon_url)
            {
                $content->icon_url = Config::gamedataWWWPath . '/' . $content->icon_url;
                $content->icon_thumb_url = substr($content-icon_url,0,strrpos($content-icon_url,'.')).'_128'.substr($content-icon_url,strrpos($content-icon_url,'.'));
            }
            $content->tags = Items::getItemTags($content->item_id)->data;
            $contents[] = $content;
        }
        return $contents;
    }

    public static function getDetailedPlayerItems($playerId, $gameId)
    {
        /* OTHER ITEMS */
        $query = "SELECT DISTINCT i.item_id, i.name, i.description, i.max_qty_in_inventory, i.weight, i.type, i.url, pi.qty, m.file_path as media_url, m.game_id as media_game_id, im.file_path as icon_url, im.game_id as icon_game_id FROM (SELECT * FROM player_items WHERE game_id={$gameId} AND player_id = {$playerId}) as pi LEFT JOIN (SELECT * FROM items WHERE game_id = {$gameId}) as i ON pi.item_id = i.item_id LEFT JOIN media as m ON i.media_id = m.media_id LEFT JOIN media as im ON i.icon_media_id = im.media_id WHERE i.type != 'ATTRIB' GROUP BY i.item_id";

        $result = Module::query($query);
        $contents = array();
        while($content = mysql_fetch_object($result)){
            if($content->media_url)
            {
                $content->media_url = Config::gamedataWWWPath . '/' . $content->media_url;
                $content->media_thumb_url = substr($content->media_url,0,strrpos($content->media_url,'.')).'_128'.substr($content->media_url,strrpos($content->media_url,'.'));
            }
            if($content->icon_url)
            {
                $content->icon_url = Config::gamedataWWWPath . '/' . $content->icon_url;
                $content->icon_thumb_url = substr($content-icon_url,0,strrpos($content-icon_url,'.')).'_128'.substr($content-icon_url,strrpos($content-icon_url,'.'));
            }
            $content->tags = Items::getItemTags($content->item_id)->data;
            $contents[] = $content;
        }

        return $contents;
    }

    public function searchGameForErrors($gid){

        //     $query = "SELECT name FROM games WHERE game_id = {$gid}";
        //     $name = Module::query($query);

        //return "\nLooking for problems in {$name}\nNote: This check does not quarantee there are no errors in your game, but only checks for a few common mistakes.\n";	

        $query = "SELECT * FROM requirements WHERE game_id = {$gid}";
        $resultMain = Module::query($query);
        while($resultMain && $row = mysql_fetch_object($resultMain)){ 
            if(!$row->requirement_detail_1){
                if($row->requirement == "PLAYER_HAS_ITEM" || $row->requirement == "PLAYER_VIEWED_ITEM"){
                    if(!($row->content_type == "Node")) return "\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which item the player needs to have/have viewed.\n";	
                    else{
                        $scriptTitle = Module::query("SELECT title FROM nodes WHERE game_id = {$gid} AND node_id = {$row->content_id}");
                        return "\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that doesn't specify which item the player needs to have/have viewed.\n";	
                    }
                    if(!$row->requirement_detail_2 && $row->requirement == "Player_HAS_ITEM"){
                        if(!($row->content_type == "Node")) return "\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that requires the player has a certain item, but not the quantity of that item needed.\n";	
                        else{
                            $scriptTitle = Module::query("SELECT title FROM nodes WHERE game_id = {$gid} AND node_id = {$row->content_id}");
                            return "\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that requires that the player has a certain item, but not the quantity of that item neeeded.\n";	
                        }
                    }
                } 
                else if($row->requirement == "PLAYER_VIEWED_NODE"){
                    if(!($row->content_type == "Node")) return "\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which node the player needed to view in order to satisfy that requirement.\n";	
                    else{
                        $scriptTitle = Module::query("SELECT title FROM nodes WHERE game_id = {$gid} AND node_id = {$row->content_id}");
                        return "\nThere is a requirement of a {$row->content_type} with the title of  {$scriptTitle} that doesn't specify which node the player needed to view in order to satisfy that requirement.\n";	
                    }
                }
                else if($row->requirement == "PLAYER_VIEWED_NPC"){
                    if(!($row->content_type == "Node")) return "\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which character the player needed to view in order to satisfy that requirement.\n";	
                    else{
                        $scriptTitle = Module::query("SELECT title FROM nodes WHERE game_id = {$gid} AND node_id = {$row->content_id}");
                        return "\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that doesn't specify which character the player needed to view in order to satisfy that requirement.\n";	
                    }
                }
                else if($row->requirement == "PLAYER_VIEWED_WEBPAGE"){
                    if(!($row->content_type == "Node")) return "\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which web page the player needed to view in order to satisfy that requirement.\n";	
                    else{
                        $scriptTitle = Module::query("SELECT title FROM nodes WHERE game_id = {$gid} AND node_id = {$row->content_id}");
                        return "\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that doesn't specify which web page the player needed to view in order to satisfy that requirement.\n";	
                    }

                }
                else if($row->requirement == "PLAYER_VIEWED_AUGBUBBLE"){
                    if(!($row->content_type == "Node")) return "\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which panoramic the player needed to view in order to satisfy that requirement.\n";
                    else{
                        $scriptTitle = Module::query("SELECT title FROM nodes WHERE game_id = {$gid} AND node_id = {$row->content_id}");
                        return "\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that doesn't specify which panoramic the player needed to view in order to satisfy that requirement.\n";
                    }
                }
                else if($row->requirement == "PLAYER_HAS_COMPLETED_QUEST"){
                    if(!($row->content_type == "Node")) return "\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which quest the player needed to complete in order to satisfy that requirement.\n";	
                    else{
                        $scriptTitle = Module::query("SELECT title FROM {$gid}_nodes WHERE node_id = {$row->content_id}");
                        return "\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that doesn't specify which quest the player needed to complete in order to satisfy that requirement.\n";	
                    }
                }
                else if($row->requirement == "PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK"){
                    if(!($row->content_type == "Node")) return "\nThere is a requirement of a {$row->content_type} with id: {$row->content_id} that doesn't specify which incoming web hook the player needed to receive in order to satisfy that requirement.\n";	
                    else{
                        $scriptTitle = Module::query("SELECT title FROM {$gid}_nodes WHERE node_id = {$row->content_id}");
                        return "\nThere is a requirement of a {$row->content_type} with the title of {$scriptTitle} that doesn't specify which incoming web hook the player needed to receive in order to satisfy that requirement.\n";	
                    }

                } 
            }
        }

        $query = "SELECT * FROM player_state_changes";
        $resultMain = Module::query($query);
        while($resultMain && $row = mysql_fetch_object($resultMain)){ 
            if($row->event_type == "VIEW_ITEM"){
                if(!$row->action_detail){
                    return "\nThere is an item of id: {$row->event_detail} that doesn't specify what item to give or take when viewed.\n";	
                }
                if(!$row->action_amount){
                    return "\nThere is an item of id: {$row->event_detail} that doesn't specify what quantity of an item to give or take when viewed.\n";	
                }
            }
            else if($row->event_type == "VIEW_NODE"){
                if(!$row->action_detail){
                    $scriptTitle = Module::query("SELECT title FROM nodes WHERE game_id = {$gid} AND node_id = {$row->event_detail}");
                    return "\nThere is a node with the title of {$scriptTitle} that doesn't specify what item to give or take when viewed.\n";	
                }
                if(!$row->action_amount){
                    $scriptTitle = Module::query("SELECT title FROM nodes WHERE game_id = {$gid} AND node_id = {$row->event_detail}");
                    return "\nThere is a node with the title of {$scriptTitle} that doesn't specify what quantity of an item to give or take when viewed.\n";	
                }
            }
            else if($row->event_type == "VIEW_NPC"){
                if(!$row->action_detail){
                    return "\nThere is a character of id: {$row->event_detail} that doesn't specify what item to give or take when viewed.\n";	
                }
                if(!$row->action_amount){
                    return "\nThere is a character of id: {$row->event_detail} that doesn't specify what quantity of an item to give or take when viewed.\n";	
                }
            } 
            else if($row->event_type == "VIEW_WEBPAGE"){
                if(!$row->action_detail){
                    return "\nThere is a web page of id: {$row->event_detail} that doesn't specify what item to give or take when viewed.\n";	
                }
                if(!$row->action_amount){
                    return "\nThere is a web page of id: {$row->event_detail} that doesn't specify what quantity of an item to give or take when viewed.\n";	
                }
            } 
            else if($row->event_type == "VIEW_AUGBUBBLE"){
                if(!$row->action_detail){
                    return "\nThere is a panoramic of id: {$row->event_detail} that doesn't specify what item to give or take when viewed.\n";	
                }
                if(!$row->action_amount){
                    return "\nThere is a panoramic of id: {$row->event_detail} that doesn't specify what quantity of an item to give or take when viewed.\n";	
                }
            } 
            else if($row->event_type == "RECEIVE_WEBHOOK"){
                if(!$row->action_detail){
                    return "\nThere is an web hook of id: {$row->event_detail} that doesn't specify what item to give or take when received.\n";	
                }
                if(!$row->action_amount){
                    return "\nThere is an web hook of id: {$row->event_detail} that doesn't specify what quantity of an item to give or take when received.\n";	
                }
            }
        }

        $query = "SELECT * FROM nodes WHERE game_id = {$gid}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)) {
            if($row->text){
                $inputString = $row->text;
                if((strspn($inputString,"<>") > 0) && ((substr_count($inputString, "<npc>") > 0) || (substr_count($inputString, "<pc>") > 0) || (substr_count($inputString, "<dialog>") > 0)) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0)){
                    @$output = simplexml_load_string($inputString);
                    if(!$output) return "\nThere is improperly formatted xml in the node with title:\n{$row->title}\nand text:\n{$row->text}\n";
                }
            }
        }

        $query = "SELECT * FROM npcs WHERE game_id = {$gid}";
        $result = Module::query($query);
        while($result && $row = mysql_fetch_object($result)) {
            if($row->text){
                $inputString = $row->text;
                if((strspn($inputString,"<>") > 0) && ((substr_count($inputString, "<npc>") > 0) || (substr_count($inputString, "<pc>") > 0) || (substr_count($inputString, "<dialog>") > 0)) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0)){
                    @$output = simplexml_load_string($inputString);
                    if(!$output) return "\nThere is improperly formatted xml in the npc with name:\n{$row->name}\nand greeting:\n{$row->text}\n";
                }
            }
            if($row->closing){
                $inputString = $row->closing;
                if((strspn($inputString,"<>") > 0) && ((substr_count($inputString, "<npc>") > 0) || (substr_count($inputString, "<pc>") > 0) || (substr_count($inputString, "<dialog>") > 0)) && !(substr_count($inputString,"<p>") > 0) && !(substr_count($inputString,"<b>") > 0) && !(substr_count($inputString,"<i>") > 0) && !(substr_count($inputString,"<img") > 0) && !(substr_count($inputString,"<table>") > 0)){
                    @$output = simplexml_load_string($inputString);
                    if(!$output) return "\nThere is improperly formatted xml in the npc with name:\n{$row->name}\nand closing:\n{$row->text}\n";
                }
            }
        }  
    }
}
?>
