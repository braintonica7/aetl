<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class User_question extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    function index_get($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('user_question/user_question_model');
        $recordCount = $this->user_question_model->get_user_question_count();

        if ($id == NULL) {
            //give multiple records...
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

            $offset = ($page - 1) * $pageSize;
            $records = $this->user_question_model->get_all_user_questions();

            $response['count'] = $recordCount;
            $response['records'] = $records;
            $response['code'] = REST_Controller::HTTP_OK;

            $this->set_response($response, REST_Controller::HTTP_OK);
        } else {
            $record = $this->user_question_model->get_user_question($id);

            if ($record != NULL) {
                $response['count'] = 1;
                $response['records'] = $record;
                $response['code'] = REST_Controller::HTTP_OK;

                $this->set_response($response, REST_Controller::HTTP_OK);
            } else {
                $response['code'] = REST_Controller::HTTP_NOT_FOUND;
                $response['error'] = 'User question not found';
                $this->set_response($response, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    /**
     * Submit an answer for a question
     * POST /api/user_question/submit
     * 
     * Required parameters:
     * - user_id: ID of the user
     * - quiz_id: ID of the quiz
     * - question_id: ID of the question
     * - option_answer: User's selected answer
     * 
     * Optional parameters:
     * - duration: Time spent on the question (in seconds)
     */
    function submit_post() {
        $this->require_jwt_auth(false); // User level auth - users can submit their answers
        $this->load->model('user_question/user_question_model');
        
        $request = json_decode($this->input->raw_input_stream, true);
        
        // Validate required fields
        if (empty($request['user_id']) || empty($request['quiz_id']) || 
            empty($request['question_id']) || !isset($request['option_answer'])) {
            $response['code'] = REST_Controller::HTTP_BAD_REQUEST;
            $response['error'] = 'Missing required fields: user_id, quiz_id, question_id, option_answer';
            $this->set_response($response, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $userId = $request['user_id'];
        $quizId = $request['quiz_id'];
        $questionId = $request['question_id'];
        $userAnswer = $request['option_answer'];
        $duration = isset($request['duration']) ? $request['duration'] : 0;

        // Submit the answer
        $result = $this->user_question_model->submit_answer($userId, $quizId, $questionId, $userAnswer, $duration);

        if ($result !== FALSE) {
            // Get the submitted answer details
            $submittedAnswer = $this->user_question_model->get_user_question($result);
            
            $response['code'] = REST_Controller::HTTP_OK;
            $response['message'] = 'Answer submitted successfully';
            $response['data'] = array(
                'id' => $submittedAnswer->id,
                'user_id' => $submittedAnswer->user_id,
                'quiz_id' => $submittedAnswer->quiz_id,
                'question_id' => $submittedAnswer->question_id,
                'option_answer' => $submittedAnswer->option_answer,
                'is_correct' => $submittedAnswer->is_correct,
                'score' => $submittedAnswer->score,
                'duration' => $submittedAnswer->duration,
                'created_at' => $submittedAnswer->created_at
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
        } else {
            $response['code'] = REST_Controller::HTTP_INTERNAL_SERVER_ERROR;
            $response['error'] = 'Failed to submit answer';
            $this->set_response($response, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get quiz results for a user
     * GET /api/user_question/results/{user_id}/{quiz_id}
     */
    function results_get($userId = NULL, $quizId = NULL) {
        $this->require_jwt_auth(false); // User level auth - users can view their results
        $this->load->model('user_question/user_question_model');
        
        if ($userId == NULL || $quizId == NULL) {
            $response['code'] = REST_Controller::HTTP_BAD_REQUEST;
            $response['error'] = 'User ID and Quiz ID are required';
            $this->set_response($response, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $results = $this->user_question_model->get_user_quiz_results($userId, $quizId);
        
        // Calculate summary statistics
        $totalQuestions = count($results);
        $correctAnswers = 0;
        $totalScore = 0;
        $totalDuration = 0;

        foreach ($results as $result) {
            if ($result->is_correct) {
                $correctAnswers++;
            }
            $totalScore += $result->score;
            $totalDuration += $result->duration;
        }

        $accuracy = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

        $response['code'] = REST_Controller::HTTP_OK;
        $response['summary'] = array(
            'user_id' => $userId,
            'quiz_id' => $quizId,
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'incorrect_answers' => $totalQuestions - $correctAnswers,
            'accuracy_percentage' => $accuracy,
            'total_score' => $totalScore,
            'total_duration_seconds' => $totalDuration
        );
        $response['detailed_results'] = $results;
        
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    function index_post() {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('user_question/user_question_model');
        
        $request = json_decode($this->input->raw_input_stream, true);
        
        $objUserQuestion = new User_question_object();
        $objUserQuestion->user_id = $request['user_id'];
        $objUserQuestion->quiz_id = $request['quiz_id'];
        $objUserQuestion->question_id = $request['question_id'];
        $objUserQuestion->duration = $request['duration'];
        $objUserQuestion->option_answer = $request['option_answer'];
        $objUserQuestion->score = $request['score'];
        $objUserQuestion->is_correct = $request['is_correct'];

        $result = $this->user_question_model->add_user_question($objUserQuestion);

        if ($result != FALSE) {
            $response['code'] = REST_Controller::HTTP_OK;
            $response['records'] = $result;
            $this->set_response($response, REST_Controller::HTTP_OK);
        } else {
            $response['code'] = REST_Controller::HTTP_INTERNAL_SERVER_ERROR;
            $response['error'] = 'Could not save user question';
            $this->set_response($response, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    function index_put() {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('user_question/user_question_model');
        
        $request = json_decode($this->input->raw_input_stream, true);
        
        $objUserQuestion = new User_question_object();
        $objUserQuestion->id = $request['id'];
        $objUserQuestion->user_id = $request['user_id'];
        $objUserQuestion->quiz_id = $request['quiz_id'];
        $objUserQuestion->question_id = $request['question_id'];
        $objUserQuestion->duration = $request['duration'];
        $objUserQuestion->option_answer = $request['option_answer'];
        $objUserQuestion->score = $request['score'];
        $objUserQuestion->is_correct = $request['is_correct'];

        $result = $this->user_question_model->update_user_question($objUserQuestion);

        if ($result != FALSE) {
            $response['code'] = REST_Controller::HTTP_OK;
            $response['records'] = $result;
            $this->set_response($response, REST_Controller::HTTP_OK);
        } else {
            $response['code'] = REST_Controller::HTTP_NOT_FOUND;
            $response['error'] = 'User question not found or could not be updated';
            $this->set_response($response, REST_Controller::HTTP_NOT_FOUND);
        }
    }

    function index_delete($id) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('user_question/user_question_model');
        
        if ($id != NULL) {
            $this->user_question_model->delete_user_question($id);
            $response['code'] = REST_Controller::HTTP_OK;
            $response['message'] = 'User question deleted successfully';
            $this->set_response($response, REST_Controller::HTTP_OK);
        } else {
            $response['code'] = REST_Controller::HTTP_BAD_REQUEST;
            $response['error'] = 'User question ID is required';
            $this->set_response($response, REST_Controller::HTTP_BAD_REQUEST);
        }
    }

}

?>
