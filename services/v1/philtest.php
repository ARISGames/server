<?php
/*
require_once('/var/www/html/server/libraries/wideimage/WideImage.php');

$gameDataFolder = "../../gamedata/";
$folder = "player";

function compressFile($path, $name)
{
    $thumbName = substr($name,0,strrpos($name,'.')).'_128'.substr($name,strrpos($name,'.'));
    $img = WideImage::load($path.$name);
    $img = $img->resize(128, 128, 'outside');
    $img = $img->crop('center','center',128,128);
    $img->saveToFile($path.$thumbName);
}

//for($folder = 966; $folder < 967; $folder++)
//{
    $cfiles = array();
    $ufiles = array();
    $path = $gameDataFolder.$folder."/";
    if($gamedatadir = opendir($gameDataFolder.$folder))
    {
        while($imgName = readdir($gamedatadir))
        {
            if(strtolower(substr($imgName, -4)) == '.jpg'  || 
                    strtolower(substr($imgName, -4)) == '.png'  ||
                    strtolower(substr($imgName, -4)) == '.gif')
            {
                if(substr($imgName, -8, 4) == '_128')
                    $cfiles[substr($imgName, 0, 10)] = $imgName;
                else
                    $ufiles[] = $imgName;
            }
        }

        $numUFiles = count($ufiles);
        for($i = 0; $i < $numUFiles; $i++)
        {
            if($cfiles[substr($ufiles[$i], 0, 10)]) continue; //already compressed
            else compressFile($path,$ufiles[$i]);
        }
    }
//}
*/
?>
