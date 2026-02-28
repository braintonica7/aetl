<?php

class Student_discussion_model extends CI_Model {

    public function add_student_discussion($objStudent_discussion) {
		$joining_date = date('Y-m-d h:i:s', strtotime($objStudent_discussion->joining_date));
        $pdo = CDatabase::getPdo();
      	$existingId=0;
		$sql = "select id from student_discussion where discussion_id=? and student_id=?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array(
			$objStudent_discussion->discussion_id,
            $objStudent_discussion->student_id
		));
        if ($row = $statement->fetch())
            $existingId = $row['id'];

		if($existingId == 0)
		{		
			$sql = "select max(id) as mvalue from student_discussion";
			$statement = $pdo->prepare($sql);
			$statement->execute();
			if ($row = $statement->fetch())
				$objStudent_discussion->id = $row['mvalue'];
			else
				$objStudent_discussion->id = 0;
			$objStudent_discussion->id = $objStudent_discussion->id + 1;
			$sql = "insert into student_discussion(id,discussion_id,student_id, joined_date ) values (?,?,?,?)";
			$statement = $pdo->prepare($sql);
			$inserted = $statement->execute(array(
				$objStudent_discussion->id,
				$objStudent_discussion->discussion_id,
				$objStudent_discussion->student_id,
				$joining_date,
			));
		}
		else{
			$inserted=true;
		}
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objStudent_discussion;
        return FALSE;
    }  
}
?>

