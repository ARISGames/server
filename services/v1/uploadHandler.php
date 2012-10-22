<?php
/*
 * This file assumes a $_FILES['file'] and a $_POST['path']
 * If called from the iPhone client, the file name is instead passed in $_REQUEST['fileName']
 * @returns the filename for the newly created file or an Error
 */ 
set_time_limit(0);

include ('media.php');

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
if (@$_REQUEST['fileName']) {
	//We are coming from the iPhone Client
	$pathInfo = pathinfo($_REQUEST['fileName']);
}
else {
	//We are coming from the form
	$pathInfo = pathinfo($_FILES['file']['name']);
}

$newMediaFileName = 'aris' . md5( date("YmdGisu") . substr((string)microtime(),2,6) . strtolower($_FILES['file']['name'])) . '.' . strtolower($pathInfo['extension']);
$newMediaFilePath = $gameMediaDirectory ."/". $newMediaFileName;

if (!move_uploaded_file( $_FILES['file']['tmp_name'], $newMediaFilePath))
die ("error moving file");

//echo "data=$newMediaFileName&returnCode=0&returnCodeDescription=Success";
echo $newMediaFileName;

?>
