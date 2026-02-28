<?php

class Wizi_quiz_user_model extends CI_Model
{

    public function get_wizi_quiz_user($id)
    {
        $obj = NULL;
        $sql = "SELECT * FROM wizi_quiz_user WHERE id = ?";
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

    public function get_user_attempt($wizi_quiz_id, $user_id, $attempt_number)
    {
        $obj = NULL;
        $sql = "SELECT * FROM wizi_quiz_user WHERE wizi_quiz_id = ? AND user_id = ? AND attempt_number = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($wizi_quiz_id, $user_id, $attempt_number));
        if ($row = $statement->fetch()) {
            $obj = $this->mapRowToObject($row);
        }
        $statement = NULL;
        $pdo = NULL;
        return $obj;
    }

    public function get_user_attempts($wizi_quiz_id, $user_id)
    {
        $records = array();
        $sql = "SELECT * FROM wizi_quiz_user WHERE wizi_quiz_id = ? AND user_id = ? ORDER BY attempt_number DESC";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($wizi_quiz_id, $user_id));
        while ($row = $statement->fetch()) {
            $records[] = $this->mapRowToObject($row);
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function get_next_attempt_number($wizi_quiz_id, $user_id)
    {
        $attempt_number = 1;
        $sql = "SELECT MAX(attempt_number) as max_attempt FROM wizi_quiz_user WHERE wizi_quiz_id = ? AND user_id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($wizi_quiz_id, $user_id));
        if (($row = $statement->fetch()) && $row['max_attempt'] !== null) {
            $attempt_number = $row['max_attempt'] + 1;
        }
        $statement = NULL;
        $pdo = NULL;
        return $attempt_number;
    }

    public function get_best_attempt($wizi_quiz_id, $user_id)
    {
        $obj = NULL;
        $sql = "SELECT * FROM wizi_quiz_user WHERE wizi_quiz_id = ? AND user_id = ? AND best_attempt = 1";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($wizi_quiz_id, $user_id));
        if ($row = $statement->fetch()) {
            $obj = $this->mapRowToObject($row);
        }
        $statement = NULL;
        $pdo = NULL;
        return $obj;
    }

    public function add_wizi_quiz_user($obj)
    {
        $pdo = CDatabase::getPdo();

        $sql = "SELECT MAX(id) as mvalue FROM wizi_quiz_user";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $obj->id = $row['mvalue'];
        else
            $obj->id = 0;
        $obj->id = $obj->id + 1;
        
        $sql = "INSERT INTO wizi_quiz_user 
                (`id`, `wizi_quiz_id`, `user_id`, `attempt_number`, `attempt_status`, `started_at`, 
                 `completed_at`, `time_spent`, `current_question_index`, `total_questions`, 
                 `answered_questions`, `correct_answers`, `incorrect_answers`, `skipped_questions`, 
                 `total_score`, `total_marks`, `accuracy_percentage`, `is_passed`, `rank`, `best_attempt`) 
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $obj->id,
            $obj->wizi_quiz_id,
            $obj->user_id,
            $obj->attempt_number,
            $obj->attempt_status,
            $obj->started_at,
            $obj->completed_at,
            $obj->time_spent,
            $obj->current_question_index,
            $obj->total_questions,
            $obj->answered_questions,
            $obj->correct_answers,
            $obj->incorrect_answers,
            $obj->skipped_questions,
            $obj->total_score,
            $obj->total_marks,
            $obj->accuracy_percentage,
            $obj->is_passed,
            $obj->rank,
            $obj->best_attempt
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $obj;
        return FALSE;
    }

    public function update_wizi_quiz_user($obj)
    {
        $sql = "UPDATE wizi_quiz_user SET 
                attempt_status = ?, started_at = ?, completed_at = ?, time_spent = ?, 
                current_question_index = ?, total_questions = ?, answered_questions = ?, 
                correct_answers = ?, incorrect_answers = ?, skipped_questions = ?, 
                total_score = ?, total_marks = ?, accuracy_percentage = ?, is_passed = ?, 
                rank = ?, best_attempt = ?
                WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $obj->attempt_status,
            $obj->started_at,
            $obj->completed_at,
            $obj->time_spent,
            $obj->current_question_index,
            $obj->total_questions,
            $obj->answered_questions,
            $obj->correct_answers,
            $obj->incorrect_answers,
            $obj->skipped_questions,
            $obj->total_score,
            $obj->total_marks,
            $obj->accuracy_percentage,
            $obj->is_passed,
            $obj->rank,
            $obj->best_attempt,
            $obj->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $obj;
        return FALSE;
    }

    public function update_best_attempt($wizi_quiz_id, $user_id, $new_best_attempt_id)
    {
        $pdo = CDatabase::getPdo();
        
        // First, reset all best_attempt flags for this user and quiz
        $sql = "UPDATE wizi_quiz_user SET best_attempt = 0 WHERE wizi_quiz_id = ? AND user_id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($wizi_quiz_id, $user_id));
        
        // Then, set the new best attempt
        $sql = "UPDATE wizi_quiz_user SET best_attempt = 1 WHERE id = ?";
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array($new_best_attempt_id));
        
        $statement = NULL;
        $pdo = NULL;
        return $updated;
    }

    public function get_leaderboard($wizi_quiz_id, $limit = 100)
    {
        $records = array();
        $sql = "SELECT wqu.*, u.display_name, u.username 
                FROM wizi_quiz_user wqu
                JOIN user u ON wqu.user_id = u.id
                WHERE wqu.wizi_quiz_id = ? 
                AND wqu.attempt_status = 'completed'
                AND wqu.best_attempt = 1
                ORDER BY wqu.total_score DESC, wqu.time_spent ASC
                LIMIT ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($wizi_quiz_id, $limit));
        $rank = 1;
        while ($row = $statement->fetch()) {
            $obj = $this->mapRowToObject($row);
            $obj->rank = $rank++;
            $obj->display_name = isset($row['display_name']) ? $row['display_name'] : '';
            $obj->username = isset($row['username']) ? $row['username'] : '';
            $records[] = $obj;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function calculate_rank($wizi_quiz_id, $user_id, $attempt_id)
    {
        $rank = 1;
        
        // Get the current attempt's score and time
        $sql = "SELECT total_score, time_spent FROM wizi_quiz_user WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($attempt_id));
        $current_attempt = $statement->fetch();
        
        if (!$current_attempt) {
            return null;
        }
        
        // Count how many attempts have better score (or same score but less time)
        $sql = "SELECT COUNT(*) as better_attempts
                FROM wizi_quiz_user 
                WHERE wizi_quiz_id = ? 
                AND attempt_status = 'completed'
                AND best_attempt = 1
                AND (total_score > ? 
                     OR (total_score = ? AND time_spent < ?))";
        $statement = $pdo->prepare($sql);
        $statement->execute(array(
            $wizi_quiz_id, 
            $current_attempt['total_score'],
            $current_attempt['total_score'],
            $current_attempt['time_spent']
        ));
        
        if ($row = $statement->fetch()) {
            $rank = (int)$row['better_attempts'] + 1;
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $rank;
    }

    public function has_user_attempted_quiz($wizi_quiz_id, $user_id)
    {
        $sql = "SELECT COUNT(*) as attempt_count 
                FROM wizi_quiz_user 
                WHERE wizi_quiz_id = ? 
                AND user_id = ? 
                AND attempt_status = 'completed'";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($wizi_quiz_id, $user_id));
        $row = $statement->fetch();
        $statement = NULL;
        $pdo = NULL;
        
        return ($row && $row['attempt_count'] > 0);
    }

    public function has_user_attempted_quiz_order($quiz_order, $language, $user_id)
    {
        $sql = "SELECT COUNT(*) as attempt_count 
                FROM wizi_quiz_user wqu
                INNER JOIN wizi_quiz wq ON wqu.wizi_quiz_id = wq.id
                WHERE wq.quiz_order = ? 
                AND wq.language = ?
                AND wqu.user_id = ? 
                AND wqu.attempt_status = 'completed'";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($quiz_order, $language, $user_id));
        $row = $statement->fetch();
        $statement = NULL;
        $pdo = NULL;
        
        return ($row && $row['attempt_count'] > 0);
    }

    /**
     * Get quiz attempts with filters for admin panel
     * Includes quiz name and user details via joins
     */
    public function get_wizi_quiz_users_with_filters($whereClause = '', $params = [], $sortField = 'wqu.id', $sortOrder = 'DESC', $limit = 25, $offset = 0)
    {
        $records = array();
        
        // Ensure limit and offset are integers
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        $sql = "SELECT 
                    wqu.*,
                    wq.name as quiz_name,
                    wq.level as quiz_level,
                    u.display_name as user_name,
                    u.username as user_email,
                    u.username as username
                FROM wizi_quiz_user wqu
                LEFT JOIN wizi_quiz wq ON wqu.wizi_quiz_id = wq.id
                LEFT JOIN user u ON wqu.user_id = u.id
                $whereClause
                ORDER BY $sortField $sortOrder
                LIMIT $limit OFFSET $offset";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        
        // Execute with only the filter params (no limit/offset params)
        $statement->execute($params);
        
        while ($row = $statement->fetch()) {
            $obj = $this->mapRowToObject($row);
            // Add joined fields
            $obj->quiz_name = isset($row['quiz_name']) ? $row['quiz_name'] : '';
            $obj->quiz_level = isset($row['quiz_level']) ? $row['quiz_level'] : '';
            $obj->user_name = isset($row['user_name']) ? $row['user_name'] : '';
            $obj->user_email = isset($row['user_email']) ? $row['user_email'] : '';
            $obj->username = isset($row['username']) ? $row['username'] : '';
            $records[] = $obj;
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    /**
     * Get count of quiz attempts with filters
     */
    public function get_wizi_quiz_user_count($whereClause = '', $params = [])
    {
        $count = 0;
        
        $sql = "SELECT COUNT(*) as cnt
                FROM wizi_quiz_user wqu
                LEFT JOIN wizi_quiz wq ON wqu.wizi_quiz_id = wq.id
                LEFT JOIN user u ON wqu.user_id = u.id
                $whereClause";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        
        if ($row = $statement->fetch()) {
            $count = (int)$row['cnt'];
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    /**
     * Get single quiz attempt with details (quiz name, user info)
     */
    public function get_wizi_quiz_user_with_details($id)
    {
        $obj = NULL;
        
        $sql = "SELECT 
                    wqu.*,
                    wq.name as quiz_name,
                    wq.level as quiz_level,
                    wq.total_marks as quiz_total_marks,
                    u.display_name as user_name,
                    u.email as user_email,
                    u.username as username
                FROM wizi_quiz_user wqu
                LEFT JOIN wizi_quiz wq ON wqu.wizi_quiz_id = wq.id
                LEFT JOIN user u ON wqu.user_id = u.id
                WHERE wqu.id = ?";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        
        if ($row = $statement->fetch()) {
            $obj = $this->mapRowToObject($row);
            $obj->quiz_name = isset($row['quiz_name']) ? $row['quiz_name'] : '';
            $obj->quiz_level = isset($row['quiz_level']) ? $row['quiz_level'] : '';
            $obj->quiz_total_marks = isset($row['quiz_total_marks']) ? $row['quiz_total_marks'] : 0;
            $obj->user_name = isset($row['user_name']) ? $row['user_name'] : '';
            $obj->user_email = isset($row['user_email']) ? $row['user_email'] : '';
            $obj->username = isset($row['username']) ? $row['username'] : '';
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $obj;
    }

    private function mapRowToObject($row)
    {
        $obj = new Wizi_quiz_user_object();
        $obj->id = $row['id'];
        $obj->wizi_quiz_id = $row['wizi_quiz_id'];
        $obj->user_id = $row['user_id'];
        $obj->attempt_number = isset($row['attempt_number']) ? $row['attempt_number'] : 1;
        $obj->attempt_status = isset($row['attempt_status']) ? $row['attempt_status'] : 'not_started';
        $obj->started_at = isset($row['started_at']) ? $row['started_at'] : null;
        $obj->completed_at = isset($row['completed_at']) ? $row['completed_at'] : null;
        $obj->time_spent = isset($row['time_spent']) ? $row['time_spent'] : 0;
        $obj->current_question_index = isset($row['current_question_index']) ? $row['current_question_index'] : 0;
        $obj->total_questions = isset($row['total_questions']) ? $row['total_questions'] : 0;
        $obj->answered_questions = isset($row['answered_questions']) ? $row['answered_questions'] : 0;
        $obj->correct_answers = isset($row['correct_answers']) ? $row['correct_answers'] : 0;
        $obj->incorrect_answers = isset($row['incorrect_answers']) ? $row['incorrect_answers'] : 0;
        $obj->skipped_questions = isset($row['skipped_questions']) ? $row['skipped_questions'] : 0;
        $obj->total_score = isset($row['total_score']) ? $row['total_score'] : 0;
        $obj->total_marks = isset($row['total_marks']) ? $row['total_marks'] : 0;
        $obj->accuracy_percentage = isset($row['accuracy_percentage']) ? $row['accuracy_percentage'] : 0.00;
        $obj->is_passed = isset($row['is_passed']) ? $row['is_passed'] : null;
        $obj->rank = isset($row['rank']) ? $row['rank'] : null;
        $obj->best_attempt = isset($row['best_attempt']) ? $row['best_attempt'] : 0;
        $obj->created_at = isset($row['created_at']) ? $row['created_at'] : null;
        $obj->updated_at = isset($row['updated_at']) ? $row['updated_at'] : null;
        return $obj;
    }

}
