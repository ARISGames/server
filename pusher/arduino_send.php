<?php
require_once('pusher_config.php');
require_once('Pusher.php');
$channel = @($_POST['arduino_channel'] ? $_POST['arduino_channel'] : $_GET['arduino_channel']);
if(!$channel) $channel = $arduino_channel;

$event = @($_POST['arduino_event'] ? $_POST['arduino_event'] : $_GET['arduino_event']);
if(!$event) $event = $arduino_event;

$data = @($_POST['arduino_data'] ? $_POST['arduino_data'] : $_GET['arduino_data']);
if(!$data) $data = $arduino_data;

$pusher = new Pusher($key, $secret, $app_id, true);
$pusher->trigger($channel, $event, $data);
echo $data;
?>
