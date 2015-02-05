<?php
require_once("module.php");

class Prune extends Module
{	
    public function pruneGame($gameId, $surrogate, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $TBD = new stdClass;
        $TBD->locations = Prune::pruneLocationsForGame($gameId, $surrogate, $editorId, $editorToken);
        $TBD->media = Prune::pruneMediaForGame($gameId, $surrogate, $editorId, $editorToken);
        $TBD->note_content = Prune::pruneNoteContentFromGame($gameId, $surrogate, $editorId, $editorToken);

        return $TBD;
    }

    public function pruneLocationsForGame($gameId, $surrogate, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $unused_locs = array();

        $locations = Module::queryArray("SELECT * FROM locations WHERE game_id = '{$gameId}'");
        $nodeLocs = array();
        $itemLocs = array();
        $npcLocs = array();
        $webpageLocs = array();
        $noteLocs = array();
        for($i = 0; $i < count($locations); $i++)
        {
            switch($locations[$i]->type)
            {
                case "Node":       $nodeLocs[]    = $locations[$i]; break;
                case "Item":       $itemLocs[]    = $locations[$i]; break;
                case "Npc":        $npcLocs[]     = $locations[$i]; break;
                case "WebPage":    $webpageLocs[] = $locations[$i]; break;
                case "PlayerNote": $noteLocs[]    = $locations[$i]; break;
            }
        }

        for($i = 0; $i < count($nodeLocs); $i++)
        {
            if(!Module::queryObject("SELECT * FROM nodes WHERE node_id = '{$nodeLocs[$i]->type_id}'"))
                $unused_locs[] = $nodeLocs[$i]->location_id;
        }

        for($i = 0; $i < count($itemLocs); $i++)
        {
            if(!Module::queryObject("SELECT * FROM items WHERE item_id = '{$itemLocs[$i]->type_id}'"))
                $unused_locs[] = $itemLocs[$i]->location_id;
        }

        for($i = 0; $i < count($npcLocs); $i++)
        {
            if(!Module::queryObject("SELECT * FROM npcs WHERE npc_id = '{$npcLocs[$i]->type_id}'"))
                $unused_locs[] = $npcLocs[$i]->location_id;
        }

        for($i = 0; $i < count($webpageLocs); $i++)
        {
            if(!Module::queryObject("SELECT * FROM web_pages WHERE web_page_id = '{$webpageLocs[$i]->type_id}'"))
                $unused_locs[] = $webpageLocs[$i]->location_id;
        }

        for($i = 0; $i < count($noteLocs); $i++)
        {
            if(!Module::queryObject("SELECT * FROM notes WHERE note_id = '{$noteLocs[$i]->type_id}'"))
                $unused_locs[] = $noteLocs[$i]->location_id;
        }

        if($surrogate)
        {
            for($i = 0; $i < count($unused_locs); $i++)
            {
                Module::queryObject("UPDATE locations SET game_id = '{$surrogate}' WHERE game_id = '{$gameId}' AND location_id = '{$unused_locs[$i]}'");
            }
        }
        else
        {
            for($i = 0; $i < count($unused_locs); $i++)
            {
                Module::queryObject("DELETE FROM locations WHERE game_id = '{$gameId}' AND location_id = '{$unused_locs[$i]}'");
            }
        }

        return $unused_locs;
    }

    public function pruneMediaForGame($gameId, $surrogate, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $known_media = array();
        $used_media = array();
        $unused_media = array();

        $media = Module::queryArray("SELECT * FROM media WHERE game_id = '{$gameId}'");
        for($i = 0; $i < count($media); $i++) $known_media[] = $media[$i]->media_id;

        $mediasrc = array();
        $a = new stdClass();
        $a->table = "aug_bubble_media";
        $a->column = "media_id";
        $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "game_object_tags"; $a->column = "media_id";                       $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "game_tags";        $a->column = "media_id";                       $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "games";            $a->column = "pc_media_id";                    $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "games";            $a->column = "icon_media_id";                  $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "games";            $a->column = "media_id";                       $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "games";            $a->column = "game_icon_media_id";             $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "items";            $a->column = "icon_media_id";                  $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "items";            $a->column = "media_id";                       $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "nodes";            $a->column = "media_id";                       $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "nodes";            $a->column = "icon_media_id";                  $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "note_content";     $a->column = "media_id";                       $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "npcs";             $a->column = "media_id";                       $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "npcs";             $a->column = "icon_media_id";                  $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "overlays";         $a->column = "media_id";                       $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "quests";           $a->column = "active_media_id";                $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "quests";           $a->column = "complete_media_id";              $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "quests";           $a->column = "active_icon_media_id";           $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "quests";           $a->column = "complete_icon_media_id";         $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "quests";           $a->column = "active_notification_media_id";   $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "quests";           $a->column = "complete_notification_media_id"; $mediasrc[] = $a;
        $a = new stdClass(); $a->table = "web_pages";        $a->column = "icon_media_id";                  $mediasrc[] = $a;

        for($i = 0; $i < count($mediasrc); $i++)
        {
          $tablemedia = Module::queryArray("SELECT * FROM {$mediasrc[$i]->table} WHERE game_id = '{$gameId}'");
          $col = $mediasrc[$i]->column;
          for($j = 0; $j < count($tablemedia); $j++)
          {
            $found = false;
            for($k = 0; $k < count($used_media) && $tablemedia[$j]->$col; $k++)
            {
              if($used_media[$k] == $tablemedia[$j]->$col)
                $found = true;
            }
            if(!$found && $tablemedia[$j]->$col)
              $used_media[] = $tablemedia[$j]->$col;
          }
        }

        $nodes = Module::queryArray("SELECT * FROM nodes WHERE game_id = '{$gameId}'");
        for($i = 0; $i < count($nodes); $i++)
        {
            $text = $nodes[$i]->text;
            $matches = array();

            //The case where no parsing is necessary 
            if(!preg_match("@<\s*dialog(ue)?\s*(\w*\s*=\s*[\"'][^\"']*[\"']\s*)*>(.*?)<\s*/\s*dialog(ue)?\s*>@is",$text,$matches))
                continue;

            //if it gets here, we actually need to parse stuff..
            $dialogContents = $matches[3]; //$dialogContents will be the string between the dialog tags; save this before parsing dialog attribs

            //parse contents of dialog tag
            while(!preg_match("@^\s*$@s",$dialogContents))//while dialogContents not empty
            {
                preg_match("@<\s*([^\s>]*)([^>]*)@is",$dialogContents,$matches);
                $tag = $matches[1]; //$tag will be the tag type (example: "npc")
                $attribs = $matches[2]; //$attribs will be the string of attributes on tag (example: "mediaId='123' title='billy'")
                preg_match("@<\s*".$tag."[^>]*>(.*?)<\s*\/\s*".$tag."\s*>(.*)@is",$dialogContents,$matches);
                $dialogContents = $matches[2]; //$dialog_contents will be the rest of the dialog contents that still need parsing
    
                if(preg_match("@npc@i",$tag) || preg_match("@pc@i",$tag))
                {
                    while(preg_match("@^\s*([^\s=]*)\s*=\s*[\"']([^\"']*)[\"']\s*(.*)@is",$attribs,$matches))
                    {
                        //In the example:  mediaId="123" name="billy"
                        $attrib_name = $matches[1]; //mediaId
                        $attrib_value = $matches[2]; //123
                        $attribs = $matches[3]; //name="billy"
    
                        if(preg_match("@mediaId@i",$attrib_name))
                        {
                            $found = false;
                            for($k = 0; $k < count($used_media) && $attrib_value; $k++)
                            {
                              if($used_media[$k] == $attrib_value)
                                $found = true;
                            }
                            if(!$found && $attrib_value)
                              $used_media[] = $attrib_value;
                        }
                    }
                }
            }
        }








        for($i = 0; $i < count($known_media); $i++)
        {
            $used = false;
            for($j = 0; $j < count($used_media); $j++)
            {
                if($known_media[$i] == $used_media[$j])
                    $used = true;
            }
            if(!$used)
            {
                $unused_media[] = $known_media[$i];
            }
        }
        
        if($surrogate)
        {
            for($i = 0; $i < count($unused_media); $i++)
            {
                Module::queryObject("UPDATE media SET game_id = '{$surrogate}' WHERE game_id = '{$gameId}' AND media_id = '{$unused_media[$i]}'");
            }
        }
        else
        {
            for($i = 0; $i < count($unused_media); $i++)
            {
                Module::queryObject("DELETE FROM media WHERE game_id = '{$gameId}' AND media_id = '{$unused_media[$i]}'");
            }
        }

        return $unused_media;
    }

    public function pruneNoteContentFromGame($gameId, $surrogate, $editorId, $editorToken)
    {
        if(!Module::authenticateGameEditor($gameId, $editorId, $editorToken, "read_write"))
            return new returnData(6, NULL, "Failed Authentication");

        $unused_content = array();

        $noteContent = Module::queryArray("SELECT * FROM note_content WHERE game_id = '{$gameId}'");
        for($i = 0; $i < count($noteContent); $i++)
        {
            if(!Module::queryObject("SELECT * FROM notes WHERE note_id = '{$noteContent[$i]->note_id}'"))
                $unused_content[] = $noteContent[$i]->content_id;
        }

        if($surrogate)
        {
            for($i = 0; $i < count($unused_content); $i++)
            {
                Module::queryObject("UPDATE note_content SET game_id = '{$surrogate}' WHERE game_id = '{$gameId}' AND note_content_id = '{$unused_content[$i]}'");
            }
        }
        else
        {
            for($i = 0; $i < count($unused_content); $i++)
            {
                Module::queryObject("DELETE FROM note_content WHERE game_id = '{$gameId}' AND note_content_id = '{$unused_content[$i]}'");
            }
        }

        return $unused_content;
    }
}

?>
