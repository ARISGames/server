<?php
require_once('pusher_config.php');
require_once('Pusher.php');

$pusher = new Pusher($key, $secret, $app_id, true);

$channel = ($_POST['channel_name'] ? $_POST['channel_name'] : $_GET['channel_name']);
if(!$channel) $channel = $private_channel;

$socket_id = ($_POST['socket_id'] ? $_POST['socket_id'] : $_GET['socket_id']);

echo $pusher->socket_auth($_POST['channel_name'], $_POST['socket_id']);
?>
