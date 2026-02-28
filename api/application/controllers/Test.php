<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Test
 *
 * @author Jawahar
 */
class Test extends CI_Controller{
    
    public function index(){
        echo "Academic session is " . PHP_EOL;
        echo CPreference::$academicSession;
    }
}
