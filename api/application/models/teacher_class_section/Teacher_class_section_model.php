<?php

class Teacher_class_section_model extends CI_Model {

    public function get_teacher_class_section($id) {
        $objTeacher_class_section = NULL;
        $sql = "select * from teacher_class_section where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objTeacher_class_section = new Teacher_class_section_object();
            $objTeacher_class_section->id = $row['id'];
            $objTeacher_class_section->employee_id = $row['employee_id'];
            $objTeacher_class_section->class_id = $row['class_id'];
            $objTeacher_class_section->section_id = $row['section_id'];
            $objTeacher_class_section->subject_id = $row['subject_id'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objTeacher_class_section;
    }

    public function get_all_teacher_class_sections() {
        $records = array();

        $sql = "select * from teacher_class_section";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objTeacher_class_section = new Teacher_class_section_object();
            $objTeacher_class_section->id = $row['id'];
            $objTeacher_class_section->employee_id = $row['employee_id'];
            $objTeacher_class_section->class_id = $row['class_id'];
            $objTeacher_class_section->section_id = $row['section_id'];
            $objTeacher_class_section->subject_id = $row['subject_id'];
            
            $records[] = $objTeacher_class_section;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_teacher_class_section($objTeacher_class_section) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from teacher_class_section";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objTeacher_class_section->id = $row['mvalue'];
        else
            $objTeacher_class_section->id = 0;
        $objTeacher_class_section->id = $objTeacher_class_section->id + 1;
        $sql = "insert into teacher_class_section values (?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objTeacher_class_section->id,
            $objTeacher_class_section->employee_id,
            $objTeacher_class_section->class_id,
            $objTeacher_class_section->section_id,
            $objTeacher_class_section->subject_id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted) {
            return $objTeacher_class_section;
        }
        return FALSE;
    }

    public function update_teacher_class_section($objTeacher_class_section) {
        $sql = "update teacher_class_section set employee_id = ?, class_id = ?, section_id = ?, subject_id = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objTeacher_class_section->employee_id,
            $objTeacher_class_section->class_id,
            $objTeacher_class_section->section_id,
            $objTeacher_class_section->subject_id,
            $objTeacher_class_section->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated) {
            return $objTeacher_class_section;
        }
        return FALSE;
    }

    public function delete_teacher_class_section($id) {
        $sql = "delete from teacher_class_section where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_teacher_class_section_count() {
        $count = 0;
        $sql = "select count(id) as cnt from teacher_class_section";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_teacher_class_section($offset, $limit, $sortBy, $sortType, $filterString = NULL, &$filterRecordCount = -1) {
        $pdo = CDatabase::getPdo();
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select teacher_class_section.*, genere.class_name, section.section, subject.subject from teacher_class_section left join genere on teacher_class_section.class_id = genere.id left join section on teacher_class_section.section_id = section.id left join subject on teacher_class_section.subject_id = subject.id order by $sortBy $sortType limit $offset, $limit";
        else {
            $sql = "select count(id) as rec_count from teacher_class_section where $filterString";
            $countStatement = $pdo->prepare($sql);
            $countStatement->execute();
            if ($row = $countStatement->fetch())
                $filterRecordCount = $row['rec_count'];
            $countStatement = NULL;
            $sql = "select teacher_class_section.*, genere.class_name, section.section, subject.subject from teacher_class_section left join genere on teacher_class_section.class_id = genere.id left join section on teacher_class_section.section_id = section.id left join subject on teacher_class_section.subject_id = subject.id where $filterString order by $sortBy $sortType limit $offset, $limit";
        }
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objTeacher_class_section = new Teacher_class_section_object();
            $objTeacher_class_section->id = $row['id'];
            $objTeacher_class_section->employee_id = $row['employee_id'];
            $objTeacher_class_section->class_id = $row['class_id'];
            $objTeacher_class_section->section_id = $row['section_id'];
            $objTeacher_class_section->subject_id = $row['subject_id'];
            $objTeacher_class_section->class_name = $row['class_name'];
            $objTeacher_class_section->section = $row['section'];
            $objTeacher_class_section->subject = $row['subject'];
            
            $records[] = $objTeacher_class_section;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

