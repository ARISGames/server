<?php
require_once('../../config.class.php');

class util
{
    public static function errorLog($message)
    {
        $errorLogFile = fopen(Config::errorLog, "a");
        $errorData = date('c').":\nRequest:\n"."http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"."\n".$message."\n\n";
        fwrite($errorLogFile, $errorData);
        fclose($errorLogFile);
    }
    public static function errorJot($message) //only log message, no metadata
    {
        $errorLogFile = fopen(Config::errorLog, "a");
        fwrite($errorLogFile, $message."\n");
        fclose($errorLogFile);
    }

    public static function sendEmail($to, $subject, $body)
    {
      if(empty($to)) return false;

      $c = curl_init(); 
      curl_setopt($c, CURLOPT_USERPWD, 'api:'.Config::mailgun_key); 
      curl_setopt($c, CURLOPT_URL, "https://api.mailgun.net/v3/arisgames.org/messages"); 
      curl_setopt($c, CURLOPT_RETURNTRANSFER, 1); 

      curl_setopt($c, CURLOPT_POST, 1);
      curl_setopt($c, CURLOPT_POSTFIELDS,
        array('from'    => 'noreply@arisgames.org', 
              'to'      => $to, 
              'subject' => $subject, 
              'text'    => $body)
      );

      $output = curl_exec($c); 
      curl_close($c);      

      return true;
    }

    public static function mToDeg($meters) // lat/lon ^ -> meters
    {
        return $meters/80000; //Ridiculous approximation, but fine for most cases
    }

    public static function degToM($degrees) // meters -> lat/lon ^
    {
        return 80000*$degrees; //Ridiculous approximation, but fine for most cases
    }

    /* stolen from http://www.lateralcode.com/creating-a-random-string-with-php/ */
    public static function rand_string($length)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $size = strlen($chars);
        for($i = 0; $i < $length; $i++)
            $str .= $chars[rand(0, $size-1)];
        return $str;
    }

    public static function fixBadQuotes($inputString)
    {
        $output = str_replace("“", "\"", $inputString);
        $output = str_replace("”", "\"", $output);
        return $output;
    }
}

?>
