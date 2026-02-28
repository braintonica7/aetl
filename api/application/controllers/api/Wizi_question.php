<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Wizi_question extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    function index_get($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(false); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('wizi_quiz/wizi_question_model');
        
        // Check if admin wants to see invalid questions
        $showInvalid = $this->input->get('show_invalid', true);
        $recordCount = $this->wizi_question_model->get_wizi_question_count();

        if ($id == NULL) {
            //give multiple records...
            $pageSize = $this->input->get('pagesize', true);
            $page = $this->input->get('page', true);
            $sortBy = $this->input->get('sortby', true);
            $sortOrder = $this->input->get('sortorder', true);
            $objFilter = $this->input->get('filter', true);
            $multipleIds = $this->input->get('mid', true);
            $language = $this->input->get('language', true);

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
            $questions = $this->wizi_question_model->get_paginated_wizi_question($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($questions) > 0) {
                $response = $this->get_success_response($questions, 'WiZi question page...!');
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
            $objQuestion = $this->wizi_question_model->get_wizi_question($id);
            if ($objQuestion == NULL) {
                $response = $this->get_failed_response(NULL, "WiZi question not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objQuestion, "WiZi question details..!");
                $this->set_output($response);
            }
        }
    }

    function index_post() {
        // ✅ SECURE: Same pattern as Question - no auth requirement for POST
        // $objUser = $this->require_jwt_auth(true); // true = admin required
        // if (!$objUser) {
        //     return; // Error response already sent by require_jwt_auth()
        // }

        $request = $this->get_request();

        $objQuestion = new Wizi_question_object();
        $objQuestion->id = 0;
        $objQuestion->question_img_url = $request['question_img_url'];
        $objQuestion->has_multiple_answer = isset($request['has_multiple_answer']) ? $request['has_multiple_answer'] : 0;
        $objQuestion->duration = $request['duration'];
        $objQuestion->option_count = $request['option_count'];
        $objQuestion->exam_id = isset($request['exam_id']) ? $request['exam_id'] : null;
        $objQuestion->subject_id = isset($request['subject_id']) ? $request['subject_id'] : null;
        $objQuestion->chapter_name = isset($request['chapter_name']) ? $request['chapter_name'] : '';
        $objQuestion->chapter_id = isset($request['chapter_id']) ? $request['chapter_id'] : null;
        $objQuestion->level = $request['level'];
        $objQuestion->topic_id = isset($request['topic_id']) ? $request['topic_id'] : null;
        $objQuestion->correct_option = $request['correct_option'];
        $objQuestion->solution = isset($request['solution']) ? $request['solution'] : '';
        $objQuestion->question_text = isset($request['question_text']) ? $request['question_text'] : '';
        $objQuestion->ai_summary = isset($request['ai_summary']) ? $request['ai_summary'] : '';
        $objQuestion->summary_generated_at = isset($request['summary_generated_at']) ? $request['summary_generated_at'] : null;
        $objQuestion->summary_confidence = isset($request['summary_confidence']) ? $request['summary_confidence'] : 0.00;
        $objQuestion->option_a = isset($request['option_a']) ? $request['option_a'] : '';
        $objQuestion->option_b = isset($request['option_b']) ? $request['option_b'] : '';
        $objQuestion->option_c = isset($request['option_c']) ? $request['option_c'] : '';
        $objQuestion->option_d = isset($request['option_d']) ? $request['option_d'] : '';
        $objQuestion->subject_name = isset($request['subject_name']) ? $request['subject_name'] : '';
        $objQuestion->topic_name = isset($request['topic_name']) ? $request['topic_name'] : '';
        $objQuestion->difficulty = isset($request['difficulty']) ? $request['difficulty'] : '';
        $objQuestion->invalid_question = isset($request['invalid_question']) ? $request['invalid_question'] : 0;
        $objQuestion->year = isset($request['year']) ? $request['year'] : 2025;
        $objQuestion->question_type = isset($request['question_type']) ? $request['question_type'] : 'mock';
        $objQuestion->flag_reason = isset($request['flag_reason']) ? $request['flag_reason'] : '';

        $this->load->model('wizi_quiz/wizi_question_model');
        $objQuestion = $this->wizi_question_model->add_wizi_question($objQuestion);
        if ($objQuestion === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating WiZi question...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objQuestion, "WiZi question created successfully...!");
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

        $this->load->model('wizi_quiz/wizi_question_model');
        $objQuestionOriginal = $this->wizi_question_model->get_wizi_question($id);

        if ($objQuestionOriginal == NULL) {
            $response = $this->get_failed_response(NULL, "WiZi question not found..!");
            $this->set_output($response);
            return;
        }

        $objQuestion = new Wizi_question_object();
        $objQuestion->id = $objQuestionOriginal->id;
        $objQuestion->question_img_url = $request['question_img_url'];
        $objQuestion->has_multiple_answer = isset($request['has_multiple_answer']) ? $request['has_multiple_answer'] : 0;
        $objQuestion->duration = $request['duration'];
        $objQuestion->option_count = $request['option_count'];
        $objQuestion->exam_id = isset($request['exam_id']) ? $request['exam_id'] : $objQuestionOriginal->exam_id;
        $objQuestion->subject_id = isset($request['subject_id']) ? $request['subject_id'] : $objQuestionOriginal->subject_id;
        $objQuestion->chapter_name = isset($request['chapter_name']) ? $request['chapter_name'] : $objQuestionOriginal->chapter_name;
        $objQuestion->chapter_id = isset($request['chapter_id']) ? $request['chapter_id'] : $objQuestionOriginal->chapter_id;
        $objQuestion->level = $request['level'];
        $objQuestion->topic_id = isset($request['topic_id']) ? $request['topic_id'] : $objQuestionOriginal->topic_id;
        $objQuestion->correct_option = $request['correct_option'];
        $objQuestion->solution = isset($request['solution']) ? $request['solution'] : $objQuestionOriginal->solution;
        $objQuestion->question_text = isset($request['question_text']) ? $request['question_text'] : $objQuestionOriginal->question_text;
        $objQuestion->ai_summary = isset($request['ai_summary']) ? $request['ai_summary'] : $objQuestionOriginal->ai_summary;
        $objQuestion->summary_generated_at = isset($request['summary_generated_at']) ? $request['summary_generated_at'] : $objQuestionOriginal->summary_generated_at;
        $objQuestion->summary_confidence = isset($request['summary_confidence']) ? $request['summary_confidence'] : $objQuestionOriginal->summary_confidence;
        $objQuestion->option_a = isset($request['option_a']) ? $request['option_a'] : $objQuestionOriginal->option_a;
        $objQuestion->option_b = isset($request['option_b']) ? $request['option_b'] : $objQuestionOriginal->option_b;
        $objQuestion->option_c = isset($request['option_c']) ? $request['option_c'] : $objQuestionOriginal->option_c;
        $objQuestion->option_d = isset($request['option_d']) ? $request['option_d'] : $objQuestionOriginal->option_d;
        $objQuestion->subject_name = isset($request['subject_name']) ? $request['subject_name'] : $objQuestionOriginal->subject_name;
        $objQuestion->topic_name = isset($request['topic_name']) ? $request['topic_name'] : $objQuestionOriginal->topic_name;
        $objQuestion->difficulty = isset($request['difficulty']) ? $request['difficulty'] : $objQuestionOriginal->difficulty;
        $objQuestion->invalid_question = isset($request['invalid_question']) ? $request['invalid_question'] : $objQuestionOriginal->invalid_question;
        $objQuestion->year = isset($request['year']) ? $request['year'] : $objQuestionOriginal->year;
        $objQuestion->question_type = isset($request['question_type']) ? $request['question_type'] : $objQuestionOriginal->question_type;
        $objQuestion->flag_reason = isset($request['flag_reason']) ? $request['flag_reason'] : $objQuestionOriginal->flag_reason;

        $objQuestion = $this->wizi_question_model->update_wizi_question($objQuestion);
        if ($objQuestion === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating WiZi question...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objQuestion, "WiZi question updated successfully...!");
            $this->set_output($response);
        }
    }

    function solution_post($id) {
        $request = $this->get_request();

        $this->load->model('wizi_quiz/wizi_question_model');
        $objQuestionOriginal = $this->wizi_question_model->get_wizi_question($id);

        if ($objQuestionOriginal == NULL) {
            $response = $this->get_failed_response(NULL, "WiZi question not found..!");
            $this->set_output($response);
            return;
        }

        $objQuestion = new Wizi_question_object();
        $objQuestion->id = $objQuestionOriginal->id;
        $objQuestion->solution = $request['solution'];

        $objQuestion = $this->wizi_question_model->update_solution($objQuestion);
        if ($objQuestion === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating WiZi question solution...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objQuestion, "WiZi question solution updated successfully...!");
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
            $this->load->model('wizi_quiz/wizi_question_model');
            $deleted = $this->wizi_question_model->delete_wizi_question($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "WiZi question deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "WiZi question deletion failed...!");
                $this->set_output($response);
            }
        }
    }

    /**
     * Get all invalid/flagged questions for admin review
     * GET /api/wizi_question/invalid
     */
    function invalid_get() {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return;
        }

        $this->load->model('wizi_quiz/wizi_question_model');
        
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
        
        // Get invalid questions
        $filterString = "invalid_question = 1";
        $questions = $this->wizi_question_model->get_paginated_wizi_question($offset, $pageSize, $sortBy, $sortOrder, $filterString);
        
        // Get total count of invalid questions
        $sql = "SELECT COUNT(id) as cnt FROM wizi_question WHERE invalid_question = 1";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        $row = $statement->fetch();
        $totalCount = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;

        $response = $this->get_success_response($questions, 'Invalid WiZi questions');
        $response['total'] = $totalCount;
        $this->set_output($response);
    }

    /**
     * Toggle invalid_question flag
     * POST /api/wizi_question/toggle_invalid/:id
     */
    function toggle_invalid_post($id) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return;
        }

        $request = $this->get_request();
        
        $this->load->model('wizi_quiz/wizi_question_model');
        $objQuestion = $this->wizi_question_model->get_wizi_question($id);

        if ($objQuestion == NULL) {
            $response = $this->get_failed_response(NULL, "WiZi question not found..!");
            $this->set_output($response);
            return;
        }

        $invalid = isset($request['invalid_question']) ? $request['invalid_question'] : !$objQuestion->invalid_question;
        $flag_reason = isset($request['flag_reason']) ? $request['flag_reason'] : '';

        $sql = "UPDATE wizi_question SET invalid_question = ?, flag_reason = ? WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array($invalid, $flag_reason, $id));
        $statement = NULL;
        $pdo = NULL;

        if ($updated) {
            $objQuestion->invalid_question = $invalid;
            $objQuestion->flag_reason = $flag_reason;
            $response = $this->get_success_response($objQuestion, "WiZi question flag updated successfully...!");
            $this->set_output($response);
        } else {
            $response = $this->get_failed_response(NULL, "Error while updating flag...!");
            $this->set_output($response);
        }
    }
}
