<?php

class Wizi_quiz_model extends CI_Model
{

    public function get_wizi_quiz($id)
    {
        $objWiziQuiz = NULL;
        $sql = "SELECT * FROM wizi_quiz WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objWiziQuiz = $this->mapRowToObject($row);
        }
        $statement = NULL;
        $pdo = NULL;
        return $objWiziQuiz;
    }

    public function get_all_wizi_quizzes()
    {
        $records = array();
        $sql = "SELECT * FROM wizi_quiz ORDER BY id DESC";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $records[] = $this->mapRowToObject($row);
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function get_active_wizi_quizzes($language = 'english')
    {
        $records = array();
        $sql = "SELECT * FROM wizi_quiz 
                WHERE status = 'active' 
                AND is_published = 1 
                AND (valid_from IS NULL OR valid_from <= NOW())
                AND (valid_until IS NULL OR valid_until >= NOW())
                AND language = ?
                ORDER BY quiz_order asc";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($language));
        while ($row = $statement->fetch()) {
            $records[] = $this->mapRowToObject($row);
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function get_paginated_wizi_quiz($offset, $limit, $sortBy, $sortType, $filterString = NULL)
    {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "SELECT wizi_quiz.*, 
                    (SELECT COUNT(*) FROM wizi_quiz_question WHERE wizi_quiz_id = wizi_quiz.id) as question_count
                    FROM wizi_quiz 
                    ORDER BY $sortBy $sortType LIMIT $offset, $limit";
        else
            $sql = "SELECT wizi_quiz.*, 
                    (SELECT COUNT(*) FROM wizi_quiz_question WHERE wizi_quiz_id = wizi_quiz.id) as question_count
                    FROM wizi_quiz 
                    WHERE $filterString 
                    ORDER BY $sortBy $sortType LIMIT $offset, $limit";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objWiziQuiz = $this->mapRowToObject($row);
            $objWiziQuiz->question_count = isset($row['question_count']) ? (int)$row['question_count'] : 0;
            $records[] = $objWiziQuiz;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_wizi_quiz($objWiziQuiz)
    {
        $pdo = CDatabase::getPdo();

        $sql = "SELECT MAX(id) as mvalue FROM wizi_quiz";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objWiziQuiz->id = $row['mvalue'];
        else
            $objWiziQuiz->id = 0;
        $objWiziQuiz->id = $objWiziQuiz->id + 1;
        
        // Ensure proper type casting for integer fields to prevent empty string errors
        $exam_id = empty($objWiziQuiz->exam_id) ? null : (int)$objWiziQuiz->exam_id;
        $subject_id = empty($objWiziQuiz->subject_id) ? null : (int)$objWiziQuiz->subject_id;
        $time_limit = (int)$objWiziQuiz->time_limit;
        $passing_score = (int)$objWiziQuiz->passing_score;
        $total_marks = (int)$objWiziQuiz->total_marks;
        $is_published = empty($objWiziQuiz->is_published) ? 0 : (int)$objWiziQuiz->is_published;
        $created_by = empty($objWiziQuiz->created_by) ? null : (int)$objWiziQuiz->created_by;
        
        $sql = "INSERT INTO wizi_quiz 
                (`id`, `name`, `description`, `instructions`, `exam_id`, `subject_id`, `level`, 
                 `time_limit`, `passing_score`, `passing_percentage`, `total_marks`, `status`, 
                 `is_published`, `published_date`, `valid_from`, `valid_until`, `cover_image`, `quiz_order`, `created_by`, `language`) 
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objWiziQuiz->id,
            $objWiziQuiz->name,
            $objWiziQuiz->description,
            $objWiziQuiz->instructions,
            $exam_id,
            $subject_id,
            $objWiziQuiz->level,
            $time_limit,
            $passing_score,
            $objWiziQuiz->passing_percentage,
            $total_marks,
            $objWiziQuiz->status,
            $is_published,
            $objWiziQuiz->published_date,
            $objWiziQuiz->valid_from,
            $objWiziQuiz->valid_until,
            $objWiziQuiz->cover_image,
            $objWiziQuiz->quiz_order,
            $created_by,
            $objWiziQuiz->language
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objWiziQuiz;
        return FALSE;
    }

    public function update_wizi_quiz($objWiziQuiz)
    {
        $sql = "UPDATE wizi_quiz SET 
                name = ?, description = ?, instructions = ?, exam_id = ?, subject_id = ?, level = ?, 
                time_limit = ?, passing_score = ?, passing_percentage = ?, total_marks = ?, status = ?, 
                is_published = ?, published_date = ?, valid_from = ?, valid_until = ?, cover_image = ?, quiz_order = ?, language = ?
                WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objWiziQuiz->name,
            $objWiziQuiz->description,
            $objWiziQuiz->instructions,
            $objWiziQuiz->exam_id,
            $objWiziQuiz->subject_id,
            $objWiziQuiz->level,
            $objWiziQuiz->time_limit,
            $objWiziQuiz->passing_score,
            $objWiziQuiz->passing_percentage,
            $objWiziQuiz->total_marks,
            $objWiziQuiz->status,
            $objWiziQuiz->is_published,
            $objWiziQuiz->published_date,
            $objWiziQuiz->valid_from,
            $objWiziQuiz->valid_until,
            $objWiziQuiz->cover_image,
            $objWiziQuiz->quiz_order,
            $objWiziQuiz->language,
            $objWiziQuiz->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objWiziQuiz;
        return FALSE;
    }

    public function delete_wizi_quiz($id)
    {
        $sql = "DELETE FROM wizi_quiz WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_wizi_quiz_count($filterString = NULL)
    {
        $count = 0;
        $sql = "SELECT COUNT(id) as cnt FROM wizi_quiz";
        if ($filterString != NULL) {
            $sql .= " WHERE $filterString";
        }
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function publish_wizi_quiz($id)
    {
        $sql = "UPDATE wizi_quiz SET is_published = 1, published_date = NOW(), status = 'active' WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
        return $updated;
    }

    public function unpublish_wizi_quiz($id)
    {
        $sql = "UPDATE wizi_quiz SET is_published = 0 WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
        return $updated;
    }

    private function mapRowToObject($row)
    {
        $objWiziQuiz = new Wizi_quiz_object();
        $objWiziQuiz->id = $row['id'];
        $objWiziQuiz->name = $row['name'];
        $objWiziQuiz->description = $row['description'];
        $objWiziQuiz->instructions = isset($row['instructions']) ? $row['instructions'] : '';
        $objWiziQuiz->exam_id = isset($row['exam_id']) ? $row['exam_id'] : 0;
        $objWiziQuiz->subject_id = isset($row['subject_id']) ? $row['subject_id'] : 0;
        $objWiziQuiz->level = isset($row['level']) ? $row['level'] : 'Moderate';
        $objWiziQuiz->time_limit = isset($row['time_limit']) ? $row['time_limit'] : 180;
        $objWiziQuiz->passing_score = isset($row['passing_score']) ? $row['passing_score'] : 0;
        $objWiziQuiz->passing_percentage = isset($row['passing_percentage']) ? $row['passing_percentage'] : 0.00;
        $objWiziQuiz->total_marks = isset($row['total_marks']) ? $row['total_marks'] : 0;
        $objWiziQuiz->status = isset($row['status']) ? $row['status'] : 'draft';
        $objWiziQuiz->is_published = isset($row['is_published']) ? $row['is_published'] : 0;
        $objWiziQuiz->published_date = isset($row['published_date']) ? $row['published_date'] : null;
        $objWiziQuiz->valid_from = isset($row['valid_from']) ? $row['valid_from'] : null;
        $objWiziQuiz->valid_until = isset($row['valid_until']) ? $row['valid_until'] : null;
        $objWiziQuiz->cover_image = isset($row['cover_image']) ? $row['cover_image'] : null;
        $objWiziQuiz->quiz_order = isset($row['quiz_order']) ? $row['quiz_order'] : null;
        $objWiziQuiz->created_by = isset($row['created_by']) ? $row['created_by'] : 0;
        $objWiziQuiz->created_at = isset($row['created_at']) ? $row['created_at'] : null;
        $objWiziQuiz->updated_at = isset($row['updated_at']) ? $row['updated_at'] : null;
        $objWiziQuiz->language = isset($row['language']) ? $row['language'] : 'english';
        return $objWiziQuiz;
    }

    public function get_previous_quizzes_in_sequence($quiz_order)
    {
        $records = array();
        if ($quiz_order === null || $quiz_order <= 1) {
            return $records; // No previous quizzes
        }
        
        $sql = "SELECT * FROM wizi_quiz 
                WHERE status = 'active' 
                AND is_published = 1 
                AND quiz_order < ?
                ORDER BY quiz_order ASC";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($quiz_order));
        while ($row = $statement->fetch()) {
            $records[] = $this->mapRowToObject($row);
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
