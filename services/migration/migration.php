<?php
//NOTE- Cannot require conflicting class names (case insensitive)!
//query the db raw for any problematic classnames
require_once("../v1/players.php");
require_once("../v1/editors.php");
require_once("../v1/games.php");
require_once("../v2/users.php");

//require gross copypastad stubs to account for above problem
require_once("games.php");

//actually meaningful migration includes
require_once("migration_dbconnection.php");
require_once("migration_return_package.php");

//for in-place image resizing
require_once("../../libraries/wideimage/WideImage.php");

class migration extends migration_dbconnection
{	
    //Would be better if it used tokens rather than name/pass combos, but v1 player has no token
    public function migrateUser($playerName, $playerPass, $editorName, $editorPass, $newName, $newPass, $newDisplay, $newEmail)
    {
        $Players = new Players;
        $Editors = new Editors;
        $users = new users;

        $v1Player = $Players->getLoginPlayerObject($playerName, $playerPass)->data;
        $v1Editor = $Editors->getToken($editorName, $editorPass, "read_write")->data;

        if($playerName && !$v1Player) return new migration_return_package(1,NULL,"Player Credentials Invalid");
        if($editorName && !$v1Editor) return new migration_return_package(1,NULL,"Editor Credentials Invalid");
        if(!$v1Player  && !$v1Editor) return new migration_return_package(1,NULL,"No Data to Migrate");

        $userpack = new stdClass();
        $userpack->user_name = $newName;
        $userpack->password = $newPass;
        $userpack->display_name = $newDisplay ? $newDisplay : $newName;
        $userpack->email = $newEmail;
        $userpack->permission = "read_write";
        $v2User = $users->logInPack($userpack)->data;
        if(!$v2User) //user doesn't exists
        {
            //Don't create new user if trying to migrate from already migrated data
            if($v1Player && migration_dbconnection::queryObject("SELECT * FROM user_migrations WHERE v1_player_id = '{$v1Player->player_id}'"))
                return new migration_return_package(1,NULL,"Player already migrated.");
            if($v1Editor && migration_dbconnection::queryObject("SELECT * FROM user_migrations WHERE v1_editor_id = '{$v1Editor->editor_id}'"))
                return new migration_return_package(1,NULL,"Editor already migrated.");

            $v2User = $users->createUserPack($userpack)->data;
            if(!$v2User) return new migration_return_package(1,NULL,"Username Taken");
        }
        else
        {
            //Don't link existing data if already linked to other user
            if($v1Player && migration_dbconnection::queryObject("SELECT * FROM user_migrations WHERE v1_player_id = '{$v1Player->player_id}' AND v2_user_id != '{$v2User->user_id}'"))
                return new migration_return_package(1,NULL,"Player already migrated.");
            if($v1Editor && migration_dbconnection::queryObject("SELECT * FROM user_migrations WHERE v1_editor_id = '{$v1Editor->editor_id} AND v2_user_id != '{$v2User->user_id}''"))
                return new migration_return_package(1,NULL,"Editor already migrated.");
        }

        if(!$v1Player) { $v1Player = new stdClass(); $v1Player->player_id = 0; }
        if(!$v1Editor) { $v1Editor = new stdClass(); $v1Editor->editor_id = 0; }

        if(migration_dbconnection::queryObject("SELECT * FROM user_migrations WHERE v2_user_id = '{$v2User->user_id}'")) //already in migrations
            migration_dbconnection::query("UPDATE user_migrations SET v1_player_id = '{$v1Player->player_id}',  v1_editor_id = '{$v1Editor->editor_id}', v1_read_write_token = '{$v1Editor->read_write_token}' WHERE v2_user_id = '{$v2User->user_id}'");
        else //not in migrations
            migration_dbconnection::query("INSERT INTO user_migrations (v2_user_id, v2_read_write_key, v1_player_id, v1_editor_id, v1_read_write_token) VALUES ('{$v2User->user_id}', '{$v2User->read_write_key}', '{$v1Player->player_id}', '{$v1Editor->editor_id}', '{$v1Editor->read_write_token}')");

        return new migration_return_package(0,true);
    }

    public function migrateGame($v1GameId, $v1EditorId, $v1EditorToken)
    {
        $Editors = new Editors;
        $Games = new Games;
        $migGames = new mig_games;

        $migData = migration_dbconnection::queryObject("SELECT * FROM user_migrations WHERE v1_editor_id = '{$v1EditorId}'");
        if(!$migData) return new migration_return_package(1, NULL, "Editor not migrated");
        if($migData->v1_read_write_token != $v1EditorToken) return new migration_return_package(1, NULL, "Editor Authentication Failed");

        $v2Auth = new stdClass();
        $v2Auth->user_id = $migData->v2_user_id;
        $v2Auth->key = $migData->v2_read_write_key;
        $v2Auth->permission = "read_write";
        
        $oldGame = $Games->getGame($v1GameId)->data;
        //conform old terminology to new
        $oldGame->published = $oldGame->ready_for_public;
        $oldGame->type = $oldGame->is_locational ? "LOCATION" : "ANYWHERE";
        $oldGame->notebook_allow_comments = $oldGame->allow_note_comments;
        $oldGame->notebook_allow_likes = $oldGame->allow_note_likes;
        $oldGame->notebook_allow_player_tags = $oldGame->allow_player_tags;
        $oldGame->map_show_player = $oldGame->show_player_location;
        $oldGame->map_offsite_mode = $oldGame->full_quick_travel;
        $oldGame->auth = $v2Auth;
        $v2GameId = $migGames->createGame($oldGame);

        $maps = new stdClass();
        $maps->media = migration::migrateMedia($v1GameId, $v2GameId);

        //update game media refrences
        $v2Game = migration_dbconnection::queryObject("SELECT * FROM games WHERE game_id = '{$v2GameId}'","v2");
        migration_dbconnection::query("UPDATE games SET media_id = '{$maps->media[$v2Game->media_id]}', icon_media_id = '{$maps->media[$v2Game->icon_media_id]}' WHERE game_id = '{$v2GameId}'","v2");
        $v2Game = migration_dbconnection::queryObject("SELECT * FROM games WHERE game_id = '{$v2GameId}'","v2"); //get updated game data

        $maps->plaques = migration::migratePlaques($v1GameId, $v2GameId, $maps);
        $maps->items = migration::migrateItems($v1GameId, $v2GameId, $maps);
        $maps->webpages = migration::migrateWebpages($v1GameId, $v2GameId, $maps);
        $characterMaps = migration::migrateDialogs($v1GameId, $v2GameId, $maps);
        $maps->dialogs = $characterMaps->dialogsMap;
        $maps->scripts = $characterMaps->scriptsMap;
        $maps->options = $characterMaps->optionsMap;
        //$maps->notes = migration::migrateNotes($v1GameId, $v2GameId, $maps); //don't migrate notes for now... (we'll get into if we should later)
        $maps->tags = migration::migrateTags($v1GameId, $v2GameId, $maps);
        $maps->webhooks = migration::migrateWebhooks($v1GameId, $v2GameId, $maps);
        $maps->quests = migration::migrateQuests($v1GameId, $v2GameId, $maps);
        $maps->events = migration::migrateEvents($v1GameId, $v2GameId, $maps);
        $maps->factories = migration::migrateFactories($v1GameId, $v2GameId, $maps);

        $sceneId = migration_dbconnection::queryInsert("INSERT INTO scenes (game_id, name, created) VALUES ('{$v2GameId}', 'Main Scene', CURRENT_TIMESTAMP)","v2");
        $triggerMaps = migration::migrateTriggers($v1GameId, $v2GameId, $sceneId, $maps);
        //both of these maps have v1 location_id as key, v2 trigger as value
        $maps->locTriggers = $triggerMaps->locationTriggerMap;
        $maps->qrTriggers = $triggerMaps->qrTriggerMap;

        $maps->tabs = migration::migrateTabs($v1GameId, $v2GameId, $maps);
        migration::updateDialogOptionLinks($v1GameId, $v2GameId, $maps); //now that tabs/objects (link targets) are updated with ids, we can make sense of them

        //no maps generated from migrateRequirements
        migration::migrateRequirements($v1GameId, $v2GameId, $maps);

        return new migration_return_package(0,$v2Game);
    }

    public function migrateMedia($v1GameId, $v2GameId)
    {
        $mediaIdMap = array();
        $mediaIdMap[0] = 0; //preserve default/no media

        $media = migration_dbconnection::queryArray("SELECT * FROM media WHERE game_id = '{$v1GameId}'","v1");
        for($i = 0; $i < count($media); $i++)
        {
            $mediaIdMap[$media[$i]->media_id] = 0; //set it to 0 in case of failure
            
            $filename = substr($media[$i]->file_path, strpos($media[$i]->file_path,'/')+1);
            $filenametitle = substr($filename,0,strrpos($filename,'.'));
            $filenameext   = substr($filename,strrpos($filename,'.'));

            if(!$filename || !$filenametitle || !$filenameext || $filename == "error moving file" || $filenameext == "(null)") continue;

            //copy
            /*
            if(!file_exists(Config::gamedataFSPath."/".$media[$i]->file_path)) continue;
            copy(Config::gamedataFSPath."/".$media[$i]->file_path,Config::v2_gamedata_folder."/".$v2GameId."/".$filename);
            */

            //download
            try{
                file_put_contents(Config::v2_gamedata_folder."/".$v2GameId."/".$filename,fopen("http://arisgames.org/server/gamedata/".$media[$i]->file_path,'r'));
            }
            catch(Exception $e){}
            
            if( //if valid extension (image) and _128 doesn't exist, but non-_128 does, do thumbnailify here
                ($filenameext == ".jpg" || $filenameext == ".png" || $filenameext == ".gif") &&
                file_exists(Config::v2_gamedata_folder."/".$v2GameId."/".$filename)
                ) 
            {
                $thumb = WideImage::load(Config::v2_gamedata_folder."/".$v2GameId."/".$filename);
                $thumb = $thumb->resize(128, 128, 'outside');
                $thumb = $thumb->crop('center','center',128,128);
                $thumb->saveToFile(Config::v2_gamedata_folder."/".$v2GameId."/".$filenametitle."_128".$filenameext);
            }

            $newMediaId = migration_dbconnection::queryInsert("INSERT INTO media (game_id, file_folder, file_name, name, created) VALUES ('{$v2GameId}','{$v2GameId}','".addslashes($filename)."','".addslashes($media[$i]->name)."',CURRENT_TIMESTAMP)", "v2");
            $mediaIdMap[$media[$i]->media_id] = $newMediaId;
        }
        return $mediaIdMap;
    }

    public function migratePlaques($v1GameId, $v2GameId, $maps)
    {
        $plaqueIdMap = array();
        $plaqueIdMap[0] = 0;

        $plaques = migration_dbconnection::queryArray("SELECT * FROM nodes WHERE game_id = '{$v1GameId}'","v1");

        //find plaques that are actually npc options so we can ignore them
        $invalidMap = array();
        $npcPlaques = migration_dbconnection::queryArray("SELECT * FROM npc_conversations WHERE game_id = '{$v1GameId}'","v1");
        for($i = 0; $i < count($npcPlaques); $i++)
            $invalidMap[$npcPlaques[$i]->node_id] = true;

        for($i = 0; $i < count($plaques); $i++)
        {
            $plaqueIdMap[$plaques[$i]->node_id] = 0; //set it to 0 in case of failure
            if($invalidMap[$plaques[$i]->node_id]) continue; //this plaque actually an npc option- ignore

            $newPlaqueId = migration_dbconnection::queryInsert("INSERT INTO plaques (game_id, name, description, icon_media_id, media_id, created) VALUES ('{$v2GameId}','".addslashes($plaques[$i]->title)."','".addslashes($plaques[$i]->text)."','{$maps->media[$plaques[$i]->icon_media_id]}','{$maps->media[$plaques[$i]->media_id]}',CURRENT_TIMESTAMP)", "v2");
            $plaqueIdMap[$plaques[$i]->node_id] = $newPlaqueId;
        }
        return $plaqueIdMap;
    }

    public function migrateItems($v1GameId, $v2GameId, $maps)
    {
        $itemIdMap = array();
        $itemIdMap[0] = 0;

        $items = migration_dbconnection::queryArray("SELECT * FROM items WHERE game_id = '{$v1GameId}'","v1");
        for($i = 0; $i < count($items); $i++)
        {
            $itemIdMap[$items[$i]->item_id] = 0; //set it to 0 in case of failure
            $newItemId = migration_dbconnection::queryInsert("INSERT INTO items (game_id, name, description, icon_media_id, media_id, droppable, destroyable, max_qty_in_inventory, weight, url, type, created) VALUES ('{$v2GameId}','".addslashes($items[$i]->name)."','".addslashes($items[$i]->description)."','{$maps->media[$items[$i]->icon_media_id]}','{$maps->media[$items[$i]->media_id]}','{$items[$i]->dropable}','{$items[$i]->destroyable}','{$items[$i]->max_qty_in_inventory}','{$items[$i]->weight}','{$items[$i]->url}','{$items[$i]->type}',CURRENT_TIMESTAMP)", "v2");
            $itemIdMap[$items[$i]->item_id] = $newItemId;
        }
        return $itemIdMap;
    }

    public function migrateWebpages($v1GameId, $v2GameId, $maps)
    {
        $webpageIdMap = array();
        $webpageIdMap[0] = 0;

        $webpages = migration_dbconnection::queryArray("SELECT * FROM web_pages WHERE game_id = '{$v1GameId}'","v1");
        for($i = 0; $i < count($webpages); $i++)
        {
            $webpageIdMap[$webpages[$i]->web_page_id] = 0; //set it to 0 in case of failure
            $newWebpageId = migration_dbconnection::queryInsert("INSERT INTO web_pages (game_id, name, icon_media_id, url, created) VALUES ('{$v2GameId}','{$webpages[$i]->name}','{$maps->media[$webpages[$i]->icon_media_id]}','{$webpages[$i]->url}',CURRENT_TIMESTAMP)", "v2");
            $webpageIdMap[$webpages[$i]->web_page_id] = $newWebpageId;
        }
        return $webpageIdMap;
    }

    public function migrateDialogs($v1GameId, $v2GameId, $maps)
    {
        //returns two maps- one mapping npc ids to dialogs, one mapping nodes to scripts
        //note- although 'characters' get created in this function, they are NOT needed for further migration, and no map is kept of their IDs

        $dialogMap = array();
        $dialogMap[0] = 0;
        $scriptMap = array();
        $scriptMap[0] = 0;
        $optionMap = array();
        $optionMap[0] = 0;

        $dialogs = migration_dbconnection::queryArray("SELECT * FROM npcs WHERE game_id = '{$v1GameId}'","v1");

        //construct map of nodes for quick recall as referenced by npc_conversations
        $nodes = migration_dbconnection::queryArray("SELECT * FROM nodes WHERE game_id = '{$v1GameId}'","v1");
        $nodeMap = array();
        for($i = 0; $i < count($nodes); $i++)
            $nodeMap[$nodes[$i]->node_id] = $nodes[$i];

        for($i = 0; $i < count($dialogs); $i++)
        {
            $dialogMap[$dialogs[$i]->npc_id] = 0; //set it to 0 in case of failure

            $newDialogId = migration_dbconnection::queryInsert("INSERT INTO dialogs (game_id, name, description, icon_media_id, created) VALUES ('{$v2GameId}','".addslashes($dialogs[$i]->name)."','".addslashes($dialogs[$i]->description)."','{$maps->media[$dialogs[$i]->icon_media_id]}',CURRENT_TIMESTAMP)", "v2");
            $newCharacterId = migration_dbconnection::queryInsert("INSERT INTO dialog_characters (game_id, name, title, media_id, created) VALUES ('{$v2GameId}','".addslashes($dialogs[$i]->name)."','".addslashes($dialogs[$i]->name)."','{$maps->media[$dialogs[$i]->media_id]}',CURRENT_TIMESTAMP)", "v2");

            $parentScriptId = 0;
            //create intro script if exists, and treat it as the root script for all others
            if($dialogs[$i]->text && $dialogs[$i]->text != "")
            {
                $newIds = migration::textToScript(false, 0, $dialogs[$i]->text, $v2GameId, $newDialogId, $newCharacterId, $dialogs[$i]->name, $maps->media[$dialogs[$i]->media_id], 0, $maps);
                $parentScriptId = $newIds->lastScriptId;
                migration_dbconnection::query("UPDATE dialogs SET intro_dialog_script_id = '{$newIds->firstScriptId}' WHERE dialog_id = '{$newDialogId}'","v2");
            }
            else
            {
                //create empty intro script
                $newIds = migration::textToScript(false, 0, "<dialog><pc></pc></dialog>", $v2GameId, $newDialogId, $newCharacterId, $dialogs[$i]->name, $maps->media[$dialogs[$i]->media_id], 0, $maps);
                $parentScriptId = $newIds->lastScriptId;
                migration_dbconnection::query("UPDATE dialogs SET intro_dialog_script_id = '{$newIds->firstScriptId}' WHERE dialog_id = '{$newDialogId}'","v2");
            }


            //add exit option from greeting
            if($newIds->exitToType) //copy exitToId directly, once everything is migrated we'll go back and update ids (need to wait for not-yet-migrated stuff)
                migration_dbconnection::queryInsert("INSERT INTO dialog_options (game_id, dialog_id, parent_dialog_script_id, link_type, link_info, prompt, sort_index, created) VALUES ('{$v2GameId}','{$newDialogId}','{$parentScriptId}','{$newIds->exitToType}','{$newIds->exitToId}','Exit','999',CURRENT_TIMESTAMP)", "v2");
            else
                migration_dbconnection::queryInsert("INSERT INTO dialog_options (game_id, dialog_id, parent_dialog_script_id, link_type, prompt, sort_index, created) VALUES ('{$v2GameId}','{$newDialogId}','{$parentScriptId}','EXIT','Exit','999',CURRENT_TIMESTAMP)", "v2");

            $options = migration_dbconnection::queryArray("SELECT * FROM npc_conversations WHERE game_id = '{$v1GameId}' AND npc_id = '{$dialogs[$i]->npc_id}'","v1");
            for($j = 0; $j < count($options); $j++)
            {
                $node = migration_dbconnection::queryObject("SELECT * FROM nodes WHERE node_id = '{$options[$j]->node_id}'","v1");
                $newIds = migration::textToScript($options[$j]->text, $options[$j]->sort_index, $node->text, $v2GameId, $newDialogId, $newCharacterId, $dialogs[$i]->name, $maps->media[$dialogs[$i]->media_id], $parentScriptId, $maps);
                $optionMap[$options[$j]->node_id] = $newIds->firstOptionId;
                $scriptMap[$options[$j]->node_id] = $newIds->lastScriptId;
                if($newIds->exitToType) //copy exitToId directly, once everything is migrated we'll go back and update ids (need to wait for not-yet-migrated stuff)
                    migration_dbconnection::queryInsert("INSERT INTO dialog_options (game_id, dialog_id, parent_dialog_script_id, link_type, link_info, prompt, sort_index, created) VALUES ('{$v2GameId}','{$newDialogId}','{$newIds->lastScriptId}','{$newIds->exitToType}','{$newIds->exitToId}','Exit','999',CURRENT_TIMESTAMP)", "v2");
                else
                    migration_dbconnection::queryInsert("INSERT INTO dialog_options (game_id, dialog_id, parent_dialog_script_id, link_id, prompt, sort_index, created) VALUES ('{$v2GameId}','{$newDialogId}','{$newIds->lastScriptId}','{$parentScriptId}','Continue','998',CURRENT_TIMESTAMP)", "v2");
            }

            $dialogMap[$dialogs[$i]->npc_id] = $newDialogId;
        }

        $returnMaps = new stdClass;
        $returnMaps->dialogsMap = $dialogMap;
        $returnMaps->scriptsMap = $scriptMap;
        $returnMaps->optionsMap = $optionMap;
        return $returnMaps;
    }

    //helper for migrateDialogs
    //returns package w/id of the first option, and first and last of the newly created chain of scripts. 
    //(aka the option that inherits the node's requirements, the script to start it off, and the to-be-parent of any more scripts)
    //disclaimer: you should probably read up on regular expressions before messing around with this...
    public function textToScript($option, $optionIndex, $text, $gameId, $dialogId, $rootCharacterId, $rootCharacterTitle, $rootCharacterMediaId, $parentScriptId, $maps)
    {
        //testing scripts
        //$text = "<dialog banana=\"testing\" butNot=\"12\" America='555'><npc mediaId = \"59\">\nHere is the first thing I will say</npc><npc mediaId = \"60\">Second Thing!!!</npc></dialog>";
        //$text = "<d  but >dialog </dialog>"

        $newIds = new stdClass;
        $newIds->firstOptionId = 0;
        $newIds->firstScriptId = 0;
        $newIds->lastScriptId = $parentScriptId;
        $newIds->exitToType = 0;
        $newIds->exitToId = 0;
        
        //The case where no parsing is necessary 
        if(!preg_match("@<\s*dialog(ue)?\s*(\w*\s*=\s*[\"']\w*[\"']\s*)*>(.*?)<\s*/\s*dialog(ue)?\s*>@is",$text,$matches))
        {
            //phew. Nothing complicated. 
            $tmpScriptId = migration_dbconnection::queryInsert("INSERT INTO dialog_scripts (game_id, dialog_id, dialog_character_id, text, created) VALUES ('{$gameId}','{$dialogId}','{$rootCharacterId}','".addslashes($text)."',CURRENT_TIMESTAMP)", "v2");
            if($option) $newIds->firstOptionId = migration_dbconnection::queryInsert("INSERT INTO dialog_options (game_id, dialog_id, parent_dialog_script_id, link_id, prompt, sort_index, created) VALUES ('{$gameId}','{$dialogId}','{$newIds->lastScriptId}','{$tmpScriptId}','".addslashes($option)."','{$optionIndex}',CURRENT_TIMESTAMP)", "v2");
            $newIds->firstScriptId = $tmpScriptId;
            $newIds->lastScriptId = $tmpScriptId;
            return $newIds;
        }

        //if it gets here, we actually need to parse stuff..
        $dialogContents = $matches[3]; //$dialogContents will be the string between the dialog tags; save this before parsing dialog attribs

        //parse dialog tag
        preg_match("@<\s*([^\s>]*)([^>]*)@is",$text,$matches);
        $attribs = $matches[2]; //$attribs will be the string of attributes on tag (example: "mediaId='123' title='billy'")
        while(preg_match("@^\s*([^\s=]*)\s*=\s*[\"']([^\"']*)[\"']\s*(.*)@is",$attribs,$matches))
        {
            //In the example:  mediaId="123" name="billy"
            $attrib_name = $matches[1]; //mediaId
            $attrib_value = $matches[2]; //123
            $attribs = $matches[3]; //name="billy"

            if(preg_match("@exitToTab@i",$attrib_name))               $newIds->exitToType = "EXIT_TO_TAB";      $newIds->exitToId = $attrib_value;
            if(preg_match("@exitToScannerWithPrompt@i",$attrib_name)) $newIds->exitToType = "EXIT_TO_TAB";      $newIds->exitToId = $attrib_value;
            if(preg_match("@exitToPlaque@i",$attrib_name))            $newIds->exitToType = "EXIT_TO_PLAQUE";   $newIds->exitToId = $attrib_value;
            if(preg_match("@exitToWebPage@i",$attrib_name))           $newIds->exitToType = "EXIT_TO_WEB_PAGE"; $newIds->exitToId = $attrib_value;
            if(preg_match("@exitToCharacter@i",$attrib_name))         $newIds->exitToType = "EXIT_TO_DIALOG";   $newIds->exitToId = $attrib_value;
            if(preg_match("@exitToItem@i",$attrib_name))              $newIds->exitToType = "EXIT_TO_ITEM";     $newIds->exitToId = $attrib_value;
        }

        //parse contents of dialog tag
        while(!preg_match("@^\s*$@s",$dialogContents))//while dialogContents not empty
        {
            preg_match("@<\s*([^\s>]*)([^>]*)@is",$dialogContents,$matches);
            $tag = $matches[1]; //$tag will be the tag type (example: "npc")
            $attribs = $matches[2]; //$attribs will be the string of attributes on tag (example: "mediaId='123' title='billy'")
            preg_match("@<\s*".$tag."[^>]*>(.*?)<\s*\/\s*".$tag."\s*>(.*)@is",$dialogContents,$matches);
            $tag_contents = $matches[1]; //$tag_contents will be the string between npc tags (example: "Hi!")
            $dialogContents = $matches[2]; //$dialog_contents will be the rest of the dialog contents that still need parsing

            $characterId = 0;
            if(preg_match("@npc@i",$tag))
            {
                $characterId = $rootCharacterId; //assume clean npc tag, use default character

                $title = "";
                $mediaId = 0;
                while(preg_match("@^\s*([^\s=]*)\s*=\s*[\"']([^\"']*)[\"']\s*(.*)@is",$attribs,$matches))
                {
                    //In the example:  mediaId="123" name="billy"
                    $attrib_name = $matches[1]; //mediaId
                    $attrib_value = $matches[2]; //123
                    $attribs = $matches[3]; //name="billy"

                    if(preg_match("@title@i",$attrib_name)) $title = $attrib_value;
                    if(preg_match("@mediaId@i",$attrib_name)) $mediaId = $attrib_value;
                    //etc... (check for other attribs here)
                }

                if($title != "" || $mediaId != 0)
                {
                    if($title == "") $title = $rootCharacterTitle;
                    if($mediaId == 0) $title = $rootCharacterMediaId;
                    $characterId = migration_dbconnection::queryInsert("INSERT INTO dialog_characters (game_id, name, title, media_id, created) VALUES ('{$gameId}','".addslashes($title)."','".addslashes($title)."','{$rootCharacterMediaId}',CURRENT_TIMESTAMP)", "v2");
                }
            }
            else if(preg_match("@pc@i",$tag))
            {
                $characterId = 0; //assume clean pc tag, use player character.

                $title = "";
                $mediaId = 0;
                while(preg_match("@^\s*([^\s=]*)\s*=\s*[\"']([^\"']*)[\"']\s*(.*)@is",$attribs,$matches))
                {
                    //In the example:  mediaId="123" name="billy"
                    $attrib_name = $matches[1]; //mediaId
                    $attrib_value = $matches[2]; //123
                    $attribs = $matches[3]; //name="billy"

                    if(preg_match("@title@i",$attrib_name)) $title = $attrib_value;
                    if(preg_match("@mediaId@i",$attrib_name)) $mediaId = $attrib_value;
                    //etc... (check for other attribs here)
                }

                if($title != "" || $mediaId != 0)
                {
                    if($title == "") $title = $rootCharacterTitle;
                    if($mediaId == 0) $title = $rootCharacterMediaId;
                    $characterId = migration_dbconnection::queryInsert("INSERT INTO dialog_characters (game_id, name, title, media_id, created) VALUES ('{$gameId}','".addslashes($title)."','".addslashes($title)."','{$rootCharacterMediaId}',CURRENT_TIMESTAMP)", "v2");
                }
                //handle non-npc tag attributes
            }

            $tmpScriptId = migration_dbconnection::queryInsert("INSERT INTO dialog_scripts (game_id, dialog_id,  dialog_character_id, text, created) VALUES ('{$gameId}','{$dialogId}','{$characterId}','".addslashes($tag_contents)."',CURRENT_TIMESTAMP)", "v2");
            if($option) $newestOptionId = migration_dbconnection::queryInsert("INSERT INTO dialog_options (game_id, dialog_id, parent_dialog_script_id, link_id, prompt, sort_index, created) VALUES ('{$gameId}','{$dialogId}','{$newIds->lastScriptId}','{$tmpScriptId}','".addslashes($option)."','{$optionIndex}',CURRENT_TIMESTAMP)", "v2");

            if(!$newIds->firstOptionId) $newIds->firstOptionId = $newestOptionId;
            if(!$newIds->firstScriptId) $newIds->firstScriptId = $tmpScriptId;
            $newIds->lastScriptId = $tmpScriptId;
            $option = "Continue"; //set option for all but first script to 'continue'
            $optionIndex = 0; //set option index 0 for all but first script
        }
        return $newIds;
    }

    public function updateDialogOptionLinks($v1GameId, $v2GameId, $maps)
    {
        $options = migration_dbconnection::queryArray("SELECT * FROM dialog_options WHERE game_id = '{$v2GameId}'","v2");
        $tabs = migration_dbconnection::queryArray("SELECT * FROM tabs WHERE game_id = '{$v2GameId}'","v2");
        $scannertab = null;
        for($j = 0; $j < count($tabs); $j++)
        {
            if(preg_match("@{$tabs[$j]->type}@i","scanner"))
                $scannertab = $tabs[$j];
        }
        $tab = null;
        for($i = 0; $i < count($options); $i++)
        {
            if($options[$i]->link_type == 'EXIT_TO_PLAQUE')   migration_dbconnection::query("UPDATE dialog_options SET link_id = '{$maps->plaques[ intval($options[$i]->link_info)]}' WHERE dialog_option_id = '{$options[$i]->dialog_option_id}'","v2");
            if($options[$i]->link_type == 'EXIT_TO_ITEM')     migration_dbconnection::query("UPDATE dialog_options SET link_id = '{$maps->items[   intval($options[$i]->link_info)]}' WHERE dialog_option_id = '{$options[$i]->dialog_option_id}'","v2");
            if($options[$i]->link_type == 'EXIT_TO_DIALOG')   migration_dbconnection::query("UPDATE dialog_options SET link_id = '{$maps->dialogs[ intval($options[$i]->link_info)]}' WHERE dialog_option_id = '{$options[$i]->dialog_option_id}'","v2");
            if($options[$i]->link_type == 'EXIT_TO_WEB_PAGE') migration_dbconnection::query("UPDATE dialog_options SET link_id = '{$maps->webpages[intval($options[$i]->link_info)]}' WHERE dialog_option_id = '{$options[$i]->dialog_option_id}'","v2");
            if($options[$i]->link_type == 'EXIT_TO_TAB')
            {
                for($j = 0; $j < count($tabs); $j++)
                {
                    if(preg_match("@{$tabs[$j]->type}@i",$options[$i]->link_info))
                        $tab = $tabs[$j];
                }
                if($tab) migration_dbconnection::query("UPDATE dialog_options SET link_id = '{$tab->tab_id}' WHERE dialog_option_id = '{$options[$i]->dialog_option_id}'","v2");
                else if($scannertab) migration_dbconnection::query("UPDATE dialog_options SET link_id = '{$scannertab->tab_id}' WHERE dialog_option_id = '{$options[$i]->dialog_option_id}'","v2");
                $tab = null;
            }
        }
    }

    public function migrateTags($v1GameId, $v2GameId, $maps)
    {
        $tagIdMap = array();
        $tagIdMap[0] = 0;

        $tags = migration_dbconnection::queryArray("SELECT * FROM game_tags WHERE game_id = '{$v1GameId}'","v1");
        for($i = 0; $i < count($tags); $i++)
        {
            $tagIdMap[$tags[$i]->tag_id] = 0; //set it to 0 in case of failure
            $newtagId = migration_dbconnection::queryInsert("INSERT INTO tags (game_id, tag, media_id, player_created, visible, sort_index, created) VALUES ('{$v2GameId}','{$tags[$i]->tag}','{$maps->media[$tags[$i]->media_id]}','{$tags[$i]->player_created}','1','0',CURRENT_TIMESTAMP)", "v2");
            $tagIdMap[$tags[$i]->tag_id] = $newtagId;
        }
        return $tagIdMap;
    }

    public function migrateWebhooks($v1GameId, $v2GameId, $maps)
    {
        $webhookIdMap = array();
        $webhookIdMap[0] = 0;

        $webhooks = migration_dbconnection::queryArray("SELECT * FROM web_hooks WHERE game_id = '{$v1GameId}'","v1");
        for($i = 0; $i < count($webhooks); $i++)
        {
            $webhookIdMap[$webhooks[$i]->web_hook_id] = 0; //set it to 0 in case of failure
            $newWebhookId = migration_dbconnection::queryInsert("INSERT INTO web_hooks (game_id, name, url, incoming, created) VALUES ('{$v2GameId}','{$webhooks[$i]->name}','{$webhooks[$i]->url}','{$webhooks[$i]->incoming}',CURRENT_TIMESTAMP)", "v2");
            $webhookIdMap[$webhooks[$i]->web_hook_id] = $newWebhookId;
        }
        return $webhookIdMap;
    }

    public function migrateQuests($v1GameId, $v2GameId, $maps)
    {
        $questIdMap = array();
        $questIdMap[0] = 0;

        $quests = migration_dbconnection::queryArray("SELECT * FROM quests WHERE game_id = '{$v1GameId}'","v1");
        for($i = 0; $i < count($quests); $i++)
        {
            $questIdMap[$quests[$i]->quest_id] = 0; //set it to 0 in case of failure
            $newQuestId = migration_dbconnection::queryInsert("INSERT INTO quests (game_id, name, description, active_icon_media_id, active_media_id, active_description, active_notification_type, active_function, complete_icon_media_id, complete_media_id, complete_description, complete_notification_type, complete_function, sort_index, created) VALUES ('{$v2GameId}', '".addslashes($quests[$i]->name)."', '".addslashes($quests[$i]->description)."', '{$maps->media[$quests[$i]->active_icon_media_id]}', '{$maps->media[$quests[$i]->active_media_id]}', '".addslashes($quests[$i]->description)."', '".($quests[$i]->full_screen_notify ? "FULL_SCREEN" : "DROP_DOWN")."', '{$quests[$i]->go_function}', '{$maps->media[$quests[$i]->complete_icon_media_id]}', '{$maps->media[$quests[$i]->complete_media_id]}', '".addslashes($quests[$i]->text_when_complete)."', '".($quests[$i]->complete_full_screen_notify ? "FULL_SCREEN" : "DROP_DOWN")."', '{$quests[$i]->complete_go_function}', '{$quests[$i]->sort_index}', CURRENT_TIMESTAMP)", "v2");
            $questIdMap[$quests[$i]->quest_id] = $newQuestId;
        }
        return $questIdMap;
    }

    public function migrateEvents($v1GameId, $v2GameId, $maps)
    {
        //round up all v1 events into groups by type and by object
        $eGroupings = new stdClass;
        $eGroupings->plaques = array();
        $eGroupings->dialogScripts = array();
        $events = migration_dbconnection::queryArray("SELECT * FROM player_state_changes WHERE game_id = '{$v1GameId}'","v1");
        $q = 0;
        for($i = 0; $i < count($events); $i++)
        {
            if($maps->plaques[$events[$i]->event_detail]) $typeGroup = &$eGroupings->plaques;
            if($maps->scripts[$events[$i]->event_detail]) $typeGroup = &$eGroupings->dialogScripts;

            if(!$typeGroup[$events[$i]->event_detail]) $typeGroup[$events[$i]->event_detail] = array();
            $typeGroup[$events[$i]->event_detail][] = $events[$i];
        }

        foreach($eGroupings->plaques as $plaqueId => $eventsList)
        {
            $event_package_id = migration::migrateEventsListIntoPackage($v2GameId, $eventsList, $maps);
            migration_dbconnection::query("UPDATE plaques SET event_package_id = '{$event_package_id}' WHERE plaque_id = '{$maps->plaques[$plaqueId]}'","v2");
        }
        foreach($eGroupings->dialogScripts as $scriptId => $eventsList)
        {
            $event_package_id = migration::migrateEventsListIntoPackage($v2GameId, $eventsList, $maps);
            migration_dbconnection::query("UPDATE dialog_scripts SET event_package_id = '{$event_package_id}' WHERE dialog_script_id = '{$maps->scripts[$scriptId]}'","v2");
        }
    }
    //helper for migrateEvents
    public function migrateEventsListIntoPackage($gameId, $eventsList, $maps)
    {
        $event_package_id = migration_dbconnection::queryInsert("INSERT INTO event_packages (game_id, created) VALUES ('{$gameId}',CURRENT_TIMESTAMP)","v2");

        for($i = 0; $i < count($eventsList); $i++)
            migration_dbconnection::queryInsert("INSERT INTO events (game_id, event_package_id, event, content_id, qty, created) VALUES ('{$gameId}', '{$event_package_id}', '{$eventsList[$i]->action}','{$maps->items[$eventsList[$i]->action_detail]}','{$eventsList[$i]->action_amount}',CURRENT_TIMESTAMP)","v2");

        return $event_package_id;
    }

    public function migrateFactories($v1GameId, $v2GameId, $maps)
    {
        $factoryIdMap = array();
        $factoryIdMap[0] = 0;

        $factories = migration_dbconnection::queryArray("SELECT * FROM spawnables WHERE game_id = '{$v1GameId}'","v1");
        for($i = 0; $i < count($factories); $i++)
        {
            $factoryIdMap[$factories[$i]->factory_id] = 0; //set it to 0 in case of failure
            $type = ""; $id = 0;
            if($factories[$i]->type == "Node")    { $type = "PLAQUE";   $id = $maps->plaques[$factories[$i]->type_id]; }
            if($factories[$i]->type == "Item")    { $type = "ITEM";     $id = $maps->items[$factories[$i]->type_id]; }
            if($factories[$i]->type == "Npc")     { $type = "DIALOG";   $id = $maps->dialogs[$factories[$i]->type_id]; }
            if($factories[$i]->type == "WebPage") { $type = "WEB_PAGE"; $id = $maps->webpages[$factories[$i]->type_id]; }
            $newfactoryId = migration_dbconnection::queryInsert("INSERT INTO factories (game_id, name, description, object_type, object_id, seconds_per_production, production_probability, max_production, produce_expiration_time, produce_expire_on_view, production_bound_type, location_bound_type, min_production_distance, max_production_distance, trigger_latitude, trigger_longitude, trigger_distance, trigger_on_enter, trigger_hidden, trigger_wiggle, trigger_title, trigger_show_title, created) VALUES ('{$v2GameId}', 'My Factory', '', '{$type}', '{$id}', '{$factories[$i]->spawn_rate}', '{$factories[$i]->spawn_probability}', '{$factories[$i]->amount}', '{$factories[$i]->time_to_live}', '{$factories[$i]->delete_when_viewed}', '{$factories[$i]->amount_restriction}', '{$factories[$i]->location_bound_type}', '{$factories[$i]->min_area}', '{$factories[$i]->max_area}', '{$factories[$i]->latitude}', '{$factories[$i]->longitude}', '{$factories[$i]->error_range}', '{$factories[$i]->force_view}', '{$factories[$i]->hidden}', '{$factories[$i]->wiggle}', '{$factories[$i]->location_name}', '{$factories[$i]->show_title}', CURRENT_TIMESTAMP)", "v2");
            $factoryIdMap[$factories[$i]->factory_id] = $newfactoryId;
        }
        return $factoryIdMap;
    }

    public function migrateTriggers($v1GameId, $v2GameId, $sceneId, $maps)
    {
        //returns two trigger maps- one mapping location ids to triggers, one mapping qr codes to triggers
        //note- although 'instances' get created in this function, they are NOT needed for further migration, and no map is kept of their IDs

        $locTriggerMap = array();
        $locTriggerMap[0] = 0;
        $qrTriggerMap = array();
        $qrTriggerMap[0] = 0;

        $qrcodes = migration_dbconnection::queryArray("SELECT * FROM qrcodes WHERE game_id = '{$v1GameId}'","v1");
        $qrCodeLocationMap = array(); //used to find qr code from location quickly
        for($i = 0; $i < count($qrcodes); $i++)
            $qrCodeLocationMap[$qrcodes[$i]->link_id] = $qrcodes[$i];

        $locations = migration_dbconnection::queryArray("SELECT * FROM locations WHERE game_id = '{$v1GameId}'","v1");

        for($i = 0; $i < count($locations); $i++)
        {
            $locTriggerMap[$locations[$i]->location_id] = 0;
            $qrTriggerMap[$locations[$i]->location_id] = 0;

            $newType = "";
            $objectId = 0;
            if($locations[$i]->type == 'AugBubble')  continue; //doesn't exist anymore
            if($locations[$i]->type == 'Event')      continue; //doesn't exist anymore (and never did?)
            if($locations[$i]->type == 'Node')       { $newType = "PLAQUE";   $objectId = $maps->plaques[$locations[$i]->type_id];  }
            if($locations[$i]->type == 'Item')       { $newType = "ITEM";     $objectId = $maps->items[$locations[$i]->type_id];    }
            if($locations[$i]->type == 'Npc')        { $newType = "DIALOG";   $objectId = $maps->dialogs[$locations[$i]->type_id];  }
            if($locations[$i]->type == 'WebPage')    { $newType = "WEB_PAGE"; $objectId = $maps->webpages[$locations[$i]->type_id]; }
            if($locations[$i]->type == 'PlayerNote') { $newType = "NOTE";     $objectId = $maps->notes[$locations[$i]->type_id];    }
            if(!$objectId) continue; //either we've encountered something invalid in the DB, or we no longer support something

            $newInstanceId = migration_dbconnection::queryInsert("INSERT INTO instances (game_id,object_id,object_type,qty,infinite_qty,created) VALUES ('{$v2GameId}','{$objectId}','{$newType}','{$locations[$i]->item_qty}','".(intval($locations[$i]->item_qty) < 0 ? 1 : 0)."',CURRENT_TIMESTAMP)","v2");
            $newTriggerId = migration_dbconnection::queryInsert("INSERT INTO triggers (game_id,instance_id,scene_id,type,name,title,latitude,longitude,distance,wiggle,show_title,hidden,trigger_on_enter,created) VALUES ('{$v2GameId}','{$newInstanceId}','{$sceneId}','LOCATION','".addslashes($locations[$i]->name)."','".addslashes($locations[$i]->name)."','{$locations[$i]->latitude}','{$locations[$i]->longitude}','".($locations[$i]->error)."','{$locations[$i]->wiggle}','{$locations[$i]->show_title}','{$locations[$i]->hidden}','{$locations[$i]->force_view}',CURRENT_TIMESTAMP)", "v2");
            $locTriggerMap[$locations[$i]->location_id] = $newTriggerId;

            //Note that this DUPLICATES INSTANCES!!! (1 location/qr combo from v1 creates 2 instances, a location trigger, and a qr trigger)
            $qrcode = $qrCodeLocationMap[$locations[$i]->location_id];
            if($qrcode)
            {
                $newInstanceId = migration_dbconnection::queryInsert("INSERT INTO instances (game_id,object_id,object_type,qty,infinite_qty,created) VALUES ('{$v2GameId}','{$objectId}','{$newType}','{$locations[$i]->item_qty}','".(intval($locations[$i]->item_qty) < 0 ? 1 : 0)."',CURRENT_TIMESTAMP)","v2");
                $newTriggerId = migration_dbconnection::queryInsert("INSERT INTO triggers (game_id,instance_id,scene_id,type,name,qr_code,created) VALUES ('{$v2GameId}','{$newInstanceId}','{$sceneId}','QR','".addslashes($locations[$i]->name)."','".addslashes($qrcode->code)."',CURRENT_TIMESTAMP)", "v2");
                $qrTriggerMap[$locations[$i]->location_id] = $newTriggerId; //note that I'm hooking up the LOCATION id to the trigger again.
                //^ This is because nothing in v1 links to qr codes, only locations.
                //but locations were now split into two objects (one for their v1 location and one for their v1 qr), and both need to be recorded.
            }
        }

        //special case where we create an immediate trigger from intro plaque
        $game = migration_dbconnection::queryObject("SELECT * FROM games WHERE game_id = '{$v1GameId}'","v1");
        if($game->on_launch_node_id)
        {
            //create req package id to give to trigger "(haven't seen plaque)"
            $requirementRootPackageId = migration_dbconnection::queryInsert("INSERT INTO requirement_root_packages (game_id, created) VALUES ('{$v2GameId}', CURRENT_TIMESTAMP)","v2");
            $requirementAndPackageId = migration_dbconnection::queryInsert("INSERT INTO requirement_and_packages (game_id, requirement_root_package_id, created) VALUES ('{$v2GameId}','{$requirementRootPackageId}', CURRENT_TIMESTAMP)","v2");
            $requirementAtomId = migration_dbconnection::queryInsert("INSERT INTO requirement_atoms (game_id, requirement_and_package_id, bool_operator, requirement, content_id, created) VALUES ('{$v2GameId}','{$requirementAndPackageId}', 0, 'PLAYER_VIEWED_PLAQUE','{$maps->plaques[$game->on_launch_node_id]}',CURRENT_TIMESTAMP)","v2");

            $newInstanceId = migration_dbconnection::queryInsert("INSERT INTO instances (game_id,object_id,object_type,created) VALUES ('{$v2GameId}','{$maps->plaques[$game->on_launch_node_id]}','PLAQUE',CURRENT_TIMESTAMP)","v2");
            $newTriggerId = migration_dbconnection::queryInsert("INSERT INTO triggers (game_id,instance_id,scene_id,type,requirement_root_package_id,created) VALUES ('{$v2GameId}','{$newInstanceId}','{$sceneId}','IMMEDIATE','{$requirementRootPackageId}',CURRENT_TIMESTAMP)", "v2");
            //no need to store req/instance/trigger in map, as nothing else should reference them
        }

        $returnMaps = new stdClass;
        $returnMaps->locationTriggerMap = $locTriggerMap;
        $returnMaps->qrTriggerMap = $qrTriggerMap;
        return $returnMaps;
    }

    public function migrateTabs($v1GameId, $v2GameId, $maps)
    {
        $tabIdMap = array();
        $tabIdMap[0] = 0; //preserve default/no tab

        //remove defaults created at game creation, just to simplify things
        migration_dbconnection::query("DELETE FROM tabs WHERE game_id = '{$v2GameId}'","v2");

        $tabs = migration_dbconnection::queryArray("SELECT * FROM game_tab_data WHERE game_id = '{$v1GameId}'","v1");
        for($i = 0; $i < count($tabs); $i++)
        {
            $tabIdMap[$tabs[$i]->id] = 0; //set it to 0 in case of failure
            if($tabs[$i]->tab_index < 1) continue; //in new model, disabled tabs are simply deleted

            //old: 'GPS','NEARBY','QUESTS','INVENTORY','PLAYER','QR','NOTE','STARTOVER','PICKGAME','NPC','ITEM','NODE','WEBPAGE'
            //new: 'MAP','DECODER','SCANNER','QUESTS','INVENTORY','PLAYER','NOTE','DIALOG','ITEM','PLAQUE','WEBPAGE'
            $newType = $tabs[$i]->tab;
            $newDetail = 0;
            if($tabs[$i]->tab == "NEARBY") continue;
            if($tabs[$i]->tab == "STARTOVER") continue;
            if($tabs[$i]->tab == "PICKGAME") continue;
            if($tabs[$i]->tab == "GPS")       { $newType = "MAP";       $newDetail = $tabs[$i]->tab_detail_1; }
            if($tabs[$i]->tab == "QUESTS")    { $newType = "QUESTS";    $newDetail = $tabs[$i]->tab_detail_1; }
            if($tabs[$i]->tab == "INVENTORY") { $newType = "INVENTORY"; $newDetail = $tabs[$i]->tab_detail_1; }
            if($tabs[$i]->tab == "PLAYER")    { $newType = "PLAYER";    $newDetail = $tabs[$i]->tab_detail_1; }
            if($tabs[$i]->tab == "NOTE")      { $newType = "NOTEBOOK";  $newDetail = $tabs[$i]->tab_detail_1; } //technically, there is a NOTE option separate from NOTEBOOK now, but was impossible in v1. so odd mapping.
            if($tabs[$i]->tab == "NPC")       { $newType = "DIALOG";    $newDetail = $maps->dialogs[$tabs[$i]->tab_detail_1]; }
            if($tabs[$i]->tab == "ITEM")      { $newType = "ITEM";      $newDetail = $maps->items[$tabs[$i]->tab_detail_1]; }
            if($tabs[$i]->tab == "NODE")      { $newType = "PLAQUE";    $newDetail = $maps->plaques[$tabs[$i]->tab_detail_1]; }
            if($tabs[$i]->tab == "WEBPAGE")   { $newType = "WEB_PAGE";  $newDetail = $maps->webpages[$tabs[$i]->tab_detail_1]; }
            if($tabs[$i]->tab == "QR") $newType = ($tabs[$i]->tab_detail_1 == 0 || $tabs[$i]->tab_detail_1 == 2) ? "SCANNER" : "DECODER";

            $newTabId = migration_dbconnection::queryInsert("INSERT INTO tabs (game_id, type, sort_index, content_id, created) VALUES 
            ('{$v2GameId}','{$newType}','{$tabs[$i]->tab_index}','{$newDetail}',CURRENT_TIMESTAMP)", "v2");
            $tabIdMap[$tabs[$i]->tab] = $newTabId;

            //if tab is QR in mode BOTH, we need to create two tabs in v2. above should have created SCANNER, so this will create QR
            //(literally copied/pasted above 3 lines. so if they change, this must as well)
            if($tabs[$i]->tab == "QR" && $tabs[$i]->tab_detail_1 == 0)
            { 
                $newType = "DECODER";
                $newTabId = migration_dbconnection::queryInsert("INSERT INTO tabs (game_id, type, sort_index, content_id, created) VALUES 
                ('{$v2GameId}','{$newType}','{$tabs[$i]->tab_index}','{$newDetail}',CURRENT_TIMESTAMP)", "v2");
                $tabIdMap[$tabs[$i]->tab] = $newTabId;
            }
        }
        return $tabIdMap;
    }

    public function migrateRequirements($v1GameId, $v2GameId, $maps)
    {
        //no need to return map of any kind- nothing references v1 requirements

        //round up all v1 requirements into groups by type and by object
        $rGroupings = new stdClass;
        $rGroupings->dialogOptions = array();
        $rGroupings->questCompletes = array();
        $rGroupings->questDisplays = array();
        $rGroupings->triggers = array();
        $rGroupings->webhooks = array();
        $rGroupings->factories = array();
        $rGroupings->overlays = array();
        $rGroupings->tabs = array();
        $requirements = migration_dbconnection::queryArray("SELECT * FROM requirements WHERE game_id = '{$v1GameId}'","v1");
        $q = 0;
        for($i = 0; $i < count($requirements); $i++)
        {
            if($requirements[$i]->content_type == "Node")            $typeGroup = &$rGroupings->dialogOptions;
            if($requirements[$i]->content_type == "QuestDisplay")    $typeGroup = &$rGroupings->questDisplays;
            if($requirements[$i]->content_type == "QuestComplete")   $typeGroup = &$rGroupings->questCompletes;
            if($requirements[$i]->content_type == "Location")        $typeGroup = &$rGroupings->triggers;
            if($requirements[$i]->content_type == "OutgoingWebHook") $typeGroup = &$rGroupings->webhooks;
            if($requirements[$i]->content_type == "Spawnable")       $typeGroup = &$rGroupings->factories;
            if($requirements[$i]->content_type == "CustomMap")       $typeGroup = &$rGroupings->overlays;
            if($requirements[$i]->content_type == "Tab")             $typeGroup = &$rGroupings->tabs;

            if(!$typeGroup[$requirements[$i]->content_id]) $typeGroup[$requirements[$i]->content_id] = array();
            $typeGroup[$requirements[$i]->content_id][] = $requirements[$i];
        }

        foreach($rGroupings->dialogOptions as $optionId => $requirementsList)
        {
            $req_package_id = migration::migrateRequirementListIntoPackage($v2GameId, $requirementsList, $maps);
            migration_dbconnection::query("UPDATE dialog_options SET requirement_root_package_id = '{$req_package_id}' WHERE dialog_option_id = '{$maps->options[$optionId]}'","v2");
        }
        foreach($rGroupings->questCompletes as $questId => $requirementsList)
        {
            $req_package_id = migration::migrateRequirementListIntoPackage($v2GameId, $requirementsList, $maps);
            migration_dbconnection::query("UPDATE quests SET complete_requirement_root_package_id = '{$req_package_id}' WHERE quest_id = '{$maps->quests[$questId]}'","v2");
        }
        foreach($rGroupings->questDisplays as $questId => $requirementsList)
        {
            $req_package_id = migration::migrateRequirementListIntoPackage($v2GameId, $requirementsList, $maps);
            migration_dbconnection::query("UPDATE quests SET active_requirement_root_package_id = '{$req_package_id}' WHERE quest_id = '{$maps->quests[$questId]}'","v2");
        }
        foreach($rGroupings->triggers as $locationId => $requirementsList)
        {
            //I do this twice- once for the v2 location trigger that was generated for the v1 location...
            $req_package_id = migration::migrateRequirementListIntoPackage($v2GameId, $requirementsList, $maps);
            migration_dbconnection::query("UPDATE triggers SET requirement_root_package_id = '{$req_package_id}' WHERE trigger_id = '{$maps->locTriggers[$locationId]}'","v2");
            //and once for the v2 qr trigger that was generated for the v1 location
            $req_package_id = migration::migrateRequirementListIntoPackage($v2GameId, $requirementsList, $maps);
            migration_dbconnection::query("UPDATE triggers SET requirement_root_package_id = '{$req_package_id}' WHERE trigger_id = '{$maps->qrTriggers[$locationId]}'","v2");
        }
        foreach($rGroupings->webhooks as $webhookId => $requirementsList)
        {
            $req_package_id = migration::migrateRequirementListIntoPackage($v2GameId, $requirementsList, $maps);
            migration_dbconnection::query("UPDATE web_hooks SET requirement_root_package_id = '{$req_package_id}' WHERE overlay_id = '{$maps->webhooks[$webhookId]}'","v2");
        }
        foreach($rGroupings->factories as $factoryId => $requirementsList)
        {
            $req_package_id = migration::migrateRequirementListIntoPackage($v2GameId, $requirementsList, $maps);
            migration_dbconnection::query("UPDATE factories SET requirement_root_package_id = '{$req_package_id}' WHERE overlay_id = '{$maps->factories[$factoryId]}'","v2");
        }
        foreach($rGroupings->overlays as $overlayId => $requirementsList)
        {
            $req_package_id = migration::migrateRequirementListIntoPackage($v2GameId, $requirementsList, $maps);
            migration_dbconnection::query("UPDATE overlays SET requirement_root_package_id = '{$req_package_id}' WHERE overlay_id = '{$maps->overlays[$overlayId]}'","v2");
        }
        foreach($rGroupings->tabs as $tabId => $requirementsList)
        {
            $req_package_id = migration::migrateRequirementListIntoPackage($v2GameId, $requirementsList, $maps);
            migration_dbconnection::query("UPDATE tabs SET requirement_root_package_id = '{$req_package_id}' WHERE tab_id = '{$maps->tabs[$tabId]}'","v2");
        }
    }
    //helper for migraterequirements
    public function migrateRequirementListIntoPackage($gameId, $requirementsList, $maps)
    {
        $root_req_id = migration_dbconnection::queryInsert("INSERT INTO requirement_root_packages (game_id, created) VALUES ('{$gameId}',CURRENT_TIMESTAMP)","v2");

        //this is the ID that all 'AND' reqs will attatch to
        $and_group_req_id = migration_dbconnection::queryInsert("INSERT INTO requirement_and_packages (game_id, requirement_root_package_id, created) VALUES ('{$gameId}', '{$root_req_id}', CURRENT_TIMESTAMP)","v2");

        for($i = 0; $i < count($requirementsList); $i++)
        {
            $requirement = ""; $content_id = 0;
            if($requirementsList[$i]->requirement == "PLAYER_VIEWED_AUGBUBBLE") continue; //no longer valid
            if($requirementsList[$i]->requirement == "PLAYER_HAS_ITEM")                       { $requirement = "PLAYER_HAS_ITEM";                       $content_id = $maps->items[$requirementsList[$i]->requirement_detail_1]; }
            if($requirementsList[$i]->requirement == "PLAYER_HAS_TAGGED_ITEM")                { $requirement = "PLAYER_HAS_TAGGED_ITEM";                $content_id = $maps->items[$requirementsList[$i]->requirement_detail_1]; }
            if($requirementsList[$i]->requirement == "PLAYER_VIEWED_ITEM")                    { $requirement = "PLAYER_VIEWED_ITEM";                    $content_id = $maps->items[$requirementsList[$i]->requirement_detail_1];}
            if($requirementsList[$i]->requirement == "PLAYER_VIEWED_NODE")                    { if($maps->plaques[$requirementsList[$i]->requirement_detail_1]) { $requirement = "PLAYER_VIEWED_PLAQUE"; $content_id = $maps->plaques[$requirementsList[$i]->requirement_detail_1]; } else { $requirement = "PLAYER_VIEWED_DIALOG_SCRIPT"; $content_id = $maps->scripts[$requirementsList[$i]->requirement_detail_1]; } }
            if($requirementsList[$i]->requirement == "PLAYER_VIEWED_NPC")                     { $requirement = "PLAYER_VIEWED_DIALOG";                  $content_id = $maps->dialogs[$requirementsList[$i]->requirement_detail_1];}
            if($requirementsList[$i]->requirement == "PLAYER_VIEWED_WEBPAGE")                 { $requirement = "PLAYER_VIEWED_WEB_PAGE";                $content_id = $maps->webpages[$requirementsList[$i]->requirement_detail_1];}
            if($requirementsList[$i]->requirement == "PLAYER_HAS_UPLOADED_MEDIA_ITEM")        { $requirement = "PLAYER_HAS_UPLOADED_MEDIA_ITEM";        }
            if($requirementsList[$i]->requirement == "PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE")  { $requirement = "PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE";  }
            if($requirementsList[$i]->requirement == "PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO")  { $requirement = "PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO";  }
            if($requirementsList[$i]->requirement == "PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO")  { $requirement = "PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO";  }
            if($requirementsList[$i]->requirement == "PLAYER_HAS_COMPLETED_QUEST")            { $requirement = "PLAYER_HAS_COMPLETED_QUEST";            $content_id = $maps->quests[$requirementsList[$i]->requirement_detail_1];}
            if($requirementsList[$i]->requirement == "PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK") { $requirement = "PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK"; }
            if($requirementsList[$i]->requirement == "PLAYER_HAS_NOTE")                       { $requirement = "PLAYER_HAS_NOTE";                       }
            if($requirementsList[$i]->requirement == "PLAYER_HAS_NOTE_WITH_TAG")              { $requirement = "PLAYER_HAS_NOTE_WITH_TAG";              }
            if($requirementsList[$i]->requirement == "PLAYER_HAS_NOTE_WITH_LIKES")            { $requirement = "PLAYER_HAS_NOTE_WITH_LIKES";            }
            if($requirementsList[$i]->requirement == "PLAYER_HAS_NOTE_WITH_COMMENTS")         { $requirement = "PLAYER_HAS_NOTE_WITH_COMMENTS";         }
            if($requirementsList[$i]->requirement == "PLAYER_HAS_GIVEN_NOTE_COMMENTS")        { $requirement = "PLAYER_HAS_GIVEN_NOTE_COMMENTS";        }
                
            $parent_and = $and_group_req_id;
            if($requirementsList[$i]->boolean_operator == "OR")
                $parent_and = migration_dbconnection::queryInsert("INSERT INTO requirement_and_packages (game_id, requirement_root_package_id, created) VALUES ('{$gameId}', '{$root_req_id}', CURRENT_TIMESTAMP)","v2");

            migration_dbconnection::queryInsert("INSERT INTO requirement_atoms (game_id, requirement_and_package_id, bool_operator, requirement, content_id, distance, qty, latitude, longitude, created) VALUES ('{$gameId}', '{$parent_and}', '".($requirementsList[$i]->not_operator == "DO")."','{$requirement}','{$content_id}','{$requirementsList[$i]->requirement_detail_1}','{$requirementsList[$i]->requirement_detail_2}','{$requirementsList[$i]->requirement_detail_3}','{$requirementsList[$i]->requirement_detail_4}',CURRENT_TIMESTAMP)","v2");
        }

        return $root_req_id;
    }
}
?>
