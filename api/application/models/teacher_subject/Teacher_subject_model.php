<?php

class Teacher_subject_model extends CI_Model {

    public function get_teacher_subject($id) {
        $sql = "select * from teacher_subject where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objTeacher_subject = new Teacher_subject_object();
            $objTeacher_subject->id = $row['id'];
            $objTeacher_subject->employee_id = $row['employee_id'];
            $objTeacher_subject->subject_id = $row['subject_id'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objTeacher_subject;
    }

    public function get_all_teacher_subjects($employee_id) {
        $records = array();

        $sql = "select * from teacher_subject where employee_id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($employee_id));
        while ($row = $statement->fetch()) {
            $objTeacher_subject = new Teacher_subject_object();
            $objTeacher_subject->id = $row['id'];
            $objTeacher_subject->employee_id = $row['employee_id'];
            $objTeacher_subject->subject_id = $row['subject_id'];

            $records[] = $objTeacher_subject;
        }
        $statement = NULL;
        $pdo = NULL; 
        return $records;
    }
    
    

    public function add_teacher_subject($objTeacher_subject) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from teacher_subject";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objTeacher_subject->id = $row['mvalue'];
        else
            $objTeacher_subject->id = 0;
        $objTeacher_subject->id = $objTeacher_subject->id + 1;
        $sql = "insert into teacher_subject values (?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objTeacher_subject->id,
            $objTeacher_subject->employee_id,
            $objTeacher_subject->subject_id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objTeacher_subject;
        return FALSE;
    }

    public function update_teacher_subject($objTeacher_subject) {
        $sql = "update teacher_subject set employee_id = ?, subject_id = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objTeacher_subject->employee_id,
            $objTeacher_subject->subject_id,
            $objTeacher_subject->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objTeacher_subject;
        return FALSE;
    }

    public function delete_teacher_subject($id) {
        $sql = "delete from teacher_subject where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_teacher_subject_count() {
        $count = 0;
        $sql = "select count(id) as cnt from teacher_subject";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_teacher_subject($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select teacher_subject.*, subject.subject from teacher_subject left join subject on teacher_subject.subject_id = subject.id order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select teacher_subject.*, subject.subject from teacher_subject left join subject on teacher_subject.subject_id = subject.id where $filterString order by $sortBy $sortType limit $offset, $limit";
        //echo "Sql is : " . $sql;
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objTeacher_subject = new Teacher_subject_object();
            $objTeacher_subject->id = $row['id'];
            $objTeacher_subject->employee_id = $row['employee_id'];
            $objTeacher_subject->subject_id = $row['subject_id'];
            $objTeacher_subject->subject = $row['subject'];
            $records[] = $objTeacher_subject;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

