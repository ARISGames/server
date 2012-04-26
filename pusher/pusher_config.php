<?php
$account = "DAVID";

if($account == "DAVID")
{
	$key = '7fe26fe9f55d4b78ea02';
	$secret = 'b806d0198786431568ab';
	$app_id = '11683';
}
else if($account == "PHIL")
{
	$key = '79f6a265dbb7402a49c9';
	$secret = 'b540e483876b09874ce6';
	$app_id = '15816';
}

$public_send = 'public_send.php';
$private_auth = 'private_auth.php';
$private_send = 'private_send.php';
$presence_auth = 'presence_auth.php';
$presence_send = 'presence_send.php';
$arduino_send = 'arduino_send.php';

$public_default_channel = 'public-default-channel';
$public_default_event = 'default-event';
$public_default_data = '';

$private_default_channel = 'private-default-channel';
$private_default_event = 'default-event';
$private_default_data = '';

$presence_default_channel = 'presence-default-channel';
$presence_default_event = 'default-event';
$presence_default_data = '';

$arduino_default_channel = 'arduino-default-channel';
$arduino_default_event = 'default-event';
$adruino_default_data = '';
?>
