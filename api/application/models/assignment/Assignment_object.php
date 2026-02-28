<?php

class Assignment_object extends CI_Model {

    public $id;
    public $scholar_id;
    public $content_id;
    public $assignment_submitted_to;
    public $last_submission_date;
    public $assignment_url;
    public $actual_submission_date;
    public $reviewed;
    public $review_comments;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->scholar_id = 0;
        $this->content_id = 0;
        $this->assignment_submitted_to = 0;

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->last_submission_date = $dateTime;

        $this->assignment_url = '';

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->actual_submission_date = $dateTime;

        $this->reviewed = 0;
        $this->review_comments = '';
    }

}

?>
