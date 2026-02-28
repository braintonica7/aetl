<?php

class Scholar_model extends CI_Model {

    public function get_scholar($id) {
        $objScholar = NULL;
        $sql = "select * from scholar where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objScholar = new Scholar_object();
            $objScholar->id = $row['id'];
            $objScholar->scholar_no = $row['scholar_no'];
            $objScholar->session = $row['session'];
            $objScholar->name = $row['name'];
            //$objScholar->dob = DateTime::createFromFormat("Y-m-d", $row['dob'])->format('Y-m-d');
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
            $objScholar->exam = $row['exam'];
            $objScholar->grade = $row['grade'];
            $objScholar->is_active = $row['is_active'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objScholar;
    }

    public function get_all_scholars() {
        $records = array();

        $sql = "select * from scholar";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objScholar = new Scholar_object();
            $objScholar->id = $row['id'];
            $objScholar->scholar_no = $row['scholar_no'];
            $objScholar->session = $row['session'];
            $objScholar->name = $row['name'];
            $objScholar->dob = DateTime::createFromFormat("Y-m-d", $row['dob']);
            $objScholar->gender = $row['gender'];
            $objScholar->father = $row['father'];
            $objScholar->mother = $row['mother'];
            $objScholar->alert_mobile_no = $row['alert_mobile_no'];
            $objScholar->class_id = $row['class_id'];
            $objScholar->section_id = $row['section_id'];
            $objScholar->exam = $row['exam'];
            $objScholar->grade = $row['grade'];
            $objScholar->is_active = $row['is_active'];

            $records[] = $objScholar;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_scholar($objScholar) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from scholar";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objScholar->id = $row['mvalue'];
        else
            $objScholar->id = 0;
        $objScholar->id = $objScholar->id + 1;
        $sql = "INSERT INTO scholar (id, scholar_no, session, name, dob, gender, father, mother, alert_mobile_no, class_id, section_id, exam, grade, is_active, device_no, card_no, city, category) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);                        
        $inserted = $statement->execute(array(
            $objScholar->id,
            $objScholar->scholar_no,
            $objScholar->session,
            $objScholar->name,            
            ($objScholar->dob !== NULL) ? $objScholar->dob->format('Y-m-d') : NULL,
            $objScholar->gender,
            $objScholar->father,
            $objScholar->mother,
            $objScholar->alert_mobile_no,
            $objScholar->class_id,
            $objScholar->section_id,
            $objScholar->exam,
            $objScholar->grade,
            $objScholar->is_active,
            '0', // device_no default value
            NULL, // card_no default value
            NULL, // city default value
            NULL  // category default value
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objScholar;
        return FALSE;
    }

    public function update_scholar($objScholar) {
        $sql = "update scholar set scholar_no = ?, session = ?, name = ?, dob = ?, gender = ?, father = ?, mother = ?, alert_mobile_no = ?, class_id = ?, section_id = ?, exam = ?, grade = ?, is_active = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objScholar->scholar_no,
            $objScholar->session,
            $objScholar->name,
            ($objScholar->dob == NULL || $objScholar->dob == FALSE) ? NULL :  $objScholar->dob->format('Y-m-d'),
            $objScholar->gender,
            $objScholar->father,
            $objScholar->mother,
            $objScholar->alert_mobile_no,
            $objScholar->class_id,
            $objScholar->section_id,
            $objScholar->exam,
            $objScholar->grade,
            $objScholar->is_active,
            $objScholar->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
        {
            if ($objScholar->dob != NULL)
                $objScholar->dob = $objScholar->dob->format('Y-m-d');
            return $objScholar;
        }
        return FALSE;
    }

    public function delete_scholar($id) {
        $pdo = CDatabase::getPdo();
        
        $sql = "delete from user where reference_id = ? and role_id = 5";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $stattement = NULL;
        
        $sql = "delete from scholar where id = ?";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_scholar_count() {
        $count = 0;
        $sql = "select count(id) as cnt from scholar";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_scholar($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select * from scholar order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select * from scholar where $filterString order by $sortBy $sortType limit $offset, $limit";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objScholar = new Scholar_object();
            $objScholar->id = $row['id'];
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
            $objScholar->exam = $row['exam'];
            $objScholar->grade = $row['grade'];
            $objScholar->is_active = $row['is_active'];
            $records[] = $objScholar;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_as_user($scholar_no) {
        
    }

    
    public function get_scholars_who_submitted_assignment($assignmentId, $classId){
        $scholars = array();
        $pdo = CDatabase::getPdo();
        $sql = "select scholar.*, assignment.actual_submission_date from scholar left join assignment on scholar.id = assignment.scholar_id where scholar.id in (select distinct scholar_id from assignment where assignment.id = ?) and scholar.class_id = ? order by name";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($assignmentId, $classId));
        while ($row = $statement->fetch()){
            $objScholar = new Scholar_object();
            $objScholar->id = $row['id'];
            $objScholar->scholar_no = $row['scholar_no'];
            $objScholar->session = $row['session'];
            $objScholar->name = $row['name'];
            //$objScholar->dob = DateTime::createFromFormat("Y-m-d", $row['dob'])->format('d-m-Y');
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
            $objScholar->exam = $row['exam'];
            $objScholar->grade = $row['grade'];
            $objScholar->is_active = $row['is_active'];
            $objScholar->assignment_submission_date = DateTime::createFromFormat("Y-m-d", $row['actual_submission_date']);
            $scholars[] = $objScholar;
        }
        $statement = NULL;
        $pdo = NULL;
        return $scholars;
    }
    
    public function get_scholars_who_didnt_submitted_assignment($assignmentId, $classId){
        $scholars = array();
        $pdo = CDatabase::getPdo();
        $sql = "select scholar.* from scholar where scholar.id not in (select distinct scholar_id from assignment where assignment.id = ?) and scholar.class_id = ? order by name";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($assignmentId, $classId));
        while ($row = $statement->fetch()){
            $objScholar = new Scholar_object();
            $objScholar->id = $row['id'];
            $objScholar->scholar_no = $row['scholar_no'];
            $objScholar->session = $row['session'];
            $objScholar->name = $row['name'];
            //$objScholar->dob = DateTime::createFromFormat("Y-m-d", $row['dob'])->format('d-m-Y');
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
            $objScholar->exam = $row['exam'];
            $objScholar->grade = $row['grade'];
            $objScholar->is_active = $row['is_active'] == 1;
            //$objScholar->assignment_submission_date = DateTime::createFromFormat("Y-m-d", $row['actual_submission_date']);
            $scholars[] = $objScholar;
        }
        $statement = NULL;
        $pdo = NULL;
        return $scholars;
    }

    public function delete_scholar_new($id) {
        $sql = "delete from scholar where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $deleted = $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
        return $deleted;
    }
}
?>

