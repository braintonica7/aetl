<?php

class Wizi_quiz_question_model extends CI_Model
{

    public function get_wizi_quiz_question($id)
    {
        $obj = NULL;
        $sql = "SELECT * FROM wizi_quiz_question WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $obj = $this->mapRowToObject($row);
        }
        $statement = NULL;
        $pdo = NULL;
        return $obj;
    }

    public function get_questions_by_quiz($wizi_quiz_id)
    {
        $records = array();
        $sql = "SELECT wqq.*, wq.* 
                FROM wizi_quiz_question wqq
                JOIN wizi_question wq ON wqq.wizi_question_id = wq.id
                WHERE wqq.wizi_quiz_id = ?
                ORDER BY wqq.question_order ASC";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($wizi_quiz_id));
        while ($row = $statement->fetch()) {
            $obj = $this->mapRowToObject($row);
            // Add question details
            $obj->question_img_url = isset($row['question_img_url']) ? $row['question_img_url'] : '';
            $obj->question_text = isset($row['question_text']) ? $row['question_text'] : '';
            $obj->correct_option = isset($row['correct_option']) ? $row['correct_option'] : '';
            $obj->option_count = isset($row['option_count']) ? $row['option_count'] : 4;
            $obj->duration = isset($row['duration']) ? $row['duration'] : 0;
            $records[] = $obj;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_wizi_quiz_question($obj)
    {
        $pdo = CDatabase::getPdo();

        $sql = "SELECT MAX(id) as mvalue FROM wizi_quiz_question";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $obj->id = $row['mvalue'];
        else
            $obj->id = 0;
        $obj->id = $obj->id + 1;
        
        $sql = "INSERT INTO wizi_quiz_question 
                (`id`, `wizi_quiz_id`, `wizi_question_id`, `question_order`, `marks`, `negative_marks`, `question_type`) 
                VALUES (?,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $obj->id,
            $obj->wizi_quiz_id,
            $obj->wizi_question_id,
            $obj->question_order,
            $obj->marks,
            $obj->negative_marks,
            isset($obj->question_type) ? $obj->question_type : 'mcq'
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $obj;
        return FALSE;
    }

    public function update_wizi_quiz_question($obj)
    {
        $sql = "UPDATE wizi_quiz_question SET 
                wizi_quiz_id = ?, wizi_question_id = ?, question_order = ?, marks = ?, negative_marks = ?, question_type = ?
                WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $obj->wizi_quiz_id,
            $obj->wizi_question_id,
            $obj->question_order,
            $obj->marks,
            $obj->negative_marks,
            isset($obj->question_type) ? $obj->question_type : 'mcq',
            $obj->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $obj;
        return FALSE;
    }

    public function delete_wizi_quiz_question($id)
    {
        $sql = "DELETE FROM wizi_quiz_question WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function delete_questions_by_quiz($wizi_quiz_id)
    {
        $sql = "DELETE FROM wizi_quiz_question WHERE wizi_quiz_id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($wizi_quiz_id));
        $statement = NULL;
        $pdo = NULL;
    }

    /**
     * Bulk add multiple questions to a quiz
     * Returns array with success status, added count, skipped count, and details
     */
    public function bulk_add_wizi_quiz_questions($wizi_quiz_id, $question_ids, $marks = 4, $negative_marks = -1.0)
    {
        $pdo = CDatabase::getPdo();
        
        // Get current max order for the quiz
        $sql = "SELECT MAX(question_order) as max_order FROM wizi_quiz_question WHERE wizi_quiz_id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($wizi_quiz_id));
        $row = $statement->fetch();
        $current_order = isset($row['max_order']) && $row['max_order'] !== null ? $row['max_order'] : 0;
        $statement = NULL;
        
        $added_count = 0;
        $skipped_count = 0;
        $added_ids = array();
        $skipped_ids = array();
        
        // Sort question IDs in ascending order
        sort($question_ids, SORT_NUMERIC);
        
        // Process each question
        foreach ($question_ids as $question_id) {
            $current_order++;
            
            // Check if already exists (to handle duplicates gracefully)
            $checkSql = "SELECT id FROM wizi_quiz_question WHERE wizi_quiz_id = ? AND wizi_question_id = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute(array($wizi_quiz_id, $question_id));
            
            if ($checkStmt->fetch()) {
                // Already exists, skip
                $skipped_count++;
                $skipped_ids[] = $question_id;
                $checkStmt = NULL;
                continue;
            }
            $checkStmt = NULL;
            
            // Get next ID
            $sql = "SELECT MAX(id) as mvalue FROM wizi_quiz_question";
            $statement = $pdo->prepare($sql);
            $statement->execute();
            if ($row = $statement->fetch())
                $next_id = $row['mvalue'];
            else
                $next_id = 0;
            $next_id = $next_id + 1;
            $statement = NULL;
            
            // Get question_type from wizi_question table
            $questionTypeSql = "SELECT question_type FROM wizi_question WHERE id = ?";
            $questionTypeStmt = $pdo->prepare($questionTypeSql);
            $questionTypeStmt->execute(array($question_id));
            $questionTypeRow = $questionTypeStmt->fetch();
            $question_type = isset($questionTypeRow['question_type']) ? $questionTypeRow['question_type'] : 'mcq';
            $questionTypeStmt = NULL;
            
            // Insert the question
            $insertSql = "INSERT INTO wizi_quiz_question 
                         (`id`, `wizi_quiz_id`, `wizi_question_id`, `question_order`, `marks`, `negative_marks`, `question_type`) 
                         VALUES (?,?,?,?,?,?,?)";
            $insertStmt = $pdo->prepare($insertSql);
            $inserted = $insertStmt->execute(array(
                $next_id,
                $wizi_quiz_id,
                $question_id,
                $current_order,
                $marks,
                $negative_marks,
                $question_type
            ));
            $insertStmt = NULL;
            
            if ($inserted) {
                $added_count++;
                $added_ids[] = $question_id;
            } else {
                $skipped_count++;
                $skipped_ids[] = $question_id;
            }
        }
        
        $pdo = NULL;
        
        return array(
            'success' => $added_count > 0,
            'added_count' => $added_count,
            'skipped_count' => $skipped_count,
            'total_requested' => count($question_ids),
            'added_ids' => $added_ids,
            'skipped_ids' => $skipped_ids,
            'message' => "Added {$added_count} questions. Skipped {$skipped_count} (already in quiz or error)."
        );
    }

    public function get_question_count_by_quiz($wizi_quiz_id)
    {
        $count = 0;
        $sql = "SELECT COUNT(id) as cnt FROM wizi_quiz_question WHERE wizi_quiz_id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($wizi_quiz_id));
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    private function mapRowToObject($row)
    {
        $obj = new Wizi_quiz_question_object();
        $obj->id = $row['id'];
        $obj->wizi_quiz_id = $row['wizi_quiz_id'];
        $obj->wizi_question_id = $row['wizi_question_id'];
        $obj->question_order = isset($row['question_order']) ? $row['question_order'] : 0;
        $obj->marks = isset($row['marks']) ? $row['marks'] : 4;
        $obj->negative_marks = isset($row['negative_marks']) ? $row['negative_marks'] : -1.0;
        $obj->question_type = isset($row['question_type']) ? $row['question_type'] : 'mcq';
        $obj->created_at = isset($row['created_at']) ? $row['created_at'] : null;
        return $obj;
    }

}
