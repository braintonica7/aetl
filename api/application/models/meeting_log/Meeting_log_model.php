<?php
class Meeting_log_model extends CI_Model
{
	public function get_meeting_log($id)
	{
		$objMeeting_log = NULL;
		$sql = "select * from meeting_log where id = ?";
		$pdo = CDatabase::getPdo();
		$statement = $pdo->prepare($sql);
		$statement->execute(array($id));
		if ($row = $statement->fetch())
		{
			$objMeeting_log = new Meeting_log_object();
			$objMeeting_log->id = $row['id'];
			$objMeeting_log->scholar_id = $row['scholar_id'];
			$objMeeting_log->meeting_id = $row['meeting_id'];
			if ($row['joined'] == NULL)
				$objMeeting_log->joined = NULL;
			else
				$objMeeting_log->joined = DateTime::createFromFormat("Y-m-d H:i:s", $row['joined'])->format('Y-m-d H:i:s');
			if ($row['left'] == NULL)
				$objMeeting_log->left = NULL;
			else
				$objMeeting_log->left = DateTime::createFromFormat("Y-m-d H:i:s", $row['left'])->format('Y-m-d H:i:s');
		}
		$statement = NULL;
		$pdo = NULL;
		return $objMeeting_log;
	}

	public function get_all_meeting_logs()
	{
		$records = array();

		$sql = "select * from meeting_log";
		$pdo = CDatabase::getPdo();
		$statement = $pdo->prepare($sql);
		$statement->execute();
		while ($row = $statement->fetch())
		{
			$objMeeting_log = new Meeting_log_object();
			$objMeeting_log->id = $row['id'];
			$objMeeting_log->scholar_id = $row['scholar_id'];
			$objMeeting_log->meeting_id = $row['meeting_id'];
			if ($row['joined'] == NULL)
				$objMeeting_log->joined = NULL;
			else
				$objMeeting_log->joined = DateTime::createFromFormat("Y-m-d H:i:s", $row['joined'])->format('Y-m-d H:i:s');
			if ($row['left'] == NULL)
				$objMeeting_log->left = NULL;
			else
				$objMeeting_log->left = DateTime::createFromFormat("Y-m-d H:i:s", $row['left'])->format('Y-m-d H:i:s');

			$records[] = $objMeeting_log;
		}
		$statement = NULL;
		$pdo = NULL;
		return $records;
	}

	public function add_meeting_log($objMeeting_log)
	{
		$pdo = CDatabase::getPdo();

		$sql = "select max(id) as mvalue from meeting_log";
		$statement = $pdo->prepare($sql);
		$statement->execute();
		if ($row = $statement->fetch())
			$objMeeting_log->id = $row['mvalue'];
		else
			$objMeeting_log->id = 0;
		$objMeeting_log->id = $objMeeting_log->id + 1;
		$sql = "insert into meeting_log values (?,?,?,?,?)";
		$statement = $pdo->prepare($sql);
		$inserted = $statement->execute(array(
				$objMeeting_log->id, 
				$objMeeting_log->scholar_id, 
				$objMeeting_log->meeting_id, 
				$objMeeting_log->joined == NULL ? NULL : $objMeeting_log->joined->format('Y-m-d H:i:s'), 
				$objMeeting_log->left == NULL ? NULL : $objMeeting_log->left->format('Y-m-d H:i:s')
			));
		$statement = NULL;
		$pdo = NULL;
		if ($inserted){
			if ($objMeeting_log->joined!= NULL)
				$objMeeting_log->joined = $objMeeting_log->joined->format('d-m-Y H:i:s');
			if ($objMeeting_log->left!= NULL)
				$objMeeting_log->left = $objMeeting_log->left->format('d-m-Y H:i:s');
			return $objMeeting_log;
		}
		return FALSE;
	}

	public function update_meeting_log($objMeeting_log)
	{
		$sql = "update meeting_log set scholar_id = ?, meeting_id = ?, joined = ?, left = ? where id = ?";
		$pdo = CDatabase::getPdo();
		$statement = $pdo->prepare($sql);
		$updated = $statement->execute(array(
				$objMeeting_log->scholar_id, 
				$objMeeting_log->meeting_id, 
				$objMeeting_log->joined == NULL ? NULL : $objMeeting_log->joined->format('Y-m-d H:i:s'), 
				$objMeeting_log->left == NULL ? NULL : $objMeeting_log->left->format('Y-m-d H:i:s'), 
				$objMeeting_log->id
			));
		$statement = NULL;
		$pdo = NULL;
		if ($updated){
			if ($objMeeting_log->joined!= NULL)
				$objMeeting_log->joined = $objMeeting_log->joined->format('d-m-Y H:i:s');
			if ($objMeeting_log->left!= NULL)
				$objMeeting_log->left = $objMeeting_log->left->format('d-m-Y H:i:s');
			return $objMeeting_log;
		}
		return FALSE;
	}

	public function delete_meeting_log($id)
	{
		$sql = "delete from meeting_log where id = ?";
		$pdo = CDatabase::getPdo();
		$statement = $pdo->prepare($sql);
		$statement->execute(array($id));
		$statement = NULL;
		$pdo = NULL;
	}

	public function get_meeting_log_count()
	{
		$count = 0;
		$sql = "select count(id) as cnt from meeting_log";
		$pdo = CDatabase::getPdo();
		$statement = $pdo->prepare($sql);
		$statement->execute();
		if ($row = $statement->fetch())
			$count = $row['cnt'];
		$statement = NULL;
		$pdo = NULL;
		return $count;
	}

	public function get_paginated_meeting_log($offset, $limit, $sortBy, $sortType, $filterString = NULL, &$filterRecordCount = -1)
	{
		$pdo = CDatabase::getPdo();
		$records = array();
		$sql = "";
		if ($filterString == NULL)
			$sql = "select* from meeting_log order by $sortBy $sortType limit $offset, $limit";
		else{
			$sql = "select count(id) as rec_count from meeting_log where $filterString";
			$countStatement = $pdo->prepare($sql);
			$countStatement->execute();
			if ($row = $countStatement->fetch())
				$filterRecordCount = $row['rec_count'];
			$countStatement = NULL;
			$sql = "select* from meeting_log where $filterString order by $sortBy $sortType limit $offset, $limit";

		}
		$statement = $pdo->prepare($sql);
		$statement->execute();
		while ($row = $statement->fetch())
		{
			$objMeeting_log = new Meeting_log_object();
			$objMeeting_log->id = $row['id'];
			$objMeeting_log->scholar_id = $row['scholar_id'];
			$objMeeting_log->meeting_id = $row['meeting_id'];
			if ($row['joined'] == NULL)
				$objMeeting_log->joined = NULL;
			else
				$objMeeting_log->joined = DateTime::createFromFormat("Y-m-d H:i:s", $row['joined'])->format('d-m-Y H:i:s');
			if ($row['left'] == NULL)
				$objMeeting_log->left = NULL;
			else
				$objMeeting_log->left = DateTime::createFromFormat("Y-m-d H:i:s", $row['left'])->format('d-m-Y H:i:s');
			$records[] = $objMeeting_log;
		}
		$statement = NULL;
		$pdo = NULL;
		return $records;
	}

}
?>

