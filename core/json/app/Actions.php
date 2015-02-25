<?php

function deserializationAction(&$body)
{
	$args = $body->getValue();
	$target = $args[0];
	
	$baseClassPath = $GLOBALS['amfphp']['classPath'];
	
	$lpos = strrpos($target, '.');
	
	$methodname = substr($target, $lpos + 1);
	$trunced = substr($target, 0, $lpos);
	$lpos = strrpos($trunced, ".");
	if ($lpos === false) {
		$classname = $trunced;
		$uriclasspath = $trunced . ".php";
		$classpath = $baseClassPath . $trunced . ".php";
	} else {
		$classname = substr($trunced, $lpos + 1);
		$classpath = $baseClassPath . str_replace(".", "/", $trunced) . ".php"; // removed to strip the basecp out of the equation here
		$uriclasspath = str_replace(".", "/", $trunced) . ".php"; // removed to strip the basecp out of the equation here
	} 
	
	$body->methodName = $methodname;
	$body->className = $classname;
	$body->classPath = $classpath;
	$body->uriClassPath = $uriclasspath;
	
	//Now deserialize the arguments
	array_shift($args);
	
	$actualArgs = array();
	
	foreach($args as $key => $value)
	{
		//Look at the value to see if it is JSON-encoded
		$urlvalue = urldecode($value);
		if($urlvalue != "")
		{
			if($urlvalue[0] != '[' && $urlvalue[0] != '{' && $urlvalue != "null" && $urlvalue != "false" && $urlvalue != "true")
			{
				
				//check to see if it is a number
				$char1 = ord($urlvalue[0]);
				if($char1 >= 0x30 && $char1 <= 0x39)
				{
					//Then this is a number
					//This line was always setting the $urlvalue to an empty string
					//$urlvalue = json_decode($urlvalue, true);
				} //Else leave urlvalue as is
                
                                // decode slashes. slashes are double encoded on client side so that they don't get confused as part of the overall URL
                                $urlvalue = str_replace("%2F", "/", $urlvalue);
                                $value = $urlvalue;
			}
			else
			{
                                //phil hack to preserve v1 behavior
                                if(strpos($_SERVER['REQUEST_URI'],"v1.") !== false)
                                    $value = json_decode($value, TRUE);
                                else
                                    $value = json_decode($value);
                                //end hack
			}
		}
		$actualArgs[] = $value;

	}
	$body->setValue($actualArgs);
}

function executionAction(& $body)
{
	$classConstruct = &$body->getClassConstruct();
	$methodName = $body->methodName;
	$args = $body->getValue();
	
	$output = Executive::doMethodCall($body, &$classConstruct, $methodName, $args);
	
	if($output !== "__amfphp_error")
	{
		$body->setResults($output);
	}
}


function serializationAction(& $body)
{
	//Take the raw response
	$rawResponse = & $body->getResults();
	
	adapterMap($rawResponse);
	
	//Now serialize it
	$encodedResponse = json_encode($rawResponse);
	
	if(count(NetDebug::getTraceStack()) > 0)
	{
		$trace = "/*" . implode("\n", NetDebug::getTraceStack()) . "*/";
		$encodedResponse = $trace . "\n" . $encodedResponse;
	}
	
	$body->setResults($encodedResponse);
}
?>
