<?php

/*
 * This file assumes a $_FILES['file'] and a $_POST['gameID']
 * @returns the filename for the newly created file or an Error
*/ 

include ('media.php');

header('HTTP/1.1 200 OK');
header('Status: 200 OK');

$media = new Media();
$prefix = $media->getPrefix($_POST['gameID']);

//Check for Errors
if ($_FILES['file']['error']) die ("data=&returnCode=4&returnCodeDescription=PHPUploadError");
if (!$prefix) die ("data=&returnCode=1&returnCodeDescription=InvalidGameID");
		
$gameMediaDirectory = $media->getMediaDirectory($prefix)->data;

$pathInfo = pathinfo($_FILES['file']['name']);
$newMediaFileName = 'aris' . md5( date("YmdGis") . $_FILES['file']['name']) . '.' . $pathInfo['extension'];
$newMediaFilePath = $gameMediaDirectory . '/' . $newMediaFileName;

if (!move_uploaded_file( $_FILES['file']['tmp_name'], $newMediaFilePath))
	die ("data=&returnCode=2&returnCodeDescription=CannotMoveFile");

//echo "data=$newMediaFileName&returnCode=0&returnCodeDescription=Success";
echo $newMediaFileName;

?>