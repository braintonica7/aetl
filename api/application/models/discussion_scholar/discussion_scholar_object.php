<?php
class Discussion_scholar_object extends CI_Model
{
	public $id;
	public $discussion_id;
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
    public $is_active;


	public function __construct()
	{
		parent::__construct();

		$this->id = 0;
		$this->discussion_id = 0;
		$this->scholar_no = '';
		$this->session = '';
		$this->name = '';

		date_default_timezone_set("Asia/Calcutta");
		$dateTime = new DateTime();
		$this->dob = $dateTime;

		$this->gender = '';
		$this->father = '';
		$this->mother = '';
		$this->alert_mobile_no = '';
		$this->class_id = 0;
		$this->section_id = 0;
                $this->is_active = 1;
	}
}
?>
