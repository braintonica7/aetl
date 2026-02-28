<?php

class Content_read_status_object extends CI_Model {

    public $id;
    public $content_id;
    public $scholar_id;
    public $read_date;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->content_id = 0;
        $this->scholar_id = 0;

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->read_date = $dateTime;
    }

}

?>
