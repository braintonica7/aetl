<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Wizi_quiz extends API_Controller
{

    public function __constructor()
    {
        parent::__construct();
    }

    /**
     * List all WiZi quizzes (GET) or get a specific one by ID
     * Requires JWT authentication with admin privileges
     */
    function index_get($id = NULL)
    {
        // Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return;
        }

        if ($id == NULL) {
            $id = $this->input->get("id");
        }

        $this->load->model('wizi_quiz/wizi_quiz_model');
        $recordCount = $this->wizi_quiz_model->get_wizi_quiz_count();

        if (empty($id) || $id == NULL) {
            // Get paginated list
            $pageSize = $this->input->get('pagesize', true);
            $page = $this->input->get('page', true);
            $sortBy = $this->input->get('sortby', true);
            $sortOrder = $this->input->get('sortorder', true);
            $objFilter = $this->input->get('filter', true);

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

            $totalNoOfPages = intdiv($recordCount, $pageSize);
            $remainder = $recordCount % $pageSize;
            if ($remainder > 0)
                $totalNoOfPages++;

            $offset = ($page - 1) * $pageSize;
            $wizi_quizzes = $this->wizi_quiz_model->get_paginated_wizi_quiz($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            
            if (count($wizi_quizzes) > 0) {
                $response = $this->get_success_response($wizi_quizzes, 'WiZi quiz list');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($wizi_quizzes);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($wizi_quizzes, 'No WiZi quizzes available');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            // Get specific quiz by ID
            $wizi_quiz = $this->wizi_quiz_model->get_wizi_quiz($id);
            
            if ($wizi_quiz == NULL) {
                $response = $this->get_failed_response(NULL, "WiZi quiz not found");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($wizi_quiz, "WiZi quiz details");
                $this->set_output($response);
            }
        }
    }

    /**
     * Create a new WiZi quiz
     * Requires JWT authentication with admin privileges
     */
    function index_post()
    {
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return;
        }

        $request = $this->get_request();

        $objWiziQuiz = new Wizi_quiz_object();
        $objWiziQuiz->id = 0;
        $objWiziQuiz->name = array_key_exists('name', $request) ? $request['name'] : '';
        $objWiziQuiz->description = array_key_exists('description', $request) ? $request['description'] : '';
        $objWiziQuiz->instructions = array_key_exists('instructions', $request) ? $request['instructions'] : '';
        $objWiziQuiz->exam_id = array_key_exists('exam_id', $request) ? $request['exam_id'] : null;
        $objWiziQuiz->subject_id = array_key_exists('subject_id', $request) ? $request['subject_id'] : null;
        $objWiziQuiz->level = array_key_exists('level', $request) ? $request['level'] : 'Moderate';
        $objWiziQuiz->time_limit = array_key_exists('time_limit', $request) ? $request['time_limit'] : 180;
        $objWiziQuiz->passing_score = array_key_exists('passing_score', $request) ? $request['passing_score'] : 0;
        $objWiziQuiz->passing_percentage = array_key_exists('passing_percentage', $request) ? $request['passing_percentage'] : 0.00;
        $objWiziQuiz->total_marks = array_key_exists('total_marks', $request) ? $request['total_marks'] : 0;
        $objWiziQuiz->status = array_key_exists('status', $request) ? $request['status'] : 'draft';
        $objWiziQuiz->is_published = array_key_exists('is_published', $request) ? ($request['is_published'] ? 1 : 0) : 0;
        $objWiziQuiz->published_date = null;
        $objWiziQuiz->valid_from = array_key_exists('valid_from', $request) ? $request['valid_from'] : null;
        $objWiziQuiz->valid_until = array_key_exists('valid_until', $request) ? $request['valid_until'] : null;
        $objWiziQuiz->cover_image = array_key_exists('cover_image', $request) ? $request['cover_image'] : null;
        $objWiziQuiz->quiz_order = array_key_exists('quiz_order', $request) ? $request['quiz_order'] : null;
        $objWiziQuiz->created_by = $objUser->id;

        $this->load->model('wizi_quiz/wizi_quiz_model');
        $objWiziQuiz = $this->wizi_quiz_model->add_wizi_quiz($objWiziQuiz);
        
        if ($objWiziQuiz === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating WiZi quiz");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objWiziQuiz, "WiZi quiz created successfully");
            $this->set_output($response);
        }
    }

    /**
     * Update an existing WiZi quiz
     * Requires JWT authentication with admin privileges
     */
    function index_put($id)
    {
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return;
        }

        $request = $this->get_request();

        $this->load->model('wizi_quiz/wizi_quiz_model');
        $objWiziQuizOriginal = $this->wizi_quiz_model->get_wizi_quiz($id);

        if ($objWiziQuizOriginal == NULL) {
            $response = $this->get_failed_response(NULL, "WiZi quiz not found");
            $this->set_output($response);
            return;
        }

        $objWiziQuiz = new Wizi_quiz_object();
        $objWiziQuiz->id = $objWiziQuizOriginal->id;
        $objWiziQuiz->name = array_key_exists('name', $request) ? $request['name'] : $objWiziQuizOriginal->name;
        $objWiziQuiz->description = array_key_exists('description', $request) ? $request['description'] : $objWiziQuizOriginal->description;
        $objWiziQuiz->instructions = array_key_exists('instructions', $request) ? $request['instructions'] : $objWiziQuizOriginal->instructions;
        $objWiziQuiz->exam_id = array_key_exists('exam_id', $request) ? $request['exam_id'] : $objWiziQuizOriginal->exam_id;
        $objWiziQuiz->subject_id = array_key_exists('subject_id', $request) ? $request['subject_id'] : $objWiziQuizOriginal->subject_id;
        $objWiziQuiz->level = array_key_exists('level', $request) ? $request['level'] : $objWiziQuizOriginal->level;
        $objWiziQuiz->time_limit = array_key_exists('time_limit', $request) ? $request['time_limit'] : $objWiziQuizOriginal->time_limit;
        $objWiziQuiz->passing_score = array_key_exists('passing_score', $request) ? $request['passing_score'] : $objWiziQuizOriginal->passing_score;
        $objWiziQuiz->passing_percentage = array_key_exists('passing_percentage', $request) ? $request['passing_percentage'] : $objWiziQuizOriginal->passing_percentage;
        $objWiziQuiz->total_marks = array_key_exists('total_marks', $request) ? $request['total_marks'] : $objWiziQuizOriginal->total_marks;
        $objWiziQuiz->status = array_key_exists('status', $request) ? $request['status'] : $objWiziQuizOriginal->status;
        $objWiziQuiz->is_published = array_key_exists('is_published', $request) ? ($request['is_published'] ? 1 : 0) : $objWiziQuizOriginal->is_published;
        $objWiziQuiz->published_date = $objWiziQuizOriginal->published_date;
        $objWiziQuiz->valid_from = array_key_exists('valid_from', $request) ? $request['valid_from'] : $objWiziQuizOriginal->valid_from;
        $objWiziQuiz->valid_until = array_key_exists('valid_until', $request) ? $request['valid_until'] : $objWiziQuizOriginal->valid_until;
        $objWiziQuiz->cover_image = array_key_exists('cover_image', $request) ? $request['cover_image'] : $objWiziQuizOriginal->cover_image;
        $objWiziQuiz->quiz_order = array_key_exists('quiz_order', $request) ? $request['quiz_order'] : $objWiziQuizOriginal->quiz_order;
        $objWiziQuiz->created_by = $objWiziQuizOriginal->created_by;

        $objWiziQuiz = $this->wizi_quiz_model->update_wizi_quiz($objWiziQuiz);
        
        if ($objWiziQuiz === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating WiZi quiz");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objWiziQuiz, "WiZi quiz updated successfully");
            $this->set_output($response);
        }
    }

    /**
     * Delete a WiZi quiz
     * Requires JWT authentication with admin privileges
     */
    function index_delete($id = NULL)
    {
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return;
        }

        if ($id != NULL) {
            $this->load->model('wizi_quiz/wizi_quiz_model');
            $deleted = $this->wizi_quiz_model->delete_wizi_quiz($id);
            
            if ($deleted) {
                $response = $this->get_success_response($id, "WiZi quiz deleted successfully");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "WiZi quiz deletion failed");
                $this->set_output($response);
            }
        }
    }

    /**
     * Publish or unpublish a WiZi quiz
     * Requires JWT authentication with admin privileges
     */
    function publish_post($id)
    {
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return;
        }

        $request = $this->get_request();
        $is_published = array_key_exists('is_published', $request) ? $request['is_published'] : 1;

        $this->load->model('wizi_quiz/wizi_quiz_model');
        $result = $this->wizi_quiz_model->publish_wizi_quiz($id, $is_published);

        if ($result) {
            $message = $is_published ? "WiZi quiz published successfully" : "WiZi quiz unpublished successfully";
            $response = $this->get_success_response($id, $message);
            $this->set_output($response);
        } else {
            $response = $this->get_failed_response($id, "Failed to update publish status");
            $this->set_output($response);
        }
    }

    /**
     * Get all questions for a specific WiZi quiz
     * Requires JWT authentication with admin privileges
     */
    function questions_get($wizi_quiz_id)
    {
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return;
        }

        if (empty($wizi_quiz_id)) {
            $response = $this->get_failed_response(NULL, "WiZi quiz ID is required");
            $this->set_output($response);
            return;
        }

        $this->load->model('wizi_quiz/wizi_quiz_question_model');
        $questions = $this->wizi_quiz_question_model->get_questions_by_quiz($wizi_quiz_id);

        if ($questions === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while getting questions");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($questions, "Questions retrieved successfully");
            $response['total'] = count($questions);
            $this->set_output($response);
        }
    }

    /**
     * Add questions to a WiZi quiz
     * Requires JWT authentication with admin privileges
     */
    function add_questions_post($wizi_quiz_id)
    {
        $objUser = $this->require_jwt_auth(true);
        if (!$objUser) {
            return;
        }

        if (empty($wizi_quiz_id)) {
            $response = $this->get_failed_response(NULL, "WiZi quiz ID is required");
            $this->set_output($response);
            return;
        }

        $request = $this->get_request();
        $questions = array_key_exists('questions', $request) ? $request['questions'] : [];

        if (empty($questions) || !is_array($questions)) {
            $response = $this->get_failed_response(NULL, "Questions array is required");
            $this->set_output($response);
            return;
        }

        // Sort questions by wizi_question_id in ascending order
        usort($questions, function($a, $b) {
            $id_a = isset($a['wizi_question_id']) ? $a['wizi_question_id'] : 0;
            $id_b = isset($b['wizi_question_id']) ? $b['wizi_question_id'] : 0;
            return $id_a - $id_b;
        });

        $this->load->model('wizi_quiz/wizi_quiz_question_model');
        $added_count = 0;

        foreach ($questions as $question) {
            $objWiziQuizQuestion = new Wizi_quiz_question_object();
            $objWiziQuizQuestion->id = 0;
            $objWiziQuizQuestion->wizi_quiz_id = $wizi_quiz_id;
            $objWiziQuizQuestion->wizi_question_id = $question['wizi_question_id'];
            $objWiziQuizQuestion->question_order = array_key_exists('question_order', $question) ? $question['question_order'] : 0;
            $objWiziQuizQuestion->marks = array_key_exists('marks', $question) ? $question['marks'] : 4;
            $objWiziQuizQuestion->negative_marks = array_key_exists('negative_marks', $question) ? $question['negative_marks'] : -1.0;

            // Get question_type from wizi_question table
            $pdo = CDatabase::getPdo();
            $questionTypeSql = "SELECT question_type FROM wizi_question WHERE id = ?";
            $questionTypeStmt = $pdo->prepare($questionTypeSql);
            $questionTypeStmt->execute(array($objWiziQuizQuestion->wizi_question_id));
            $questionTypeRow = $questionTypeStmt->fetch();
            $objWiziQuizQuestion->question_type = isset($questionTypeRow['question_type']) ? $questionTypeRow['question_type'] : 'mcq';
            $questionTypeStmt = NULL;
            $pdo = NULL;

            $result = $this->wizi_quiz_question_model->add_wizi_quiz_question($objWiziQuizQuestion);
            if ($result !== FALSE) {
                $added_count++;
            }
        }

        if ($added_count > 0) {
            $response = $this->get_success_response(['added_count' => $added_count], "$added_count questions added successfully");
            $this->set_output($response);
        } else {
            $response = $this->get_failed_response(NULL, "Failed to add questions");
            $this->set_output($response);
        }
    }

    /**
     * Get list of active/published WiZi quizzes for students
     * Requires JWT authentication
     * @param string language Optional language filter (default: english)
     */
    function active_get()
    {
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        // Get language parameter with default value
        $language = $this->input->get('language', true);
        if (empty($language)) {
            $language = 'english';
        }

        // Validate language parameter
        $allowed_languages = array('english', 'hindi', 'spanish', 'french', 'german');
        if (!in_array(strtolower($language), $allowed_languages)) {
            $language = 'english'; // Fallback to default
        }

        $this->load->model('wizi_quiz/wizi_quiz_model');
        $active_quizzes = $this->wizi_quiz_model->get_active_wizi_quizzes($language);

        // If no quizzes found in requested language and it's not english, try english as fallback
        if (empty($active_quizzes) && strtolower($language) !== 'english') {
            log_message('DEBUG', "No quizzes found for language: " . $language . ", falling back to English");
            $active_quizzes = $this->wizi_quiz_model->get_active_wizi_quizzes('english');
        }
        
        log_message('DEBUG', "Total active quizzes found: " . count($active_quizzes) . " for user ID: " . $objUser->id);

        // Load user attempt model to check attempts
        $this->load->model('wizi_quiz/wizi_quiz_user_model');

        // Get user subscription to determine quiz access
        $this->load->model('user/user_model');
        $subscription_data = $this->user_model->get_user_subscription_features($objUser->id);
        $plan_key = isset($subscription_data['subscription_plan_key']) ? $subscription_data['subscription_plan_key'] : 'free';
		log_message('DEBUG', "User ID {$objUser->id} has subscription plan: {$plan_key}");
        // Determine max accessible quizzes based on subscription
        // free/basic: 1 quiz, premium/pro: all quizzes
        $max_quizzes = ($plan_key === 'free') ? 1 : PHP_INT_MAX;

        $previous_quiz_attempted = true; // First quiz is always unlocked
        $enriched_quizzes = array();

        foreach ($active_quizzes as $quiz) {
            // Check if user has attempted this specific quiz (completed attempts only)
            $has_attempted = $this->wizi_quiz_user_model->has_user_attempted_quiz($quiz->id, $objUser->id);
            
            // Check subscription-based access (based on quiz_order)
            $subscription_locked = $quiz->quiz_order > $max_quizzes;
            
            // For sequential unlocking, check if previous quiz ORDER has been attempted (same language)
            if ($quiz->quiz_order > 1) {
                $previous_quiz_attempted = $this->wizi_quiz_user_model->has_user_attempted_quiz_order($quiz->quiz_order - 1, $quiz->language, $objUser->id);
            }
            
            log_message('DEBUG', "Quiz ID {$quiz->id}, Order: {$quiz->quiz_order}, Has Attempted: " . ($has_attempted ? 'Yes' : 'No') . ", Previous Quiz Attempted: " . ($previous_quiz_attempted ? 'Yes' : 'No') . ", Subscription Locked: " . ($subscription_locked ? 'Yes' : 'No'));
            
            // Quiz is locked if:
            // 1. Previous quiz in sequence hasn't been attempted (sequential lock)
            // 2. User's subscription doesn't allow access to this quiz (subscription lock)
            $is_locked = !$previous_quiz_attempted || $subscription_locked;
            
            // Determine lock reason for frontend display
            $lock_reason = null;
            if ($subscription_locked) {
                $lock_reason = 'subscription';
            } else if (!$previous_quiz_attempted) {
                $lock_reason = 'sequential';
            }
            
            // Add flags to quiz object
            $quiz->has_attempted = $has_attempted;
            $quiz->is_locked = $is_locked;
            $quiz->lock_reason = $lock_reason;
            $quiz->user_plan = $plan_key;
            
            $enriched_quizzes[] = $quiz;
        }

        $response = $this->get_success_response($enriched_quizzes, "Active WiZi quizzes");
        $response['total'] = count($enriched_quizzes);
        $this->set_output($response);
    }

    /**
     * Start a new attempt for a WiZi quiz
     * Requires JWT authentication
     */
    function start_mock_test_post($wizi_quiz_id)
    {
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        if (empty($wizi_quiz_id)) {
            $response = $this->get_failed_response(NULL, "WiZi quiz ID is required");
            $this->set_output($response);
            return;
        }

        // Check if quiz exists and is published
        $this->load->model('wizi_quiz/wizi_quiz_model');
        $quiz = $this->wizi_quiz_model->get_wizi_quiz($wizi_quiz_id);

        if ($quiz == NULL) {
            $response = $this->get_failed_response(NULL, "WiZi quiz not found");
            $this->set_output($response);
            return;
        }

        if ($quiz->is_published != 1) {
            $response = $this->get_failed_response(NULL, "This quiz is not available");
            $this->set_output($response);
            return;
        }

        // Check subscription-based access
        $this->load->model('user/user_model');
        $subscription_data = $this->user_model->get_user_subscription_features($objUser->id);
        $plan_key = isset($subscription_data['subscription_plan_key']) ? $subscription_data['subscription_plan_key'] : 'free';

        // Determine max accessible quizzes based on subscription
        $max_quizzes = ($plan_key === 'free' ) ? 1 : PHP_INT_MAX;

        if ($quiz->quiz_order > $max_quizzes) {
            $response = $this->get_failed_response(NULL, "This quiz requires a Premium or Pro subscription. Please upgrade your plan to access.");
            $this->set_output($response);
            return;
        }

        // Check if previous quiz in sequence has been attempted (sequential unlocking - language specific)
        if ($quiz->quiz_order > 1) {
            $this->load->model('wizi_quiz/wizi_quiz_user_model');
            $previous_quiz_attempted = $this->wizi_quiz_user_model->has_user_attempted_quiz_order($quiz->quiz_order - 1, $quiz->language, $objUser->id);
            if (!$previous_quiz_attempted) {
                $response = $this->get_failed_response(NULL, "Please complete the previous quiz first");
                $this->set_output($response);
                return;
            }
        }

        // Check validity dates
        $current_date = date('Y-m-d H:i:s');
        if ($quiz->valid_from != NULL && $current_date < $quiz->valid_from) {
            $response = $this->get_failed_response(NULL, "This quiz is not yet available");
            $this->set_output($response);
            return;
        }

        if ($quiz->valid_until != NULL && $current_date > $quiz->valid_until) {
            $response = $this->get_failed_response(NULL, "This quiz has expired");
            $this->set_output($response);
            return;
        }

        // Create new attempt
        $this->load->model('wizi_quiz/wizi_quiz_user_model');
        $attempt_number = $this->wizi_quiz_user_model->get_next_attempt_number($wizi_quiz_id, $objUser->id);

        // Get total questions count for this quiz
        $this->load->model('wizi_quiz/wizi_quiz_question_model');
        $total_questions = $this->wizi_quiz_question_model->get_question_count_by_quiz($wizi_quiz_id);

        $objWiziQuizUser = new Wizi_quiz_user_object();
        $objWiziQuizUser->id = 0;
        $objWiziQuizUser->wizi_quiz_id = $wizi_quiz_id;
        $objWiziQuizUser->user_id = $objUser->id;
        $objWiziQuizUser->attempt_number = $attempt_number;
        $objWiziQuizUser->attempt_status = 'in_progress';
        $objWiziQuizUser->started_at = date('Y-m-d H:i:s');
        $objWiziQuizUser->total_questions = $total_questions;
        $objWiziQuizUser->questions_attempted = 0;
        $objWiziQuizUser->correct_answers = 0;
        $objWiziQuizUser->incorrect_answers = 0;
        $objWiziQuizUser->skipped_questions = 0;
        $objWiziQuizUser->total_score = 0;
        $objWiziQuizUser->percentage = 0;
        $objWiziQuizUser->time_taken_seconds = 0;
        $objWiziQuizUser->best_attempt = 0;

        $objWiziQuizUser = $this->wizi_quiz_user_model->add_wizi_quiz_user($objWiziQuizUser);

        if ($objWiziQuizUser === FALSE) {
            $response = $this->get_failed_response(NULL, "Failed to start quiz attempt");
            $this->set_output($response);
            return;
        }

        // Copy questions to user attempt
        $this->load->model('wizi_quiz/wizi_quiz_question_user_model');
        $copied = $this->wizi_quiz_question_user_model->copy_questions_to_user_attempt($objWiziQuizUser->id, $wizi_quiz_id);

        if (!$copied) {
            $response = $this->get_failed_response(NULL, "Failed to initialize quiz questions");
            $this->set_output($response);
            return;
        }

        $response = $this->get_success_response($objWiziQuizUser, "Quiz attempt started successfully");
        $this->set_output($response);
    }

    /**
     * Get questions for current attempt
     * Requires JWT authentication
     */
    function attempt_questions_get($attempt_id)
    {
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        if (empty($attempt_id)) {
            $response = $this->get_failed_response(NULL, "Attempt ID is required");
            $this->set_output($response);
            return;
        }

        // Verify attempt belongs to user
        $this->load->model('wizi_quiz/wizi_quiz_user_model');
        $attempt = $this->wizi_quiz_user_model->get_wizi_quiz_user($attempt_id);

        if ($attempt == NULL || $attempt->user_id != $objUser->id) {
            $response = $this->get_failed_response(NULL, "Attempt not found or access denied");
            $this->set_output($response);
            return;
        }

        // Ensure attempt is marked as in_progress when quiz is opened
        // This handles cases where user navigates to quiz page or refreshes
        if ($attempt->attempt_status != 'completed') {
            $attempt->attempt_status = 'in_progress';
            $this->wizi_quiz_user_model->update_wizi_quiz_user($attempt);
        }

        // Get questions without correct answers and solutions for in-progress attempts
        $this->load->model('wizi_quiz/wizi_quiz_question_user_model');
        $questions = $this->wizi_quiz_question_user_model->get_questions_by_attempt($attempt_id);

        // Remove sensitive data if quiz is in progress
        if ($attempt->attempt_status == 'in_progress') {
            foreach ($questions as $question) {
                unset($question->correct_answer);
                unset($question->solution);
            }
        }

        // Prepare response with attempt info and questions
        $result = array(
            'attempt' => array(
                'id' => $attempt->id,
                'wizi_quiz_id' => $attempt->wizi_quiz_id,
                'attempt_number' => $attempt->attempt_number,
                'attempt_status' => $attempt->attempt_status,
                'current_question_index' => $attempt->current_question_index,
                'total_questions' => $attempt->total_questions,
                'answered_questions' => $attempt->answered_questions,
                'started_at' => $attempt->started_at,
                'time_spent' => $attempt->time_spent
            ),
            'questions' => $questions
        );

        $response = $this->get_success_response($result, "Questions retrieved successfully");
        $response['total'] = count($questions);
        $this->set_output($response);
    }

    /**
     * Submit answer for a question
     * Requires JWT authentication
     */
    function submit_answer_post()
    {
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        $request = $this->get_request();
        $question_user_id = array_key_exists('question_user_id', $request) ? $request['question_user_id'] : NULL;
        $user_answer = array_key_exists('user_answer', $request) ? $request['user_answer'] : NULL;
        $time_spent = array_key_exists('time_spent', $request) ? $request['time_spent'] : 0;
        $status = array_key_exists('status', $request) ? $request['status'] : 'answered';

        if (empty($question_user_id)) {
            $response = $this->get_failed_response(NULL, "Question user ID is required");
            $this->set_output($response);
            return;
        }

        // Get the question user record
        $this->load->model('wizi_quiz/wizi_quiz_question_user_model');
        $question_user = $this->wizi_quiz_question_user_model->get_wizi_quiz_question_user($question_user_id);

        if ($question_user == NULL) {
            $response = $this->get_failed_response(NULL, "Question not found");
            $this->set_output($response);
            return;
        }

        // Verify user owns this attempt
        $this->load->model('wizi_quiz/wizi_quiz_user_model');
        $attempt = $this->wizi_quiz_user_model->get_wizi_quiz_user($question_user->wizi_quiz_user_id);

        if ($attempt == NULL || $attempt->user_id != $objUser->id) {
            $response = $this->get_failed_response(NULL, "Access denied");
            $this->set_output($response);
            return;
        }

        // Update the answer
        $question_user->user_answer = $user_answer;
        $question_user->status = $status;
        $question_user->time_spent = $time_spent;
        $question_user->answered_at = date('Y-m-d H:i:s');

        // Check if answer is correct
        // For 'answered' and 'answered_marked_review' statuses, calculate marks
        if ($user_answer != NULL && ($status == 'answered' || $status == 'answered_marked_review')) {
            $question_user->is_correct = ($user_answer == $question_user->correct_answer) ? 1 : 0;
            $question_user->marks_obtained = $question_user->is_correct ? $question_user->marks : $question_user->negative_marks;
        } else {
            $question_user->is_correct = 0;
            $question_user->marks_obtained = 0;
        }

        $updated = $this->wizi_quiz_question_user_model->update_wizi_quiz_question_user($question_user);

        if ($updated === FALSE) {
            $response = $this->get_failed_response(NULL, "Failed to save answer");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($updated, "Answer saved successfully");
            $this->set_output($response);
        }
    }

    /**
     * Update question status without submitting answer
     * Used for: Mark for Review, Clear Response, etc.
     * Requires JWT authentication
     */
    function update_question_status_post()
    {
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        $request = $this->get_request();
        $question_user_id = array_key_exists('question_user_id', $request) ? $request['question_user_id'] : NULL;
        $status = array_key_exists('status', $request) ? $request['status'] : NULL;
        $time_spent = array_key_exists('time_spent', $request) ? $request['time_spent'] : NULL;

        if (empty($question_user_id)) {
            $response = $this->get_failed_response(NULL, "Question user ID is required");
            $this->set_output($response);
            return;
        }

        if (empty($status)) {
            $response = $this->get_failed_response(NULL, "Status is required");
            $this->set_output($response);
            return;
        }

        // Validate status values
        $valid_statuses = array('not_attempted', 'marked_review', 'answered_marked_review');
        if (!in_array($status, $valid_statuses)) {
            $response = $this->get_failed_response(NULL, "Invalid status. Allowed: not_attempted, marked_review, answered_marked_review");
            $this->set_output($response);
            return;
        }

        // Get the question user record
        $this->load->model('wizi_quiz/wizi_quiz_question_user_model');
        $question_user = $this->wizi_quiz_question_user_model->get_wizi_quiz_question_user($question_user_id);

        if ($question_user == NULL) {
            $response = $this->get_failed_response(NULL, "Question not found");
            $this->set_output($response);
            return;
        }

        // Verify user owns this attempt
        $this->load->model('wizi_quiz/wizi_quiz_user_model');
        $attempt = $this->wizi_quiz_user_model->get_wizi_quiz_user($question_user->wizi_quiz_user_id);

        if ($attempt == NULL || $attempt->user_id != $objUser->id) {
            $response = $this->get_failed_response(NULL, "Access denied");
            $this->set_output($response);
            return;
        }

        // Update the status
        $question_user->status = $status;
        
        // Update time spent if provided
        if ($time_spent !== NULL) {
            $question_user->time_spent = $time_spent;
        }

        // If clearing response (not_attempted), clear the answer
        if ($status == 'not_attempted') {
            $question_user->user_answer = NULL;
            $question_user->is_correct = 0;
            $question_user->marks_obtained = 0;
        }
        // If marking for review with existing answer, keep the answer but update status
        // answered_marked_review preserves the answer and marks

        $updated = $this->wizi_quiz_question_user_model->update_wizi_quiz_question_user($question_user);

        if ($updated === FALSE) {
            $response = $this->get_failed_response(NULL, "Failed to update question status");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($updated, "Question status updated successfully");
            $this->set_output($response);
        }
    }

    /**
     * Update current question index for resume functionality
     * Requires JWT authentication
     */
    function update_current_question_post($attempt_id)
    {
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        if (empty($attempt_id)) {
            $response = $this->get_failed_response(NULL, "Attempt ID is required");
            $this->set_output($response);
            return;
        }

        $request = $this->get_request();
        $current_question_index = array_key_exists('current_question_index', $request) ? $request['current_question_index'] : NULL;

        if ($current_question_index === NULL) {
            $response = $this->get_failed_response(NULL, "Current question index is required");
            $this->set_output($response);
            return;
        }

        // Verify attempt belongs to user
        $this->load->model('wizi_quiz/wizi_quiz_user_model');
        $attempt = $this->wizi_quiz_user_model->get_wizi_quiz_user($attempt_id);

        if ($attempt == NULL || $attempt->user_id != $objUser->id) {
            $response = $this->get_failed_response(NULL, "Attempt not found or access denied");
            $this->set_output($response);
            return;
        }

        // Update current question index
        $attempt->current_question_index = $current_question_index;
        $updated = $this->wizi_quiz_user_model->update_wizi_quiz_user($attempt);

        if ($updated === FALSE) {
            $response = $this->get_failed_response(NULL, "Failed to update current question");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response(array('current_question_index' => $current_question_index), "Current question updated successfully");
            $this->set_output($response);
        }
    }

    /**
     * Update time spent for the quiz attempt
     * Requires JWT authentication
     */
    function update_time_spent_post($attempt_id)
    {
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        if (empty($attempt_id)) {
            $response = $this->get_failed_response(NULL, "Attempt ID is required");
            $this->set_output($response);
            return;
        }

        $request = $this->get_request();
        $time_spent = array_key_exists('time_spent', $request) ? $request['time_spent'] : NULL;

        if ($time_spent === NULL) {
            $response = $this->get_failed_response(NULL, "Time spent is required");
            $this->set_output($response);
            return;
        }

        // Verify attempt belongs to user
        $this->load->model('wizi_quiz/wizi_quiz_user_model');
        $attempt = $this->wizi_quiz_user_model->get_wizi_quiz_user($attempt_id);

        if ($attempt == NULL || $attempt->user_id != $objUser->id) {
            $response = $this->get_failed_response(NULL, "Attempt not found or access denied");
            $this->set_output($response);
            return;
        }

        // Update time spent
        $attempt->time_spent = $time_spent;
        $updated = $this->wizi_quiz_user_model->update_wizi_quiz_user($attempt);

        if ($updated === FALSE) {
            $response = $this->get_failed_response(NULL, "Failed to update time spent");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response(array('time_spent' => $time_spent), "Time spent updated successfully");
            $this->set_output($response);
        }
    }

    /**
     * Complete/submit the quiz attempt
     * Requires JWT authentication
     */
    function complete_attempt_post($attempt_id)
    {
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        if (empty($attempt_id)) {
            $response = $this->get_failed_response(NULL, "Attempt ID is required");
            $this->set_output($response);
            return;
        }

        // Verify attempt belongs to user
        $this->load->model('wizi_quiz/wizi_quiz_user_model');
        $attempt = $this->wizi_quiz_user_model->get_wizi_quiz_user($attempt_id);

        if ($attempt == NULL || $attempt->user_id != $objUser->id) {
            $response = $this->get_failed_response(NULL, "Attempt not found or access denied");
            $this->set_output($response);
            return;
        }

        // Get statistics
        $this->load->model('wizi_quiz/wizi_quiz_question_user_model');
        $stats = $this->wizi_quiz_question_user_model->get_statistics_by_attempt($attempt_id);

        // Update attempt with final statistics
        $attempt->attempt_status = 'completed';
        $attempt->total_questions = $stats['total_questions'];
        $attempt->answered_questions = $stats['answered'];
        $attempt->correct_answers = $stats['correct'];
        $attempt->incorrect_answers = $stats['incorrect'];
        $attempt->skipped_questions = $stats['skipped'];
        $attempt->total_score = $stats['total_score'];
        $attempt->completed_at = date('Y-m-d H:i:s');

        // Calculate total marks from questions
        $this->load->model('wizi_quiz/wizi_quiz_question_model');
        $total_marks = 0;
        $quiz_questions = $this->wizi_quiz_question_model->get_questions_by_quiz($attempt->wizi_quiz_id);
        foreach ($quiz_questions as $q) {
            $total_marks += $q->marks;
        }
        $attempt->total_marks = $total_marks;

        // Calculate accuracy percentage
        if ($stats['total_questions'] > 0) {
            $attempt->accuracy_percentage = ($stats['correct'] / $stats['total_questions']) * 100;
        } else {
            $attempt->accuracy_percentage = 0;
        }

        // Calculate time spent from individual questions
        $user_questions = $this->wizi_quiz_question_user_model->get_questions_by_attempt($attempt_id);
        $total_time_spent = 0;
        foreach ($user_questions as $uq) {
            $total_time_spent += $uq->time_spent;
        }
        $attempt->time_spent = $total_time_spent;

        // Update best attempt flag first
        $this->wizi_quiz_user_model->update_best_attempt($attempt->wizi_quiz_id, $objUser->id, $attempt->id);

        // Calculate rank (after best_attempt is updated)
        $attempt->rank = $this->wizi_quiz_user_model->calculate_rank($attempt->wizi_quiz_id, $objUser->id, $attempt->id);

        $updated = $this->wizi_quiz_user_model->update_wizi_quiz_user($attempt);

        if ($updated === FALSE) {
            $response = $this->get_failed_response(NULL, "Failed to complete quiz attempt");
            $this->set_output($response);
            return;
        }

        $response = $this->get_success_response($updated, "Quiz attempt completed successfully");
        $this->set_output($response);
    }

    /**
     * Get leaderboard for a WiZi quiz
     * Requires JWT authentication
     */
    function leaderboard_get($wizi_quiz_id)
    {
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        if (empty($wizi_quiz_id)) {
            $response = $this->get_failed_response(NULL, "WiZi quiz ID is required");
            $this->set_output($response);
            return;
        }

        $limit = $this->input->get('limit', true);
        if (empty($limit) || !is_numeric($limit)) {
            $limit = 50;
        }

        $this->load->model('wizi_quiz/wizi_quiz_user_model');
        $leaderboard = $this->wizi_quiz_user_model->get_leaderboard($wizi_quiz_id, $limit);

        $response = $this->get_success_response($leaderboard, "Leaderboard retrieved successfully");
        $response['total'] = count($leaderboard);
        $this->set_output($response);
    }

    /**
     * Get user's attempts for a specific WiZi quiz
     * Requires JWT authentication
     */
    function my_attempts_get($wizi_quiz_id)
    {
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        if (empty($wizi_quiz_id)) {
            $response = $this->get_failed_response(NULL, "WiZi quiz ID is required");
            $this->set_output($response);
            return;
        }

        $this->load->model('wizi_quiz/wizi_quiz_user_model');
        $attempts = $this->wizi_quiz_user_model->get_user_attempts($wizi_quiz_id, $objUser->id);

        $response = $this->get_success_response($attempts, "User attempts retrieved successfully");
        $response['total'] = count($attempts);
        $this->set_output($response);
    }

    /**
     * Get detailed results for a completed attempt
     * Requires JWT authentication
     */
    function attempt_result_get($attempt_id)
    {
        $objUser = $this->require_jwt_auth(false);
        if (!$objUser) {
            return;
        }

        if (empty($attempt_id)) {
            $response = $this->get_failed_response(NULL, "Attempt ID is required");
            $this->set_output($response);
            return;
        }

        // Verify attempt belongs to user
        $this->load->model('wizi_quiz/wizi_quiz_user_model');
        $attempt = $this->wizi_quiz_user_model->get_wizi_quiz_user($attempt_id);

        if ($attempt == NULL || $attempt->user_id != $objUser->id) {
            $response = $this->get_failed_response(NULL, "Attempt not found or access denied");
            $this->set_output($response);
            return;
        }

        // Get questions with answers and solutions
        $this->load->model('wizi_quiz/wizi_quiz_question_user_model');
        $questions = $this->wizi_quiz_question_user_model->get_questions_by_attempt($attempt_id);

        // Get rank
        $rank = $this->wizi_quiz_user_model->calculate_rank($attempt->wizi_quiz_id, $objUser->id, $attempt_id);

        $result = array(
            'attempt' => $attempt,
            'questions' => $questions,
            'rank' => $rank,
            'total_questions' => count($questions)
        );

        $response = $this->get_success_response($result, "Attempt result retrieved successfully");
        $this->set_output($response);
    }
}
