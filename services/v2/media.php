<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("return_package.php");
require_once("../../libraries/wideimage/WideImage.php");

class media extends dbconnection
{
    private static function defaultMediaObject($mediaId)
    {
        $fake_sql_media = new stdClass;
        $fake_sql_media->game_id = 0;
        $fake_sql_media->media_id = $mediaId;
        $fake_sql_media->display_name = "Default NPC";
        $fake_sql_media->file_folder = "0";
        $fake_sql_media->file_name = "npc.png";
        return media::mediaObjectFromSQL($fake_sql_media);
    }

    //Takes in media JSON, all fields optional except user_id + key
    public static function createMedia($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return media::createMediaPack($glob); }
    public static function createMediaPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $filenameext = substr($pack->file_name,strrpos($pack->file_name,'.')+1);
        $filename = md5((string)microtime().$pack->file_name);
        $newfilename = 'aris'.$filename.'.'.$filenameext;
        $newthumbfilename = 'aris'.$filename.'_128.'.$filenameext;

        if(
                //Images
                $filenameext != "jpg" &&
                $filenameext != "png" &&
                $filenameext != "gif" &&
                //Video
                $filenameext != "mp4" &&
                $filenameext != "mov" &&
                $filenameext != "m4v" &&
                $filenameext != "3gp" &&
                //Audio
                $filenameext != "caf" &&
                $filenameext != "mp3" &&
                $filenameext != "aac" &&
                $filenameext != "m4a"
        )
        return new return_package(1,NULL,"Invalid filetype: '{$filenameext}'");

        $filefolder = "";
        if($pack->game_id) $filefolder = $pack->game_id;
        else               $filefolder = "players";
        $fspath      = Config::gamedataFSPath."/".$filefolder."/".$newfilename;
        $fsthumbpath = Config::gamedataFSPath."/".$filefolder."/".$newthumbfilename;

        $fp = fopen($fspath, 'w');
        if(!$fp) return new return_package(1,NULL,"Couldn't open file:$fspath");
        fwrite($fp,base64_decode($pack->data));
        fclose($fp);

        if($filenameext == "jpg" || $filenameext == "png" || $filenameext == "gif")
        {
            $thumb = WideImage::load($fspath);
            $thumb = $thumb->resize(128, 128, 'outside');
            $thumb = $thumb->crop('center','center',128,128);
            $thumb->saveToFile($fsthumbpath);
        }

        $pack->media_id = dbconnection::queryInsert(
            "INSERT INTO media (".
            "file_folder,".
            "file_name,".
            ($pack->game_id       ? "game_id,"      : "").
            ($pack->auth->user_id ? "user_id,"      : "").
            ($pack->display_name  ? "display_name," : "").
            "created".
            ") VALUES (".
            "'".$filefolder."',".
            "'".$newfilename."',".
            ($pack->game_id       ? "'".addslashes($pack->game_id)."',"       : "").
            ($pack->auth->user_id ? "'".addslashes($pack->auth->user_id)."'," : "").
            ($pack->display_name  ? "'".addslashes($pack->display_name)."',"  : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return media::getMediaPack($pack);
    }

    //Takes in game JSON, all fields optional except user_id + key
    public static function updateMedia($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return media::updateMediaPack($glob); }
    public static function updateMediaPack($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        //boring, but this is the only immutable property of media
        dbconnection::query(
            "UPDATE media SET ".
            ($pack->display_name ? "display_name = '".addslashes($pack->display_name)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE media_id = '{$pack->media_id}'"
        );

        return media::getMedia($pack);
    }

    private static function mediaObjectFromSQL($sql_media)
    {
        if(!$sql_media) return $sql_media;
        $media = new stdClass();
        $media->media_id     = $sql_media->media_id;
        $media->game_id      = $sql_media->game_id;
        $media->display_name = $sql_media->display_name;
        $media->file_name    = $sql_media->file_name;

        $filenametitle = substr($sql_media->file_name,0,strrpos($sql_media->file_name,'.'));
        $filenameext   = substr($sql_media->file_name,strrpos($sql_media->file_name,'.'));

        $media->url       = Config::gamedataWWWPath."/".$sql_media->file_folder."/".$sql_media->file_name;
        $media->thumb_url = Config::gamedataWWWPath."/".$sql_media->file_folder."/".$filenametitle."_128".$filenameext;

        return $media;
    }

    public static function getMedia($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return media::getMediaPack($glob); }
    public static function getMediaPack($pack)
    {
        if(!($sql_media = dbconnection::queryObject("SELECT * FROM media WHERE media_id = '{$pack->media_id}' LIMIT 1")))
            return new return_package(0,media::defaultMediaObject($pack->media_id));
        return new return_package(0, media::mediaObjectFromSQL($sql_media));
    }	

    public static function getMediaForGame($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return media::getMediaForGamePack($glob); }
    public static function getMediaForGamePack($pack)
    {
        $sql_medias = dbconnection::queryArray("SELECT * FROM media WHERE (game_id = '{$pack->game_id}' OR game_id = 0)");
        $medias = array();
        for($i = 0; $i < count($sql_medias); $i++)
            $medias[] = media::mediaObjectFromSQL($sql_medias[$i]);

        return new return_package(0, $medias);
    }

    public static function deleteMedia($glob) { $data = file_get_contents("php://input"); $glob = json_decode($data); return media::deleteMediaPack($glob); }
    public static function deleteMediaPack($pack)
    {
        $media_sql = dbconnection::queryObject("SELECT * FROM media WHERE media_id = '{$pack->media_id}'");

        $pack->auth->game_id = $media_sql->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        if(!unlink(Config::gamedataFSPath."/".$media_sql->file_folder."/".$media_sql->file_name)) 
            return new return_package(1, "Could not delete file.");

        dbconnection::query("DELETE FROM media WHERE media_id = '{$pack->media_id}' LIMIT 1");
        return new return_package(0);
    }
}
?>
