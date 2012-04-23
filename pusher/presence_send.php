<?php
require_once('pusher_config.php');
require_once('Pusher.php');

$pusher = new Pusher($key, $secret, $app_id, true);
$data = @($_POST['presence_data'] ? $_POST['presence_data'] : $_GET['presence_data']);
$pusher->trigger($presence_channel, $presence_event, $data);
echo $data;
?>
