<?php
require_once('pusher_config.php');
require_once('Pusher.php');

$pusher = new Pusher($key, $secret, $app_id, true);

$channel = (isset($_POST['channel_name']) ? $_POST['channel_name'] : $_GET['channel_name']);
if(!$channel) $channel = $presence_channel;

$socket_id = (isset($_POST['socket_id']) ? $_POST['socket_id'] : $_GET['socket_id']);

$user_id = (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : time());
$_SESSION['user_id'] = $user_id;

//$user_name = (isset($_POST['user_name']) ? $_POST['user_name'] : $_GET['user_name']); 
//if(!$user_name) $user_name = "fred";
$user_name = "valued_voter";

echo $pusher->presence_auth($channel, $socket_id, $user_id, array('name' => $user_name));
?>
