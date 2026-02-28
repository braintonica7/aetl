<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CPreference
 *
 * @author Jawahar
 */
class CPreference {

    private static $preferencesFetched = FALSE;
    public static $academicSession;

    public function __construct() {        
        if (!self::$preferencesFetched) {            
            $this->fetch_preferences();
        }
    }

    private function fetch_preferences() {
        $pdo = CDatabase::getPdo();
        $sql = "select * from preference order by id";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {            
            if ($row['preference_name'] === 'Academic Session')
                self::$academicSession = $row['preference_value'];
        }
        $statement = NULL;
        $pdo = NULL;
        self::$preferencesFetched = TRUE;
    }

}
