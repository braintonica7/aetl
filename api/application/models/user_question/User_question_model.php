<?php

class User_question_model extends CI_Model {

    public function get_User_question($id) {
        $objUserQuestion = NULL;
        $sql = "select * from user_question where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objUserQuestion = new User_question_object();
            $objUserQuestion->id = $row['id'];
            $objUserQuestion->user_id = $row['user_id'];
            $objUserQuestion->quiz_id = $row['quiz_id'];
            $objUserQuestion->quiz_question_id = $row['quiz_question_id'];
            $objUserQuestion->question_id = $row['question_id'];
            $objUserQuestion->duration = $row['duration'];
            $objUserQuestion->option_answer = $row['option_answer'];
            $objUserQuestion->status = $row['status'];
            $objUserQuestion->score = $row['score'];
            $objUserQuestion->is_correct = $row['is_correct'];
            $objUserQuestion->created_at = $row['created_at'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objUserQuestion;
    }

    public function get_all_User_questions() {
        $records = array();
        $sql = "select * from user_question";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objUserQuestion = new User_question_object();
            $objUserQuestion->id = $row['id'];
            $objUserQuestion->user_id = $row['user_id'];
            $objUserQuestion->quiz_id = $row['quiz_id'];
            $objUserQuestion->quiz_question_id = $row['quiz_question_id'];
            $objUserQuestion->question_id = $row['question_id'];
            $objUserQuestion->duration = $row['duration'];
            $objUserQuestion->option_answer = $row['option_answer'];
            $objUserQuestion->status = $row['status'];
            $objUserQuestion->score = $row['score'];
            $objUserQuestion->is_correct = $row['is_correct'];
            $objUserQuestion->created_at = $row['created_at'];
            $records[] = $objUserQuestion;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_User_question($objUserQuestion) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from user_question";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objUserQuestion->id = $row['mvalue'];
        else
            $objUserQuestion->id = 0;
        $objUserQuestion->id = $objUserQuestion->id + 1;

        $sql = "insert into user_question (id, user_id, quiz_id, quiz_question_id, question_id, duration, option_answer, score, is_correct) values (?,?,?,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objUserQuestion->id,
            $objUserQuestion->user_id,
            $objUserQuestion->quiz_id,
            $objUserQuestion->quiz_question_id,
            $objUserQuestion->question_id,
            $objUserQuestion->duration,
            $objUserQuestion->option_answer,
            $objUserQuestion->score,
            $objUserQuestion->is_correct
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objUserQuestion;
        return FALSE;
    }

    public function submit_answer($userId, $quizId, $quizQuestionId, $userAnswer, $duration = 0) {
        $pdo = CDatabase::getPdo();
        
        // Get the correct answer using JOIN between quiz_question and question tables
        // questionId is quiz_question.id, so we need to join to get the actual question's correct_option
        $sql = "SELECT q.correct_option, qq.question_id 
                FROM quiz_question qq 
                JOIN question q ON qq.question_id = q.id 
                WHERE qq.id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($quizQuestionId));
        $correctOption = null;
        $actualQuestionId = null;
        if ($row = $statement->fetch()) {
            $correctOption = $row['correct_option'];
            $actualQuestionId = $row['question_id'];
        }
        
        if ($correctOption === null) {
            $statement = NULL;
            $pdo = NULL;
            return FALSE; // Quiz question or question not found
        }
        
        // Determine if answer is correct
        $isCorrect = ($userAnswer == $correctOption) ? 1 : 0;
        
        // Calculate score (you can modify this logic as needed)
        $score = $isCorrect ? 4 : -1; // Simple scoring: 4 point for correct, -1 for incorrect

        // Check if user has already answered this quiz question (using quiz_question_id)
        $sql = "SELECT id FROM user_question WHERE user_id = ? AND quiz_id = ? AND quiz_question_id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($userId, $quizId, $quizQuestionId));
        
        if ($row = $statement->fetch()) {
            // Update existing answer (e.g., when changing from skipped to answered)
            $sql = "UPDATE user_question SET option_answer = ?, is_correct = ?, score = ?, duration = ?, status = 'answered', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $statement = $pdo->prepare($sql);
            $updated = $statement->execute(array(
                $userAnswer,
                $isCorrect,
                $score,
                $duration,
                $row['id']
            ));
            $statement = NULL;
            $pdo = NULL;
            return $updated ? $row['id'] : FALSE;
        } else {
            // Insert new answer (storing both quiz_question_id and actual question_id)
            $sql = "SELECT max(id) as mvalue FROM user_question";
            $statement = $pdo->prepare($sql);
            $statement->execute();
            $newId = 1;
            if ($row = $statement->fetch()) {
                $newId = $row['mvalue'] + 1;
            }
            
            $sql = "INSERT INTO user_question (id, user_id, quiz_id, quiz_question_id, question_id, duration, option_answer, score, is_correct, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),NOW())";
            $statement = $pdo->prepare($sql);
            $inserted = $statement->execute(array(
                $newId,
                $userId,
                $quizId,
                $quizQuestionId,  // Store the quiz_question.id
                $actualQuestionId,  // Store the actual question_id
                $duration,
                $userAnswer,
                $score,
                $isCorrect,
                'answered'  // Status is 'answered' for submitted answers
            ));
            $statement = NULL;
            $pdo = NULL;
            return $inserted ? $newId : FALSE;
        }
    }

    public function update_User_question($objUserQuestion) {
        $sql = "update user_question set user_id = ?, quiz_id = ?, quiz_question_id = ?, question_id = ?, duration = ?, option_answer = ?, score = ?, is_correct = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objUserQuestion->user_id,
            $objUserQuestion->quiz_id,
            $objUserQuestion->quiz_question_id,
            $objUserQuestion->question_id,
            $objUserQuestion->duration,
            $objUserQuestion->option_answer,
            $objUserQuestion->score,
            $objUserQuestion->is_correct,
            $objUserQuestion->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objUserQuestion;
        return FALSE;
    }

    public function delete_User_question($id) {
        $sql = "delete from user_question where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_User_question_count() {
        $count = 0;
        $sql = "select count(id) as cnt from user_question";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_user_quiz_results($userId, $quizId) {
        $records = array();
        $sql = "SELECT uq.*, q.question_text, q.correct_option 
                FROM user_question uq 
                JOIN question q ON uq.question_id = q.id 
                WHERE uq.user_id = ? AND uq.quiz_id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($userId, $quizId));
        while ($row = $statement->fetch()) {
            $objUserQuestion = new User_question_object();
            $objUserQuestion->id = $row['id'];
            $objUserQuestion->user_id = $row['user_id'];
            $objUserQuestion->quiz_id = $row['quiz_id'];
            $objUserQuestion->quiz_question_id = $row['quiz_question_id'];
            $objUserQuestion->question_id = $row['question_id'];
            $objUserQuestion->duration = $row['duration'];
            $objUserQuestion->option_answer = $row['option_answer'];
            $objUserQuestion->status = $row['status'];
            $objUserQuestion->score = $row['score'];
            $objUserQuestion->is_correct = $row['is_correct'];
            $objUserQuestion->created_at = $row['created_at'];
            // Add additional fields for convenience
            $objUserQuestion->question_text = $row['question_text'];
            $objUserQuestion->correct_option = $row['correct_option'];
            $records[] = $objUserQuestion;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }
    
    /**
     * Check if user has completed all questions for a quiz
     */
    public function is_quiz_completed($user_id, $quiz_id) {
        // Get total number of questions in the quiz
        $this->db->select('COUNT(*) as total_questions');
        $this->db->from('question');
        $this->db->where('quiz_id', $quiz_id);
        $total_questions_query = $this->db->get();
        $total_questions = $total_questions_query->row()->total_questions;
        
        // Get number of questions answered by user
        $this->db->select('COUNT(*) as answered_questions');
        $this->db->from('user_question');
        $this->db->where('user_id', $user_id);
        $this->db->where('quiz_id', $quiz_id);
        $answered_questions_query = $this->db->get();
        $answered_questions = $answered_questions_query->row()->answered_questions;
        
        return ($answered_questions >= $total_questions && $total_questions > 0);
    }
    
    /**
     * Get user's quiz completion statistics
     */
    public function get_quiz_completion_stats($user_id, $quiz_id) {
        // Get total number of questions in the quiz
        $this->db->select('COUNT(*) as total_questions');
        $this->db->from('question');
        $this->db->where('quiz_id', $quiz_id);
        $total_questions_query = $this->db->get();
        $total_questions = $total_questions_query->row()->total_questions;
        
        // Get number of questions answered by user
        $this->db->select('COUNT(*) as answered_questions');
        $this->db->from('user_question');
        $this->db->where('user_id', $user_id);
        $this->db->where('quiz_id', $quiz_id);
        $answered_questions_query = $this->db->get();
        $answered_questions = $answered_questions_query->row()->answered_questions;
        
        return array(
            'total_questions' => $total_questions,
            'answered_questions' => $answered_questions,
            'completed' => ($answered_questions >= $total_questions && $total_questions > 0),
            'progress_percentage' => $total_questions > 0 ? round(($answered_questions / $total_questions) * 100, 2) : 0
        );
    }

    /**
     * Insert a skip entry for a question
     */
    public function insert_skip_entry($user_id, $quiz_id, $quiz_question_id, $question_id, $duration = 0) {
        $pdo = CDatabase::getPdo();
        
        try {
            $sql = "INSERT INTO user_question 
                    (user_id, quiz_id, quiz_question_id, question_id, option_answer, 
                     duration, score, is_correct, status, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, NULL, ?, 0, 0, 'skipped', NOW(), NOW())";
            
            $statement = $pdo->prepare($sql);
            $result = $statement->execute(array($user_id, $quiz_id, $quiz_question_id, $question_id, $duration));
            
            if ($result) {
                $id = $pdo->lastInsertId();
                $statement = NULL;
                $pdo = NULL;
                return $id;
            }
            
            $statement = NULL;
            $pdo = NULL;
            return FALSE;
            
        } catch (Exception $e) {
            log_message('error', "Error inserting skip entry: " . $e->getMessage());
            $statement = NULL;
            $pdo = NULL;
            return FALSE;
        }
    }

    /**
     * Get user question by quiz_question_id
     */
    public function get_user_question_by_quiz_question($user_id, $quiz_question_id) {
        $pdo = CDatabase::getPdo();
        
        $sql = "SELECT * FROM user_question WHERE user_id = ? AND quiz_question_id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id, $quiz_question_id));
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        
        $statement = NULL;
        $pdo = NULL;
        
        return $result;
    }

}

?>
