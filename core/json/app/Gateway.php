<?php
define("AMFPHP_BASE", realpath(dirname(dirname(dirname(__FILE__)))) . "/");
require_once(AMFPHP_BASE . "shared/app/BasicGateway.php");
require_once(AMFPHP_BASE . "shared/util/MessageBody.php");
require_once(AMFPHP_BASE . "shared/util/functions.php");
require_once(AMFPHP_BASE . "json/app/Actions.php");


class Gateway extends BasicGateway
{
	function createBody()
	{
		$GLOBALS['amfphp']['encoding'] = 'json';
		$body = & new MessageBody();
		
		$uri = __setUri();
		$elements = explode('/json.php', $uri);
		
		if(strlen($elements[1]) == 0)
		{
			echo("The JSON gateway is installed correctly. Call like this: json.php/MyClazz.myMethod/arg1/arg2");
			exit();
		}
		$args = substr($elements[1], 1);
		$rawArgs = explode('/', $args);

		// <_POST HACK>
		if(isset($_POST) && count($_POST) > 0)
		{
			$len = count($rawArgs);
			// Check for and remove [last] empty arg from URL '/' explosion
			if($len && trim($rawArgs[$len-1]) == "")
				unset($rawArgs[$len-1]);
			
			// Append the POST variables
			for($i=0;$i < count($_POST); $i++)
				$rawArgs[] = $_POST[$i];
		}
		// </_POST HACK>



		$GLOBALS['HTTP_RAW_POST_DATA'] = file_get_contents("php://input");
		if(isset($GLOBALS['HTTP_RAW_POST_DATA']))
		{
			$rawArgs[] = $GLOBALS['HTTP_RAW_POST_DATA'];
		}
		
		$body->setValue($rawArgs);
		return $body;
	}
	
	/**
	 * Create the chain of actions
	 */
	function registerActionChain()
	{
		$this->actions['deserialization'] = 'deserializationAction';
		$this->actions['classLoader'] = 'classLoaderAction';
		$this->actions['security'] = 'securityAction';
		$this->actions['exec'] = 'executionAction';
		$this->actions['serialization'] = 'serializationAction';
	}
}
?>
