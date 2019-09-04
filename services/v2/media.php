<?php
require_once("dbconnection.php");
require_once("users.php");
require_once("editors.php");
require_once("games.php");
require_once("client.php");
require_once("return_package.php");

class media extends dbconnection
{
    private static function defaultMediaObject($mediaId)
    {
        $fake_sql_media = new stdClass;
        $fake_sql_media->game_id = 0;
        $fake_sql_media->media_id = $mediaId;
        $fake_sql_media->name = "ARIS";
        $fake_sql_media->file_folder = "0";
        $fake_sql_media->file_name = "aris.png";
        $fake_sql_media->autoplay = 0;
        return media::mediaObjectFromSQL($fake_sql_media);
    }

    public static function createMediaFromRawUpload($pack)
    {
        $pack->data = base64_encode(file_get_contents(Config::raw_uploads_folder . '/' . $pack->raw_upload_id));
        return media::createMedia($pack);
        // TODO delete raw file
    }

    //Takes in media JSON, all fields optional except user_id + key
    public static function createMedia($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        $filenameext = strtolower(substr($pack->file_name,strrpos($pack->file_name,'.')+1));
        if($filenameext == "jpeg") $filenameext = "jpg"; //sanity
        $filename = md5((string)microtime().$pack->file_name);
        $newfilename         = 'aris'.$filename.'.'.$filenameext;
        $newresizedfilename  = 'aris'.$filename.'_resized.'.$filenameext;
        $newbigthumbfilename = 'aris'.$filename.'_256.'.$filenameext;
        $newthumbfilename    = 'aris'.$filename.'_128.'.$filenameext;

        // Make sure playerUploaded requirements keep in sync with this list
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
                $filenameext != "m4a" &&
                //3D
                $filenameext != "zip"
        )
        return new return_package(1,NULL,"Invalid filetype: '{$filenameext}'");

        $filefolder = "";
        if($pack->game_id) $filefolder = $pack->game_id;
        else               $filefolder = "players";
        $fspath         = Config::v2_gamedata_folder."/".$filefolder."/".$newfilename;
        $fsresizedpath  = Config::v2_gamedata_folder."/".$filefolder."/".$newresizedfilename;
        $fsbigthumbpath = Config::v2_gamedata_folder."/".$filefolder."/".$newbigthumbfilename;
        $fsthumbpath    = Config::v2_gamedata_folder."/".$filefolder."/".$newthumbfilename;

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
                $w = $image->getImageWidth();
                $h = $image->getImageHeight();
                if($w < $h) $image->resizeImage($pack->resize, ($pack->resize/$w)*$h, Imagick::FILTER_LANCZOS, 1);
                else        $image->resizeImage(($pack->resize/$h)*$w, $pack->resize, Imagick::FILTER_LANCZOS, 1);

                $image->setImageCompression(Imagick::COMPRESSION_JPEG);
                $image->setImageCompressionQuality(40);
                $image->writeImage($fsresizedpath);
                $did_resize = true;
            }

            //aspect fill to 256x256
            $image = new Imagick(isset($pack->resize) ? $fsresizedpath : $fspath);
            $w = $image->getImageWidth();
            $h = $image->getImageHeight();
            if($w < $h) $image->thumbnailImage(256, (256/$w)*$h, 1, 1);
            else        $image->thumbnailImage((256/$h)*$w, 256, 1, 1);
            //crop around center
            $w = $image->getImageWidth();
            $h = $image->getImageHeight();
            $image->cropImage(256, 256, ($w-256)/2, ($h-256)/2);
            $image->writeImage($fsbigthumbpath);

            //aspect fill to 128x128
            $image = new Imagick(isset($pack->resize) ? $fsresizedpath : $fspath);
            $w = $image->getImageWidth();
            $h = $image->getImageHeight();
            if($w < $h) $image->thumbnailImage(128, (128/$w)*$h, 1, 1);
            else        $image->thumbnailImage((128/$h)*$w, 128, 1, 1);
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
            (isset($pack->autoplay)      ? "autoplay,"     : "").
            "created".
            ") VALUES (".
            "'".$filefolder."',".
            "'".($did_resize ? $newresizedfilename : $newfilename)."',".
            (isset($pack->game_id)       ? "'".addslashes($pack->game_id)."',"       : "").
            (isset($pack->auth->user_id) ? "'".addslashes($pack->auth->user_id)."'," : "").
            (isset($pack->name)          ? "'".addslashes($pack->name)."',"          : "").
            (isset($pack->autoplay)      ? "'".addslashes($pack->autoplay)."',"      : "").
            "CURRENT_TIMESTAMP".
            ")"
        );

        client::logPlayerUploadedMedia($pack);
        if ($filenameext === 'jpg' || $filenameext === 'png' || $filenameext === 'gif') {
            client::logPlayerUploadedMediaImage($pack);
        }
        if ($filenameext === 'mp4' || $filenameext === 'mov' || $filenameext === 'm4v' || $filenameext === '3gp') {
            client::logPlayerUploadedMediaVideo($pack);
        }
        if ($filenameext === 'caf' || $filenameext === 'mp3' || $filenameext === 'aac' || $filenameext === 'm4a') {
            client::logPlayerUploadedMediaAudio($pack);
        }
        games::bumpGameVersion($pack);
        return media::getMedia($pack);
    }

    //Takes in game JSON, all fields optional except user_id + key
    public static function updateMedia($pack)
    {
        $pack->auth->permission = "read_write";
        if(!users::authenticateUser($pack->auth)) return new return_package(6, NULL, "Failed Authentication");

        dbconnection::query(
            "UPDATE media SET ".
            (isset($pack->name    ) ?     "name = '".addslashes($pack->name    )."', " : "").
            (isset($pack->autoplay) ? "autoplay = '".addslashes($pack->autoplay)."', " : "").
            "last_active = CURRENT_TIMESTAMP ".
            "WHERE media_id = '{$pack->media_id}'"
        );

        games::bumpGameVersion($pack);
        return media::getMedia($pack);
    }

    public static function mediaObjectFromSQL($sql_media)
    {
        if(!$sql_media) return $sql_media;
        $media = new stdClass();
        $media->media_id     = $sql_media->media_id;
        $media->game_id      = $sql_media->game_id;
        $media->name         = $sql_media->name;
        $media->autoplay     = $sql_media->autoplay;
        $media->file_name    = $sql_media->file_name;

        $filenametitle = substr($sql_media->file_name,0,strrpos($sql_media->file_name,'.'));
        $filenameext   = substr($sql_media->file_name,strrpos($sql_media->file_name,'.'));

        if(substr($filenametitle, -8) === "_resized")
        {
            $filenametitle = substr($filenametitle, 0, -8);
        }

        $media->url           = Config::v2_gamedata_www_path."/".$sql_media->file_folder."/".$sql_media->file_name;
        $media->big_thumb_url = Config::v2_gamedata_www_path."/".$sql_media->file_folder."/".$filenametitle."_256".$filenameext;
        $media->thumb_url     = Config::v2_gamedata_www_path."/".$sql_media->file_folder."/".$filenametitle."_128".$filenameext;

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
        $sql_medias = dbconnection::queryArray("SELECT * FROM media WHERE (game_id = '{$pack->game_id}')");
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
        games::bumpGameVersion($pack);
        return new return_package(0);
    }
}
?>
