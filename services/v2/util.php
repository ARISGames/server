<?php
require_once('../../config.class.php');

//mailgun
require 'vendor/autoload.php';
use Mailgun\Mailgun;

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

      $mg = new Mailgun(Config::mailgun_key);
      $mg->sendMessage("arisgames.org",
        array('from'    => 'noreply@arisgames.org', 
              'to'      => $to, 
              'subject' => $subject, 
              'text'    => $body)
      );

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
