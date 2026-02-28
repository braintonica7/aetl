<?php

class Class_group_object extends CI_Model {

    public $id;
    public $class_group;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->class_group = '';
    }

}

?>
