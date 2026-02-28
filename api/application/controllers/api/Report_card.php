<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Report_card extends API_Controller
{

    public function __constructor()
    {
        parent::__construct();
    }

    /**
     * Generate a UUID v4
     * @return string
     */
    private function generateUUID()
    {
        // Generate random bytes
        $data = random_bytes(16);
        
        // Set version (4) and variant bits
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant bits
        
        // Format as UUID string without hyphens
        return sprintf('%s%s%s%s%s',
            bin2hex(substr($data, 0, 4)),
            bin2hex(substr($data, 4, 2)),
            bin2hex(substr($data, 6, 2)),
            bin2hex(substr($data, 8, 2)),
            bin2hex(substr($data, 10, 6))
        );
    }

  
    public function calculateStringTimeToMiliseconds($timeInString)
    {
        $startTime = new DateTime("now");
        $endDate = new DateTime($timeInString);

        $interval = $startTime->diff($endDate);

        $totalMiliseconds = 0;
        $totalMiliseconds += $interval->m * 2630000000;
        $totalMiliseconds += $interval->d * 86400000;
        $totalMiliseconds += $interval->h * 3600000;
        $totalMiliseconds += $interval->i * 60000;
        $totalMiliseconds += $interval->s * 1000;
		$totalMiliseconds += $startTime->format("u")/1000;
      
        return  round($totalMiliseconds);
    }

    /**
     * Get Subject-wise Topic Performance for a User
     * GET /api/quiz/user_topic_performance/{user_id}
     * Returns subject-wise topic names with question attempt and accuracy counts
     */
    function user_topic_performance_get()
    {
        // ✅ SECURE: Require JWT authentication
        $objUser = $this->require_jwt_auth();
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $user_id = $this->uri->segment(4);
        
        // If no user_id provided in URL, use authenticated user's ID
        if (empty($user_id)) {
            $user_id = $objUser->id;
        }

        // ✅ SECURE: Users can only access their own data (unless admin)
        if ($user_id != $objUser->id && !in_array($objUser->role_id, [1, 2, 3, 4])) {
            $this->send_forbidden_response("You can only access your own topic performance data");
            return;
        }

        try {
            $topic_performance = $this->get_user_topic_performance_data($user_id);
            
            if (empty($topic_performance)) {
                $response = $this->get_failed_response(NULL, "No quiz data found for this user");
                $this->set_output($response);
                return;
            }

            $response = $this->get_success_response($topic_performance, "User topic performance retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Error retrieving user topic performance for user {$user_id}: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error retrieving topic performance: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Handle OPTIONS request for CORS - User Topic Performance
     */
    function user_topic_performance_options()
    {
        $this->add_headers();
        $this->response(array('status' => 'OK'), REST_Controller::HTTP_OK);
    }

    /**
     * Helper function to get subject-wise topic performance data for a user
     * @param int $user_id
     * @return array Subject-wise topic performance data
     */
    private function get_user_topic_performance_data($user_id)
    {
        $pdo = CDatabase::getPdo();
        
        // Main query to get subject-wise topic performance
        $sql = "SELECT 
                    s.id as subject_id,
                    s.subject as subject_name,
                    COALESCE(t.topic_name, q.topic_name, 'Unknown Topic') as topic_name,
                    COALESCE(t.id, q.topic_id, 0) as topic_id,
                    COUNT(*) as total_questions_attempted,
                    SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                    SUM(CASE WHEN uq.is_correct = 0 AND uq.status = 'answered' THEN 1 ELSE 0 END) as incorrect_answers,
                    SUM(CASE WHEN uq.status = 'skipped' THEN 1 ELSE 0 END) as skipped_questions,
                    ROUND(
                        (SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 
                        2
                    ) as accuracy_percentage,
                    SUM(uq.duration) as total_time_spent_seconds,
                    ROUND(AVG(uq.duration), 2) as average_time_per_question_seconds
                FROM user_question uq
                JOIN question q ON uq.question_id = q.id
                JOIN subject s ON q.subject_id = s.id
                LEFT JOIN topic t ON q.topic_id = t.id
                WHERE uq.user_id = ?
                GROUP BY 
                    s.id, 
                    s.subject, 
                    COALESCE(t.topic_name, q.topic_name, 'Unknown Topic'),
                    COALESCE(t.id, q.topic_id, 0)
                ORDER BY 
                    s.subject ASC, 
                    accuracy_percentage DESC, 
                    total_questions_attempted DESC";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize data by subject
        $subject_topic_performance = array();
        $overall_stats = array(
            'total_subjects' => 0,
            'total_topics' => 0,
            'total_questions_attempted' => 0,
            'total_correct_answers' => 0,
            'overall_accuracy_percentage' => 0,
            'total_time_spent_seconds' => 0
        );
        
        foreach ($results as $row) {
            $subject_name = $row['subject_name'];
            
            // Initialize subject if not exists
            if (!isset($subject_topic_performance[$subject_name])) {
                $subject_topic_performance[$subject_name] = array(
                    'subject_id' => (int)$row['subject_id'],
                    'subject_name' => $subject_name,
                    'topics' => array(),
                    'subject_summary' => array(
                        'total_topics' => 0,
                        'total_questions_attempted' => 0,
                        'total_correct_answers' => 0,
                        'subject_accuracy_percentage' => 0,
                        'total_time_spent_seconds' => 0
                    )
                );
            }
            
            // Add topic data
            $topic_data = array(
                'topic_id' => (int)$row['topic_id'],
                'topic_name' => $row['topic_name'],
                'total_questions_attempted' => (int)$row['total_questions_attempted'],
                'correct_answers' => (int)$row['correct_answers'],
                'incorrect_answers' => (int)$row['incorrect_answers'],
                'skipped_questions' => (int)$row['skipped_questions'],
                'accuracy_percentage' => (float)$row['accuracy_percentage'],
                'total_time_spent_seconds' => (int)$row['total_time_spent_seconds'],
                'average_time_per_question_seconds' => (float)$row['average_time_per_question_seconds']
            );
            
            $subject_topic_performance[$subject_name]['topics'][] = $topic_data;
            
            // Update subject summary
            $subject_topic_performance[$subject_name]['subject_summary']['total_topics']++;
            $subject_topic_performance[$subject_name]['subject_summary']['total_questions_attempted'] += (int)$row['total_questions_attempted'];
            $subject_topic_performance[$subject_name]['subject_summary']['total_correct_answers'] += (int)$row['correct_answers'];
            $subject_topic_performance[$subject_name]['subject_summary']['total_time_spent_seconds'] += (int)$row['total_time_spent_seconds'];
            
            // Update overall stats
            $overall_stats['total_questions_attempted'] += (int)$row['total_questions_attempted'];
            $overall_stats['total_correct_answers'] += (int)$row['correct_answers'];
            $overall_stats['total_time_spent_seconds'] += (int)$row['total_time_spent_seconds'];
            $overall_stats['total_topics']++;
        }
        
        // Calculate subject and overall accuracy percentages
        foreach ($subject_topic_performance as $subject_name => &$subject_data) {
            if ($subject_data['subject_summary']['total_questions_attempted'] > 0) {
                $subject_data['subject_summary']['subject_accuracy_percentage'] = round(
                    ($subject_data['subject_summary']['total_correct_answers'] / 
                     $subject_data['subject_summary']['total_questions_attempted']) * 100, 
                    2
                );
            }
        }
        
        $overall_stats['total_subjects'] = count($subject_topic_performance);
        if ($overall_stats['total_questions_attempted'] > 0) {
            $overall_stats['overall_accuracy_percentage'] = round(
                ($overall_stats['total_correct_answers'] / $overall_stats['total_questions_attempted']) * 100, 
                2
            );
        }
        
        // Convert associative array to indexed array for easier frontend handling
        $subjects_array = array_values($subject_topic_performance);
        
        $statement = NULL;
        $pdo = NULL;
        
        return array(
            'user_id' => (int)$user_id,
            'overall_stats' => $overall_stats,
            'subjects' => $subjects_array
        );
    }

    /**
     * Get Quiz-wise Performance Data for Logged in User
     * GET /api/report_card/quiz_wise_performance
     * Returns quiz-wise performance data with all required metrics
     */
    function quiz_wise_performance_get()
    {
        // ✅ SECURE: Require JWT authentication
        $objUser = $this->require_jwt_auth();
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $user_id = $objUser->id;

        try {
            $quiz_performance_data = $this->get_quiz_wise_performance_data($user_id);
            
            if (empty($quiz_performance_data['quizzes'])) {
                $response = $this->get_failed_response(NULL, "No completed quiz data found for this user");
                $this->set_output($response);
                return;
            }

            $response = $this->get_success_response($quiz_performance_data, "Quiz-wise performance data retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Error retrieving quiz-wise performance for user {$user_id}: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error retrieving quiz performance data: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Handle OPTIONS request for CORS - Quiz Wise Performance
     */
    function quiz_wise_performance_options()
    {
        $this->add_headers();
        $this->response(array('status' => 'OK'), REST_Controller::HTTP_OK);
    }

    /**
     * Helper function to get quiz-wise performance data for a user
     * @param int $user_id
     * @return array Quiz-wise performance data
     */
    private function get_quiz_wise_performance_data($user_id)
    {
        $pdo = CDatabase::getPdo();
        
        // Main query to get quiz-wise performance data
        // We need to aggregate user_question data per quiz and get quiz details with subjects
        $sql = "SELECT 
                    q.id as quiz_id,
                    q.quiz_reference,
                    q.name as quiz_name,
                    q.level as quiz_level,
                    MAX(uq.created_at) as completion_date,
                    COUNT(uq.id) as total_questions,
                    SUM(CASE WHEN uq.status = 'answered' THEN 1 ELSE 0 END) as answered,
                    SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) as correct,
                    SUM(CASE WHEN uq.is_correct = 0 AND uq.status = 'answered' THEN 1 ELSE 0 END) as incorrect,
                    SUM(CASE WHEN uq.status IN ('skipped', 'timeout') THEN 1 ELSE 0 END) as unanswered,
                    SUM(COALESCE(uq.score, 0)) as total_score,
                    ROUND(
                        (SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(uq.id)) * 100, 
                        2
                    ) as percentage,
                    SUM(COALESCE(uq.duration, 0)) as time_spent_seconds,
                    ROUND(AVG(COALESCE(uq.duration, 0)), 2) as average_time_per_question,
                    -- Get attempt number (assuming latest attempt for now)
                    COALESCE(up.quiz_attempt_number, 1) as attempt_number
                FROM user_question uq
                JOIN quiz q ON uq.quiz_id = q.id
                LEFT JOIN user_performance up ON (up.user_id = uq.user_id AND up.quiz_id = uq.quiz_id)
                WHERE uq.user_id = ?
                GROUP BY 
                    q.id, 
                    q.quiz_reference, 
                    q.name, 
                    q.level,
                    up.quiz_attempt_number
                HAVING COUNT(uq.id) > 0
                ORDER BY completion_date DESC";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        $quiz_results = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        $quizzes_with_subjects = array();
        $total_quizzes_completed = 0;
        
        // For each quiz, get the subjects
        foreach ($quiz_results as $quiz) {
            $quiz_id = $quiz['quiz_id'];
            
            // Get subjects for this quiz
            $subjects_sql = "SELECT s.subject as subject_name 
                           FROM quiz_subjects qs 
                           JOIN subject s ON qs.subject_id = s.id 
                           WHERE qs.quiz_id = ?
                           ORDER BY s.subject";
            
            $subjects_statement = $pdo->prepare($subjects_sql);
            $subjects_statement->execute(array($quiz_id));
            $subjects_result = $subjects_statement->fetchAll(PDO::FETCH_COLUMN);
            
            // Format quiz data
            $quiz_data = array(
                'quiz_id' => (int)$quiz['quiz_id'],
                'quiz_reference' => $quiz['quiz_reference'],
                'quiz_name' => $quiz['quiz_name'],
                'completion_date' => $quiz['completion_date'],
                'subjects' => $subjects_result, // Array of subject names
                'total_questions' => (int)$quiz['total_questions'],
                'answered' => (int)$quiz['answered'],
                'correct' => (int)$quiz['correct'],
                'incorrect' => (int)$quiz['incorrect'],
                'unanswered' => (int)$quiz['unanswered'],
                'score' => (int)$quiz['total_score'],
                'percentage' => (float)$quiz['percentage'],
                'time_spent_seconds' => (int)$quiz['time_spent_seconds'],
                'average_time_per_question' => (float)$quiz['average_time_per_question'],
                'quiz_level' => $quiz['quiz_level'],
                'attempt_number' => (int)$quiz['attempt_number']
            );
            
            $quizzes_with_subjects[] = $quiz_data;
            $total_quizzes_completed++;
            
            $subjects_statement = NULL;
        }
        
        $statement = NULL;
        $pdo = NULL;
        
        return array(
            'user_id' => (int)$user_id,
            'total_quizzes_completed' => $total_quizzes_completed,
            'quizzes' => $quizzes_with_subjects
        );
    }

    /**
     * Get Subject-wise Chapter Performance for Logged in User
     * GET /api/report_card/subject_chapter_performance
     * Returns subject-wise chapter performance data with chapter ranking
     */
    function subject_chapter_performance_get()
    {
        // ✅ SECURE: Require JWT authentication
        $objUser = $this->require_jwt_auth();
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $user_id = $objUser->id;

        try {
            $chapter_performance_data = $this->get_subject_chapter_performance_data($user_id);
            
            if (empty($chapter_performance_data['subjects'])) {
                $response = $this->get_failed_response(NULL, "No chapter performance data found for this user");
                $this->set_output($response);
                return;
            }

            $response = $this->get_success_response($chapter_performance_data, "Subject-wise chapter performance data retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Error retrieving subject-wise chapter performance for user {$user_id}: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error retrieving chapter performance data: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Handle OPTIONS request for CORS - Subject Chapter Performance
     */
    function subject_chapter_performance_options()
    {
        $this->add_headers();
        $this->response(array('status' => 'OK'), REST_Controller::HTTP_OK);
    }

    /**
     * Helper function to get subject-wise chapter performance data for a user
     * @param int $user_id
     * @return array Subject-wise chapter performance data
     */
    private function get_subject_chapter_performance_data($user_id)
    {
        $pdo = CDatabase::getPdo();
        
        // Main query to get chapter performance data grouped by subject
        // Simplified approach to avoid complex COALESCE issues
        $sql = "SELECT 
                    s.id as subject_id,
                    s.subject as subject_name,
                    CASE 
                        WHEN c.id IS NOT NULL THEN c.id 
                        WHEN q.chapter_id IS NOT NULL THEN q.chapter_id 
                        ELSE 0 
                    END as chapter_id,
                    CASE 
                        WHEN c.chapter_name IS NOT NULL THEN c.chapter_name 
                        WHEN q.chapter_name IS NOT NULL THEN q.chapter_name 
                        ELSE 'Unknown Chapter' 
                    END as chapter_name,
                    COUNT(uq.id) as total_questions_attempted,
                    SUM(CASE WHEN uq.status = 'answered' THEN 1 ELSE 0 END) as questions_answered,
                    SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                    SUM(CASE WHEN uq.is_correct = 0 AND uq.status = 'answered' THEN 1 ELSE 0 END) as incorrect_answers,
                    SUM(CASE WHEN uq.status IN ('skipped', 'timeout') THEN 1 ELSE 0 END) as unanswered_questions,
                    ROUND(
                        (SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(uq.id)) * 100, 
                        2
                    ) as accuracy_percentage,
                    SUM(COALESCE(uq.duration, 0)) as total_time_spent_seconds,
                    ROUND(AVG(COALESCE(uq.duration, 0)), 2) as average_time_per_question,
                    MAX(COALESCE(uq.score, 0)) as best_quiz_score,
                    COUNT(DISTINCT uq.quiz_id) as attempts_count,
                    MAX(uq.created_at) as last_attempt_date
                FROM user_question uq
                JOIN question q ON uq.question_id = q.id
                JOIN subject s ON q.subject_id = s.id
                LEFT JOIN chapter c ON q.chapter_id = c.id
                WHERE uq.user_id = ?
                  AND (q.chapter_id IS NOT NULL OR q.chapter_name IS NOT NULL)
                GROUP BY 
                    s.id, 
                    s.subject,
                    CASE 
                        WHEN c.id IS NOT NULL THEN c.id 
                        WHEN q.chapter_id IS NOT NULL THEN q.chapter_id 
                        ELSE 0 
                    END,
                    CASE 
                        WHEN c.chapter_name IS NOT NULL THEN c.chapter_name 
                        WHEN q.chapter_name IS NOT NULL THEN q.chapter_name 
                        ELSE 'Unknown Chapter' 
                    END
                HAVING COUNT(uq.id) > 0
                ORDER BY 
                    s.subject ASC,
                    accuracy_percentage DESC,
                    total_questions_attempted DESC";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize data by subject with chapter ranking
        $subject_chapter_performance = array();
        $overall_stats = array(
            'total_subjects_attempted' => 0,
            'total_chapters_attempted' => 0,
            'total_questions_attempted' => 0,
            'overall_accuracy_percentage' => 0
        );
        
        foreach ($results as $row) {
            $subject_id = (int)$row['subject_id'];
            $subject_name = $row['subject_name'];
            
            // Initialize subject if not exists
            if (!isset($subject_chapter_performance[$subject_id])) {
                $subject_chapter_performance[$subject_id] = array(
                    'subject_id' => $subject_id,
                    'subject_name' => $subject_name,
                    'chapters' => array(),
                    'subject_summary' => array(
                        'total_chapters_attempted' => 0,
                        'total_questions_attempted' => 0,
                        'overall_accuracy_percentage' => 0,
                        'strongest_chapter' => '',
                        'weakest_chapter' => ''
                    )
                );
            }
            
            // Add chapter data
            $chapter_data = array(
                'chapter_id' => (int)$row['chapter_id'],
                'chapter_name' => $row['chapter_name'],
                'total_questions_attempted' => (int)$row['total_questions_attempted'],
                'questions_answered' => (int)$row['questions_answered'],
                'correct_answers' => (int)$row['correct_answers'],
                'incorrect_answers' => (int)$row['incorrect_answers'],
                'unanswered_questions' => (int)$row['unanswered_questions'],
                'accuracy_percentage' => (float)$row['accuracy_percentage'],
                'total_time_spent_seconds' => (int)$row['total_time_spent_seconds'],
                'average_time_per_question' => (float)$row['average_time_per_question'],
                'best_quiz_score' => (int)$row['best_quiz_score'],
                'attempts_count' => (int)$row['attempts_count'],
                'last_attempt_date' => $row['last_attempt_date']
            );
            
            $subject_chapter_performance[$subject_id]['chapters'][] = $chapter_data;
            
            // Update subject summary
            $subject_chapter_performance[$subject_id]['subject_summary']['total_chapters_attempted']++;
            $subject_chapter_performance[$subject_id]['subject_summary']['total_questions_attempted'] += (int)$row['total_questions_attempted'];
            
            // Update overall stats
            $overall_stats['total_questions_attempted'] += (int)$row['total_questions_attempted'];
        }
        
        // Calculate rankings and summary stats for each subject
        foreach ($subject_chapter_performance as $subject_id => &$subject_data) {
            $chapters = &$subject_data['chapters'];
            $total_questions = $subject_data['subject_summary']['total_questions_attempted'];
            $total_correct = 0;
            
            // Add chapter ranking and calculate totals
            foreach ($chapters as $index => &$chapter) {
                $chapter['chapter_rank'] = $index + 1; // Rank within subject (already sorted by accuracy)
                $total_correct += $chapter['correct_answers'];
            }
            
            // Calculate subject overall accuracy
            if ($total_questions > 0) {
                $subject_data['subject_summary']['overall_accuracy_percentage'] = round(
                    ($total_correct / $total_questions) * 100, 
                    2
                );
            }
            
            // Find strongest and weakest chapters
            if (count($chapters) > 0) {
                $subject_data['subject_summary']['strongest_chapter'] = $chapters[0]['chapter_name']; // Highest accuracy
                $subject_data['subject_summary']['weakest_chapter'] = end($chapters)['chapter_name']; // Lowest accuracy
            }
        }
        
        // CRITICAL: Unset references to prevent data corruption
        unset($subject_data, $chapters, $chapter);
        
        // Calculate overall stats
        $overall_stats['total_subjects_attempted'] = count($subject_chapter_performance);
        $overall_stats['total_chapters_attempted'] = array_sum(array_column(array_column($subject_chapter_performance, 'subject_summary'), 'total_chapters_attempted'));
        
        if ($overall_stats['total_questions_attempted'] > 0) {
            $total_correct_overall = 0;
            foreach ($subject_chapter_performance as $subject_data) {
                foreach ($subject_data['chapters'] as $chapter) {
                    $total_correct_overall += $chapter['correct_answers'];
                }
            }
            $overall_stats['overall_accuracy_percentage'] = round(
                ($total_correct_overall / $overall_stats['total_questions_attempted']) * 100, 
                2
            );
        }
        
        // Convert associative array to indexed array for easier frontend handling
        $subjects_array = array();
        foreach ($subject_chapter_performance as $subject_id => $subject_data) {
            $subjects_array[] = $subject_data;
        }
        
        $statement = NULL;
        $pdo = NULL;
        
        return array(
            'user_id' => (int)$user_id,
            'overall_stats' => $overall_stats,
            'subjects' => $subjects_array
        );
    }

}
