<?php
require_once('pusher_config.php');
require_once('Pusher.php');

$pusher = new Pusher($key, $secret, $app_id, true);
echo $pusher->socket_auth($_POST['channel_name'], $_POST['socket_id']);
?>
