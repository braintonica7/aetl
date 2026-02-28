<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Wizi_quiz_question extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    /**
     * Get questions for a specific quiz or get all quiz-question mappings
     * GET /api/wizi_quiz_question?wizi_quiz_id=123
     * GET /api/wizi_quiz_question/:id (single record)
     */
    function index_get($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return;
        }

        $this->load->model('wizi_quiz/wizi_quiz_question_model');

        if ($id == NULL) {
            // Check if filtering by quiz
            $wizi_quiz_id = $this->input->get('wizi_quiz_id', true);
            
            if ($wizi_quiz_id) {
                // Get all questions for a specific quiz with question details
                $questions = $this->wizi_quiz_question_model->get_questions_by_quiz($wizi_quiz_id);
                $response = $this->get_success_response($questions, 'Questions for quiz');
                $response['total'] = count($questions);
                $this->set_output($response);
            } else {
                // Get paginated list of all quiz-question mappings
                $pageSize = $this->input->get('pagesize', true);
                $page = $this->input->get('page', true);
                $sortBy = $this->input->get('sortby', true);
                $sortOrder = $this->input->get('sortorder', true);

                if (empty($pageSize))
                    $pageSize = 25;

                if (empty($page))
                    $page = 1;

                if (empty($sortBy))
                    $sortBy = "id";

                if (empty($sortOrder))
                    $sortOrder = 'desc';

                $offset = ($page - 1) * $pageSize;
                
                // Get total count
                $sql = "SELECT COUNT(id) as cnt FROM wizi_quiz_question";
                $pdo = CDatabase::getPdo();
                $statement = $pdo->prepare($sql);
                $statement->execute();
                $row = $statement->fetch();
                $totalCount = $row['cnt'];
                $statement = NULL;
                $pdo = NULL;

                // Get paginated records (cast to int for LIMIT/OFFSET)
                $pageSize = (int)$pageSize;
                $offset = (int)$offset;
                $sql = "SELECT wqq.*, wz.name as quiz_name, wq.question_img_url, wq.question_text 
                        FROM wizi_quiz_question wqq
                        JOIN wizi_quiz wz ON wqq.wizi_quiz_id = wz.id
                        LEFT JOIN wizi_question wq ON wqq.wizi_question_id = wq.id
                        ORDER BY wqq.{$sortBy} {$sortOrder}
                        LIMIT {$pageSize} OFFSET {$offset}";
                
                $pdo = CDatabase::getPdo();
                $statement = $pdo->prepare($sql);
                $statement->execute();
                
                $records = array();
                while ($row = $statement->fetch()) {
                    $obj = new stdClass();
                    $obj->id = $row['id'];
                    $obj->wizi_quiz_id = $row['wizi_quiz_id'];
                    $obj->wizi_question_id = $row['wizi_question_id'];
                    $obj->question_order = $row['question_order'];
                    $obj->marks = $row['marks'];
                    $obj->negative_marks = $row['negative_marks'];
                    $obj->quiz_name = $row['quiz_name'];
                    $obj->question_img_url = isset($row['question_img_url']) ? $row['question_img_url'] : '';
                    $obj->question_text = isset($row['question_text']) ? $row['question_text'] : '';
                    $records[] = $obj;
                }
                $statement = NULL;
                $pdo = NULL;

                $response = $this->get_success_response($records, 'Quiz questions list');
                $response['total'] = $totalCount;
                $this->set_output($response);
            }
        } else {
            // Get single record
            $objQuizQuestion = $this->wizi_quiz_question_model->get_wizi_quiz_question($id);
            if ($objQuizQuestion == NULL) {
                $response = $this->get_failed_response(NULL, "Quiz question not found..!");
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objQuizQuestion, "Quiz question details..!");
                $this->set_output($response);
            }
        }
    }

    /**
     * Bulk add questions to a quiz
     * POST /api/wizi_quiz_question/bulk_add
     * Body: {
     *   "wizi_quiz_id": 123,
     *   "question_ids": [1,2,3,4,5],
     *   "marks": 4,
     *   "negative_marks": -1.0
     * }
     */
    function bulk_add_post() {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return;
        }

        $request = $this->get_request();

        // Validate required fields
        if (!isset($request['wizi_quiz_id']) || !isset($request['question_ids']) || empty($request['question_ids'])) {
            $response = $this->get_failed_response(NULL, "wizi_quiz_id and question_ids are required");
            $this->set_output($response);
            return;
        }

        $wizi_quiz_id = $request['wizi_quiz_id'];
        $question_ids = $request['question_ids'];
        $marks = isset($request['marks']) ? $request['marks'] : 4;
        $negative_marks = isset($request['negative_marks']) ? $request['negative_marks'] : -1.0;

        if (!is_array($question_ids)) {
            $response = $this->get_failed_response(NULL, "question_ids must be an array");
            $this->set_output($response);
            return;
        }

        $this->load->model('wizi_quiz/wizi_quiz_question_model');
        
        $result = $this->wizi_quiz_question_model->bulk_add_wizi_quiz_questions(
            $wizi_quiz_id,
            $question_ids,
            $marks,
            $negative_marks
        );

        if ($result['success']) {
            $response = $this->get_success_response($result, "Questions added successfully");
            $this->set_output($response);
        } else {
            $response = $this->get_failed_response($result, "Failed to add questions");
            $this->set_output($response);
        }
    }

    /**
     * Update marks/negative_marks for a quiz question
     * PUT /api/wizi_quiz_question/:id
     */
    function index_put($id) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return;
        }

        $request = $this->get_request();

        $this->load->model('wizi_quiz/wizi_quiz_question_model');
        $objQuizQuestionOriginal = $this->wizi_quiz_question_model->get_wizi_quiz_question($id);

        if ($objQuizQuestionOriginal == NULL) {
            $response = $this->get_failed_response(NULL, "Quiz question not found..!");
            $this->set_output($response);
            return;
        }

        $objQuizQuestion = new Wizi_quiz_question_object();
        $objQuizQuestion->id = $objQuizQuestionOriginal->id;
        $objQuizQuestion->wizi_quiz_id = isset($request['wizi_quiz_id']) ? $request['wizi_quiz_id'] : $objQuizQuestionOriginal->wizi_quiz_id;
        $objQuizQuestion->wizi_question_id = isset($request['wizi_question_id']) ? $request['wizi_question_id'] : $objQuizQuestionOriginal->wizi_question_id;
        $objQuizQuestion->question_order = isset($request['question_order']) ? $request['question_order'] : $objQuizQuestionOriginal->question_order;
        $objQuizQuestion->marks = isset($request['marks']) ? $request['marks'] : $objQuizQuestionOriginal->marks;
        $objQuizQuestion->negative_marks = isset($request['negative_marks']) ? $request['negative_marks'] : $objQuizQuestionOriginal->negative_marks;

        $objQuizQuestion = $this->wizi_quiz_question_model->update_wizi_quiz_question($objQuizQuestion);
        if ($objQuizQuestion === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating quiz question...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objQuizQuestion, "Quiz question updated successfully...!");
            $this->set_output($response);
        }
    }

    /**
     * Remove a question from quiz
     * DELETE /api/wizi_quiz_question/:id
     */
    function index_delete($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return;
        }

        if ($id != NULL) {
            $this->load->model('wizi_quiz/wizi_quiz_question_model');
            $deleted = $this->wizi_quiz_question_model->delete_wizi_quiz_question($id);
            if ($deleted !== FALSE) {
                $response = $this->get_success_response($id, "Question removed from quiz successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "Failed to remove question from quiz...!");
                $this->set_output($response);
            }
        }
    }

    /**
     * Get available questions for adding to quiz (excludes already added questions)
     * GET /api/wizi_quiz_question/available?wizi_quiz_id=123&filters...
     */
    function available_get() {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return;
        }

        $wizi_quiz_id = $this->input->get('wizi_quiz_id', true);
        if (empty($wizi_quiz_id)) {
            $response = $this->get_failed_response(NULL, "wizi_quiz_id is required");
            $this->set_output($response);
            return;
        }

        // Get filters
        $exam_id = $this->input->get('exam_id', true);
        $subject_id = $this->input->get('subject_id', true);
        $chapter_id = $this->input->get('chapter_id', true);
        $topic_id = $this->input->get('topic_id', true);
        $level = $this->input->get('level', true);
        $question_type = $this->input->get('question_type', true);
        $year = $this->input->get('year', true);
        $id_start = $this->input->get('id_start', true);
        $id_end = $this->input->get('id_end', true);
        $exclude_invalid = $this->input->get('exclude_invalid', true);
        
        // Pagination
        $pageSize = $this->input->get('pagesize', true);
        $page = $this->input->get('page', true);

        if (empty($pageSize))
            $pageSize = 50;

        if (empty($page))
            $page = 1;

        if (empty($exclude_invalid))
            $exclude_invalid = true;

        $offset = ($page - 1) * $pageSize;

        // Build SQL query
        $sql = "SELECT wq.* FROM wizi_question wq 
                WHERE wq.id NOT IN (
                    SELECT wizi_question_id FROM wizi_quiz_question WHERE wizi_quiz_id = ?
                )";
        $params = array($wizi_quiz_id);

        if ($exclude_invalid) {
            $sql .= " AND (wq.invalid_question = 0 OR wq.invalid_question IS NULL)";
        }

        if (!empty($exam_id)) {
            $sql .= " AND wq.exam_id = ?";
            $params[] = $exam_id;
        }

        if (!empty($subject_id)) {
            $sql .= " AND wq.subject_id = ?";
            $params[] = $subject_id;
        }

        if (!empty($chapter_id)) {
            $sql .= " AND wq.chapter_id = ?";
            $params[] = $chapter_id;
        }

        if (!empty($topic_id)) {
            $sql .= " AND wq.topic_id = ?";
            $params[] = $topic_id;
        }

        if (!empty($level)) {
            $sql .= " AND wq.level = ?";
            $params[] = $level;
        }

        if (!empty($question_type)) {
            $sql .= " AND wq.question_type = ?";
            $params[] = $question_type;
        }

        if (!empty($year)) {
            $sql .= " AND wq.year = ?";
            $params[] = $year;
        }

        if (!empty($id_start) && !empty($id_end)) {
            $sql .= " AND wq.id BETWEEN ? AND ?";
            $params[] = $id_start;
            $params[] = $id_end;
        } elseif (!empty($id_start)) {
            $sql .= " AND wq.id >= ?";
            $params[] = $id_start;
        } elseif (!empty($id_end)) {
            $sql .= " AND wq.id <= ?";
            $params[] = $id_end;
        }

        // Get total count
        $countSql = "SELECT COUNT(*) as cnt FROM (" . $sql . ") as subquery";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($countSql);
        $statement->execute($params);
        $row = $statement->fetch();
        $totalCount = $row['cnt'];
        $statement = NULL;

        // Add pagination (use direct values for LIMIT/OFFSET to avoid PDO string binding issue)
        $pageSize = (int)$pageSize;
        $offset = (int)$offset;
        $sql .= " ORDER BY wq.id ASC LIMIT {$pageSize} OFFSET {$offset}";

        log_message('debug', 'SQL Query: ' . $sql . ' with params: ' . json_encode($params));
        
        // Execute query
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        
        $questions = array();
        while ($row = $statement->fetch()) {
            $obj = new stdClass();
            $obj->id = $row['id'];
            $obj->question_img_url = $row['question_img_url'];
            $obj->question_text = isset($row['question_text']) ? $row['question_text'] : '';
            $obj->exam_id = $row['exam_id'];
            $obj->subject_id = $row['subject_id'];
            $obj->subject_name = isset($row['subject_name']) ? $row['subject_name'] : '';
            $obj->chapter_id = $row['chapter_id'];
            $obj->chapter_name = isset($row['chapter_name']) ? $row['chapter_name'] : '';
            $obj->topic_id = $row['topic_id'];
            $obj->topic_name = isset($row['topic_name']) ? $row['topic_name'] : '';
            $obj->level = $row['level'];
            $obj->question_type = isset($row['question_type']) ? $row['question_type'] : 'mcq';
            $obj->year = isset($row['year']) ? $row['year'] : 2025;
            $obj->duration = $row['duration'];
            $obj->option_count = $row['option_count'];
            $obj->correct_option = $row['correct_option'];
            $questions[] = $obj;
        }
        $statement = NULL;
        $pdo = NULL;

        $response = $this->get_success_response($questions, 'Available questions');
        $response['total'] = $totalCount;
        $this->set_output($response);
    }
}
