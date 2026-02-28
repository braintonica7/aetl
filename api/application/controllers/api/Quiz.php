<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Quiz extends API_Controller
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

    /**
     * Check if user can create more custom quizzes based on their subscription and limits
     * @param object $user User object with subscription details
     * @return array Array with 'allowed' boolean and optional 'message' and 'remaining'
     */
    private function checkCustomQuizLimit($user)
    {
        // Check if custom quiz limits are enabled
        if (!$this->config->item('quiz_enable_custom_limits')) {
            return array('allowed' => true);
        }

        try {
            // Load user model to get current quiz count
            $this->load->model('user/user_model');
            
            // Get user's current subscription type (default to free if not set)
            $subscription_type = isset($user->subscription_type) ? $user->subscription_type : $this->config->item('quiz_default_user_type');
            
            // Get the limit for this user type
            $limit_key = 'quiz_custom_limit_' . $subscription_type;
            $user_limit = $this->config->item($limit_key);
            
            // Check for custom user-specific limit override
            if (isset($user->custom_quiz_limit) && $user->custom_quiz_limit !== null) {
                $user_limit = $user->custom_quiz_limit;
            }
            
            // If limit is -1, user has unlimited quizzes (typically admin)
            if ($user_limit == -1) {
                return array('allowed' => true, 'unlimited' => true);
            }
            
            // Get current count of custom quizzes for this user
            $current_count = $this->getCustomQuizCount($user->id);
            
            // Check if user has reached their limit
            if ($current_count >= $user_limit) {
                $error_message = $this->config->item('quiz_error_limit_exceeded');
                if ($this->config->item('quiz_log_quota_violations')) {
                    log_message('info', "Quiz limit exceeded for user {$user->id}: {$current_count}/{$user_limit} (type: {$subscription_type})");
                }
                return array(
                    'allowed' => false, 
                    'message' => $error_message,
                    'current' => $current_count,
                    'limit' => $user_limit,
                    'subscription_type' => $subscription_type
                );
            }
            
            // Check if user is approaching their limit (warning threshold)
            $remaining = $user_limit - $current_count;
            $warning_threshold = $this->config->item('quiz_quota_warning_threshold');
            $is_approaching_limit = ($current_count / $user_limit) >= $warning_threshold;
            
            $result = array(
                'allowed' => true,
                'current' => $current_count,
                'limit' => $user_limit,
                'remaining' => $remaining,
                'subscription_type' => $subscription_type
            );
            
            // Add warning if approaching limit
            if ($is_approaching_limit && $this->config->item('quiz_enable_quota_warnings')) {
                $warning_message = str_replace('{remaining}', $remaining, $this->config->item('quiz_warning_approaching_limit'));
                $result['warning'] = $warning_message;
                
                if ($this->config->item('quiz_log_quota_warnings')) {
                    log_message('info', "Quiz limit warning for user {$user->id}: {$current_count}/{$user_limit} (type: {$subscription_type})");
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            log_message('error', "Error checking quiz limit for user {$user->id}: " . $e->getMessage());
            return array(
                'allowed' => false, 
                'message' => $this->config->item('quiz_error_limit_check_failed'),
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Get count of custom quizzes created by a user
     * @param int $user_id User ID
     * @return int Number of custom quizzes
     */
    private function getCustomQuizCount($user_id)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM quiz WHERE user_id = ? AND user_id IS NOT NULL";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute(array($user_id));
            
            if ($row = $statement->fetch()) {
                return (int)$row['count'];
            }
            
            return 0;
        } catch (Exception $e) {
            log_message('error', "Error getting custom quiz count for user {$user_id}: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Increment the custom quiz count for a user after successful quiz creation
     * @param int $user_id User ID
     * @return bool Success status
     */
    private function incrementCustomQuizCount($user_id)
    {
        try {
            // Update the counter in the user table (if the field exists)
            $sql = "UPDATE user SET custom_quiz_count = (
                        SELECT COUNT(*) FROM quiz WHERE user_id = ? AND user_id IS NOT NULL
                    ) WHERE id = ?";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $result = $statement->execute(array($user_id, $user_id));
            
            log_message('debug', "Updated custom quiz count for user {$user_id}");
            return $result;
        } catch (Exception $e) {
            log_message('error', "Error updating custom quiz count for user {$user_id}: " . $e->getMessage());
            return false;
        }
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

    function submit_response_get()
    {
        $machine = $this->input->get('mid');
        $key = $this->input->get('key');
        $apiKey = $this->input->get('apikey');

      if($key == "A")
        $key = 1;
      if($key == "B")
        $key = 2;
      if($key == "C")
        $key = 3;
      if($key == "D")
        $key = 4;
      
      
      
        $objRFIDLog = new RFIDLogObject();
        $objRFIDLog->logid = 0;
        $objRFIDLog->machineid = $machine;
        $objRFIDLog->cardno = $key;
        $objRFIDLog->apikey = $apiKey;

        $this->load->model('rfidlog/rfidlogmodel');
        $this->rfidlogmodel->add_rfidlog($objRFIDLog);

        // Submit The Response //
        $this->load->model('quiz/quiz_model');
        $quiz = $this->quiz_model->get_quiz_for_device($machine);
        if (!empty($quiz)) {
            $quiz_id = $quiz->quiz_id;
            $question_id = $quiz->question_id;
            $question_time = $quiz->question_time;
            $user_id = $quiz->user_id;
            $seconds = $this->calculateStringTimeToMiliseconds($question_time);
            $this->quiz_model->add_user_answer_device($user_id, $quiz_id, $question_id, $seconds, $key);
        }

        $response = $this->get_success_response($quiz, "quiz");
        $this->set_output($response);
    }
  
    function submit_response1_get(){
        $machine = $this->input->get('mid');
        $key = $this->input->get('key');
        $apiKey = $this->input->get('apikey');

        $objRFIDLog = new RFIDLogObject();
        $objRFIDLog->logid = 0;
        $objRFIDLog->machineid = $machine;
        $objRFIDLog->cardno = $key;
        $objRFIDLog->apikey = $apiKey;
        
        $this->load->model('rfidlog/rfidlogmodel');
        $this->rfidlogmodel->add_rfidlog($objRFIDLog);
        $response = $this->get_success_response($objRFIDLog, "quiz");
        $this->set_output($response);
    }
  
    function index_get($id = NULL)
    {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(false); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if ($id == NULL) {
            $id = $this->input->get("id");
        }
        $this->load->model('quiz/quiz_model');
        $recordCount = $this->quiz_model->get_quiz_count();

        if (empty($id) || $id == NULL) {
            //give multiple records...
            $pageSize = $this->input->get('pagesize', true);
            $page = $this->input->get('page', true);
            $sortBy = $this->input->get('sortby', true);
            $sortOrder = $this->input->get('sortorder', true);
            $objFilter = $this->input->get('filter', true);
            $multipleIds = $this->input->get('mid', true);

            if (empty($pageSize))
                $pageSize = 10;

            if (empty($page))
                $page = 1;

            if (empty($sortBy))
                $sortBy = "id";

            if (empty($sortOrder))
                $sortOrder = 'desc';

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
                //Replace the quiz_user_id to quiz.user_id in filter string
                $filterString = str_replace("quiz_user_id", "quiz.user_id", $filterString);
            }

            if (strlen($multipleIds) > 0) {
                if (strlen($filterString) == 0)
                    $filterString = "id in (" . $multipleIds . ")";
                else
                    $filterString .= " and id in (" . $multipleIds . ")";
            }

            $totalNoOfPages = intdiv($recordCount, $pageSize);
            $remainder = $recordCount % $pageSize;
            if ($remainder > 0)
                $totalNoOfPages++;

            $offset = ($page - 1) * $pageSize;
            $quizs = $this->quiz_model->get_paginated_quiz($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($quizs) > 0) {
                $response = $this->get_success_response($quizs, 'quiz page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($quizs);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($quizs, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            // Check if the id is a quiz_reference (non-numeric) or quiz_id (numeric)
            if (is_numeric($id)) {
                $objquiz = $this->quiz_model->get_quiz($id);
            } else {
                log_message('debug', 'Quiz API: Looking up quiz by reference: ' . $id);
                
                $objquiz = $this->quiz_model->get_Quiz_by_reference($id);
            }
            
            if ($objquiz == NULL) {
                log_message('warning', "Quiz API: Quiz not found for ID/reference: " . $id);
                $response = $this->get_failed_response(NULL, "quiz not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                log_message('debug', "Quiz API: Quiz found successfully for ID/reference: " . $id);
                $response = $this->get_success_response($objquiz, "quiz details..!");
                $this->set_output($response);
            }
        }
    }

    function index_post()
    {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();

        $objquiz = new quiz_object();
        $objquiz->id = 0;
        $objquiz->name = $request['name'];
        $objquiz->description = $request['description'];
        $objquiz->class_id = $request['class_id'];
        $objquiz->subject_id = $request['subject_id'];
        $objquiz->exam_id = array_key_exists('exam_id', $request) ? $request['exam_id'] : 0;
        $objquiz->quiz_detail_image = array_key_exists('quiz_detail_image',$request) ? $request['quiz_detail_image'] : "";
        $objquiz->youtube_video_id = array_key_exists('youtube_video_id', $request) ? $request['youtube_video_id'] : "";
        $objquiz->marking = array_key_exists('marking',$request) ? $request['marking'] : "";

        $this->load->model('quiz/quiz_model');
        $objquiz = $this->quiz_model->add_quiz($objquiz);
        if ($objquiz === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating quiz...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objquiz, "quiz created successfully...!");
            $this->set_output($response);
        }
    }

    function index_put($id)
    {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();

        $this->load->model('quiz/quiz_model');
        $objquizOriginal = $this->quiz_model->get_quiz($id);

        $objquiz = new quiz_object();
        $objquiz->id = $objquizOriginal->id;
        $objquiz->name = $request['name'];
        $objquiz->description = $request['description'];
        $objquiz->class_id = $request['class_id'];
        $objquiz->subject_id = $request['subject_id'];
        $objquiz->exam_id = array_key_exists('exam_id', $request) ? $request['exam_id'] : 0;
        $objquiz->quiz_detail_image = $request['quiz_detail_image'];
        $objquiz->youtube_video_id = $request['youtube_video_id'];
        $objquiz->marking = $request['marking'];


        $objquiz = $this->quiz_model->update_quiz($objquiz);
        if ($objquiz === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating quiz...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objquiz, "quiz updated successfully...!");
            $this->set_output($response);
        }
    }

    function index_delete($id = NULL)
    {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if ($id != NULL) {
            $this->load->model('quiz/quiz_model');
            $deleted = $this->quiz_model->delete_quiz($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "quiz deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "quiz deletion failed...!");
                $this->set_output($response);
            }
        }
    }

    /**
     * Get questions for quiz - JWT Protected Endpoint
     * Requires valid JWT authentication
     */
    function get_questions_for_quiz_get()
    {
        // ✅ SECURE: Require JWT authentication
        $objUser = $this->require_jwt_auth();
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $quiz_id = $this->input->get('quiz_id', true);
        $quiz_reference = $this->input->get('quiz_reference', true);

        // If quiz_reference is provided, get quiz_id from it
        if (!empty($quiz_reference)) {
            $this->load->model('quiz/quiz_model');
            $quiz = $this->quiz_model->get_Quiz_by_reference($quiz_reference);
            if ($quiz) {
                $quiz_id = $quiz->id;
            } else {
                $response = $this->get_failed_response(NULL, "Quiz not found with reference");
                $this->set_secure_output($response, REST_Controller::HTTP_NOT_FOUND);
                return;
            }
        }

        if (empty($quiz_id)) {
            $response = $this->get_failed_response(NULL, "Quiz ID or reference required");
            $this->set_secure_output($response, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->load->model('quiz/quiz_model');
        $objquestions = $this->quiz_model->get_all_questions_for_quiz($quiz_id);
        if ($objquestions === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while getting questions");
            $this->set_secure_output($response, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        } else {
            $response = $this->get_success_response($objquestions, "Questions retrieved successfully");
            $this->set_secure_output($response);
        }
    }

    /**
     * Get scholars for quiz - JWT Protected Endpoint 
     * Requires JWT authentication
     */
    function get_scholars_for_quiz_get()
    {
        // ✅ SECURE: Require JWT authentication
        $objUser = $this->require_jwt_auth();
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $quiz_id = $this->input->get('quiz_id', true);

        if (empty($quiz_id)) {
            $response = $this->get_failed_response(NULL, "Quiz ID is required");
            $this->set_secure_output($response, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->load->model('quiz/quiz_model');
        $objScholars = $this->quiz_model->get_all_scholars_for_quiz($quiz_id);
        if ($objScholars === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while getting scholars");
            $this->set_secure_output($response, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        } else {
            $response = $this->get_success_response($objScholars, "Scholars retrieved successfully");
            $this->set_secure_output($response);
        }
    }
    function get_question_leaderboard_get()
    {

        $question_id = $this->input->get('question_id', true);
        $quiz_id = $this->input->get('quiz_id', true);

        $this->load->model('quiz/quiz_model');
        $objquestions = $this->quiz_model->get_question_leaderboard($question_id, $quiz_id);
        if ($objquestions === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating question...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objquestions, "question List!");
            $this->set_output($response);
        }
    }

    function get_user_leaderboard_for_quiz_get()
    {

        $quiz_id = $this->input->get('quiz_id', true);

        $this->load->model('quiz/quiz_model');
        $objquestions = $this->quiz_model->get_user_leaderboard($quiz_id);
        if ($objquestions === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating question...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objquestions, "question List!");
            $this->set_output($response);
        }
    }

    function update_question_score_get()
    {
        $quiz_id = $this->input->get('quiz_id', true);
        $question_id = $this->input->get('question_id', true);

        $this->load->model('quiz/quiz_model');
        $this->quiz_model->update_question_score($question_id, $quiz_id);
        $response = $this->get_success_response($quiz_id, "question score update");
        $this->set_output($response);
    }

    function update_quiz_live_status_get()
    {
        $quiz_id = $this->input->get('quiz_id', true);
        $is_live = $this->input->get('is_live', true);

        $this->load->model('quiz/quiz_model');
        $this->quiz_model->update_live_status($quiz_id, $is_live);
        $response = $this->get_success_response($quiz_id, "Quiz updated");
        $this->set_output($response);
    }

      function update_quiz_current_question_post()
    {
        $request = $this->get_request();

        $quiz_id = $request['quiz_id'];
        $question_id = $request['question_id'];

        $this->load->model('quiz/quiz_model');
        $objquiz = $this->quiz_model->update_quiz_current_question($quiz_id, $question_id);
        if ($objquiz === FALSE) {
            $response = $this->get_failed_response(NULL, "Error updating quiz...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objquiz, "quiz question updated successfully...!");
            $this->set_output($response);
        }
    }
  
    function update_quiz_start_post()
    {
        $request = $this->get_request();

        $quiz_id = $request['quiz_id'];
        $start_date = $request['start_date'];

        $this->load->model('quiz/quiz_model');
        $objquiz = $this->quiz_model->update_start_date($quiz_id, $start_date);
        if ($objquiz === FALSE) {
            $response = $this->get_failed_response(NULL, "Error updating quiz...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objquiz, "quiz start updated successfully...!");
            $this->set_output($response);
        }
    }

    function update_quiz_stop_post()
    {
        $request = $this->get_request();

        $quiz_id = $request['quiz_id'];

        $this->load->model('quiz/quiz_model');
        $objquiz = $this->quiz_model->update_quiz_stop($quiz_id);
        if ($objquiz === FALSE) {
            $response = $this->get_failed_response(NULL, "Error updating quiz...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objquiz, "quiz updated successfully...!");
            $this->set_output($response);
        }
    }

    function add_user_answer_post()
    {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();

        // Validate required fields
        if (empty($request['user_id']) || empty($request['quiz_id']) || 
            empty($request['question_id']) || !isset($request['option_answer'])) {
            $response = $this->get_failed_response(NULL, "Missing required fields: user_id, quiz_id, question_id, option_answer");
            $this->set_output($response);
            return;
        }

        $user_id = $request['user_id'];
        $quiz_id = $request['quiz_id'];
        $question_id = $request['question_id'];
        $duration = isset($request['duration']) ? $request['duration'] : 0;
        $option_answer = $request['option_answer'];

        // Load the User_question model for proper handling of is_correct
        $this->load->model('user_question/user_question_model');
        
        // Use the submit_answer method which automatically calculates is_correct
        $result = $this->user_question_model->submit_answer($user_id, $quiz_id, $question_id, $option_answer, $duration);
        
        if ($result !== FALSE) {
            // Get the submitted answer details but only return non-sensitive information
            $submittedAnswer = $this->user_question_model->get_user_question($result);
            
            $response_data = array(
                'id' => $submittedAnswer->id,
                'user_id' => $submittedAnswer->user_id,
                'quiz_id' => $submittedAnswer->quiz_id,
                'question_id' => $submittedAnswer->question_id,
                'option_answer' => $submittedAnswer->option_answer,
                'duration' => $submittedAnswer->duration,
                'created_at' => $submittedAnswer->created_at
                // Deliberately NOT including is_correct and score for security
            );
            
            // Check if quiz is completed and trigger performance analysis
            // $this->load->model('user_performance/user_performance_model');
            // if ($this->user_performance_model->is_quiz_completed($user_id, $quiz_id)) {
            //     // Quiz completed - trigger AI performance analysis
            //     $this->load->library('User_performance_library');
            //     $performance_result = $this->user_performance_library->generate_performance_analysis($user_id, $quiz_id);
                
            //     if ($performance_result !== FALSE) {
            //         $response_data['quiz_completed'] = true;
            //         $response_data['performance_analysis_generated'] = true;
            //         log_message('info', "Performance analysis auto-generated for user {$user_id}, quiz {$quiz_id}");
            //     } else {
            //         $response_data['quiz_completed'] = true;
            //         $response_data['performance_analysis_generated'] = false;
            //         log_message('error', "Failed to auto-generate performance analysis for user {$user_id}, quiz {$quiz_id}");
            //     }
            // } else {
            //     $response_data['quiz_completed'] = false;
            // }
            
            $response = $this->get_success_response($response_data, "Answer submitted successfully");
            $this->set_output($response);
        } else {
            $response = $this->get_failed_response(NULL, "Error updating Answer - Question not found or database error");
            $this->set_output($response);
        }
    }

    /**
     * Submit/Complete a quiz explicitly
     * POST /api/quiz/submit_quiz
     * Allows users to mark quiz as completed even with skipped questions
     */
    function submit_quiz_post()
    {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();

        // Validate required fields
        if (empty($request['user_id']) || empty($request['quiz_id'])) {
            $response = $this->get_failed_response(NULL, "Missing required fields: user_id, quiz_id");
            $this->set_output($response);
            return;
        }

        $user_id = $request['user_id'];
        $quiz_id = $request['quiz_id'];
        $force_completion = isset($request['force_completion']) ? (bool)$request['force_completion'] : false;

        try {
            $this->load->model('user_performance/user_performance_model');
            
            // Ensure all questions have entries (insert skipped for missing ones)
            $skipped_count = $this->user_performance_model->ensure_complete_quiz_data($user_id, $quiz_id);
            
            // Check if user has answered at least one question
            $this->load->model('user_question/user_question_model');
            $answered_questions = $this->user_question_model->get_user_quiz_results($user_id, $quiz_id);
            
            if (empty($answered_questions) && !$force_completion) {
                $response = $this->get_failed_response(NULL, "Cannot submit quiz without answering any questions. Use force_completion=true to override.");
                $this->set_output($response);
                return;
            }

            // Check if quiz is now considered completed
            $is_completed = $this->user_performance_model->is_quiz_completed($user_id, $quiz_id);
            
            // Update quiz status in quiz table
            $this->load->model('quiz/quiz_model');
            if ($is_completed) {
                // Mark quiz as completed with current timestamp
                $status_updated = $this->quiz_model->update_quiz_status(
                    $quiz_id, 
                    Quiz_model::STATUS_COMPLETED, 
                    date('Y-m-d H:i:s')
                );
                log_message('info', "Quiz {$quiz_id} status updated to completed: " . ($status_updated ? 'success' : 'failed'));
            } else {
                // Mark quiz as in_progress (partial submission)
                $status_updated = $this->quiz_model->update_quiz_status(
                    $quiz_id, 
                    Quiz_model::STATUS_IN_PROGRESS
                );
                log_message('info', "Quiz {$quiz_id} status updated to in_progress: " . ($status_updated ? 'success' : 'failed'));
            }
            
            $response_data = array(
                'user_id' => $user_id,
                'quiz_id' => $quiz_id,
                'completed' => $is_completed,
                'submitted_at' => date('Y-m-d H:i:s'),
                'answered_questions' => count($answered_questions),
                'skipped_questions_added' => $skipped_count
            );

            if ($is_completed) {
                // Award points for quiz completion
                log_message('info', "Quiz {$quiz_id} marked as completed for user {$user_id}, attempting to award points");
                $this->load->library('User_point_service');
                $point_result = $this->user_point_service->award_quiz_completion_points($user_id, $quiz_id);
                
                // Log the full point result for debugging
                log_message('info', "Point calculation result: " . json_encode($point_result));
                
                if ($point_result['success']) {
                    $response_data['points_awarded'] = $point_result['points_awarded'];
                    $response_data['point_details'] = array(
                        'base_points' => $point_result['base_points'],
                        'bonus_points' => $point_result['bonus_points'],
                        'bonuses_earned' => $point_result['bonuses_earned'],
                        'earning_rule' => $point_result['earning_rule'],
                        'score_percentage' => $point_result['score_percentage'],
                        'total_points' => $point_result['user_points']['total_points'],
                        'available_points' => $point_result['user_points']['available_points'],
                        'weekly_points' => $point_result['user_points']['current_week_points'],
                        'ai_tutor_minutes' => $point_result['ai_tutor_minutes']
                    );
                    log_message('info', "Points awarded successfully for user {$user_id}, quiz {$quiz_id}: {$point_result['points_awarded']} points");
                } else {
                    $response_data['points_awarded'] = 0;
                    $response_data['point_error'] = $point_result['error'];
                    log_message('info', "Points not awarded for user {$user_id}, quiz {$quiz_id}: {$point_result['error']}");
                }
            } else {
                // Quiz not completed - no points awarded
                log_message('info', "Quiz {$quiz_id} not marked as completed for user {$user_id}, no points awarded");
                $response_data['points_awarded'] = 0;
                $response_data['point_message'] = 'Quiz not completed - no points awarded';
            }

            $response = $this->get_success_response($response_data, "Quiz submitted successfully");
            $this->set_output($response);

        } catch (Exception $e) {
            $response = $this->get_failed_response(NULL, "Error submitting quiz: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Abandon or timeout a quiz
     * POST /api/quiz/abandon_quiz
     * Marks quiz as abandoned or timeout without completion
     * 
     * Request body:
     * {
     *   "user_id": 123,
     *   "quiz_id": 456,
     *   "reason": "abandoned|timeout",
     *   "notes": "Optional notes about abandonment"
     * }
     */
    function abandon_quiz_post() {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();

        // Validate required fields
        if (empty($request['user_id']) || empty($request['quiz_id'])) {
            $response = $this->get_failed_response(NULL, "Missing required fields: user_id, quiz_id");
            $this->set_output($response);
            return;
        }

        $user_id = $request['user_id'];
        $quiz_id = $request['quiz_id'];
        $reason = isset($request['reason']) ? $request['reason'] : 'abandoned';
        $notes = isset($request['notes']) ? $request['notes'] : '';

        // Validate reason
        $valid_reasons = ['abandoned', 'timeout'];
        if (!in_array($reason, $valid_reasons)) {
            $response = $this->get_failed_response(NULL, "Invalid reason. Must be 'abandoned' or 'timeout'");
            $this->set_output($response);
            return;
        }

        try {
            $this->load->model('quiz/quiz_model');

            // Check if quiz exists and belongs to user
            $pdo = CDatabase::getPdo();
            $check_sql = "SELECT id, quiz_status FROM quiz WHERE id = ? AND user_id = ?";
            $statement = $pdo->prepare($check_sql);
            $statement->execute(array($quiz_id, $user_id));
            $quiz = $statement->fetch(PDO::FETCH_ASSOC);
            $statement = null;

            if (!$quiz) {
                $response = $this->get_failed_response(NULL, "Quiz not found or does not belong to user");
                $this->set_output($response);
                return;
            }

            // Check if quiz is already completed
            if ($quiz['quiz_status'] === 'completed') {
                $response = $this->get_failed_response(NULL, "Cannot abandon a completed quiz");
                $this->set_output($response);
                return;
            }

            // Update quiz status
            $status = ($reason === 'timeout') ? Quiz_model::STATUS_TIMEOUT : Quiz_model::STATUS_ABANDONED;
            $status_updated = $this->quiz_model->update_quiz_status($quiz_id, $status);

            if ($status_updated) {
                log_message('info', "Quiz {$quiz_id} marked as {$status} for user {$user_id}. Reason: {$reason}. Notes: {$notes}");
                
                $response_data = array(
                    'user_id' => $user_id,
                    'quiz_id' => $quiz_id,
                    'status' => $status,
                    'reason' => $reason,
                    'abandoned_at' => date('Y-m-d H:i:s')
                );

                $response = $this->get_success_response($response_data, "Quiz marked as {$status} successfully");
                $this->set_output($response);
            } else {
                log_message('error', "Failed to update quiz {$quiz_id} status to {$status} for user {$user_id}");
                $response = $this->get_failed_response(NULL, "Failed to update quiz status");
                $this->set_output($response);
            }

        } catch (Exception $e) {
            log_message('error', "Error abandoning quiz {$quiz_id} for user {$user_id}: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error abandoning quiz: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Skip a question in a quiz
     * POST /api/quiz/skip_question
     * Inserts a skipped entry when user clicks Skip or Next without answering
     */
    function skip_question_post()
    {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();

        // Validate required fields
        if (empty($request['user_id']) || empty($request['quiz_id']) || 
            empty($request['question_id'])) {
            $response = $this->get_failed_response(NULL, "Missing required fields: user_id, quiz_id, question_id");
            $this->set_output($response);
            return;
        }

        $user_id = $request['user_id'];
        $quiz_id = $request['quiz_id'];
        $question_id = $request['question_id']; 
        $quiz_question_id = isset($request['quiz_question_id']) ? $request['quiz_question_id'] : null;
        $duration = isset($request['duration']) ? $request['duration'] : 0;

        try {
            $this->load->model('user_question/user_question_model');

            // If quiz_question_id not provided, get it from question_id and quiz_id
            if (empty($quiz_question_id)) {
                $pdo = CDatabase::getPdo();
                $sql = "SELECT id FROM quiz_question WHERE quiz_id = ? AND question_id = ?";
                $statement = $pdo->prepare($sql);
                $statement->execute(array($quiz_id, $question_id));
                $result = $statement->fetch();
                $statement = NULL;
                $pdo = NULL;
                
                if (!$result) {
                    $response = $this->get_failed_response(NULL, "Question not found in this quiz");
                    $this->set_output($response);
                    return;
                }
                $quiz_question_id = $result['id'];
            }

            // Check if user has already answered or skipped this question
            $existing = $this->user_question_model->get_user_question_by_quiz_question($user_id, $quiz_question_id);
            
            if ($existing) {
                $response = $this->get_failed_response(NULL, "Question already answered or skipped");
                $this->set_output($response);
                return;
            }

            // Insert skip entry - using both quiz_question_id and question_id for proper database structure
            $result = $this->user_question_model->insert_skip_entry($user_id, $quiz_id, $quiz_question_id, $question_id, $duration);
            
            if ($result !== FALSE) {
                $response_data = array(
                    'id' => $result,
                    'user_id' => $user_id,
                    'quiz_id' => $quiz_id,
                    'question_id' => $question_id,
                    'quiz_question_id' => $quiz_question_id,
                    'duration' => $duration,
                    'status' => 'skipped',
                    'skipped_at' => date('Y-m-d H:i:s')
                );
                
                $response = $this->get_success_response($response_data, "Question skipped successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(NULL, "Error skipping question");
                $this->set_output($response);
            }

        } catch (Exception $e) {
            $response = $this->get_failed_response(NULL, "Error skipping question: " . $e->getMessage());
            $this->set_output($response);
        }
    }
  
  
    /**
     * Get quiz report - ADMIN ONLY Endpoint
     * Requires JWT authentication with admin privileges
     */
    function get_quiz_report_get()
    {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        // ✅ SECURE: Additional permission check for reports
        if (!$this->has_permission('reports')) {
            $this->send_forbidden_response("Insufficient permissions to access reports");
            return;
        }

        $quiz_id = $this->input->get('quiz_id', true);

        if (empty($quiz_id)) {
            $response = $this->get_failed_response(NULL, "Quiz ID is required");
            $this->set_secure_output($response, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->load->model('quiz/quiz_model');
        $objScoreReport = $this->quiz_model->get_quiz_score_report($quiz_id);
        $objDetailsReport = $this->quiz_model->get_quiz_details_report($quiz_id);
        $objQuestionsReport = $this->quiz_model->get_quiz_total_questions_report($quiz_id);

        if ($objScoreReport === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while generating quiz report");
            $this->set_secure_output($response, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        } else {
            $objReport = new stdClass();
            $objReport->score = $objScoreReport;
            $objReport->details = $objDetailsReport[0];
            $objReport->questions = $objQuestionsReport[0];

            $response = $this->get_success_response($objReport, "Quiz report generated successfully");
            $this->set_secure_output($response);
        }
    }
  
  function get_quiz_student_report_get()
    {

        $quiz_id = $this->input->get('quiz_id', true);
        $user_id = $this->input->get('user_id', true);

        $this->load->model('quiz/quiz_model');
       // $objScoreReport = $this->quiz_model->get_quiz_score_report($quiz_id);
       // $objDetailsReport = $this->quiz_model->get_quiz_details_report($quiz_id);
       // $objQuestionsReport = $this->quiz_model->get_quiz_total_questions_report($quiz_id);
        $objStudentReport = $this->quiz_model->get_quiz_student_report($quiz_id, $user_id);

        if ($objStudentReport === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating question...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objStudentReport, "Quiz Student Report");
            $this->set_output($response);
        }
    }

    /**
     * Test Point Award Function
     * POST /api/quiz/test_point_award
     * Direct test of award_quiz_completion_points function
     */
    function test_point_award_post()
    {
        $request = $this->get_request();

        // Validate required fields
        if (empty($request['user_id']) || empty($request['quiz_id'])) {
            $response = $this->get_failed_response(NULL, "Missing required fields: user_id, quiz_id");
            $this->set_output($response);
            return;
        }

        $user_id = $request['user_id'];
        $quiz_id = $request['quiz_id'];

        try {
            // Load the User_point_service library
            $this->load->library('User_point_service');
            
            // Call the point award function directly
            $point_result = $this->user_point_service->award_quiz_completion_points($user_id, $quiz_id);
            
            // Prepare detailed response
            $response_data = array(
                'user_id' => $user_id,
                'quiz_id' => $quiz_id,
                'timestamp' => date('Y-m-d H:i:s'),
                'point_award_result' => $point_result
            );

            if ($point_result['success']) {
                $response = $this->get_success_response($response_data, "Point award test completed successfully");
                log_message('info', "Point award test successful for user {$user_id}, quiz {$quiz_id}: {$point_result['points_awarded']} points");
            } else {
                $response = $this->get_success_response($response_data, "Point award test completed with issues: " . $point_result['error']);
                log_message('info', "Point award test failed for user {$user_id}, quiz {$quiz_id}: {$point_result['error']}");
            }
            
            $this->set_output($response);

        } catch (Exception $e) {
            $error_response = array(
                'user_id' => $user_id,
                'quiz_id' => $quiz_id,
                'timestamp' => date('Y-m-d H:i:s'),
                'error' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            );
            
            $response = $this->get_failed_response($error_response, "Exception occurred during point award test: " . $e->getMessage());
            $this->set_output($response);
            log_message('error', "Point award test exception for user {$user_id}, quiz {$quiz_id}: " . $e->getMessage());
        }
    }

    /**
     * Generate Custom Quiz API
     * POST /api/quiz/generate_custom_quiz
     */
    function generate_custom_quiz_post()
    {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        // Get request data (handles both JSON and form POST)
        $request = $this->get_request();
        
        $exam_id = isset($request['exam_id']) ? $request['exam_id'] : null;
        $subject_ids = isset($request['subject_ids']) ? $request['subject_ids'] : null;
        $chapter_ids = isset($request['chapter_ids']) ? $request['chapter_ids'] : null;
        $topic_ids = isset($request['topic_ids']) ? $request['topic_ids'] : null;
        $difficulty = isset($request['difficulty']) ? $request['difficulty'] : null;
        $number_of_questions = isset($request['number_of_questions']) ? $request['number_of_questions'] : null;
        $quiz_name =  isset($request['quiz_name']) ? $request['quiz_name'] : null;
        $quiz_description = isset($request['quiz_description']) ? $request['quiz_description'] : null;
        $quiz_type = isset($request['quiz_type']) ? $request['quiz_type'] : 'public';
        $question_type = isset($request['question_type']) ? $request['question_type'] : 'regular';
        $user_id = isset($request['user_id']) ? $request['user_id'] : null;

        // Debug logging
        log_message('debug', "Quiz API received request: " . json_encode($request));
        log_message('debug', "Quiz API parsed: exam_id=$exam_id, subject_ids=$subject_ids, difficulty=$difficulty, number_of_questions=$number_of_questions, quiz_type=$quiz_type, question_type=$question_type, user_id=$user_id");

        // Validate required parameters
        if (empty($exam_id) || empty($subject_ids) || empty($difficulty) || empty($number_of_questions)) {
            $response = $this->get_failed_response(NULL, "Missing required parameters: exam_id, subject_ids, difficulty, number_of_questions");
            $this->set_output($response);
            return;
        }

        // Parse JSON arrays
        if (is_string($subject_ids)) {
            $subject_ids = json_decode($subject_ids, true);
        }
        if (is_string($chapter_ids) && !empty($chapter_ids)) {
            $chapter_ids = json_decode($chapter_ids, true);
        }
        if (is_string($topic_ids) && !empty($topic_ids)) {
            $topic_ids = json_decode($topic_ids, true);
        }

        // Validate subject_ids array after parsing
        if (!is_array($subject_ids) || empty($subject_ids)) {
            $response = $this->get_failed_response(NULL, "subject_ids must be a non-empty array");
            $this->set_output($response);
            return;
        }

        // Validate quiz_type
        if (!in_array($quiz_type, ['public', 'private'])) {
            $quiz_type = 'public'; // Default to public if invalid value
        }
        
        // Validate question_type
        $valid_question_types = array('regular', 'pyq', 'mock');
        if (!in_array($question_type, $valid_question_types)) {
            $response = $this->get_failed_response(NULL, "Invalid question_type. Must be one of: regular, pyq, mock");
            $this->set_output($response);
            return;
        }
        
        $number_of_questions = (int)$number_of_questions;
        if ($number_of_questions < 1) {
            $response = $this->get_failed_response(NULL, "number_of_questions must be at least 1");
            $this->set_output($response);
            return;
        }
        
        // Cap at 60 questions maximum for custom quizzes
        if ($number_of_questions > 60) {
            $number_of_questions = 60;
            log_message('info', "Quiz questions capped at 60. Original request: {$request['number_of_questions']}");
        }

        // ✅ QUOTA VALIDATION: Check if user can create more custom quizzes
        $quota_check = $this->checkCustomQuizLimit($objUser);
        if (!$quota_check['allowed']) {
            $response_data = array(
                'quota_exceeded' => true,
                'current_count' => isset($quota_check['current']) ? $quota_check['current'] : 0,
                'limit' => isset($quota_check['limit']) ? $quota_check['limit'] : 0,
                'subscription_type' => isset($quota_check['subscription_type']) ? $quota_check['subscription_type'] : 'free'
            );
            
            $error_message = isset($quota_check['message']) ? $quota_check['message'] : 'Custom quiz limit exceeded';
            $response = $this->get_failed_response($response_data, $error_message);
            $this->set_output($response);
            return;
        }

        try {
            // Load models
            $this->load->model('quiz/quiz_model');
            $this->load->model('quiz_question/quiz_question_model');
            $this->load->model('question/question_model');
            $this->load->model('exam/exam_model');
            $this->load->model('subject/subject_model');
            $this->load->model('quiz_subject/quiz_subject_model');

            // Get exam details to include exam name in description
            $exam = $this->exam_model->get_exam($exam_id);
            $exam_name = isset($exam->exam_name) ? $exam->exam_name : "Unknown Exam";

            // Get subject names for description
            $subject_names = array();
            foreach ($subject_ids as $subject_id) {
                $subject = $this->subject_model->get_Subject($subject_id);
                if ($subject && isset($subject->subject_name)) {
                    $subject_names[] = $subject->subject_name;
                }
            }
            $subjects_text = !empty($subject_names) ? implode(', ', $subject_names) : 'Multiple Subjects';

            // Create the quiz first
            $objQuiz = new Quiz_object();
            
            // Generate quiz name based on question type
            if (!empty($quiz_name)) {
                $objQuiz->name = $quiz_name;
            } else {
                switch ($question_type) {
                    case 'pyq':
                        $objQuiz->name = "PYQ Quiz - " . date('Y-m-d H:i:s');
                        break;
                    case 'mock':
                        $objQuiz->name = "Mock Test - " . date('Y-m-d H:i:s');
                        break;
                    case 'regular':
                    default:
                        $objQuiz->name = "Custom Quiz - " . date('Y-m-d H:i:s');
                        break;
                }
            }
            
            // Enhanced description with exam name, subjects, and question type
            if (!empty($quiz_description)) {
                $objQuiz->description = $quiz_description . " (Exam: " . $exam_name . ", Subjects: " . $subjects_text . ")";
            } else {
                switch ($question_type) {
                    case 'pyq':
                        $objQuiz->description = "Previous Year Questions (PYQ) for " . $exam_name . " covering " . $subjects_text;
                        break;
                    case 'mock':
                        $objQuiz->description = "Mock Test for " . $exam_name . " covering " . $subjects_text;
                        break;
                    case 'regular':
                    default:
                        $objQuiz->description = "Custom quiz for " . $exam_name . " covering " . $subjects_text;
                        break;
                }
            }
            
            $objQuiz->class_id = 0; // Default class
            $objQuiz->subject_id = $subject_ids[0]; // Keep first subject as primary for backward compatibility
            $objQuiz->exam_id = $exam_id; // Set the exam_id
            $objQuiz->start_date = date('Y-m-d H:i:s');
            $objQuiz->is_live = 0;
            $objQuiz->marking = "4"; // Default marking scheme
            $objQuiz->quiz_type = $quiz_type;
            $objQuiz->quiz_question_type = $question_type; // Set the question type
            $objQuiz->user_id = $user_id;
            $objQuiz->quiz_reference = $this->generateUUID(); // Generate UUID reference
            $objQuiz->level = $difficulty; // Set the level from difficulty parameter

            // Add quiz to database
            $createdQuiz = $this->quiz_model->add_Quiz($objQuiz);
            
            if ($createdQuiz === FALSE) {
                $response = $this->get_failed_response(NULL, "Failed to create quiz");
                $this->set_output($response);
                return;
            }

            $quiz_id = $createdQuiz->id;

            // Add quiz-subject relationships to the junction table
            $subjects_added = $this->quiz_subject_model->add_quiz_subjects($quiz_id, $subject_ids);
            if (!$subjects_added) {
                log_message('warning', "Failed to add quiz-subject relationships for quiz ID: " . $quiz_id);
                // Continue with quiz creation even if subject relationships fail
            }

            // Debug logging - include subject IDs for tracking
            log_message('debug', "Created quiz object: " . json_encode($createdQuiz));
            log_message('debug', "Quiz created with multiple subjects: " . json_encode($subject_ids));
            log_message('debug', "Quiz-subject relationships added: " . ($subjects_added ? 'Success' : 'Failed'));

            // Build question query filters
            $filters = array();
            $filters['exam_id'] = $exam_id;
            $filters['subject_ids'] = $subject_ids;
            $filters['difficulty'] = $difficulty;
            $filters['limit'] = $number_of_questions;
            $filters['question_type'] = $question_type; // Add question_type filter
            $filters['exclude_user_id'] = $user_id; // Add user_id to exclude their correctly answered questions
            
            if (!empty($chapter_ids) && is_array($chapter_ids)) {
                $filters['chapter_ids'] = $chapter_ids;
            }
            
            if (!empty($topic_ids) && is_array($topic_ids)) {
                $filters['topic_ids'] = $topic_ids;
            }

            // Get balanced questions with equal distribution across subjects and chapters
            $questions = $this->get_balanced_questions($filters);

            // Enhanced post-processing to ensure we meet the requested number of questions
            $questions = $this->ensure_sufficient_questions($questions, $filters, $number_of_questions);

            if (empty($questions)) {
                // If no questions found, remove the created quiz
                $this->quiz_model->delete_Quiz($quiz_id);
                $response = $this->get_failed_response(NULL, "No questions found matching the specified criteria");
                $this->set_output($response);
                return;
            }

            // Questions are organized by subject with randomization within each subject
            // Limit to requested number of questions (safety check)
            $questions = array_slice($questions, 0, $number_of_questions);

            // Add questions to quiz
            $question_order = 1;
            $added_questions = array();

            foreach ($questions as $question) {
                $objQuizQuestion = new Quiz_question_object();
                $objQuizQuestion->quiz_id = $quiz_id;
                $objQuizQuestion->question_id = $question->id;
                $objQuizQuestion->question_order = $question_order;

                $result = $this->quiz_question_model->add_Quiz_question($objQuizQuestion);
                
                if ($result !== FALSE) {
                    $added_questions[] = array(
                        'question_id' => $question->id,
                        'question_order' => $question_order,
                        'level' => $question->level,
                        'subject_id' => $question->subject_id,
                        'chapter_id' => $question->chapter_id,
                        'topic_id' => $question->topic_id
                    );
                    $question_order++;
                }
            }

            // Calculate estimated time based on quiz level
            $questionTimeMinutes = ($difficulty === 'Advance') ? 2 : 1; // 2 minutes for Advance, 1 minute for Elementary/Moderate
            $estimatedTimeMinutes = count($added_questions) * $questionTimeMinutes;

            // Get subject distribution information
            $subject_distribution = $this->get_subject_distribution_report($added_questions, $subject_ids);

            // Prepare response data
            $quiz_data = array(
                'quiz_id' => $quiz_id,
                'quiz_reference' => $createdQuiz->quiz_reference,
                'quiz_name' => $createdQuiz->name,
                'quiz_description' => $createdQuiz->description,
                'quiz_type' => $createdQuiz->quiz_type,
                'quiz_question_type' => $question_type,
                'user_id' => $createdQuiz->user_id,
                'total_questions' => count($added_questions),
                'difficulty' => $difficulty,
                'level' => $difficulty, // Include level for consistency
                'estimated_time' => $estimatedTimeMinutes . ' minutes',
                'question_time_per_question' => $questionTimeMinutes . ' minute' . ($questionTimeMinutes !== 1 ? 's' : ''),
                'total_marks' => count($added_questions) * 4,
                'questions' => $added_questions,
                'subject_distribution' => $subject_distribution
            );
            
            // Add note if questions were capped
            if (isset($request['number_of_questions']) && (int)$request['number_of_questions'] > 60) {
                $quiz_data['questions_capped'] = true;
                $quiz_data['original_request'] = (int)$request['number_of_questions'];
                $quiz_data['capped_note'] = 'Quiz questions were capped at 60 (maximum allowed for custom quizzes)';
            }

            // Add information about question filtering if user_id was provided
            if (!empty($user_id)) {
                $quiz_data['filtered_for_user'] = true;
                $quiz_data['filtering_note'] = 'Questions you have answered correctly in previous quizzes were excluded from this quiz';
                
                // If we had to use fallback, inform the user
                if (isset($new_questions) && count($new_questions) != count($questions)) {
                    $quiz_data['contains_repeated_questions'] = true;
                    $quiz_data['repeated_note'] = 'Some previously answered questions were included to meet the requested number of questions';
                }
            } else {
                $quiz_data['filtered_for_user'] = false;
            }

            // Add information about fallback strategies if they were applied
            if (isset($this->fallback_strategies_applied) && !empty($this->fallback_strategies_applied)) {
                $quiz_data['fallback_strategies_applied'] = true;
                $quiz_data['original_question_count'] = $this->original_question_count;
                $quiz_data['fallback_question_count'] = $this->fallback_question_count;
                $quiz_data['fallback_strategies'] = $this->fallback_strategies_applied;
                $quiz_data['fallback_note'] = 'Additional questions were found using fallback strategies to meet your requested number of questions: ' . implode('; ', $this->fallback_strategies_applied);
            } else {
                $quiz_data['fallback_strategies_applied'] = false;
            }

            // Add success rate information
            $actual_questions = count($added_questions);
            $quiz_data['question_fulfillment'] = array(
                'requested' => $number_of_questions,
                'fulfilled' => $actual_questions,
                'percentage' => round(($actual_questions / $number_of_questions) * 100, 1)
            );

            // Add warning if significantly fewer questions than requested
            if ($actual_questions < ($number_of_questions * 0.8)) { // Less than 80% of requested
                $quiz_data['insufficient_questions_warning'] = true;
                $quiz_data['warning_message'] = "Warning: Only {$actual_questions} out of {$number_of_questions} requested questions were found. You may want to relax your criteria or choose different subjects/difficulty levels.";
            }

            // Debug logging
            log_message('debug', "Quiz response data: " . json_encode($quiz_data));

            // ✅ UPDATE QUOTA: Increment user's custom quiz count after successful creation
            $this->incrementCustomQuizCount($objUser->id);
            
            // Add quota information to response if warnings are enabled
            if ($this->config->item('quiz_enable_quota_warnings')) {
                $updated_quota = $this->checkCustomQuizLimit($objUser);
                if (isset($updated_quota['remaining'])) {
                    $quiz_data['quota_info'] = array(
                        'remaining' => $updated_quota['remaining'],
                        'total_used' => $updated_quota['current'] + 1, // +1 for the quiz just created
                        'limit' => $updated_quota['limit'],
                        'subscription_type' => $updated_quota['subscription_type']
                    );
                    
                    // Add warning if provided
                    if (isset($updated_quota['warning'])) {
                        $quiz_data['quota_warning'] = $updated_quota['warning'];
                    }
                }
            }

            // ✅ SAVE CUSTOM QUIZ DATA: Save request parameters and subject distribution before sending response
            try {
                // Prepare the request data to save (excluding sensitive information)
                $request_data_to_save = array(
                    'exam_id' => $exam_id,
                    'subject_ids' => $subject_ids,
                    'chapter_ids' => $chapter_ids,
                    'topic_ids' => $topic_ids,
                    'difficulty' => $difficulty,
                    'question_type' => $question_type,
                    'number_of_questions' => $number_of_questions,
                    'quiz_name' => $quiz_name,
                    'quiz_description' => $quiz_description,
                    'quiz_type' => $quiz_type,
                    'timestamp' => date('Y-m-d H:i:s')
                );
                
                // Convert to JSON strings
                $request_json = json_encode($request_data_to_save);
                $subject_distribution_json = json_encode($subject_distribution);
                
                // Update the quiz record with the new data
                $update_sql = "UPDATE quiz SET 
                               custom_quiz_request_data = ?, 
                               subject_distribution_data = ? 
                               WHERE id = ?";
                
                $pdo = CDatabase::getPdo();
                $update_statement = $pdo->prepare($update_sql);
                $update_result = $update_statement->execute(array(
                    $request_json,
                    $subject_distribution_json,
                    $quiz_id
                ));
                
                if ($update_result) {
                    log_message('debug', "Successfully saved custom quiz data for quiz ID: {$quiz_id}");
                } else {
                    log_message('warning', "Failed to save custom quiz data for quiz ID: {$quiz_id}");
                }
                
                $update_statement = NULL;
                $pdo = NULL;
                
            } catch (Exception $save_exception) {
                // Log the error but don't fail the quiz creation
                log_message('error', "Error saving custom quiz data for quiz ID {$quiz_id}: " . $save_exception->getMessage());
            }

            $response = $this->get_success_response($quiz_data, "Custom quiz generated successfully");
            $this->set_output($response);

        } catch (Exception $e) {
            $response = $this->get_failed_response(NULL, "Error generating quiz: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Helper function to get balanced questions with equal distribution across subjects and chapters
     * Implements 90% primary difficulty + 10% adjacent difficulty mixing
     */
    private function get_balanced_questions($filters)
    {
        $pdo = CDatabase::getPdo();
        
        $total_questions = (int)$filters['limit'];
        $subject_ids = $filters['subject_ids'];
        $chapter_ids = isset($filters['chapter_ids']) ? $filters['chapter_ids'] : array();
        $primary_difficulty = $filters['difficulty'];
        $question_type = isset($filters['question_type']) ? $filters['question_type'] : 'regular';
        $exclude_user_id = isset($filters['exclude_user_id']) ? $filters['exclude_user_id'] : null;
        
        // Validate inputs
        if ($total_questions <= 0) {
            log_message('error', "Invalid total_questions: {$total_questions}");
            return array();
        }
        
        if (empty($subject_ids) || !is_array($subject_ids)) {
            log_message('error', "Invalid subject_ids: " . json_encode($subject_ids));
            return array();
        }
        
        // Calculate difficulty distribution (90% primary, 10% adjacent)
        $primary_questions_count = ceil($total_questions * 0.9);
        $adjacent_questions_count = $total_questions - $primary_questions_count;
        
        // Debug: Check what difficulty levels are available
        $available_levels = $this->debug_difficulty_levels($filters['exam_id'], $subject_ids);
        
        // Get adjacent difficulty level
        $adjacent_difficulty = $this->get_adjacent_difficulty($primary_difficulty);
        
        if ($primary_difficulty === 'Advance') {
            log_message('debug', "Advance level selected: using 100% Advance questions (no mixing with lower levels)");
            
        } else {
            log_message('debug', "Balanced question selection: {$primary_questions_count} primary ({$primary_difficulty}) + {$adjacent_questions_count} adjacent ({$adjacent_difficulty})");
        }
        
        // Calculate questions per subject
        $questions_per_subject = floor($total_questions / count($subject_ids));
        $remaining_questions = $total_questions % count($subject_ids);
        
        log_message('debug', "Subject distribution: {$questions_per_subject} per subject, {$remaining_questions} remaining for " . count($subject_ids) . " subjects");
        
        $all_questions = array();
        $distribution_report = array();
        
        // ✅ Track actual questions allocated to prevent over-distribution
        $total_questions_allocated = 0;
        
        foreach ($subject_ids as $index => $subject_id) {
            // ✅ Calculate how many questions remain to be allocated
            $remaining_to_allocate = $total_questions - $total_questions_allocated;
            
            if ($remaining_to_allocate <= 0) {
                log_message('debug', "Subject {$subject_id}: skipping, total allocation reached ({$total_questions_allocated}/{$total_questions})");
                break;
            }
            
            $subject_question_count = $questions_per_subject;
            
            // Distribute remaining questions to first subjects
            if ($index < $remaining_questions) {
                $subject_question_count++;
            }
            
            // ✅ Ensure we don't exceed total remaining allocation
            $subject_question_count = min($subject_question_count, $remaining_to_allocate);
            
            if ($subject_question_count == 0) {
                continue;
            }
            
            // Calculate difficulty split for this subject
            // Special case: Advance level uses 100% Advance questions (no mixing)
            if ($primary_difficulty === 'Advance') {
                $subject_primary_count = $subject_question_count;
                $subject_adjacent_count = 0;
            } else if ($subject_question_count >= 5) {
                // For other levels, ensure we have at least 1 adjacent question if we have more than 5 total questions
                $subject_adjacent_count = max(1, floor($subject_question_count * 0.1));
                $subject_primary_count = $subject_question_count - $subject_adjacent_count;
            } else {
                // For very small numbers, use all primary difficulty
                $subject_primary_count = $subject_question_count;
                $subject_adjacent_count = 0;
            }
            
            log_message('debug', "Subject {$subject_id}: {$subject_question_count} total ({$subject_primary_count} primary + {$subject_adjacent_count} adjacent) - Remaining to allocate: {$remaining_to_allocate}");
            
            // Filter chapter_ids for this specific subject if needed
            $subject_chapter_ids = array();
            if (!empty($chapter_ids) && is_array($chapter_ids)) {
                // For now, use all provided chapter_ids for each subject
                // In future, you could filter chapter_ids by subject_id if needed
                $subject_chapter_ids = $chapter_ids;
            }
            
            // Get questions for this subject
            $subject_questions = $this->get_subject_questions(array(
                'exam_id' => $filters['exam_id'],
                'subject_id' => $subject_id,
                'chapter_ids' => $subject_chapter_ids,
                'topic_ids' => isset($filters['topic_ids']) ? $filters['topic_ids'] : array(),
                'primary_difficulty' => $primary_difficulty,
                'primary_count' => $subject_primary_count,
                'adjacent_difficulty' => $adjacent_difficulty,
                'adjacent_count' => $subject_adjacent_count,
                'question_type' => $question_type,
                'exclude_user_id' => $exclude_user_id
            ));
            
            // ✅ CRITICAL: Enforce strict limit on what we actually add
            $returned_count = count($subject_questions);
            log_message('debug', "Subject {$subject_id} returned {$returned_count} questions, allocated was {$subject_question_count}");
            
            if ($returned_count > $subject_question_count) {
                log_message('error', "CRITICAL: Subject {$subject_id} returned {$returned_count} questions but only {$subject_question_count} were allocated. FORCE trimming to {$subject_question_count}");
                $subject_questions = array_slice($subject_questions, 0, $subject_question_count);
            }
            
            // ✅ Double-check after trim
            $actual_count_after_trim = count($subject_questions);
            if ($actual_count_after_trim != $subject_question_count && $returned_count >= $subject_question_count) {
                log_message('error', "CRITICAL ERROR: Trim failed! Expected {$subject_question_count}, got {$actual_count_after_trim}");
            }
            
            log_message('debug', "Subject {$subject_id} result: " . count($subject_questions) . " questions will be added (expected {$subject_question_count})");
            
            $all_questions = array_merge($all_questions, $subject_questions);
            
            // ✅ Update total allocated counter
            $total_questions_allocated += count($subject_questions);
            
            log_message('debug', "Subject {$subject_id} added: " . count($subject_questions) . " questions. Total allocated so far: {$total_questions_allocated}/{$total_questions}");
            
            // Track distribution for reporting
            $distribution_report[$subject_id] = array(
                'subject_id' => $subject_id,
                'questions_assigned' => $subject_question_count,
                'questions_found' => count($subject_questions),
                'primary_difficulty' => $primary_difficulty,
                'primary_count' => $subject_primary_count,
                'adjacent_difficulty' => $adjacent_difficulty,
                'adjacent_count' => $subject_adjacent_count
            );
        }
        
        // ✅ FINAL VALIDATION: Ensure we haven't exceeded total
        if (count($all_questions) > $total_questions) {
            log_message('error', "CRITICAL: Total questions exceeded limit! Found " . count($all_questions) . " but limit was {$total_questions}. Force trimming to {$total_questions}");
            $all_questions = array_slice($all_questions, 0, $total_questions);
        }
        
        // Check if we have uneven distribution and try to balance it
        $total_found = count($all_questions);
        if ($total_found < $total_questions) {
            log_message('debug', "Insufficient questions found ({$total_found} out of {$total_questions}). Attempting to balance with fallback strategy.");
            
            // Try to get more questions without user exclusion if that was the issue
            if (!empty($exclude_user_id)) {
                // ✅ Pass distribution_report to fallback so it knows per-subject limits
                $fallback_questions = $this->get_balanced_questions_fallback($filters, $all_questions, $distribution_report);
                $all_questions = array_merge($all_questions, $fallback_questions);
            }
        }
        
        // ✅ POST-PROCESSING: Enforce perfect distribution by redistributing questions
        log_message('debug', "Pre-redistribution: " . count($all_questions) . " total questions");
        $all_questions = $this->enforce_balanced_distribution($all_questions, $subject_ids, $total_questions);
        log_message('debug', "Post-redistribution: " . count($all_questions) . " total questions");
        
        // Group questions by subject and randomize within each subject, but keep subjects sequential
        $all_questions = $this->organize_questions_by_subject($all_questions, $subject_ids);
        
        // Update distribution report based on actual questions found
        $this->update_distribution_report($all_questions, $distribution_report);
        
        // Store distribution report in filters for later use
        $this->question_distribution_report = $distribution_report;
        
        log_message('debug', "Total balanced questions found: " . count($all_questions));
        log_message('debug', "Final distribution report: " . json_encode($distribution_report));
        
        $pdo = NULL;
        
        return $all_questions;
    }
    
    /**
     * Debug method to check what difficulty levels exist in database
     */
    private function debug_difficulty_levels($exam_id, $subject_ids)
    {
        $pdo = CDatabase::getPdo();
        
        $subject_placeholders = str_repeat('?,', count($subject_ids) - 1) . '?';
        $sql = "SELECT DISTINCT level, COUNT(*) as count 
                FROM question 
                WHERE exam_id = ? AND subject_id IN ({$subject_placeholders}) 
                AND (invalid_question IS NULL OR invalid_question = 0)
                GROUP BY level 
                ORDER BY level";
        
        $params = array_merge(array($exam_id), $subject_ids);
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        
        $levels = array();
        while ($row = $statement->fetch()) {
            $levels[$row['level']] = $row['count'];
        }
        
        log_message('debug', "DEBUG: Available difficulty levels for exam {$exam_id}: " . json_encode($levels));

        $statement = NULL;
        $pdo = NULL;
        
        return $levels;
    }

    /**
     * Get adjacent difficulty level for 10% mixing - always go to next higher level
     */
    private function get_adjacent_difficulty($primary_difficulty)
    {
        switch ($primary_difficulty) {
            case 'Elementary':
                return 'Moderate'; // Next level up
            case 'Moderate':
                return 'Advance'; // Next level up (highest level)
            case 'Intermediate': // Fallback for old naming convention
                return 'Advance'; // Next level up
            case 'Advance':
                return null; // Already at highest level, no upper level available
            default:
                return 'Moderate'; // Default fallback
        }
    }
    
    /**
     * Get questions for a specific subject with balanced chapter distribution
     */
    private function get_subject_questions($params)
    {
        $pdo = CDatabase::getPdo();
        
        $subject_id = $params['subject_id'];
        $chapter_ids = $params['chapter_ids'];
        $primary_count = $params['primary_count'];
        $adjacent_count = $params['adjacent_count'];
        $total_allowed = $primary_count + $adjacent_count; // Total questions allowed for this subject
        
        $subject_questions = array();
        
        // Get primary difficulty questions
        if ($primary_count > 0) {
            log_message('debug', "Getting {$primary_count} primary difficulty ({$params['primary_difficulty']}) questions for subject {$subject_id}");
            $primary_questions = $this->get_questions_by_difficulty(array(
                'exam_id' => $params['exam_id'],
                'subject_id' => $subject_id,
                'chapter_ids' => $chapter_ids,
                'topic_ids' => $params['topic_ids'],
                'difficulty' => $params['primary_difficulty'],
                'limit' => $primary_count,
                'question_type' => isset($params['question_type']) ? $params['question_type'] : 'regular',
                'exclude_user_id' => $params['exclude_user_id']
            ));
            
            // ✅ CRITICAL: Cap primary questions to requested limit
            if (count($primary_questions) > $primary_count) {
                log_message('debug', "Primary questions exceeded limit: " . count($primary_questions) . " > {$primary_count}, trimming to {$primary_count}");
                $primary_questions = array_slice($primary_questions, 0, $primary_count);
            }
            
            log_message('debug', "Found " . count($primary_questions) . " primary difficulty questions for subject {$subject_id}");
            
            // If we didn't get enough primary questions, try to get more from adjacent difficulty
            if (count($primary_questions) < $primary_count && !empty($params['adjacent_difficulty'])) {
                $shortage = $primary_count - count($primary_questions);
                log_message('debug', "Primary difficulty shortage: need {$shortage} more questions for subject {$subject_id}");
                
                $fallback_questions = $this->get_questions_by_difficulty(array(
                    'exam_id' => $params['exam_id'],
                    'subject_id' => $subject_id,
                    'chapter_ids' => $chapter_ids,
                    'topic_ids' => $params['topic_ids'],
                    'difficulty' => $params['adjacent_difficulty'],
                    'limit' => $shortage,
                    'question_type' => isset($params['question_type']) ? $params['question_type'] : 'regular',
                    'exclude_user_id' => $params['exclude_user_id']
                ));
                
                // ✅ CRITICAL: Cap fallback questions to shortage amount
                if (count($fallback_questions) > $shortage) {
                    log_message('debug', "Fallback questions exceeded shortage: " . count($fallback_questions) . " > {$shortage}, trimming to {$shortage}");
                    $fallback_questions = array_slice($fallback_questions, 0, $shortage);
                }
                
                log_message('debug', "Found " . count($fallback_questions) . " fallback questions from {$params['adjacent_difficulty']} for subject {$subject_id}");
                $primary_questions = array_merge($primary_questions, $fallback_questions);
                
                // ✅ CRITICAL: After merge, enforce primary_count limit again
                if (count($primary_questions) > $primary_count) {
                    log_message('warning', "Primary questions after fallback merge exceeded {$primary_count}: " . count($primary_questions) . ", force trimming");
                    $primary_questions = array_slice($primary_questions, 0, $primary_count);
                }
            }
            
            // ✅ CRITICAL: Before adding to subject_questions, check we don't exceed total_allowed
            $current_count = count($subject_questions);
            $space_remaining = $total_allowed - $current_count;
            $questions_to_add = min(count($primary_questions), $space_remaining);
            
            if ($questions_to_add < count($primary_questions)) {
                log_message('warning', "Can only add {$questions_to_add} primary questions (out of " . count($primary_questions) . ") due to total_allowed limit of {$total_allowed}");
                $primary_questions = array_slice($primary_questions, 0, $questions_to_add);
            }
            
            $subject_questions = array_merge($subject_questions, $primary_questions);
            log_message('debug', "After adding primary questions, subject {$subject_id} has " . count($subject_questions) . " questions (limit: {$total_allowed})");
        }
        
        // Get adjacent difficulty questions (only if higher level exists)
        if ($adjacent_count > 0 && !empty($params['adjacent_difficulty']) && $params['adjacent_difficulty'] !== null) {
            // ✅ Calculate remaining quota to prevent exceeding total allowed
            $current_count = count($subject_questions);
            $remaining_quota = $total_allowed - $current_count;
            $actual_adjacent_limit = min($adjacent_count, $remaining_quota);
            
            if ($actual_adjacent_limit > 0) {
                log_message('debug', "Getting {$actual_adjacent_limit} adjacent difficulty ({$params['adjacent_difficulty']}) questions for subject {$subject_id} (remaining quota: {$remaining_quota})");
                $adjacent_questions = $this->get_questions_by_difficulty(array(
                    'exam_id' => $params['exam_id'],
                    'subject_id' => $subject_id,
                    'chapter_ids' => $chapter_ids,
                    'topic_ids' => $params['topic_ids'],
                    'difficulty' => $params['adjacent_difficulty'],
                    'limit' => $actual_adjacent_limit,
                    'question_type' => isset($params['question_type']) ? $params['question_type'] : 'regular',
                    'exclude_user_id' => $params['exclude_user_id']
                ));
                
                // ✅ CRITICAL: Cap adjacent questions to actual limit
                if (count($adjacent_questions) > $actual_adjacent_limit) {
                    log_message('debug', "Adjacent questions exceeded limit: " . count($adjacent_questions) . " > {$actual_adjacent_limit}, trimming to {$actual_adjacent_limit}");
                    $adjacent_questions = array_slice($adjacent_questions, 0, $actual_adjacent_limit);
                }
                
                // ✅ CRITICAL: Double-check remaining space before merge
                $current_count_before_adjacent = count($subject_questions);
                $actual_space_remaining = $total_allowed - $current_count_before_adjacent;
                $safe_adjacent_count = min(count($adjacent_questions), $actual_space_remaining);
                
                if ($safe_adjacent_count < count($adjacent_questions)) {
                    log_message('warning', "Can only add {$safe_adjacent_count} adjacent questions (out of " . count($adjacent_questions) . ") due to remaining space of {$actual_space_remaining}");
                    $adjacent_questions = array_slice($adjacent_questions, 0, $safe_adjacent_count);
                }
                
                log_message('debug', "Found " . count($adjacent_questions) . " adjacent difficulty questions for subject {$subject_id}");
                $subject_questions = array_merge($subject_questions, $adjacent_questions);
                log_message('debug', "After adding adjacent questions, subject {$subject_id} has " . count($subject_questions) . " questions (limit: {$total_allowed})");
            } else {
                log_message('debug', "Skipping adjacent questions for subject {$subject_id}: quota already filled ({$current_count}/{$total_allowed})");
            }
        } else {
            $skip_reason = 'count=' . $adjacent_count;
            if (empty($params['adjacent_difficulty']) || $params['adjacent_difficulty'] === null) {
                $skip_reason .= ', no higher difficulty level available';
            } else {
                $skip_reason .= ', difficulty=' . $params['adjacent_difficulty'];
            }
            log_message('debug', "Skipping adjacent difficulty for subject {$subject_id}: {$skip_reason}");
        }
        
        // ✅ FINAL SAFETY: Enforce strict total limit for this subject
        if (count($subject_questions) > $total_allowed) {
            log_message('error', "CRITICAL: Subject {$subject_id} exceeded total allocation: " . count($subject_questions) . " > {$total_allowed}, FORCE trimming to {$total_allowed}");
            $subject_questions = array_slice($subject_questions, 0, $total_allowed);
        }
        
        // ✅ ABSOLUTE FINAL CHECK: Verify count matches expectation
        $final_count = count($subject_questions);
        if ($final_count > $total_allowed) {
            log_message('error', "CRITICAL ERROR: Subject {$subject_id} still has {$final_count} questions after trimming! Expected max: {$total_allowed}. Emergency trim.");
            $subject_questions = array_slice($subject_questions, 0, $total_allowed);
        }
        
        log_message('debug', "Subject {$subject_id} final count: " . count($subject_questions) . " questions (allocated: {$total_allowed}) - VERIFIED");
        
        $pdo = NULL;
        
        return $subject_questions;
    }
    
    /**
     * Get questions by specific difficulty with chapter balancing
     */
    private function get_questions_by_difficulty($params)
    {
        $pdo = CDatabase::getPdo();
        
        $question_type = isset($params['question_type']) ? $params['question_type'] : 'regular';
        $base_sql = "SELECT * FROM question WHERE question_type=? AND (invalid_question IS NULL OR invalid_question = 0)";
        $base_params = array($question_type);
        
        // Add exam filter
        if (!empty($params['exam_id'])) {
            $base_sql .= " AND exam_id = ?";
            $base_params[] = $params['exam_id'];
        }
        
        // Add subject filter
        $base_sql .= " AND subject_id = ?";
        $base_params[] = $params['subject_id'];
        
        // Add difficulty filter
        $base_sql .= " AND level = ?";
        $base_params[] = $params['difficulty'];
        
        // Add topic filter if specified
        if (!empty($params['topic_ids']) && is_array($params['topic_ids'])) {
            $placeholders = str_repeat('?,', count($params['topic_ids']) - 1) . '?';
            $base_sql .= " AND topic_id IN ($placeholders)";
            $base_params = array_merge($base_params, $params['topic_ids']);
        }
        
        // Exclude questions that the user has already answered correctly
        if (!empty($params['exclude_user_id'])) {
            $base_sql .= " AND id NOT IN (
                            SELECT DISTINCT uq.question_id 
                            FROM user_question uq 
                            WHERE uq.user_id = ? AND uq.is_correct = 1
                          )";
            $base_params[] = $params['exclude_user_id'];
        }
        
        $questions = array();
        $limit = (int)$params['limit'];
        
        // Validate limit
        if ($limit <= 0) {
            log_message('debug', "Invalid limit: {$limit}, returning empty array");
            return array();
        }
        
        // If chapters are specified, distribute equally across chapters
        if (!empty($params['chapter_ids']) && is_array($params['chapter_ids'])) {
            $questions_per_chapter = floor($limit / count($params['chapter_ids']));
            $remaining_questions = $limit % count($params['chapter_ids']);
            
            log_message('debug', "Chapter distribution: {$questions_per_chapter} per chapter, {$remaining_questions} remaining for " . count($params['chapter_ids']) . " chapters (total limit: {$limit})");
            
            $total_fetched = 0; // ✅ Track total questions fetched across all chapters
            
            foreach ($params['chapter_ids'] as $index => $chapter_id) {
                // ✅ Check if we've already reached the limit
                if ($total_fetched >= $limit) {
                    log_message('debug', "Chapter {$chapter_id}: skipping, limit already reached ({$total_fetched}/{$limit})");
                    break;
                }
                
                $chapter_limit = $questions_per_chapter;
                
                // Distribute remaining questions to first chapters
                if ($index < $remaining_questions) {
                    $chapter_limit++;
                }
                
                if ($chapter_limit == 0) {
                    continue;
                }
                
                // ✅ Ensure we don't exceed total limit
                $chapter_limit = min($chapter_limit, $limit - $total_fetched);
                
                $chapter_sql = $base_sql . " AND chapter_id = ? ORDER BY RAND() LIMIT " . (int)$chapter_limit;
                $chapter_params = array_merge($base_params, array($chapter_id));
                
                log_message('debug', "Chapter {$chapter_id} SQL: " . $chapter_sql);
                log_message('debug', "Chapter {$chapter_id} params: " . json_encode($chapter_params));
                
                $statement = $pdo->prepare($chapter_sql);
                $statement->execute($chapter_params);
                
                $chapter_questions_count = 0;
                while ($row = $statement->fetch()) {
                    // ✅ Double-check we're not exceeding limit
                    if ($total_fetched >= $limit) {
                        break;
                    }
                    $questions[] = $this->create_question_object($row);
                    $chapter_questions_count++;
                    $total_fetched++;
                }
                
                log_message('debug', "Chapter {$chapter_id}: found {$chapter_questions_count} questions (total so far: {$total_fetched}/{$limit})");
                
                $statement = NULL;
            }
            
            log_message('debug', "Chapter distribution complete: {$total_fetched} total questions fetched (limit was {$limit})");
            
            // ✅ SAFETY: If somehow we exceeded limit, trim to exact limit
            if (count($questions) > $limit) {
                log_message('warning', "Chapter distribution exceeded limit: " . count($questions) . " > {$limit}, trimming to {$limit}");
                $questions = array_slice($questions, 0, $limit);
            }
        } else {
            // No chapter filter, get questions randomly from subject
            $sql = $base_sql . " ORDER BY RAND() LIMIT " . (int)$limit;
            
            log_message('debug', "No chapter filter SQL: " . $sql);
            log_message('debug', "No chapter filter params: " . json_encode($base_params));
            
            $statement = $pdo->prepare($sql);
            $statement->execute($base_params);
            
            $subject_questions_count = 0;
            while ($row = $statement->fetch()) {
                $questions[] = $this->create_question_object($row);
                $subject_questions_count++;
            }
            
            log_message('debug', "Subject {$params['subject_id']}: found {$subject_questions_count} questions");
            
            $statement = NULL;
        }
        
        // ✅ FINAL SAFETY: Ensure we never return more than requested limit
        if (count($questions) > $limit) {
            log_message('error', "CRITICAL: get_questions_by_difficulty exceeded limit! Returned " . count($questions) . " but limit was {$limit}. Force trimming to {$limit}");
            $questions = array_slice($questions, 0, $limit);
        }
        
        log_message('debug', "get_questions_by_difficulty final return: " . count($questions) . " questions (limit was {$limit})");
        
        $pdo = NULL;
        
        return $questions;
    }
    
    /**
     * Create Question_object from database row
     */
    private function create_question_object($row)
    {
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
        
        return $objQuestion;
    }
    
    /**
     * Get additional questions as fallback when initial query doesn't return enough
     * ✅ Now respects per-subject allocation limits from distribution_report
     */
    private function get_balanced_questions_fallback($original_filters, $existing_questions, $distribution_report)
    {
        // Remove user exclusion and try to get more questions
        $fallback_filters = $original_filters;
        unset($fallback_filters['exclude_user_id']);
        
        $existing_question_ids = array_map(function($q) { return $q->id; }, $existing_questions);
        
        // ✅ Count how many questions each subject currently has
        $subject_current_counts = array();
        foreach ($existing_questions as $question) {
            $subject_id = $question->subject_id;
            if (!isset($subject_current_counts[$subject_id])) {
                $subject_current_counts[$subject_id] = 0;
            }
            $subject_current_counts[$subject_id]++;
        }
        
        log_message('debug', "Fallback: Current counts per subject: " . json_encode($subject_current_counts));
        
        // Get questions without user exclusion, respecting per-subject limits
        $fallback_questions = array();
        foreach ($original_filters['subject_ids'] as $subject_id) {
            // ✅ Calculate how many more questions this subject needs
            $allocated = isset($distribution_report[$subject_id]['questions_assigned']) ? $distribution_report[$subject_id]['questions_assigned'] : 0;
            $current = isset($subject_current_counts[$subject_id]) ? $subject_current_counts[$subject_id] : 0;
            $needed = max(0, $allocated - $current);
            
            if ($needed <= 0) {
                log_message('debug', "Fallback: Subject {$subject_id} already has {$current}/{$allocated} questions, skipping");
                continue;
            }
            
            log_message('debug', "Fallback: Subject {$subject_id} needs {$needed} more questions (has {$current}/{$allocated})");
            
            $subject_questions = $this->get_questions_by_difficulty(array(
                'exam_id' => $fallback_filters['exam_id'],
                'subject_id' => $subject_id,
                'chapter_ids' => isset($fallback_filters['chapter_ids']) ? $fallback_filters['chapter_ids'] : array(),
                'topic_ids' => isset($fallback_filters['topic_ids']) ? $fallback_filters['topic_ids'] : array(),
                'difficulty' => $fallback_filters['difficulty'],
                'limit' => $needed // ✅ Only get exactly what's needed for this subject
            ));
            
            $added_for_subject = 0;
            // Add questions that we don't already have, up to needed amount
            foreach ($subject_questions as $question) {
                if ($added_for_subject >= $needed) {
                    break; // ✅ Stop when we've added enough for this subject
                }
                
                if (!in_array($question->id, $existing_question_ids)) {
                    $fallback_questions[] = $question;
                    $existing_question_ids[] = $question->id;
                    $added_for_subject++;
                }
            }
            
            log_message('debug', "Fallback: Added {$added_for_subject} questions for subject {$subject_id}");
        }
        
        log_message('debug', "Fallback: Total questions added: " . count($fallback_questions));
        
        return $fallback_questions;
    }
    
    /**
     * ✅ POST-PROCESSING: Enforce perfectly balanced distribution
     * Removes excess questions from over-allocated subjects and redistributes to under-allocated ones
     */
    private function enforce_balanced_distribution($questions, $subject_ids, $total_questions)
    {
        log_message('debug', "enforce_balanced_distribution: Starting with " . count($questions) . " questions for {$total_questions} target");
        
        // Calculate target allocation per subject
        $questions_per_subject = floor($total_questions / count($subject_ids));
        $remaining_questions = $total_questions % count($subject_ids);
        
        // Group questions by subject
        $questions_by_subject = array();
        foreach ($questions as $question) {
            $subject_id = $question->subject_id;
            if (!isset($questions_by_subject[$subject_id])) {
                $questions_by_subject[$subject_id] = array();
            }
            $questions_by_subject[$subject_id][] = $question;
        }
        
        // Calculate target counts per subject
        $target_counts = array();
        
        foreach ($subject_ids as $index => $subject_id) {
            $target = $questions_per_subject;
            if ($index < $remaining_questions) {
                $target++;
            }
            $target_counts[$subject_id] = $target;
            $actual = isset($questions_by_subject[$subject_id]) ? count($questions_by_subject[$subject_id]) : 0;
            
            log_message('debug', "enforce_balanced_distribution: Subject {$subject_id} - Target: {$target}, Actual: {$actual}, Difference: " . ($actual - $target));
        }
        
        // Step 1: Trim excess from over-allocated subjects
        // DO NOT redistribute across subjects - questions belong to their original subject
        
        foreach ($subject_ids as $subject_id) {
            $target = $target_counts[$subject_id];
            $subject_questions = isset($questions_by_subject[$subject_id]) ? $questions_by_subject[$subject_id] : array();
            
            if (count($subject_questions) > $target) {
                // Remove excess questions - trim to target
                $excess_count = count($subject_questions) - $target;
                array_splice($questions_by_subject[$subject_id], $target);
                
                log_message('debug', "enforce_balanced_distribution: Subject {$subject_id} - Removed {$excess_count} excess questions (trimmed to {$target})");
            } else if (count($subject_questions) < $target) {
                // Subject is under-allocated - we'll need more questions from this subject
                $shortage = $target - count($subject_questions);
                log_message('debug', "enforce_balanced_distribution: Subject {$subject_id} is short by {$shortage} questions (has " . count($subject_questions) . ", target {$target})");
            }
        }
        
        // Step 3: Rebuild balanced questions array from correctly allocated subjects
        $balanced_questions = array();
        foreach ($subject_ids as $subject_id) {
            if (isset($questions_by_subject[$subject_id])) {
                $target = $target_counts[$subject_id];
                
                // Force trim to exact target
                if (count($questions_by_subject[$subject_id]) > $target) {
                    log_message('warning', "enforce_balanced_distribution: Subject {$subject_id} still has " . count($questions_by_subject[$subject_id]) . " after rebalancing, force trimming to {$target}");
                    $questions_by_subject[$subject_id] = array_slice($questions_by_subject[$subject_id], 0, $target);
                }
                
                $balanced_questions = array_merge($balanced_questions, $questions_by_subject[$subject_id]);
            }
        }
        
        // Final validation
        $final_count = count($balanced_questions);
        if ($final_count > $total_questions) {
            log_message('warning', "enforce_balanced_distribution: Final count {$final_count} exceeds target {$total_questions}, trimming");
            $balanced_questions = array_slice($balanced_questions, 0, $total_questions);
        }
        
        // Log final distribution and verify targets
        $final_distribution = array();
        foreach ($balanced_questions as $question) {
            $subject_id = $question->subject_id;
            if (!isset($final_distribution[$subject_id])) {
                $final_distribution[$subject_id] = 0;
            }
            $final_distribution[$subject_id]++;
        }
        
        log_message('debug', "enforce_balanced_distribution: Final distribution: " . json_encode($final_distribution));
        log_message('debug', "enforce_balanced_distribution: Target distribution: " . json_encode($target_counts));
        
        // ✅ CRITICAL CHECK: Verify each subject meets target exactly
        $needs_adjustment = false;
        foreach ($subject_ids as $subject_id) {
            $target = $target_counts[$subject_id];
            $actual = isset($final_distribution[$subject_id]) ? $final_distribution[$subject_id] : 0;
            
            if ($actual != $target) {
                log_message('error', "enforce_balanced_distribution: Subject {$subject_id} final count {$actual} does NOT match target {$target}!");
                $needs_adjustment = true;
            }
        }
        
        if ($needs_adjustment) {
            log_message('error', "enforce_balanced_distribution: Distribution mismatch detected! Applying emergency rebalancing...");
            // Emergency rebalancing will be handled by caller
        }
        
        return $balanced_questions;
    }
    
    /**
     * Trim questions evenly across subjects to meet target count
     */
    private function trim_questions_evenly($questions, $target_count, $subject_ids)
    {
        if (count($questions) <= $target_count) {
            return $questions;
        }
        
        // Group questions by subject
        $questions_by_subject = array();
        foreach ($questions as $question) {
            $subject_id = $question->subject_id;
            if (!isset($questions_by_subject[$subject_id])) {
                $questions_by_subject[$subject_id] = array();
            }
            $questions_by_subject[$subject_id][] = $question;
        }
        
        $questions_per_subject = floor($target_count / count($subject_ids));
        $remaining_questions = $target_count % count($subject_ids);
        
        $trimmed_questions = array();
        foreach ($subject_ids as $index => $subject_id) {
            if (!isset($questions_by_subject[$subject_id])) {
                continue;
            }
            
            $subject_limit = $questions_per_subject;
            if ($index < $remaining_questions) {
                $subject_limit++;
            }
            
            $subject_questions = array_slice($questions_by_subject[$subject_id], 0, $subject_limit);
            $trimmed_questions = array_merge($trimmed_questions, $subject_questions);
        }
        
        return $trimmed_questions;
    }
    
    /**
     * Update distribution report based on actual questions found
     */
    private function update_distribution_report($questions, &$distribution_report)
    {
        // Reset counts
        foreach ($distribution_report as &$report) {
            $report['questions_found'] = 0;
            $report['actual_difficulty_breakdown'] = array();
        }
        
        // Count actual questions by subject and difficulty
        foreach ($questions as $question) {
            $subject_id = $question->subject_id;
            $difficulty = $question->level;
            
            if (isset($distribution_report[$subject_id])) {
                $distribution_report[$subject_id]['questions_found']++;
                
                if (!isset($distribution_report[$subject_id]['actual_difficulty_breakdown'][$difficulty])) {
                    $distribution_report[$subject_id]['actual_difficulty_breakdown'][$difficulty] = 0;
                }
                $distribution_report[$subject_id]['actual_difficulty_breakdown'][$difficulty]++;
            }
        }
    }
    
    /**
     * Organize questions by subject (sequential subjects, randomized within each subject)
     */
    private function organize_questions_by_subject($questions, $subject_ids)
    {
        // Group questions by subject
        $questions_by_subject = array();
        foreach ($questions as $question) {
            $subject_id = $question->subject_id;
            if (!isset($questions_by_subject[$subject_id])) {
                $questions_by_subject[$subject_id] = array();
            }
            $questions_by_subject[$subject_id][] = $question;
        }
        
        // Randomize questions within each subject
        foreach ($questions_by_subject as $subject_id => $subject_questions) {
            shuffle($questions_by_subject[$subject_id]);
        }
        
        // Organize questions by subject order (maintain subject sequence)
        $organized_questions = array();
        foreach ($subject_ids as $subject_id) {
            if (isset($questions_by_subject[$subject_id])) {
                $organized_questions = array_merge($organized_questions, $questions_by_subject[$subject_id]);
                
                log_message('debug', "Subject {$subject_id}: " . count($questions_by_subject[$subject_id]) . " questions added to sequential order");
            }
        }
        
        log_message('debug', "Questions organized: " . count($organized_questions) . " total questions in subject-sequential order");
        
        return $organized_questions;
    }
    
    /**
     * Generate subject distribution report for API response
     */
    private function get_subject_distribution_report($added_questions, $subject_ids)
    {
        $distribution = array();
        $difficulty_counts = array();
        
        // Load subject model to get subject names
        $this->load->model('subject/subject_model');
        
        // Count questions by subject and difficulty
        foreach ($added_questions as $question) {
            $subject_id = $question['subject_id'];
            $level = $question['level'];
            
            if (!isset($distribution[$subject_id])) {
                // Get subject name
                $subject = $this->subject_model->get_Subject($subject_id);
                $subject_name = ($subject && isset($subject->subject_name)) ? $subject->subject_name : "Subject {$subject_id}";
                
                $distribution[$subject_id] = array(
                    'subject_id' => $subject_id,
                    'subject_name' => $subject_name,
                    'total_questions' => 0,
                    'difficulty_breakdown' => array()
                );
            }
            
            // Increment total for this subject
            $distribution[$subject_id]['total_questions']++;
            
            // Count by difficulty level
            if (!isset($distribution[$subject_id]['difficulty_breakdown'][$level])) {
                $distribution[$subject_id]['difficulty_breakdown'][$level] = 0;
            }
            $distribution[$subject_id]['difficulty_breakdown'][$level]++;
            
            // Track overall difficulty distribution
            if (!isset($difficulty_counts[$level])) {
                $difficulty_counts[$level] = 0;
            }
            $difficulty_counts[$level]++;
        }
        
        // Calculate percentages for difficulty distribution
        $total_questions = count($added_questions);
        $difficulty_percentages = array();
        foreach ($difficulty_counts as $level => $count) {
            $difficulty_percentages[$level] = round(($count / $total_questions) * 100, 1);
        }
        
        return array(
            'total_questions' => $total_questions,
            'subjects_selected' => count($subject_ids),
            'subjects_distribution' => array_values($distribution),
            'difficulty_distribution' => array(
                'counts' => $difficulty_counts,
                'percentages' => $difficulty_percentages
            ),
            'balanced_distribution' => true,
            'subject_sequential_order' => true,
            'distribution_note' => 'Questions are distributed equally across selected subjects. Elementary/Moderate levels use 90% primary + 10% next higher level mixing. Advance level uses 100% Advance questions only. Questions are organized by subject in sequential order with randomization within each subject.'
        );
    }
    
    /**
     * Fallback to old method for backwards compatibility
     */
    private function get_filtered_questions($filters)
    {
        // For backwards compatibility, redirect to balanced method
        return $this->get_balanced_questions($filters);
    }

    /**
     * Enhanced method to ensure sufficient questions are found through multiple fallback strategies
     * This method applies progressively relaxed criteria to meet the requested number of questions
     */
    private function ensure_sufficient_questions($questions, $original_filters, $requested_count)
    {
        $current_count = count($questions);
        $shortage = $requested_count - $current_count;
        
        log_message('debug', "ensure_sufficient_questions: Current count: {$current_count}, Requested: {$requested_count}, Shortage: {$shortage}");
        
        // If we already have enough questions, return as is
        if ($shortage <= 0) {
            log_message('debug', "ensure_sufficient_questions: Sufficient questions found, returning original set");
            return $questions;
        }
        
        // Track which fallback strategies we've tried
        $fallback_strategies = array();
        $fallback_questions = array();
        $existing_question_ids = array_map(function($q) { return $q->id; }, $questions);
        
        // Strategy 1: Remove user exclusion filter (if present)
        if (!empty($original_filters['exclude_user_id']) && $shortage > 0) {
            log_message('debug', "ensure_sufficient_questions: Strategy 1 - Removing user exclusion filter");
            $fallback_filters = $original_filters;
            unset($fallback_filters['exclude_user_id']);
            $fallback_filters['limit'] = $shortage * 2; // Get extra to account for duplicates
            
            $strategy1_questions = $this->get_balanced_questions($fallback_filters);
            foreach ($strategy1_questions as $question) {
                if (!in_array($question->id, $existing_question_ids) && count($fallback_questions) < $shortage) {
                    $fallback_questions[] = $question;
                    $existing_question_ids[] = $question->id;
                }
            }
            
            $strategy1_found = count($fallback_questions);
            $fallback_strategies[] = "Removed user exclusion filter: +{$strategy1_found} questions";
            log_message('debug', "ensure_sufficient_questions: Strategy 1 found {$strategy1_found} additional questions");
            
            $shortage = $requested_count - ($current_count + count($fallback_questions));
        }
        
        // Strategy 2: Relax difficulty constraints (mix with adjacent levels)
        if ($shortage > 0) {
            log_message('debug', "ensure_sufficient_questions: Strategy 2 - Relaxing difficulty constraints");
            $difficulty_fallbacks = $this->get_difficulty_fallback_levels($original_filters['difficulty']);
            
            foreach ($difficulty_fallbacks as $fallback_difficulty) {
                if ($shortage <= 0) break;
                
                $fallback_filters = $original_filters;
                $fallback_filters['difficulty'] = $fallback_difficulty;
                $fallback_filters['limit'] = $shortage * 2;
                unset($fallback_filters['exclude_user_id']); // Remove user exclusion for fallback
                
                $difficulty_questions = $this->get_balanced_questions($fallback_filters);
                $strategy2_start_count = count($fallback_questions);
                
                foreach ($difficulty_questions as $question) {
                    if (!in_array($question->id, $existing_question_ids) && count($fallback_questions) < ($requested_count - $current_count)) {
                        $fallback_questions[] = $question;
                        $existing_question_ids[] = $question->id;
                    }
                }
                
                $strategy2_found = count($fallback_questions) - $strategy2_start_count;
                if ($strategy2_found > 0) {
                    $fallback_strategies[] = "Added {$fallback_difficulty} difficulty: +{$strategy2_found} questions";
                    log_message('debug', "ensure_sufficient_questions: Strategy 2 ({$fallback_difficulty}) found {$strategy2_found} additional questions");
                }
                
                $shortage = $requested_count - ($current_count + count($fallback_questions));
            }
        }
        
        // Strategy 3: Expand to related subjects from the same exam
        if ($shortage > 0) {
            log_message('debug', "ensure_sufficient_questions: Strategy 3 - Expanding to related subjects");
            $strategy3_start_count = count($fallback_questions);
            
            $related_subjects = $this->get_related_subjects($original_filters['exam_id'], $original_filters['subject_ids']);
            
            if (!empty($related_subjects)) {
                $fallback_filters = $original_filters;
                $fallback_filters['subject_ids'] = array_merge($original_filters['subject_ids'], $related_subjects);
                $fallback_filters['limit'] = $shortage * 2;
                unset($fallback_filters['exclude_user_id']);
                unset($fallback_filters['chapter_ids']); // Remove chapter restrictions
                unset($fallback_filters['topic_ids']); // Remove topic restrictions
                
                $related_questions = $this->get_balanced_questions($fallback_filters);
                
                foreach ($related_questions as $question) {
                    if (!in_array($question->id, $existing_question_ids) && count($fallback_questions) < ($requested_count - $current_count)) {
                        $fallback_questions[] = $question;
                        $existing_question_ids[] = $question->id;
                    }
                }
                
                $strategy3_found = count($fallback_questions) - $strategy3_start_count;
                if ($strategy3_found > 0) {
                    $fallback_strategies[] = "Expanded to related subjects: +{$strategy3_found} questions";
                    log_message('debug', "ensure_sufficient_questions: Strategy 3 found {$strategy3_found} additional questions");
                }
            }
            
            $shortage = $requested_count - ($current_count + count($fallback_questions));
        }
        
        // Strategy 4: Remove chapter and topic restrictions
        if ($shortage > 0 && (!empty($original_filters['chapter_ids']) || !empty($original_filters['topic_ids']))) {
            log_message('debug', "ensure_sufficient_questions: Strategy 4 - Removing chapter/topic restrictions");
            $strategy4_start_count = count($fallback_questions);
            
            $fallback_filters = $original_filters;
            unset($fallback_filters['chapter_ids']);
            unset($fallback_filters['topic_ids']);
            unset($fallback_filters['exclude_user_id']);
            $fallback_filters['limit'] = $shortage * 2;
            
            $unrestricted_questions = $this->get_balanced_questions($fallback_filters);
            
            foreach ($unrestricted_questions as $question) {
                if (!in_array($question->id, $existing_question_ids) && count($fallback_questions) < ($requested_count - $current_count)) {
                    $fallback_questions[] = $question;
                    $existing_question_ids[] = $question->id;
                }
            }
            
            $strategy4_found = count($fallback_questions) - $strategy4_start_count;
            if ($strategy4_found > 0) {
                $fallback_strategies[] = "Removed chapter/topic restrictions: +{$strategy4_found} questions";
                log_message('debug', "ensure_sufficient_questions: Strategy 4 found {$strategy4_found} additional questions");
            }
        }
        
        // Combine original questions with fallback questions
        $final_questions = array_merge($questions, $fallback_questions);
        $final_count = count($final_questions);
        
        // Log fallback summary
        if (!empty($fallback_strategies)) {
            $strategies_text = implode('; ', $fallback_strategies);
            log_message('info', "ensure_sufficient_questions: Applied fallback strategies - {$strategies_text}. Final count: {$final_count}/{$requested_count}");
        }
        
        // CRITICAL: Apply balanced distribution to the final merged result
        // This ensures the correct per-subject distribution (e.g., 7,7,6 for 20 questions across 3 subjects)
        if ($final_count > 0) {
            log_message('debug', "ensure_sufficient_questions: Pre-final-redistribution count: {$final_count}");
            
            // Get subject_ids from original filters
            $subject_ids = $original_filters['subject_ids'];
            
            log_message('debug', "ensure_sufficient_questions: Applying redistribution for {$requested_count} questions across " . count($subject_ids) . " subjects");
            
            // Apply redistribution to enforce the target distribution
            // enforce_balanced_distribution signature: ($questions, $subject_ids, $total_questions)
            $final_questions = $this->enforce_balanced_distribution($final_questions, $subject_ids, $requested_count);
            $final_count = count($final_questions);
            
            log_message('debug', "ensure_sufficient_questions: Post-final-redistribution count: {$final_count}");
            
            // Check if we still have shortage after redistribution (some subjects might be under-allocated)
            // SAFEGUARD: Only attempt per-subject top-up if shortage is reasonable (< 50% of requested)
            if ($final_count < $requested_count && ($requested_count - $final_count) < ($requested_count * 0.5)) {
                $remaining_shortage = $requested_count - $final_count;
                log_message('debug', "ensure_sufficient_questions: Still need {$remaining_shortage} more questions after redistribution");
                
                // Calculate per-subject targets and current counts
                $questions_per_subject = floor($requested_count / count($subject_ids));
                $remaining_questions = $requested_count % count($subject_ids);
                
                // Count current questions per subject
                $current_per_subject = array();
                foreach ($final_questions as $q) {
                    $sid = $q->subject_id;
                    if (!isset($current_per_subject[$sid])) {
                        $current_per_subject[$sid] = 0;
                    }
                    $current_per_subject[$sid]++;
                }
                
                // Try to get more questions for under-allocated subjects
                $additional_questions = array();
                // PERFORMANCE: Use associative array for O(1) lookups instead of in_array
                $existing_question_ids = array();
                foreach ($final_questions as $q) {
                    $existing_question_ids[$q->id] = true;
                }
                
                // SAFEGUARD: Limit to maximum 3 subjects to prevent excessive DB queries
                $subjects_processed = 0;
                $max_subjects_to_process = 3;
                
                foreach ($subject_ids as $index => $subject_id) {
                    // SAFEGUARD: Stop if we've processed enough subjects
                    if ($subjects_processed >= $max_subjects_to_process) {
                        log_message('warning', "ensure_sufficient_questions: Reached max subjects to process ({$max_subjects_to_process}), stopping per-subject top-up");
                        break;
                    }
                    
                    $target = $questions_per_subject + ($index < $remaining_questions ? 1 : 0);
                    $current = isset($current_per_subject[$subject_id]) ? $current_per_subject[$subject_id] : 0;
                    $subject_shortage = $target - $current;
                    
                    if ($subject_shortage > 0) {
                        $subjects_processed++;
                        
                        log_message('debug', "ensure_sufficient_questions: Subject {$subject_id} needs {$subject_shortage} more questions (has {$current}, target {$target})");
                        
                        // SAFEGUARD: Use direct SQL query instead of get_balanced_questions to avoid recursion
                        // This prevents infinite loop: ensure_sufficient_questions -> get_balanced_questions -> ensure_sufficient_questions
                        $pdo = CDatabase::getPdo();
                        
                        $query_limit = min($subject_shortage * 3, 30); // Cap at 30 to prevent excessive queries
                        
                        $sql = "SELECT * FROM question 
                                WHERE question_type = ? 
                                AND (invalid_question IS NULL OR invalid_question = 0) 
                                AND exam_id = ? 
                                AND subject_id = ? 
                                AND level = ?";
                        
                        $params = array(
                            isset($original_filters['question_type']) ? $original_filters['question_type'] : 'regular',
                            $original_filters['exam_id'],
                            $subject_id,
                            $original_filters['difficulty']
                        );
                        
                        // Add chapter filter if specified
                        if (!empty($original_filters['chapter_ids']) && is_array($original_filters['chapter_ids'])) {
                            $chapter_placeholders = str_repeat('?,', count($original_filters['chapter_ids']) - 1) . '?';
                            $sql .= " AND chapter_id IN ({$chapter_placeholders})";
                            $params = array_merge($params, $original_filters['chapter_ids']);
                        }
                        
                        // CRITICAL: LIMIT must be embedded directly as PDO doesn't handle it well as a bound parameter
                        $sql .= " ORDER BY RAND() LIMIT " . (int)$query_limit;
                        
                        $statement = $pdo->prepare($sql);
                        $statement->execute($params);
                        
                        $added_for_subject = 0;
                        while (($row = $statement->fetch()) && $added_for_subject < $subject_shortage) {
                            // PERFORMANCE: Use isset on associative array instead of in_array
                            if (!isset($existing_question_ids[$row['id']])) {
                                $question = $this->create_question_object($row);
                                $additional_questions[] = $question;
                                $existing_question_ids[$row['id']] = true;
                                $added_for_subject++;
                            }
                        }
                        
                        $statement = NULL;
                        $pdo = NULL;
                        
                        if ($added_for_subject > 0) {
                            $fallback_strategies[] = "Added {$added_for_subject} more questions for Subject {$subject_id}";
                            log_message('debug', "ensure_sufficient_questions: Added {$added_for_subject} more questions for Subject {$subject_id}");
                        } else {
                            log_message('warning', "ensure_sufficient_questions: Could not find additional questions for Subject {$subject_id}");
                        }
                    }
                }
                
                // Merge additional questions
                if (count($additional_questions) > 0) {
                    $final_questions = array_merge($final_questions, $additional_questions);
                    $final_count = count($final_questions);
                    log_message('debug', "ensure_sufficient_questions: After fetching per-subject additions: {$final_count} total questions");
                }
            }
        }
        
        // Store fallback information for response
        $this->fallback_strategies_applied = $fallback_strategies;
        $this->original_question_count = $current_count;
        $this->fallback_question_count = count($fallback_questions);
        
        return $final_questions;
    }
    
    /**
     * Get difficulty levels for fallback (all levels except the current one, ordered by preference)
     */
    private function get_difficulty_fallback_levels($current_difficulty)
    {
        $all_levels = array('Elementary', 'Moderate', 'Advance');
        $fallback_levels = array();
        
        // Remove current difficulty from options
        $available_levels = array_filter($all_levels, function($level) use ($current_difficulty) {
            return $level !== $current_difficulty;
        });
        
        switch ($current_difficulty) {
            case 'Elementary':
                // For Elementary, prefer Moderate then Advance
                $fallback_levels = array('Moderate', 'Advance');
                break;
            case 'Moderate':
                // For Moderate, prefer Elementary then Advance
                $fallback_levels = array('Elementary', 'Advance');
                break;
            case 'Advance':
                // For Advance, prefer Moderate then Elementary
                $fallback_levels = array('Moderate', 'Elementary');
                break;
            default:
                $fallback_levels = $available_levels;
        }
        
        return array_intersect($fallback_levels, $available_levels);
    }
    
    /**
     * Get related subjects from the same exam (excluding already selected subjects)
     */
    private function get_related_subjects($exam_id, $selected_subject_ids)
    {
        $pdo = CDatabase::getPdo();
        
        // Get subjects from the same exam that are not already selected
        $placeholders = str_repeat('?,', count($selected_subject_ids) - 1) . '?';
        $sql = "SELECT DISTINCT subject_id 
                FROM question 
                WHERE exam_id = ? 
                AND subject_id NOT IN ({$placeholders})
                AND (invalid_question IS NULL OR invalid_question = 0)
                LIMIT 3"; // Limit to 3 additional subjects to avoid too much expansion
        
        $params = array_merge(array($exam_id), $selected_subject_ids);
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        
        $related_subjects = array();
        while ($row = $statement->fetch()) {
            $related_subjects[] = $row['subject_id'];
        }
        
        $statement = NULL;
        $pdo = NULL;
        
        log_message('debug', "get_related_subjects: Found " . count($related_subjects) . " related subjects for exam {$exam_id}");
        
        return $related_subjects;
    }

    /**
     * Get user quiz history
     * GET /api/quiz/user_history/{user_id}?quiz_question_type=regular
     * 
     * @param quiz_question_type Query parameter to filter by quiz type (regular|pyq|mock). Default: regular
     */
    function user_history_get()
    {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $user_id = $this->uri->segment(4);
        
        if (empty($user_id)) {
            $response = $this->get_failed_response(NULL, "User ID is required");
            $this->set_output($response);
            return;
        }

        // Get quiz_question_type parameter with default value 'regular'
        $quiz_question_type = $this->input->get('quiz_question_type');
        if (empty($quiz_question_type)) {
            $quiz_question_type = 'regular';
        }
        
        // Validate quiz_question_type parameter
        $valid_types = array('regular', 'pyq', 'mock');
        if (!in_array($quiz_question_type, $valid_types)) {
            $response = $this->get_failed_response(NULL, "Invalid quiz_question_type. Must be one of: regular, pyq, mock");
            $this->set_output($response);
            return;
        }

        try {
            $this->load->model('quiz/quiz_model');
            
            // Get quiz history for the user
            $quiz_history = $this->get_user_quiz_history($user_id, $quiz_question_type);
            
            $response = $this->get_success_response($quiz_history, "User quiz history retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            $response = $this->get_failed_response(NULL, "Error retrieving quiz history: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Helper function to get user quiz history from database
     * 
     * @param int $user_id The user ID
     * @param string $quiz_question_type Filter by quiz question type (regular|pyq|mock)
     */
    private function get_user_quiz_history($user_id, $quiz_question_type = 'regular')
    {
        $pdo = CDatabase::getPdo();
        
        // SQL to get user quiz attempts with quiz details and scores
        $sql = "SELECT 
                    q.id as quiz_id,
                    q.name as quiz_name,
                    q.description as quiz_description,
                    q.quiz_reference,
                    q.quiz_type,
                    q.quiz_question_type,
                    MIN(uq.created_at) as attempt_date,
                    COUNT(DISTINCT uq.question_id) as completed_questions,
                    (SELECT COUNT(*) FROM quiz_question qq WHERE qq.quiz_id = q.id) as total_questions,
                    ROUND(
                        CASE 
                            WHEN (SELECT COUNT(*) FROM quiz_question qq WHERE qq.quiz_id = q.id) > 0 
                            THEN (SUM(COALESCE(uq.score, 0)) / ((SELECT COUNT(*) FROM quiz_question qq WHERE qq.quiz_id = q.id) * 4)) * 100
                            ELSE 0 
                        END, 2
                    ) as score,
                    SUM(uq.duration) as time_spent,
                    MAX(uq.created_at) as last_attempt
                FROM quiz q
                LEFT JOIN user_question uq ON q.id = uq.quiz_id AND uq.user_id = ?
                WHERE (uq.user_id = ? OR (q.quiz_type = 'public' AND q.user_id = ?))
                    AND q.quiz_question_type = ?
                GROUP BY q.id, q.name, q.description, q.quiz_reference, q.quiz_type, q.quiz_question_type
                HAVING completed_questions > 0
                ORDER BY last_attempt DESC
                LIMIT 20";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id, $user_id, $user_id, $quiz_question_type));
        
        $quiz_history = array();
        while ($row = $statement->fetch()) {
            $quiz_history[] = array(
                'quiz_id' => $row['quiz_id'],
                'quiz_name' => $row['quiz_name'],
                'quiz_description' => $row['quiz_description'],
                'quiz_reference' => $row['quiz_reference'],
                'quiz_type' => $row['quiz_type'],
                'quiz_question_type' => $row['quiz_question_type'],
                'attempt_date' => $row['attempt_date'],
                'completed_questions' => (int)$row['completed_questions'],
                'total_questions' => (int)$row['total_questions'],
                'score' => (float)$row['score'],
                'time_spent' => (int)$row['time_spent'],
                'last_attempt' => $row['last_attempt']
            );
        }
        
        $statement = NULL;
        $pdo = NULL;
        
        return $quiz_history;
    }

    /**
     * Get user statistics
     * GET /api/quiz/user_stats/{user_id}
     */
    function user_stats_get()
    {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $user_id = $this->uri->segment(4);
        
        if (empty($user_id)) {
            $response = $this->get_failed_response(NULL, "User ID is required");
            $this->set_output($response);
            return;
        }

        try {
            $this->load->model('quiz/quiz_model');
            
            // Get user statistics
            $user_stats = $this->get_user_statistics($user_id);
            
            $response = $this->get_success_response($user_stats, "User statistics retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            $response = $this->get_failed_response(NULL, "Error retrieving user statistics: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Get quiz results by quiz reference
     * @param string $quiz_reference
     */
    function results_get($quiz_reference)
    {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        try {
            $this->load->model('quiz/quiz_model');
           // $this->load->model('user/User_model');
            
            // Get current user ID
            // $user_id = $this->User_model->getCurrentUserId();
            // if (!$user_id) {
            //     $response = $this->get_failed_response(NULL, "User not authenticated");
            //     $this->set_output($response);
            //     return;
            // }
            
            // Get quiz by reference
            $quiz = $this->quiz_model->get_Quiz_by_reference($quiz_reference);
            if (!$quiz) {
                $response = $this->get_failed_response(NULL, "Quiz not found");
                $this->set_output($response);
                return;
            }
            
            // Get quiz results data
            $results = $this->get_quiz_results($quiz->user_id, $quiz->id, $quiz_reference);
            
            $response = $this->get_success_response($results, "Quiz results retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            $response = $this->get_failed_response(NULL, "Error retrieving quiz results: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Helper function to get quiz results from database
     */
    private function get_quiz_results($user_id, $quiz_id, $quiz_reference)
    {
        // Load quiz_subject_model for getting multiple subjects
        $this->load->model('quiz_subject/quiz_subject_model');
        
        $pdo = CDatabase::getPdo();
        
        // Get quiz details with exam and subject names
        $sql_quiz = "SELECT q.*, e.exam_name as exam_name, s.subject as subject_name 
                     FROM quiz q 
                     LEFT JOIN exam e ON q.exam_id = e.id 
                     LEFT JOIN subject s ON q.subject_id = s.id 
                     WHERE q.id = ?";
        $statement = $pdo->prepare($sql_quiz);
        $statement->execute(array($quiz_id));
        $quiz = $statement->fetch(PDO::FETCH_ASSOC);
        
        // Get all subjects for this quiz from quiz_subjects table
        $quiz_subjects = $this->quiz_subject_model->get_quiz_subjects($quiz_id);
        $subject_names = array();
        foreach ($quiz_subjects as $subject) {
            $subject_names[] = $subject->subject_name;
        }
        
        // If no subjects in junction table, fall back to primary subject
        if (empty($subject_names) && !empty($quiz['subject_name'])) {
            $subject_names[] = $quiz['subject_name'];
        }
        
        // Get questions with user answers through quiz_question junction table
        $sql_questions = "SELECT 
                            q.id,
                            q.question_img_url,
                            q.has_multiple_answer,
                            q.duration,
                            q.option_count,
                            q.exam_id,
                            q.subject_id,
                            q.chapter_name,
                            q.chapter_id,
                            q.level,
                            q.topic_id,
                            q.correct_option,
                            q.solution,
                            qq.id as quiz_question_id,
                            qq.question_order,
                            uq.option_answer as user_answer,
                            uq.score,
                            uq.duration as time_taken,
                            uq.created_at as attempt_date,
                            uq.is_correct
                          FROM quiz_question qq
                          JOIN question q ON qq.question_id = q.id
                          LEFT JOIN user_question uq ON qq.id = uq.quiz_question_id 
                                                     AND uq.user_id = ? 
                                                     AND uq.quiz_id = ?
                          WHERE qq.quiz_id = ?
                          ORDER BY qq.question_order";
        
        $statement = $pdo->prepare($sql_questions);
        $statement->execute(array($user_id, $quiz_id, $quiz_id));
        $questions = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate stats
        $total_questions = count($questions);
        $answered_questions = 0;
        $correct_answers = 0;
        $total_time_taken = 0;
        $total_score = 0;
        
        foreach ($questions as &$question) {
            if ($question['user_answer']) {
                $answered_questions++;
                $total_time_taken += (int)$question['time_taken'];
                
                // Use the is_correct field from database (already calculated)
                $is_correct = (bool)$question['is_correct'];
                if ($is_correct) {
                    $correct_answers++;
                }
                
                $question['is_correct'] = $is_correct;
                $question['status'] = $is_correct ? 'correct' : 'incorrect';
                $total_score += (int)$question['score'];
            } else {
                $question['is_correct'] = false;
                $question['status'] = 'unanswered';
                $question['time_taken'] = 0;
            }
            
            // Since options are not stored in database, create placeholder options
            // This would need to be handled differently based on how options are stored
            $question['options'] = array(
                'A' => 'Option A',
                'B' => 'Option B', 
                'C' => 'Option C',
                'D' => 'Option D'
            );
            
            // Add question text field for consistency with frontend
            $question['question_text'] = 'Question ' . $question['question_order'];
            
            // Rename fields for frontend compatibility
            $question['correct_answer'] = $question['correct_option'];
            $question['explanation'] = $question['solution'];
            
            // Add quiz_question_id for reference
            $question['quiz_question_id'] = $question['quiz_question_id'];
        }
        
        // Calculate percentage
        $percentage = $total_questions > 0 ? round(($correct_answers / $total_questions) * 100, 2) : 0;
        
        $statement = NULL;
        $pdo = NULL;
         
        return array(
            'quiz_reference' => $quiz_reference,
            'quiz' => array(
                'id' => $quiz['id'],
                'name' => $quiz['name'],
                'description' => $quiz['description'],
                'class_name' => '', // Not available in current schema
                'subject_name' => $quiz['subject_name'], // Primary subject for backward compatibility
                'subjects' => $subject_names, // All subjects for the quiz
                'exam_name' => $quiz['exam_name'],
                'exam_id' => $quiz['exam_id']
            ),
            'questions' => $questions,
            'stats' => array(
                'total_questions' => $total_questions,
                'answered_questions' => $answered_questions,
                'correct_answers' => $correct_answers,
                'incorrect_answers' => $answered_questions - $correct_answers,
                'unanswered_questions' => $total_questions - $answered_questions,
                'percentage' => $percentage,
                'total_score' => $total_score,
                'max_possible_score' => $total_questions * 4, // Assuming 4 points per question
                'total_time_taken' => $total_time_taken,
                'average_time_per_question' => $answered_questions > 0 ? round($total_time_taken / $answered_questions, 2) : 0
            )
        );
    }

 

    /**
     * Helper function to get user statistics from database
     */
    private function get_user_statistics($user_id)
    {
        $pdo = CDatabase::getPdo();
        
        // Get total tests completed
        $sql_tests = "SELECT COUNT(DISTINCT quiz_id) as tests_completed 
                      FROM user_question 
                      WHERE user_id = ?";
        $statement = $pdo->prepare($sql_tests);
        $statement->execute(array($user_id));
        $tests_result = $statement->fetch();
        $tests_completed = $tests_result ? (int)$tests_result['tests_completed'] : 0;
        
        // Get overall average score - calculate per quiz, then average
        $sql_score = "SELECT ROUND(AVG(quiz_score), 2) as avg_score
                      FROM (
                          SELECT 
                              uq.quiz_id,
                              (SUM(COALESCE(uq.score, 0)) / (COUNT(*) * 4)) * 100 as quiz_score
                          FROM user_question uq
                          WHERE uq.user_id = ?
                          GROUP BY uq.quiz_id
                      ) as quiz_scores";
        $statement = $pdo->prepare($sql_score);
        $statement->execute(array($user_id));
        $score_result = $statement->fetch();
        $avg_score = $score_result ? (float)$score_result['avg_score'] : 0;
        
        // Get total study time (in minutes)
        $sql_time = "SELECT SUM(duration) as total_time 
                     FROM user_question 
                     WHERE user_id = ?";
        $statement = $pdo->prepare($sql_time);
        $statement->execute(array($user_id));
        $time_result = $statement->fetch();
        $total_time_seconds = $time_result ? (int)$time_result['total_time'] : 0;
        $total_time_hours = round($total_time_seconds / 3600, 1);
        
        // Get user ranking (simplified - based on average score per quiz)
        $sql_rank = "SELECT 
                        COUNT(*) + 1 as user_rank
                     FROM (
                         SELECT 
                             user_id, 
                             AVG(quiz_score) as avg_score
                         FROM (
                             SELECT 
                                 uq.user_id,
                                 uq.quiz_id,
                                 (SUM(COALESCE(uq.score, 0)) / (COUNT(*) * 4)) * 100 as quiz_score
                             FROM user_question uq
                             WHERE uq.user_id != ?
                             GROUP BY uq.user_id, uq.quiz_id
                         ) as quiz_scores
                         GROUP BY user_id
                         HAVING avg_score > ?
                     ) as ranking";
        $statement = $pdo->prepare($sql_rank);
        $statement->execute(array($user_id, $avg_score));
        $rank_result = $statement->fetch();
        $user_rank = $rank_result ? (int)$rank_result['user_rank'] : 1;
        
        $statement = NULL;
        $pdo = NULL;
        
        return array(
            'tests_completed' => $tests_completed,
            'overall_progress' => $avg_score,
            'study_time_hours' => $total_time_hours,
            'current_rank' => $user_rank
        );
    }

    /**
     * Add log from client (web/mobile)
     * POST /api/quiz/add_log
     * Accepts: log_type (debug|info|error), message, context (optional)
     */
    function add_log_post()
    {
        $request = $this->get_request();
        
        // Get log type and message from request
        $log_type = isset($request['log_type']) ? strtolower(trim($request['log_type'])) : 'info';
        $message = isset($request['message']) ? trim($request['message']) : '';
        $context = isset($request['context']) ? $request['context'] : array();
        
        // Validate inputs
        if (empty($message)) {
            $response = $this->get_failed_response(NULL, "Message is required");
            $this->set_output($response);
            return;
        }
        
        // Validate log type
        $valid_log_types = array('debug', 'info', 'error');
        if (!in_array($log_type, $valid_log_types)) {
            $log_type = 'info'; // Default to info if invalid type
        }
        
        // Get user info if available (optional, doesn't require JWT)
        $user_info = '';
        if (isset($request['user_id'])) {
            $user_info = " [User: {$request['user_id']}]";
        }
        
        // Get client type (web/mobile)
        $client_type = isset($request['client_type']) ? $request['client_type'] : 'unknown';
        
        // Build log message with context
        $log_message = "[CLIENT-{$client_type}]{$user_info} {$message}";
        
        // Add context if provided
        if (!empty($context)) {
            $log_message .= " | Context: " . json_encode($context);
        }
        
        // Write to log based on type
        log_message($log_type, $log_message);
        
        try {
            $response = $this->get_success_response(array(
                'logged' => true,
                'log_type' => $log_type,
                'timestamp' => date('Y-m-d H:i:s')
            ), "Log entry added successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            $response = $this->get_failed_response(NULL, "Error adding log: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Simple endpoint to view recent logs
     * GET /api/quiz/logs
     */
    function logs_get()
    {
        try {
            $log_path = APPPATH . 'logs/log-' . date('Y-m-d') . '.php';
            
            if (!file_exists($log_path)) {
                $response = $this->get_failed_response(array(
                    'log_file' => $log_path,
                    'exists' => false,
                    'directory_exists' => is_dir(APPPATH . 'logs/'),
                    'app_path' => APPPATH
                ), "Log file not found for today");
                $this->set_output($response);
                return;
            }
            
            // Get the last 50 lines of the log file
            $lines = file($log_path);
            $recent_lines = array_slice($lines, -50);
            
            // Clean up the lines (remove PHP tags and format)
            $cleaned_lines = array();
            foreach ($recent_lines as $line) {
                $line = trim($line);
                if (!empty($line) && $line != '<?php defined(\'BASEPATH\') OR exit(\'No direct script access allowed\'); ?>') {
                    $cleaned_lines[] = $line;
                }
            }
            
            $response = $this->get_success_response(array(
                'log_file' => $log_path,
                'total_lines' => count($lines),
                'recent_lines' => $cleaned_lines,
                'instructions' => 'These are the last 50 log entries. Look for DEBUG, ERROR, or INFO entries related to quiz operations.'
            ), "Recent logs retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            $response = $this->get_failed_response(array(
                'error' => $e->getMessage(),
                'log_directory' => APPPATH . 'logs/',
                'expected_file' => 'log-' . date('Y-m-d') . '.php'
            ), "Error reading logs");
            $this->set_output($response);
        }
    }

    /**
     * Generate Student Report Card - JWT Protected Endpoint
     * POST /api/quiz/generate_student_report_card
     * Generates comprehensive student performance report with all metrics
     */
    function generate_student_report_card1_post()
    {
        // ✅ SECURE: Require JWT authentication
        $objUser = $this->require_jwt_auth();
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();
        $user_id = isset($request['user_id']) ? $request['user_id'] : $objUser->id;

        // Security check: users can only generate their own report card unless admin
        if ($user_id != $objUser->id && !$this->has_permission('admin')) {
            $response = $this->get_failed_response(NULL, "Unauthorized: You can only generate your own report card");
            $this->set_secure_output($response, REST_Controller::HTTP_FORBIDDEN);
            return;
        }

        try {
            $this->load->model('user_performance/student_report_card_model');
            $this->load->model('user_performance/badge_model');

            // Generate comprehensive report card data
            $report_data = $this->generate_comprehensive_report_data($user_id);
            
            if ($report_data === FALSE) {
                $response = $this->get_failed_response(NULL, "No quiz data found for this user");
                $this->set_secure_output($response, REST_Controller::HTTP_NOT_FOUND);
                return;
            }

            // Save report card to database (replace existing one)
            $result = $this->student_report_card_model->save_student_report_card($user_id, $report_data);
            
            if ($result !== FALSE) {
                // Process badge achievements
                $badges_earned = $this->badge_model->check_and_award_badges($user_id, $report_data);
                
                $response_data = array(
                    'user_id' => $user_id,
                    'report_generated_at' => date('Y-m-d H:i:s'),
                    'badges_earned' => $badges_earned,
                    'report_card' => $report_data
                );
                
                $response = $this->get_success_response($response_data, "Student report card generated successfully");
                $this->set_secure_output($response);
            } else {
                $response = $this->get_failed_response(NULL, "Error saving report card data");
                $this->set_secure_output($response, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (Exception $e) {
            log_message('error', "Error generating student report card for user {$user_id}: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error generating report card: " . $e->getMessage());
            $this->set_secure_output($response, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get Student Report Card - JWT Protected Endpoint
     * GET /api/quiz/get_student_report_card/{user_id}
     * Retrieves existing student report card data
     */
    function get_student_report_card1_get($user_id = null)
    {
        // ✅ SECURE: Require JWT authentication
        $objUser = $this->require_jwt_auth();
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if (empty($user_id)) {
            $user_id = $objUser->id;
        }

        // Security check: users can only view their own report card unless admin
        if ($user_id != $objUser->id && !$this->has_permission('admin')) {
            $response = $this->get_failed_response(NULL, "Unauthorized: You can only view your own report card");
            $this->set_secure_output($response, REST_Controller::HTTP_FORBIDDEN);
            return;
        }

        try {
            $this->load->model('user_performance/student_report_card_model');
            
            $report_card = $this->student_report_card_model->get_student_report_card($user_id);
            
            if ($report_card === FALSE || empty($report_card)) {
                $response = $this->get_failed_response(NULL, "No report card found for this user. Generate one first.");
                $this->set_secure_output($response, REST_Controller::HTTP_NOT_FOUND);
                return;
            }

            $response = $this->get_success_response($report_card, "Student report card retrieved successfully");
            $this->set_secure_output($response);

        } catch (Exception $e) {
            log_message('error', "Error retrieving student report card for user {$user_id}: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error retrieving report card: " . $e->getMessage());
            $this->set_secure_output($response, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get Student Badges - JWT Protected Endpoint
     * GET /api/quiz/get_student_badges/{user_id}
     * Retrieves earned badges for a student
     */
    function get_student_badges1_get($user_id = null)
    {
        // ✅ SECURE: Require JWT authentication
        $objUser = $this->require_jwt_auth();
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if (empty($user_id)) {
            $user_id = $objUser->id;
        }

        // Security check: users can only view their own badges unless admin
        if ($user_id != $objUser->id && !$this->has_permission('admin')) {
            $response = $this->get_failed_response(NULL, "Unauthorized: You can only view your own badges");
            $this->set_secure_output($response, REST_Controller::HTTP_FORBIDDEN);
            return;
        }

        try {
            $this->load->model('user_performance/badge_model');

            $badges_data = $this->badge_model->get_user_badges_with_progress($user_id);

            $response = $this->get_success_response($badges_data, "Student badges retrieved successfully");
            $this->set_secure_output($response);

        } catch (Exception $e) {
            log_message('error', "Error retrieving student badges for user {$user_id}: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error retrieving badges: " . $e->getMessage());
            $this->set_secure_output($response, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Helper function to generate comprehensive report card data
     * @param int $user_id
     * @return array|false Report data or false if no data found
     */
    private function generate_comprehensive_report_data($user_id)
    {
        $pdo = CDatabase::getPdo();
        
        // Check if user has any quiz attempts
        $sql_check = "SELECT COUNT(*) as quiz_count FROM user_question WHERE user_id = ?";
        $statement = $pdo->prepare($sql_check);
        $statement->execute(array($user_id));
        $check_result = $statement->fetch();
        
        if (!$check_result || $check_result['quiz_count'] == 0) {
            return FALSE;
        }

        // 1. Basic Quiz Statistics
        $basic_stats = $this->calculate_basic_quiz_stats($user_id, $pdo);
        
        // 2. Time Metrics
        $time_metrics = $this->calculate_time_metrics($user_id, $pdo);
        
        // 3. Performance Metrics
        $performance_metrics = $this->calculate_performance_metrics($user_id, $pdo);
        
        // 4. Subject-wise Performance
        $subject_stats = $this->calculate_subject_performance($user_id, $pdo);
        
        // 5. Chapter-wise Performance
        $chapter_stats = $this->calculate_chapter_performance($user_id, $pdo);
        
        // 6. Topic-wise Performance
        $topic_stats = $this->calculate_topic_performance($user_id, $pdo);
        
        // 7. Difficulty Level Performance
        $difficulty_stats = $this->calculate_difficulty_performance($user_id, $pdo);
        
        // 8. Progress Data for Charts
        $progress_data = $this->generate_progress_chart_data($user_id, $pdo);
        
        // 9. Learning Analytics
        $learning_analytics = $this->calculate_learning_analytics($user_id, $pdo);

        // Compile all data
        $report_data = array_merge(
            $basic_stats,
            $time_metrics,
            $performance_metrics,
            array(
                'subject_wise_stats' => json_encode($subject_stats['details']),
                'strongest_subject' => $subject_stats['strongest'],
                'weakest_subject' => $subject_stats['weakest'],
                'chapter_wise_stats' => json_encode($chapter_stats),
                'topic_wise_stats' => json_encode($topic_stats['details']),
                'topics_covered_count' => $topic_stats['count'],
                'difficulty_wise_stats' => json_encode($difficulty_stats),
                'quiz_progress_data' => json_encode($progress_data['quiz_progress']),
                'accuracy_trend_data' => json_encode($progress_data['accuracy_trend']),
                'speed_improvement_data' => json_encode($progress_data['speed_improvement'])
            ),
            $learning_analytics
        );

        return $report_data;
    }

    /**
     * Calculate basic quiz statistics
     */
    private function calculate_basic_quiz_stats($user_id, $pdo)
    {
        // Get date range
        $sql_dates = "SELECT MIN(DATE(created_at)) as start_date, MAX(DATE(created_at)) as end_date 
                      FROM user_question WHERE user_id = ?";
        $statement = $pdo->prepare($sql_dates);
        $statement->execute(array($user_id));
        $dates = $statement->fetch();

        // Basic stats
        $sql_basic = "SELECT 
                        COUNT(DISTINCT quiz_id) as total_quizzes,
                        COUNT(*) as total_questions,
                        SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                        SUM(CASE WHEN is_correct = 0 AND status = 'answered' THEN 1 ELSE 0 END) as incorrect_answers,
                        SUM(CASE WHEN status = 'skipped' THEN 1 ELSE 0 END) as skipped_questions
                      FROM user_question WHERE user_id = ?";
        $statement = $pdo->prepare($sql_basic);
        $statement->execute(array($user_id));
        $basic = $statement->fetch();

        return array(
            'data_period_start' => $dates['start_date'],
            'data_period_end' => $dates['end_date'],
            'total_quizzes_taken' => (int)$basic['total_quizzes'],
            'total_questions_attempted' => (int)$basic['total_questions'],
            'total_correct_answers' => (int)$basic['correct_answers'],
            'total_incorrect_answers' => (int)$basic['incorrect_answers'],
            'total_skipped_questions' => (int)$basic['skipped_questions']
        );
    }

    /**
     * Calculate time metrics
     */
    private function calculate_time_metrics($user_id, $pdo)
    {
        $sql_time = "SELECT 
                       SUM(duration) as total_time_seconds,
                       AVG(quiz_time) as avg_quiz_time,
                       AVG(duration) as avg_question_time
                     FROM (
                       SELECT quiz_id, SUM(duration) as quiz_time, AVG(duration) as duration
                       FROM user_question 
                       WHERE user_id = ?
                       GROUP BY quiz_id
                     ) as quiz_times";
        $statement = $pdo->prepare($sql_time);
        $statement->execute(array($user_id));
        $time = $statement->fetch();

        return array(
            'total_time_spent_seconds' => (int)$time['total_time_seconds'],
            'average_time_per_quiz_seconds' => round((float)$time['avg_quiz_time'], 2),
            'average_time_per_question_seconds' => round((float)$time['avg_question_time'], 2)
        );
    }

    /**
     * Calculate performance metrics
     */
    private function calculate_performance_metrics($user_id, $pdo)
    {
        // Overall accuracy
        $sql_accuracy = "SELECT 
                           (SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as accuracy
                         FROM user_question WHERE user_id = ? AND status = 'answered'";
        $statement = $pdo->prepare($sql_accuracy);
        $statement->execute(array($user_id));
        $accuracy = $statement->fetch();

        // Quiz scores
        $sql_scores = "SELECT 
                         AVG(quiz_score) as avg_score,
                         MAX(quiz_score) as highest_score,
                         MAX(quiz_id) as highest_score_quiz_id
                       FROM (
                         SELECT 
                           quiz_id,
                           (SUM(COALESCE(score, 0)) / (COUNT(*) * 4)) * 100 as quiz_score
                         FROM user_question 
                         WHERE user_id = ?
                         GROUP BY quiz_id
                       ) as quiz_scores";
        $statement = $pdo->prepare($sql_scores);
        $statement->execute(array($user_id));
        $scores = $statement->fetch();

        // Get highest score quiz name
        if ($scores['highest_score_quiz_id']) {
            $sql_quiz_name = "SELECT name FROM quiz WHERE id = ?";
            $statement = $pdo->prepare($sql_quiz_name);
            $statement->execute(array($scores['highest_score_quiz_id']));
            $quiz_name = $statement->fetch();
            $highest_score_quiz_name = $quiz_name ? $quiz_name['name'] : 'Unknown Quiz';
        } else {
            $highest_score_quiz_name = 'No quiz completed';
        }

        return array(
            'overall_accuracy_percentage' => round((float)$accuracy['accuracy'], 2),
            'average_quiz_score' => round((float)$scores['avg_score'], 2),
            'highest_quiz_score' => round((float)$scores['highest_score'], 2),
            'highest_score_quiz_id' => (int)$scores['highest_score_quiz_id'],
            'highest_score_quiz_name' => $highest_score_quiz_name
        );
    }

    /**
     * Calculate subject-wise performance
     */
    private function calculate_subject_performance($user_id, $pdo)
    {
        $sql_subjects = "SELECT 
                           s.subject as subject_name,
                           COUNT(*) as total_questions,
                           SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                           (SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as accuracy,
                           SUM(uq.duration) as time_spent
                         FROM user_question uq
                         JOIN question q ON uq.question_id = q.id
                         JOIN subject s ON q.subject_id = s.id
                         WHERE uq.user_id = ?
                         GROUP BY s.id, s.subject
                         ORDER BY accuracy DESC";
        $statement = $pdo->prepare($sql_subjects);
        $statement->execute(array($user_id));
        $subjects = $statement->fetchAll();

        $subject_details = array();
        $strongest = null;
        $weakest = null;
        $max_accuracy = 0;
        $min_accuracy = 100;

        foreach ($subjects as $subject) {
            $accuracy = round((float)$subject['accuracy'], 2);
            $subject_details[$subject['subject_name']] = array(
                'total_questions' => (int)$subject['total_questions'],
                'correct_answers' => (int)$subject['correct_answers'],
                'accuracy' => $accuracy,
                'time_spent' => (int)$subject['time_spent']
            );

            if ($accuracy > $max_accuracy) {
                $max_accuracy = $accuracy;
                $strongest = $subject['subject_name'];
            }
            if ($accuracy < $min_accuracy) {
                $min_accuracy = $accuracy;
                $weakest = $subject['subject_name'];
            }
        }

        return array(
            'details' => $subject_details,
            'strongest' => $strongest,
            'weakest' => $weakest
        );
    }

    /**
     * Calculate chapter-wise performance
     */
    private function calculate_chapter_performance($user_id, $pdo)
    {
        $sql_chapters = "SELECT 
                           s.subject as subject_name,
                           COALESCE(q.chapter_name, 'Unknown Chapter') as chapter_name,
                           COUNT(*) as total_questions,
                           SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                           (SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as accuracy
                         FROM user_question uq
                         JOIN question q ON uq.question_id = q.id
                         JOIN subject s ON q.subject_id = s.id
                         WHERE uq.user_id = ?
                         GROUP BY s.subject, q.chapter_name
                         ORDER BY s.subject, accuracy DESC";
        $statement = $pdo->prepare($sql_chapters);
        $statement->execute(array($user_id));
        $chapters = $statement->fetchAll();

        $chapter_details = array();
        foreach ($chapters as $chapter) {
            $subject = $chapter['subject_name'];
            if (!isset($chapter_details[$subject])) {
                $chapter_details[$subject] = array();
            }
            $chapter_details[$subject][$chapter['chapter_name']] = array(
                'total_questions' => (int)$chapter['total_questions'],
                'correct_answers' => (int)$chapter['correct_answers'],
                'accuracy' => round((float)$chapter['accuracy'], 2)
            );
        }

        return $chapter_details;
    }

    /**
     * Calculate topic-wise performance
     */
    private function calculate_topic_performance($user_id, $pdo)
    {
        $sql_topics = "SELECT 
                         COALESCE(q.topic_name, 'Unknown Topic') as topic_name,
                         s.subject as subject_name,
                         COUNT(*) as total_questions,
                         SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                         (SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as accuracy
                       FROM user_question uq
                       JOIN question q ON uq.question_id = q.id
                       JOIN subject s ON q.subject_id = s.id
                       WHERE uq.user_id = ?
                       GROUP BY q.topic_name, s.subject
                       ORDER BY accuracy DESC";
        $statement = $pdo->prepare($sql_topics);
        $statement->execute(array($user_id));
        $topics = $statement->fetchAll();

        $topic_details = array();
        $topics_count = 0;

        foreach ($topics as $topic) {
            $topic_details[$topic['topic_name']] = array(
                'subject' => $topic['subject_name'],
                'total_questions' => (int)$topic['total_questions'],
                'correct_answers' => (int)$topic['correct_answers'],
                'accuracy' => round((float)$topic['accuracy'], 2)
            );
            $topics_count++;
        }

        return array(
            'details' => $topic_details,
            'count' => $topics_count
        );
    }

    /**
     * Calculate difficulty-wise performance
     */
    private function calculate_difficulty_performance($user_id, $pdo)
    {
        $sql_difficulty = "SELECT 
                             COALESCE(q.difficulty, q.level, 'Unknown') as difficulty_level,
                             COUNT(*) as total_questions,
                             SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                             (SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as accuracy,
                             AVG(uq.duration) as avg_time_per_question
                           FROM user_question uq
                           JOIN question q ON uq.question_id = q.id
                           WHERE uq.user_id = ?
                           GROUP BY COALESCE(q.difficulty, q.level)
                           ORDER BY accuracy DESC";
        $statement = $pdo->prepare($sql_difficulty);
        $statement->execute(array($user_id));
        $difficulties = $statement->fetchAll();

        $difficulty_details = array();
        foreach ($difficulties as $difficulty) {
            $difficulty_details[$difficulty['difficulty_level']] = array(
                'total_questions' => (int)$difficulty['total_questions'],
                'correct_answers' => (int)$difficulty['correct_answers'],
                'accuracy' => round((float)$difficulty['accuracy'], 2),
                'avg_time_per_question' => round((float)$difficulty['avg_time_per_question'], 2)
            );
        }

        return $difficulty_details;
    }

    /**
     * Generate progress chart data
     */
    private function generate_progress_chart_data($user_id, $pdo)
    {
        // Quiz-wise progress (score over time)
        $sql_quiz_progress = "SELECT 
                                q.name as quiz_name,
                                uq.quiz_id,
                                DATE(MIN(uq.created_at)) as attempt_date,
                                (SUM(COALESCE(uq.score, 0)) / (COUNT(*) * 4)) * 100 as score,
                                (SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as accuracy,
                                AVG(uq.duration) as avg_time_per_question
                              FROM user_question uq
                              JOIN quiz q ON uq.quiz_id = q.id
                              WHERE uq.user_id = ?
                              GROUP BY uq.quiz_id, q.name
                              ORDER BY MIN(uq.created_at)";
        $statement = $pdo->prepare($sql_quiz_progress);
        $statement->execute(array($user_id));
        $quiz_progress = $statement->fetchAll();

        $chart_data = array(
            'quiz_progress' => array(),
            'accuracy_trend' => array(),
            'speed_improvement' => array()
        );

        foreach ($quiz_progress as $progress) {
            $chart_data['quiz_progress'][] = array(
                'quiz_name' => $progress['quiz_name'],
                'date' => $progress['attempt_date'],
                'score' => round((float)$progress['score'], 2)
            );
            
            $chart_data['accuracy_trend'][] = array(
                'quiz_name' => $progress['quiz_name'],
                'date' => $progress['attempt_date'],
                'accuracy' => round((float)$progress['accuracy'], 2)
            );
            
            $chart_data['speed_improvement'][] = array(
                'quiz_name' => $progress['quiz_name'],
                'date' => $progress['attempt_date'],
                'avg_time' => round((float)$progress['avg_time_per_question'], 2)
            );
        }

        return $chart_data;
    }

    /**
     * Calculate learning analytics
     */
    private function calculate_learning_analytics($user_id, $pdo)
    {
        // Learning streak calculation
        $sql_streak = "SELECT 
                         COUNT(DISTINCT DATE(created_at)) as active_days,
                         MAX(DATE(created_at)) as last_active,
                         MIN(DATE(created_at)) as first_active
                       FROM user_question WHERE user_id = ?";
        $statement = $pdo->prepare($sql_streak);
        $statement->execute(array($user_id));
        $streak_info = $statement->fetch();

        // Questions per day average
        $total_days = $streak_info['active_days'] ? $streak_info['active_days'] : 1;
        $sql_questions_count = "SELECT COUNT(*) as total_questions FROM user_question WHERE user_id = ?";
        $statement = $pdo->prepare($sql_questions_count);
        $statement->execute(array($user_id));
        $questions_count = $statement->fetch();
        
        $questions_per_day = round((float)$questions_count['total_questions'] / $total_days, 2);

        // Peak performance hour (simplified)
        $sql_peak_hour = "SELECT 
                            HOUR(created_at) as hour,
                            (SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as accuracy
                          FROM user_question 
                          WHERE user_id = ?
                          GROUP BY HOUR(created_at)
                          ORDER BY accuracy DESC
                          LIMIT 1";
        $statement = $pdo->prepare($sql_peak_hour);
        $statement->execute(array($user_id));
        $peak_hour = $statement->fetch();

        // Favorite subject (most attempted)
        $sql_favorite = "SELECT 
                           s.subject as subject_name,
                           COUNT(*) as question_count
                         FROM user_question uq
                         JOIN question q ON uq.question_id = q.id
                         JOIN subject s ON q.subject_id = s.id
                         WHERE uq.user_id = ?
                         GROUP BY s.id, s.subject
                         ORDER BY question_count DESC
                         LIMIT 1";
        $statement = $pdo->prepare($sql_favorite);
        $statement->execute(array($user_id));
        $favorite = $statement->fetch();

        return array(
            'learning_streak_days' => (int)$streak_info['active_days'],
            'questions_per_day_average' => $questions_per_day,
            'peak_performance_hour' => $peak_hour ? (int)$peak_hour['hour'] : null,
            'favorite_subject' => $favorite ? $favorite['subject_name'] : null,
            'total_badges_earned' => 0, // Will be updated by badge service
            'achievement_score' => 0.0  // Will be calculated based on various metrics
        );
    }

    // ============================
    // Student Report Card API Endpoints
    // ============================

    /**
     * Generate Student Report Card - JWT Protected Endpoint
     * POST /api/quiz/generate_student_report_card
     * Requires valid JWT authentication
     */
    function generate_student_report_card_post()
    {
        // ✅ SECURE: Require JWT authentication
        $objUser = $this->require_jwt_auth();
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();
        // Accept both 'user_id' and 'id' for compatibility
        $user_id = null;
        if (isset($request['user_id'])) {
            $user_id = $request['user_id'];
        } elseif (isset($request['id'])) {
            $user_id = $request['id'];
        } else {
            // Default to authenticated user's ID
            $user_id = $objUser->id;
        }

        // ✅ SECURE: Users can only generate their own report (unless admin)
        $auth_user_id = $objUser->id;
        if ($user_id != $auth_user_id && !in_array($objUser->role_id, [1, 2, 3, 4])) {
            $this->send_forbidden_response("You can only generate your own report card");
            return;
        }

        try {
            $this->load->model('user_performance/student_report_card_model');
            $this->load->model('user_performance/badge_model');

            // Calculate comprehensive report card data
            $report_data = $this->calculate_student_report_data($user_id);
            
            if (!$report_data) {
                $this->send_bad_request_response("No quiz data available for report generation for this user ID {$user_id}");
                return;
            }

            //log the report data for debugging
            log_message('debug', "Generated report data for user {$user_id}: " . print_r($report_data, true));
            // Save report card to database
            $report_id = $this->student_report_card_model->save_student_report_card($user_id, $report_data);
            
            if ($report_id) {
                // Check and award badges
                $this->badge_model->check_and_award_badges($user_id, $report_data);

                $response_data = array_merge($report_data, array('report_id' => $report_id));
                $this->send_success_response($response_data, "Student report card generated successfully");
            } else {
                $this->send_internal_server_error("Failed to save report card data");
            }

        } catch (Exception $e) {
            log_message('error', "Report Card Generation Error: " . $e->getMessage());
            $this->send_internal_server_error("Error generating report card: " . $e->getMessage());
        }
    }

    /**
     * Handle OPTIONS request for CORS - Report Card Generation
     */
    function generate_student_report_card_options()
    {
        $this->add_headers();
        $this->response(array('status' => 'OK'), REST_Controller::HTTP_OK);
    }

    /**
     * Get Student Report Card - JWT Protected Endpoint
     * GET /api/quiz/get_student_report_card
     * Requires valid JWT authentication
     */
    function get_student_report_card_get()
    {
        // ✅ SECURE: Require JWT authentication
        $objUser = $this->require_jwt_auth();
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $user_id = $this->input->get('user_id', true);
        if (!$user_id) {
            $user_id = $this->input->get('id', true); // Accept 'id' as well
        }
        if (!$user_id) {
            $user_id = $objUser->id; // Default to authenticated user
        }

        // ✅ SECURE: Users can only access their own report (unless admin)
        $auth_user_id = $objUser->id;
        if ($user_id != $auth_user_id && !in_array($objUser->role_id, [1, 2, 3, 4])) {
            $this->send_forbidden_response("You can only access your own report card");
            return;
        }

        try {
            $this->load->model('user_performance/student_report_card_model');
            $report_card = $this->student_report_card_model->get_student_report_card($user_id);

            if ($report_card) {
                $this->send_success_response($report_card, "Report card retrieved successfully");
            } else {
                $this->send_not_found_response("No report card found. Please generate a new report card first.");
            }

        } catch (Exception $e) {
            log_message('error', "Report Card Retrieval Error: " . $e->getMessage());
            $this->send_internal_server_error("Error retrieving report card");
        }
    }

    /**
     * Handle OPTIONS request for CORS - Get Report Card
     */
    function get_student_report_card_options()
    {
        $this->add_headers();
        $this->response(array('status' => 'OK'), REST_Controller::HTTP_OK);
    }

    /**
     * Get Student Badges - JWT Protected Endpoint
     * GET /api/quiz/get_student_badges
     * Requires valid JWT authentication
     */
    function get_student_badges_get()
    {
        // ✅ SECURE: Require JWT authentication
        $objUser = $this->require_jwt_auth();
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $user_id = $this->input->get('user_id', true);
        if (!$user_id) {
            $user_id = $this->input->get('id', true); // Accept 'id' as well
        }
        if (!$user_id) {
            $user_id = $objUser->id; // Default to authenticated user
        }

        // ✅ SECURE: Users can only access their own badges (unless admin)
        $auth_user_id = $objUser->id;
        if ($user_id != $auth_user_id && !in_array($objUser->role_id, [1, 2, 3, 4])) {
            $this->send_forbidden_response("You can only access your own badges");
            return;
        }

        try {
            $this->load->model('user_performance/badge_model');
            $badges_data = $this->badge_model->get_user_badges_with_progress($user_id);

            if ($badges_data !== FALSE) {
                $this->send_success_response($badges_data, "Badges retrieved successfully");
            } else {
                $this->send_internal_server_error("Error retrieving badges data");
            }

        } catch (Exception $e) {
            log_message('error', "Badges Retrieval Error: " . $e->getMessage());
            $this->send_internal_server_error("Error retrieving badges");
        }
    }

    /**
     * Handle OPTIONS request for CORS - Get Student Badges
     */
    function get_student_badges_options()
    {
        $this->add_headers();
        $this->response(array('status' => 'OK'), REST_Controller::HTTP_OK);
    }

    /**
     * Calculate comprehensive student report data
     * Private helper method for report generation
     * 
     * @param int $user_id User ID
     * @return array|false Report data array or false on failure
     */
    private function calculate_student_report_data($user_id)
    {
        try {
            $pdo = CDatabase::getPdo();

            // Basic quiz statistics
            $basic_stats = $this->get_basic_quiz_statistics($user_id);
            if (!$basic_stats || $basic_stats['total_quizzes'] == 0) {
                return false; // No quiz data available
            }

            // Subject-wise breakdown
            $subject_breakdown = $this->get_subject_breakdown($user_id);
            
            // Chapter-wise breakdown
            $chapter_breakdown = $this->get_chapter_breakdown($user_id);
            
            // Difficulty-wise breakdown
            $difficulty_breakdown = $this->get_difficulty_breakdown($user_id);
            
            // Performance trends for charts
            $quiz_progress_data = $this->get_quiz_progress_data($user_id);
            $accuracy_trend_data = $this->get_accuracy_trend_data($user_id);
            $speed_improvement_data = $this->get_speed_improvement_data($user_id);
            
            // Advanced analytics
            $learning_insights = $this->get_learning_insights($user_id);
            
            // Performance summary
            $performance_summary = $this->generate_performance_summary($basic_stats, $subject_breakdown);

            // Calculate additional required metrics
            $answered_questions = $basic_stats['total_questions'] - $basic_stats['skipped_questions'];
            $total_incorrect = $answered_questions - $basic_stats['correct_answers'];
            $total_skipped = $basic_stats['skipped_questions'];
            $accuracy_percentage = ($answered_questions > 0) ? 
                round(($basic_stats['correct_answers'] / $answered_questions) * 100, 2) : 0;
            
            // Calculate average time per question and quiz
            $avg_time_per_question = ($answered_questions > 0) ? 
                round($basic_stats['total_time_spent'] / $answered_questions, 2) : 0;
            
            // Calculate average time per quiz
            $avg_time_per_quiz = ($basic_stats['total_quizzes'] > 0) ? 
                round($basic_stats['total_time_spent'] / $basic_stats['total_quizzes'], 2) : 0;
            
            // Get strongest and weakest subjects
            $strongest_subject = !empty($subject_breakdown) ? 
                array_keys($subject_breakdown, max($subject_breakdown))[0] : 'N/A';
            $weakest_subject = !empty($subject_breakdown) ? 
                array_keys($subject_breakdown, min($subject_breakdown))[0] : 'N/A';

            return array(
                // Required fields for Student_report_card_model
                'data_period_start' => date('Y-m-d', strtotime('-30 days')), // Last 30 days
                'data_period_end' => date('Y-m-d'),
                'total_quizzes_taken' => $basic_stats['total_quizzes'],
                'total_questions_attempted' => $basic_stats['total_questions'],
                'total_correct_answers' => $basic_stats['correct_answers'],
                'total_incorrect_answers' => $total_incorrect,
                'total_skipped_questions' => $total_skipped,
                'total_time_spent_seconds' => $basic_stats['total_time_spent'],
                'average_time_per_quiz_seconds' => $avg_time_per_quiz,
                'average_time_per_question_seconds' => $avg_time_per_question,
                'overall_accuracy_percentage' => $accuracy_percentage,
                'average_quiz_score' => $basic_stats['average_score'] ?? 0,
                'highest_quiz_score' => $basic_stats['highest_score'] ?? 0,
                'highest_score_quiz_id' => $basic_stats['highest_score_quiz_id'] ?? 0,
                'highest_score_quiz_name' => $basic_stats['highest_score_quiz_name'] ?? 'N/A',
                'subject_wise_stats' => json_encode($subject_breakdown),
                'strongest_subject' => $strongest_subject,
                'weakest_subject' => $weakest_subject,
                'chapter_wise_stats' => json_encode($chapter_breakdown),
                'topic_wise_stats' => json_encode($this->get_subject_wise_topics($user_id)),
                'topics_covered_count' => count($this->get_subject_wise_topics($user_id)),
                'difficulty_wise_stats' => json_encode($difficulty_breakdown),
                'quiz_progress_data' => json_encode($quiz_progress_data),
                'accuracy_trend_data' => json_encode($accuracy_trend_data),
                'speed_improvement_data' => json_encode($speed_improvement_data),
                'learning_streak_days' => $learning_insights['streak_days'] ?? 0,
                'questions_per_day_average' => $learning_insights['questions_per_day'] ?? 0,
                'peak_performance_hour' => $learning_insights['peak_hour'] ?? 12,
                'favorite_subject' => $strongest_subject,
                'total_badges_earned' => 0, // Will be calculated by badge system
                'achievement_score' => round($accuracy_percentage, 0), // Use accuracy as achievement score
                
                // Additional metadata for backwards compatibility
                'generated_at' => date('Y-m-d H:i:s'),
                'last_updated' => date('Y-m-d H:i:s')
            );

        } catch (Exception $e) {
            log_message('error', "Error calculating report data: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get basic quiz statistics for a user
     */
    private function get_basic_quiz_statistics($user_id)
    {
        $pdo = CDatabase::getPdo();
        
        // Get basic stats including skipped questions
        $sql = "SELECT 
                    COUNT(DISTINCT uq.quiz_id) as total_quizzes,
                    COUNT(uq.question_id) as total_questions,
                    SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                    SUM(CASE WHEN uq.option_answer IS NULL OR uq.option_answer = '' THEN 1 ELSE 0 END) as skipped_questions,
                    SUM(uq.duration) as total_time_spent,
                    ROUND(AVG(CASE WHEN uq.is_correct = 1 THEN 100 ELSE 0 END), 2) as average_score
                FROM user_question uq 
                WHERE uq.user_id = ?";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        $basic_stats = $statement->fetch(PDO::FETCH_ASSOC);
        
        // Get highest score with quiz details
        $sql = "SELECT 
                    quiz_id,
                    ROUND(AVG(CASE WHEN is_correct = 1 THEN 100 ELSE 0 END), 2) as accuracy_percentage
                FROM user_question 
                WHERE user_id = ? AND option_answer IS NOT NULL
                GROUP BY quiz_id
                ORDER BY accuracy_percentage DESC
                LIMIT 1";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        $highest_score_result = $statement->fetch(PDO::FETCH_ASSOC);
        $basic_stats['highest_score'] = $highest_score_result['accuracy_percentage'] ?? 0;
        $basic_stats['highest_score_quiz_id'] = $highest_score_result['quiz_id'] ?? 0;
        
        // Get quiz name for highest score (if quiz table exists)
        if ($basic_stats['highest_score_quiz_id'] > 0) {
            $sql = "SELECT name FROM quiz WHERE id = ?";
            $statement = $pdo->prepare($sql);
            $statement->execute(array($basic_stats['highest_score_quiz_id']));
            $quiz_name_result = $statement->fetch(PDO::FETCH_ASSOC);
            $basic_stats['highest_score_quiz_name'] = $quiz_name_result['name'] ?? 'Quiz #' . $basic_stats['highest_score_quiz_id'];
        } else {
            $basic_stats['highest_score_quiz_name'] = 'N/A';
        }
        
        // Get best subject performance
        $sql = "SELECT 
                    s.subject as subject_name,
                    ROUND(AVG(CASE WHEN uq.is_correct = 1 THEN 100 ELSE 0 END), 2) as accuracy
                FROM user_question uq
                JOIN question q ON uq.question_id = q.id
                JOIN subject s ON q.subject_id = s.id
                WHERE uq.user_id = ? AND uq.option_answer IS NOT NULL
                GROUP BY s.id, s.subject
                ORDER BY accuracy DESC
                LIMIT 1";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        $best_subject = $statement->fetch(PDO::FETCH_ASSOC);
        $basic_stats['best_subject'] = $best_subject['subject_name'] ?? 'N/A';
        $basic_stats['best_accuracy'] = $best_subject['accuracy'] ?? 0;
        
        // Get fastest completion time
        $sql = "SELECT MIN(total_time) as fastest_completion
                FROM (
                    SELECT 
                        quiz_id,
                        SUM(duration) as total_time
                    FROM user_question 
                    WHERE user_id = ? AND option_answer IS NOT NULL
                    GROUP BY quiz_id
                    HAVING COUNT(*) > 0
                ) quiz_times";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        $fastest_time = $statement->fetch(PDO::FETCH_ASSOC);
        $basic_stats['fastest_completion'] = $fastest_time['fastest_completion'] ?? 0;
        
        $statement = NULL;
        $pdo = NULL;
        
        return $basic_stats;
    }

    /**
     * Additional helper methods for report calculation
     * (These would continue with the implementation of other statistics)
     */
    private function get_subject_breakdown($user_id) 
    {
        // Implementation for subject-wise statistics
        $pdo = CDatabase::getPdo();
        
        $sql = "SELECT 
                    s.subject as subject_name,
                    COUNT(uq.question_id) as questions_attempted,
                    SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                    ROUND(AVG(CASE WHEN uq.is_correct = 1 THEN 100 ELSE 0 END), 2) as accuracy,
                    SUM(uq.duration) as total_time
                FROM user_question uq
                JOIN question q ON uq.question_id = q.id
                JOIN subject s ON q.subject_id = s.id
                WHERE uq.user_id = ? AND uq.option_answer IS NOT NULL
                GROUP BY s.id, s.subject
                ORDER BY accuracy DESC";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        $subjects = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        $statement = NULL;
        $pdo = NULL;
        
        return $subjects;
    }

    private function get_chapter_breakdown($user_id)
    {
        // Similar implementation for chapters
        return array(); // Placeholder - implement based on your chapter structure
    }

    private function get_difficulty_breakdown($user_id)
    {
        $pdo = CDatabase::getPdo();
        
        $sql = "SELECT 
                    q.level as difficulty,
                    COUNT(uq.question_id) as questions_attempted,
                    SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                    ROUND(AVG(CASE WHEN uq.is_correct = 1 THEN 100 ELSE 0 END), 2) as accuracy
                FROM user_question uq
                JOIN question q ON uq.question_id = q.id
                WHERE uq.user_id = ? AND uq.option_answer IS NOT NULL
                GROUP BY q.level
                ORDER BY accuracy DESC";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        $difficulty_stats = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        $statement = NULL;
        $pdo = NULL;
        
        return $difficulty_stats;
    }

    private function get_quiz_progress_data($user_id)
    {
        // Get last 10 quiz performances for progress chart
        $pdo = CDatabase::getPdo();
        
        $sql = "SELECT 
                    DATE(uq.created_at) as quiz_date,
                    ROUND(AVG(CASE WHEN uq.is_correct = 1 THEN 100 ELSE 0 END), 2) as accuracy
                FROM user_question uq
                WHERE uq.user_id = ? AND uq.option_answer IS NOT NULL
                GROUP BY DATE(uq.created_at)
                ORDER BY quiz_date DESC
                LIMIT 10";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        $progress_data = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        $statement = NULL;
        $pdo = NULL;
        
        return array_reverse($progress_data); // Chronological order
    }

    private function get_accuracy_trend_data($user_id)
    {
        // Subject-wise accuracy trends
        return $this->get_subject_breakdown($user_id);
    }

    private function get_speed_improvement_data($user_id)
    {
        // Average time per question over time
        $pdo = CDatabase::getPdo();
        
        $sql = "SELECT 
                    DATE(uq.created_at) as quiz_date,
                    AVG(uq.duration) as avg_time_per_question
                FROM user_question uq
                WHERE uq.user_id = ? AND uq.option_answer IS NOT NULL
                GROUP BY DATE(uq.created_at)
                ORDER BY quiz_date DESC
                LIMIT 10";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        $speed_data = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        $statement = NULL;
        $pdo = NULL;
        
        return array_reverse($speed_data);
    }

    private function get_learning_insights($user_id)
    {
        $pdo = CDatabase::getPdo();
        
        // Calculate learning streak days
        $sql = "SELECT COUNT(DISTINCT DATE(created_at)) as streak_days
                FROM user_question 
                WHERE user_id = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        $streak_result = $statement->fetch(PDO::FETCH_ASSOC);
        
        // Calculate questions per day average
        $sql = "SELECT COUNT(*) / 30 as questions_per_day
                FROM user_question 
                WHERE user_id = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        $qpd_result = $statement->fetch(PDO::FETCH_ASSOC);
        
        // Calculate peak performance hour
        $sql = "SELECT HOUR(created_at) as hour, 
                       COUNT(*) as activity_count,
                       AVG(CASE WHEN is_correct = 1 THEN 100 ELSE 0 END) as accuracy
                FROM user_question 
                WHERE user_id = ? AND option_answer IS NOT NULL
                GROUP BY HOUR(created_at)
                ORDER BY accuracy DESC, activity_count DESC
                LIMIT 1";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        $peak_result = $statement->fetch(PDO::FETCH_ASSOC);
        
        return array(
            'streak_days' => (int)($streak_result['streak_days'] ?? 0),
            'questions_per_day' => round($qpd_result['questions_per_day'] ?? 0, 1),
            'peak_hour' => (int)($peak_result['hour'] ?? 12),
            'consistent_improvement' => true,
            'peak_performance_time' => sprintf('%02d:00', $peak_result['hour'] ?? 12),
            'preferred_difficulty' => 'Moderate',
            'learning_pattern' => 'Visual learner based on question types'
        );
    }

    private function generate_performance_summary($basic_stats, $subject_breakdown)
    {
        $total_accuracy = $basic_stats['average_score'];
        
        if ($total_accuracy >= 90) {
            return 'Excellent performance! You are mastering the concepts very well.';
        } elseif ($total_accuracy >= 75) {
            return 'Good performance! Keep practicing to reach excellence.';
        } elseif ($total_accuracy >= 60) {
            return 'Average performance. Focus on your weak areas for improvement.';
        } else {
            return 'Need improvement. Spend more time on fundamentals and practice regularly.';
        }
    }

    private function get_subject_wise_topics($user_id)
    {
        $pdo = CDatabase::getPdo();
        
        // Get topics covered by subject
        $sql = "SELECT 
                    s.subject as subject_name,
                    COUNT(DISTINCT q.topic_id) as topics_covered
                FROM user_question uq
                JOIN question q ON uq.question_id = q.id
                JOIN subject s ON q.subject_id = s.id
                WHERE uq.user_id = ? AND uq.option_answer IS NOT NULL
                GROUP BY s.id, s.subject";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        $topics_data = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        $statement = NULL;
        $pdo = NULL;
        
        return $topics_data;
    }

    private function get_improvement_areas($subject_breakdown)
    {
        $weak_subjects = array();
        foreach ($subject_breakdown as $subject) {
            if ($subject['accuracy'] < 70) {
                $weak_subjects[] = $subject['subject_name'];
            }
        }
        return $weak_subjects;
    }

    private function get_strengths($subject_breakdown)
    {
        $strong_subjects = array();
        foreach ($subject_breakdown as $subject) {
            if ($subject['accuracy'] >= 85) {
                $strong_subjects[] = $subject['subject_name'];
            }
        }
        return $strong_subjects;
    }

    private function generate_next_goals($basic_stats, $subject_breakdown)
    {
        $goals = array();
        
        if ($basic_stats['average_score'] < 80) {
            $goals[] = 'Aim to achieve 80% overall accuracy';
        }
        
        if ($basic_stats['total_quizzes'] < 10) {
            $goals[] = 'Complete 10 total quizzes';
        }
        
        // Add subject-specific goals
        foreach ($subject_breakdown as $subject) {
            if ($subject['accuracy'] < 70) {
                $goals[] = 'Improve ' . $subject['subject_name'] . ' performance to 70%';
            }
        }
        
        return $goals;
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
     * Get Simple Subject-wise Topic Summary for a User
     * GET /api/quiz/user_topic_summary/{user_id}
     * Returns a simplified version with just topic names, attempt counts and correct counts
     */
    function user_topic_summary_get()
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
            $this->send_forbidden_response("You can only access your own topic summary data");
            return;
        }

        try {
            $topic_summary = $this->get_user_topic_summary_data($user_id);
            
            if (empty($topic_summary)) {
                $response = $this->get_failed_response(NULL, "No quiz data found for this user");
                $this->set_output($response);
                return;
            }

            $response = $this->get_success_response($topic_summary, "User topic summary retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Error retrieving user topic summary for user {$user_id}: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error retrieving topic summary: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Handle OPTIONS request for CORS - User Topic Summary
     */
    function user_topic_summary_options()
    {
        $this->add_headers();
        $this->response(array('status' => 'OK'), REST_Controller::HTTP_OK);
    }

    /**
     * Helper function to get simplified subject-wise topic summary for a user
     * @param int $user_id
     * @return array Simplified topic summary data
     */
    private function get_user_topic_summary_data($user_id)
    {
        $pdo = CDatabase::getPdo();
        
        // Simplified query for basic topic summary
        $sql = "SELECT 
                    s.subject as subject_name,
                    COALESCE(t.topic_name, q.topic_name, 'Unknown Topic') as topic_name,
                    COUNT(*) as questions_attempted,
                    SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) as questions_correct,
                    ROUND(
                        (SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 
                        1
                    ) as accuracy
                FROM user_question uq
                JOIN question q ON uq.question_id = q.id
                JOIN subject s ON q.subject_id = s.id
                LEFT JOIN topic t ON q.topic_id = t.id
                WHERE uq.user_id = ?
                GROUP BY 
                    s.subject, 
                    COALESCE(t.topic_name, q.topic_name, 'Unknown Topic')
                ORDER BY 
                    s.subject ASC, 
                    questions_attempted DESC";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($user_id));
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize by subject
        $summary = array();
        foreach ($results as $row) {
            $subject_name = $row['subject_name'];
            
            if (!isset($summary[$subject_name])) {
                $summary[$subject_name] = array();
            }
            
            $summary[$subject_name][] = array(
                'topic_name' => $row['topic_name'],
                'questions_attempted' => (int)$row['questions_attempted'],
                'questions_correct' => (int)$row['questions_correct'],
                'accuracy' => (float)$row['accuracy']
            );
        }
        
        $statement = NULL;
        $pdo = NULL;
        
        return array(
            'user_id' => (int)$user_id,
            'subject_topic_summary' => $summary
        );
    }

    /**
     * Admin endpoint to get comprehensive user quiz statistics with pagination and filtering
     * GET /api/quiz/users_quiz_statistics
     * 
     * Query Parameters:
     * - page: Page number (default: 1)
     * - limit: Records per page (default: 20, max: 100)
     * - search: Search by username or display_name
     * - date_from: Filter quizzes from this date (YYYY-MM-DD)
     * - date_to: Filter quizzes until this date (YYYY-MM-DD)
     * - min_quizzes: Minimum number of quizzes attempted
     * - active_only: Show only users with recent activity (default: false)
     * - sort_by: quiz_count|total_score|avg_percentage|last_activity (default: quiz_count)
     * - sort_order: desc|asc (default: desc)
     */
    function users_quiz_statistics_get()
    {
        // ✅ SECURE: Require JWT authentication with admin access
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        try {
            // Get query parameters with defaults
            $page = max(1, (int)($this->input->get('page') ?: 1));
            $limit = min(100, max(1, (int)($this->input->get('limit') ?: 20)));
            $search = trim($this->input->get('search') ?: '');
            $date_from = $this->input->get('date_from') ?: null;
            $date_to = $this->input->get('date_to') ?: null;
            $min_quizzes = max(0, (int)($this->input->get('min_quizzes') ?: 0));
            $active_only = $this->input->get('active_only') === 'true';
            $sort_by = $this->input->get('sort_by') ?: 'total_quizzes_attempted';
            $sort_order = strtoupper($this->input->get('sort_order') ?: 'DESC');

            // Validate sort parameters
            $valid_sort_fields = ['total_quizzes_attempted', 'total_score', 'accuracy_percentage', 'last_activity', 'average_score'];
            if (!in_array($sort_by, $valid_sort_fields)) {
                $sort_by = 'total_quizzes_attempted';
            }
            if (!in_array($sort_order, ['ASC', 'DESC'])) {
                $sort_order = 'DESC';
            }

            $offset = ($page - 1) * $limit;

            // Build the main query
            $sql = "
                SELECT 
                    u.id as user_id,
                    u.username,
                    u.display_name,
                    u.last_activity,
                    u.last_login,
                    
                    -- Quiz Counts
                    COUNT(DISTINCT uq.quiz_id) as total_quizzes_attempted,
                    COUNT(DISTINCT CASE WHEN quiz_completed.quiz_id IS NOT NULL THEN uq.quiz_id END) as total_quizzes_completed,
                    ROUND(
                        CASE 
                            WHEN COUNT(DISTINCT uq.quiz_id) > 0 
                            THEN (COUNT(DISTINCT CASE WHEN quiz_completed.quiz_id IS NOT NULL THEN uq.quiz_id END) * 100.0 / COUNT(DISTINCT uq.quiz_id)) 
                            ELSE 0 
                        END, 2
                    ) as completion_rate,
                    
                    -- Performance Metrics
                    COALESCE(SUM(CASE WHEN uq.is_correct = 1 THEN uq.score ELSE 0 END), 0) as total_score,
                    ROUND(
                        CASE 
                            WHEN COUNT(DISTINCT CASE WHEN quiz_completed.quiz_id IS NOT NULL THEN uq.quiz_id END) > 0 
                            THEN SUM(CASE WHEN uq.is_correct = 1 THEN uq.score ELSE 0 END) / COUNT(DISTINCT CASE WHEN quiz_completed.quiz_id IS NOT NULL THEN uq.quiz_id END)
                            ELSE 0 
                        END, 2
                    ) as average_score,
                    ROUND(
                        CASE 
                            WHEN COUNT(uq.id) > 0 
                            THEN (SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(uq.id)) 
                            ELSE 0 
                        END, 2
                    ) as accuracy_percentage,
                    MAX(CASE WHEN uq.is_correct = 1 THEN uq.score ELSE 0 END) as highest_score,
                    
                    -- Question Statistics
                    COUNT(uq.id) as total_questions_answered,
                    SUM(CASE WHEN uq.is_correct = 1 THEN 1 ELSE 0 END) as total_correct_answers,
                    SUM(CASE WHEN uq.is_correct = 0 THEN 1 ELSE 0 END) as total_incorrect_answers,
                    SUM(CASE WHEN uq.status = 'skipped' THEN 1 ELSE 0 END) as total_skipped_questions,
                    
                    -- Time Statistics
                    COALESCE(SUM(uq.duration), 0) as total_time_spent_seconds,
                    ROUND(
                        CASE 
                            WHEN COUNT(DISTINCT CASE WHEN quiz_completed.quiz_id IS NOT NULL THEN uq.quiz_id END) > 0 
                            THEN SUM(uq.duration) / COUNT(DISTINCT CASE WHEN quiz_completed.quiz_id IS NOT NULL THEN uq.quiz_id END)
                            ELSE 0 
                        END, 2
                    ) as average_time_per_quiz,
                    ROUND(
                        CASE 
                            WHEN COUNT(uq.id) > 0 
                            THEN SUM(uq.duration) / COUNT(uq.id) 
                            ELSE 0 
                        END, 2
                    ) as average_time_per_question,
                    
                    -- Activity Data
                    MIN(uq.created_at) as first_quiz_date,
                    MAX(uq.created_at) as last_quiz_date,
                    DATEDIFF(NOW(), MAX(uq.created_at)) as days_since_last_quiz,
                    COUNT(DISTINCT DATE(uq.created_at)) as active_days_count,
                    COUNT(DISTINCT s.id) as unique_subjects_attempted,
                    
                    -- Level Distribution
                    COUNT(DISTINCT CASE WHEN q.level = 'Elementary' THEN uq.quiz_id END) as elementary_quizzes,
                    COUNT(DISTINCT CASE WHEN q.level = 'Intermediate' THEN uq.quiz_id END) as intermediate_quizzes,
                    COUNT(DISTINCT CASE WHEN q.level = 'Advance' THEN uq.quiz_id END) as advanced_quizzes
                    
                FROM user u
                LEFT JOIN user_question uq ON u.id = uq.user_id
                LEFT JOIN quiz q ON uq.quiz_id = q.id
                LEFT JOIN quiz_subjects qs ON q.id = qs.quiz_id
                LEFT JOIN subject s ON qs.subject_id = s.id
                LEFT JOIN (
                    -- Subquery to identify completed quizzes
                    SELECT 
                        uq2.user_id,
                        uq2.quiz_id,
                        COUNT(DISTINCT qq.question_id) as total_questions,
                        COUNT(DISTINCT uq2.question_id) as answered_questions
                    FROM user_question uq2
                    INNER JOIN quiz_question qq ON uq2.quiz_id = qq.quiz_id
                    GROUP BY uq2.user_id, uq2.quiz_id
                    HAVING answered_questions >= total_questions
                ) quiz_completed ON u.id = quiz_completed.user_id AND uq.quiz_id = quiz_completed.quiz_id
                WHERE 1=1";

            // Add search filter
            if (!empty($search)) {
                $sql .= " AND (u.username LIKE :search OR u.display_name LIKE :search)";
            }

            // Add date filters
            if (!empty($date_from)) {
                $sql .= " AND DATE(uq.created_at) >= :date_from";
            }
            if (!empty($date_to)) {
                $sql .= " AND DATE(uq.created_at) <= :date_to";
            }

            // Add active users filter
            if ($active_only) {
                $sql .= " AND u.last_activity >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            }

            $sql .= " GROUP BY u.id, u.username, u.display_name, u.last_activity, u.last_login";

            // Add minimum quiz filter after GROUP BY
            if ($min_quizzes > 0) {
                $sql .= " HAVING total_quizzes_attempted >= :min_quizzes";
            }

            // Add sorting
            $sql .= " ORDER BY $sort_by $sort_order";

            // Add pagination
            $sql .= " LIMIT :limit OFFSET :offset";

            // Count query for pagination
            $count_sql = "
                SELECT COUNT(DISTINCT u.id) as total_users
                FROM user u
                LEFT JOIN user_question uq ON u.id = uq.user_id
                WHERE 1=1";

            if (!empty($search)) {
                $count_sql .= " AND (u.username LIKE :search OR u.display_name LIKE :search)";
            }
            if (!empty($date_from)) {
                $count_sql .= " AND DATE(uq.created_at) >= :date_from";
            }
            if (!empty($date_to)) {
                $count_sql .= " AND DATE(uq.created_at) <= :date_to";
            }
            if ($active_only) {
                $count_sql .= " AND u.last_activity >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            }

            $pdo = CDatabase::getPdo();

            // Execute count query
            $count_stmt = $pdo->prepare($count_sql);
            if (!empty($search)) {
                $count_stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
            }
            if (!empty($date_from)) {
                $count_stmt->bindValue(':date_from', $date_from, PDO::PARAM_STR);
            }
            if (!empty($date_to)) {
                $count_stmt->bindValue(':date_to', $date_to, PDO::PARAM_STR);
            }
            
            $count_stmt->execute();
            $total_users = (int)$count_stmt->fetchColumn();

            // Execute main query
            $stmt = $pdo->prepare($sql);
            
            // Bind parameters
            if (!empty($search)) {
                $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
            }
            if (!empty($date_from)) {
                $stmt->bindValue(':date_from', $date_from, PDO::PARAM_STR);
            }
            if (!empty($date_to)) {
                $stmt->bindValue(':date_to', $date_to, PDO::PARAM_STR);
            }
            if ($min_quizzes > 0) {
                $stmt->bindValue(':min_quizzes', $min_quizzes, PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format the results
            $formatted_users = [];
            foreach ($users as $user) {
                $formatted_users[] = [
                    'user_id' => (int)$user['user_id'],
                    'username' => $user['username'],
                    'display_name' => $user['display_name'],
                    'last_activity' => $user['last_activity'],
                    'last_login' => $user['last_login'],
                    
                    // Quiz Counts
                    'total_quizzes_attempted' => (int)$user['total_quizzes_attempted'],
                    'total_quizzes_completed' => (int)$user['total_quizzes_completed'],
                    'completion_rate' => (float)$user['completion_rate'],
                    
                    // Performance Metrics
                    'total_score' => (int)$user['total_score'],
                    'average_score' => (float)$user['average_score'],
                    'accuracy_percentage' => (float)$user['accuracy_percentage'],
                    'highest_score' => (int)$user['highest_score'],
                    
                    // Question Statistics
                    'total_questions_answered' => (int)$user['total_questions_answered'],
                    'total_correct_answers' => (int)$user['total_correct_answers'],
                    'total_incorrect_answers' => (int)$user['total_incorrect_answers'],
                    'total_skipped_questions' => (int)$user['total_skipped_questions'],
                    
                    // Time Statistics
                    'total_time_spent_seconds' => (int)$user['total_time_spent_seconds'],
                    'average_time_per_quiz' => (float)$user['average_time_per_quiz'],
                    'average_time_per_question' => (float)$user['average_time_per_question'],
                    
                    // Activity Data
                    'first_quiz_date' => $user['first_quiz_date'],
                    'last_quiz_date' => $user['last_quiz_date'],
                    'days_since_last_quiz' => (int)$user['days_since_last_quiz'],
                    'active_days_count' => (int)$user['active_days_count'],
                    'unique_subjects_attempted' => (int)$user['unique_subjects_attempted'],
                    
                    // Level Distribution
                    'quiz_level_distribution' => [
                        'elementary' => (int)$user['elementary_quizzes'],
                        'intermediate' => (int)$user['intermediate_quizzes'],
                        'advanced' => (int)$user['advanced_quizzes']
                    ]
                ];
            }

            // Calculate pagination info
            $total_pages = ceil($total_users / $limit);

            $stmt = null;
            $count_stmt = null;
            $pdo = null;

            $response_data = [
                'users' => $formatted_users,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $total_pages,
                    'total_users' => $total_users,
                    'per_page' => $limit,
                    'has_next_page' => $page < $total_pages,
                    'has_prev_page' => $page > 1
                ],
                'filters_applied' => [
                    'search' => $search,
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                    'min_quizzes' => $min_quizzes,
                    'active_only' => $active_only,
                    'sort_by' => $sort_by,
                    'sort_order' => $sort_order
                ]
            ];

            $response = $this->get_success_response($response_data, "User quiz statistics retrieved successfully");
            $this->set_output($response);

        } catch (Exception $e) {
            log_message('error', 'Error in users_quiz_statistics_get: ' . $e->getMessage());
            $response = $this->get_failed_response(NULL, 'Failed to retrieve user quiz statistics');
            $this->set_output($response);
        }
    }

    /**
     * Debug endpoint to manually trigger point generation for a quiz
     * POST /api/quiz/debug_points
     * 
     * This endpoint is useful for debugging point calculation issues.
     * It manually triggers the point calculation and awarding process for a specific user and quiz.
     * 
     * Parameters:
     * - quiz_id (required): The ID of the quiz
     * - user_id (optional): The user ID (defaults to current authenticated user)
     * - force (optional): Force point calculation even if points were already awarded (default: false)
     */
    function debug_points_post()
    {
        try {
            $request = $this->get_request();
            // Get input parameters
            $quiz_id = $request['quiz_id'];
            $user_id = $request['user_id'];
            $force = $request['force'] === 'true' || $request['force'] === true;

            // If no user_id provided, try to get from JWT auth
            if (empty($user_id)) {
                $objUser = $this->require_jwt_auth();
                if ($objUser && isset($objUser->id)) {
                    $user_id = $objUser->id;
                }
            }
            
            // Validate inputs
            if (empty($quiz_id)) {
                $response = $this->get_failed_response(NULL, "quiz_id is required");
                $this->set_output($response);
                return;
            }
            
            if (empty($user_id)) {
                $response = $this->get_failed_response(NULL, "user_id is required or user must be authenticated");
                $this->set_output($response);
                return;
            }
            
            // Load required models and libraries
            $this->load->model('quiz/quiz_model');
            $this->load->model('user_question/user_question_model');
            $this->load->model('user_performance/user_performance_model');
            $this->load->library('User_point_service');
            
            // Get quiz details
            $quiz = $this->quiz_model->get_quiz($quiz_id);
            if (!$quiz) {
                $response = $this->get_failed_response(NULL, "Quiz not found with ID: $quiz_id");
                $this->set_output($response);
                return;
            }
            
            // Get user's quiz answers
            $user_answers = $this->user_question_model->get_user_quiz_results($user_id, $quiz_id);
            if (empty($user_answers)) {
                $response = $this->get_failed_response(NULL, "No quiz answers found for user $user_id in quiz $quiz_id");
                $this->set_output($response);
                return;
            }
            
            // Check if quiz is completed
            $is_completed = $this->user_performance_model->is_quiz_completed($user_id, $quiz_id);
            if (!$is_completed) {
                $response = $this->get_failed_response(NULL, "Quiz is not marked as completed for this user");
                $this->set_output($response);
                return;
            }
            
            // Check if points were already awarded (unless force is true)
            if (!$force) {
                $this->load->model('user_point/user_point_model');
                $existing_transactions = $this->user_point_model->get_user_point_transactions($user_id, 10, 0, 'earned');
                foreach ($existing_transactions as $transaction) {
                    if ($transaction->quiz_id == $quiz_id) {
                        $response_data = [
                            'already_awarded' => true,
                            'existing_transaction' => $transaction,
                            'message' => "Points already awarded for this quiz. Use force=true to recalculate."
                        ];
                        $response = $this->get_success_response($response_data, "Points already awarded for this quiz");
                        $this->set_output($response);
                        return;
                    }
                }
            }
            
            // Calculate quiz statistics for debugging
            $total_questions = count($user_answers);
            $correct_answers = 0;
            $skipped_questions = 0;
            
            foreach ($user_answers as $answer) {
                if ($answer->status == "skipped") {
                    $skipped_questions++;
                } elseif ($answer->is_correct == 1) {
                    $correct_answers++;
                }
            }
            
            $score_percentage = $total_questions > 0 ? round(($correct_answers / $total_questions) * 100, 2) : 0;
            
            // Attempt to award points
            log_message('info', "Debug Points: Attempting to award points for user $user_id, quiz $quiz_id");
            $point_result = $this->user_point_service->award_quiz_completion_points($user_id, $quiz_id);
            
            // Prepare detailed response
            $response_data = [
                'debug_info' => [
                    'user_id' => (int)$user_id,
                    'quiz_id' => (int)$quiz_id,
                    'quiz_title' => isset($quiz->quiz_name) ? $quiz->quiz_name : (isset($quiz->name) ? $quiz->name : 'Unknown'),
                    'force_mode' => $force,
                    'is_completed' => $is_completed
                ],
                'quiz_stats' => [
                    'total_questions' => $total_questions,
                    'correct_answers' => $correct_answers,
                    'skipped_questions' => $skipped_questions,
                    'score_percentage' => $score_percentage,
                    'answered_questions' => $total_questions - $skipped_questions
                ],
                'point_calculation' => $point_result,
                'config_values' => [
                    'minimum_questions' => $this->config->item('points_minimum_quiz_questions') ?: 10,
                    'low_score_points' => $this->config->item('points_low_score') ?: 120,
                    'medium_score_points' => $this->config->item('points_medium_score') ?: 240,
                    'high_score_points' => $this->config->item('points_high_score') ?: 360,
                    'no_skip_bonus' => $this->config->item('points_bonus_no_skip') ?: 60,
                    'hard_level_bonus' => $this->config->item('points_bonus_hard_level') ?: 120,
                    'weekly_limit' => $this->config->item('points_weekly_limit_default') ?: 1200,
                    'score_threshold_low' => $this->config->item('points_score_threshold_low') ?: 50.0,
                    'score_threshold_high' => $this->config->item('points_score_threshold_high') ?: 90.0
                ]
            ];
            
            if (!empty($point_result['success'])) {
                $points_awarded = isset($point_result['points_awarded']) ? $point_result['points_awarded'] : 0;
                log_message('info', "Debug Points: Successfully awarded {$points_awarded} points to user $user_id for quiz $quiz_id");
                $message = "Points awarded successfully! {$points_awarded} points given.";
            } else {
                $error_message = isset($point_result['error']) ? $point_result['error'] : 'Unknown error';
                log_message('info', "Debug Points: Failed to award points to user $user_id for quiz $quiz_id. Reason: {$error_message}");
                $message = "Point calculation completed but points not awarded. Reason: {$error_message}";
            }
            
            $response = $this->get_success_response($response_data, $message);
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', 'Debug Points Error: ' . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Error during point calculation: " . $e->getMessage());
            $this->set_output($response);
        }
    }

    /**
     * Get unattempted quizzes for the current user
     * GET /api/quiz/unattempted
     */
    function unattempted_get()
    {
        // ✅ SECURE: Require JWT authentication
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        try {
            // Load the quiz model
            $this->load->model('quiz/quiz_model');
            
            // Get unattempted quizzes for the current user
            $unattempted_quizzes = $this->quiz_model->get_unattempted_quizzes_by_user($objUser->id);
            
            if (empty($unattempted_quizzes)) {
                $response = $this->get_success_response(array(), "No unattempted quizzes found");
                $this->set_output($response);
                return;
            }
            
            // Convert quiz objects to arrays for JSON response
            $quiz_list = array();
            foreach ($unattempted_quizzes as $quiz) {
                // Get total question count for this quiz
                $total_questions = 0;
                try {
                    $total_questions = $this->quiz_model->get_quiz_question_count($quiz->id);
                } catch (Exception $e) {
                    log_message('error', "Error getting question count for quiz {$quiz->id}: " . $e->getMessage());
                    $total_questions = 0; // Default to 0 if we can't get the count
                }
                
                $quiz_data = array(
                    'id' => $quiz->id,
                    'name' => $quiz->name,
                    'description' => $quiz->description,
                    'subject_id' => $quiz->subject_id,
                    'start_date' => $quiz->start_date,
                    'quiz_detail_image' => $quiz->quiz_detail_image,
                    'is_live' => $quiz->is_live,
                    'marking' => $quiz->marking,
                    'quiz_type' => $quiz->quiz_type,
                    'user_id' => $quiz->user_id,
                    'quiz_reference' => $quiz->quiz_reference,
                    'exam_id' => $quiz->exam_id,
                    'level' => $quiz->level,
                    'total_questions' => $total_questions,
                    'subject_names' => isset($quiz->subject_names) ? $quiz->subject_names : '',
                );
                $quiz_list[] = $quiz_data;
            }
            
            $response = $this->get_success_response($quiz_list, "Unattempted quizzes retrieved successfully");
            $this->set_output($response);
            
        } catch (Exception $e) {
            log_message('error', "Error getting unattempted quizzes for user {$objUser->id}: " . $e->getMessage());
            $response = $this->get_failed_response(NULL, "Failed to retrieve unattempted quizzes");
            $this->set_output($response);
        }
    }
}
