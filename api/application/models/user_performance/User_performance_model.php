<?php

class User_performance_model extends CI_Model {

    public function get_user_performance($id) {
        $objUserPerformance = NULL;
        $sql = "select * from user_performance where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objUserPerformance = $this->map_row_to_object($row);
        }
        $statement = NULL;
        $pdo = NULL;
        return $objUserPerformance;
    }

    public function get_user_quiz_performance($userId, $quizId) {
        $objUserPerformance = NULL;
        $sql = "select * from user_performance where user_id = ? and quiz_id = ? order by quiz_attempt_number desc limit 1";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($userId, $quizId));
        if ($row = $statement->fetch()) {
            $objUserPerformance = $this->map_row_to_object($row);
        }
        $statement = NULL;
        $pdo = NULL;
        return $objUserPerformance;
    }

    public function get_user_performance_history($userId, $limit = 10) {
        $records = array();
        $sql = "select * from user_performance where user_id = ? order by created_at desc limit ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($userId, $limit));
        while ($row = $statement->fetch()) {
            $records[] = $this->map_row_to_object($row);
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_user_performance($objUserPerformance) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from user_performance";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objUserPerformance->id = $row['mvalue'];
        else
            $objUserPerformance->id = 0;
        $objUserPerformance->id = $objUserPerformance->id + 1;

        $sql = "insert into user_performance (
                    id, user_id, quiz_id, quiz_attempt_number,
                    total_questions, correct_answers, incorrect_answers, unanswered_questions,
                    accuracy_percentage, total_time_spent, average_time_per_question,
                    strongest_subject, weakest_subject, subject_scores,
                    strongest_topic, weakest_topic, topic_scores,
                    previous_quiz_accuracy, accuracy_improvement, previous_avg_time, time_improvement,
                    overall_progress_trend, subject_progress,
                    time_management_score, difficulty_performance, performance_score,
                    ai_recommendations, ai_learning_suggestions, progress_insights, 
                    improvement_areas, strengths_identified, raw_ai_response, analysis_version,
                    ai_prompt, created_at, updated_at
                ) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objUserPerformance->id,
            $objUserPerformance->user_id,
            $objUserPerformance->quiz_id,
            $objUserPerformance->quiz_attempt_number,
            $objUserPerformance->total_questions,
            $objUserPerformance->correct_answers,
            $objUserPerformance->incorrect_answers,
            $objUserPerformance->unanswered_questions,
            $objUserPerformance->accuracy_percentage,
            $objUserPerformance->total_time_spent,
            $objUserPerformance->average_time_per_question,
            $objUserPerformance->strongest_subject,
            $objUserPerformance->weakest_subject,
            $objUserPerformance->subject_scores,
            $objUserPerformance->strongest_topic,
            $objUserPerformance->weakest_topic,
            $objUserPerformance->topic_scores,
            $objUserPerformance->previous_quiz_accuracy,
            $objUserPerformance->accuracy_improvement,
            $objUserPerformance->previous_avg_time,
            $objUserPerformance->time_improvement,
            $objUserPerformance->overall_progress_trend,
            $objUserPerformance->subject_progress,
            $objUserPerformance->time_management_score,
            $objUserPerformance->difficulty_performance,
            $objUserPerformance->performance_score,
            $objUserPerformance->ai_recommendations,
            $objUserPerformance->ai_learning_suggestions,
            $objUserPerformance->progress_insights,
            $objUserPerformance->improvement_areas,
            $objUserPerformance->strengths_identified,
            $objUserPerformance->raw_ai_response,
            $objUserPerformance->analysis_version,
            $objUserPerformance->ai_prompt,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s')
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objUserPerformance;
        return FALSE;
    }

    /**
     * Generate performance analysis data for AI processing
     */
    public function get_quiz_analysis_data($userId, $quizId) {
        $pdo = CDatabase::getPdo();
        
        // Get current quiz performance - Enhanced to handle skipped questions with status tracking
        $sql_current = "SELECT 
                            q.name as quiz_name,
                            q.description as quiz_description,
                            s.subject as subject_name,
                            e.exam_name,
                            COUNT(uq.id) as total_recorded,
                            SUM(CASE WHEN uq.status = 'answered' THEN 1 ELSE 0 END) as total_answered,
                            SUM(CASE WHEN uq.status = 'skipped' THEN 1 ELSE 0 END) as total_skipped,
                            SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                            SUM(CASE WHEN uq.is_correct = 0 AND uq.status = 'answered' THEN 1 ELSE 0 END) as incorrect_answers,
                            AVG(CASE WHEN uq.status = 'answered' THEN uq.duration ELSE NULL END) as avg_time_per_answered_question,
                            SUM(uq.duration) as total_time,
                            (SELECT COUNT(*) FROM quiz_question qq WHERE qq.quiz_id = ?) as total_questions
                        FROM user_question uq
                        JOIN quiz q ON uq.quiz_id = q.id
                        LEFT JOIN subject s ON q.subject_id = s.id
                        LEFT JOIN exam e ON q.exam_id = e.id
                        WHERE uq.user_id = ? AND uq.quiz_id = ?";
        
        $statement = $pdo->prepare($sql_current);
        $statement->execute(array($quizId, $userId, $quizId));
        $current_performance = $statement->fetch(PDO::FETCH_ASSOC);
        
        // Calculate enhanced performance metrics
        if ($current_performance) {
            $total_questions = (int)$current_performance['total_questions'];
            $answered_questions = (int)$current_performance['total_answered'];
            $skipped_questions = (int)$current_performance['total_skipped'];
            $correct_answers = (int)$current_performance['correct_answers'];
            
            // Map avg_time_per_answered_question to avg_time_per_question for consistency
            if (isset($current_performance['avg_time_per_answered_question'])) {
                $current_performance['avg_time_per_question'] = $current_performance['avg_time_per_answered_question'];
            }
            
            // Completion percentage (includes skipped)
            $current_performance['completion_percentage'] = $total_questions > 0 ? 
                round((((int)$current_performance['total_recorded']) / $total_questions) * 100, 2) : 0;
            
            // Accuracy percentage (only for answered questions)
            $current_performance['accuracy_percentage'] = $answered_questions > 0 ? 
                round(($correct_answers / $answered_questions) * 100, 2) : 0;
            
            // Overall score percentage (includes skipped as 0)
            $current_performance['overall_score_percentage'] = $total_questions > 0 ? 
                round(($correct_answers / $total_questions) * 100, 2) : 0;
            
            // Skip rate
            $current_performance['skip_rate'] = $total_questions > 0 ? 
                round(($skipped_questions / $total_questions) * 100, 2) : 0;
                
            // Unanswered questions (should be 0 with this new approach)
            $current_performance['unanswered_questions'] = max(0, $total_questions - (int)$current_performance['total_recorded']);
            
            // Add user_id and quiz_id for reference
            $current_performance['user_id'] = $userId;
            $current_performance['quiz_id'] = $quizId;
        }
        
        // Get question-wise details - Updated to include status information
        $sql_questions = "SELECT 
                            q.id as question_id,
                            qq.id as quiz_question_id,
                            q.question_text,
                            q.ai_summary,
                            q.level as difficulty,
                            q.correct_option,
                            s.subject as subject_name,
                            c.chapter_name,
                            t.topic_name,
                            uq.option_answer as user_answer,
                            uq.is_correct,
                            uq.duration as time_taken,
                            COALESCE(uq.status, 'missing') as answer_status
                          FROM quiz_question qq
                          JOIN question q ON qq.question_id = q.id
                          LEFT JOIN user_question uq ON qq.id = uq.quiz_question_id AND uq.user_id = ?
                          LEFT JOIN subject s ON q.subject_id = s.id
                          LEFT JOIN chapter c ON q.chapter_id = c.id
                          LEFT JOIN topic t ON q.topic_id = t.id
                          WHERE qq.quiz_id = ?
                          ORDER BY qq.question_order ASC";
        
        $statement = $pdo->prepare($sql_questions);
        $statement->execute(array($userId, $quizId));
        $questions_analysis = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        // Get user's historical performance for comparison
        $sql_history = "SELECT 
                            up.accuracy_percentage,
                            up.average_time_per_question,
                            up.strongest_subject,
                            up.weakest_subject,
                            up.created_at,
                            q.subject_id,
                            s.subject as subject_name
                        FROM user_performance up
                        JOIN quiz q ON up.quiz_id = q.id
                        LEFT JOIN subject s ON q.subject_id = s.id
                        WHERE up.user_id = ? AND up.quiz_id != ?
                        ORDER BY up.created_at DESC
                        LIMIT 5";
        
        $statement = $pdo->prepare($sql_history);
        $statement->execute(array($userId, $quizId));
        $historical_performance = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        $statement = NULL;
        $pdo = NULL;
        
        return array(
            'current_performance' => $current_performance,
            'questions_analysis' => $questions_analysis,
            'historical_performance' => $historical_performance
        );
    }

    /**
     * Check if user has completed the quiz (simple count-based logic)
     * Now works with skipped questions properly tracked in user_question table
     */
    public function is_quiz_completed($userId, $quizId) {
        $pdo = CDatabase::getPdo();
        
        // First check if quiz table has status (new quiz status feature)
        $status_sql = "SELECT quiz_status FROM quiz WHERE id = ? AND user_id = ?";
        $statement = $pdo->prepare($status_sql);
        $statement->execute(array($quizId, $userId));
        $status_result = $statement->fetch(PDO::FETCH_ASSOC);
        $statement = null;
        
        // If quiz_status exists and is set to completed, use that
        if ($status_result && isset($status_result['quiz_status'])) {
            $is_completed = ($status_result['quiz_status'] === 'completed');
            log_message('info', "is_quiz_completed: Using quiz_status for quiz {$quizId}: " . ($is_completed ? 'true' : 'false'));
            return $is_completed;
        }
        
        // Fallback: Calculate completion based on question counts (backward compatibility)
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM quiz_question WHERE quiz_id = ?) as total_questions,
                    (SELECT COUNT(*) FROM user_question WHERE user_id = ? AND quiz_id = ?) as recorded_questions";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($quizId, $userId, $quizId));
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        
        $statement = NULL;
        $pdo = NULL;
        
        $is_completed = ($result && $result['total_questions'] > 0 && 
                $result['total_questions'] == $result['recorded_questions']);
        
        log_message('info', "is_quiz_completed: Using fallback calculation for quiz {$quizId}: " . ($is_completed ? 'true' : 'false'));
        
        return $is_completed;
    }

    /**
     * Ensure all quiz questions have entries in user_question table
     * Insert skipped entries for any missing questions
     */
    public function ensure_complete_quiz_data($user_id, $quiz_id) {
        $pdo = CDatabase::getPdo();
        
        try {
            // Get all quiz questions that don't have user_question entries
            $sql = "SELECT qq.id as quiz_question_id, qq.question_id, qq.question_order
                    FROM quiz_question qq
                    LEFT JOIN user_question uq ON qq.id = uq.quiz_question_id 
                                                AND uq.user_id = ? 
                                                AND uq.quiz_id = ?
                    WHERE qq.quiz_id = ? AND uq.id IS NULL
                    ORDER BY qq.question_order";
            
            $statement = $pdo->prepare($sql);
            $statement->execute(array($user_id, $quiz_id, $quiz_id));
            $missing_questions = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            $inserted_count = 0;
            
            // Insert skipped entries for missing questions
            foreach ($missing_questions as $question) {
                $insert_sql = "INSERT INTO user_question 
                              (user_id, quiz_id, quiz_question_id, question_id, option_answer, 
                               duration, score, is_correct, status, created_at, updated_at) 
                              VALUES (?, ?, ?, ?, NULL, 0, 0, 0, 'skipped', NOW(), NOW())";
                
                $insert_statement = $pdo->prepare($insert_sql);
                if ($insert_statement->execute(array(
                    $user_id, 
                    $quiz_id, 
                    $question['quiz_question_id'], 
                    $question['question_id']
                ))) {
                    $inserted_count++;
                }
            }
            
            $statement = NULL;
            $pdo = NULL;
            
            if ($inserted_count > 0) {
                log_message('info', "Inserted $inserted_count skipped question entries for user $user_id, quiz $quiz_id");
            }
            
            return $inserted_count;
            
        } catch (Exception $e) {
            log_message('error', "Error ensuring complete quiz data: " . $e->getMessage());
            $statement = NULL;
            $pdo = NULL;
            return false;
        }
    }

    /**
     * Get next attempt number for user-quiz combination
     */
    public function get_next_attempt_number($userId, $quizId) {
        $pdo = CDatabase::getPdo();
        
        $sql = "SELECT COALESCE(MAX(quiz_attempt_number), 0) + 1 as next_attempt 
                FROM user_performance 
                WHERE user_id = ? AND quiz_id = ?";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($userId, $quizId));
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        
        $statement = NULL;
        $pdo = NULL;
        
        return $result ? (int)$result['next_attempt'] : 1;
    }

    private function map_row_to_object($row) {
        $objUserPerformance = new User_performance_object();
        $objUserPerformance->id = $row['id'];
        $objUserPerformance->user_id = $row['user_id'];
        $objUserPerformance->quiz_id = $row['quiz_id'];
        $objUserPerformance->quiz_attempt_number = $row['quiz_attempt_number'];
        $objUserPerformance->total_questions = $row['total_questions'];
        $objUserPerformance->correct_answers = $row['correct_answers'];
        $objUserPerformance->incorrect_answers = $row['incorrect_answers'];
        $objUserPerformance->unanswered_questions = $row['unanswered_questions'];
        $objUserPerformance->accuracy_percentage = $row['accuracy_percentage'];
        $objUserPerformance->total_time_spent = $row['total_time_spent'];
        $objUserPerformance->average_time_per_question = $row['average_time_per_question'];
        $objUserPerformance->strongest_subject = $row['strongest_subject'];
        $objUserPerformance->weakest_subject = $row['weakest_subject'];
        $objUserPerformance->subject_scores = $row['subject_scores'];
        $objUserPerformance->strongest_topic = $row['strongest_topic'];
        $objUserPerformance->weakest_topic = $row['weakest_topic'];
        $objUserPerformance->topic_scores = $row['topic_scores'];
        $objUserPerformance->previous_quiz_accuracy = $row['previous_quiz_accuracy'];
        $objUserPerformance->accuracy_improvement = $row['accuracy_improvement'];
        $objUserPerformance->previous_avg_time = $row['previous_avg_time'];
        $objUserPerformance->time_improvement = $row['time_improvement'];
        $objUserPerformance->overall_progress_trend = $row['overall_progress_trend'];
        $objUserPerformance->subject_progress = $row['subject_progress'];
        $objUserPerformance->time_management_score = $row['time_management_score'];
        $objUserPerformance->difficulty_performance = $row['difficulty_performance'];
        $objUserPerformance->performance_score = $row['performance_score'];
        $objUserPerformance->ai_recommendations = $row['ai_recommendations'];
        $objUserPerformance->ai_learning_suggestions = $row['ai_learning_suggestions'];
        $objUserPerformance->progress_insights = $row['progress_insights'];
        $objUserPerformance->improvement_areas = $row['improvement_areas'];
        $objUserPerformance->strengths_identified = $row['strengths_identified'];
        $objUserPerformance->raw_ai_response = $row['raw_ai_response'];
        $objUserPerformance->analysis_version = $row['analysis_version'];
        $objUserPerformance->ai_prompt = $row['ai_prompt'];
        $objUserPerformance->created_at = $row['created_at'];
        $objUserPerformance->updated_at = $row['updated_at'];
        
        return $objUserPerformance;
    }
    
    /**
     * Get user performance by user_id and quiz_id
     */
    public function get_user_performance_by_quiz($user_id, $quiz_id) {
        $objUserPerformance = NULL;
        $sql = "select * from user_performance where user_id = ? and quiz_id = ? order by quiz_attempt_number desc limit 1";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id, $quiz_id));
        if ($row = $statement->fetch()) {
            $objUserPerformance = $this->map_row_to_object($row);
        }
        $statement = NULL;
        $pdo = NULL;
        return $objUserPerformance;
    }
}

?>
