<?php

class Time_limit_assignment_object extends CI_Model {

    public $id;
    public $academic_session;
    public $title;
    public $assignment_url;
    public $class_id;
    public $subject_id;
    public $available_from;
    public $available_till;
    public $uploaded_by;
    public $is_active;
    public $is_approved;
    public $created;
    public $updated;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->academic_session = '';
        $this->title = '';
        $this->assignment_url = '';
        $this->class_id = 0;
        $this->subject_id = 0;

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->available_from = $dateTime;


        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->available_till = $dateTime;

        $this->uploaded_by = 0;
        $this->is_active = 0;
        $this->is_approved = 0;

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->created = $dateTime;


        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->updated = $dateTime;
    }

}

?>
