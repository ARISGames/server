<?php
require_once('pusher_config.php');
require_once('Pusher.php');

$pusher = new Pusher($key, $secret, $app_id, true);
$data = @($_POST['private_data'] ? $_POST['private_data'] : $_GET['private_data']);
$pusher->trigger($private_channel, $private_event, $data);
echo $data;
?>
