<?php
class Scholar_object extends CI_Model
{
	public $id;
	public $scholar_no;
	public $session;
	public $name;
	public $dob;
	public $gender;
	public $father;
	public $mother;
	public $alert_mobile_no;
	public $class_id;
	public $section_id;
	public $exam;
	public $grade;
    public $is_active;


	public function __construct()
	{
		parent::__construct();

		$this->id = 0;
		$this->scholar_no = '';
		$this->session = '';
		$this->name = '';

		$this->dob = NULL; // Set to NULL instead of current date

		$this->gender = '';
		$this->father = '';
		$this->mother = '';
		$this->alert_mobile_no = '';
		$this->class_id = 0;
		$this->section_id = 0;
		$this->exam = '';
		$this->grade = '';
        $this->is_active = 1;
	}
}
?>
