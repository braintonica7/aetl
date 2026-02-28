<?php

class Question_object extends CI_Model {

    public $id;
    public $question_img_url;
    public $has_multiple_answer;
    public $duration;
    public $option_count;
    public $exam_id;
    public $subject_id;
    public $chapter_name;
    public $chapter_id;
    public $level;
    public $topic_id;
    public $correct_option;
    public $solution;
    public $question_text;
    public $ai_summary;
    public $summary_generated_at;
    public $summary_confidence;
    public $option_a;
    public $option_b;
    public $option_c;
    public $option_d;
    public $subject_name;
    public $topic_name;
    public $difficulty;
    public $invalid_question;
    public $year;
    public $question_type;
    public $flag_reason;
    public $language;
    
    

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->question_img_url = '';
        $this->has_multiple_answer = false;
        $this->duration = 0;
        $this->option_count = 0;
        $this->exam_id = 0;
        $this->subject_id = 0;
        $this->chapter_name = '';
        $this->chapter_id = 0;
        $this->level = '';
        $this->topic_id = 0;
        $this->correct_option = 0;
        $this->solution = '';
        $this->question_text = '';
        $this->ai_summary = '';
        $this->summary_generated_at = null;
        $this->summary_confidence = 0.0;
        $this->option_a = '';
        $this->option_b = '';
        $this->option_c = '';
        $this->option_d = '';
        $this->subject_name = '';
        $this->topic_name = '';
        $this->difficulty = '';
        $this->invalid_question = false;
        $this->year = 2025;
        $this->question_type = 'regular';
        $this->flag_reason = null;
        $this->language = 'en';
    }

}

?>
