<?php

class Topic_object extends CI_Model {

    public $id;
    public $topic_name;
    public $chapter_id;
    public $subject_id;
    public $question_count; // For quiz builder endpoints

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->topic_name = '';
        $this->chapter_id = 0;
        $this->subject_id = 0;
        $this->question_count = 0;
    }

}

?>
