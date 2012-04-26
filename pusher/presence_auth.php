<?php
require_once('pusher_config.php');
require_once('Pusher.php');

$pusher = new Pusher($key, $secret, $app_id, true);

if ($_REQUEST['channel']) $channel = $_REQUEST['channel'];
else $channel = $presence_default_channel;

$socket_id = $_REQUEST['socket_id'];

if ($_SESSION['user_id']) $user_id = $_SESSION['user_id']
else $user_id = time();

$user_name = "valued_voter";

echo $pusher->presence_auth($channel, $socket_id, $user_id, array('name' => $user_name));
?>
