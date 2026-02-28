<?php

class Subject_model extends CI_Model {

    public function get_subject($id) {
        $sql = "select * from subject where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $objSubject = null;
        if ($row = $statement->fetch()) {
            $objSubject = new Subject_object();
            $objSubject->id = $row['id'];
            $objSubject->subject = $row['subject'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objSubject;
    }

    public function get_all_subjects() {
        $records = array();

        $sql = "select * from subject";        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objSubject = new Subject_object();
            $objSubject->id = $row['id'];
            $objSubject->subject = $row['subject'];

            $records[] = $objSubject;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }
    
    public function get_subjects_for_faculty($employeeId){
        $subjects = array();
        $pdo = CDatabase::getPdo();
        $sql = "select subject.* from teacher_subject left join subject on teacher_subject.subject_id = subject.id where teacher_subject.employee_id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($employeeId));
        while ($row = $statement->fetch()){
            $objSubject = new Subject_object(); 
            $objSubject->id = $row['id'];
            $objSubject->subject = $row['subject'];
            
            $subjects[] = $objSubject;
        }
        $statement = NULL;
        $pdo = NULL();
        return $subjects();
    }

    public function add_subject($objSubject) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from subject";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objSubject->id = $row['mvalue'];
        else
            $objSubject->id = 0;
        $objSubject->id = $objSubject->id + 1;
        $sql = "insert into subject values (?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objSubject->id,
            $objSubject->subject
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objSubject;
        return FALSE;
    }

    public function update_subject($objSubject) {
        $sql = "update subject set subject = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objSubject->subject,
            $objSubject->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objSubject;
        return FALSE;
    }

    public function delete_subject($id) {
        $sql = "delete from subject where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_subject_count() {
        $count = 0;
        $sql = "select count(id) as cnt from subject";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_subject($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        $sortBy = 'subject';
        $sortType = 'asc';
        if ($filterString == NULL)
            $sql = "select * from subject order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select * from subject where $filterString order by $sortBy $sortType limit $offset, $limit";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objSubject = new Subject_object();
            $objSubject->id = $row['id'];
            $objSubject->subject = $row['subject'];
            $records[] = $objSubject;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }
    
    public function get_subjects_for_class($classId){
        $subjects = array();
        $pdo = CDatabase::getPdo();
        $sql = "select DISTINCT subject.id, subject.subject from content left join subject on content.subject_id = subject.id where content.class_id = ? order by subject.subject";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($classId));
        while ($row = $statement->fetch()){
            $objSubject = new Subject_object();
            $objSubject->id = $row['id'];
            $objSubject->subject = $row['subject'];
            $subjects[] = $objSubject;
        }
        $statement = NULL;
        $pdo = NULL;
        return $subjects;
    }

    /**
     * Get subjects that have questions with question counts
     * @return array Array of Subject_object with question_count property
     */
    public function get_subjects_with_question_counts() {
        $subjects = array();
        $pdo = CDatabase::getPdo();
        
        // Query to get subjects that have questions (either directly or through chapters/topics)
        $sql = "SELECT DISTINCT s.id, s.subject, 
                       COUNT(DISTINCT q.id) as question_count
                FROM subject s
                LEFT JOIN question q ON (q.subject_id = s.id 
                                      OR q.chapter_id IN (SELECT id FROM chapter WHERE subject_id = s.id)
                                      OR q.topic_id IN (SELECT id FROM topic WHERE subject_id = s.id))
                WHERE q.id IS NOT NULL
                GROUP BY s.id, s.subject
                HAVING question_count > 0
                ORDER BY s.subject";
        
        $statement = $pdo->prepare($sql);
        $statement->execute();
        
        while ($row = $statement->fetch()) {
            $objSubject = new Subject_object();
            $objSubject->id = $row['id'];
            $objSubject->subject = $row['subject'];
            $objSubject->question_count = (int)$row['question_count'];
            
            $subjects[] = $objSubject;
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $subjects;
    }

}
?>

