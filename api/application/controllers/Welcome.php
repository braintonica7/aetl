<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends API_Controller {

    public function __constructor()
    {
        parent::__construct();
    }

    public function ping_get() {
        echo gmdate("Y-m-d\TH:i:s\Z");
     }

    public function index_get()
    {
        
    }
    function index_post()
    {
        $request = $this->get_request();
        echo 'The Posted Data is : <br>';
        print_r($request);
    }

}
