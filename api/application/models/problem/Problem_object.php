<?php

class Problem_object extends CI_Model {

    public $id;
    public $topic;
    public $file_url;
    public $is_resolved;
    public $is_picked;
	public $board_data;
	public $uploaded_by;
    public $update_date;
    public $discussion_id;
    public $resolved_by;
    public $picked_by;


    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->topic = '';
        $this->file_url = '';
        $this->is_resolved = 0;
        $this->is_picked = 0;
		$this->board_data = '';
		date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $this->update_date = $dateTime;
        $this->uploaded_by = 0;
        $this->discussion_id = 0;
        $this->resolved_by = 0;
        $this->picked_by = 0;
    }

}

?>
