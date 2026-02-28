<?php

class Question_model extends CI_Model {

    public function get_Question($id) {
        $objQuestion = NULL;
        $sql = "select * from question where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objQuestion = new Question_object();
            $objQuestion->id = $row['id'];
            $objQuestion->question_img_url = $row['question_img_url'];
            $objQuestion->has_multiple_answer = $row['has_multiple_answer'];
            $objQuestion->duration = $row['duration'];
            $objQuestion->option_count = $row['option_count'];
            $objQuestion->exam_id = $row['exam_id'];
            $objQuestion->subject_id = $row['subject_id'];
            $objQuestion->chapter_name = $row['chapter_name'] ?? '';
            $objQuestion->chapter_id = $row['chapter_id'];
            $objQuestion->topic_id = $row['topic_id'];
            $objQuestion->level = $row['level'];
            $objQuestion->correct_option = $row['correct_option'];
            $objQuestion->solution = $row['solution'];
            $objQuestion->question_text = $row['question_text'];
            $objQuestion->ai_summary = $row['ai_summary'] ?? '';
            $objQuestion->summary_generated_at = $row['summary_generated_at'];
            $objQuestion->summary_confidence = $row['summary_confidence'] ?? 0.0;
            $objQuestion->option_a = $row['option_a'] ?? '';
            $objQuestion->option_b = $row['option_b'] ?? '';
            $objQuestion->option_c = $row['option_c'] ?? '';
            $objQuestion->option_d = $row['option_d'] ?? '';
            $objQuestion->subject_name = $row['subject_name'] ?? '';
            $objQuestion->topic_name = $row['topic_name'] ?? '';
            $objQuestion->difficulty = $row['difficulty'] ?? '';
            $objQuestion->invalid_question = $row['invalid_question'] ?? false;
            $objQuestion->year = $row['year'] ?? 2025;
            $objQuestion->question_type = $row['question_type'] ?? 'regular';
            $objQuestion->flag_reason = $row['flag_reason'] ?? null;
            $objQuestion->language = $row['language'] ?? 'en';
        }
        $statement = NULL;
        $pdo = NULL;
        return $objQuestion;
    }

    public function get_all_Questions() {
        $records = array();

        $sql = "select * from question";
        $sql = "select * from question where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objQuestion = new Question_object();
            $objQuestion->id = $row['id'];
            $objQuestion->question_img_url = $row['question_img_url'];
            $objQuestion->has_multiple_answer = $row['has_multiple_answer'];
            $objQuestion->duration = $row['duration'];
            $objQuestion->option_count = $row['option_count'];
            $objQuestion->exam_id = $row['exam_id'];
            $objQuestion->subject_id = $row['subject_id'];
            $objQuestion->chapter_id = $row['chapter_id'];
            $objQuestion->topic_id = $row['topic_id'];
            $objQuestion->level = $row['level'];
            $objQuestion->correct_option = $row['correct_option'];
            $objQuestion->solution = $row['solution'];
            $objQuestion->question_text = $row['question_text'];
            $records[] = $objQuestion;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_Question($objQuestion) {
        //log_message('info', 'add_Question: Starting question insertion');
        
        try {
            $pdo = CDatabase::getPdo();
            
            // Get max ID
            $sql = "select max(id) as mvalue from question";
            $statement = $pdo->prepare($sql);
            $statement->execute();
            
            if ($row = $statement->fetch()) {
                $objQuestion->id = $row['mvalue'];
                //log_message('info', "add_Question: Found max ID: " . $objQuestion->id);
            } else {
                $objQuestion->id = 0;
                //log_message('info', 'add_Question: No existing records, starting from 0');
            }
            
            $objQuestion->id = $objQuestion->id + 1;
            //log_message('info', "add_Question: New question ID will be: " . $objQuestion->id);
            
            // Log the data being inserted
            // log_message('info', "add_Question: Inserting question data - " . json_encode(array(
            //     'id' => $objQuestion->id,
            //     'exam_id' => $objQuestion->exam_id,
            //     'subject_id' => $objQuestion->subject_id,
            //     'chapter_id' => $objQuestion->chapter_id,
            //     'topic_id' => $objQuestion->topic_id,
            //     'year' => $objQuestion->year,
            //     'question_type' => $objQuestion->question_type,
            //     'language' => $objQuestion->language
            // )));
            
            $sql = "insert into question (id, question_img_url,has_multiple_answer, duration,option_count, exam_id, subject_id,chapter_id, topic_id,level, correct_option, question_text, year, question_type, language ) values (?,?,?,?,?,?,?, ?,?,?,?,?,?,?,?)";
            $statement = $pdo->prepare($sql);
            
            $inserted = $statement->execute(array(
                $objQuestion->id,
                $objQuestion->question_img_url,
                $objQuestion->has_multiple_answer,
                $objQuestion->duration,
                $objQuestion->option_count,
                $objQuestion->exam_id,
                $objQuestion->subject_id,
                $objQuestion->chapter_id,
                $objQuestion->topic_id,
                $objQuestion->level,
                $objQuestion->correct_option,
                $objQuestion->question_text,
                $objQuestion->year,
                $objQuestion->question_type,
                $objQuestion->language
            ));
            
            if ($inserted) {
                //log_message('info', "add_Question: Successfully inserted question with ID: " . $objQuestion->id);
                $statement = NULL;
                $pdo = NULL;
                return $objQuestion;
            } else {
                $errorInfo = $statement->errorInfo();
                log_message('error', "add_Question: Failed to insert question. Error: " . json_encode($errorInfo));
                $statement = NULL;
                $pdo = NULL;
                return FALSE;
            }
            
        } catch (Exception $e) {
            log_message('error', "add_Question: Exception occurred - " . $e->getMessage());
            log_message('error', "add_Question: Stack trace - " . $e->getTraceAsString());
            return FALSE;
        }
    }

    public function update_Question($objQuestion) {
        $sql = "update question set question_img_url = ?, has_multiple_answer = ?, duration = ?, option_count = ?, exam_id=?, subject_id=?, chapter_id=?, topic_id=?, level=?, correct_option=?, question_text=?, year=?, question_type=?, language=? where id = ?";
        //echo $sql;
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objQuestion->question_img_url,
            $objQuestion->has_multiple_answer,
            $objQuestion->duration,
            $objQuestion->option_count,
            $objQuestion->exam_id,
            $objQuestion->subject_id,
            $objQuestion->chapter_id,
            $objQuestion->topic_id,
            $objQuestion->level,
            $objQuestion->correct_option,
            $objQuestion->question_text,
            $objQuestion->year,
            $objQuestion->question_type,
            $objQuestion->language,
            $objQuestion->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objQuestion;
        return FALSE;
    }

    public function update_solution($objQuestion) {
        $sql = "update question set solution = ? where id = ?";
        //echo $sql;
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

    public function update_solution_latest($id, $solution) {
        $sql = "update question set solution = ? where id = ?";
        //echo $sql;
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $solution,
            $id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return TRUE;
        return FALSE;
    }

    public function delete_Question($id) {
        $sql = "delete from question where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_Question_count() {
        $count = 0;
        $sql = "select count(id) as cnt from question WHERE (invalid_question IS NULL OR invalid_question = 0)";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    /**
     * Get total count of all questions (including flagged ones) - for admin use
     * @return int - Total count including flagged questions
     */
    public function get_all_Question_count() {
        $count = 0;
        $sql = "select count(id) as cnt from question";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_Question($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        //echo "<br>IN Model sortBy = $sortBy";

        // Add filter to exclude flagged questions by default
        $flagFilter = "(invalid_question IS NULL OR invalid_question = 0)";

        if ($filterString == NULL)
            $sql = "select* from question where $flagFilter order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select* from question where $filterString AND $flagFilter order by $sortBy $sortType limit $offset, $limit";

        //$sql = "select * from question limit $offset, $limit";

       // print_r($sql);
      //  echo "<br>";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objQuestion = new Question_object();
            $objQuestion->id = $row['id'];
			$objQuestion->question_id = $row['id'];
            $objQuestion->question_img_url = $row['question_img_url'];
            $objQuestion->has_multiple_answer = $row['has_multiple_answer'];
            $objQuestion->duration = $row['duration'];
            $objQuestion->option_count = $row['option_count'];
            $objQuestion->exam_id = $row['exam_id'];
            $objQuestion->subject_id = $row['subject_id'];
            $objQuestion->chapter_id = $row['chapter_id'];
            $objQuestion->topic_id = $row['topic_id'];
            $objQuestion->level = $row['level'];
            $objQuestion->correct_option = $row['correct_option'];
            $objQuestion->solution = $row['solution'];
            $objQuestion->question_text = $row['question_text'];
            $objQuestion->year = $row['year'] ?? 2025;
            $objQuestion->question_type = $row['question_type'] ?? 'regular';
            $objQuestion->flag_reason = $row['flag_reason'] ?? null;
            $records[] = $objQuestion;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    /**
     * Flag a question as invalid and create history record
     * @param int $question_id - Question ID to flag
     * @param int $user_id - User ID who is reporting the question
     * @param string $flag_reason - Reason for flagging the question
     * @return bool - Success status
     */
    public function flag_question_as_invalid($question_id, $user_id, $flag_reason = null) {
        $pdo = CDatabase::getPdo();
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // First, update the question to mark it as invalid and store the flag reason
            $sql = "UPDATE question SET invalid_question = 1, flag_reason = ? WHERE id = ?";
            $statement = $pdo->prepare($sql);
            $updated = $statement->execute(array($flag_reason, $question_id));
            
            if (!$updated) {
                $pdo->rollback();
                return false;
            }
            
            // Then, create a history record
            $history_sql = "INSERT INTO question_status_history (user_id, question_id, status, reported_date, created_at, updated_at) 
                           VALUES (?, ?, 'reported', NOW(), NOW(), NOW())";
            $history_statement = $pdo->prepare($history_sql);
            $history_inserted = $history_statement->execute(array($user_id, $question_id));
            
            if (!$history_inserted) {
                $pdo->rollback();
                return false;
            }
            
            // Commit the transaction
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $pdo->rollback();
            return false;
        } finally {
            $statement = null;
            $history_statement = null;
            $pdo = null;
        }
    }

    /**
     * Check if a question exists
     * @param int $question_id - Question ID to check
     * @return bool - True if exists
     */
    public function question_exists($question_id) {
        $sql = "SELECT id FROM question WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($question_id));
        $exists = $statement->fetch() !== false;
        
        $statement = null;
        $pdo = null;
        return $exists;
    }

    /**
     * Check if a question is already flagged as invalid
     * @param int $question_id - Question ID to check
     * @return bool - True if already flagged
     */
    public function is_question_flagged($question_id) {
        $sql = "SELECT invalid_question FROM question WHERE id = ? AND invalid_question = 1";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($question_id));
        $flagged = $statement->fetch() !== false;
        
        $statement = null;
        $pdo = null;
        return $flagged;
    }

    /**
     * Get all flagged questions for admin review
     * @param int $offset - Offset for pagination
     * @param int $limit - Limit for pagination
     * @param string $sortBy - Column to sort by
     * @param string $sortType - Sort direction (ASC/DESC)
     * @return array - Array of flagged questions with history data
     */
    public function get_flagged_questions($offset = 0, $limit = 50, $sortBy = 'id', $sortType = 'DESC') {
        $records = array();
        
        $sql = "SELECT q.*, 
                       qsh.reported_date, qsh.status as report_status,
                       u.display_name as reporter_name, NULL as reporter_email
                FROM question q 
                LEFT JOIN question_status_history qsh ON q.id = qsh.question_id AND qsh.status = 'reported'
                LEFT JOIN user u ON qsh.user_id = u.id
                WHERE q.invalid_question = 1 
                ORDER BY $sortBy $sortType 
                LIMIT $offset, $limit";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        
        while ($row = $statement->fetch()) {
            $objQuestion = new Question_object();
            $objQuestion->id = $row['id'];
            $objQuestion->question_img_url = $row['question_img_url'];
            $objQuestion->has_multiple_answer = $row['has_multiple_answer'];
            $objQuestion->duration = $row['duration'];
            $objQuestion->option_count = $row['option_count'];
            $objQuestion->exam_id = $row['exam_id'];
            $objQuestion->subject_id = $row['subject_id'];
            $objQuestion->chapter_id = $row['chapter_id'];
            $objQuestion->topic_id = $row['topic_id'];
            $objQuestion->level = $row['level'];
            $objQuestion->correct_option = $row['correct_option'];
            $objQuestion->solution = $row['solution'];
            $objQuestion->question_text = $row['question_text'];
            $objQuestion->invalid_question = $row['invalid_question'];
            $objQuestion->year = $row['year'] ?? 2025;
            $objQuestion->question_type = $row['question_type'] ?? 'regular';
            $objQuestion->flag_reason = $row['flag_reason'] ?? null;
            
            // Add reporting information
            $objQuestion->reported_date = $row['reported_date'];
            $objQuestion->report_status = $row['report_status'];
            $objQuestion->reporter_name = $row['reporter_name'];
            $objQuestion->reporter_email = $row['reporter_email'];
            
            $records[] = $objQuestion;
        }
        
        $statement = null;
        $pdo = null;
        return $records;
    }

    /**
     * Get count of flagged questions
     * @return int - Count of flagged questions
     */
    public function get_flagged_questions_count() {
        $count = 0;
        $sql = "SELECT COUNT(id) as cnt FROM question WHERE invalid_question = 1";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        
        if ($row = $statement->fetch()) {
            $count = $row['cnt'];
        }
        
        $statement = null;
        $pdo = null;
        return $count;
    }

    /**
     * Unflag a question (mark as corrected) - for admin use
     * @param int $question_id - Question ID to unflag
     * @param int $admin_user_id - Admin user ID performing the action
     * @return bool - Success status
     */
    public function unflag_question($question_id, $admin_user_id) {
        $pdo = CDatabase::getPdo();
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Update question to mark as valid
            $sql = "UPDATE question SET invalid_question = 0 WHERE id = ?";
            $statement = $pdo->prepare($sql);
            $updated = $statement->execute(array($question_id));
            
            if (!$updated) {
                $pdo->rollback();
                return false;
            }
            
            // Update history records to mark as corrected
            $history_sql = "UPDATE question_status_history 
                           SET status = 'corrected', corrected_date = NOW(), updated_at = NOW() 
                           WHERE question_id = ? AND status = 'reported'";
            $history_statement = $pdo->prepare($history_sql);
            $history_statement->execute(array($question_id));
            
            // Commit the transaction
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $pdo->rollback();
            return false;
        } finally {
            $statement = null;
            $history_statement = null;
            $pdo = null;
        }
    }

}
?>

