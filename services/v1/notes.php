<?php
require_once("module.php");
require_once("media.php");
require_once("games.php");
require_once("locations.php");
require_once("players.php");
require_once("playerStateChanges.php");
require_once("editorFoldersAndContent.php");

class Notes extends Module
{
    //Returns note_id
    function createNewNote($gameId, $playerId, $lat=0, $lon=0)
    {
	$query = "INSERT INTO notes (game_id, owner_id, title) VALUES ('{$gameId}', '{$playerId}', 'New Note')";
	@mysql_query($query);
	if (mysql_error()) return new returnData(1, NULL, mysql_error());
	$nId = mysql_insert_id();
	EditorFoldersAndContent::saveContent($gameId, false, 0, 'PlayerNote', $nId, 0);
	Module::processGameEvent($playerId, $gameId, Module::kLOG_GET_NOTE, $nId);
	Players::dropNote($gameId, $playerId, $nId, $lat, $lon);
	return new returnData(0, $nId);
    }

    function updateNote($noteId, $title, $publicToMap, $publicToNotebook, $sortIndex='0')
    {
	$title = addslashes($title);
	$query = "UPDATE notes SET title = '{$title}', public_to_map = '{$publicToMap}', public_to_notebook = '{$publicToNotebook}', sort_index='{$sortIndex}' WHERE note_id = '{$noteId}'";
	@mysql_query($query);
	if (mysql_error()) return new returnData(1, NULL, mysql_error());

	$query = "SELECT * FROM notes WHERE note_id = '{$noteId}'";
	$result = mysql_query($query);
	$noteobj = mysql_fetch_object($result);

	$query = "UPDATE locations SET name = '{$title}' WHERE game_id = {$noteobj->game_id} AND type='PlayerNote' AND type_id = '{$noteId}'";
	@mysql_query($query);
	if (mysql_error()) return new returnData(1, NULL, mysql_error());

        return new returnData(0);
    }

    function addContentToNote($noteId, $gameId, $playerId, $mediaId, $type, $text, $title='', $lat=0, $lon=0)
    {
        $text = addslashes($text);
        if($title == '') $title = Date('F jS Y h:i:s A');
        else $title = addslashes($title);
        $query = "INSERT INTO note_content (note_id, game_id, media_id, type, text, title) VALUES ('{$noteId}', '{$gameId}', '{$mediaId}', '{$type}', '{$text}', '{$title}')";
        $result = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        $contentId = mysql_insert_id();

        Module::processGameEvent($playerId, $gameId, Module::kLOG_UPLOAD_MEDIA_ITEM, $contentId, $lat, $lon);
        if($type == "PHOTO"){
            Module::processGameEvent($playerId, $gameId, Module::kLOG_UPLOAD_MEDIA_ITEM_IMAGE, $contentId, $lat, $lon);
        }
        else if($type == "AUDIO"){
            Module::processGameEvent($playerId, $gameId, Module::kLOG_UPLOAD_MEDIA_ITEM_AUDIO, $contentId, $lat, $lon);
        }
        else if($type == "VIDEO"){
            Module::processGameEvent($playerId, $gameId, Module::kLOG_UPLOAD_MEDIA_ITEM_VIDEO, $contentId, $lat, $lon);
        }
        return new returnData(0, $contentId);
    }

    function addContentToNoteFromFileName($gameId, $noteId, $playerId, $filename, $type, $name="playerUploadedContent")
    {
        if(!$name)
            $name = date("Y-m-d H:i:s");
        $newMediaResultData = Media::createMedia($gameId, $name, $filename, 0);
        $newMediaId = $newMediaResultData->data->media_id;

        return Notes::addContentToNote($noteId, $gameId, $playerId, $newMediaId, $type, "", "");
    }

    function updateContent($contentId, $text)
    {
        $text = addslashes($text);
        $query = "UPDATE note_content SET text='{$text}' WHERE content_id='{$contentId}'";
        @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        return new returnData(0);
    }

    function deleteNoteContent($contentId)
    {
        $query = "DELETE FROM note_content WHERE content_id = '{$contentId}'";
        @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        return new returnData(0);
    }

    function updateContentTitle($contentId, $title)
    {
        $title = addslashes($title);
        $query = "UPDATE note_content SET title='{$title}' WHERE content_id='{$contentId}'";
        @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        return new returnData(0);
    }

    function addCommentToNote($gameId, $playerId, $noteId, $title="New Comment")
    {
        $query = "SELECT owner_id FROM notes WHERE game_id = '{$gameId}' AND note_id = '{$noteId}'";
        $result = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        $questownerobj = mysql_fetch_object($result);
        $questowner = $questownerobj->owner_id;

        Module::processGameEvent($playerId, $gameId, Module::kLOG_GIVE_NOTE_COMMENT, $noteId);

        $query = "INSERT INTO notes (game_id, owner_id, parent_note_id, title) VALUES ('{$gameId}', '{$playerId}', '{$noteId}', '{$title}')";
        $result = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        $commentId = mysql_insert_id();

        return new returnData(0, $commentId);
    }

    function updateComment($noteId, $title)
    {
        $title = addslashes($title);
        $query = "UPDATE notes SET title= '{$title}' WHERE note_id = '{$noteId}'";
        $result = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        return new returnData(0, $newAve);	
    }

    //Gets all notes accessible through the notebook by an arbitrary player
    function getNotesForGame($gameId, $playerId) 
    {
        $query = "SELECT note_id FROM notes WHERE game_id = '{$gameId}' AND parent_note_id = '0' AND (public_to_notebook = '1' OR public_to_map = '1')";
        $result = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());

        $notes = array();
        while($note = mysql_fetch_object($result))
        {
            $notes[] = Notes::getFullNoteObject($note->note_id, $playerId);
        }
        return new returnData(0, $notes);
    }

    //Gets an individual's notes. 
    function getNotesForPlayer($playerId, $gameId)
    {
        $query = "SELECT note_id FROM notes WHERE owner_id = '{$playerId}' AND game_id = '{$gameId}' AND parent_note_id = 0 ORDER BY sort_index ASC";
        $result = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());

        $notes = array();
        while($note = mysql_fetch_object($result))
        {
            $notes[] = Notes::getFullNoteObject($note->note_id, $playerId);
        }

        return new returnData(0, $notes);
    }

    function getNoteById($noteId, $playerId=0)
    {
        $note = Notes::getFullNoteObject($noteId, $playerId);
        return new returnData(0, $note);
    }

    function getFullNoteObject($noteId, $playerId=0)
    {
        $query = "SELECT * FROM notes WHERE note_id = '{$noteId}'";
        $result = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        if($note = mysql_fetch_object($result))
        {
            $query = "SELECT user_name FROM players WHERE player_id = '{$note->owner_id}'";
            $player = mysql_query($query);
            $playerObj = mysql_fetch_object($player);
            $note->username = $playerObj->user_name;
            $note->contents = Notes::getNoteContents($noteId, $note->game_id);
            $note->comments = Notes::getNoteComments($noteId, $playerId);
            $note->tags = Notes::getNoteTags($noteId, $note->game_id);
            $note->likes = Notes::getNoteLikes($noteId);
            $note->player_liked = ($playerId == 0 ? 0 : Notes::playerLiked($playerId, $noteId));
            $note->icon_media_id = 5;
            if($note->dropped = Notes::noteDropped($noteId, $note->game_id))
                $location = Notes::getNoteLocation($noteId, $note->game_id);	
            else
            {
                $location = new stdClass();
                $location->lat = $location->lon = 0;
            }
            $note->lat = $location->lat;
            $note->lon = $location->lon;
            return $note;
        }
        return;
    }

    function getNoteContents($noteId, $gameId)
    {
        $query = "SELECT * FROM note_content WHERE note_id = '{$noteId}'";
        $result = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());

        $contents = array();
        while($content = mysql_fetch_object($result))
        {
            $content->media = Media::getMediaObject($gameId, $content->media_id);
            $contents[] = $content;
        }

        return $contents;
    }

    function getNoteComments($noteId, $playerId)
    {
        $query = "SELECT note_id FROM notes WHERE parent_note_id = '{$noteId}'";
        $result = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());

        $comments = array();
        while($commentNoteId = mysql_fetch_object($result))
        {
            $comment = Notes::getFullNoteObject($commentNoteId->note_id, $playerId);
            $comments[] = $comment;
        }
        return $comments;
    }

    //Gets list of all tags owned by a note
    //Array of json objects:
    //	tag = 'my tag'
    //	tag_id = 4
    //	player_created = 0 (if 0, created by game author, and potentially used in requirements. if 1, created by some player)
    function getNoteTags($noteId, $gameId)
    {
        $query = "SELECT gt.tag, gt.tag_id, gt.player_created FROM note_tags LEFT JOIN ((SELECT tag, tag_id, player_created FROM game_tags WHERE game_id = '{$gameId}') as gt) ON note_tags.tag_id = gt.tag_id WHERE note_id = '{$noteId}'";
        $result = mysql_query($query);
        $tags = array();
        while($tag = mysql_fetch_object($result))	
            $tags[] = $tag;
        return $tags;
    }

    function getGameTag($tagId)
    {
        $query = "SELECT * FROM game_tags WHERE tag_id = '{$tagId}'";
        $result = mysql_query($query);
        if($tag = mysql_fetch_object($result))
            return new returnData(0,$tag);
        else
            return new returnData(1,NULL,"Tag Not Found");
    }

    function getNoteLikes($noteId)
    {
        $query = "SELECT COUNT(*) as numLikes FROM note_likes WHERE note_id = '{$noteId}'";
        $result  = mysql_query($query);
        $likes = mysql_fetch_object($result);
        return $likes->numLikes;
    }

    function playerLiked($playerId, $noteId)
    {
        $query = "SELECT COUNT(*) as liked FROM note_likes WHERE player_id = '{$playerId}' AND note_id = '{$noteId}' LIMIT 1";
        $result = mysql_query($query);
        $liked = mysql_fetch_object($result);
        return $liked->liked;
    }

    function noteDropped($noteId, $gameId)
    {
	$query = "SELECT * FROM locations WHERE game_id = {$gameId} AND type='PlayerNote' AND type_id='{$noteId}' LIMIT 1";
        $result = mysql_query($query);

        if(mysql_num_rows($result) > 0)
            return true;
        else
            return false;
    }

    function getNoteLocation($noteId, $gameId)
    {
	$query = "SELECT * FROM locations WHERE game_id = {$gameId} AND type='PlayerNote' AND type_id='{$noteId}' LIMIT 1";
        $result = mysql_query($query);
        $locObj = mysql_fetch_object($result);
        $retLoc = new stdClass();
        $retLoc->lat = $locObj->latitude;
        $retLoc->lon = $locObj->longitude;
        return $retLoc;
    }

    function deleteNote($noteId)
    {
        //If noteId is 0, it will rather elegantly delete EVERYTHING in the note database 
        //becasue 0 is used for the parent_id of all new notes
        if($noteId == 0) return new returnData(0);

        $query = "SELECT * FROM notes WHERE note_id = '{$noteId}' LIMIT 1";
      
    $query = "DELETE FROM note_tags WHERE note_id = '{$noteId}'";
    mysql_query($query);
      
    $query = "DELETE FROM note_likes WHERE note_id = '{$noteId}'";
    mysql_query($query);

        $result = @mysql_query($query);
        $noteObj = mysql_fetch_object($result);

        $query = "SELECT note_id FROM notes WHERE parent_note_id = '{$noteId}'";
        $result = @mysql_query($query);

        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        while($commentNote = mysql_fetch_object($result))
            Notes::deleteNote($commentNote->note_id);
        //Delete Note locations
        Locations::deleteLocationsForObject($noteObj->game_id, "PlayerNote", $noteId);
        //Delete the folder record
        //EditorFolderContents::deleteContent($noteObj->game_id, "PlayerNote", $noteId); //This would cause an infinite loop becasue it deletes the note
	$query = "DELETE FROM folder_contents WHERE game_id = {$noteObj->game_id} AND content_type = 'PlayerNote' AND content_id = '{$noteId}'";
        mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        //Delete the Note's Content
        $query = "DELETE FROM note_content WHERE note_id = '{$noteId}'";
        @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        $query = "DELETE FROM note_tags WHERE note_id = '{$noteId}'";
        mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        $query = "DELETE FROM note_likes WHERE note_id = '{$noteId}'";
        mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        //Delete the Note itself
        $query = "DELETE FROM notes WHERE note_id = '{$noteId}'";
        @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error()); 
        return new returnData(0);
    }

    //Gets all tags in game (NOT note/tag pairs), regardless of how created
    //Array of json objects
    //	tag = 'my tag'
    //	tag_id = 4
    //	player_created = 0 (0 means tag created by game author, 1 means created by some player, and is instantiated at least once in a note in game)
    function getGameTags($gameId)
    {
        $query = "SELECT tag_id, tag, player_created from game_tags WHERE game_id = '{$gameId}'";
        $result = mysql_query($query);
        $tags = array();
        while($tag = mysql_fetch_object($result))	
            $tags[] = $tag;
        return $tags;
    }

    function getAllTagsInGame($gameId)
    {
        $tags = Notes::getGameTags($gameId);
        return new returnData(0, $tags);
    }

    function addTagToNote($noteId, $gameId, $tag)
    {
        //Check if tag exists for game
        $query = "SELECT tag_id FROM game_tags WHERE game_id = '{$gameId}' AND tag = '{$tag}' LIMIT 1";
        $result = mysql_query($query);
        $id = mysql_fetch_object($result);

        //If not
        if(!$id->tag_id)
        {
            //Make sure it is ok for player to create tag for game
            $query = "SELECT allow_player_tags FROM games WHERE game_id='{$gameId}'";	
            $result = mysql_query($query);
            $allow = mysql_fetch_object($result);
            if($allow->allow_player_tags != 1)
                //Player not allowed to create own tag
                return new returnData(1, NULL, "Player Generated Tags Not Allowed In This Game");	

            //Create tag for game
            $query = "INSERT INTO game_tags (tag, game_id, player_created) VALUES ('{$tag}', '{$gameId}', 1)";
            mysql_query($query);
            $id->tag_id = mysql_insert_id();
        }

        //Apply tag to note
        $query = "INSERT INTO note_tags (note_id, tag_id) VALUES ('{$noteId}', '{$id->tag_id}')";
        mysql_query($query);

        return new returnData(0, $id->tag_id);
    }

    function deleteTagFromNote($noteId, $tagId)
    {
        $query = "DELETE FROM note_tags WHERE note_id = '{$noteId}' AND tag_id = '{$tagId}'";
        mysql_query($query);

        $query = "SELECT * FROM note_tags WHERE tag_id = '{$tagId}'";
        $result = mysql_query($query);
        if(mysql_num_rows($result) == 0)
        {
            //Deleting a tag from a note can only delete the tag from the game if it is the last instantiation of that tag, and the tag was player created
            $query = "DELETE FROM game_tags WHERE tag_id = '{$tagId}' AND player_created = 1";
            mysql_query($query);
        }
        return new returnData(0);
    }

    //Returns new id
    function addTagToGame($gameId, $tag)
    {
        $query = "INSERT INTO game_tags (tag, game_id, player_created) VALUES ('{$tag}', '{$gameId}', 0)";
        mysql_query($query);
        return new returnData(0, mysql_insert_id());
    }

    //If author created, demotes to player created. completely wipes if player created. player created tags cannot exist if not instantiated at least once.
    function deleteTagFromGame($gameId, $tagId)
    {
        $query = "SELECT * FROM game_tags WHERE tag_id = '{$tagId}'"; 
        $result = mysql_query($query);
        $tag = mysql_fetch_object($result);
        if($tag->player_created == 1)
        {
            //Completely wipe from game
            $query = "DELETE FROM game_tags WHERE tag_id = '{$tagId}'";
            mysql_query($query);
            $query = "DELETE FROM note_tags WHERE tag_id = '{$tagId}'";
            mysql_query($query);
        }
        else
        {
            //Checks to see if instantiated at least once (a necessary property of player_created notes)
            $query = "SELECT note_id FROM note_tags WHERE tag_id = '{$tagId}'";
            $result = mysql_query($query);
            if(mysql_num_rows($result) > 0) //Exists at least once- just demote
            {
                $query = "UPDATE game_tags SET player_created = 1 WHERE tag_id = '{$tagId}'";
                $result = mysql_query($query);
            }
            else //isn't instantiated- wipe it
            {
                $query = "DELETE FROM game_tags WHERE tag_id = '{$tagId}'";
                mysql_query($query);
                $query = "DELETE FROM note_tags WHERE tag_id = '{$tagId}'";
                mysql_query($query);
            }
        }
        return new returnData(0);
    }

    function likeNote($playerId, $noteId)
    {
        $query = "SELECT owner_id, game_id FROM notes WHERE note_id = '{$noteId}'";
        $result = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        $questownerobj = mysql_fetch_object($result);
        $questowner = $questownerobj->owner_id;
        $gameId = $questownerobj->game_id;

        Module::processGameEvent($playerId, $gameId, Module::kLOG_GIVE_NOTE_LIKE, $noteId);

        $query = "INSERT INTO note_likes (player_id, note_id) VALUES ('{$playerId}', '{$noteId}')";
        mysql_query($query);

        return new returnData(0);
    }

    function unlikeNote($playerId, $noteId)
    {
        $query = "DELETE FROM note_likes WHERE player_id = '{$playerId}' AND note_id = '{$noteId}'";
        mysql_query($query);
        return new returnData(0);
    }

    //TEMPORARY FUNCTION LOCATION
    function createFossilNoteDatabaseForGame($gameId)
    {
        $jsonRaw='
            [
            {
                "lat" : "43.613609",
                    "enterer" : "M. Sommers",
                    "collection_name" : "Isotelus-Diplograptus community, Maquoketa Fm., Upper Iowa River, Minnesota",
                    "lng" : "-92.427780"
            },
            {
                "lat" : "43.016945",
                "enterer" : "M. Sommers",
                "collection_name" : "Halquist Quarry #1",
                "lng" : "-88.223053"
            },
            {
                "lat" : "43.016945",
                "enterer" : "M. Sommers",
                "collection_name" : "Halquist Quarry #2",
                "lng" : "-88.223053"
            },
            {
                "lat" : "43.054169",
                "enterer" : "J. Alroy",
                "collection_name" : "Rockford (Ager)",
                "lng" : "-92.958336"
            },
            {
                "lat" : "43.054169",
                "enterer" : "J. Alroy",
                "collection_name" : "Rockford (Guber)",
                "lng" : "-92.958336"
            },
            {
                "lat" : "43.054169",
                "enterer" : "J. Alroy",
                "collection_name" : "Rockford (Mallory)",
                "lng" : "-92.958336"
            },
            {
                "lat" : "42.416668",
                "enterer" : "M. Sommers",
                "collection_name" : "Elizabeth - Galena area (Illinois)",
                "lng" : "-90.428886"
            },
            {
                "lat" : "43.445557",
                "enterer" : "M. Uhen",
                "collection_name" : "Jolman Site",
                "lng" : "-85.969719"
            },
            {
                "lat" : "43.535278",
                "enterer" : "M. Uhen",
                "collection_name" : "Huls Site",
                "lng" : "-86.288330"
            },
            {
                "lat" : "43.994720",
                "enterer" : "M. Uhen",
                "collection_name" : "Ludington State Park",
                "lng" : "-86.478615"
            },
            {
                "lat" : "43.285557",
                "enterer" : "M. Uhen",
                "collection_name" : "Shaw Farm Site",
                "lng" : "-85.780830"
            },
            {
                "lat" : "43.257221",
                "enterer" : "M. Uhen",
                "collection_name" : "McKay Site",
                "lng" : "-85.978058"
            },
            {
                "lat" : "43.550278",
                "enterer" : "M. Uhen",
                "collection_name" : "White Cloud, near",
                "lng" : "-85.771942"
            },
            {
                "lat" : "43.532501",
                "enterer" : "M. Uhen",
                "collection_name" : "Freemont, NW of",
                "lng" : "-86.029724"
            },
            {
                "lat" : "43.589169",
                "enterer" : "M. Uhen",
                "collection_name" : "Ferry",
                "lng" : "-86.235275"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Spechts Ferry Formation, Iowa and Wisconsin",
                "lng" : "-90.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Quimbys Mill Formation, Iowa and Wisconsin",
                "lng" : "-90.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Grand Detour Formation, Iowa and Wisconsin",
                "lng" : "-90.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Mifflin Formation, Iowa and Wisconsin",
                "lng" : "-90.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Pecatonica Formation, Iowa and Wisconsin",
                "lng" : "-90.000000"
            },
            {
                "lat" : "43.038891",
                "enterer" : "M. Sommers",
                "collection_name" : "Milwaukee Metropolitan Sewerage District Dropshafts KK-1 and LM-S, Racine Fm., W",
                "lng" : "-87.906387"
            },
            {
                "lat" : "43.707222",
                "enterer" : "Z. Krug",
                "collection_name" : "Virgiana community, Mayville Fm, 30121 - Patzkowsky",
                "lng" : "-88.383614"
            },
            {
                "lat" : "43.707222",
                "enterer" : "Z. Krug",
                "collection_name" : "Virgiana community, Mayville Fm, 30228 - Patzkowsky",
                "lng" : "-88.383614"
            },
            {
                "lat" : "43.494446",
                "enterer" : "Z. Krug",
                "collection_name" : "Virgiana community, Mayville Fm, 30282 - Patzkowsky",
                "lng" : "-88.544724"
            },
            {
                "lat" : "43.494446",
                "enterer" : "Z. Krug",
                "collection_name" : "Virgiana community, Mayville Fm, 34771 - Patzkowsky",
                "lng" : "-88.544724"
            },
            {
                "lat" : "43.776669",
                "enterer" : "Z. Krug",
                "collection_name" : "Pentamerus community, Waukesha Fm, 30280 - Patzkowsky",
                "lng" : "-88.446945"
            },
            {
                "lat" : "42.726112",
                "enterer" : "Z. Krug",
                "collection_name" : "Racine Fm, SE Wisconsin, Horlick Quarry - Patzkowsky",
                "lng" : "-87.782776"
            },
            {
                "lat" : "42.726112",
                "enterer" : "Z. Krug",
                "collection_name" : "Racine Fm, SE Wisconsin, Ives Quarry - Patzkowsky",
                "lng" : "-87.782776"
            },
            {
                "lat" : "42.880001",
                "enterer" : "Z. Krug",
                "collection_name" : "Racine Fm, SE Wisconsin, Franklin Quarry - Patzkowsky",
                "lng" : "-88.038330"
            },
            {
                "lat" : "43.005554",
                "enterer" : "K. Koverman",
                "collection_name" : "Eucalyptocrinites-Crotalocrinites-Caryocrinites Association of the Racine Format",
                "lng" : "-88.376389"
            },
            {
                "lat" : "42.716667",
                "enterer" : "M. Foote",
                "collection_name" : "Bumastus (Bumastus) ioxus Subassociation, Horlick Quarry, Racine",
                "lng" : "-87.783333"
            },
            {
                "lat" : "43.389000",
                "enterer" : "A. Stigall",
                "collection_name" : "Milwaukee",
                "lng" : "-87.906403"
            },
            {
                "lat" : "43.830002",
                "enterer" : "A. Stigall",
                "collection_name" : "Milwaukee",
                "lng" : "-87.932999"
            },
            {
                "lat" : "43.830002",
                "enterer" : "A. Stigall",
                "collection_name" : "Milwaukee",
                "lng" : "-87.932999"
            },
            {
                "lat" : "43.389000",
                "enterer" : "A. Stigall",
                "collection_name" : "Milwaukee County, Milwaukee Formation",
                "lng" : "-87.906403"
            },
            {
                "lat" : "43.483334",
                "enterer" : "H. Street",
                "collection_name" : "Harrington Beach State Park, Lake Church",
                "lng" : "-87.800003"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Wise Lake Formation, Sinsinawa Member Echinoderms, Upper Mississippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Wise Lake Formation, Rifle Hill Formation Echinoderms, Upper Mississippi Valle",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dubuque Formation Echinoderms, Upper Mississippi Valley (Illinois, Iowa)",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Scales Shale, Elgin Member Echinoderms, Upper Mississippi Valley (Illinois Iowa)",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Scales Shale, Clermont Member Echinoderms, Upper Mississippi Valley (IL, IA)",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Brainard Shale Echinoderms, Upper Mississippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Neda Formation Echinoderms, Upper Mississippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Mifflin Formation Echinoderms of the Upper Mississippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Glenwood Formation Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Pecatonica Formation Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Mifflin Formation Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Grand Detour Formation Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Nachusa Formation Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Quimbys Mill Formation Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Spechts Ferry Formation Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Guttenberg Formation Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "44.000000",
                "enterer" : "P. Hearn",
                "collection_name" : "Unit 8, Fort Atkinson Formation",
                "lng" : "-88.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Pecatonica Formation Echinoderms, Upper Mississippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Ion Formation, St. James Member Gastropoda and Monoplacophora, IL and IA",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Fm, Beecher Member Member Gastropoda and Monoplacophophora, IL and IA",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Fm, Eagle Point Member Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Fm, Fairplay Member Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Fm, Mortimer Member Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Fm, Rivoli Member Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Fm, Sherwood Member Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Fm, Wall Member Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "41.983334",
                "enterer" : "M. Foote",
                "collection_name" : "Trilobites of bryozoan-rich bioherm flank beds, Hopkinton Dolo.,Clinton Co.",
                "lng" : "-90.733330"
            },
            {
                "lat" : "42.999168",
                "enterer" : "Z. Krug",
                "collection_name" : "Pentamerus community, Waukesha Fm, 34720 - Patzkowsky",
                "lng" : "-89.533058"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Formation, Wall Member Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Formation, Wyota Member Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Wise Lake Formation, Sinsinawa Member Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Wise Lake Formation, Rifle Hill Member Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dubuque Formation Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Scales Shale, Elgin Member Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Scales Shale, Clermont Member Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Fort Atkinson Limestone Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Grand Detour Formation Echinoderms, Upper Mississippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Wise Lake Fm, Sinsinawa Member Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Wise Lake Fm, Rifle Hill Member Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dubuque Formation Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Scales Shale, Elgin Member Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Scales Shale, Clermont Member Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Glenwood Formation, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Pecatonica Formation, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Mifflin Formation, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.250000",
                "enterer" : "M. Foote",
                "collection_name" : "Dalmanella-Eospirigerina Community, Mosalem Formation, Jackson Co., Iowa",
                "lng" : "-90.416664"
            },
            {
                "lat" : "42.250000",
                "enterer" : "M. Foote",
                "collection_name" : "Lingula-Orbiculoid Community, Mosalem Formation, Jackson County, Iowa",
                "lng" : "-90.416664"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Nachusa Formation Echinoderms, Upper Mississippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Nachusa Formation, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Quimbys Mill Formation, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Spechts Ferry Formation, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Guttenberg Formation, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Ion Formation, Buckhorn Member, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Ion Formation, St. James Member, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Dunleith Formation, Beecher Member, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Dunleith Formation, Eagle Point Member, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "41.900002",
                "enterer" : "M. Foote",
                "collection_name" : "Ferganella Community, Behr Quarry, Welton, Iowa.",
                "lng" : "-90.599998"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Quimbys Mill Formation Echinoderms, Upper Mississippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Dunleith Formation, Mortimer Member, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Dunleith Formation, Rivoli Member, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Dunleith Formation, Sherwood Member, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Dunleith Formation, Wall Member, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Dunleith Formation, Wyota Member, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Wise Lake Formation, Sinsinawa Member, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Wise Lake Formation, Rifle Hill Member, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Dubuque Formation, Illinois, Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.150002",
                "enterer" : "M. Foote",
                "collection_name" : "Pentameroides-\'Costistricklandia\' castellana Comm., Jackson Co., Iowa",
                "lng" : "-90.683334"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Spechts Ferry Formation Echinoderms, Upper Mississippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Scales Shale, Clermont Member, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Bryozoan-Dicoelosia Community, Scotch Grove Formation, Iowa",
                "lng" : "-91.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "M. Foote",
                "collection_name" : "\'Costistricklandia\' castellana-Eospirifer Community, eastern Iowa",
                "lng" : "-91.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Hedeina-Gypidulid Community, upper Scotch Grove Fm., eastern Iowa",
                "lng" : "-91.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Pentameroides subrectus Community, Johns Creek Quarry Member, eastern Iowa",
                "lng" : "-91.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Pentameroides subrectus Community, Buck Creek Quarry Member, eastern Iowa",
                "lng" : "-91.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Pentameroides subrectus Community, Scotch Grove Formation, eastern Iowa",
                "lng" : "-91.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Pentamerus oblongus Comm., Marcus Member, eastern Iowa",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Trilobites of the Sthenarocalymene celebra Assoc., Milwaukee Co., Wisconsin",
                "lng" : "-88.000000"
            },
            {
                "lat" : "43.049999",
                "enterer" : "K. Layou",
                "collection_name" : "Rockford Brick and Tile Company pit, Lime Creek Formation,Iowa",
                "lng" : "-92.966667"
            },
            {
                "lat" : "43.049999",
                "enterer" : "K. Layou",
                "collection_name" : "Rockford Brick and Tile Company pit, Lime Creek Formation,Iowa",
                "lng" : "-92.966667"
            },
            {
                "lat" : "43.049999",
                "enterer" : "K. Layou",
                "collection_name" : "Rockford brick and Tile Company pit, Lime Creek Formation, Iowa",
                "lng" : "-92.966667"
            },
            {
                "lat" : "43.049999",
                "enterer" : "K. Layou",
                "collection_name" : "Rockford brick and Tile Company pit, Lime Creek Formation, Iowa",
                "lng" : "-92.966667"
            },
            {
                "lat" : "43.049999",
                "enterer" : "K. Layou",
                "collection_name" : "Rockford brick and Tile Company pit, Lime Creek Formation, Iowa",
                "lng" : "-92.966667"
            },
            {
                "lat" : "43.049999",
                "enterer" : "K. Layou",
                "collection_name" : "Rockford brick and Tile Company pit, Lime Creek Formation, Iowa",
                "lng" : "-92.966667"
            },
            {
                "lat" : "43.049999",
                "enterer" : "K. Layou",
                "collection_name" : "Rockford brick and Tile Company pit, Lime Creek Formation, Iowa",
                "lng" : "-92.966667"
            },
            {
                "lat" : "43.009167",
                "enterer" : "K. Layou",
                "collection_name" : "Roseville (Locality 1), Floyd Co.",
                "lng" : "-92.809723"
            },
            {
                "lat" : "43.016666",
                "enterer" : "T. Hanson",
                "collection_name" : "Glenwood, Platteville, and Decorah Conodonts of Iowa",
                "lng" : "-91.183334"
            },
            {
                "lat" : "43.049999",
                "enterer" : "K. Layou",
                "collection_name" : "Rockford Brick and Tile Company pit, Lime Creek Formation,Iowa",
                "lng" : "-92.966667"
            },
            {
                "lat" : "43.049999",
                "enterer" : "K. Layou",
                "collection_name" : "Rockford Brick and Tile Company pit, Lime Creek Formation,Iowa",
                "lng" : "-92.966667"
            },
            {
                "lat" : "43.049999",
                "enterer" : "K. Layou",
                "collection_name" : "Rockford Brick and Tile Company pit, Lime Creek Formation,Iowa",
                "lng" : "-92.966667"
            },
            {
                "lat" : "43.049999",
                "enterer" : "K. Layou",
                "collection_name" : "Rockford Brick and Tile Company pit, Lime Creek Formation,Iowa",
                "lng" : "-92.966667"
            },
            {
                "lat" : "43.049999",
                "enterer" : "K. Layou",
                "collection_name" : "Rockford Brick and Tile Company pit, Lime Creek Formation,Iowa",
                "lng" : "-92.966667"
            },
            {
                "lat" : "43.054169",
                "enterer" : "J. Alroy",
                "collection_name" : "Rockford (House)",
                "lng" : "-92.958336"
            },
            {
                "lat" : "43.043056",
                "enterer" : "Z. Krug",
                "collection_name" : "Apopentamerus community, Racine Fm, 34642 - Patzkowsky",
                "lng" : "-87.906387"
            },
            {
                "lat" : "43.043056",
                "enterer" : "Z. Krug",
                "collection_name" : "Apopentamerus community, Racine Fm, 34643 - Patzkowsky",
                "lng" : "-87.906387"
            },
            {
                "lat" : "43.043056",
                "enterer" : "Z. Krug",
                "collection_name" : "Apopentamerus community, Racine Fm, 34655 - Patzkowsky",
                "lng" : "-87.906387"
            },
            {
                "lat" : "43.051666",
                "enterer" : "Z. Krug",
                "collection_name" : "Racine Fm, Milwaukee, Hartung Quarry, 34504 - Patzkowsky",
                "lng" : "-88.007500"
            },
            {
                "lat" : "43.051666",
                "enterer" : "Z. Krug",
                "collection_name" : "Racine Fm, Milwaukee, Hartung Quarry, 34507 - Patzkowsky",
                "lng" : "-88.007500"
            },
            {
                "lat" : "43.051666",
                "enterer" : "Z. Krug",
                "collection_name" : "Racine Fm, Milwaukee, Hartung Quarry, 34512 - Patzkowsky",
                "lng" : "-88.007500"
            },
            {
                "lat" : "43.051666",
                "enterer" : "Z. Krug",
                "collection_name" : "Racine Fm, Milwaukee, Hartung Quarry, 34544 - Patzkowsky",
                "lng" : "-88.007500"
            },
            {
                "lat" : "43.051666",
                "enterer" : "Z. Krug",
                "collection_name" : "Racine Fm, Milwaukee, Hartung Quarry, 34547 - Patzkowsky",
                "lng" : "-88.007500"
            },
            {
                "lat" : "43.051666",
                "enterer" : "Z. Krug",
                "collection_name" : "Racine Fm, Milwaukee, Currie Park Quarry, 34601 - Patzkowsky",
                "lng" : "-88.007500"
            },
            {
                "lat" : "43.051666",
                "enterer" : "Z. Krug",
                "collection_name" : "Racine Fm, Milwaukee, Currie Park Quarry, 34602 - Patzkowsky",
                "lng" : "-88.007500"
            },
            {
                "lat" : "43.043056",
                "enterer" : "Z. Krug",
                "collection_name" : "Racine Fm, Milwaukee, Story Quarry, 34611 - Patzkowsky",
                "lng" : "-87.906387"
            },
            {
                "lat" : "43.051666",
                "enterer" : "Z. Krug",
                "collection_name" : "Racine Fm, SE Wisconsin, Francey Quarry - Patzkowsky",
                "lng" : "-88.007500"
            },
            {
                "lat" : "43.049999",
                "enterer" : "M. Foote",
                "collection_name" : "Bumastus (Cybantyx) cuniculus Subassociation, Schoonmaker Quarry, Wauwatosa",
                "lng" : "-88.016670"
            },
            {
                "lat" : "43.003334",
                "enterer" : "M. Sommers",
                "collection_name" : "Eastern molluscan dominated biofacies, Platteville Fm.",
                "lng" : "-91.157219"
            },
            {
                "lat" : "43.003334",
                "enterer" : "M. Sommers",
                "collection_name" : "Western brachiopod dominated biofacies, Platteville Fm., Minn/Wisc/Iowa/Ill",
                "lng" : "-91.157219"
            },
            {
                "lat" : "43.283333",
                "enterer" : "K. Koverman",
                "collection_name" : "Porocrinus pentagonius-Carabocrinus Association of the Wise Lake Formation at Bu",
                "lng" : "-91.849998"
            },
            {
                "lat" : "43.283333",
                "enterer" : "K. Koverman",
                "collection_name" : "Porocrinus fayettensis-Pleurocystites beckeri-Sygcaulocrinus typus Community at",
                "lng" : "-91.849998"
            },
            {
                "lat" : "42.936390",
                "enterer" : "K. Koverman",
                "collection_name" : "Diamphidiocystis-Bucanopsis Community Of the Brainard Formation",
                "lng" : "-91.705002"
            },
            {
                "lat" : "42.936390",
                "enterer" : "K. Koverman",
                "collection_name" : "Thaerodonta-Cupulocrinus angustus-Cornulites Community of the Brainard",
                "lng" : "-91.705002"
            },
            {
                "lat" : "42.936390",
                "enterer" : "K. Koverman",
                "collection_name" : "Thaerodonta-Cupulocrinus angustus-Cornulites Community of the Brainard",
                "lng" : "-91.705002"
            },
            {
                "lat" : "43.283333",
                "enterer" : "K. Koverman",
                "collection_name" : "Hindia parva?-Sphenothallus-Apycnodiscus Communityof the Elgin",
                "lng" : "-91.366669"
            },
            {
                "lat" : "43.500000",
                "enterer" : "T. Hanson",
                "collection_name" : "Cummingsville Formation Bryozoans of the Upper Mississippi Valley",
                "lng" : "-91.500000"
            },
            {
                "lat" : "43.299999",
                "enterer" : "Z. Krug",
                "collection_name" : "Platteville Fm, Wisconsin",
                "lng" : "-91.783333"
            },
            {
                "lat" : "42.008331",
                "enterer" : "M. Sommers",
                "collection_name" : "Basal Maquoketa fm., basal Richmondian, eastern Iowa",
                "lng" : "-91.643890"
            },
            {
                "lat" : "42.500000",
                "enterer" : "T. Hanson",
                "collection_name" : "Galena Dolomite Pelecypods, Upper Mississippi Valley",
                "lng" : "-92.500000"
            },
            {
                "lat" : "42.633331",
                "enterer" : "M. Foote",
                "collection_name" : "Flabellitesia Community, Blanding Fm., Fairbank Quarry, Fayette Co., Iowa",
                "lng" : "-92.050003"
            },
            {
                "lat" : "42.016666",
                "enterer" : "M. Foote",
                "collection_name" : "Harpidium (Isovella)-Trimerella Community, lower Gower Formation, Cedar Rapids",
                "lng" : "-91.650002"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Guttenberg Formation, Iowa and Wisconsin",
                "lng" : "-90.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Guttenberg Formation Echinoderms, Upper Mississippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Ion Formarion, Buckhorn Member Echinoderms, Upper MIssissippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Ion Formation, St. James Member Echinoderms, Upper Mississippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Formation, Beecher Member Echinoderms, Upper Mississippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Formation, Eagle Point Member Echinoderms, Upper Mississippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Formation, Fairplay Member Echioderms, Upper Mississippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Formation, Mortimer Member Echinoderms, Upper Mississippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Formation, Rivoli Member Echinoderms, Upper Mississippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Formation, Sherwood Member Echinoderms, Upper Mississippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Formation, Wall Member Echinoderms, Upper Mississippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Formation, Wyota Member Echinoderms, Upper Mississippi Valley",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "St. Peter Sandstone Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Ion Formation, Buckhorn Member Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Fm, Wyota Member Gastropoda and Monoplacophora, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Grand Detour Formation, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Dunleith Formation, Fairplay Member, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Sardeson Collection, Scales Shale, Elgin Member, Illinois and Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Pentamerus oblongus Comm., Picture Rock Member, eastern Iowa",
                "lng" : "-91.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Porpites Community, Scotch Grove Formation, eastern Iowa",
                "lng" : "-92.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Tabulate-Rugose Coral Community, Buck Creek Quarry Member, E. Iowa.",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Platteville Formation Pelecypods, Upper Mississippi Valley",
                "lng" : "-91.500000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Glenwood Formation Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Pecatonica Formation Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Mifflin Formation Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Grand Detour Formation Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Nachusa Formation Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Quimbys Mill Formation Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Spechts Ferry Formation Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Guttenberg Formation Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Ion Formation, Buckhorn Member Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Ion Formation, St. James Member Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Formation, Beecher Member Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Formation, Eagle Point Member Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Formation, Fairplay Member Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Formation, Mortimer Member Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Formation, Rivoli Member Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dunleith Formation, Sherwood Member Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Brainard Shale Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Stricklandia lens progressa Community, Sweeney Member, E Iowa and NW Illinois",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Tabulate Coral-Lamellar Stromatoporoid Comm., Llandovery, E Iowa and NW Illinois",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Blanding Formation, E Iowa and NW Illinois",
                "lng" : "-91.000000"
            },
            {
                "lat" : "42.483334",
                "enterer" : "K. Koverman",
                "collection_name" : "Palaeoneilo fecunda-Plagioglypta iowaensis Community of the Dubuque",
                "lng" : "-90.866669"
            },
            {
                "lat" : "42.173332",
                "enterer" : "K. Koverman",
                "collection_name" : "Eucalyptocrinites sp. cf. E. ornatus Association of the Scotch Grove",
                "lng" : "-91.107498"
            },
            {
                "lat" : "42.500000",
                "enterer" : "T. Hanson",
                "collection_name" : "Mifflin Formation Bryozoa of the Upper Mississippi Valley",
                "lng" : "-91.250000"
            },
            {
                "lat" : "42.500000",
                "enterer" : "T. Hanson",
                "collection_name" : "Quimbys Mill Formation Bryozoans of the Upper Mississippi Valley",
                "lng" : "-91.250000"
            },
            {
                "lat" : "42.500000",
                "enterer" : "T. Hanson",
                "collection_name" : "Decorah Shale (Stictopora angularis zone) Bryozoans, Upper Mississippi Valley",
                "lng" : "-91.250000"
            },
            {
                "lat" : "42.500000",
                "enterer" : "T. Hanson",
                "collection_name" : "Spechts Ferry Shale Bryozoans of the Upper Mississippi Valley",
                "lng" : "-91.250000"
            },
            {
                "lat" : "42.500000",
                "enterer" : "T. Hanson",
                "collection_name" : "Decorah Shale (Stictopora mutabilis zone) Bryozoans, Upper Mississippi Valley",
                "lng" : "-91.250000"
            },
            {
                "lat" : "42.500000",
                "enterer" : "T. Hanson",
                "collection_name" : "Guttenberg Formation Bryozoans of the Upper Mississippi Valley",
                "lng" : "-91.250000"
            },
            {
                "lat" : "42.500000",
                "enterer" : "T. Hanson",
                "collection_name" : "Decorah Shale (Stictopora minima zone) Bryozoans, Upper Mississippi Valley",
                "lng" : "-91.250000"
            },
            {
                "lat" : "42.500000",
                "enterer" : "T. Hanson",
                "collection_name" : "Ion Formation Bryozoans of the Upper Mississippi Valley",
                "lng" : "-91.250000"
            },
            {
                "lat" : "42.500000",
                "enterer" : "T. Hanson",
                "collection_name" : "Prosser Limestone Pelecypods, Upper Mississippi Valley",
                "lng" : "-91.000000"
            },
            {
                "lat" : "42.500000",
                "enterer" : "T. Hanson",
                "collection_name" : "Maquoketa Formation Pelecypods, Upper Mississippi Valley",
                "lng" : "-91.250000"
            },
            {
                "lat" : "42.233334",
                "enterer" : "M. Foote",
                "collection_name" : "Trilobites of bryozoan-rich bioherm flank beds, Hopkinton Dolo., Jones Co.",
                "lng" : "-91.183334"
            },
            {
                "lat" : "41.916668",
                "enterer" : "M. Foote",
                "collection_name" : "Ferganella Community, Massilon, Iowa.",
                "lng" : "-90.916664"
            },
            {
                "lat" : "42.116669",
                "enterer" : "M. Foote",
                "collection_name" : "Leperditiid Community, Gower Formation, eastern Iowa",
                "lng" : "-91.349998"
            },
            {
                "lat" : "42.049999",
                "enterer" : "M. Foote",
                "collection_name" : "Protomegastrophia-Atrypa Community, upper Scotch Grove Fm., Jones Co., Iowa",
                "lng" : "-91.000000"
            },
            {
                "lat" : "42.049999",
                "enterer" : "M. Foote",
                "collection_name" : "Rhipidium Community, upper Scotch Grove Formation, Jackson County, Iowa",
                "lng" : "-91.000000"
            },
            {
                "lat" : "42.049999",
                "enterer" : "M. Foote",
                "collection_name" : "Rhynchonellid-Protathyrid Community, Scotch Grove and Gower Formations, E Iowa",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "St. Peter Sandstone Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Neda Formation Cephalopods, Illinois, Iowa, Wisconsin",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Maquoketa Formation Cephalopods, Iowa",
                "lng" : "-92.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Flabellitesia Community, Llandovery, eastern Iowa",
                "lng" : "-92.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Harpidium (Isovella) maquoketa Community, Hopkinton Fm., E Iowa. and NW Illinois",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Harpidium (Isovella) maquoketa-Stricklandia laevis Comm., Hopkinton Fm., E Iowa",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Harpidium (Lissocoelina) Community, Gower Formation, Eastern Iowa",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Pentamerus-Stricklandia Comm., late Aeronian, E Iowa and NW Illinois",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Stricklandia laevis Community, Hopkinton Formation, Eastern Iowa",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.733334",
                "enterer" : "T. Hanson",
                "collection_name" : "St. Peter Sandstone of Southeastern Minnesota: Fountain",
                "lng" : "-92.133331"
            },
            {
                "lat" : "43.500000",
                "enterer" : "T. Hanson",
                "collection_name" : "Elgin Member, Maquoketa Formation, Granger, Minnesota rugose coral",
                "lng" : "-92.133331"
            },
            {
                "lat" : "43.349998",
                "enterer" : "M. Foote",
                "collection_name" : "Brachiopods of Mason City Mbr., Shell Rock Fm., North-Central Iowa",
                "lng" : "-92.783333"
            },
            {
                "lat" : "43.349998",
                "enterer" : "M. Foote",
                "collection_name" : "Brachiopods of Rock Grove Mbr., Shell Rock Fm., North-Central Iowa",
                "lng" : "-92.783333"
            },
            {
                "lat" : "43.349998",
                "enterer" : "M. Foote",
                "collection_name" : "Brachiopods of Nora Mbr., Shell Rock Fm., North-Central Iowa",
                "lng" : "-92.783333"
            },
            {
                "lat" : "43.521999",
                "enterer" : "A. Stigall",
                "collection_name" : "Floyd County, Lime Creek Formation",
                "lng" : "-92.948303"
            },
            {
                "lat" : "43.521999",
                "enterer" : "A. Stigall",
                "collection_name" : "Floyd County, Lime Creek Formation",
                "lng" : "-92.948303"
            },
            {
                "lat" : "43.521999",
                "enterer" : "A. Stigall",
                "collection_name" : "Floyd County, Lime Creek Formation",
                "lng" : "-92.948303"
            },
            {
                "lat" : "43.669998",
                "enterer" : "A. Stigall",
                "collection_name" : "Rockford Township",
                "lng" : "-92.967003"
            },
            {
                "lat" : "43.330002",
                "enterer" : "A. Stigall",
                "collection_name" : "Roseville",
                "lng" : "-92.817001"
            },
            {
                "lat" : "43.521999",
                "enterer" : "A. Stigall",
                "collection_name" : "1 mi above Rockford",
                "lng" : "-92.948303"
            },
            {
                "lat" : "43.830002",
                "enterer" : "A. Stigall",
                "collection_name" : "Charles City",
                "lng" : "-92.667000"
            },
            {
                "lat" : "43.299999",
                "enterer" : "A. Stigall",
                "collection_name" : "Mitchell",
                "lng" : "-92.817001"
            },
            {
                "lat" : "43.521999",
                "enterer" : "A. Stigall",
                "collection_name" : "Rockford",
                "lng" : "-92.948303"
            },
            {
                "lat" : "43.521999",
                "enterer" : "A. Stigall",
                "collection_name" : "Bird Hill (South of Rockford)",
                "lng" : "-92.948303"
            },
            {
                "lat" : "43.667000",
                "enterer" : "A. Stigall",
                "collection_name" : "Floyd County, Lime Creek Formation",
                "lng" : "-92.783302"
            },
            {
                "lat" : "43.669998",
                "enterer" : "A. Stigall",
                "collection_name" : "Bassett",
                "lng" : "-92.516998"
            },
            {
                "lat" : "43.521999",
                "enterer" : "A. Stigall",
                "collection_name" : "2.75 mi W of Rockford on IA Hwy D",
                "lng" : "-92.948303"
            },
            {
                "lat" : "43.521999",
                "enterer" : "A. Stigall",
                "collection_name" : "Bird Hill, 4-5 mi SW of Rockford",
                "lng" : "-92.948303"
            },
            {
                "lat" : "43.521999",
                "enterer" : "A. Stigall",
                "collection_name" : "Rockford Twp, sec 16, T95N, R18W",
                "lng" : "-92.948303"
            },
            {
                "lat" : "43.533001",
                "enterer" : "A. Stigall",
                "collection_name" : "Rockford Brick and Tile, Rockford Twp",
                "lng" : "-92.498299"
            },
            {
                "lat" : "43.330002",
                "enterer" : "A. Stigall",
                "collection_name" : "Roseville",
                "lng" : "-92.817001"
            },
            {
                "lat" : "43.330002",
                "enterer" : "A. Stigall",
                "collection_name" : "Roseville",
                "lng" : "-92.817001"
            },
            {
                "lat" : "44.000557",
                "enterer" : "Z. Krug",
                "collection_name" : "Cummingsville Fm, MN - Patzkowsky",
                "lng" : "-92.449997"
            },
            {
                "lat" : "44.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Lower Decorah Shale Ostracodes in Minnesota",
                "lng" : "-92.000000"
            },
            {
                "lat" : "44.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Middle Decorah Shale Ostracodes in Minnesota",
                "lng" : "-92.000000"
            },
            {
                "lat" : "44.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Upper Decorah Shale Ostracodes in Minnesota",
                "lng" : "-92.000000"
            },
            {
                "lat" : "44.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Glenwood Formation Echinoderms, Upper Mississippi Valley",
                "lng" : "-92.000000"
            },
            {
                "lat" : "44.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Platteville Formation Echinoderms, Upper Mississippi Valley (Minnesota)",
                "lng" : "-92.000000"
            },
            {
                "lat" : "44.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Decorah Shale Echinoderms, Upper Mississippi Valley",
                "lng" : "-92.000000"
            },
            {
                "lat" : "44.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Cummingsville Formation Echinoderms, Upper Mississippi Valley",
                "lng" : "-92.000000"
            },
            {
                "lat" : "44.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Prosser Limestone Echinoderms of the Upper Mississippi Valley",
                "lng" : "-92.000000"
            },
            {
                "lat" : "44.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Stewartville Formation Echinoderms of the Upper Mississippi Valley",
                "lng" : "-92.000000"
            },
            {
                "lat" : "44.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Dubuque Formation Echinoderms, Upper Mississippi Valley (Minnesota)",
                "lng" : "-92.000000"
            },
            {
                "lat" : "44.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Maquoketa Formation, Elgin Member Echinoderms, Upper Mississippi Valley",
                "lng" : "-92.000000"
            },
            {
                "lat" : "44.000000",
                "enterer" : "T. Hanson",
                "collection_name" : "Maquoketa Formation, Clermont Member Echinoderms, Upper Mississippi Valley",
                "lng" : "-92.000000"
            },
            {
                "lat" : "44.005554",
                "enterer" : "B. Kröger",
                "collection_name" : "Oneota dolomite near Trempealeau",
                "lng" : "-91.463333"
            },
            {
                "lat" : "43.895557",
                "enterer" : "B. Kröger",
                "collection_name" : "Oneota dolomite near Dresbach",
                "lng" : "-91.343330"
            },
            {
                "lat" : "44.250000",
                "enterer" : "S. Peters",
                "collection_name" : "Highway 93 road cut",
                "lng" : "-91.500000"
            },
            {
                "lat" : "43.830002",
                "enterer" : "A. Stigall",
                "collection_name" : "Rockford",
                "lng" : "-92.932999"
            },
            {
                "lat" : "43.830002",
                "enterer" : "A. Stigall",
                "collection_name" : "Rockford",
                "lng" : "-92.932999"
            },
            {
                "lat" : "43.830002",
                "enterer" : "A. Stigall",
                "collection_name" : "Shell Rock, Rockford",
                "lng" : "-92.932999"
            },
            {
                "lat" : "43.830002",
                "enterer" : "A. Stigall",
                "collection_name" : "Rockford",
                "lng" : "-92.932999"
            },
            {
                "lat" : "43.830002",
                "enterer" : "A. Stigall",
                "collection_name" : "Rockford",
                "lng" : "-92.932999"
            },
            {
                "lat" : "43.830002",
                "enterer" : "A. Stigall",
                "collection_name" : "Rockford",
                "lng" : "-92.932999"
            },
            {
                "lat" : "43.830002",
                "enterer" : "A. Stigall",
                "collection_name" : "Shell Rock, Rockford",
                "lng" : "-92.932999"
            },
            {
                "lat" : "43.830002",
                "enterer" : "A. Stigall",
                "collection_name" : "Rockford",
                "lng" : "-92.932999"
            },
            {
                "lat" : "44.272221",
                "enterer" : "M. Uhen",
                "collection_name" : "Kenyon",
                "lng" : "-92.985558"
            },
            {
                "lat" : "42.119446",
                "enterer" : "M. Sommers",
                "collection_name" : "Carroll Lake, Maquoketa Fm., Illinois",
                "lng" : "-90.067497"
            },
            {
                "lat" : "42.500557",
                "enterer" : "M. Sommers",
                "collection_name" : "Dubuque, Maquoketa Fm., Iowa",
                "lng" : "-90.664444"
            },
            {
                "lat" : "42.433334",
                "enterer" : "M. Sommers",
                "collection_name" : "Stricklandiid community, Hopkinton Fm., Iowa/Illinois/Wisconsin",
                "lng" : "-90.766670"
            },
            {
                "lat" : "42.200001",
                "enterer" : "M. Sommers",
                "collection_name" : "Pentamerid community, Hopkinton Fm., Iowa/Illinois/Wisconsin",
                "lng" : "-90.516670"
            },
            {
                "lat" : "42.433334",
                "enterer" : "M. Sommers",
                "collection_name" : "Coral-Algal community, McDevitt Quarry, Hopkinton Dolomite, Dubuque Co., Iowa",
                "lng" : "-90.766670"
            },
            {
                "lat" : "43.081390",
                "enterer" : "Z. Krug",
                "collection_name" : "Pentamerus community, Waukesha Fm, 30286 - Patzkowsky",
                "lng" : "-88.261108"
            },
            {
                "lat" : "43.023335",
                "enterer" : "M. Uhen",
                "collection_name" : "Cedar Road Site",
                "lng" : "-86.036392"
            },
            {
                "lat" : "42.416668",
                "enterer" : "M. Foote",
                "collection_name" : "Diamphidiocystis-Pyrgocystis Comm., near Ord./Sil. boundary, Guilford, Illinois",
                "lng" : "-90.283333"
            },
            {
                "lat" : "42.400002",
                "enterer" : "M. Foote",
                "collection_name" : "Brockocystis nodosaria-Polytryphocycloides Assoc., Llandovery, Eastern Iowa",
                "lng" : "-91.300003"
            },
            {
                "lat" : "42.500000",
                "enterer" : "M. Foote",
                "collection_name" : "Apiocystitinid Association, Llandovery, E Iowa and NW Illinois",
                "lng" : "-90.199997"
            },
            {
                "lat" : "42.500000",
                "enterer" : "M. Foote",
                "collection_name" : "Cyclocrinites dactioloides-Dimerocrinites (Eudimerocrinites) sp. Assoc., E Iowa",
                "lng" : "-91.500000"
            },
            {
                "lat" : "42.500000",
                "enterer" : "M. Foote",
                "collection_name" : "Carpocrinus bodei Assoc., Farmers Creek Member, Hopkinton Fm., Eastern Iowa",
                "lng" : "-91.500000"
            },
            {
                "lat" : "42.500000",
                "enterer" : "M. Foote",
                "collection_name" : "Gomphocystites Assoc., Farmers Creek Member, Hopkinton Fm., Eastern Iowa",
                "lng" : "-91.500000"
            },
            {
                "lat" : "42.483334",
                "enterer" : "M. Foote",
                "collection_name" : "Hagnocrinus-Luxocrinus Assoc., Welton Member, Eastern Iowa",
                "lng" : "-90.866669"
            },
            {
                "lat" : "42.266666",
                "enterer" : "M. Foote",
                "collection_name" : "Lecathylus gregarius-Sphenothallus Community, Mosalem Formation",
                "lng" : "-90.416664"
            },
            {
                "lat" : "43.250000",
                "enterer" : "T. Hanson",
                "collection_name" : "Maquoketa Group, Fort Atkinson Fm, northeastern Iowa-northwestern Illinois",
                "lng" : "-90.166664"
            },
            {
                "lat" : "42.733334",
                "enterer" : "Z. Krug",
                "collection_name" : "Mifflin Submember, Platteville Fm, SW Wisconsin, Hesperorthis-Eoleperditia",
                "lng" : "-90.483330"
            },
            {
                "lat" : "42.733334",
                "enterer" : "Z. Krug",
                "collection_name" : "Mifflin Submember, Platteville Fm, SW Wisconsin, Hesperorthis-Sinuites",
                "lng" : "-90.483330"
            },
            {
                "lat" : "42.733334",
                "enterer" : "P. Wagner",
                "collection_name" : "UW 4014/1, south of Platteville",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.750000",
                "enterer" : "P. Wagner",
                "collection_name" : "UW 4014/2, 6.4 kilometers west of Platteville",
                "lng" : "-90.566666"
            },
            {
                "lat" : "42.860558",
                "enterer" : "T. Liebrecht",
                "collection_name" : "Mineral Point",
                "lng" : "-90.188057"
            },
            {
                "lat" : "42.814445",
                "enterer" : "A. Hendy",
                "collection_name" : "New Glarus [Guttenberg Fm, Galena Gp]",
                "lng" : "-89.635002"
            },
            {
                "lat" : "42.814445",
                "enterer" : "A. Hendy",
                "collection_name" : "New Glarus [Dunleith Fm, Galena Gp]",
                "lng" : "-89.635002"
            },
            {
                "lat" : "42.549999",
                "enterer" : "P. Wagner",
                "collection_name" : "Top of quarry, north side of Wisconsin State Rte 81, 6.4 km west of Rte 213",
                "lng" : "-89.233330"
            },
            {
                "lat" : "43.091667",
                "enterer" : "K. Layou",
                "collection_name" : "Tom Williams Quarry (Locality 2), Floyd Co.",
                "lng" : "-92.966667"
            },
            {
                "lat" : "43.081390",
                "enterer" : "Z. Krug",
                "collection_name" : "Pentamerus community, Waukesha Fm, 30285 - Patzkowsky",
                "lng" : "-88.261108"
            },
            {
                "lat" : "43.043056",
                "enterer" : "Z. Krug",
                "collection_name" : "Apopentamerus community, Racine Fm, 34659 - Patzkowsky",
                "lng" : "-87.906387"
            },
            {
                "lat" : "43.043056",
                "enterer" : "Z. Krug",
                "collection_name" : "Apopentamerus community, Racine Fm, 34660 - Patzkowsky",
                "lng" : "-87.906387"
            },
            {
                "lat" : "43.051666",
                "enterer" : "Z. Krug",
                "collection_name" : "Racine Fm, Milwaukee, Hartung Quarry, 34489 - Patzkowsky",
                "lng" : "-88.007500"
            },
            {
                "lat" : "43.051666",
                "enterer" : "Z. Krug",
                "collection_name" : "Racine Fm, Milwaukee, Hartung Quarry, 34499 - Patzkowsky",
                "lng" : "-88.007500"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Lampterocrinus inflatus Community, Racine and Waukesha Formations",
                "lng" : "-88.000000"
            },
            {
                "lat" : "43.033333",
                "enterer" : "M. Foote",
                "collection_name" : "Pisocrinus-Gissocrinus Assoc., Hartung Quarry",
                "lng" : "-87.900002"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Pisocrinus-Gissocrinus Assoc., SE Wisconsin, NE Illinois, and N Indiana",
                "lng" : "-88.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "P. Hearn",
                "collection_name" : "Brandon Bridge, 30231, #4, \"Orthis\" layer",
                "lng" : "-88.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "P. Novack-Gottshall",
                "collection_name" : "Guttenberg Formation, Glenhaven Member",
                "lng" : "-91.000000"
            },
            {
                "lat" : "43.008610",
                "enterer" : "A. Hendy",
                "collection_name" : "Mt Horeb [Galena Gp]",
                "lng" : "-89.738335"
            },
            {
                "lat" : "43.012501",
                "enterer" : "A. Hendy",
                "collection_name" : "Waukesha [Brandon Bridge Fm]",
                "lng" : "-88.238335"
            },
            {
                "lat" : "43.020000",
                "enterer" : "J. Zambito",
                "collection_name" : "lower Berthelet brach list",
                "lng" : "-87.900002"
            },
            {
                "lat" : "43.020000",
                "enterer" : "J. Zambito",
                "collection_name" : "upper Berthelet brach list",
                "lng" : "-87.900002"
            },
            {
                "lat" : "43.020000",
                "enterer" : "J. Zambito",
                "collection_name" : "lower Lindwurm brach list",
                "lng" : "-87.900002"
            },
            {
                "lat" : "43.020000",
                "enterer" : "J. Zambito",
                "collection_name" : "Milwaukee unnamed unit brach list",
                "lng" : "-87.900002"
            },
            {
                "lat" : "43.020000",
                "enterer" : "J. Zambito",
                "collection_name" : "Milwaukee upper 1 meter brach list",
                "lng" : "-87.900002"
            },
            {
                "lat" : "43.020000",
                "enterer" : "J. Zambito",
                "collection_name" : "Lindwurm brach list",
                "lng" : "-87.900002"
            },
            {
                "lat" : "43.020000",
                "enterer" : "J. Zambito",
                "collection_name" : "Milwaukee Fmn brach list",
                "lng" : "-87.900002"
            },
            {
                "lat" : "43.024445",
                "enterer" : "B. Kröger",
                "collection_name" : "Oneota dolomite near McGregor",
                "lng" : "-91.182220"
            },
            {
                "lat" : "43.049999",
                "enterer" : "M. Foote",
                "collection_name" : "Thaerodonta recedens-Iowacystis sagittaria Comm., Elgin Member, Maquoketa Fm.",
                "lng" : "-91.833336"
            },
            {
                "lat" : "42.500000",
                "enterer" : "M. Foote",
                "collection_name" : "Atrypa \'reticularis\'-Dimerocrinites (Eudimerocrinites) sp. Assoc., Krapfl Quarry",
                "lng" : "-91.500000"
            },
            {
                "lat" : "42.483334",
                "enterer" : "M. Foote",
                "collection_name" : "Petalocrinus n. sp. Assoc., LaPorte City Formation",
                "lng" : "-91.583336"
            },
            {
                "lat" : "42.500000",
                "enterer" : "M. Foote",
                "collection_name" : "Brachiopods of Gizzard Creek Mbr., Coralville Fmn., Eastern Iowa",
                "lng" : "-92.000000"
            },
            {
                "lat" : "42.500000",
                "enterer" : "M. Foote",
                "collection_name" : "Brachiopods of Cou Falls Mbr., Coralville Fm., Eastern Iowa",
                "lng" : "-92.000000"
            },
            {
                "lat" : "42.468601",
                "enterer" : "A. Stigall",
                "collection_name" : "Independence",
                "lng" : "-91.889198"
            },
            {
                "lat" : "42.867001",
                "enterer" : "A. Stigall",
                "collection_name" : "Randalia",
                "lng" : "-91.900002"
            },
            {
                "lat" : "42.799999",
                "enterer" : "A. Stigall",
                "collection_name" : "Fayette",
                "lng" : "-91.782997"
            },
            {
                "lat" : "42.830002",
                "enterer" : "A. Stigall",
                "collection_name" : "Shellsburg",
                "lng" : "-91.866997"
            },
            {
                "lat" : "42.468601",
                "enterer" : "A. Stigall",
                "collection_name" : "1.5 mi NE of Independence",
                "lng" : "-91.889198"
            },
            {
                "lat" : "42.830002",
                "enterer" : "A. Stigall",
                "collection_name" : "Palo",
                "lng" : "-91.817001"
            },
            {
                "lat" : "42.830002",
                "enterer" : "A. Stigall",
                "collection_name" : "Palo",
                "lng" : "-91.817001"
            },
            {
                "lat" : "42.466999",
                "enterer" : "A. Stigall",
                "collection_name" : "Independence",
                "lng" : "-91.782997"
            },
            {
                "lat" : "42.867001",
                "enterer" : "A. Stigall",
                "collection_name" : "Randalia",
                "lng" : "-91.900002"
            },
            {
                "lat" : "42.330002",
                "enterer" : "A. Stigall",
                "collection_name" : "Linn Junction",
                "lng" : "-91.717003"
            },
            {
                "lat" : "42.944000",
                "enterer" : "A. Stigall",
                "collection_name" : "2.1 mi SE of Shellsburg",
                "lng" : "-91.869202"
            },
            {
                "lat" : "42.468601",
                "enterer" : "A. Stigall",
                "collection_name" : "I mi NE of Indpendence",
                "lng" : "-91.889198"
            },
            {
                "lat" : "42.466999",
                "enterer" : "A. Stigall",
                "collection_name" : "Indpendence",
                "lng" : "-91.782997"
            },
            {
                "lat" : "42.867001",
                "enterer" : "A. Stigall",
                "collection_name" : "Randalia",
                "lng" : "-91.900002"
            },
            {
                "lat" : "42.330002",
                "enterer" : "A. Stigall",
                "collection_name" : "Linn Junction",
                "lng" : "-91.717003"
            },
            {
                "lat" : "42.799999",
                "enterer" : "A. Stigall",
                "collection_name" : "Fayette County",
                "lng" : "-91.782997"
            },
            {
                "lat" : "43.141666",
                "enterer" : "K. Layou",
                "collection_name" : "Nora Springs (Locality 3), Floyd Co.",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.144444",
                "enterer" : "K. Layou",
                "collection_name" : "Nora Springs north (Locality 4), Floyd Co.",
                "lng" : "-93.001389"
            },
            {
                "lat" : "43.150002",
                "enterer" : "A. Stigall",
                "collection_name" : "Nora Springs",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.150002",
                "enterer" : "A. Stigall",
                "collection_name" : "Nora Springs",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.150002",
                "enterer" : "A. Stigall",
                "collection_name" : "Shell Rock River at Nora Springs",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.150002",
                "enterer" : "A. Stigall",
                "collection_name" : "Shell Rock River at Nora Springs",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.150002",
                "enterer" : "A. Stigall",
                "collection_name" : "Nora Springs, Wheelerwood",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.150002",
                "enterer" : "A. Stigall",
                "collection_name" : "Charles City",
                "lng" : "-92.733002"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Brachiopods of Osage Springs Mbr., Lithograph City Fm., N-C and E Iowa",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Brachiopods of Idlewild Mbr., Lithograph City Fm., N-C and E Iowa",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Upper Strophodonta fauna, Idlewild Mbr., Lithograph City Fm., N-C and E Iowa",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Eleutherokomma fauna, Idlewild Mbr., Lithograph City Fm., N-C and E Iowa",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Brachiopods of lower Juniper Hill Mbr., Lime Creek Fm., North-Central Iowa",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Brachiopods of upper Juniper Hill Mbr., Lime Creek Fm., North-Central Iowa",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Brachiopods of lower Cerro Gordo Mbr., Lime Creek Fm., North-Central Iowa",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Brachiopods of upper Cerro Gordo Mbr., Lime Creek Fm., North-Central Iowa",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Brachiopods of lower Owen Mbr., Lime Creek Fm., North-Central Iowa",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Brachiopods of upper Owen Mbr., Lime Creek Fm., North-Central Iowa",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.099998",
                "enterer" : "A. Stigall",
                "collection_name" : "Bird Hill (South of Rockford)",
                "lng" : "-92.932999"
            },
            {
                "lat" : "43.099998",
                "enterer" : "A. Stigall",
                "collection_name" : "Bird Hill (South of Rockford)",
                "lng" : "-92.932999"
            },
            {
                "lat" : "43.099998",
                "enterer" : "A. Stigall",
                "collection_name" : "Bird Hill",
                "lng" : "-92.932999"
            },
            {
                "lat" : "43.117001",
                "enterer" : "A. Stigall",
                "collection_name" : "Rudd",
                "lng" : "-92.917000"
            },
            {
                "lat" : "43.099998",
                "enterer" : "A. Stigall",
                "collection_name" : "Bird Hill",
                "lng" : "-92.932999"
            },
            {
                "lat" : "43.117001",
                "enterer" : "A. Stigall",
                "collection_name" : "Rudd",
                "lng" : "-92.917000"
            },
            {
                "lat" : "43.099998",
                "enterer" : "A. Stigall",
                "collection_name" : "Floyd City",
                "lng" : "-92.766998"
            },
            {
                "lat" : "43.133331",
                "enterer" : "U. Merkel",
                "collection_name" : "Shell Rock River, Nora Member, lower biostrome",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.133331",
                "enterer" : "U. Merkel",
                "collection_name" : "Shell Rock River, Nora Member, upper biostrome",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.133331",
                "enterer" : "U. Merkel",
                "collection_name" : "Shell Rock River, Mason City Member, biostrome",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.133331",
                "enterer" : "U. Merkel",
                "collection_name" : "Shell Rock River, upper Mason City Member",
                "lng" : "-93.000000"
            },
            {
                "lat" : "42.700001",
                "enterer" : "A. Stigall",
                "collection_name" : "Shell Rock, Baumgardner",
                "lng" : "-92.567001"
            },
            {
                "lat" : "42.632999",
                "enterer" : "A. Stigall",
                "collection_name" : "Jessup",
                "lng" : "-92.669998"
            },
            {
                "lat" : "42.766701",
                "enterer" : "A. Stigall",
                "collection_name" : "Klein & Johnson Quarry",
                "lng" : "-92.316902"
            },
            {
                "lat" : "42.766701",
                "enterer" : "A. Stigall",
                "collection_name" : "Keidle Bluff",
                "lng" : "-92.316902"
            },
            {
                "lat" : "42.667000",
                "enterer" : "A. Stigall",
                "collection_name" : "Shell Rock, Wheelerwood",
                "lng" : "-92.583000"
            },
            {
                "lat" : "42.700001",
                "enterer" : "A. Stigall",
                "collection_name" : "Shell Rock, Baumgardner",
                "lng" : "-92.567001"
            },
            {
                "lat" : "42.700001",
                "enterer" : "A. Stigall",
                "collection_name" : "Waverly",
                "lng" : "-92.467003"
            },
            {
                "lat" : "42.667000",
                "enterer" : "A. Stigall",
                "collection_name" : "Shell Rock, Wheelerwood",
                "lng" : "-92.583000"
            },
            {
                "lat" : "42.533001",
                "enterer" : "A. Stigall",
                "collection_name" : "Black Hawk County",
                "lng" : "-92.449997"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Rugosa of the Lime Creek Fmn, Cerro Gordo Mbr, north-central Iowa",
                "lng" : "-93.000000"
            },
            {
                "lat" : "43.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Rugosa of the Lime Creek Fmn, Owen Mbr, north-central Iowa",
                "lng" : "-93.000000"
            },
            {
                "lat" : "42.950001",
                "enterer" : "A. Stigall",
                "collection_name" : "Marble Rock",
                "lng" : "-92.833000"
            },
            {
                "lat" : "42.950001",
                "enterer" : "A. Stigall",
                "collection_name" : "Marble Rock",
                "lng" : "-92.833000"
            },
            {
                "lat" : "42.882999",
                "enterer" : "A. Stigall",
                "collection_name" : "Greene",
                "lng" : "-92.800003"
            },
            {
                "lat" : "42.750000",
                "enterer" : "A. Stigall",
                "collection_name" : "Dumont (near base of Owen)",
                "lng" : "-92.967003"
            },
            {
                "lat" : "42.950001",
                "enterer" : "A. Stigall",
                "collection_name" : "Rockford Quarry & Bird Hill (S of Rockford)",
                "lng" : "-92.932999"
            },
            {
                "lat" : "43.234722",
                "enterer" : "M. Uhen",
                "collection_name" : "Casnovia",
                "lng" : "-85.790558"
            },
            {
                "lat" : "43.131943",
                "enterer" : "M. Uhen",
                "collection_name" : "Fruitport, near",
                "lng" : "-86.154724"
            },
            {
                "lat" : "42.324165",
                "enterer" : "M. Uhen",
                "collection_name" : "Shine Site",
                "lng" : "-86.138336"
            },
            {
                "lat" : "42.426388",
                "enterer" : "M. Uhen",
                "collection_name" : "South Haven, north of",
                "lng" : "-86.216667"
            },
            {
                "lat" : "42.571110",
                "enterer" : "M. Uhen",
                "collection_name" : "Fennville, SW of",
                "lng" : "-86.161110"
            },
            {
                "lat" : "42.717777",
                "enterer" : "M. Uhen",
                "collection_name" : "Fleser Site",
                "lng" : "-85.791115"
            },
            {
                "lat" : "42.776112",
                "enterer" : "M. Uhen",
                "collection_name" : "Holland, east of",
                "lng" : "-86.009171"
            },
            {
                "lat" : "42.866669",
                "enterer" : "P. Wagner",
                "collection_name" : "Belleville",
                "lng" : "-89.533333"
            },
            {
                "lat" : "42.906666",
                "enterer" : "M. Uhen",
                "collection_name" : "Smith Site",
                "lng" : "-85.772499"
            },
            {
                "lat" : "42.975555",
                "enterer" : "M. Uhen",
                "collection_name" : "Vermontville Site",
                "lng" : "-85.985275"
            },
            {
                "lat" : "42.906387",
                "enterer" : "M. Uhen",
                "collection_name" : "Grandville Gravel Pit",
                "lng" : "-85.753609"
            },
            {
                "lat" : "42.979443",
                "enterer" : "M. Uhen",
                "collection_name" : "Allensdale",
                "lng" : "-85.974167"
            },
            {
                "lat" : "42.964722",
                "enterer" : "M. Uhen",
                "collection_name" : "Bass River Pit Site",
                "lng" : "-86.033607"
            },
            {
                "lat" : "42.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Caryocrinites cf. ornatus Comm., Waukesha and Racine Fms., Upper Midwest",
                "lng" : "-88.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Eucalyptocrinites asper Comm., Racine, Waukesha, and Sugar Run Formations",
                "lng" : "-88.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Eucalyptocrinites ornatus-Carbonate Mound Comm., Racine and Wabash Formations",
                "lng" : "-88.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Siphonocrinus nobilis-Caryocrinites Comm., Wisconsin-Indiana Dolomite Belt",
                "lng" : "-88.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Dimerocrinites (D.) pentangularis Assoc., Racine Formation",
                "lng" : "-88.000000"
            },
            {
                "lat" : "41.849998",
                "enterer" : "M. Foote",
                "collection_name" : "Periechocrinus necis-Coelocystis subglobosus Assoc., Racine Fm.",
                "lng" : "-87.650002"
            },
            {
                "lat" : "41.900002",
                "enterer" : "M. Foote",
                "collection_name" : "Siphonocrinus nobilis Community, Welton Member of Scotch Grove Fm., Behr Quarry",
                "lng" : "-90.599998"
            },
            {
                "lat" : "41.849998",
                "enterer" : "M. Foote",
                "collection_name" : "Callocystites-Lysocystites Assoc., Lower Welton Member, Eastern Iowa",
                "lng" : "-90.183334"
            },
            {
                "lat" : "41.948101",
                "enterer" : "A. Stigall",
                "collection_name" : "Johnson County, Lithograph City Formation",
                "lng" : "-91.886101"
            },
            {
                "lat" : "41.948101",
                "enterer" : "A. Stigall",
                "collection_name" : "Johnson County, Shell Rock Formation",
                "lng" : "-91.886101"
            },
            {
                "lat" : "41.948101",
                "enterer" : "A. Stigall",
                "collection_name" : "Johnson County, Shell Rock Formation",
                "lng" : "-91.886101"
            },
            {
                "lat" : "41.948101",
                "enterer" : "A. Stigall",
                "collection_name" : "Johnson County, Shell Rock Formation",
                "lng" : "-91.886101"
            },
            {
                "lat" : "41.817001",
                "enterer" : "A. Stigall",
                "collection_name" : "Solon",
                "lng" : "-91.508003"
            },
            {
                "lat" : "41.948101",
                "enterer" : "A. Stigall",
                "collection_name" : "Coralville Lake",
                "lng" : "-91.886101"
            },
            {
                "lat" : "41.948101",
                "enterer" : "A. Stigall",
                "collection_name" : "Johnson County, Lithograph City Formation",
                "lng" : "-91.886101"
            },
            {
                "lat" : "41.795601",
                "enterer" : "A. Stigall",
                "collection_name" : "!.3 mi W of Middle Amana",
                "lng" : "-91.899399"
            },
            {
                "lat" : "41.795601",
                "enterer" : "A. Stigall",
                "collection_name" : ".5 mi W of Middle Amana",
                "lng" : "-91.899399"
            },
            {
                "lat" : "41.817001",
                "enterer" : "A. Stigall",
                "collection_name" : "Amana",
                "lng" : "-91.866997"
            },
            {
                "lat" : "41.795601",
                "enterer" : "A. Stigall",
                "collection_name" : "1.3 mi W of Middle Amana",
                "lng" : "-91.899399"
            },
            {
                "lat" : "41.817001",
                "enterer" : "A. Stigall",
                "collection_name" : "Solon",
                "lng" : "-91.508003"
            },
            {
                "lat" : "41.817001",
                "enterer" : "A. Stigall",
                "collection_name" : "Solon",
                "lng" : "-91.508003"
            },
            {
                "lat" : "41.817001",
                "enterer" : "A. Stigall",
                "collection_name" : "Solon",
                "lng" : "-91.508003"
            },
            {
                "lat" : "41.817001",
                "enterer" : "A. Stigall",
                "collection_name" : "Solon",
                "lng" : "-91.508003"
            },
            {
                "lat" : "41.830002",
                "enterer" : "J. Zambito",
                "collection_name" : "F1 LCf Om",
                "lng" : "-90.230003"
            },
            {
                "lat" : "41.830002",
                "enterer" : "J. Zambito",
                "collection_name" : "F1 LCf Ind",
                "lng" : "-90.230003"
            },
            {
                "lat" : "41.830002",
                "enterer" : "J. Zambito",
                "collection_name" : "F1 Cf CFm",
                "lng" : "-90.230003"
            },
            {
                "lat" : "41.830002",
                "enterer" : "J. Zambito",
                "collection_name" : "F1 LCf Rm",
                "lng" : "-90.230003"
            },
            {
                "lat" : "41.830002",
                "enterer" : "J. Zambito",
                "collection_name" : "F1 LCf Sm",
                "lng" : "-90.230003"
            },
            {
                "lat" : "41.830002",
                "enterer" : "J. Zambito",
                "collection_name" : "F2 LCf Sm",
                "lng" : "-90.230003"
            },
            {
                "lat" : "41.830002",
                "enterer" : "J. Zambito",
                "collection_name" : "F2 LCf Rm",
                "lng" : "-90.230003"
            },
            {
                "lat" : "41.830002",
                "enterer" : "J. Zambito",
                "collection_name" : "F2 LCf Rm2",
                "lng" : "-90.230003"
            },
            {
                "lat" : "42.116669",
                "enterer" : "M. Foote",
                "collection_name" : "Marsupiocrinus (Amarsupiocrinus) primaevus Assoc., Johns Creek Quarry",
                "lng" : "-91.133331"
            },
            {
                "lat" : "42.119999",
                "enterer" : "M. Uhen",
                "collection_name" : "Powers Site",
                "lng" : "-85.906670"
            },
            {
                "lat" : "42.150555",
                "enterer" : "M. Uhen",
                "collection_name" : "Watervliet, SE of",
                "lng" : "-86.232780"
            },
            {
                "lat" : "42.122780",
                "enterer" : "M. Uhen",
                "collection_name" : "Berrien County",
                "lng" : "-86.341667"
            },
            {
                "lat" : "42.106388",
                "enterer" : "M. Uhen",
                "collection_name" : "Decatur, west of",
                "lng" : "-86.040558"
            },
            {
                "lat" : "42.299999",
                "enterer" : "A. Stigall",
                "collection_name" : "La Port City",
                "lng" : "-92.182999"
            },
            {
                "lat" : "42.317001",
                "enterer" : "A. Stigall",
                "collection_name" : "Brandon",
                "lng" : "-92.169998"
            },
            {
                "lat" : "42.314400",
                "enterer" : "A. Stigall",
                "collection_name" : ".25 mi of IA 283, .5 mi E of Brandon",
                "lng" : "-92.190002"
            },
            {
                "lat" : "42.314400",
                "enterer" : "A. Stigall",
                "collection_name" : "Brandon",
                "lng" : "-92.190002"
            },
            {
                "lat" : "42.314400",
                "enterer" : "A. Stigall",
                "collection_name" : "Cedar River, 1.5 mi W of Brandon",
                "lng" : "-92.190002"
            },
            {
                "lat" : "42.317001",
                "enterer" : "A. Stigall",
                "collection_name" : "Brandon",
                "lng" : "-92.169998"
            },
            {
                "lat" : "42.314400",
                "enterer" : "A. Stigall",
                "collection_name" : "1.25 mi NE of Brandon",
                "lng" : "-92.190002"
            },
            {
                "lat" : "42.317001",
                "enterer" : "A. Stigall",
                "collection_name" : "Brandon",
                "lng" : "-92.169998"
            },
            {
                "lat" : "42.311401",
                "enterer" : "A. Stigall",
                "collection_name" : "I mi N of edge of Brandon",
                "lng" : "-92.190002"
            },
            {
                "lat" : "42.314400",
                "enterer" : "A. Stigall",
                "collection_name" : ".5 mi E of Brandon",
                "lng" : "-92.190002"
            },
            {
                "lat" : "42.314400",
                "enterer" : "A. Stigall",
                "collection_name" : "Cedar River, 2.5 mi S & 1.5 mi W of Brandon",
                "lng" : "-92.190002"
            },
            {
                "lat" : "42.317001",
                "enterer" : "A. Stigall",
                "collection_name" : "Brandon",
                "lng" : "-92.169998"
            },
            {
                "lat" : "42.317001",
                "enterer" : "A. Stigall",
                "collection_name" : "Buchanan County",
                "lng" : "-92.169998"
            },
            {
                "lat" : "42.369999",
                "enterer" : "J. Zambito",
                "collection_name" : "FI 1 Brachiopods C&E IA",
                "lng" : "-92.410004"
            },
            {
                "lat" : "42.369999",
                "enterer" : "J. Zambito",
                "collection_name" : "FI 2 Brachiopods C&E IA",
                "lng" : "-92.410004"
            },
            {
                "lat" : "42.369999",
                "enterer" : "J. Zambito",
                "collection_name" : "FI 3 Brachiopods C&E IA",
                "lng" : "-92.410004"
            },
            {
                "lat" : "42.369999",
                "enterer" : "J. Zambito",
                "collection_name" : "FI 4 Brachiopods C&E IA",
                "lng" : "-92.410004"
            },
            {
                "lat" : "42.369999",
                "enterer" : "J. Zambito",
                "collection_name" : "FI 5 Brachiopods C&E IA",
                "lng" : "-92.410004"
            },
            {
                "lat" : "42.369999",
                "enterer" : "J. Zambito",
                "collection_name" : "FI 7 Brachiopods C&E IA",
                "lng" : "-92.410004"
            },
            {
                "lat" : "42.369999",
                "enterer" : "J. Zambito",
                "collection_name" : "FI 8 Brachiopods C&E IA",
                "lng" : "-92.410004"
            },
            {
                "lat" : "42.369999",
                "enterer" : "J. Zambito",
                "collection_name" : "FI 9 Brachiopods C&E IA",
                "lng" : "-92.410004"
            },
            {
                "lat" : "42.000000",
                "enterer" : "M. Foote",
                "collection_name" : "Calliocrinus longispinus-Eucalyptocrinites cf. ornatus Assoc., Eastern Iowa",
                "lng" : "-90.500000"
            },
            {
                "lat" : "42.116669",
                "enterer" : "M. Foote",
                "collection_name" : "Marsupiocrinus (Amarsupiocrinus)-Manticrinus Assoc., Eastern Iowa",
                "lng" : "-91.133331"
            },
            {
                "lat" : "42.099998",
                "enterer" : "M. Foote",
                "collection_name" : "Calliocrinus-Petalocrinus mirabilis Assoc., Fawn Creek Mbr., E Iowa",
                "lng" : "-91.283333"
            },
            {
                "lat" : "42.066666",
                "enterer" : "M. Foote",
                "collection_name" : "Basal Athyris fauna, Gizzard Creek Mbr., Palo Quarry",
                "lng" : "-91.599998"
            },
            {
                "lat" : "42.066666",
                "enterer" : "M. Foote",
                "collection_name" : "Brachiopods of Independence Shale in Iowa",
                "lng" : "-92.066666"
            },
            {
                "lat" : "42.168598",
                "enterer" : "A. Stigall",
                "collection_name" : "1.4 mi SE of Vinton",
                "lng" : "-92.233002"
            },
            {
                "lat" : "42.132999",
                "enterer" : "A. Stigall",
                "collection_name" : "Vinton",
                "lng" : "-92.330002"
            },
            {
                "lat" : "42.132999",
                "enterer" : "A. Stigall",
                "collection_name" : "Fulton",
                "lng" : "-90.682999"
            },
            {
                "lat" : "42.132999",
                "enterer" : "A. Stigall",
                "collection_name" : "Vinton",
                "lng" : "-92.330002"
            },
            {
                "lat" : "42.132999",
                "enterer" : "A. Stigall",
                "collection_name" : "Vinton",
                "lng" : "-92.330002"
            },
            {
                "lat" : "42.132999",
                "enterer" : "A. Stigall",
                "collection_name" : "Vinton",
                "lng" : "-92.330002"
            },
            {
                "lat" : "42.182999",
                "enterer" : "A. Stigall",
                "collection_name" : "Linn County",
                "lng" : "-91.682999"
            },
            {
                "lat" : "42.016666",
                "enterer" : "P. Wagner",
                "collection_name" : "Bird Hill East",
                "lng" : "-93.016670"
            },
            {
                "lat" : "42.049999",
                "enterer" : "P. Wagner",
                "collection_name" : "Rockford Brick & Tile Quarry",
                "lng" : "-92.949997"
            },
            {
                "lat" : "42.006935",
                "enterer" : "N. Heim",
                "collection_name" : "UI locality Z-1F, Eagle City Ls, Hampton Fm, Marshall Co, Iowa",
                "lng" : "-92.775475"
            },
            {
                "lat" : "42.000000",
                "enterer" : "J. Alroy",
                "collection_name" : "North of Le Grand",
                "lng" : "-92.783333"
            },
            {
                "lat" : "42.000000",
                "enterer" : "P. Hearn",
                "collection_name" : "Little Cedar Formation, Bed-R1",
                "lng" : "-91.000000"
            },
            {
                "lat" : "42.000000",
                "enterer" : "P. Hearn",
                "collection_name" : "Little Cedar Formation, Bed-R3",
                "lng" : "-91.000000"
            },
            {
                "lat" : "42.192501",
                "enterer" : "L. Villier",
                "collection_name" : "quarry 2 miles south of Urbana",
                "lng" : "-91.868614"
            },
            {
                "lat" : "41.883331",
                "enterer" : "M. Foote",
                "collection_name" : "Caryocrinites sp. B Assoc., Welton Mbr., Scotch Grove Fm., Iowa",
                "lng" : "-90.550003"
            },
            {
                "lat" : "41.870834",
                "enterer" : "M. Uhen",
                "collection_name" : "Dowagiac River",
                "lng" : "-86.235275"
            },
            {
                "lat" : "42.036388",
                "enterer" : "M. Uhen",
                "collection_name" : "Fred Berndt Farm",
                "lng" : "-86.465553"
            },
            {
                "lat" : "41.978611",
                "enterer" : "M. Uhen",
                "collection_name" : "Prillwitz Site",
                "lng" : "-86.310555"
            },
            {
                "lat" : "41.992779",
                "enterer" : "M. Uhen",
                "collection_name" : "Eau Claire",
                "lng" : "-86.226669"
            },
            {
                "lat" : "41.878334",
                "enterer" : "M. Uhen",
                "collection_name" : "Harbert Site",
                "lng" : "-86.618889"
            },
            {
                "lat" : "42.050278",
                "enterer" : "M. Uhen",
                "collection_name" : "Adams Farm",
                "lng" : "-86.290833"
            },
            {
                "lat" : "41.950279",
                "enterer" : "M. Uhen",
                "collection_name" : "Baroda",
                "lng" : "-86.543053"
            },
            {
                "lat" : "41.935555",
                "enterer" : "M. Uhen",
                "collection_name" : "Beebe Farm",
                "lng" : "-86.485001"
            },
            {
                "lat" : "41.906666",
                "enterer" : "M. Uhen",
                "collection_name" : "Snow",
                "lng" : "-86.465553"
            },
            {
                "lat" : "41.805279",
                "enterer" : "M. Uhen",
                "collection_name" : "Bakerstown Marsh",
                "lng" : "-86.387497"
            },
            {
                "lat" : "41.790833",
                "enterer" : "M. Uhen",
                "collection_name" : "Terre Coupe",
                "lng" : "-86.445831"
            },
            {
                "lat" : "41.805832",
                "enterer" : "M. Uhen",
                "collection_name" : "Avery Marsh",
                "lng" : "-86.581108"
            },
            {
                "lat" : "41.950279",
                "enterer" : "M. Uhen",
                "collection_name" : "Lake Township",
                "lng" : "-86.561386"
            },
            {
                "lat" : "41.805557",
                "enterer" : "M. Uhen",
                "collection_name" : "Besson-Holden Farm",
                "lng" : "-86.561943"
            },
            {
                "lat" : "41.816666",
                "enterer" : "M. Uhen",
                "collection_name" : "Jones",
                "lng" : "-85.847504"
            },
            {
                "lat" : "42.000000",
                "enterer" : "P. Hearn",
                "collection_name" : "Racine Dolomite Formation, Lecthaylus Shale",
                "lng" : "-88.000000"
            },
            {
                "lat" : "41.849445",
                "enterer" : "T. Liebrecht",
                "collection_name" : "Dixon",
                "lng" : "-89.481110"
            },
            {
                "lat" : "41.866390",
                "enterer" : "L. Villier",
                "collection_name" : "quarry of the Medusa Portland Cement, Rock River, Dixon, Lee County",
                "lng" : "-89.447502"
            },
            {
                "lat" : "42.166668",
                "enterer" : "M. Foote",
                "collection_name" : "Bolicrinus-Theleproktocrinus Assoc., Farmers Creek Mbr., East-Central Iowa",
                "lng" : "-90.583336"
            },
            {
                "lat" : "42.166668",
                "enterer" : "M. Foote",
                "collection_name" : "Petalocrinus mirabilis-Pentameroides-Coral Assoc., Buck Creek Member, E Iowa",
                "lng" : "-91.099998"
            },
            {
                "lat" : "42.207779",
                "enterer" : "M. Uhen",
                "collection_name" : "Pine Creek Site",
                "lng" : "-86.196114"
            },
            {
                "lat" : "42.178612",
                "enterer" : "M. Uhen",
                "collection_name" : "Carmichael Site",
                "lng" : "-87.118332"
            },
            {
                "lat" : "42.207779",
                "enterer" : "M. Uhen",
                "collection_name" : "Hartford, near",
                "lng" : "-86.156944"
            },
            {
                "lat" : "42.193054",
                "enterer" : "M. Uhen",
                "collection_name" : "Heuser Site",
                "lng" : "-86.118332"
            },
            {
                "lat" : "42.207500",
                "enterer" : "M. Uhen",
                "collection_name" : "Watervliet, near",
                "lng" : "-86.310837"
            },
            {
                "lat" : "42.193611",
                "enterer" : "M. Uhen",
                "collection_name" : "Hager Township Site",
                "lng" : "-86.388336"
            },
            {
                "lat" : "42.178890",
                "enterer" : "M. Uhen",
                "collection_name" : "Paw Paw River Bed",
                "lng" : "-85.907219"
            },
            {
                "lat" : "42.164165",
                "enterer" : "M. Uhen",
                "collection_name" : "Lawton",
                "lng" : "-85.869720"
            },
            {
                "lat" : "42.251945",
                "enterer" : "M. Uhen",
                "collection_name" : "Almena Township",
                "lng" : "-85.833885"
            },
            {
                "lat" : "42.248611",
                "enterer" : "M. Uhen",
                "collection_name" : "Paw Paw Lake, near",
                "lng" : "-86.239998"
            },
            {
                "lat" : "42.371387",
                "enterer" : "M. Uhen",
                "collection_name" : "Van Buren County",
                "lng" : "-86.127502"
            },
            {
                "lat" : "42.324444",
                "enterer" : "M. Uhen",
                "collection_name" : "Mentha, south of",
                "lng" : "-85.775276"
            },
            {
                "lat" : "42.466667",
                "enterer" : "M. Foote",
                "collection_name" : "Tecnocyrtina fauna, Gizzard Creek Mbr., Coralville Fmn., Glory Quarry",
                "lng" : "-92.316666"
            },
            {
                "lat" : "42.533001",
                "enterer" : "A. Stigall",
                "collection_name" : "Littleton",
                "lng" : "-92.330002"
            },
            {
                "lat" : "42.533001",
                "enterer" : "A. Stigall",
                "collection_name" : "Littleton",
                "lng" : "-92.330002"
            },
            {
                "lat" : "42.533001",
                "enterer" : "A. Stigall",
                "collection_name" : "Littleton",
                "lng" : "-92.330002"
            },
            {
                "lat" : "42.466999",
                "enterer" : "A. Stigall",
                "collection_name" : "Waterloo",
                "lng" : "-92.330002"
            },
            {
                "lat" : "42.417500",
                "enterer" : "M. Uhen",
                "collection_name" : "Galena (Snyder Collection)",
                "lng" : "-90.425835"
            },
            {
                "lat" : "42.508331",
                "enterer" : "T. Liebrecht",
                "collection_name" : "Beloit (Beloit Mbr.)",
                "lng" : "-89.031944"
            },
            {
                "lat" : "42.416668",
                "enterer" : "J. Alroy",
                "collection_name" : "Galena (Leidy Collection)",
                "lng" : "-90.433334"
            },
            {
                "lat" : "42.369999",
                "enterer" : "J. Zambito",
                "collection_name" : "FI 6 Brachiopods C&E IA",
                "lng" : "-92.410004"
            }
        ]';
        $jsonObj = json_decode($jsonRaw);
        foreach($jsonObj as $num=>$fossil)
        {
            $noteId = Notes::createNewNote($gameId, 0);
            $noteId = $noteId->data;
            Notes::updateNote($noteId, "Fossil", true, true, 0);
            Notes::addContentToNote($noteId, $gameId, 0, 0, "NOTE", $fossil->collection_name." \nEntered By:".$fossil->enterer);
            Module::giveNoteToWorld($gameId, $noteId, $fossil->lat, $fossil->lng);
        }

        return new returnData(0);
    }

    // API FUNCTIONS \/ \/ \/
    public static function getFullNotesForGame($noteReqObj)
    {
        $gameId = $noteReqObj['gameId'];

        if(is_numeric($gameId))
            $gameId = intval($gameId);
        else
            return new returnData(1, "Error- Invalid Game Id: ".$gameId);

        $prefix = Module::getPrefix($gameId);
        if (!$prefix) return new returnData(1, "Error- Invalid Game Id: ".$gameId);

        $notes = array();
        $query = "SELECT note_id, owner_id FROM notes WHERE game_id = '{$gameId}' AND parent_note_id = 0";
        $result = mysql_query($query);
        while($noteObj = mysql_fetch_object($result))
        {
            $note = Notes::getDetailedFullNoteObject($noteObj->note_id, $noteObj->player_id);
            $notes[] = $note;
        }
        return new returnData(0,$notes);
    }

    public static function getDetailedPlayerNotes($playerId, $gameId, $individual=true)
    {
        /* NOTES */
        if($individual)
            $query = "SELECT note_id FROM notes WHERE (owner_id = '{$playerId}' OR public_to_notebook = '1') AND game_id = '{$gameId}' AND parent_note_id = 0 ORDER BY sort_index ASC";
        else
            $query = "SELECT note_id FROM notes WHERE owner_id = '{$playerId}' AND game_id = '{$gameId}' AND parent_note_id = 0 ORDER BY sort_index ASC";

        $result = mysql_query($query);

        $notes = array();
        while($noteId = mysql_fetch_object($result)) {
            $note = Notes::getDetailedFullNoteObject($noteId->note_id, $playerId);
            //Phil commented out these lines because they make no sense- notes have no media, their CONTENT does. 6/11
            //$note->media_url = Media::getMediaDirectoryURL($note->game_id)->data . '/' . $note->media_url;
            //$note->icon_url = Media::getMediaDirectoryURL(0)->data . '/' . $note->icon_url;
            $notes[] = $note;
        }
        return $notes;
    }

    private static function getDetailedFullNoteObject($noteId, $playerId=0)
    {
        $query = "SELECT note_id, game_id, owner_id, title, public_to_map, public_to_notebook FROM notes WHERE note_id = '{$noteId}'";
        $result = @mysql_query($query);
        if (mysql_error()) return new returnData(1, NULL, mysql_error());
        if($note = mysql_fetch_object($result))
        {
            $query = "SELECT user_name FROM players WHERE player_id = '{$note->owner_id}'";
            $player = mysql_query($query);
            $playerObj = mysql_fetch_object($player);
            $note->username = $playerObj->user_name;
            $note->contents = Notes::getNoteContentsAPI($noteId);
            $note->comments = Notes::getNoteCommentsAPI($noteId, $playerId);
            $note->tags = Notes::getNoteTagsAPI($noteId, $note->game_id);
            $note->likes = Notes::getNoteLikesAPI($noteId);
            $note->player_liked = ($playerId == 0 ? 0 : Notes::playerLikedAPI($playerId, $noteId));
            $note->icon_media_id = 5;
            if($note->dropped = Notes::noteDropped($noteId, $note->game_id))
                $location = Notes::getNoteLocation($noteId, $note->game_id);	
            else
            {
                $location = new stdClass();
                $location->lat = $location->lon = 0;
            }
            $note->lat = $location->lat;
            $note->lon = $location->lon;
            return $note;
        }
        return;
    }

	private static function getNoteContentsAPI($noteId)
	{
		//the whole 'm.file_path AS file_name' is for legacy reasons... Phil 10/12/2012
		$query = "SELECT nc.media_id, nc.type, nc.text, nc.game_id, nc.title, m.file_path, m.file_path AS file_name, m.game_id FROM note_content as nc LEFT JOIN media as m ON nc.media_id = m.media_id WHERE note_id = '{$noteId}'";
		$result = mysql_query($query);
		$contents = array();
		while($content = mysql_fetch_object($result))
		{
			$content->media_url = Config::gamedataWWWPath . '/' . $content->file_path;
			$contents[] = $content;
		}
        return $contents;
    }

    private static function getNoteCommentsAPI($noteId, $playerId)
    {
        $query = "SELECT note_id FROM notes WHERE parent_note_id = '{$noteId}'";
        $result = mysql_query($query);

        $comments = array();
        while($commentNoteId = mysql_fetch_object($result))
        {
            $comment = Notes::getDetailedFullNoteObject($commentNoteId->note_id, $playerId);
            $comments[] = $comment;
        }
        return $comments;
    }

    private static function getNoteTagsAPI($noteId, $gameId)
    {
        $query = "SELECT gt.tag, gt.tag_id, gt.player_created FROM note_tags LEFT JOIN ((SELECT tag, tag_id, player_created FROM game_tags WHERE game_id = '{$gameId}') as gt) ON note_tags.tag_id = gt.tag_id WHERE note_id = '{$noteId}'";
        $result = mysql_query($query);
        $tags = array();
        while($tag = mysql_fetch_object($result))	
            $tags[] = $tag;
        return $tags;
    }

    private static function getNoteLikesAPI($noteId)
    {
        $query = "SELECT COUNT(*) as numLikes FROM note_likes WHERE note_id = '{$noteId}'";
        $result  = mysql_query($query);
        $likes = mysql_fetch_object($result);
        return $likes->numLikes;
    }

    private static function playerLikedAPI($playerId, $noteId)
    {
        $query = "SELECT COUNT(*) as liked FROM note_likes WHERE player_id = '{$playerId}' AND note_id = '{$noteId}' LIMIT 1";
        $result = mysql_query($query);
        $liked = mysql_fetch_object($result);
        return $liked->liked;
    }
}
