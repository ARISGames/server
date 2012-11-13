<?php
require_once('../../config.class.php');
require_once('../Pusher.php');
require_once('../pusher_defaults.php');

$pusher = new Pusher(Config::pusher_key, Config::pusher_secret, Config::pusher_app_id, true);

$channel = isset($_REQUEST['channel_name']) ? $_REQUEST['channel_name'] : $presence_default_channel;
$socket_id = $_REQUEST['socket_id'];

$user_id = (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : time());
$_SESSION['user_id'] = $user_id;

$user_name = isset($_REQUEST['user_name']) ? $_REQUEST['user_name'] : 'valued_user';

echo $pusher->presence_auth($channel, $socket_id, $user_id, array('name' => $user_name));
?>
