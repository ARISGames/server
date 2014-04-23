<?php

require_once("dbconnection.php");
require_once("returnData.php");
require_once("users.php");
require_once("editors.php");
require_once("media.php");

class games extends dbconnection
{	
    //Takes in game JSON, all fields optional except user_id + key
    public static function createGameJSON($glob)
    {
	$data = file_get_contents("php://input");
        $glob = json_decode($data);
        return games::createGame($glob);
    }

    public static function createGame($pack)
    {
        if(!users::authenticateUser($pack->auth->user_id, $pack->auth->key, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $gameId = dbconnection::queryInsert(
            "INSERT INTO games (".
            ($pack->name                   ? "name,"                   : "").
            ($pack->description            ? "description,"            : "").
            ($pack->icon_media_id          ? "icon_media_id,"          : "").
            ($pack->media_id               ? "media_id,"               : "").
            ($pack->map_type               ? "map_type,"               : "").
            ($pack->latitude               ? "latitude,"               : "").
            ($pack->longitude              ? "longitude,"              : "").
            ($pack->zoom_level             ? "zoom_level,"             : "").
            ($pack->show_player_location   ? "show_player_location,"   : "").
            ($pack->full_quick_travel      ? "full_quick_travel,"      : "").
            ($pack->allow_note_comments    ? "allow_note_comments,"    : "").
            ($pack->allow_note_player_tags ? "allow_note_player_tags," : "").
            ($pack->allow_note_likes       ? "allow_note_likes,"       : "").
            ($pack->inventory_weight_cap   ? "inventory_weight_cap,"   : "").
            ($pack->ready_for_public       ? "ready_for_public,"       : "").
            "created".
            ") VALUES (".
            ($pack->name                   ? "'".addslashes($pack->name)."',"                   : "").
            ($pack->description            ? "'".addslashes($pack->description)."',"            : "").
            ($pack->icon_media_id          ? "'".addslashes($pack->icon_media_id)."',"          : "").
            ($pack->media_id               ? "'".addslashes($pack->media_id)."',"               : "").
            ($pack->map_type               ? "'".addslashes($pack->map_type)."',"               : "").
            ($pack->latitude               ? "'".addslashes($pack->latitude)."',"               : "").
            ($pack->longitude              ? "'".addslashes($pack->longitude)."',"              : "").
            ($pack->zoom_level             ? "'".addslashes($pack->zoom_level)."',"             : "").
            ($pack->show_player_location   ? "'".addslashes($pack->show_player_location)."',"   : "").
            ($pack->full_quick_travel      ? "'".addslashes($pack->full_quick_travel)."',"      : "").
            ($pack->allow_note_comments    ? "'".addslashes($pack->allow_note_comments)."',"    : "").
            ($pack->allow_note_player_tags ? "'".addslashes($pack->allow_note_player_tags)."'," : "").
            ($pack->allow_note_likes       ? "'".addslashes($pack->allow_note_likes)."',"       : "").
            ($pack->inventory_weight_cap   ? "'".addslashes($pack->inventory_weight_cap)."',"   : "").
            ($pack->ready_for_public       ? "'".addslashes($pack->ready_for_public)."',"       : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        dbconnection::queryInsert("INSERT INTO user_games (game_id, user_id, created) VALUES ('{$gameId}','{$pack->auth->user_id}',CURRENT_TIMESTAMP)");

        dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ('{$gameId}', 'QUESTS',    '1')");
        dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ('{$gameId}', 'GPS',       '2')");
        dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ('{$gameId}', 'INVENTORY', '3')");
        dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ('{$gameId}', 'QR',        '4')");
        dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ('{$gameId}', 'PLAYER',    '5')");
        dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index) VALUES ('{$gameId}', 'NOTE',      '6')");

        $gameDirectory = Media::getMediaDirectory($gameId)->data;
        mkdir($gameDirectory,0777);

        return games::getGame($gameId);
    }

    //Takes in game JSON, all fields optional except user_id + key
    public static function updateGameJSON($glob)
    {
	$data = file_get_contents("php://input");
        $glob = json_decode($data);
        return games::updateGame($glob);
    }

    public static function updateGame($pack)
    {
        return $pack;
        return editors::authenticateGameEditor($pack->game_id, $pack->auth->user_id, $pack->auth->key, "read_write");
        if(!editors::authenticateGameEditor($pack->game_id, $pack->auth->user_id, $pack->auth->key, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $gameId = dbconnection::queryInsert(
            "UPDATE games SET ".
            ($pack->name                   ? "name                   = '{$pack->name}', "             : "").
            ($pack->description            ? "description            = '{$description}', "            : "").
            ($pack->icon_media_id          ? "icon_media_id          = '{$icon_media_id}', "          : "").
            ($pack->media_id               ? "media_id               = '{$media_id}', "               : "").
            ($pack->map_type               ? "map_type               = '{$map_type}', "               : "").
            ($pack->latitude               ? "latitude               = '{$latitude}', "               : "").
            ($pack->longitude              ? "longitude              = '{$longitude}', "              : "").
            ($pack->zoom_level             ? "zoom_level             = '{$zoom_level}', "             : "").
            ($pack->show_player_location   ? "show_player_location   = '{$show_player_location}', "   : "").
            ($pack->full_quick_travel      ? "full_quick_travel      = '{$full_quick_travel}', "      : "").
            ($pack->allow_note_comments    ? "allow_note_comments    = '{$allow_note_comments}', "    : "").
            ($pack->allow_note_player_tags ? "allow_note_player_tags = '{$allow_note_player_tags}', " : "").
            ($pack->allow_note_likes       ? "allow_note_likes       = '{$allow_note_likes}', "       : "").
            ($pack->inventory_weight_cap   ? "inventory_weight_cap   = '{$inventory_weight_cap}', "   : "").
            ($pack->ready_for_public       ? "ready_for_public       = '{$ready_for_public}', "       : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE game_id = '{$pack->game_id}'"
        );

        return games::getGame($gameId);
    }

    public static function getGame($gameId)
    {
        $sql_game = dbconnection::queryObject("SELECT * FROM games WHERE game_id = '{$gameId}' LIMIT 1");
        if(!$sql_game) return new returnData(2, NULL, "The game you've requested does not exist");

        $game = new stdClass();
        $game->game_id = $sql_game->game_id;
        $game->name = $sql_game->name;
        $game->description = $sql_game->description;
        $game->icon_media_id = $sql_game->icon_media_id;
        $game->media_id = $sql_game->media_id;
        $game->map_type = $sql_game->map_type;
        $game->latitude = $sql_game->latitude;
        $game->longitude = $sql_game->longitude;
        $game->zoom_level = $sql_game->zoom_level;
        $game->show_player_location = $sql_game->show_player_location;
        $game->full_quick_travel = $sql_game->full_quick_travel;
        $game->allow_note_comments = $sql_game->allow_note_comments;
        $game->allow_note_player_tags = $sql_game->allow_note_player_tags;
        $game->allow_note_likes = $sql_game->allow_note_likes;
        $game->inventory_weight_cap = $sql_game->inventory_weight_cap;
        $game->ready_for_public = $sql_game->ready_for_public;

        return new returnData(0,$game);
    }

    public static function deleteGame($gameId, $userId, $key)
    {
        if(!editors::authenticateGameEditor($gameId, $userId, $key, "read_write")) return new returnData(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM games WHERE game_id = '{$gameId}' LIMIT 1");
        dbconnection::query("DELETE FROM game_tab_data WHERE game_id = '{$gameId}' LIMIT 1");
        $command = 'rm -rf '. Config::gamedataFSPath . "/{$gameId}";
        exec($command, $output, $return);
        if($return) return new returnData(4, NULL, "unable to delete game directory");
        return new returnData(0);
    }

    public function getTabBarItemsForGame($gameId)
    {
        $tabs = dbconnection::queryArray("SELECT * FROM game_tab_data WHERE game_id = '{$gameId}' ORDER BY tab_index ASC");
        return new returnData(0, $tabs, NULL);
    }

    public function saveTab($gameId, $tab, $index, $userId, $key)
    {
        if(!editors::authenticateGameEditor($gameId, $userId, $key, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        dbconnection::query("UPDATE game_tab_data SET tab_index = '{$index}' WHERE game_id = '{$gameId}' AND tab = '{$tab}'");
        return new returnData(0);
    }

    public function getFullGameObject($gameId, $playerId, $boolGetLocationalInfo = 0, $intSkipAtDistance = 99999999, $latitude = 0, $longitude = 0)
    {
        $gameObj = dbconnection::queryObject("SELECT * FROM games WHERE game_id = '{$gameId}' LIMIT 1");

        //Check if Game Has Been Played
        $debugString .= $gameId ." HAS BEEN PLAYED: ";
        $sTime = microtime(true);
        $gameObj->has_been_played = dbconnection::queryObject("SELECT count(player_id) as count FROM player_log WHERE player_id = '{$playerId}' AND game_id = '{$gameId}' AND deleted = 0 LIMIT 1")->count > 0;
        $debugString .=(microtime(true)-$sTime)."\n";

        //Get Locational Stuff
        if($boolGetLocationalInfo)
        {
            $debugString .= $gameId ." LOCATION INFO: ";
            $sTime = microtime(true);
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
            $debugString .=(microtime(true)-$sTime)."\n";
        }

        //Get Editors
        $debugString .= $gameId ." EDITORS: ";
        $sTime = microtime(true);
        $editors = dbconnection::queryArray("SELECT editors.* FROM editors, game_editors WHERE game_editors.editor_id = editors.editor_id AND game_editors.game_id = {$gameId}");
        $editorsString = "";
        for($i = 0; $i < count($editors); $i++)
            $editorsString .= $editors[$i]->name .", ";
        $gameObj->editors = rtrim($editorsString, ", "); //trims off last comma
        $debugString .=(microtime(true)-$sTime)."\n";

        //Get Num Players
        $debugString .= $gameId ." NUM_PLAYERS: ";
        $sTime = microtime(true);
        $gameObj->numPlayers = dbconnection::queryObject("SELECT count(player_id) as count FROM players WHERE last_game_id = {$gameId}")->count;
        $debugString .=(microtime(true)-$sTime)."\n";

        //Calculate the rating
        $debugString .= $gameId ." RATING: ";
        $sTime = microtime(true);
        $gameObj->rating = dbconnection::queryObject("SELECT AVG(rating) AS rating FROM game_comments WHERE game_id = {$gameId}")->rating;
        if($gameObj->rating == NULL) $gameObj->rating = 0;
        $debugString .=(microtime(true)-$sTime)."\n";

        //Getting Comments
        $debugString .= $gameId ." COMMENTS: ";
        $sTime = microtime(true);
        $gameComments = dbconnection::queryArray("SELECT * FROM game_comments WHERE game_id = {$gameId}");
        $comments = array();
        for($i = 0; $i < count($gameComments); $i++)
        {
            $c = new stdClass();
            $c->playerId = $gameComments[$i]->player_id;
            $c->username = dbconnection::queryObject("SELECT user_name FROM players WHERE player_id = '{$gameComments[$i]->player_id}'")->user_name;
            $c->rating = $gameComments[$i]->rating;
            $c->text = $gameComments[$i]->comment == 'Comment' ? "" : $gameComments[$i]->comment;
            $c->title = $gameComments[$i]->title;
            $c->timestamp = $gameComments[$i]->time_stamp;
            $comments[] = $c;
        }
        $gameObj->comments = $comments;
        $debugString .=(microtime(true)-$sTime)."\n";

        //Calculate score
        $gameObj->calculatedScore = ($gameObj->rating - 3) * $x;
        $gameObj->numComments = $x;

        Module::serverErrorLog($debugString);
        return $gameObj;
    }

    public function saveComment($playerId, $gameId, $rating, $comment, $title)
    {
        if($comment == 'Comment') $comment = "";
        $prevComment = dbconnection::queryObject("SELECT * FROM game_comments WHERE game_id = '{$gameId}' AND player_id = '{$playerId}'");
        if($prevComment) dbconnection::query("UPDATE game_comments SET rating='{$rating}', comment='{$comment}', title='{$title}' WHERE game_id = '{$gameId}' AND player_id = '{$playerId}'");
        else             dbconnection::query("INSERT INTO game_comments (game_id, player_id, rating, comment, title) VALUES ('{$gameId}', '{$playerId}', '{$rating}', '{$comment}', '{$title}')");
        
        $editorEmails = dbconnection::queryArray("SELECT editors.email FROM (SELECT * FROM game_editors WHERE game_id = ".$gameId.") AS ge LEFT JOIN editors ON ge.editor_id = editors.editor_id");
        if(count($editorEmails) > 0)
        {
            $gameName = dbconnection::queryObject("SELECT name FROM games WHERE game_id = $gameId")->name;
            $playerName = dbconnection::queryObject("SELECT user_name FROM players WHERE player_id = $playerId")->user_name;
            $sub = "New Rating for '".$gameName."'";
            $body = "Congratulations! People are playing your ARIS game! \n".$playerName." Recently gave your game ".$rating." stars out of 5" . (($comment.$title) ? ", commenting \"".$title.": ".$comment."\"" : ".");
        }
        for($i = 0; $i < count($editorEmails); $i++)
            Module::sendEmail($editorEmails[$i]->email,$sub,$body);

        return new returnData(0);
    }

    function addNoteTagToGame($gameId, $tag)
    {
        dbconnection::query("INSERT INTO game_tags (game_id, tag) VALUES ('{$gameId}', '{$tag}')");
        return new returnData(0);
    }

    public function duplicateGame($gameId, $userId, $key)
    {
        if(!users::authenticateUser($userId, $key, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

	//Add back in when requirements not being deleted is fixed, recheck for other issues
	//$errorString = Conversations::searchGameForErrors($gameId);
	//if($errorString) return new returnData(3, NULL, $errorString);
        
	Module::serverErrorLog("Duplicating Game Id:".$gameId);

        $game = dbconnection::queryObject("SELECT * FROM games WHERE game_id = {$gameId} LIMIT 1");
        if (!$game) return new returnData(2, NULL, "invalid game id");

        $compatibleName = false;
        $appendNo = 1;
        while(!$compatibleName)
        {
            $query = "SELECT * FROM games WHERE name = '".addslashes($game->name)."_copy".$appendNo."'";
            $result = dbconnection::query($query);
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
                $userId, $key)->data;

        //Remove the tabs created by createGame
        dbconnection::query("DELETE FROM game_tab_data WHERE game_id = {$newGameId}");

        $result = dbconnection::query("SELECT * FROM game_tab_data WHERE game_id = {$gameId}");
        while($result && $row = mysql_fetch_object($result))
            dbconnection::query("INSERT INTO game_tab_data (game_id, tab, tab_index, tab_detail_1) VALUES ('{$newGameId}', '{$row->tab}', '{$row->tab_index}', '{$row->tab_detail_1}')");

        $query = "SELECT * FROM requirements WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO requirements (game_id, content_type, content_id, requirement, not_operator, boolean_operator, requirement_detail_1, requirement_detail_2, requirement_detail_3, requirement_detail_4) VALUES ('{$newGameId}', '{$row->content_type}', '{$row->content_id}', '{$row->requirement}', '{$row->not_operator}', '{$row->boolean_operator}', '{$row->requirement_detail_1}', '{$row->requirement_detail_2}', '{$row->requirement_detail_3}', '{$row->requirement_detail_4}')";
            dbconnection::query($query);
        }

        $query = "SELECT * FROM quests WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO quests (game_id, name, description, text_when_complete, sort_index, go_function, active_media_id, complete_media_id, full_screen_notify, active_icon_media_id, complete_icon_media_id) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '".addSlashes($row->description)."', '".addSlashes($row->text_when_complete)."', '{$row->sort_index}', '{$row->go_function}', '{$row->active_media_id}', '{$row->complete_media_id}', '{$row->full_screen_notify}', '{$row->active_icon_media_id}', '{$row->complete_icon_media_id}')";

            dbconnection::query($query);
            $newId = mysql_insert_id();

            $query = "UPDATE requirements SET requirement_detail_1 = {$newId} WHERE game_id = '{$newGameId}' AND requirement = 'PLAYER_HAS_COMPLETED_QUEST' AND requirement_detail_1 = '{$row->quest_id}'";
            dbconnection::query($query);


            $query = "UPDATE requirements SET content_id = {$newId} WHERE game_id = '{$newGameId}' AND (content_type = 'QuestDisplay' OR content_type = 'QuestComplete') AND content_id = '{$row->quest_id}'";
            dbconnection::query($query);
        }

        $newFolderIds = array();
        $query = "SELECT * FROM folders WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO folders (game_id, name, parent_id, previous_id, is_open) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '{$row->parent_id}', '{$row->previous_id}', '{$row->is_open}')";
            dbconnection::query($query);
            $newFolderIds[($row->folder_id)] = mysql_insert_id();
        }

        $query = "SELECT * FROM folders WHERE game_id = {$newGameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            if($row->folder_id != 0){
                $query = "UPDATE folders SET parent_id = {$newFolderIds[($row->parent_id)]} WHERE game_id = '{$newGameId}' AND folder_id = {$row->folder_id}";
                dbconnection::query($query);
            }
        }

        $query = "SELECT * FROM folder_contents WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO folder_contents (game_id, folder_id, content_type, content_id, previous_id) VALUES ('{$newGameId}', '{$newFolderIds[($row->folder_id)]}', '{$row->content_type}', '{$row->content_id}', '{$row->previous_id}')";
            dbconnection::query($query);

            if($row->folder_id != 0){
                $query = "UPDATE folder_contents SET folder_id = {$newFolderIds[($row->folder_id)]} WHERE game_id = '{$newGameId}' AND object_content_id = {$row->object_content_id}";
                dbconnection::query($query); 
            }
        }

        $query = "SELECT * FROM qrcodes WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO qrcodes (game_id, link_type, link_id, code, match_media_id) VALUES ('{$newGameId}', '{$row->link_type}', '{$row->link_id}', '{$row->code}', '{$row->match_media_id}')";
            dbconnection::query($query);
        }

        $query = "SELECT * FROM fountains WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO fountains (game_id, type, location_id, spawn_probability, spawn_rate, max_amount, last_spawned, active) VALUES ('{$newGameId}', '{$row->type}', '{$row->location_id}', '{$row->spawn_probability}', '{$row->spawn_rate}', '{$row->max_amount}', '{$row->last_spawned}', '{$row->active}')";
            dbconnection::query($query);
        }

        $query = "SELECT * FROM spawnables WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO spawnables (game_id, type, type_id, amount, max_area, amount_restriction, location_bound_type, latitude, longitude, spawn_probability, spawn_rate, delete_when_viewed, last_spawned, error_range, force_view, hidden, allow_quick_travel, wiggle, time_to_live, active, location_name, show_title, min_area) VALUES ('{$newGameId}', '{$row->type}', '{$row->type_id}', '{$row->amount}', '{$row->max_area}', '{$row->amount_restriction}', '{$row->location_bound_type}', '{$row->latitude}', '{$row->longitude}', '{$row->spawn_probability}', '{$row->spawn_rate}', '{$row->delete_when_viewed}', '{$row->last_spawned}', '{$row->error_range}', '{$row->force_view}', '{$row->hidden}', '{$row->allow_quick_travel}', '{$row->wiggle}', '{$row->time_to_live}', '{$row->active}', '{$row->location_name}', '{$row->show_title}', '{$row->min_area}')";
            dbconnection::query($query);
            $newId = mysql_insert_id();

            $query = "UPDATE fountains SET location_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Spawnable' AND location_id = {$row->spawnable_id}";
            dbconnection::query($query);
        }

        $query = "SELECT * FROM locations WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO locations (game_id, name, description, latitude, longitude, error, type, type_id, icon_media_id, item_qty, hidden, force_view, allow_quick_travel) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '".addSlashes($row->description)."', '{$row->latitude}', '{$row->longitude}', '{$row->error}', '{$row->type}', '{$row->type_id}', '{$row->icon_media_id}', '{$row->item_qty}', '{$row->hidden}', '{$row->force_view}', '{$row->allow_quick_travel}')";
            dbconnection::query($query);
            $newId = mysql_insert_id();

            $query = "UPDATE fountains SET location_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Location' AND location_id = {$row->location_id}";
            dbconnection::query($query);

            $query = "UPDATE qrcodes SET link_id = {$newId} WHERE game_id = '{$newGameId}' AND link_type = 'Location' AND link_id = {$row->location_id}";
            dbconnection::query($query);

            $query = "UPDATE requirements SET content_id = {$newId} WHERE game_id = '{$newGameId}' AND content_type = 'Location' AND content_id = {$row->location_id}";
            dbconnection::query($query);
        }

        $query = "SELECT * FROM npc_conversations WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO npc_conversations (game_id, npc_id, node_id, text, sort_index) VALUES ('{$newGameId}', '{$row->npc_id}', '{$row->node_id}', '".addSlashes($row->text)."', '{$row->sort_index}')";
            dbconnection::query($query);
        }

        $query = "SELECT * FROM player_state_changes WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO player_state_changes (game_id, event_type, event_detail, action, action_detail, action_amount) VALUES ('{$newGameId}', '{$row->event_type}', '{$row->event_detail}', '{$row->action}', '{$row->action_detail}', '{$row->action_amount}')";
            dbconnection::query($query);
        }

        $newNpcIds = array();
        $query = "SELECT * FROM npcs WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){

            $query = "INSERT INTO npcs (game_id, name, description, text, closing, media_id, icon_media_id) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '".addSlashes($row->description)."', '".addSlashes($row->text)."', '".addSlashes($row->closing)."', '{$row->media_id}', '{$row->icon_media_id}')";
            dbconnection::query($query);
            $newId = mysql_insert_id();
            $newNpcIds[($row->npc_id)] = $newId;

            $query = "UPDATE npc_conversations SET npc_id = {$newId} WHERE game_id = '{$newGameId}' AND npc_id = {$row->npc_id}";
            dbconnection::query($query);

            $query = "UPDATE folder_contents SET content_id = {$newId} WHERE game_id = '{$newGameId}' AND content_type = 'Npc' AND content_id = {$row->npc_id}";
            dbconnection::query($query);

            $query = "UPDATE locations SET type_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Npc' AND type_id = {$row->npc_id}";
            dbconnection::query($query);

            $query = "UPDATE player_state_changes SET event_detail = {$newId} WHERE game_id = '{$newGameId}' AND event_type = 'VIEW_NPC' AND event_detail = {$row->npc_id}";
            dbconnection::query($query);

            $query = "UPDATE requirements SET requirement_detail_1 = {$newId} WHERE game_id = '{$newGameId}' AND requirement = 'PLAYER_VIEWED_NPC' AND requirement_detail_1 = {$row->npc_id}";
            dbconnection::query($query);

            $query = "UPDATE spawnables SET type_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Npc' AND type_id = {$row->npc_id}";
            dbconnection::query($query);
        }

        $newNodeIds = array();
        $query = "SELECT * FROM nodes WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO nodes (game_id, title, text, opt1_text, opt1_node_id, opt2_text, opt2_node_id, opt3_text, opt3_node_id, require_answer_incorrect_node_id, require_answer_string, require_answer_correct_node_id, media_id, icon_media_id) VALUES ('{$newGameId}', '".addSlashes($row->title)."', '".addSlashes($row->text)."', '{$row->opt1_text}', '{$row->opt1_node_id}', '{$row->opt2_text}', '{$row->opt2_node_id}', '{$row->opt3_text}', '{$row->opt3_node_id}', '{$row->require_answer_incorrect_node_id}', '{$row->require_answer_string}', '{$row->require_answer_correct_node_id}', '{$row->media_id}', '{$row->icon_media_id}')";
            dbconnection::query($query);
            $newId = mysql_insert_id();
            $newNodeIds[($row->node_id)] = $newId;

            $query = "UPDATE folder_contents SET content_id = {$newId} WHERE game_id = '{$newGameId}' AND content_type = 'Node' AND content_id = {$row->node_id}";
            dbconnection::query($query);

            $query = "UPDATE locations SET type_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Node' AND type_id = {$row->node_id}";
            dbconnection::query($query);

            $query = "UPDATE npc_conversations SET node_id = {$newId} WHERE game_id = '{$newGameId}' AND node_id = {$row->node_id}";
            dbconnection::query($query);

            $query = "UPDATE player_state_changes SET event_detail = {$newId} WHERE game_id = '{$newGameId}' AND event_type = 'VIEW_NODE' AND event_detail = {$row->node_id}";
            dbconnection::query($query);

            $query = "UPDATE requirements SET content_id = {$newId} WHERE game_id = '{$newGameId}' AND content_type = 'Node' AND content_id = {$row->node_id}";
            dbconnection::query($query);

            $query = "UPDATE requirements SET requirement_detail_1 = {$newId} WHERE game_id = '{$newGameId}' AND requirement = 'PLAYER_VIEWED_NODE' AND requirement_detail_1 = {$row->node_id}";
            dbconnection::query($query);

            $query = "UPDATE spawnables SET type_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Node' AND type_id = {$row->node_id}";
            dbconnection::query($query);

            if ($row->node_id == $game->on_launch_node_id) {
                $query = "UPDATE games SET on_launch_node_id = {$newId} WHERE game_id = '{$newGameId}'";
                dbconnection::query($query);
            }
            if ($row->node_id == $game->game_complete_node_id) {
                $query = "UPDATE games SET game_complete_node_id = {$newId} WHERE game_id = '{$newGameId}'";
                dbconnection::query($query);
            }
        }

        $newItemIds = array();
        $query = "SELECT * FROM items WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO items (game_id, name, description, is_attribute, icon_media_id, media_id, dropable, destroyable, max_qty_in_inventory, creator_player_id, origin_latitude, origin_longitude, origin_timestamp, weight, url, type) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '".addSlashes($row->description)."', '{$row->is_attribute}', '{$row->icon_media_id}', '{$row->media_id}', '{$row->dropable}', '{$row->destroyable}', '{$row->max_qty_in_inventory}', '{$row->creator_player_id}', '{$row->origin_latitude}', '{$row->origin_longitude}', '{$row->origin_timestamp}', '{$row->weight}', '{$row->url}', '{$row->type}')";
            dbconnection::query($query);
            $newId = mysql_insert_id();
            $newItemIds[($row->item_id)] = $newId;

            $query = "UPDATE folder_contents SET content_id = {$newId} WHERE game_id = '{$newGameId}' AND content_type = 'Item' AND content_id = {$row->item_id}";
            dbconnection::query($query);

            $query = "UPDATE locations SET type_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Item' AND type_id = {$row->item_id}";
            dbconnection::query($query);

            $query = "UPDATE player_state_changes SET event_detail = {$newId} WHERE game_id = '{$newGameId}' AND event_type = 'VIEW_ITEM' AND event_detail = {$row->item_id}";
            dbconnection::query($query);

            $query = "UPDATE player_state_changes SET action_detail = {$newId} WHERE game_id = '{$newGameId}' AND action_detail = {$row->item_id}";
            dbconnection::query($query);

            $query = "UPDATE requirements SET requirement_detail_1 = {$newId} WHERE game_id = '{$newGameId}' AND (requirement = 'PLAYER_HAS_ITEM' OR requirement = 'PLAYER_VIEWED_ITEM') AND requirement_detail_1 = {$row->item_id}";
            dbconnection::query($query);

            $query = "UPDATE spawnables SET type_id = {$newId} WHERE game_id = '{$newGameId}' AND type = 'Item' AND type_id = {$row->item_id}";
            dbconnection::query($query);
        }

        $query = "SELECT * FROM aug_bubble_media WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO aug_bubble_media (game_id, aug_bubble_id, media_id, text, index) VALUES ('{$newGameId}', '{$row->aug_bubble_id}', '{$row->media_id}', '{$row->text}', '{$row->index}')";
            dbconnection::query($query);
        }

        $newAugBubbleIds = array();
        $query = "SELECT * FROM aug_bubbles WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO aug_bubbles (game_id, name, description, icon_media_id) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '".addSlashes($row->description)."', '{$row->icon_media_id}')).";
            dbconnection::query($query);
            $newId = mysql_insert_id();
            $newAugBubbleIds[($row->aug_bubble_id)] = $newId;

            $query = "UPDATE aug_bubble_media SET aug_bubble_id = {$newId} WHERE aug_bubble_id = {$row->aug_bubble_id}";
            dbconnection::query($query);
            $query = "UPDATE locations SET type_id = {$newId} WHERE type = 'AugBubble' AND type_id = {$row->aug_bubble_id} AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE folder_contents SET content_id = {$newId} WHERE content_type = 'AugBubble' AND content_id = {$row->aug_bubble_id} AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE requirements SET requirement_detail_1 = {$newId} WHERE (requirement = 'PLAYER_HAS_NOT_VIEWED_AUGBUBBLE' OR requirement = 'PLAYER_VIEWED_AUGBUBBLE') AND requirement_detail_1 = {$row->aug_bubble_id}  AND game_id = '{$newGameId}'";
            dbconnection::query($query);
        }

        $newWebPageIds = array();
        $query = "SELECT * FROM web_pages WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO web_pages (game_id, name, url, icon_media_id) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '{$row->url}', '{$row->icon_media_id}')";
            dbconnection::query($query);
            $newId = mysql_insert_id();
            $newWebPageIds[($row->web_page_id)] = $newId;

            $query = "UPDATE locations SET type_id = {$newId} WHERE type = 'WebPage' AND type_id = {$row->web_page_id} AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE folder_contents SET content_id = {$newId} WHERE content_type = 'WebPage' AND content_id = {$row->web_page_id} AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE requirements SET requirement_detail_1 = {$newId} WHERE (requirement = 'PLAYER_HAS_NOT_VIEWED_WEBPAGE' OR requirement = 'PLAYER_VIEWED_WEBPAGE') AND requirement_detail_1 = {$row->web_page_id} AND game_id = '{$newGameId}'";
            dbconnection::query($query);
        }

        $query = "SELECT * FROM web_hooks WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $query = "INSERT INTO web_hooks (game_id, name, url, incoming) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '".addSlashes($row->url)."', '{$row->incoming}')";
            dbconnection::query($query);
            $newId = mysql_insert_id();

            $query = "UPDATE requirements SET content_id = {$newId} WHERE content_type = 'OutgoingWebHook' AND content_id = {$row->web_hook_id}  AND game_id = '{$newGameId}'";
            dbconnection::query($query);
        }

        $originalMediaId = array();
        $newMediaId = array();
        $query = "SELECT * FROM media WHERE game_id = {$gameId}";
        $result = dbconnection::query($query);
        while($result && $row = mysql_fetch_object($result)){
            $newMediaFilePath = $newGameId.substr($row->file_path,strpos($row->file_path,'/'));
            $query = "INSERT INTO media (game_id, name, file_path, is_icon) VALUES ('{$newGameId}', '".addSlashes($row->name)."', '{$newMediaFilePath}', '{$row->is_icon}')";
            dbconnection::query($query);
            $newId = mysql_insert_id();
            $newMediaIds[($row->media_id)] = $newId;

            if($row->file_path != "" && substr($row->file_path,-1) != "/" && file_exists("../../gamedata/" . $row->file_path)) copy(("../../gamedata/" . $row->file_path),("../../gamedata/" . $newMediaFilePath));

            $query = "UPDATE items SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE items SET media_id = {$newId} WHERE media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE locations SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE nodes SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE nodes SET media_id = {$newId} WHERE media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE npcs SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE npcs SET media_id = {$newId} WHERE media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE qrcodes SET match_media_id = {$newId} WHERE match_media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE quests SET active_icon_media_id = {$newId} WHERE active_icon_media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE quests SET complete_icon_media_id = {$newId} WHERE complete_icon_media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE quests SET active_media_id = {$newId} WHERE active_media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE quests SET complete_media_id = {$newId} WHERE complete_media_id = $row->media_id AND game_id = '{$newGameId}'";
            dbconnection::query($query);
            $query = "UPDATE aug_bubbles SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = {$newGameId}";
            dbconnection::query($query);
            $query = "UPDATE aug_bubble_media SET media_id = {$newId} WHERE media_id = $row->media_id AND game_id = {$newGameId}";
            dbconnection::query($query);
            $query = "UPDATE games SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = {$newGameId}";
            dbconnection::query($query);
            $query = "UPDATE games SET media_id = {$newId} WHERE media_id = $row->media_id AND game_id = {$newGameId}";
            dbconnection::query($query);
            $query = "UPDATE games SET pc_media_id = {$newId} WHERE pc_media_id = $row->media_id AND game_id = {$newGameId}";
            dbconnection::query($query);
            $query = "UPDATE web_pages SET icon_media_id = {$newId} WHERE icon_media_id = $row->media_id AND game_id = {$newGameId}";
            dbconnection::query($query);
        }

        //NOTE: substr removes <?xml version="1.0" ? //> from the beginning of the text
        $query = "SELECT node_id FROM npc_conversations WHERE game_id = {$newGameId}";
        $result = dbconnection::query($query);
        while($result && ($npcConvo = mysql_fetch_object($result))) {
            $query = "SELECT node_id, text FROM nodes WHERE node_id = {$npcConvo->node_id}";
            $resultNode = dbconnection::query($query);
            if($result && ($node = mysql_fetch_object($resultNode))){
                $inputString = $node->text;
                $output = Games::replaceXMLIds($inputString, $newNpcIds, $newNodeIds, $newItemIds, $newAugBubbleIds, $newWebPageIds, $newMediaIds);
                if($output){
                    $output = substr($output,22);
                    $updateQuery = "UPDATE nodes SET text = '".addslashes($output)."' WHERE node_id = {$node->node_id} AND game_id = {$newGameId}";
                    dbconnection::query($updateQuery);
                }
            }
        }

        $query = "SELECT * FROM npcs WHERE game_id = {$newGameId}";
        $result = dbconnection::query($query);
        while($result && ($row = mysql_fetch_object($result))) {
            if($row->text){
                $inputString = $row->text;
                $output = Games::replaceXMLIds($inputString, $newNpcIds, $newNodeIds, $newItemIds, $newAugBubbleIds, $newWebPageIds, $newMediaIds);
                if($output){
                    $output = substr($output,22);
                    $updateQuery = "UPDATE npcs SET text = '".addslashes($output)."' WHERE npc_id = {$row->npc_id} AND game_id = {$newGameId}";
                    dbconnection::query($updateQuery);
                }
            }
            if($row->closing){
                $inputString = $row->closing;
                $output = Games::replaceXMLIds($inputString, $newNpcIds, $newNodeIds, $newItemIds, $newAugBubbleIds, $newWebPageIds, $newMediaIds);
                if($output){
                    $output = substr($output,22);
                    $updateQuery = "UPDATE npcs SET closing = '".addslashes($output)."' WHERE npc_id = {$row->npc_id} AND game_id = {$newGameId}";
                    dbconnection::query($updateQuery);
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
}
?>
