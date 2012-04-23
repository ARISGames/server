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
$private_send = 'presence_send.php';
$arduino_send = 'arduino_send.php';

$public_channel = 'public-pusher_room_channel';
$public_event = 'public-pusher_room_event';
$public_data = '';

$private_channel = 'private-pusher_room_channel';
$private_event = 'private-pusher_room_event';
$private_data = '';

$private_channel = 'presence-pusher_room_channel';
$private_event = 'presence-pusher_room_event';
$private_data = '';

$arduino_channel = 'arduino-pusher_room_channel';
$arduino_event = 'arduino-pusher_room_event';
$adruino_data = '';
?>
