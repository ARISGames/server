<?php

class util
{	
/*
    public static function findLowestAvailableIdFromTable($tableName, $idColumnName)
    {
        $query = "
            SELECT  $idColumnName
            FROM    (
                    SELECT  1 AS $idColumnName
                    ) q1
            WHERE   NOT EXISTS
            (
             SELECT  1
             FROM    $tableName
             WHERE   $idColumnName = 1
            )
            UNION ALL
            SELECT  *
            FROM    (
                    SELECT  $idColumnName + 1
                    FROM    $tableName t
                    WHERE   NOT EXISTS
                    (
                     SELECT  1
                     FROM    $tableName ti
                     WHERE   ti.$idColumnName = t.$idColumnName + 1
                    )
                    ORDER BY
                    $idColumnName
                    LIMIT 1
                    ) q2
            ORDER BY
            $idColumnName
            LIMIT 1
            ";
        if($result = Utils::query($query))
        {
            if($lowestNonUsedId = mysql_fetch_object($result)->media_id)
                return $lowestNonUsedId;
        }
        else
        {
            //Just going to use the next auto_increment id...
            $query = "SELECT MAX($idColumnName) as 'nextAIID' FROM $tableName";
            $result = Utils::query($query);
            if($nextAutoIncrementId = mysql_fetch_object($result)->nextAIID)
                return $nextAutoIncrementId;
        }
        return null;
    }
*/

    public static function serverErrorLog($message)
    {
        $errorLogFile = fopen(Config::serverErrorLog, "a");
        $errorData = date('c').":\nRequest:\n"."http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"."\n".$message."\n\n";
        fwrite($errorLogFile, $errorData);
        fclose($errorLogFile);
    }

/*
    public static function sendEmail($to, $subject, $body)
    {
        include_once('../../libraries/phpmailer/class.phpmailer.php');

        if (empty($to)) return false;

        $mail = new phpmailer;
        $mail->PluginDir = '../../libraries/phpmailer';

        $mail->CharSet = 'UTF-8';
        $mail->Subject = substr(stripslashes($subject), 0, 900);
        $mail->From = 'noreply@arisgames.org';
        $mail->FromName = 'ARIS Mailer';

        $mail->AddAddress($to, 'ARIS Author');
        $mail->MsgHTML($body);

        $mail->WordWrap = 79;

        if ($mail->Send()) return true;
        else return false;
    }
*/

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

    public static function addSlashesArrayFriendly($input)
    {
        if (is_array($input))
            return array_map('addSlashes', $input);
        else
            return addSlashes($input);
    }
}

?>
