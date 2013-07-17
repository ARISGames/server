<?php
require_once("module.php");
include ('../../libraries/wideimage/WideImage.php');

class Philtest extends Module
{
	public function doThing()
	{
            $gameDataFolder = "../../../gamedata/";
            //for($i = 966; $i < 967; $i++)
            //{
            $i = "player";
                if($gamedatadir = opendir($gameDataFolder.$i))
                {
                    while($imgName = readdir($gamedatadir))
                    {
                        if($imgName == "." || $imgName == ".." || substr($imgName, -4) == '.caf' || substr($imgName, -4) == '.mp4' || strlen($imgName) != strlen("aris74d70d3563e3e0c95c5d5ba2dfe65b9a.png"))
                            continue;

                        $imgThumbName = substr($imgName,0,strrpos($imgName,'.')).'_128'.substr($imgName,strrpos($imgName,'.'));
                        $img = WideImage::load($gameDataFolder.$i."/".$imgName);
                        $img = $img->resize(128, 128, 'outside');
                        $img = $img->crop('center','center',128,128);
                        $img->saveToFile($gameDataFolder.$i."/".$imgThumbName);
                    }
                }
            //}
        }
}
?>
