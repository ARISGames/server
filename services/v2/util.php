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
        array('from'    => 'noreply@fielddaylab.org',
              'to'      => $to,
              'subject' => $subject,
              'text'    => $body,
              'html'    => $body)
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

    public static function rcopy($src,$dst)
    {
      //hack to "fix" security issue of this becoming open API via our terrible framework
      if(strpos($_SERVER['REQUEST_URI'],'rcopy') !== false) return new return_package(6, NULL, "Attempt to bypass authentication externally.");

      $dir = opendir($src);
      @mkdir($dst);
      while(false !== ($file = readdir($dir)))
      {
        if(($file != '.') && ($file != '..'))
        {
          if(is_dir($src.'/'.$file))
            util::rcopy($src.'/'.$file,$dst.'/'.$file);
          else
            copy($src.'/'.$file,$dst.'/'.$file);
        }
      }
      closedir($dir);
    }

    public static function rdel($dirPath)
    {
      //hack to "fix" security issue of this becoming open API via our terrible framework
      if(strpos($_SERVER['REQUEST_URI'],'rdel') !== false) return new return_package(6, NULL, "Attempt to bypass authentication externally.");

      if(!is_dir($dirPath))
        throw new InvalidArgumentException("$dirPath must be a directory");
      if(substr($dirPath, strlen($dirPath) - 1, 1) != '/')
        $dirPath .= '/';

      $files = glob($dirPath . '*', GLOB_MARK);
      foreach($files as $file)
      {
        if(is_dir($file))
          util::rdel($file);
        else
          unlink($file);
      }
      rmdir($dirPath);
    }


    public static function rzip($srcfolder, $destzip)
    {
      //hack to "fix" security issue of this becoming open API via our terrible framework
      if(strpos($_SERVER['REQUEST_URI'],'rzip') !== false) return new return_package(6, NULL, "Attempt to bypass authentication externally.");

      $rootPath = realpath($srcfolder);

      $zip = new ZipArchive();
      $zip->open($destzip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

      $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY);

      foreach($files as $name => $file)
      {
        if(!$file->isDir())
        {
          $filePath = $file->getRealPath();
          $relativePath = substr($filePath, strlen($rootPath) + 1);

          $zip->addFile($filePath, $relativePath);
        }
      }

      $zip->close();
    }
}

?>
