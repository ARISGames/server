<?php
	
	/**
	 * JSON gateway
	 */
	
	header("Access-Control-Allow-Origin: *");
	include("globals.php");
	include "core/json/app/Gateway.php";
	
	$gateway = new Gateway();
	
	$gateway->setBaseClassPath($servicesPath);
	
	$gateway->service();
?>
