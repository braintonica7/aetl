<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Quiz_question extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    function index_get($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('quiz_question/quiz_question_model');
        $recordCount = $this->quiz_question_model->get_quiz_question_count();

        if ($id == NULL) {
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
            $quiz_questions = $this->quiz_question_model->get_paginated_quiz_question($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($quiz_questions) > 0) {
                $response = $this->get_success_response($quiz_questions, 'quiz_question page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($quiz_questions);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($quiz_questions, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            $objquiz_question = $this->quiz_question_model->get_quiz_question($id);
            if ($objquiz_question == NULL) {
                $response = $this->get_failed_response(NULL, "quiz_question not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objquiz_question, "quiz_question details..!");
                $this->set_output($response);
            }
        }
    }

    function index_post() {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();

        $objquiz_question = new quiz_question_object();
        $objquiz_question->id = 0;
        $objquiz_question->quiz_id = $request['quiz_id'];
        $objquiz_question->question_id = $request['question_id'];
        $objquiz_question->question_order = $request['question_order'];

        $this->load->model('quiz_question/quiz_question_model');
        $objquiz_question = $this->quiz_question_model->add_quiz_question($objquiz_question);
        if ($objquiz_question === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating quiz_question...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objquiz_question, "quiz_question created successfully...!");
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

        $this->load->model('quiz_question/quiz_question_model');
        $objquiz_questionOriginal = $this->quiz_question_model->get_quiz_question($id);

        $objquiz_question = new quiz_question_object();
        $objquiz_question->id = $objquiz_questionOriginal->id;
        $objquiz_question->quiz_id = $request['quiz_id'];
        $objquiz_question->question_id = $request['question_id'];
        $objquiz_question->question_order = $request['question_order'];
        $objquiz_question = $this->quiz_question_model->update_quiz_question($objquiz_question);
        if ($objquiz_question === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating quiz_question...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objquiz_question, "quiz_question updated successfully...!");
            $this->set_output($response);
        }
    }

    function quiz_question_delete_post() {
		$request = $this->get_request();
		$id =  $request['id'];
		$this->load->model('quiz_question/quiz_question_model');
            $deleted = $this->quiz_question_model->delete_quiz_question($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "quiz_question deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "quiz_question deletion failed...!");
                $this->set_output($response);
            }

       
    }

    function get_questions_for_quiz_question_get() {

        $quiz_id = $this->input->get('quiz_id', true);

        $this->load->model('quiz_question/quiz_question_model');
        $objquestions = $this->quiz_question_model->get_all_questions_for_quiz($quiz_id);
        if ($objquestions === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating question...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objquestions, "question List!");
            $this->set_output($response);
        }
    }

}
