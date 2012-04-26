<?php
    
//Accepts a request for a private authentication token
//http://dev.arisgames.org/server/pusher2/private_auth.php?channel=mychannel&socket_id=someid
    
require_once('pusher_config.php');
require_once('Pusher.php');

$pusher = new Pusher($key, $secret, $app_id, true);

if ($_REQUEST['channel']) $channel = $_REQUEST['channel'];
else $channel = $private_default_channel;

$socket_id = $_REQUEST['socket_id'];

echo $pusher->socket_auth($channel, $socket_id);
    
?>
