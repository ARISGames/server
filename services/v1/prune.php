<?php
require_once("module.php");

class Prune extends Module
{	
    public function pruneGame($gameId)
    {
        $TBD = new stdClass;
        $TBD->locations = Prune::pruneLocationsForGame($gameId)->data;
        $TBD->media = Prune::pruneMediaForGame($gameId)->data;
        $TBD->note_content = Prune::pruneNoteContentFromGame($gameId)->data;

        return new returnData(0,$TBD);
    }

    public function pruneLocationsForGame($gameId)
    {
        $TBD = array();

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
            {
                $D = new stdClass;
                $D->type = "Location";
                $D->id = $nodeLocs[$i]->location_id;
                $D->description = "(Loc Node ".$nodeLocs[$i]->location_id.")";
                $TBD[] = $D;
            }
        }

        for($i = 0; $i < count($itemLocs); $i++)
        {
            if(!Module::queryObject("SELECT * FROM items WHERE item_id = '{$itemLocs[$i]->type_id}'"))
            {
                $D = new stdClass;
                $D->type = "Location";
                $D->id = $itemLocs[$i]->location_id;
                $D->description = "(Loc Item ".$itemLocs[$i]->location_id.")";
                $TBD[] = $D;
            }
        }

        for($i = 0; $i < count($npcLocs); $i++)
        {
            if(!Module::queryObject("SELECT * FROM npcs WHERE npc_id = '{$npcLocs[$i]->type_id}'"))
            {
                $D = new stdClass;
                $D->type = "Location";
                $D->id = $npcLocs[$i]->location_id;
                $D->description = "(Loc Npc ".$npcLocs[$i]->location_id.")";
                $TBD[] = $D;
            }
        }

        for($i = 0; $i < count($webpageLocs); $i++)
        {
            if(!Module::queryObject("SELECT * FROM web_pages WHERE web_page_id = '{$webpageLocs[$i]->type_id}'"))
            {
                $D = new stdClass;
                $D->type = "Location";
                $D->id = $webpageLocs[$i]->location_id;
                $D->description = "(Loc WebPage ".$webpageLocs[$i]->location_id.")";
                $TBD[] = $D;
            }
        }

        for($i = 0; $i < count($noteLocs); $i++)
        {
            if(!Module::queryObject("SELECT * FROM notes WHERE note_id = '{$noteLocs[$i]->type_id}'"))
            {
                $D = new stdClass;
                $D->type = "Location";
                $D->id = $noteLocs[$i]->location_id;
                $D->description = "(Loc Note ".$noteLocs[$i]->location_id.")";
                $TBD[] = $D;
            }
        }

        return new returnData(0,$TBD);
    }

    public function pruneMediaForGame($gameId)
    {
        $TBD = array();

        $media = Module::queryArray("SELECT * FROM media WHERE game_id = '{$gameId}'");

        $nodeMediaMap = array();
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
    
                        if(preg_match("@mediaId@i",$attrib_name)) $nodeMediaMap[$attrib_value] = true;
                    }
                }
            }
        }

        for($i = 0; $i < count($media); $i++)
        {
            $mid = $media[$i]->media_id;
            if($nodeMediaMap[$mid]) continue;
            if(Module::queryObject("SELECT * FROM games WHERE game_id = '{$gameId}' AND (game_icon_media_id = '{$mid}' OR pc_media_id = '{$mid}' OR icon_media_id = '{$mid}' OR media_id = '{$mid}')")) continue;
            if(Module::queryObject("SELECT * FROM game_object_tags WHERE game_id = '{$gameId}' AND (media_id = '{$mid}')")) continue;
            if(Module::queryObject("SELECT * FROM game_tags WHERE game_id = '{$gameId}' AND (media_id = '{$mid}')")) continue;
            if(Module::queryObject("SELECT * FROM items WHERE game_id = '{$gameId}' AND (media_id = '{$mid}' OR icon_media_id = '{$mid}')")) continue;
            if(Module::queryObject("SELECT * FROM locations WHERE game_id = '{$gameId}' AND (icon_media_id = '{$mid}')")) continue;
            if(Module::queryObject("SELECT * FROM nodes WHERE game_id = '{$gameId}' AND (media_id = '{$mid}' OR icon_media_id = '{$mid}')")) continue;
            if(Module::queryObject("SELECT * FROM note_content WHERE game_id = '{$gameId}' AND (media_id = '{$mid}')")) continue;
            if(Module::queryObject("SELECT * FROM npcs WHERE game_id = '{$gameId}' AND (media_id = '{$mid}' OR icon_media_id = '{$mid}')")) continue;
            if(Module::queryObject("SELECT * FROM overlays WHERE game_id = '{$gameId}' AND (media_id = '{$mid}')")) continue;
            if(Module::queryObject("SELECT * FROM quests WHERE game_id = '{$gameId}' AND (active_notification_media_id = '{$mid}' OR complete_notification_media_id = '{$mid}' OR active_media_id = '{$mid}' OR complete_media_id = '{$mid}' OR active_icon_media_id = '{$mid}' OR complete_icon_media_id = '{$mid}')")) continue;
            if(Module::queryObject("SELECT * FROM web_pages WHERE game_id = '{$gameId}' AND (icon_media_id = '{$mid}')")) continue;

            $D = new stdClass;
            $D->type = "Media";
            $D->id = $mid;
            $D->description = "(Media ".$mid.")";
            $TBD[] = $D;
        }

        return new returnData(0,$TBD);
    }

    public function pruneNoteContentFromGame($gameId)
    {
        $TBD = array();

        $noteContent = Module::queryArray("SELECT * FROM note_content WHERE game_id = '{$gameId}'");
        for($i = 0; $i < count($noteContent); $i++)
        {
            if(!Module::queryObject("SELECT * FROM notes WHERE note_id = '{$noteContent[$i]->note_id}'"))
            {
                $D = new stdClass;
                $D->type = "NoteContent";
                $D->id = $noteContent[$i]->content_id;
                $D->description = "(NoteContent ".$noteContent[$i]->content_id.")";
                $TBD[] = $D;
            }
        }

        return new returnData(0,$TBD);
    }
}

?>
