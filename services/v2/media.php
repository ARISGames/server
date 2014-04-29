<?php
require_once("dbconnection.php");
require_once("return_package.php");
require_once("../../libraries/wideimage/WideImage.php");

class media extends dbconnection
{
    //This will be the returned format of every media query
    private function mediaObjectFromSQL($sql_media)
    {
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

    private function defaultMediaObject($mediaId)
    {
        $fake_sql_media = new stdClass;
        $fake_sql_media->game_id = 0;
        $fake_sql_media->media_id = $mediaId;
        $fake_sql_media->display_name = "Default NPC";
        $fake_sql_media->file_folder = "0";
        $fake_sql_media->file_name = "npc.png";
        return media::mediaObjectFromSQL($fake_sql_media);
    }

    public function getMediaForGame($gameId)
    {
        $sql_medias = dbconnection::queryArray("SELECT * FROM media WHERE (game_id = '{$gameId}' OR game_id = 0) AND SUBSTRING(file_path,1,1) != 'p'");

        $medias = array();
        for($i = 0; $i < count($sql_medias); $i++)
            $medias[] = media::mediaObjectFromSQL($sql_medias[$i]);
        return new return_package(0, $medias);
    }

    //Takes in media JSON, all fields optional except user_id + key
    public static function createMediaJSON($glob)
    {
        $data = file_get_contents("php://input");
        $glob = json_decode($data);
        return media::createMedia($glob);
    }

    public static function createMedia($pack)
    {
        //commented out because we need to allow anyone to create media for any game due to notes...
        /*
        if(($pack->game_id && !editors::authenticateGameEditor($pack->game_id, $pack->auth->user_id, $pack->auth->key, "read_write")) //game media
         || !editors::authenticateEditor($pack->auth->user_id, $pack->auth->key, "read_write")) //player media
            return new return_package(6, NULL, "Failed Authentication");
        */

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

        $mediaId = dbconnection::queryInsert(
            "INSERT INTO media (".
            "file_folder,".
            "file_name,".
            ($pack->game_id      ? "game_id,"      : "").
            ($pack->display_name ? "display_name," : "").
            "created".
            ") VALUES (".
            "'".$filefolder."',".
            "'".$newfilename."',".
            ($pack->game_id      ? "'".addslashes($pack->game_id)."',"      : "").
            ($pack->display_name ? "'".addslashes($pack->display_name)."'," : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return media::getMedia($mediaId);
    }

    //Takes in game JSON, all fields optional except user_id + key
    public static function updateMediaJSON($glob)
    {
        $data = file_get_contents("php://input");
        $glob = json_decode($data);
        return media::updateMedia($glob);
    }

    public static function updateMedia($pack)
    {
        $gameId = dbconnection::queryObject("SELECT * FROM media WHERE media_id = '{$pack->media_id}'")->game_id;
        //commented out because we need to allow anyone to update media for any game due to notes...
        /*
        if(!editors::authenticateGameEditor($gameId, $pack->auth->user_id, $pack->auth->key, "read_write"))
            return new return_package(6, NULL, "Failed Authentication");
        */

        //boring, but this is the only immutable property of media
        dbconnection::query(
            "UPDATE media SET ".
            ($pack->display_name ? "display_name = '".addslashes($pack->display_name)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE media_id = '{$pack->media_id}'"
        );

        return media::getMedia($pack->media_id);
    }

    public function getMedia($mediaId)
    {
        if(!($sql_media = dbconnection::queryObject("SELECT * FROM media WHERE media_id = '{$mediaId}' LIMIT 1")))
            return new return_package(0,media::defaultMediaObject($mediaId));
        return new return_package(0, media::mediaObjectFromSQL($sql_media));
    }	

    public static function deleteMedia($mediaId, $userId, $key)
    {
        $media_sql = dbconnection::queryObject("SELECT * FROM media WHERE media_id = '{$mediaId}'");
        if(!editors::authenticateGameEditor($media_sql->gameId, $userId, $key, "read_write")) return new return_package(6, NULL, "Failed Authentication");

        if(!unlink(Config::gamedataFSPath."/".$media_sql->file_folder."/".$media_sql->file_name)) 
            return new return_package(1, "Could not delete file.");

        dbconnection::query("DELETE FROM media WHERE media_id = '{$mediaId}' LIMIT 1");
        return new return_package(0);
    }
}
?>
