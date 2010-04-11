<?php

/*
 * This file assumes a $_FILES['file'] and a $_POST['gameID']
 * @returns the filename for the newly created file or an Error
*/ 

include ('media.php');

header('HTTP/1.1 200 OK');
header('Status: 200 OK');

$media = new Media();


//Check for Errors
if ($_FILES['file']['error']) die ("error");
		
$gameMediaDirectory = $media->getMediaDirectory($_POST['gameID'])->data;

$pathInfo = pathinfo($_POST['fileName']);
$newMediaFileName = 'aris' . md5( date("YmdGis") . $_FILES['file']['name']) . '.' . $pathInfo['extension'];
$newMediaFilePath = $gameMediaDirectory . '/' . $newMediaFileName;

if (!move_uploaded_file( $_FILES['file']['tmp_name'], $newMediaFilePath))
	die ("error");

//echo "data=$newMediaFileName&returnCode=0&returnCodeDescription=Success";
echo $newMediaFileName;

?>