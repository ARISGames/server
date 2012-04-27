<?php

//Sends an event using a channel, event and data
//http://dev.arisgames.org/server/pusher2/public_send.php?channel=new&event=wow&data=some%20data
    
require_once('pusher_config.php');
require_once('Pusher.php');

//if(isset($_REQUEST['usedefaults'])) setDefaults($_REQUEST['usedefaults']); //Sets default variables in pusher_config.php

if (isset($_REQUEST['channel'])) $channel = $_REQUEST['channel'];
else $channel = $default_channel;

if (isset($_REQUEST['event'])) $event = $_REQUEST['event'];
else $event = $default_event;

if (isset($_REQUEST['data'])) $data = $_REQUEST['data'];
else $data = $default_data;

$pusher = new Pusher($key, $secret, $app_id, true);
$pusher->trigger($channel, $event, $data);
echo ("Sent Channel: '$channel', Event: '$event', Data: '$data'");
    
?>
