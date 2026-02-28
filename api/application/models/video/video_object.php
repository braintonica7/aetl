<?php
class Video_object extends CI_Model
{
	public $id;
	public $name;
	public $chapter_bgImg;
	public $subject_id;
	public $subject_name;
	public $topic_id;
	public $topic_name;
	public $topic_desc;
	public $topic_url;
	public $topic_bgImg;
	public $topic_duration;
	
	public function __construct()
	{
		parent::__construct();

		$this->id = 0;
		$this->name = '';
		$this->chapter_bgImg = '';
		$this->subject_id = 0;
		$this->subject_name = '';
		$this->topic_id = 0;
		$this->topic_name = '';
		$this->topic_desc = '';
		$this->topic_url = '';
		$this->topic_bgImg = '';
		$this->topic_duration = '';		
	}
}
?>
