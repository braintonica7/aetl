<?php

class Student_tracking_object extends CI_Model {

    public $id;
    public $student_id;
    public $content_id;
    public $view_date;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->student_id = 0;
        $this->content_id = 0;

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->view_date = $dateTime;
    }

}

?>
