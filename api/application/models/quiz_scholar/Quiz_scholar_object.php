<?php

class Quiz_scholar_object extends CI_Model {

    public $id;
    public $quiz_id;
    public $scholar_id;
    public $scholar_order;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->quiz_id = 0;
        $this->scholar_id = 0;
        $this->scholar_order = 0;
        
    }

}

?>
