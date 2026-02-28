<?php

class Wizi_quiz_object extends CI_Model {

    public $id;
    public $name;
    public $description;
    public $instructions;
    public $exam_id;
    public $subject_id;
    public $level;
    public $time_limit;
    public $passing_score;
    public $passing_percentage;
    public $total_marks;
    public $status;
    public $is_published;
    public $published_date;
    public $valid_from;
    public $valid_until;
    public $cover_image;
    public $quiz_order;
    public $created_by;
    public $created_at;
    public $updated_at;
	public $language;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->name = '';
        $this->description = '';
        $this->instructions = '';
        $this->exam_id = 0;
        $this->subject_id = 0;
        $this->level = 'Moderate';
        $this->time_limit = 180; // Default 3 hours in minutes
        $this->passing_score = 0;
        $this->passing_percentage = 0.00;
        $this->total_marks = 0;
        $this->status = 'draft';
        $this->is_published = 0;
        $this->published_date = null;
        $this->valid_from = null;
        $this->valid_until = null;
        $this->cover_image = null;
        $this->quiz_order = null;
        $this->created_by = 0;
		$this->language = 'english';
        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->created_at = $dateTime;
        $this->updated_at = $dateTime;
    }

}

?>
