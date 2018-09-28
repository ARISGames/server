<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("games.php");
require_once("return_package.php");

require_once("tags.php");
require_once("requirements.php");
require_once("media.php");

class tags extends dbconnection
{
    //Takes in tag JSON, all fields optional except user_id + key
    public static function createTag($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->tag_id = dbconnection::queryInsert(
            "INSERT INTO tags (".
            "game_id,".
            (isset($pack->tag)            ? "tag,"            : "").
            (isset($pack->media_id)       ? "media_id,"       : "").
            (isset($pack->visible)        ? "visible,"        : "").
            (isset($pack->curated)        ? "curated,"        : "").
            (isset($pack->sort_index)     ? "sort_index,"     : "").
            (isset($pack->color)          ? "color,"          : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->tag)            ? "'".addslashes($pack->tag)."',"            : "").
            (isset($pack->media_id)       ? "'".addslashes($pack->media_id)."',"       : "").
            (isset($pack->visible)        ? "'".addslashes($pack->visible)."',"        : "").
            (isset($pack->curated)        ? "'".addslashes($pack->curated)."',"        : "").
            (isset($pack->sort_index)     ? "'".addslashes($pack->sort_index)."',"     : "").
            (isset($pack->color)          ? "'".addslashes($pack->color)."',"          : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        games::bumpGameVersion($pack);
        return tags::getTag($pack);
    }

    //Takes in tag JSON, all fields optional except user_id + key
    public static function createObjectTag($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->object_tag_id = dbconnection::queryInsert(
            "INSERT INTO object_tags (".
            "game_id,".
            (isset($pack->object_type) ? "object_type," : "").
            (isset($pack->object_id)   ? "object_id,"   : "").
            (isset($pack->tag_id)      ? "tag_id,"      : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->object_type) ? "'".addslashes($pack->object_type)."'," : "").
            (isset($pack->object_id)   ? "'".addslashes($pack->object_id)."',"   : "").
            (isset($pack->tag_id)      ? "'".addslashes($pack->tag_id)."',"      : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        games::bumpGameVersion($pack);
        return tags::getObjectTag($pack);
    }

    public static function updateTag($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM tags WHERE tag_id = '{$pack->tag_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE tags SET ".
            (isset($pack->tag)            ? "tag            = '".addslashes($pack->tag)."', "            : "").
            (isset($pack->media_id)       ? "media_id       = '".addslashes($pack->media_id)."', "       : "").
            (isset($pack->visible)        ? "visible        = '".addslashes($pack->visible)."', "        : "").
            (isset($pack->curated)        ? "curated        = '".addslashes($pack->curated)."', "        : "").
            (isset($pack->sort_index)     ? "sort_index     = '".addslashes($pack->sort_index)."', "     : "").
            (isset($pack->color)          ? "color          = '".addslashes($pack->color)."', "          : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE tag_id = '{$pack->tag_id}'"
        );

        games::bumpGameVersion($pack);
        return tags::getTag($pack);
    }

    private static function tagObjectFromSQL($sql_tag)
    {
        if(!$sql_tag) return $sql_tag;
        $tag = new stdClass();
        $tag->tag_id         = $sql_tag->tag_id;
        $tag->game_id        = $sql_tag->game_id;
        $tag->tag            = $sql_tag->tag;
        $tag->media_id       = $sql_tag->media_id;
        if ($sql_tag->media_id) {
            $tag->media      = media::getMedia($sql_tag);
        }
        $tag->visible        = $sql_tag->visible;
        $tag->curated        = $sql_tag->curated;
        $tag->sort_index     = $sql_tag->sort_index;
        $tag->color          = $sql_tag->color;

        return $tag;
    }

    private static function objectTagObjectFromSQL($sql_object_tag)
    {
        if(!$sql_object_tag) return $sql_object_tag;
        $tag = new stdClass();
        $tag->object_tag_id = $sql_object_tag->object_tag_id;
        $tag->object_type   = $sql_object_tag->object_type;
        $tag->object_id     = $sql_object_tag->object_id;
        $tag->tag_id        = $sql_object_tag->tag_id;

        return $tag;
    }

    public static function getTag($pack)
    {
        $sql_tag = dbconnection::queryObject("SELECT * FROM tags WHERE tag_id = '{$pack->tag_id}' LIMIT 1");
        if(!$sql_tag) return new return_package(2, NULL, "The tag you've requested does not exist");
        return new return_package(0,tags::tagObjectFromSQL($sql_tag));
    }

    public static function getObjectTag($pack)
    {
        $sql_object_tag = dbconnection::queryObject("SELECT * FROM object_tags WHERE object_tag_id = '{$pack->object_tag_id}' LIMIT 1");
        if(!$sql_object_tag) return new return_package(2, NULL, "The tag you've requested does not exist");
        return new return_package(0,tags::objectTagObjectFromSQL($sql_object_tag));
    }

    public static function getTagsForGame($pack)
    {
        $game_id = intval($pack->game_id);
        $sql_tags = dbconnection::queryArray("SELECT * FROM tags WHERE game_id = '{$game_id}'");
        $tags = array();
        for($i = 0; $i < count($sql_tags); $i++) {
            if($ob = tags::tagObjectFromSQL($sql_tags[$i])) $tags[] = $ob;
        }

        // Include field options for the "pin" field of a siftr for legacy clients
        $api = (isset($pack->api) ? intval($pack->api) : 0);
        if ($api < 2) {
            $sql_options = dbconnection::queryArray("SELECT field_options.* FROM games JOIN field_options ON games.field_id_pin = field_options.field_id WHERE games.game_id = '{$game_id}'");
            for($i = 0; $i < count($sql_options); $i++) {
                $sql_option = $sql_options[$i];
                if ($sql_option) {
                    $tag = new stdClass();
                    $tag->tag_id     = $sql_option->field_option_id + 10000000;
                    $tag->game_id    = $sql_option->game_id;
                    $tag->tag        = $sql_option->option;
                    $tag->media_id   = 0;
                    $tag->visible    = 1;
                    $tag->curated    = 0;
                    $tag->sort_index = $sql_option->sort_index;
                    $tag->color      = $sql_option->color;
                    $tags[] = $tag;
                }
            }
        }

        return new return_package(0,$tags);

    }

    public static function getObjectTagsForGame($pack)
    {
        $sql_object_tags = dbconnection::queryArray("SELECT * FROM object_tags WHERE game_id = '{$pack->game_id}'");
        $object_tags = array();
        for($i = 0; $i < count($sql_object_tags); $i++)
            if($ob = tags::objectTagObjectFromSQL($sql_object_tags[$i])) $object_tags[] = $ob;

        return new return_package(0,$object_tags);

    }

    public static function getObjectTagsForObject($pack)
    {
        $sql_object_tags = dbconnection::queryArray("SELECT * FROM object_tags WHERE game_id = '{$pack->game_id}' AND object_type = '{$pack->object_type}' AND object_id = '{$pack->object_id}'");
        $object_tags = array();
        for($i = 0; $i < count($sql_object_tags); $i++)
            if($ob = tags::objectTagObjectFromSQL($sql_object_tags[$i])) $object_tags[] = $ob;

        return new return_package(0,$object_tags);

    }

    public static function deleteTag($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM tags WHERE tag_id = '{$pack->tag_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM tags WHERE tag_id = '{$pack->tag_id}' LIMIT 1");
        //cleanup
        if ($pack->new_tag_id) {
            dbconnection::query("UPDATE object_tags SET tag_id = '{$pack->new_tag_id}' WHERE tag_id = '{$pack->tag_id}'");
        } else {
            $tags = dbconnection::queryArray("SELECT * FROM object_tags WHERE tag_id = '{$pack->tag_id}'");
            for($i = 0; $i < count($tags); $i++)
            {
                $pack->object_tag_id = $tags[$i]->object_tag_id;
                tags::deleteObjectTag($pack);
            }
        }

        $reqAtoms = dbconnection::queryArray("SELECT * FROM requirement_atoms WHERE requirement = 'PLAYER_HAS_TAGGED_ITEM' AND content_id = '{$pack->tag_id}'");
        for($i = 0; $i < count($reqAtoms); $i++)
        {
            $pack->requirement_atom_id = $reqAtoms[$i]->requirement_atom_id;
            requirements::deleteRequirementAtom($pack);
        }

        $reqAtoms = dbconnection::queryArray("SELECT * FROM requirement_atoms WHERE requirement = 'GAME_HAS_TAGGED_ITEM' AND content_id = '{$pack->tag_id}'");
        for($i = 0; $i < count($reqAtoms); $i++)
        {
            $pack->requirement_atom_id = $reqAtoms[$i]->requirement_atom_id;
            requirements::deleteRequirementAtom($pack);
        }

        $reqAtoms = dbconnection::queryArray("SELECT * FROM requirement_atoms WHERE requirement = 'PLAYER_HAS_NOTE_WITH_TAG' AND content_id = '{$pack->tag_id}'");
        for($i = 0; $i < count($reqAtoms); $i++)
        {
            $pack->requirement_atom_id = $reqAtoms[$i]->requirement_atom_id;
            requirements::deleteRequirementAtom($pack);
        }

        games::bumpGameVersion($pack);
        return new return_package(0);
    }

    public static function deleteObjectTag($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM object_tags WHERE object_tag_id = '{$pack->object_tag_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM object_tags WHERE object_tag_id = '{$pack->object_tag_id}' LIMIT 1");
        games::bumpGameVersion($pack);
        return new return_package(0);
    }

    public static function deleteObjectTagsForObject($pack)
    {
      $pack->auth->game_id = $pack->game_id;
      $pack->auth->permission = "read_write";
      if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

      dbconnection::query("DELETE FROM object_tags WHERE game_id = '{$pack->game_id}' AND object_type = '{$pack->object_type}' AND object_id = '{$pack->object_id}';");
      games::bumpGameVersion($pack);
    }

    public static function countObjectsWithTag($pack)
    {
        $object_type = addslashes($pack->object_type);
        $tag_id = intval($pack->tag_id);

        $obj = dbconnection::queryObject(
            "SELECT COUNT(1) AS count FROM object_tags WHERE object_type = '{$object_type}' AND tag_id = '{$tag_id}'"
        );
        return new return_package(0, $obj);
    }
}
?>
