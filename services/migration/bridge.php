<?php

require_once('../../config.class.php');

function bridgeService($v, $class, $function, $url_args, $post_args)
{
    $c = curl_init(); 

    curl_setopt($c,CURLOPT_URL,Config::bridge_api_path."/json.php/".$v.".".$class.".".$function."/".$url_args);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    if($post_args)
    {
	$str = json_encode($post_args);
	curl_setopt($c,CURLOPT_POST,true);
	curl_setopt($c,CURLOPT_POSTFIELDS,$str);
	curl_setopt($c,CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: '.strlen($str))
	);
    }

    $response = curl_exec($c); 
    curl_close($c); 
    return json_decode($response);
}

?>

