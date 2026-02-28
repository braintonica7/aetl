<?php

class Discussion_object extends CI_Model {

    public $id;
    public $topic;
    public $class_id;
	public $subject_id;
	public $start_date;
    public $is_live;
    public $created_by;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->topic = '';
        $this->class_id = 0;
		$this->subject_id = 0;
		date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->start_date = $dateTime;
        $this->created_by = 0;
        $this->is_live = 0;
    }

}

?>
