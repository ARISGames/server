<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

/*
 * This file assumes a $_FILES['file'] and a $_POST['path']
 * If called from the iPhone client, the file name is instead passed in $_REQUEST['fileName']
 * @returns the filename for the newly created file or an Error
 */ 
set_time_limit(0);

require_once('media.php');
require_once('../../libraries/wideimage/WideImage.php');

header('HTTP/1.1 200 OK');
header('Status: 200 OK');

$form = '
<html><body>
<form action="' . $_SERVER['PHP_SELF'] . '" 
enctype="multipart/form-data" 
method="post">
<p>gameID: <input type="text" name="gameID" size="30"></p>
<p>file: <input type="file" name="file" size="30"></p>
<p><input type="submit" value="Upload"></p>
<body></html>';

if (!$_FILES['file']) die ($form);

$media = new Media();

//Check for Errors
if ($_FILES['file']['error']) die ("file upload error");

if(isset($_POST['gameID']) ) $_POST['path'] = $_POST['gameID']; // for legacy use. - Phil 10/12/2012
$gameMediaDirectory = $media->getMediaDirectory($_POST['path'])->data;

$pathInfo = '';
if (@$_REQUEST['fileName']) $pathInfo = pathinfo($_REQUEST['fileName']);   //We are coming from the iPhone Client
else                        $pathInfo = pathinfo($_FILES['file']['name']); //We are coming from the form

$md5 = md5(date("YmdGisu").substr((string)microtime(),2,6).strtolower($_FILES['file']['name']));
$ext = ($pathInfo['extension'] != '') ? strtolower($pathInfo['extension']) : 'jpg';
$newMediaFileName     = 'aris'.$md5.'.'    .$ext;
if($ext == "jpg" || $ext == "png" || $ext == "gif")
$resizedMediaFileName = 'aris'.$md5.'_128.'.$ext;
else
$resizedMediaFileName = 'aris'.$md5.'_128.jpg';

if(
        //Images
        $ext != "jpg" &&
        $ext != "png" &&
        $ext != "gif" &&
        //Video
        $ext != "mp4" &&
        $ext != "mov" &&
        $ext != "m4v" &&
        $ext != "3gp" &&
        //Audio
        $ext != "caf" &&
        $ext != "mp3" &&
        $ext != "aac" &&
        $ext != "m4a" &&
        //Overlays
        $ext != "zip"
  )
die("Invalid filetype");

if(!move_uploaded_file( $_FILES['file']['tmp_name'], $gameMediaDirectory."/".$newMediaFileName))
die("error moving file");

if($ext == "jpg" || $ext == "png" || $ext == "gif")
{
    $img = WideImage::load($gameMediaDirectory."/".$newMediaFileName);
    $img = $img->resize(128, 128, 'outside');
    $img = $img->crop('center','center',128,128);
    $img->saveToFile($gameMediaDirectory."/".$resizedMediaFileName);
}
else if($ext == "mp4") //only works with mp4
{
    /*
       $ffmpeg = '../../libraries/ffmpeg';
       $videoFilePath      = $gameMediaDirectory."/".$newMediaFileName; 
       $tempImageFilePath  = $gameMediaDirectory."/temp_".$resizedMediaFileName; 
       $imageFilePath      = $gameMediaDirectory."/".$resizedMediaFileName; 
       $cmd = "$ffmpeg -i $videoFilePath 2>&1"; 
       $thumbTime = 1;
       if(preg_match('/Duration: ((\d+):(\d+):(\d+))/s', shell_exec($cmd), $videoLength))
       $thumbTime = (($videoLength[2] * 3600) + ($videoLength[3] * 60) + $videoLength[4])/2; 
       $cmd = "$ffmpeg -i $videoFilePath -deinterlace -an -ss $thumbTime -t 00:00:01 -r 1 -y -vcodec mjpeg -f mjpeg $tempImageFilePath 2>&1"; 
       shell_exec($cmd);

       $img = WideImage::load($tempImageFilePath);
       $img = $img->resize(128, 128, 'outside');
       $img = $img->crop('center','center',128,128);
       $img->saveToFile($imageFilePath);
     */
}

//echo "data=$newMediaFileName&returnCode=0&returnCodeDescription=Success";
echo $newMediaFileName;

$errorLogFile = fopen('/var/www/html/server/gamedata/aris_error_log.txt', "a");
fwrite($errorLogFile, "File Uploaded: ".$gameMediaDirectory."/".$newMediaFileName."");
fclose($errorLogFile);

?>
