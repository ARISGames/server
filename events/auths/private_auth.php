<?php
require_once('../../config.class.php');
require_once('../Pusher.php');
require_once('../pusher_defaults.php');

$channel = isset($_REQUEST['channel']) ? $_REQUEST['channel'] : $private_default_channel;
$socket_id = $_REQUEST['socket_id'];

$pusher = new Pusher(Config::pusher_key, Config::pusher_secret, Config::pusher_app_id, true);
echo $pusher->socket_auth($channel, $socket_id);
    
?>
