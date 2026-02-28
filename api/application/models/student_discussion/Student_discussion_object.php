<?php

class Student_discussion_object extends CI_Model {

    public $id;
    public $student_id;
    public $discussion_id;
    public $joining_date;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->student_id = 0;
        $this->discussion_id = 0;
		date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->joining_date = $dateTime;        
    }

}

?>
