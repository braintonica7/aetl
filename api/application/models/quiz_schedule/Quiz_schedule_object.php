<?php

class Quiz_schedule_object extends CI_Model {

    public $quiz_id;
    public $quiz_name;
    public $class_id;
    public $class_name;
	public $subject_id;
	public $subject_name;
	public $chapter_id;
	public $chapter_name;
	public $topic_id;
	public $topic_name;
	public $topic_desc;
	public $topic_schedule;

    public function __construct() {
        parent::__construct();

        $this->quiz_id = 0;
        $this->quiz_name = '';
        $this->class_id = 0;
        $this->class_name = '';
		$this->subject_id = 0;
        $this->subject_name = '';
		$this->chapter_id = 0;
        $this->chapter_name = '';
		$this->topic_id = 0;
        $this->topic_name = '';
        $this->topic_desc = '';
		date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->topic_schedule = $dateTime;
       
    }

}

?>
