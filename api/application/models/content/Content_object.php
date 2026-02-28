<?php

class Content_object extends CI_Model {

    public $id;
    public $academic_session;
    public $content_type_id;
    public $content_url;
    public $account_id;
    public $class_id;    
    public $subject_id;
    public $topic;
    public $topic_note;
    public $submission_date;
    public $is_active;
    public $is_approved;
    public $uploaded_by;
    public $approved_by;
    public $approval_date;
    public $sensored_by;
    public $sensor_date;
    public $created;
    public $updated;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->academic_session = '';
        $this->content_type_id = 0;
        $this->content_url = '';
        $this->account_id = 0;
        $this->class_id = 0;
        $this->subject_id = 0;
        $this->topic = '';
        $this->topic_note = '';

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->submission_date = $dateTime;

        $this->is_active = 0;
        $this->is_approved = 0;
        $this->uploaded_by = 0;
        $this->approved_by = 0;

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->approval_date = $dateTime;

        $this->sensored_by = 0;

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->sensor_date = $dateTime;


        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->created = $dateTime;


        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->updated = $dateTime;
    }

}

?>
