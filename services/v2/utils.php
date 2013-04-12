<?php
require_once('../../config.class.php');

abstract class Utils
{
    public function connect()
    {
        $this->conn = @mysql_connect(Config::dbHost, Config::dbUser, Config::dbPass);
        if (!$this->conn)
        {
            Utils::serverErrorLog("Problem Connecting to MySQL: " . mysql_error());
            if(Config::adminEmail) Utils::sendEmail(Config::adminEmail,"ARIS Server Error", mysql_error());
        }
        mysql_select_db(Config::dbSchema);
        Utils::query("set names utf8");
        Utils::query("set charset utf8");
    }

    public function query($query)
    {
        $r = mysql_query($query);
        if(mysql_error()) 
        {
            Utils::serverErrorLog("Error!"."\nQuery:\n".$query."\nError: ".mysql_error());
            return false;
        }
        return $r;
    }

    public function queryObject($query)
    {
        if($r = Utils::query($query))
            return mysql_fetch_object($r);
        return new stdClass();
    }

    public function queryArray($query)
    {
        if($r = Utils::query($query))
        {
            $a = array();
            while($o = mysql_fetch_object($r)) 
                $a[] = $o;
            return $a;
        }
        return array();
    }

    public function findLowestAvailableIdFromTable($tableName, $idColumnName)
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

    protected function serverErrorLog($message)
    {
        $errorLogFile = fopen(Config::serverErrorLog, "a");
        $errorData = date('c').":\nRequest:\n"."http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"."\n".$message."\n\n";
        fwrite($errorLogFile, $errorData);
        fclose($errorLogFile);
    }

    protected function sendEmail($to, $subject, $body)
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

    protected function mToDeg($meters) // lat/lon ^ -> meters
    {
        return $meters/80000; //Ridiculous approximation, but fine for most cases
    }

    protected function degToM($degrees) // meters -> lat/lon ^
    {
        return 80000*$degrees; //Ridiculous approximation, but fine for most cases
    }

    /* stolen from http://www.lateralcode.com/creating-a-random-string-with-php/ */
    public function rand_string($length) 
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";  
        $size = strlen($chars);
        for($i = 0; $i < $length; $i++)
            $str .= $chars[rand(0, $size-1)];
        return $str;
    }
}
?>
