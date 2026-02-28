<?php

class Tl_assignment_check_submission_object extends CI_Model {

    public $id;
    public $tl_assignment_check_id;
    public $url;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->tl_assignment_check_id = 0;
        $this->url = '';
    }

}

?>
