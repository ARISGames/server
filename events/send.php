<?php
require_once('../config.class.php');
require_once('Pusher.php');
require_once('pusher_defaults.php');

if(isset($_REQUEST['default'])) setDefaults($_REQUEST['default']);

$channel = isset($_REQUEST['channel']) ? $_REQUEST['channel'] : $default_channel;
$event = isset($_REQUEST['event']) ? $_REQUEST['event'] : $default_event;
$data = isset($_REQUEST['data']) ? $_REQUEST['data'] : $default_data;

$pusher = new Pusher(Config::pusher_key, Config::pusher_secret, Config::pusher_app_id, true);
$pusher->trigger($channel, $event, $data);
echo "Channel: '$channel', Event: '$event', Data: '$data'";
//echo "STOP SENDING EVENTS!!!! (You're sending like 100 per minute... we can't keep up with that!)";
?>
