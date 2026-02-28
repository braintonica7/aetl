<?php

class Genere_object extends CI_Model {

    public $id;
    public $class_group_id;
    public $class_name;
    public $numeric_equivalent;

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->class_group_id = 0;
        $this->class_name = '';
        $this->numeric_equivalent = 0;
    }

}

?>
