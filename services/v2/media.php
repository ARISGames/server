<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("return_package.php");

class media extends dbconnection
{
    private static function defaultMediaObject($mediaId)
    {
        $fake_sql_media = new stdClass;
        $fake_sql_media->game_id = 0;
        $fake_sql_media->media_id = $mediaId;
        $fake_sql_media->name = "Default NPC";
        $fake_sql_media->file_folder = "0";
        $fake_sql_media->file_name = "npc.png";
        return media::mediaObjectFromSQL($fake_sql_media);
    }

    //Takes in media JSON, all fields optional except user_id + key
    public static function createMedia($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $filenameext = strtolower(substr($pack->file_name,strrpos($pack->file_name,'.')+1));
        if($filenameext == "jpeg") $filenameext = "jpg"; //sanity
        $filename = md5((string)microtime().$pack->file_name);
        $newfilename = 'aris'.$filename.'.'.$filenameext;
        $resizedfilename = 'aris'.$filename.'_resized.'.$filenameext;
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
        $fspath      = Config::v2_gamedata_folder."/".$filefolder."/".$newfilename;
        $resizedpath = Config::v2_gamedata_folder."/".$filefolder."/".$resizedfilename;
        $fsthumbpath = Config::v2_gamedata_folder."/".$filefolder."/".$newthumbfilename;

        $fp = fopen($fspath, 'w');
        if(!$fp) return new return_package(1,NULL,"Couldn't open file:$fspath");
        fwrite($fp,base64_decode($pack->data));
        fclose($fp);



        $did_resize = false;
        if($filenameext == "jpg" || $filenameext == "png" || $filenameext == "gif")
        {
            if(isset($pack->resize))
            {
                $image = new Imagick($fspath);

                // Reorient based on EXIF tag
                switch ($image->getImageOrientation()) {
                    case Imagick::ORIENTATION_UNDEFINED:
                        // We assume normal orientation
                        break;
                    case Imagick::ORIENTATION_TOPLEFT:
                        // All good
                        break;
                    case Imagick::ORIENTATION_TOPRIGHT:
                        $image->flopImage();
                        break;
                    case Imagick::ORIENTATION_BOTTOMRIGHT:
                        $image->rotateImage('#000', 180);
                        break;
                    case Imagick::ORIENTATION_BOTTOMLEFT:
                        $image->rotateImage('#000', 180);
                        $image->flopImage();
                        break;
                    case Imagick::ORIENTATION_LEFTTOP:
                        $image->rotateImage('#000', 90);
                        $image->flopImage();
                        break;
                    case Imagick::ORIENTATION_RIGHTTOP:
                        $image->rotateImage('#000', 90);
                        break;
                    case Imagick::ORIENTATION_RIGHTBOTTOM:
                        $image->rotateImage('#000', -90);
                        $image->flopImage();
                        break;
                    case Imagick::ORIENTATION_LEFTBOTTOM:
                        $image->rotateImage('#000', -90);
                        break;
                }
                $image->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);

                // Resize image proportionally so min(width, height) == $pack->resize
                if ($image->getImageWidth() < $image->getImageHeight()) {
                  $image->resizeImage($pack->resize, 0, Imagick::FILTER_LANCZOS, 1);
                }
                else {
                  $image->resizeImage(0, $pack->resize, Imagick::FILTER_LANCZOS, 1);
                }

                $image->setImageCompression(Imagick::COMPRESSION_JPEG);
                $image->setImageCompressionQuality(40);
                $image->writeImage($resizedpath);
                $did_resize = true;
            }

            $image = new Imagick($fspath);
            //aspect fill to 128x128
            $w = $image->getImageWidth();
            $h = $image->getImageHeight();
            if($w < $h) $image->thumbnailImage(128, 0, 1, 1);
            else        $image->thumbnailImage(0, 128, 1, 1);
            //crop around center
            $w = $image->getImageWidth();
            $h = $image->getImageHeight();
            $image->cropImage(128, 128, ($w-128)/2, ($h-128)/2);
            $image->writeImage($fsthumbpath);
        }

        if($did_resize) unlink($fspath); // after making the 128 thumbnail

        $pack->media_id = dbconnection::queryInsert(
            "INSERT INTO media (".
            "file_folder,".
            "file_name,".
            (isset($pack->game_id)       ? "game_id,"      : "").
            (isset($pack->auth->user_id) ? "user_id,"      : "").
            (isset($pack->name)          ? "name,"         : "").
            "created".
            ") VALUES (".
            "'".$filefolder."',".
            "'".($did_resize ? $resizedfilename : $newfilename)."',".
            (isset($pack->game_id)       ? "'".addslashes($pack->game_id)."',"       : "").
            (isset($pack->auth->user_id) ? "'".addslashes($pack->auth->user_id)."'," : "").
            (isset($pack->name)          ? "'".addslashes($pack->name)."',"          : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        return media::getMedia($pack);
    }

    //Takes in game JSON, all fields optional except user_id + key
    public static function updateMedia($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        //boring, but this is the only immutable property of media
        dbconnection::query(
            "UPDATE media SET ".
            (isset($pack->name) ? "name = '".addslashes($pack->name)."', " : "").
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
        $media->name         = $sql_media->name;
        $media->file_name    = $sql_media->file_name;

        $filenametitle = substr($sql_media->file_name,0,strrpos($sql_media->file_name,'.'));
        $filenameext   = substr($sql_media->file_name,strrpos($sql_media->file_name,'.'));

        $media->url       = Config::v2_gamedata_www_path."/".$sql_media->file_folder."/".$sql_media->file_name;
        $media->thumb_url = Config::v2_gamedata_www_path."/".$sql_media->file_folder."/".$filenametitle."_128".$filenameext;

        return $media;
    }

    public static function getMedia($pack)
    {
        if(!($sql_media = dbconnection::queryObject("SELECT * FROM media WHERE media_id = '{$pack->media_id}' LIMIT 1")))
            return new return_package(0,media::defaultMediaObject($pack->media_id));
        return new return_package(0, media::mediaObjectFromSQL($sql_media));
    }

    public static function getMediaForGame($pack)
    {
        $sql_medias = dbconnection::queryArray("SELECT * FROM media WHERE (game_id = '{$pack->game_id}' OR (game_id = 0 AND user_id = 0))");
        $medias = array();
        for($i = 0; $i < count($sql_medias); $i++)
            if($ob = media::mediaObjectFromSQL($sql_medias[$i])) $medias[] = $ob;

        return new return_package(0, $medias);
    }

    public static function deleteMedia($pack)
    {
        $media_sql = dbconnection::queryObject("SELECT * FROM media WHERE media_id = '{$pack->media_id}'");

        $pack->auth->game_id = $media_sql->game_id;
        $pack->auth->permission = "read_write";
        if(!editors::authenticateGameEditor($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        if(!unlink(Config::v2_gamedata_folder."/".$media_sql->file_folder."/".$media_sql->file_name))
            return new return_package(1, "Could not delete file.");

        dbconnection::query("DELETE FROM media WHERE media_id = '{$pack->media_id}' LIMIT 1");
        //cleanup
        dbconnection::query("UPDATE games SET media_id = 0 WHERE media_id = '{$pack->media_id}'");
        dbconnection::query("UPDATE games SET icon_media_id = 0 WHERE icon_media_id = '{$pack->media_id}'");
        dbconnection::query("UPDATE tabs SET icon_media_id = 0 WHERE icon_media_id = '{$pack->media_id}'");
        dbconnection::query("UPDATE items SET media_id = 0 WHERE media_id = '{$pack->media_id}'");
        dbconnection::query("UPDATE items SET icon_media_id = 0 WHERE icon_media_id = '{$pack->media_id}'");
        dbconnection::query("UPDATE dialogs SET icon_media_id = 0 WHERE icon_media_id = '{$pack->media_id}'");
        dbconnection::query("UPDATE dialog_characters SET media_id = 0 WHERE media_id = '{$pack->media_id}'");
        dbconnection::query("UPDATE plaques SET media_id = 0 WHERE media_id = '{$pack->media_id}'");
        dbconnection::query("UPDATE plaques SET icon_media_id = 0 WHERE icon_media_id = '{$pack->media_id}'");
        dbconnection::query("UPDATE web_pages SET icon_media_id = 0 WHERE icon_media_id = '{$pack->media_id}'");
        dbconnection::query("UPDATE tags SET media_id = 0 WHERE media_id = '{$pack->media_id}'");
        dbconnection::query("UPDATE overlays SET media_id = 0 WHERE media_id = '{$pack->media_id}'");
        dbconnection::query("UPDATE note_media SET media_id = 0 WHERE media_id = '{$pack->media_id}'");
        dbconnection::query("UPDATE quests SET active_media_id = 0 WHERE active_media_id = '{$pack->media_id}'");
        dbconnection::query("UPDATE quests SET active_icon_media_id = 0 WHERE active_icon_media_id = '{$pack->media_id}'");
        dbconnection::query("UPDATE quests SET complete_media_id = 0 WHERE complete_media_id = '{$pack->media_id}'");
        dbconnection::query("UPDATE quests SET complete_icon_media_id = 0 WHERE complete_icon_media_id = '{$pack->media_id}'");
        dbconnection::query("UPDATE triggers SET icon_media_id = 0 WHERE icon_media_id = '{$pack->media_id}'");
        dbconnection::query("UPDATE factories SET trigger_icon_media_id = 0 WHERE trigger_icon_media_id = '{$pack->media_id}'");
        dbconnection::query("UPDATE users SET media_id = 0 WHERE media_id = '{$pack->media_id}'");
        return new return_package(0);
    }
}
?>
