<?php
require_once('../../config.class.php');

class util
{
    public static function serverErrorLog($message)
    {
        $errorLogFile = fopen(Config::serverErrorLog, "a");
        $errorData = date('c').":\nRequest:\n"."http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"."\n".$message."\n\n";
        fwrite($errorLogFile, $errorData);
        fclose($errorLogFile);
    }
    public static function serverErrorJot($message) //only log message, no metadata
    {
        $errorLogFile = fopen(Config::serverErrorLog, "a");
        fwrite($errorLogFile, $message."\n");
        fclose($errorLogFile);
    }


    public static function sendEmail($to, $subject, $body)
    {
        include_once('../../libraries/phpmailer/class.phpmailer.php');

        if(empty($to)) return false;

        $mail = new phpmailer;
        $mail->PluginDir = '../../libraries/phpmailer';

        $mail->CharSet = 'UTF-8';
        $mail->Subject = substr(stripslashes($subject), 0, 900);
        $mail->From = 'noreply@arisgames.org';
        $mail->FromName = 'ARIS Mailer';

        $mail->AddAddress($to, 'ARIS Author');
        $mail->MsgHTML($body);

        $mail->WordWrap = 79;

        if($mail->Send()) return true;
        else return false;
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
