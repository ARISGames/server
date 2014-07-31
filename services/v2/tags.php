<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("return_package.php");

class tags extends dbconnection
{	
    //Takes in tag JSON, all fields optional except user_id + key
    public static function createTag($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return tags::createTagPack($glob); }
    public static function createTagPack($pack)
    {
        $pack->auth->game_id = $pack->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $pack->tag_id = dbconnection::queryInsert(
            "INSERT INTO tags (".
            "game_id,".
            (isset($pack->tag)            ? "tag,"            : "").
            (isset($pack->media_id)       ? "media_id,"       : "").
            (isset($pack->player_created) ? "player_created," : "").
            (isset($pack->visible)        ? "visible,"        : "").
            (isset($pack->sort_index)     ? "sort_index,"     : "").
            "created".
            ") VALUES (".
            "'".addslashes($pack->game_id)."',".
            (isset($pack->tag)            ? "'".addslashes($pack->tag)."',"            : "").
            (isset($pack->media_id)       ? "'".addslashes($pack->media_id)."',"       : "").
            (isset($pack->player_created) ? "'".addslashes($pack->player_created)."'," : "").
            (isset($pack->visible)        ? "'".addslashes($pack->visible)."',"        : "").
            (isset($pack->sort_index)     ? "'".addslashes($pack->sort_index)."',"     : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return tags::getTagPack($pack);
    }

    //Takes in tag JSON, all fields optional except user_id + key
    public static function createObjectTag($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return tags::createObjectTagPack($glob); }
    public static function createObjectTagPack($pack)
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

        return tags::getObjectTagPack($pack);
    }

    public static function updateTag($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return tags::updateTagPack($glob); }
    public static function updateTagPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM tags WHERE tag_id = '{$pack->tag_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE tags SET ".
            (isset($pack->tag)            ? "tag            = '".addslashes($pack->tag)."', "            : "").
            (isset($pack->media_id)       ? "media_id       = '".addslashes($pack->media_id)."', "       : "").
            (isset($pack->player_created) ? "player_created = '".addslashes($pack->player_created)."', " : "").
            (isset($pack->visible)        ? "visible        = '".addslashes($pack->visible)."', "        : "").
            (isset($pack->sort_index)     ? "sort_index     = '".addslashes($pack->sort_index)."', "     : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE tag_id = '{$pack->tag_id}'"
        );

        return tags::getTagPack($pack);
    }

    private static function tagObjectFromSQL($sql_tag)
    {
        if(!$sql_tag) return $sql_tag;
        $tag = new stdClass();
        $tag->tag_id         = $sql_tag->tag_id;
        $tag->game_id        = $sql_tag->game_id;
        $tag->tag            = $sql_tag->tag;
        $tag->media_id       = $sql_tag->media_id;
        $tag->player_created = $sql_tag->player_created;
        $tag->visible        = $sql_tag->visible;
        $tag->sort_index     = $sql_tag->sort_index;

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

    public static function getTag($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return tags::getTagPack($glob); }
    public static function getTagPack($pack)
    {
        $sql_tag = dbconnection::queryObject("SELECT * FROM tags WHERE tag_id = '{$pack->tag_id}' LIMIT 1");
        if(!$sql_tag) return new return_package(2, NULL, "The tag you've requested does not exist");
        return new return_package(0,tags::tagObjectFromSQL($sql_tag));
    }

    public static function getObjectTag($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return tags::getObjectTagPack($glob); }
    public static function getObjectTagPack($pack)
    {
        $sql_object_tag = dbconnection::queryObject("SELECT * FROM object_tags WHERE object_tag_id = '{$pack->object_tag_id}' LIMIT 1");
        if(!$sql_object_tag) return new return_package(2, NULL, "The tag you've requested does not exist");
        return new return_package(0,tags::objectTagObjectFromSQL($sql_object_tag));
    }

    public static function getTagsForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return tags::getTagsForGamePack($glob); }
    public static function getTagsForGamePack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $sql_tags = dbconnection::queryArray("SELECT * FROM tags WHERE game_id = '{$pack->game_id}'");
        $tags = array();
        for($i = 0; $i < count($sql_tags); $i++)
            if($ob = tags::tagObjectFromSQL($sql_tags[$i])) $tags[] = $ob;

        return new return_package(0,$tags);

    }

    public static function getObjectTagsForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return tags::getObjectTagsForGamePack($glob); }
    public static function getObjectTagsForGamePack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $sql_object_tags = dbconnection::queryArray("SELECT * FROM object_tags WHERE game_id = '{$pack->game_id}'");
        $object_tags = array();
        for($i = 0; $i < count($sql_object_tags); $i++)
            if($ob = tags::objectTagObjectFromSQL($sql_object_tags[$i])) $object_tags[] = $ob;

        return new return_package(0,$object_tags);

    }

    public static function getObjectTagsForObject($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return tags::getObjectTagsForObjectPack($glob); }
    public static function getObjectTagsForObjectPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $sql_object_tags = dbconnection::queryArray("SELECT * FROM object_tags WHERE game_id = '{$pack->game_id}' AND object_type = '{$pack->object_type}' AND object_id = '{$pack->object_id}'");
        $object_tags = array();
        for($i = 0; $i < count($sql_object_tags); $i++)
            if($ob = tags::objectTagObjectFromSQL($sql_object_tags[$i])) $object_tags[] = $ob;

        return new return_package(0,$object_tags);

    }

    public static function deleteTag($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return tags::deleteTagPack($glob); }
    public static function deleteTagPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM tags WHERE tag_id = '{$pack->tag_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM tags WHERE tag_id = '{$pack->tag_id}' LIMIT 1");
        //cleanup
        $tags = dbconnection::queryArray("SELECT * FROM object_tags WHERE tag_id = '{$pack->tag_id}'");
        for($i = 0; $i < count($tags); $i++)
        {
            $pack->object_tag_id = $tags[$i]->object_tag_id;
            tags::deleteObjectTagPack($pack);
        }

        $reqAtoms = dbconnection::queryArray("SELECT * FROM requirement_atoms WHERE requirement = 'PLAYER_HAS_TAGGED_ITEM' AND content_id = '{$pack->tag_id}'");
        for($i = 0; $i < count($reqAtoms); $i++)
        {
            $pack->requirement_atom_id = $reqAtoms[$i]->requirement_atom_id;
            requirements::deleteRequirementAtomPack($pack);
        }

        $reqAtoms = dbconnection::queryArray("SELECT * FROM requirement_atoms WHERE requirement = 'PLAYER_HAS_NOTE_WITH_TAG' AND content_id = '{$pack->tag_id}'");
        for($i = 0; $i < count($reqAtoms); $i++)
        {
            $pack->requirement_atom_id = $reqAtoms[$i]->requirement_atom_id;
            requirements::deleteRequirementAtomPack($pack);
        }

        return new return_package(0);
    }

    public static function deleteObjectTag($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return tags::deleteObjectTagPack($glob); }
    public static function deleteObjectTagPack($pack)
    {
        $pack->auth->game_id = dbconnection::queryObject("SELECT * FROM object_tags WHERE object_tag_id = '{$pack->object_tag_id}'")->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query("DELETE FROM object_tags WHERE object_tag_id = '{$pack->object_tag_id}' LIMIT 1");
        return new return_package(0);
    }
}
?>
