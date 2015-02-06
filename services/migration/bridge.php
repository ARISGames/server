<?php

function bridgeService($v, $class, $function, $url_args, $post_args)
{
    $c = curl_init(); 

    curl_setopt($c,CURLOPT_URL,"localhost/server/json.php/".$v.".".$class.".".$function."/".$url_args);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    if($post_args)
    {
        curl_setopt($c,CURLOPT_POST,true); 
        curl_setopt($c,CURLOPT_POSTFIELDS,json_encode($post_args)); 
    }

    $response = curl_exec($c); 
    curl_close($c); 
    return json_decode($response);
}

?>
