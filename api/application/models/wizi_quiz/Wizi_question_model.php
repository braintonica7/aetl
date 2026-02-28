<?php

class Wizi_question_model extends CI_Model
{

    public function get_wizi_question($id)
    {
        $objQuestion = NULL;
        $sql = "SELECT * FROM wizi_question WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objQuestion = $this->mapRowToObject($row);
        }
        $statement = NULL;
        $pdo = NULL;
        return $objQuestion;
    }

    public function get_all_wizi_questions()
    {
        $records = array();
        $sql = "SELECT * FROM wizi_question WHERE invalid_question = 0 ORDER BY id DESC";
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

    public function get_paginated_wizi_question($offset, $limit, $sortBy, $sortType, $filterString = NULL)
    {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "SELECT * FROM wizi_question 
                    WHERE invalid_question = 0 
                    ORDER BY $sortBy $sortType LIMIT $offset, $limit";
        else
            $sql = "SELECT * FROM wizi_question 
                    WHERE $filterString AND invalid_question = 0 
                    ORDER BY $sortBy $sortType LIMIT $offset, $limit";

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

    public function add_wizi_question($objQuestion)
    {
        $pdo = CDatabase::getPdo();

        $sql = "SELECT MAX(id) as mvalue FROM wizi_question";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objQuestion->id = $row['mvalue'];
        else
            $objQuestion->id = 0;
        $objQuestion->id = $objQuestion->id + 1;
        
        $sql = "INSERT INTO wizi_question 
                (`id`, `question_img_url`, `has_multiple_answer`, `duration`, `option_count`, 
                 `exam_id`, `subject_id`, `chapter_name`, `chapter_id`, `level`, `topic_id`, 
                 `correct_option`, `solution`, `question_text`, `ai_summary`, `summary_generated_at`, 
                 `summary_confidence`, `option_a`, `option_b`, `option_c`, `option_d`, 
                 `subject_name`, `topic_name`, `difficulty`, `invalid_question`, `year`, `question_type`, `flag_reason`, `language`) 
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objQuestion->id,
            $objQuestion->question_img_url,
            $objQuestion->has_multiple_answer,
            $objQuestion->duration,
            $objQuestion->option_count,
            $objQuestion->exam_id,
            $objQuestion->subject_id,
            $objQuestion->chapter_name,
            $objQuestion->chapter_id,
            $objQuestion->level,
            $objQuestion->topic_id,
            $objQuestion->correct_option,
            $objQuestion->solution,
            $objQuestion->question_text,
            $objQuestion->ai_summary,
            $objQuestion->summary_generated_at,
            $objQuestion->summary_confidence,
            $objQuestion->option_a,
            $objQuestion->option_b,
            $objQuestion->option_c,
            $objQuestion->option_d,
            $objQuestion->subject_name,
            $objQuestion->topic_name,
            $objQuestion->difficulty,
            $objQuestion->invalid_question,
            $objQuestion->year,
            $objQuestion->question_type,
            $objQuestion->flag_reason,
            $objQuestion->language
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objQuestion;
        return FALSE;
    }

    public function update_wizi_question($objQuestion)
    {
        $sql = "UPDATE wizi_question SET 
                question_img_url = ?, has_multiple_answer = ?, duration = ?, option_count = ?, 
                exam_id = ?, subject_id = ?, chapter_name = ?, chapter_id = ?, level = ?, topic_id = ?, 
                correct_option = ?, solution = ?, question_text = ?, ai_summary = ?, summary_generated_at = ?, 
                summary_confidence = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, 
                subject_name = ?, topic_name = ?, difficulty = ?, invalid_question = ?, year = ?, 
                question_type = ?, flag_reason = ?, language = ?
                WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objQuestion->question_img_url,
            $objQuestion->has_multiple_answer,
            $objQuestion->duration,
            $objQuestion->option_count,
            $objQuestion->exam_id,
            $objQuestion->subject_id,
            $objQuestion->chapter_name,
            $objQuestion->chapter_id,
            $objQuestion->level,
            $objQuestion->topic_id,
            $objQuestion->correct_option,
            $objQuestion->solution,
            $objQuestion->question_text,
            $objQuestion->ai_summary,
            $objQuestion->summary_generated_at,
            $objQuestion->summary_confidence,
            $objQuestion->option_a,
            $objQuestion->option_b,
            $objQuestion->option_c,
            $objQuestion->option_d,
            $objQuestion->subject_name,
            $objQuestion->topic_name,
            $objQuestion->difficulty,
            $objQuestion->invalid_question,
            $objQuestion->year,
            $objQuestion->question_type,
            $objQuestion->flag_reason,
            $objQuestion->language,
            $objQuestion->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objQuestion;
        return FALSE;
    }

    public function update_solution($objQuestion)
    {
        $sql = "UPDATE wizi_question SET solution = ? WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objQuestion->solution,
            $objQuestion->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objQuestion;
        return FALSE;
    }

    public function delete_wizi_question($id)
    {
        $sql = "DELETE FROM wizi_question WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_wizi_question_count($filterString = NULL)
    {
        $count = 0;
        $sql = "SELECT COUNT(id) as cnt FROM wizi_question WHERE invalid_question = 0";
        if ($filterString != NULL) {
            $sql .= " AND $filterString";
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

    private function mapRowToObject($row)
    {
        $objQuestion = new Wizi_question_object();
        $objQuestion->id = $row['id'];
        $objQuestion->question_img_url = $row['question_img_url'];
        $objQuestion->has_multiple_answer = isset($row['has_multiple_answer']) ? $row['has_multiple_answer'] : 0;
        $objQuestion->duration = isset($row['duration']) ? $row['duration'] : 0;
        $objQuestion->option_count = isset($row['option_count']) ? $row['option_count'] : 4;
        $objQuestion->exam_id = isset($row['exam_id']) ? $row['exam_id'] : 0;
        $objQuestion->subject_id = isset($row['subject_id']) ? $row['subject_id'] : 0;
        $objQuestion->chapter_name = isset($row['chapter_name']) ? $row['chapter_name'] : '';
        $objQuestion->chapter_id = isset($row['chapter_id']) ? $row['chapter_id'] : 0;
        $objQuestion->level = isset($row['level']) ? $row['level'] : '';
        $objQuestion->topic_id = isset($row['topic_id']) ? $row['topic_id'] : 0;
        $objQuestion->correct_option = isset($row['correct_option']) ? $row['correct_option'] : '';
        $objQuestion->solution = isset($row['solution']) ? $row['solution'] : '';
        $objQuestion->question_text = isset($row['question_text']) ? $row['question_text'] : '';
        $objQuestion->ai_summary = isset($row['ai_summary']) ? $row['ai_summary'] : '';
        $objQuestion->summary_generated_at = isset($row['summary_generated_at']) ? $row['summary_generated_at'] : null;
        $objQuestion->summary_confidence = isset($row['summary_confidence']) ? $row['summary_confidence'] : 0.00;
        $objQuestion->option_a = isset($row['option_a']) ? $row['option_a'] : '';
        $objQuestion->option_b = isset($row['option_b']) ? $row['option_b'] : '';
        $objQuestion->option_c = isset($row['option_c']) ? $row['option_c'] : '';
        $objQuestion->option_d = isset($row['option_d']) ? $row['option_d'] : '';
        $objQuestion->subject_name = isset($row['subject_name']) ? $row['subject_name'] : '';
        $objQuestion->topic_name = isset($row['topic_name']) ? $row['topic_name'] : '';
        $objQuestion->difficulty = isset($row['difficulty']) ? $row['difficulty'] : '';
        $objQuestion->invalid_question = isset($row['invalid_question']) ? $row['invalid_question'] : 0;
        $objQuestion->year = isset($row['year']) ? $row['year'] : 2025;
        $objQuestion->question_type = isset($row['question_type']) ? $row['question_type'] : 'mock';
        $objQuestion->flag_reason = isset($row['flag_reason']) ? $row['flag_reason'] : '';
        $objQuestion->language = isset($row['language']) ? $row['language'] : 'en';
        $objQuestion->created_at = isset($row['created_at']) ? $row['created_at'] : null;
        $objQuestion->updated_at = isset($row['updated_at']) ? $row['updated_at'] : null;
        return $objQuestion;
    }

}
