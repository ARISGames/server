<?php
$send_url = 'send.php';

$public_default_auth = '';
$public_default_channel = 'public-default-channel';
$public_default_event = 'default-event';
$public_default_data = '';

$private_default_auth = 'auths/private_auth.php';
$private_default_channel = 'private-default-channel';
$private_default_event = 'default-event';
$private_default_data = '';

$presence_default_auth = 'auths/presence_auth.php';
$presence_default_channel = 'presence-default-channel';
$presence_default_event = 'default-event';
$presence_default_data = '';

$default_auth = '';
$default_channel = 'default-channel';
$default_event = 'default-event';
$default_data = '';

function setDefaults($room)
{
    if($room == 'public')
    {
        $default_auth = $public_default_auth;
        $default_channel = $public_default_channel;
        $default_event = $public_default_event;
        $default_data = $public_default_data;
    }
    else if($room == 'private')
    {
        $default_auth = $private_default_auth;
        $default_channel = $private_default_channel;
        $default_event = $private_default_event;
        $default_data = $private_default_data;
    }
    else if($room == 'presence')
    {
        $default_auth = $presence_default_auth;
        $default_channel = $presence_default_channel;
        $default_event = $presence_default_event;
        $default_data = $presence_default_data;
    }
}
?>
