<?php

class Tl_assignment_check_object extends CI_Model {

    public $id;
    public $academic_session;
    public $tl_assignment_id;
    public $scholar_id;
    public $checked_by_employee_id;
    public $max_marks;
    public $marks_obtained;
    public $review_comments;
    public $attachments;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->academic_session = '';
        $this->tl_assignment_id = 0;
        $this->scholar_id = 0;
        $this->checked_by_employee_id = 0;
        $this->max_marks = 0;
        $this->marks_obtained = 0;
        $this->review_comments = '';
        $this->attachments = array();
    }

}

?>
