<?php

class CDatabase {
 
    static function getPdo() {
        $hostName = 'localhost';
        $portNo = 3306;

        // Server
        //$dbName = 'bteduworld_leaderboard';               
        //$userName = 'bteduworld_leaderboard';     
        //$password = 'VPNcVG48VPNcVG48';
 
        //Local
        $dbName = 'leaderboard';               
        $userName = 'root';     
        $password = 'root';

        $connectionString = "mysql:host=" . $hostName . ";port=" . $portNo . ";dbname=" . $dbName;
        $objPDO = new PDO($connectionString, $userName, $password, array(
            PDO::ATTR_TIMEOUT => 180, // in seconds
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ));
        return $objPDO;
    }

}

?> 