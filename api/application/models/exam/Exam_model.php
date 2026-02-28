<?php

class Exam_model extends CI_Model {

    public function get_exam($id) {
        $sql = "select * from exam where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objExam = new Exam_object();
            $objExam->id = $row['id'];
            $objExam->exam_name = $row['exam_name'];
            $objExam->max_score = $row['max_score'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objExam;
    }

    public function get_all_exams() {
        $records = array();

        $sql = "select * from exam";        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objExam = new Exam_object();
            $objExam->id = $row['id'];
            $objExam->exam_name = $row['exam_name'];
            $objExam->max_score = $row['max_score'];

            $records[] = $objExam;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }
    
    public function get_exams_for_faculty($employeeId){
        $exams = array();
        $pdo = CDatabase::getPdo();
        $sql = "select exam.* from teacher_exam left join exam on teacher_exam.exam_id = exam.id where teacher_exam.employee_id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($employeeId));
        while ($row = $statement->fetch()){
            $objExam = new Exam_object(); 
            $objExam->id = $row['id'];
            $objExam->exam_name = $row['exam_name'];
            $objExam->max_score = $row['max_score'];
            
            $exams[] = $objExam;
        }
        $statement = NULL;
        $pdo = NULL();
        return $exams();
    }

    public function add_exam($objExam) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from exam";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objExam->id = $row['mvalue'];
        else
            $objExam->id = 0;
        $objExam->id = $objExam->id + 1;
        $sql = "insert into exam values (?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objExam->id,
            $objExam->exam_name,
            $objExam->max_score
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objExam;
        return FALSE;
    }

    public function update_exam($objExam) {
        $sql = "update exam set exam_name = ?, max_score = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objExam->exam_name,
            $objExam->max_score,
            $objExam->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objExam;
        return FALSE;
    }

    public function delete_exam($id) {
        $sql = "delete from exam where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_exam_count() {
        $count = 0;
        $sql = "select count(id) as cnt from exam";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_exam($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        $sortBy = 'exam_name';
        $sortType = 'asc';
        if ($filterString == NULL)
            $sql = "select * from exam order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select * from exam where $filterString order by $sortBy $sortType limit $offset, $limit";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objExam = new Exam_object();
            $objExam->id = $row['id'];
            $objExam->exam_name = $row['exam_name'];
            $objExam->max_score = $row['max_score'];
            $records[] = $objExam;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }
    
    public function get_exams_for_class($classId){
        $exams = array();
        $pdo = CDatabase::getPdo();
        $sql = "select DISTINCT exam.id, exam.exam_name, exam.max_score from content left join exam on content.exam_id = exam.id where content.class_id = ? order by exam.exam_name";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($classId));
        while ($row = $statement->fetch()){
            $objExam = new Exam_object();
            $objExam->id = $row['id'];
            $objExam->exam_name = $row['exam_name'];
            $objExam->max_score = $row['max_score'];
            $exams[] = $objExam;
        }
        $statement = NULL;
        $pdo = NULL;
        return $exams;
    }
    public function get_subjects_for_exam($examId){
        $subjects = array();
        $pdo = CDatabase::getPdo();
        $sql = "select exam_subject.*, subject.subject from exam_subject left join subject on exam_subject.subject_id = subject.id where exam_subject.exam_id = ? order by subject.subject";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($examId));
        while ($row = $statement->fetch()){
            $objExamSubject = new Exam_subject_object();
            $objExamSubject->id = $row['id'];
            $objExamSubject->exam_id = $row['exam_id'];
            $objExamSubject->subject_id = $row['subject_id'];
            $objExamSubject->max_score = $row['max_score'];
            $objExamSubject->subject_name = $row['subject'];
            $subjects[] = $objExamSubject;
        }
        $statement = NULL;
        $pdo = NULL;
        return $subjects;
    }

}
?>

