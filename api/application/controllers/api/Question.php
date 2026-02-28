<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Question extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    function index_get($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(false); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('question/question_model');
        $recordCount = $this->question_model->get_question_count();

        if ($id == NULL) {
            //give multiple records...
            $pageSize = $this->input->get('pagesize', true);
            $page = $this->input->get('page', true);
            $sortBy = $this->input->get('sortby', true);
            $sortOrder = $this->input->get('sortorder', true);
            $objFilter = $this->input->get('filter', true);
            $multipleIds = $this->input->get('mid', true);
            $language = $this->input->get('language', true);

          //  echo " sortBy = $sortBy";
          
            if ($pageSize == 30)
                $pageSize = 200;
                
            if (empty($pageSize))
                $pageSize = 10;

            if (empty($page))
                $page = 1;

            if (empty($sortBy)){
               $sortBy = "id";
            }
          	   
          if ($sortBy == "id"){
              $sortBy = "id";
              $sortOrder = 'desc';
          } else {
             if (empty($sortOrder))
                $sortOrder = 'desc';
          }


            if (empty($objFilter))
                $objFilter = NULL;
            else
                $objFilter = json_decode($objFilter);

            if (empty($multipleIds))
                $multipleIds = "";
            else
                $multipleIds = trim($multipleIds);
            if (CUtility::endsWith($multipleIds, ",")) {
                $multipleIds = substr($multipleIds, 0, strlen($multipleIds) - 1);
            }
          
            $filterString = "";
            if ($objFilter != NULL) {
                foreach ($objFilter as $key => $value) {
                    if (
                            CUtility::endsWith($key, "=") ||
                            CUtility::endsWith($key, "!=") ||
                            CUtility::endsWith($key, ">") ||
                            CUtility::endsWith($key, ">=") ||
                            CUtility::endsWith($key, "<") ||
                            CUtility::endsWith($key, "<=")
                    )
                        $filterString .= $key . $value . " and ";
                    else
                        $filterString .= $key . " like('%" . $value . "%') and ";
                }
                $filterString = substr($filterString, 0, strlen($filterString) - 5);
            }
         
            if (strlen($multipleIds) > 0) {
                if (strlen($filterString) == 0)
                    $filterString = "id in (" . $multipleIds . ")";
                else
                    $filterString .= " and id in (" . $multipleIds . ")";
            }
            
            // Add language filtering
            if (!empty($language)) {
                // Validate language parameter (only 'en' or 'hi' allowed)
                if ($language === 'en' || $language === 'hi') {
                    if (strlen($filterString) == 0)
                        $filterString = "language = '" . $language . "'";
                    else
                        $filterString .= " and language = '" . $language . "'";
                }
            }

            $totalNoOfPages = intdiv($recordCount, $pageSize);
            $remainder = $recordCount % $pageSize;
            if ($remainder > 0)
                $totalNoOfPages++;

            $offset = ($page - 1) * $pageSize;
            $questions = $this->question_model->get_paginated_question($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($questions) > 0) {
                $response = $this->get_success_response($questions, 'question page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = $recordCount;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($questions, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            $objquestion = $this->question_model->get_question($id);
            if ($objquestion == NULL) {
                $response = $this->get_failed_response(NULL, "question not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objquestion, "question details..!");
                $this->set_output($response);
            }
        }
    }

    function index_post() {
        // ✅ SECURE: Require JWT authentication with admin privileges
        // $objUser = $this->require_jwt_auth(true); // true = admin required
        // if (!$objUser) {
        //     return; // Error response already sent by require_jwt_auth()
        // }

        $request = $this->get_request();

        $objquestion = new question_object();
        $objquestion->id = 0;
         $objquestion->question_img_url = $request['question_img_url'];
        $objquestion->has_multiple_answer = 0; //$request['has_multiple_answer'];
        $objquestion->duration = $request['duration'];
        $objquestion->option_count = $request['option_count'];
        $objquestion->exam_id = $request['exam_id'];
        $objquestion->subject_id = $request['subject_id'];
        $objquestion->chapter_id = $request['chapter_id'];
        $objquestion->topic_id = $request['topic_id'];
        $objquestion->level = $request['level'];
        $objquestion->correct_option = $request['correct_option'];
        $objquestion->year = isset($request['year']) ? $request['year'] : 2025;
        $objquestion->question_type = isset($request['question_type']) ? $request['question_type'] : 'regular';
		$objquestion->language = isset($request['language']) ? $request['language'] : 'en';

		//log_message('debug', 'Question::index_post - Creating question with data: ' . json_encode($objquestion));

        $this->load->model('question/question_model');
		// log if model is loaded
		//log_message('debug', 'Question::index_post - Question model loaded successfully');

        $objquestion = $this->question_model->add_question($objquestion);
		// log the result of add_question
		//log_message('debug', 'Question::index_post - add_question result: ' . json_encode($objquestion));

        if ($objquestion === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating question...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objquestion, "question created successfully...!");
            $this->set_output($response);
        }
    }

    function index_put($id) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();

        $this->load->model('question/question_model');
        $objquestionOriginal = $this->question_model->get_question($id);

        $objquestion = new question_object();
        $objquestion->id = $objquestionOriginal->id;
        $objquestion->question_img_url = $request['question_img_url'];
        $objquestion->has_multiple_answer = 0; //$request['has_multiple_answer'];
        $objquestion->duration = $request['duration'];
        $objquestion->option_count = $request['option_count'];
        $objquestion->exam_id = $request['exam_id'];
        $objquestion->subject_id = $request['subject_id'];
        $objquestion->chapter_id = $request['chapter_id'];
        $objquestion->topic_id = $request['topic_id'];
        $objquestion->level = $request['level'];
        $objquestion->correct_option = $request['correct_option'];
        $objquestion->year = isset($request['year']) ? $request['year'] : $objquestionOriginal->year;
        $objquestion->question_type = isset($request['question_type']) ? $request['question_type'] : $objquestionOriginal->question_type;

        $objquestion = $this->question_model->update_question($objquestion);
        if ($objquestion === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating question...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objquestion, "question updated successfully...!");
            $this->set_output($response);
        }
    }

    function solution_post($id) {
        $request = $this->get_request();

        $this->load->model('question/question_model');
        $objquestionOriginal = $this->question_model->get_question($id);

        $objquestion = new question_object();
        $objquestion->id = $objquestionOriginal->id;
        $objquestion->solution = $request['solution'];

        $objquestion = $this->question_model->update_solution($objquestion);
        if ($objquestion === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating question...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objquestion, "question updated successfully...!");
            $this->set_output($response);
        }
    }

    function index_delete($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if ($id != NULL) {
            $this->load->model('question/question_model');
            $deleted = $this->question_model->delete_question($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "question deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "question deletion failed...!");
                $this->set_output($response);
            }
        }
    }

    /**
     * Get questions with analysis fields for admin analysis view
     * GET /api/question/analysis
     */
    public function analysis_get() {
        try {
            $this->load->model('question/question_model');
            
            // Get pagination parameters
            $pageSize = $this->input->get('pagesize', true) ?: 20;
            $page = $this->input->get('page', true) ?: 1;
            $sortBy = $this->input->get('sortby', true) ?: 'id';
            $sortOrder = $this->input->get('sortorder', true) ?: 'desc';
            
            // Get filter parameters
            $objFilter = $this->input->get('filter', true);
            $filterString = "";
            
            if (!empty($objFilter)) {
                $objFilter = json_decode($objFilter);
                if ($objFilter) {
                    foreach ($objFilter as $key => $value) {
                        if (in_array($key, ['subject_id', 'chapter_id', 'exam_id', 'topic_id', 'year'])) {
                            $filterString .= "$key = '$value' and ";
                        } else if ($key === 'question_type') {
                            $filterString .= "question_type = '$value' and ";
                        } else if ($key === 'q') {
                            // Global search across text fields
                            $filterString .= "(question_text LIKE '%$value%' OR solution LIKE '%$value%' OR ai_summary LIKE '%$value%') and ";
                        } else if ($key === 'has_content') {
                            if ($value === 'true') {
                                $filterString .= "(question_text IS NOT NULL AND question_text != '' AND ai_summary IS NOT NULL AND ai_summary != '' AND solution IS NOT NULL AND solution != '') and ";
                            } else if ($value === 'false') {
                                $filterString .= "(question_text IS NULL OR question_text = '' OR ai_summary IS NULL OR ai_summary = '' OR solution IS NULL OR solution = '') and ";
                            }
                        } else {
                            $filterString .= "$key LIKE '%$value%' and ";
                        }
                    }
                    if (substr($filterString, -5) === ' and ') {
                        $filterString = substr($filterString, 0, -5);
                    }
                }
            }
            
            // Get total count for pagination
            $totalCount = $this->get_analysis_count($filterString);
            
            // Calculate pagination
            $offset = ($page - 1) * $pageSize;
            
            // Get questions with analysis data
            $questions = $this->get_analysis_questions($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            
            $response = array(
                'status' => 'success',
                'result' => $questions,
                'message' => 'Questions analysis data retrieved successfully',
                'total' => $totalCount
            );
            
            $this->set_output($response);
            
        } catch (Exception $e) {
+            log_message('error', "Question analysis error: " . $e->getMessage());
            $response = array(
                'status' => 'error',
                'result' => null,
                'message' => 'Failed to retrieve questions analysis: ' . $e->getMessage()
            );
            $this->set_output($response);
        }
    }
    
    /**
     * Get analysis questions with specified fields
     */
    private function get_analysis_questions($offset, $pageSize, $sortBy, $sortOrder, $filterString) {
        $pdo = CDatabase::getPdo();
        
        $whereClause = "";
        if (!empty($filterString)) {
            $whereClause = "WHERE " . $filterString;
        }
        
        $sql = "SELECT 
                    q.id,
                    q.question_img_url,
                    q.question_text,
                    q.solution,
                    q.ai_summary,
                    q.summary_generated_at,
                    q.subject_id,
                    q.chapter_id,
                    q.exam_id,
                    q.topic_id,
                    s.subject as subject_name,
                    c.chapter_name,
                    e.exam_name,
                    t.topic_name
                FROM question q
                LEFT JOIN subject s ON q.subject_id = s.id
                LEFT JOIN chapter c ON q.chapter_id = c.id
                LEFT JOIN exam e ON q.exam_id = e.id
                LEFT JOIN topic t ON q.topic_id = t.id
                $whereClause
                ORDER BY q.$sortBy $sortOrder
                LIMIT $offset, $pageSize";
        
        $statement = $pdo->prepare($sql);
        $statement->execute();
        
        $results = array();
        while ($row = $statement->fetch()) {
            $results[] = array(
                'id' => intval($row['id']),
                'question_img_url' => $row['question_img_url'],
                'question_text' => $row['question_text'],
                'solution' => $row['solution'],
                'ai_summary' => $row['ai_summary'],
                'summary_generated_at' => $row['summary_generated_at'],
                'subject_id' => intval($row['subject_id']),
                'chapter_id' => intval($row['chapter_id']),
                'exam_id' => intval($row['exam_id']),
                'topic_id' => intval($row['topic_id']),
                'subject_name' => $row['subject_name'],
                'chapter_name' => $row['chapter_name'],
                'exam_name' => $row['exam_name'],
                'topic_name' => $row['topic_name']
            );
        }
        
        return $results;
    }
    
    /**
     * Get total count for analysis questions
     */
    private function get_analysis_count($filterString) {
        $pdo = CDatabase::getPdo();
        
        $whereClause = "";
        if (!empty($filterString)) {
            $whereClause = "WHERE " . $filterString;
        }
        
        $sql = "SELECT COUNT(*) as total FROM question q $whereClause";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        $result = $statement->fetch();
        
        return intval($result['total']);
    }

    /**
     * Flag a question as invalid
     * POST /api/questions/{id}/flag
     * Requires authentication
     * Request body: { "flag_reason": "Reason for flagging" }
     */
    function flag_post($id) {
        // Require JWT authentication
        $objUser = $this->require_jwt_auth();
        if (!$objUser) {
            return; // Authentication error already handled by require_jwt_auth
        }

        // Load the question model
        $this->load->model('question/question_model');

        // Validate question ID
        if (empty($id) || !is_numeric($id)) {
            $response = $this->get_failed_response(NULL, 'Invalid question ID provided');
            $this->set_output($response);
            return;
        }

        // Get flag_reason from request body
        $requestData = $this->get_request();
        $flag_reason = isset($requestData['flag_reason']) ? trim($requestData['flag_reason']) : "Invalid Question";

        // Validate flag_reason (optional but recommended)
        if (empty($flag_reason)) {
            $response = $this->get_failed_response(NULL, 'Flag reason is required');
            $this->set_output($response);
            return;
        }

        // Check if question exists
        if (!$this->question_model->question_exists($id)) {
            $response = $this->get_failed_response(NULL, 'Question not found');
            $this->set_output($response);
            return;
        }

        // Check if question is already flagged
        // if ($this->question_model->is_question_flagged($id)) {
        //     $response = $this->get_failed_response(NULL, 'Question is already flagged as invalid');
        //     $this->set_output($response);
        //     return;
        // }

        // Check if user has already reported this question (load history model)
        $this->load->model('question/question_status_history_model');
        if ($this->question_status_history_model->has_user_reported($objUser->id, $id)) {
            $response = $this->get_failed_response(NULL, 'You have already reported this question');
            $this->set_output($response);
            return;
        }

        // Flag the question with reason
        $flagged = $this->question_model->flag_question_as_invalid($id, $objUser->id, $flag_reason);
        
        if ($flagged) {
            $response = $this->get_success_response(
                array(
                    'question_id' => $id,
                    'flagged' => true,
                    'flagged_by' => $objUser->id,
                    'flagged_at' => date('Y-m-d H:i:s'),
                    'flag_reason' => $flag_reason
                ), 
                'Question has been flagged as invalid and will be reviewed by administrators'
            );
        } else {
            $response = $this->get_failed_response(NULL, 'Failed to flag question. Please try again.');
        }

        $this->set_output($response);
    }

    /**
     * Get all flagged questions for admin review
     * GET /api/questions/flagged
     * Requires authentication (preferably admin)
     */
    function flagged_get() {
        // Require JWT authentication 
        $objUser = $this->require_jwt_auth();
        if (!$objUser) {
            return; // Authentication error already handled
        }

        // Load the question model
        $this->load->model('question/question_model');

        // Get pagination parameters
        $pageSize = $this->input->get('pagesize', true) ?: 50;
        $page = $this->input->get('page', true) ?: 1;
        $sortBy = $this->input->get('sortby', true) ?: 'id';
        $sortOrder = $this->input->get('sortorder', true) ?: 'DESC';

        // Validate pagination parameters
        if (!is_numeric($pageSize) || $pageSize <= 0) $pageSize = 50;
        if (!is_numeric($page) || $page <= 0) $page = 1;

        // Validate sort parameters to prevent SQL injection
        $allowedSortFields = ['id', 'question_text', 'subject_id', 'level', 'reported_date'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'id';
        }
        if (!in_array(strtoupper($sortOrder), ['ASC', 'DESC'])) {
            $sortOrder = 'DESC';
        }

        // Calculate offset
        $offset = ($page - 1) * $pageSize;

        // Get flagged questions
        $flaggedQuestions = $this->question_model->get_flagged_questions($offset, $pageSize, $sortBy, $sortOrder);
        $totalCount = $this->question_model->get_flagged_questions_count();

        // Calculate pagination info
        $totalPages = ceil($totalCount / $pageSize);

        if (count($flaggedQuestions) > 0) {
            $response = $this->get_success_response($flaggedQuestions, 'Flagged questions retrieved successfully');
            $response['pagination'] = array(
                'current_page' => intval($page),
                'page_size' => intval($pageSize),
                'total_pages' => $totalPages,
                'total_records' => $totalCount,
                'has_next' => $page < $totalPages,
                'has_previous' => $page > 1
            );
        } else {
            $response = $this->get_success_response(array(), 'No flagged questions found');
            $response['pagination'] = array(
                'current_page' => 1,
                'page_size' => intval($pageSize),
                'total_pages' => 0,
                'total_records' => 0,
                'has_next' => false,
                'has_previous' => false
            );
        }

        $this->set_output($response);
    }

    /**
     * Unflag a question (mark as corrected) - Admin endpoint
     * PUT /api/questions/{id}/unflag
     * Requires admin authentication
     */
    function unflag_put($id) {
        // Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Authentication error already handled
        }

        // Load the question model
        $this->load->model('question/question_model');

        // Validate question ID
        if (empty($id) || !is_numeric($id)) {
            $response = $this->get_failed_response(NULL, 'Invalid question ID provided');
            $this->set_output($response);
            return;
        }

        // Check if question exists
        if (!$this->question_model->question_exists($id)) {
            $response = $this->get_failed_response(NULL, 'Question not found');
            $this->set_output($response);
            return;
        }

        // Check if question is actually flagged
        if (!$this->question_model->is_question_flagged($id)) {
            $response = $this->get_failed_response(NULL, 'Question is not flagged as invalid');
            $this->set_output($response);
            return;
        }

        // Unflag the question
        $unflagged = $this->question_model->unflag_question($id, $objUser->id);
        
        if ($unflagged) {
            $response = $this->get_success_response(
                array(
                    'question_id' => $id,
                    'flagged' => false,
                    'corrected_by' => $objUser->id,
                    'corrected_at' => date('Y-m-d H:i:s')
                ), 
                'Question has been unflagged and marked as corrected'
            );
        } else {
            $response = $this->get_failed_response(NULL, 'Failed to unflag question. Please try again.');
        }

        $this->set_output($response);
    }

}
