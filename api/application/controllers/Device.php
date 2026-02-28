<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Device extends CI_Controller
{

    public function __constructor()
    {
        parent::__construct();
    }

    
    function index()
    {
        $this->load->model('rfidlog/rfidlogmodel');
        $log = $this->rfidlogmodel->get_latest_rfidlog();
        $this->load->view('device', $log);
    }


}
