<?php

class Quiz_object extends CI_Model {

    public $id;
    public $name;
    public $description;
	public $subject_id;
    public $start_date;
    public $quiz_detail_image;
    public $is_live;
    public $marking;
    public $quiz_type;
    public $user_id;
    public $quiz_reference;
    public $exam_id;
    public $level;
    public $quiz_question_type;
    public $total_questions;
    public $correct_answers;
    public $incorrect_answers;
    public $total_score;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->name = '';
        $this->description = '';
		$this->subject_id = 0;
		date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->start_date = $dateTime;
        $this->quiz_detail_image = "";
        $this->is_live = 0;
        $this->marking = "Regular"; // Default marking
        $this->quiz_type = "private"; // Default to private
        $this->user_id = 0;
        $this->quiz_reference = "";
        $this->exam_id = 0;
        $this->quiz_question_type = 'regular';
        $this->total_questions = 0;
        $this->correct_answers = 0;
        $this->incorrect_answers = 0;
        $this->total_score = 0;
    }

}

?>
