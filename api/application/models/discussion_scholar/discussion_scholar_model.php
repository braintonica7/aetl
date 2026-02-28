<?php

class Discussion_scholar_model extends CI_Model {


	public function get_discussion_scholar_count($filter) {
        $count = 0;
        $sql = "select count(s.id) as cnt from scholar s 
			join student_discussion sd on s.id=sd.student_id
			where $filter";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

	public function get_paginated_discussion_scholar($offset, $limit, $filterString = NULL) {
        $records = array();
        $sql = "";
      
      $cols = " s.id, s.scholar_no, s.session, s.name, s.dob, s.gender, s.father, s.mother, s.alert_mobile_no, s.class_id, s.section_id, s.is_active ";
         if ($filterString == NULL)
            $sql = "select $cols, sd.discussion_id from scholar s join student_discussion sd on s.id=sd.student_id order by s.name limit $offset, $limit";
        else
            $sql = "select $cols, sd.discussion_id from scholar s join student_discussion sd on s.id=sd.student_id where $filterString order by s.name limit $offset, $limit";
      
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
       while ($row = $statement->fetch()) {
            $objScholar = new Discussion_Scholar_object();
            $objScholar->id = $row['id'];
            $objScholar->discussion_id = $row['discussion_id'];
            $objScholar->scholar_no = $row['scholar_no'];
            $objScholar->session = $row['session'];
            $objScholar->name = $row['name'];
            if ($row['dob'] != NULL)
                $objScholar->dob = DateTime::createFromFormat("Y-m-d", $row['dob'])->format('d-m-Y');
            else
                $objScholar->dob = NULL;
            $objScholar->gender = $row['gender'];
            $objScholar->father = $row['father'];
            $objScholar->mother = $row['mother'];
            $objScholar->alert_mobile_no = $row['alert_mobile_no'];
            $objScholar->class_id = $row['class_id'];
            $objScholar->section_id = $row['section_id'];
            $objScholar->is_active = $row['is_active'];
            $records[] = $objScholar;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }   
}
?>

