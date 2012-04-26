<?php

//Sends an event using a channel, event and data
//http://dev.arisgames.org/server/pusher2/private_send.php?channel=new&event=wow&data=some%20data
    
require_once('pusher_config.php');
require_once('Pusher.php');

$pusher = new Pusher($key, $secret, $app_id, true);

if ($_REQUEST['channel']) $channel = $_REQUEST['channel'];
else $channel = $private_default_channel;

if ($_REQUEST['event']) $event = $_REQUEST['event'];
else $event = $private_default_event;

if ($_REQUEST['data']) $data = $_REQUEST['data'];
else $data = $private_default_data;
    
$pusher->trigger($channel, $event, $data);

echo ("Sent Channel: '$channel', Event: '$event', Data: '$data'");

?>
