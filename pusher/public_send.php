<?php
require_once('pusher_config.php');
require_once('Pusher.php');
$channel = @($_POST['public_channel'] ? $_POST['public_channel'] : $_GET['public_channel']);
if(!$channel) $channel = $public_channel;

$event = @($_POST['public_event'] ? $_POST['public_event'] : $_GET['public_event']);
if(!$event) $event = $public_event;

$data = @($_POST['public_data'] ? $_POST['public_data'] : $_GET['public_data']);
if(!$data) $data = $public_data;

$pusher = new Pusher($key, $secret, $app_id, true);
$pusher->trigger($channel, $event, $data);
echo $data;
?>
