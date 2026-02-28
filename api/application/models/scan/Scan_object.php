<?php

class Scan_object extends CI_Model {

    public $id;
    public $doc_id;
    public $doc_status;
    public $repair_order_id;
    public $vin_no;
    public $v_production_date;
    public $v_model_year;
    public $v_model_description;
    public $v_model_name;
    public $insurance_company;
    public $repair_facility;
    public $document;
    public $data;
    public $status;
    public $error;
    public $create_date;

    
    

    public function __construct() {
        parent::__construct();

        $this->id = 0;
        $this->doc_id = '';
        $this->doc_status ='';
        $this->repair_order_id = '';
        $this->vin_no = '';
        $this->v_production_date = '';
        $this->v_model_year = '';
        $this->v_model_description = '';
        $this->v_model_name = '';
        $this->insurance_company = '';
        $this->repair_facility = '';
        $this->document = '';
        $this->data = '';
        $this->status = '';
        $this->error = '';
        $this->create_date = '';

    }

}

?>
