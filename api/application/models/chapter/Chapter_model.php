<?php

class Chapter_model extends CI_Model {

    public function get_chapter($id) {
        $objChapter = NULL;
        $sql = "select * from chapter where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objChapter = new Chapter_object();
            $objChapter->id = $row['id'];
            $objChapter->chapter_name = $row['chapter_name'];
            $objChapter->subject_id = $row['subject_id'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objChapter;
    }

    public function get_all_chapters() {
        $records = array();

        $sql = "select * from chapter";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objChapter = new Chapter_object();
			$objChapter->id = $row['id'];
            $objChapter->chapter_name = $row['chapter_name'];
            $objChapter->subject_id = $row['subject_id'];
            $records[] = $objChapter;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_chapter($objChapter) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from chapter";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objChapter->id = $row['mvalue'];
        else
            $objChapter->id = 0;
        $objChapter->id = $objChapter->id + 1;
        $sql = "insert into chapter values (?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objChapter->id,
            $objChapter->chapter_name,
            $objChapter->subject_id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objChapter;
        return FALSE;
    }

    public function update_chapter($objChapter) {
        $sql = "update chapter set chapter_name = ?, subject_id = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objChapter->chapter_name,
            $objChapter->subject_id,
            $objChapter->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objChapter;
        return FALSE;
    }

    public function delete_chapter($id) {
        $sql = "delete from chapter where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_chapter_count() {
        $count = 0;
        $sql = "select count(id) as cnt from chapter";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_chapter($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        $sortBy = 'chapter_name';
        $sortType = 'asc';
        if ($filterString == NULL)
            $sql = "select * from chapter order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select * from chapter where $filterString order by $sortBy $sortType limit $offset, $limit";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objChapter = new Chapter_object();
            $objChapter->id = $row['id'];
            $objChapter->chapter_name = $row['chapter_name'];
            $objChapter->subject_id = $row['subject_id'];
            $records[] = $objChapter;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    /**
     * Get chapters that have questions with question counts for a specific subject
     * @param int $subject_id The subject ID to filter chapters by
     * @return array Array of Chapter_object with question_count property
     */
    public function get_chapters_with_question_counts($subject_id) {
        $chapters = array();
        $pdo = CDatabase::getPdo();
        
        // Query to get chapters that have questions (either directly or through topics)
        $sql = "SELECT DISTINCT c.id, c.chapter_name, c.subject_id,
                       COUNT(DISTINCT q.id) as question_count
                FROM chapter c
                LEFT JOIN question q ON (q.chapter_id = c.id 
                                      OR q.topic_id IN (SELECT id FROM topic WHERE chapter_id = c.id))
                WHERE c.subject_id = ? AND q.id IS NOT NULL
                GROUP BY c.id, c.chapter_name, c.subject_id
                HAVING question_count > 0
                ORDER BY c.chapter_name";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($subject_id));
        
        while ($row = $statement->fetch()) {
            $objChapter = new Chapter_object();
            $objChapter->id = $row['id'];
            $objChapter->chapter_name = $row['chapter_name'];
            $objChapter->subject_id = $row['subject_id'];
            $objChapter->question_count = (int)$row['question_count'];
            
            $chapters[] = $objChapter;
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $chapters;
    }

}
?>

