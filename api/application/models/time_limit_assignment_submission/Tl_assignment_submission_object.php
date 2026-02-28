<?php

class Tl_assignment_submission_object extends CI_Model {

    public $id;
    public $academic_session;
    public $tl_assignment_id;
    public $assignment_available_from;
    public $assignment_available_till;
    public $scholar_id;
    public $assignment_submitted_to;
    public $submitted_assignment_url;
    public $part_no;
    public $submission_time;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->academic_session = CPreference::$academicSession;;
        $this->tl_assignment_id = 0;

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->assignment_available_from = $dateTime;


        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->assignment_available_till = $dateTime;

        $this->scholar_id = 0;
        $this->assignment_submitted_to = 0;
        $this->submitted_assignment_url = '';
        $this->part_no = 0;

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->submission_time = $dateTime;
    }

}

?>
