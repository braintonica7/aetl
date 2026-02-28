<?php
class Meeting_log_object extends CI_Model
{
	public $id;
	public $scholar_id;
	public $meeting_id;
	public $joined;
	public $left;


	public function __construct()
	{
		parent::__construct();

		$this->id = 0;
		$this->scholar_id = 0;
		$this->meeting_id = 0;

		date_default_timezone_set("Asia/Calcutta");
		$dateTime = new DateTime();
		$this->joined = $dateTime;


		date_default_timezone_set("Asia/Calcutta");
		$dateTime = new DateTime();
		$this->left = $dateTime;

	}
}
?>
