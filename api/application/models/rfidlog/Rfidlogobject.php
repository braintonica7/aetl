<?php

class RFIDLogObject extends CI_Model {

    public $logid;
    public $series;
    public $logdate;
    public $logtime;
    public $machineid;
    public $cardno;
    public $apikey;

    public function __construct() {
        parent::__construct();

        $this->logid = 0;
        $this->series = 'A';

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        
        $this->logdate = $dateTime;
        $this->logtime = $dateTime;

        $this->machineid = '';
        $this->cardno = '';
        $this->apikey = '';
    }

}

?>
