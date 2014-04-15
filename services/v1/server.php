<?php
require_once("module.php");

class Server extends Module
{
    public static function migrateDB()
    {
        $migrations = array();
        if($migrationsDir = opendir(Config::migrationsDir))
        {
            while($migration = readdir($migrationsDir))
            {
                if(preg_match('/^[0..9]+\.sql$/',$migration))
                {
                    $migrations[intval(substr($migration, 0, -4))] = $migration;
                }
            }
        }
        
        $migrated = array();
        if(Module::queryObject("SHOW TABLES LIKE 'aris_migrations'"))
        {
            $knownVersions = Module::queryArray("SELECT * FROM aris_migrations");
            foreach($knownVersions as $version)
            {
                if(!$migrated[intval($version->version_major)]) $migrated[intval($version->version_major)] = array();
                $migrated[intval($version->version_major)][intval($version->version_minor)] = $version->timestamp;
            }
        }
        else
        {
            //The one migration/construction to be done outside the .sql files
            Module::query("CREATE TABLE aris_migrations ( version_major int(32) unsigned NOT NULL, version_minor int(32) unsigned NOT NULL, timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (version_major,version_minor))");
        }

        foreach($migrations as $major => $file)
        {
            if($migrated[$major+1]) continue;

            $file_handle = fopen(Config::migrationsDir."/".$file, "r");
            $minor = 0;
            while(!feof($file_handle)) 
            {
                //Funny way to continue to read from the file until it either ends, or reaches a semicolon
                $query = "";
                while(!feof($file_handle) && strpos($query .= fgets($file_handle),';') === FALSE) {}

                if(!$migrated[$major][$minor])
                {
                    mysql_query($query);
                    if(mysql_error())
                    {
                        $error = "Error upgrading database to version ".$major.".".$minor.". Error was:\n".mysql_error()."\n in query:\n".$query;
                        Module::serverErrorLog($error);
                        echo $error;
                        return $error;
                    }
                    Module::query("INSERT INTO aris_migrations (version_major, version_minor) VALUES ('".$major."','".$minor."')");
                }
                $minor++;
            }
            fclose($file_handle);
        }
        return 0;
    }
}
?>

