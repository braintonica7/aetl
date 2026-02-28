<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Dashboard
 *
 * @author Jawahar
 */
class Dashboard extends API_Controller {

    public function upcoming_assignments_get() {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $objUser = $this->get_logged_user();
        $scholarId = $objUser->scholar->id;
        $classId = $objUser->scholar->class_id;

        $pdo = CDatabase::getPdo();        
        $sql = "select content.*, subject.subject from content left join subject on content.subject_id = subject.id where content_type_id = 4 and class_id = $classId and content.id not in(select DISTINCT content_id from assignment where scholar_id = $scholarId) order by submission_date";        
        $statement = $pdo->prepare($sql);
        $statement->execute();
        $assignments = array();
        while ($row = $statement->fetch()) {        
            $objContent = new Content_object();
            $objContent->id = $row['id'];
            $objContent->academic_session = $row['academic_session'];
            $objContent->content_type_id = $row['content_type_id'];
            $objContent->content_url = $row['content_url'];
            $objContent->class_id = $row['class_id'];
            $objContent->subject_id = $row['subject_id'];
            $objContent->topic = $row['topic'];
            $objContent->topic_note = $row['topic_note'];

            if ($row['submission_date'] == NULL)
                $objContent->submission_date = NULL;
            else
                $objContent->submission_date = DateTime::createFromFormat("Y-m-d", $row['submission_date'])->format('Y-m-d');

            $objContent->is_active = $row['is_active'] == 1;
            $objContent->is_approved = $row['is_approved'] == 1;
            $objContent->uploaded_by = $row['uploaded_by'];
            $objContent->approved_by = $row['approved_by'];

            if ($row['approval_date'] == NULL)
                $objContent->approval_date = NULL;
            else
                $objContent->approval_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['approval_date'])->format('Y-m-d H:i:s');

            $objContent->sensored_by = $row['sensored_by'];

            if ($row['sensor_date'] == NULL)
                $objContent->approval_date = NULL;
            else
                $objContent->approval_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['sensor_date'])->format('Y-m-d H:i:s');

            $objContent->created = DateTime::createFromFormat("Y-m-d H:i:s", $row['created']);

            if ($row['updated'] == NULL)
                $objContent->approval_date = NULL;
            else
                $objContent->approval_date = DateTime::createFromFormat("Y-m-d H:i:s", $row['updated'])->format('Y-m-d H:i:s');
            
            $objContent->subject = $row['subject'];
                                    
            $assignments[] = $objContent;
        }
        $statement = NULL;
        $pdo = NULL;
        
        if (count($assignments) > 0) 
        {
            $response = $this->get_success_response($assignments, "List of upcoming assignments..!");
            $this->set_output($response);
        }else{
            $response = $this->get_failed_response(NULL, "No Data Available..!");
            $this->set_output($response);
        }
    }


        /**
     * Get dashboard banner image path
     * GET /api/dashboard/app_updates
     * Returns the active banner image URL for mobile dashboard
     */
    public function app_updates_get() {
        try {
            
            $curent_app_version = $this->input->get('curent_app_version');
            if ($curent_app_version === null || $curent_app_version === '') {
                $curent_app_version = '1.0.3';
            }

            $current_deployed_version = '1.0.5';
            $app_update_available = false;
            if (version_compare($curent_app_version, $current_deployed_version, '<')) {
                $app_update_available = true;
            }
            $banner_data = array(
                'app_update_available' => $app_update_available
            );

            $response = $this->get_success_response($banner_data, "Banner retrieved successfully");
            $this->set_output($response);

        } catch (Exception $e) {
            log_message('error', "Dashboard banner error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to retrieve banner: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Get dashboard banner image path
     * GET /api/dashboard/banner
     * Returns the active banner image URL for mobile dashboard
     */
    public function banner_get() {
        try {
            // ✅ SECURE: Require JWT authentication for user access
             $objUser = $this->require_jwt_auth(false); // false = regular user access
             if (!$objUser) {
                 return; // Error response already sent by require_jwt_auth()
             }

            // TODO: Replace with database query when banner management is implemented
            // For now, returning hardcoded path
            $banner_data = array(
                'image_url' => ['https://images.wiziai.com/assets/wizi-banner.jpg'],
                'is_active' => true
            );

            $response = $this->get_success_response($banner_data, "Banner retrieved successfully");
            $this->set_output($response);

        } catch (Exception $e) {
            log_message('error', "Dashboard banner error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to retrieve banner: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Get comprehensive question data summary for admin dashboard
     * GET /api/dashboard/question_summary
     */
    public function question_summary_get() {
        try {

            // ✅ SECURE: Require JWT authentication for user access
            $objUser = $this->require_jwt_auth(true); // true = admin access
            if (!$objUser) {
                return; // Error response already sent by require_jwt_auth()
            }
            $this->load->model('question/Question_model');
            
            // Get question_type parameter with default value 'regular'
            $question_type = $this->input->get('question_type');
            if ($question_type === null || $question_type === '') {
                $question_type = 'regular';
            }
            
            // Validate question_type
            $valid_types = array('regular', 'pyq', 'mock');
            if (!in_array($question_type, $valid_types)) {
                $response = $this->get_failed_response(NULL, "Invalid question_type. Must be one of: regular, pyq, mock");
                $this->set_output($response);
                return;
            }
             
            // Get overall statistics filtered by question_type
            $summary = array(
                'question_type' => $question_type,
                'total_questions' => $this->get_total_questions_count($question_type),
                'questions_by_subject' => $this->get_questions_by_subject($question_type),
                'questions_by_chapter' => $this->get_questions_by_chapter($question_type),
                'questions_by_topic' => $this->get_questions_by_topic($question_type),
                'questions_by_exam' => $this->get_questions_by_exam($question_type),
                'content_generation_stats' => $this->get_content_generation_stats($question_type),
                'recent_activity' => $this->get_recent_question_activity($question_type)
            );
            
            $response = $this->get_success_response($summary, "Question summary retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Dashboard question summary error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to retrieve question summary: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Get questions by subject with detailed chapter breakdown
     * GET /api/dashboard/questions_by_subject/{subject_id}
     */
    public function questions_by_subject_get($subject_id = null) {
        try {
            // ✅ SECURE: Require JWT authentication for user access
            $objUser = $this->require_jwt_auth(false); // false = regular user access
            if (!$objUser) {
                return; // Error response already sent by require_jwt_auth()
            }
            if (!$subject_id) {
                $response = $this->get_failed_response(NULL, "Subject ID is required");
                $this->set_output($response);
                return;
            }

            $this->load->model('question/Question_model');
            
            // Get subject details
            $subject_details = $this->get_subject_details($subject_id);
            if (!$subject_details) {
                $response = $this->get_failed_response(NULL, "Subject not found");
                $this->set_output($response);
                return;
            }

            // Get questions by chapter for this subject
            $chapters_data = $this->get_questions_by_chapter_for_subject($subject_id);
            
            // Get content generation stats for this subject
            $content_stats = $this->get_content_generation_stats_for_subject($subject_id);
            
            // Get total questions for this subject
            $total_questions = $this->get_total_questions_for_subject($subject_id);

            $summary = array(
                'subject_details' => $subject_details,
                'total_questions' => $total_questions,
                'questions_by_chapter' => $chapters_data,
                'content_generation_stats' => $content_stats
            );
            
            $response = $this->get_success_response($summary, "Subject question summary retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Dashboard subject questions error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to retrieve subject questions: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Get total questions count
     */
    private function get_total_questions_count($question_type = 'regular') {
        $pdo = CDatabase::getPdo();
        $sql = "SELECT COUNT(*) as total FROM question WHERE question_type = :question_type";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':question_type', $question_type, PDO::PARAM_STR);
        $statement->execute();
        $result = $statement->fetch();
        return intval($result['total']);
    }

    /**
     * Get questions count by subject
     */
    private function get_questions_by_subject($question_type = 'regular') {
        $pdo = CDatabase::getPdo();
        $sql = "SELECT 
                    s.subject as subject_name,
                    s.id as subject_id,
                    COUNT(q.id) as question_count
                FROM subject s
                LEFT JOIN question q ON s.id = q.subject_id AND q.question_type = :question_type
                GROUP BY s.id, s.subject
                ORDER BY question_count DESC, s.subject ASC";
        
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':question_type', $question_type, PDO::PARAM_STR);
        $statement->execute();
        
        $results = array();
        while ($row = $statement->fetch()) {
            $results[] = array(
                'subject_id' => $row['subject_id'],
                'subject_name' => $row['subject_name'],
                'question_count' => intval($row['question_count'])
            );
        }
        
        return $results;
    }

    /**
     * Get questions count by chapter
     */
    private function get_questions_by_chapter($question_type = 'regular') {
        $pdo = CDatabase::getPdo();
        $sql = "SELECT 
                    c.id as chapter_id,
                    c.chapter_name,
                    s.subject as subject_name,
                    COUNT(q.id) as question_count
                FROM chapter c
                LEFT JOIN subject s ON c.subject_id = s.id
                LEFT JOIN question q ON c.id = q.chapter_id AND q.question_type = :question_type
                GROUP BY c.id, c.chapter_name, s.subject
                HAVING question_count > 0
                ORDER BY question_count DESC, c.chapter_name ASC
                LIMIT 20";
        
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':question_type', $question_type, PDO::PARAM_STR);
        $statement->execute();
        
        $results = array();
        while ($row = $statement->fetch()) {
            $results[] = array(
                'chapter_id' => $row['chapter_id'],
                'chapter_name' => $row['chapter_name'],
                'subject_name' => $row['subject_name'],
                'question_count' => intval($row['question_count'])
            );
        }
        
        return $results;
    }

    /**
     * Get questions count by topic
     */
    private function get_questions_by_topic($question_type = 'regular') {
        $pdo = CDatabase::getPdo();
        $sql = "SELECT 
                    t.id as topic_id,
                    t.topic_name,
                    c.chapter_name,
                    s.subject as subject_name,
                    COUNT(q.id) as question_count
                FROM topic t
                LEFT JOIN chapter c ON t.chapter_id = c.id
                LEFT JOIN subject s ON t.subject_id = s.id
                LEFT JOIN question q ON t.id = q.topic_id AND q.question_type = :question_type
                GROUP BY t.id, t.topic_name, c.chapter_name, s.subject
                HAVING question_count > 0
                ORDER BY question_count DESC, t.topic_name ASC
                LIMIT 20";
        
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':question_type', $question_type, PDO::PARAM_STR);
        $statement->execute();
        
        $results = array();
        while ($row = $statement->fetch()) {
            $results[] = array(
                'topic_id' => $row['topic_id'],
                'topic_name' => $row['topic_name'],
                'chapter_name' => $row['chapter_name'],
                'subject_name' => $row['subject_name'],
                'question_count' => intval($row['question_count'])
            );
        }
        
        return $results;
    }

    /**
     * Get questions count by exam
     */
    private function get_questions_by_exam($question_type = 'regular') {
        $pdo = CDatabase::getPdo();
        $sql = "SELECT 
                    e.id as exam_id,
                    e.exam_name,
                    COUNT(q.id) as question_count
                FROM exam e
                LEFT JOIN question q ON e.id = q.exam_id AND q.question_type = :question_type
                GROUP BY e.id, e.exam_name
                HAVING question_count > 0
                ORDER BY question_count DESC, e.exam_name ASC";
        
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':question_type', $question_type, PDO::PARAM_STR);
        $statement->execute();
        
        $results = array();
        while ($row = $statement->fetch()) {
            $results[] = array(
                'exam_id' => $row['exam_id'],
                'exam_name' => $row['exam_name'],
                'question_count' => intval($row['question_count'])
            );
        }
        
        return $results;
    }

    /**
     * Get content generation statistics
     */
    private function get_content_generation_stats($question_type = 'regular') {
        $pdo = CDatabase::getPdo();
        
        // Get question text generation stats
        $sql_question_text = "SELECT 
                                COUNT(*) as total_questions,
                                COUNT(CASE WHEN question_text IS NOT NULL AND question_text != '' THEN 1 END) as with_question_text,
                                COUNT(CASE WHEN question_text IS NULL OR question_text = '' THEN 1 END) as without_question_text
                              FROM question
                              WHERE question_type = :question_type";
        
        $statement = $pdo->prepare($sql_question_text);
        $statement->bindValue(':question_type', $question_type, PDO::PARAM_STR);
        $statement->execute();
        $question_text_stats = $statement->fetch();
        
        // Get AI summary generation stats
        $sql_ai_summary = "SELECT 
                            COUNT(*) as total_questions,
                            COUNT(CASE WHEN ai_summary IS NOT NULL AND ai_summary != '' THEN 1 END) as with_ai_summary,
                            COUNT(CASE WHEN ai_summary IS NULL OR ai_summary = '' THEN 1 END) as without_ai_summary
                           FROM question
                           WHERE question_type = :question_type";
        
        $statement = $pdo->prepare($sql_ai_summary);
        $statement->bindValue(':question_type', $question_type, PDO::PARAM_STR);
        $statement->execute();
        $ai_summary_stats = $statement->fetch();
        
        // Get solution generation stats
        $sql_solution = "SELECT 
                          COUNT(*) as total_questions,
                          COUNT(CASE WHEN solution IS NOT NULL AND solution != '' THEN 1 END) as with_solution,
                          COUNT(CASE WHEN solution IS NULL OR solution = '' THEN 1 END) as without_solution
                         FROM question
                         WHERE question_type = :question_type";
        
        $statement = $pdo->prepare($sql_solution);
        $statement->bindValue(':question_type', $question_type, PDO::PARAM_STR);
        $statement->execute();
        $solution_stats = $statement->fetch();
        
        // Calculate percentages
        $total = intval($question_text_stats['total_questions']);
        
        return array(
            'total_questions' => $total,
            'question_text' => array(
                'generated' => intval($question_text_stats['with_question_text']),
                'not_generated' => intval($question_text_stats['without_question_text']),
                'percentage_generated' => $total > 0 ? round((intval($question_text_stats['with_question_text']) / $total) * 100, 1) : 0
            ),
            'ai_summary' => array(
                'generated' => intval($ai_summary_stats['with_ai_summary']),
                'not_generated' => intval($ai_summary_stats['without_ai_summary']),
                'percentage_generated' => $total > 0 ? round((intval($ai_summary_stats['with_ai_summary']) / $total) * 100, 1) : 0
            ),
            'solution' => array(
                'generated' => intval($solution_stats['with_solution']),
                'not_generated' => intval($solution_stats['without_solution']),
                'percentage_generated' => $total > 0 ? round((intval($solution_stats['with_solution']) / $total) * 100, 1) : 0
            )
        );
    }

    /**
     * Get recent question activity (last 30 days)
     */
    private function get_recent_question_activity($question_type = 'regular') {
        $pdo = CDatabase::getPdo();
        $sql = "SELECT 
                    DATE(summary_generated_at) as activity_date,
                    COUNT(*) as questions_processed
                FROM question 
                WHERE summary_generated_at IS NOT NULL 
                AND summary_generated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND question_type = :question_type
                GROUP BY DATE(summary_generated_at)
                ORDER BY activity_date DESC
                LIMIT 10";
        
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':question_type', $question_type, PDO::PARAM_STR);
        $statement->execute();
        
        $results = array();
        while ($row = $statement->fetch()) {
            $results[] = array(
                'date' => $row['activity_date'],
                'questions_processed' => intval($row['questions_processed'])
            );
        }
        
        return $results;
    }

    /**
     * Get subject details by ID
     */
    private function get_subject_details($subject_id) {
        $pdo = CDatabase::getPdo();
        $sql = "SELECT id, subject as subject_name FROM subject WHERE id = :subject_id";
        $statement = $pdo->prepare($sql);
        $statement->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetch();
        
        if ($result) {
            return array(
                'subject_id' => intval($result['id']),
                'subject_name' => $result['subject_name']
            );
        }
        
        return null;
    }

    /**
     * Get total questions count for a specific subject
     */
    private function get_total_questions_for_subject($subject_id) {
        $pdo = CDatabase::getPdo();
        $sql = "SELECT COUNT(*) as total FROM question WHERE subject_id = :subject_id";
        $statement = $pdo->prepare($sql);
        $statement->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetch();
        return intval($result['total']);
    }

    /**
     * Get questions count by chapter for a specific subject
     */
    private function get_questions_by_chapter_for_subject($subject_id) {
        $pdo = CDatabase::getPdo();
        $sql = "SELECT 
                    c.id as chapter_id,
                    c.chapter_name as chapter_name,
                    COUNT(q.id) as question_count,
                    -- Content generation stats for each chapter
                    SUM(CASE WHEN q.question_text IS NOT NULL AND q.question_text != '' THEN 1 ELSE 0 END) as questions_with_text,
                    SUM(CASE WHEN q.ai_summary IS NOT NULL AND q.ai_summary != '' THEN 1 ELSE 0 END) as questions_with_summary,
                    SUM(CASE WHEN q.solution IS NOT NULL AND q.solution != '' THEN 1 ELSE 0 END) as questions_with_solution
                FROM chapter c
                LEFT JOIN question q ON c.id = q.chapter_id
                WHERE c.subject_id = :subject_id
                GROUP BY c.id, c.chapter_name
                HAVING COUNT(q.id) > 0
                ORDER BY question_count DESC";
        
        $statement = $pdo->prepare($sql);
        $statement->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
        $statement->execute();
        
        $results = array();
        while ($row = $statement->fetch()) {
            $total_questions = intval($row['question_count']);
            $results[] = array(
                'chapter_id' => intval($row['chapter_id']),
                'chapter_name' => $row['chapter_name'],
                'question_count' => $total_questions,
                'content_stats' => array(
                    'question_text_generated' => intval($row['questions_with_text']),
                    'ai_summary_generated' => intval($row['questions_with_summary']),
                    'solution_generated' => intval($row['questions_with_solution']),
                    'completion_percentage' => $total_questions > 0 ? 
                        round((intval($row['questions_with_text']) + intval($row['questions_with_summary']) + intval($row['questions_with_solution'])) / ($total_questions * 3) * 100, 1) : 0
                )
            );
        }
        
        return $results;
    }

    /**
     * Get content generation statistics for a specific subject
     */
    private function get_content_generation_stats_for_subject($subject_id) {
        $pdo = CDatabase::getPdo();
        
        // Get question text stats
        $sql = "SELECT 
                    COUNT(*) as total_questions,
                    SUM(CASE WHEN question_text IS NOT NULL AND question_text != '' THEN 1 ELSE 0 END) as with_question_text,
                    SUM(CASE WHEN question_text IS NULL OR question_text = '' THEN 1 ELSE 0 END) as without_question_text
                FROM question 
                WHERE subject_id = :subject_id";
        
        $statement = $pdo->prepare($sql);
        $statement->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
        $statement->execute();
        $question_text_stats = $statement->fetch();
        
        // Get AI summary stats
        $sql = "SELECT 
                    SUM(CASE WHEN ai_summary IS NOT NULL AND ai_summary != '' THEN 1 ELSE 0 END) as with_ai_summary,
                    SUM(CASE WHEN ai_summary IS NULL OR ai_summary = '' THEN 1 ELSE 0 END) as without_ai_summary
                FROM question 
                WHERE subject_id = :subject_id";
        
        $statement = $pdo->prepare($sql);
        $statement->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
        $statement->execute();
        $ai_summary_stats = $statement->fetch();
        
        // Get solution stats
        $sql = "SELECT 
                    SUM(CASE WHEN solution IS NOT NULL AND solution != '' THEN 1 ELSE 0 END) as with_solution,
                    SUM(CASE WHEN solution IS NULL OR solution = '' THEN 1 ELSE 0 END) as without_solution
                FROM question 
                WHERE subject_id = :subject_id";
        
        $statement = $pdo->prepare($sql);
        $statement->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
        $statement->execute();
        $solution_stats = $statement->fetch();
        
        // Calculate percentages
        $total = intval($question_text_stats['total_questions']);
        
        return array(
            'total_questions' => $total,
            'question_text' => array(
                'generated' => intval($question_text_stats['with_question_text']),
                'not_generated' => intval($question_text_stats['without_question_text']),
                'percentage_generated' => $total > 0 ? round((intval($question_text_stats['with_question_text']) / $total) * 100, 1) : 0
            ),
            'ai_summary' => array(
                'generated' => intval($ai_summary_stats['with_ai_summary']),
                'not_generated' => intval($ai_summary_stats['without_ai_summary']),
                'percentage_generated' => $total > 0 ? round((intval($ai_summary_stats['with_ai_summary']) / $total) * 100, 1) : 0
            ),
            'solution' => array(
                'generated' => intval($solution_stats['with_solution']),
                'not_generated' => intval($solution_stats['without_solution']),
                'percentage_generated' => $total > 0 ? round((intval($solution_stats['with_solution']) / $total) * 100, 1) : 0
            )
        );
    }

    /**
     * Get user activity timeline - recent quizzes and achievements
     * GET /api/dashboard/activity_timeline
     * Returns: Array of recent activities (quizzes, achievements, streaks)
     */
    public function activity_timeline_get() {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        $user_id = $objUser->id;
        $limit = $this->input->get('limit') ? intval($this->input->get('limit')) : 10;

        try {
            $pdo = CDatabase::getPdo();
            
            // Get recent quiz completions with scores
            $sql = "SELECT 
                        q.id as quiz_id,
                        q.name as quiz_name,
                        q.exam_id,
                        q.subject_id,
                        s.subject as subject_name,
                        COUNT(DISTINCT uq.question_id) as questions_attempted,
                        SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                        ROUND((SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as accuracy,
                        MAX(uq.created_at) as completed_at
                    FROM user_question uq
                    INNER JOIN quiz q ON uq.quiz_id = q.id
                    LEFT JOIN subject s ON q.subject_id = s.id
                    WHERE uq.user_id = ?
                    GROUP BY uq.quiz_id
                    ORDER BY MAX(uq.created_at) DESC
                    LIMIT ?";
            
            $statement = $pdo->prepare($sql);
            $statement->bindValue(1, $user_id, PDO::PARAM_INT);
            $statement->bindValue(2, $limit, PDO::PARAM_INT);
            $statement->execute();
            $activities = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            $formatted_activities = array();
            foreach ($activities as $activity) {
                $formatted_activities[] = array(
                    'type' => 'quiz',
                    'quiz_id' => intval($activity['quiz_id']),
                    'title' => $activity['quiz_name'],
                    'subtitle' => $activity['subject_name'] ?: 'Quiz',
                    'score' => floatval($activity['accuracy']) . '%',
                    'details' => $activity['correct_answers'] . '/' . $activity['questions_attempted'] . ' correct',
                    'timestamp' => $activity['completed_at'],
                    'points' => 0 // Can be enhanced with actual points if available
                );
            }
            
            $response = $this->get_success_response($formatted_activities, "Activity timeline retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Activity timeline error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to retrieve activity timeline: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Get subject-wise performance with chapter breakdown
     * GET /api/dashboard/subject_performance
     * Returns: Subject performance data with chapter-wise accuracy
     */
    public function subject_performance_get() {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        $user_id = $objUser->id;

        try {
            $pdo = CDatabase::getPdo();
            
            // Get subject-wise performance
            $sql = "SELECT 
                        s.id as subject_id,
                        s.subject as subject_name,
                        COUNT(DISTINCT uq.question_id) as questions_attempted,
                        SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                        ROUND((SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as accuracy
                    FROM user_question uq
                    INNER JOIN question q ON uq.question_id = q.id
                    INNER JOIN subject s ON q.subject_id = s.id
                    WHERE uq.user_id = ?
                    GROUP BY s.id, s.subject
                    ORDER BY s.subject";
            
            $statement = $pdo->prepare($sql);
            $statement->execute(array($user_id));
            $subjects = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            $subject_performance = array();
            
            // For each subject, get chapter-wise performance
            foreach ($subjects as $subject) {
                $subject_id = $subject['subject_id'];
                
                $sql_chapters = "SELECT 
                                    c.id as chapter_id,
                                    c.chapter_name,
                                    COUNT(DISTINCT uq.question_id) as questions_attempted,
                                    SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                                    ROUND((SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as accuracy
                                FROM user_question uq
                                INNER JOIN question q ON uq.question_id = q.id
                                INNER JOIN chapter c ON q.chapter_id = c.id
                                WHERE uq.user_id = ? AND q.subject_id = ?
                                GROUP BY c.id, c.chapter_name
                                ORDER BY c.chapter_name";
                
                $stmt_chapters = $pdo->prepare($sql_chapters);
                $stmt_chapters->execute(array($user_id, $subject_id));
                $chapters = $stmt_chapters->fetchAll(PDO::FETCH_ASSOC);
                
                $subject_performance[] = array(
                    'subject_id' => intval($subject['subject_id']),
                    'subject_name' => $subject['subject_name'],
                    'accuracy' => floatval($subject['accuracy']),
                    'questions_attempted' => intval($subject['questions_attempted']),
                    'correct_answers' => intval($subject['correct_answers']),
                    'chapters' => array_map(function($chapter) {
                        return array(
                            'chapter_id' => intval($chapter['chapter_id']),
                            'chapter_name' => $chapter['chapter_name'],
                            'accuracy' => floatval($chapter['accuracy']),
                            'questions_attempted' => intval($chapter['questions_attempted'])
                        );
                    }, $chapters)
                );
            }
            
            $response = $this->get_success_response($subject_performance, "Subject performance retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Subject performance error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to retrieve subject performance: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Get user's study streak data
     * GET /api/dashboard/study_streak
     * Returns: Current streak, longest streak, last activity date
     */
    public function study_streak_get() {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        $user_id = $objUser->id;

        try {
            $pdo = CDatabase::getPdo();
            
            // Get all unique activity dates for the user (dates when they attempted quizzes)
            $sql = "SELECT DISTINCT DATE(created_at) as activity_date
                    FROM user_question
                    WHERE user_id = ?
                    ORDER BY activity_date DESC";
            
            $statement = $pdo->prepare($sql);
            $statement->execute(array($user_id));
            $activity_dates = $statement->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($activity_dates)) {
                $result = array(
                    'current_streak' => 0,
                    'longest_streak' => 0,
                    'last_activity_date' => null
                );
            } else {
                // Calculate current streak
                $current_streak = 0;
                $today = new DateTime();
                $today->setTime(0, 0, 0);
                
                foreach ($activity_dates as $date_str) {
                    $activity_date = new DateTime($date_str);
                    $activity_date->setTime(0, 0, 0);
                    
                    $diff = $today->diff($activity_date)->days;
                    
                    if ($diff == $current_streak) {
                        $current_streak++;
                    } else {
                        break;
                    }
                }
                
                // Calculate longest streak
                $longest_streak = 1;
                $temp_streak = 1;
                
                for ($i = 0; $i < count($activity_dates) - 1; $i++) {
                    $date1 = new DateTime($activity_dates[$i]);
                    $date2 = new DateTime($activity_dates[$i + 1]);
                    $diff = $date1->diff($date2)->days;
                    
                    if ($diff == 1) {
                        $temp_streak++;
                        $longest_streak = max($longest_streak, $temp_streak);
                    } else {
                        $temp_streak = 1;
                    }
                }
                
                $result = array(
                    'current_streak' => $current_streak,
                    'longest_streak' => $longest_streak,
                    'last_activity_date' => $activity_dates[0]
                );
            }
            
            $response = $this->get_success_response($result, "Study streak retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Study streak error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to retrieve study streak: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Get user's daily and weekly goals progress
     * GET /api/dashboard/goals_progress
     * Returns: Daily and weekly quiz completion goals
     */
    public function goals_progress_get() {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        $user_id = $objUser->id;

        try {
            $pdo = CDatabase::getPdo();
            
            // Get quizzes completed today
            $sql_daily = "SELECT COUNT(DISTINCT quiz_id) as completed_today
                          FROM user_question
                          WHERE user_id = ? AND DATE(created_at) = CURDATE()";
            
            $statement = $pdo->prepare($sql_daily);
            $statement->execute(array($user_id));
            $daily_result = $statement->fetch();
            $completed_today = intval($daily_result['completed_today']);
            
            // Get quizzes completed this week
            $sql_weekly = "SELECT COUNT(DISTINCT quiz_id) as completed_this_week
                           FROM user_question
                           WHERE user_id = ? 
                           AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
            
            $statement = $pdo->prepare($sql_weekly);
            $statement->execute(array($user_id));
            $weekly_result = $statement->fetch();
            $completed_this_week = intval($weekly_result['completed_this_week']);
            
            // Default goals (can be customized per user in future)
            $daily_target = 3;
            $weekly_target = 15;
            
            $result = array(
                'daily' => array(
                    'target' => $daily_target,
                    'completed' => $completed_today,
                    'percentage' => $daily_target > 0 ? round(($completed_today / $daily_target) * 100, 2) : 0
                ),
                'weekly' => array(
                    'target' => $weekly_target,
                    'completed' => $completed_this_week,
                    'percentage' => $weekly_target > 0 ? round(($completed_this_week / $weekly_target) * 100, 2) : 0
                )
            );
            
            $response = $this->get_success_response($result, "Goals progress retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Goals progress error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to retrieve goals progress: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Get performance trends (comparison of current period vs previous period)
     * GET /api/dashboard/performance_trends?days=7
     * 
     * @return JSON response with trend data
     */
    public function performance_trends_get() {
        // Require JWT authentication
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        try {
            $user_id = $objUser->id;
            $days = $this->input->get('days') ?: 7; // Default to 7 days

            $pdo = CDatabase::getPdo();

            // Calculate date ranges
            $current_start = date('Y-m-d', strtotime("-{$days} days"));
            $previous_start = date('Y-m-d', strtotime("-" . ($days * 2) . " days"));
            $previous_end = date('Y-m-d', strtotime("-{$days} days"));

            // Get current period stats
            $sql_current = "SELECT 
                            COUNT(DISTINCT quiz_id) as tests_taken,
                            COUNT(*) as total_questions,
                            SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                            SUM(duration) as total_time
                           FROM user_question 
                           WHERE user_id = ? 
                           AND DATE(created_at) >= ?";
            
            $stmt_current = $pdo->prepare($sql_current);
            $stmt_current->bindValue(1, $user_id, PDO::PARAM_INT);
            $stmt_current->bindValue(2, $current_start, PDO::PARAM_STR);
            $stmt_current->execute();
            $current_stats = $stmt_current->fetch(PDO::FETCH_ASSOC);

            // Get previous period stats
            $sql_previous = "SELECT 
                            COUNT(DISTINCT quiz_id) as tests_taken,
                            COUNT(*) as total_questions,
                            SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                            SUM(duration) as total_time
                           FROM user_question 
                           WHERE user_id = ? 
                           AND DATE(created_at) >= ? 
                           AND DATE(created_at) < ?";
            
            $stmt_previous = $pdo->prepare($sql_previous);
            $stmt_previous->bindValue(1, $user_id, PDO::PARAM_INT);
            $stmt_previous->bindValue(2, $previous_start, PDO::PARAM_STR);
            $stmt_previous->bindValue(3, $previous_end, PDO::PARAM_STR);
            $stmt_previous->execute();
            $previous_stats = $stmt_previous->fetch(PDO::FETCH_ASSOC);

            // Calculate accuracy
            $current_accuracy = $current_stats['total_questions'] > 0 
                ? round(($current_stats['correct_answers'] / $current_stats['total_questions']) * 100, 2) 
                : 0;
            $previous_accuracy = $previous_stats['total_questions'] > 0 
                ? round(($previous_stats['correct_answers'] / $previous_stats['total_questions']) * 100, 2) 
                : 0;

            // Calculate time in hours
            $current_time = round($current_stats['total_time'] / 3600, 2);
            $previous_time = round($previous_stats['total_time'] / 3600, 2);

            // Get current rank (simplified - just count users with better average)
            $sql_rank = "SELECT COUNT(*) + 1 as user_rank
                        FROM (
                            SELECT user_id, AVG(CASE WHEN is_correct = 1 THEN 100 ELSE 0 END) as avg_score
                            FROM user_question
                            WHERE DATE(created_at) >= ?
                            GROUP BY user_id
                            HAVING AVG(CASE WHEN is_correct = 1 THEN 100 ELSE 0 END) > (
                                SELECT AVG(CASE WHEN is_correct = 1 THEN 100 ELSE 0 END)
                                FROM user_question
                                WHERE user_id = ? AND DATE(created_at) >= ?
                            )
                        ) as ranked_users";
            
            $stmt_rank = $pdo->prepare($sql_rank);
            $stmt_rank->bindValue(1, $current_start, PDO::PARAM_STR);
            $stmt_rank->bindValue(2, $user_id, PDO::PARAM_INT);
            $stmt_rank->bindValue(3, $current_start, PDO::PARAM_STR);
            $stmt_rank->execute();
            $rank_data = $stmt_rank->fetch(PDO::FETCH_ASSOC);
            $current_rank = $rank_data['user_rank'];

            // Get previous rank
            $stmt_rank_prev = $pdo->prepare($sql_rank);
            $stmt_rank_prev->bindValue(1, $previous_start, PDO::PARAM_STR);
            $stmt_rank_prev->bindValue(2, $user_id, PDO::PARAM_INT);
            $stmt_rank_prev->bindValue(3, $previous_start, PDO::PARAM_STR);
            $stmt_rank_prev->execute();
            $rank_data_prev = $stmt_rank_prev->fetch(PDO::FETCH_ASSOC);
            $previous_rank = $rank_data_prev['user_rank'];

            // Calculate changes
            $accuracy_change = round($current_accuracy - $previous_accuracy, 2);
            $tests_change = $current_stats['tests_taken'] - $previous_stats['tests_taken'];
            $rank_change = $previous_rank - $current_rank; // Positive = improved (rank decreased)
            $time_change = round($current_time - $previous_time, 2);

            // Build result
            $result = array(
                'period_days' => intval($days),
                'accuracy_trend' => array(
                    'current' => floatval($current_accuracy),
                    'previous' => floatval($previous_accuracy),
                    'change' => floatval($accuracy_change),
                    'trend' => $accuracy_change >= 0 ? 'up' : 'down'
                ),
                'tests_trend' => array(
                    'current' => intval($current_stats['tests_taken']),
                    'previous' => intval($previous_stats['tests_taken']),
                    'change' => intval($tests_change),
                    'trend' => $tests_change >= 0 ? 'up' : 'down'
                ),
                'rank_trend' => array(
                    'current' => intval($current_rank),
                    'previous' => intval($previous_rank),
                    'change' => intval($rank_change), // Positive = improvement
                    'trend' => $rank_change >= 0 ? 'up' : 'down'
                ),
                'time_trend' => array(
                    'current' => floatval($current_time),
                    'previous' => floatval($previous_time),
                    'change' => floatval($time_change),
                    'trend' => $time_change >= 0 ? 'up' : 'down'
                )
            );

            $response = $this->get_success_response($result, "Performance trends retrieved successfully");
            $this->set_output($response);

        } catch (Exception $e) {
            log_message('error', "Performance trends error: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to retrieve performance trends: " . $e->getMessage());
            $this->set_output($response);
        }
    }

}
