<?php

class Topic_model extends CI_Model {

    public function get_topic($id) {
        $sql = "select * from topic where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objTopic = new Topic_object();
            $objTopic->id = $row['id'];
            $objTopic->topic_name = $row['topic_name'];
            $objTopic->chapter_id = $row['chapter_id'];
            $objTopic->subject_id = $row['subject_id'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objTopic;
    }

    public function get_all_topics() {
        $records = array();

        $sql = "select * from topic";        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objTopic = new Topic_object();
            $objTopic->id = $row['id'];
            $objTopic->topic_name = $row['topic_name'];
            $objTopic->chapter_id = $row['chapter_id'];
            $objTopic->subject_id = $row['subject_id'];

            $records[] = $objTopic;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }
    
    public function get_topics_for_faculty($employeeId){
        $topics = array();
        $pdo = CDatabase::getPdo();
        $sql = "select topic.* from teacher_topic left join topic on teacher_topic.topic_id = topic.id where teacher_topic.employee_id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($employeeId));
        while ($row = $statement->fetch()){
            $objTopic = new Topic_object(); 
            $objTopic->id = $row['id'];
            $objTopic->topic_name = $row['topic_name'];
            $objTopic->chapter_id = $row['chapter_id'];
            $objTopic->subject_id = $row['subject_id'];
            
            $topics[] = $objTopic;
        }
        $statement = NULL;
        $pdo = NULL;
        return $topics;
    }

    public function add_topic($objTopic) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from topic";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objTopic->id = $row['mvalue'];
        else
            $objTopic->id = 0;
        $objTopic->id = $objTopic->id + 1;
        $sql = "insert into topic values (?,?, ?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objTopic->id,
            $objTopic->topic_name,
            $objTopic->chapter_id,
            $objTopic->subject_id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objTopic;
        return FALSE;
    }

    public function update_topic($objTopic) {
        $sql = "update topic set topic_name = ?, chapter_id = ?, subject_id = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objTopic->topic_name,
            $objTopic->chapter_id,
            $objTopic->subject_id,
            $objTopic->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objTopic;
        return FALSE;
    }

    public function delete_topic($id) {
        $sql = "delete from topic where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_topic_count() {
        $count = 0;
        $sql = "select count(id) as cnt from topic";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_topic($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        $sortBy = 'topic_name';
        $sortType = 'asc';
        if ($filterString == NULL)
            $sql = "select * from topic order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select * from topic where $filterString order by $sortBy $sortType limit $offset, $limit";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objTopic = new Topic_object();
            $objTopic->id = $row['id'];
            $objTopic->topic_name = $row['topic_name'];
            $objTopic->chapter_id = $row['chapter_id'];
            $objTopic->subject_id = $row['subject_id'];
            $records[] = $objTopic;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }
    
    public function get_topics_for_class($classId){
        $topics = array();
        $pdo = CDatabase::getPdo();
        $sql = "select DISTINCT topic.id, topic.topic_name from content left join topic on content.topic_id = topic.id where content.class_id = ? order by topic.topic_name";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($classId));
        while ($row = $statement->fetch()){
            $objTopic = new Topic_object();
            $objTopic->id = $row['id'];
            $objTopic->topic_name = $row['topic_name'];
            $objTopic->chapter_id = $row['chapter_id'];
            $objTopic->subject_id = $row['subject_id'];
            $topics[] = $objTopic;
        }
        $statement = NULL;
        $pdo = NULL;
        return $topics;
    }

    /**
     * Get topics that have questions with question counts for a specific chapter
     * @param int $chapter_id The chapter ID to filter topics by
     * @return array Array of Topic_object with question_count property
     */
    public function get_topics_with_question_counts($chapter_id) {
        $topics = array();
        $pdo = CDatabase::getPdo();
        
        // Query to get topics that have questions directly assigned to them
        $sql = "SELECT DISTINCT t.id, t.topic_name, t.chapter_id, t.subject_id,
                       COUNT(DISTINCT q.id) as question_count
                FROM topic t
                LEFT JOIN question q ON q.topic_id = t.id
                WHERE t.chapter_id = ? AND q.id IS NOT NULL
                GROUP BY t.id, t.topic_name, t.chapter_id, t.subject_id
                HAVING question_count > 0
                ORDER BY t.topic_name";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($chapter_id));
        
        while ($row = $statement->fetch()) {
            $objTopic = new Topic_object();
            $objTopic->id = $row['id'];
            $objTopic->topic_name = $row['topic_name'];
            $objTopic->chapter_id = $row['chapter_id'];
            $objTopic->subject_id = $row['subject_id'];
            $objTopic->question_count = (int)$row['question_count'];
            
            $topics[] = $objTopic;
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $topics;
    }

}
?>

