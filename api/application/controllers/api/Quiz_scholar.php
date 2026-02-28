<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Quiz_scholar extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    function index_get($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('quiz_scholar/quiz_scholar_model');
        $recordCount = $this->quiz_scholar_model->get_quiz_scholar_count();

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
            $quiz_scholars = $this->quiz_scholar_model->get_paginated_quiz_scholar($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($quiz_scholars) > 0) {
                $response = $this->get_success_response($quiz_scholars, 'quiz_scholar page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($quiz_scholars);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($quiz_scholars, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            $objquiz_scholar = $this->quiz_scholar_model->get_quiz_scholar($id);
            if ($objquiz_scholar == NULL) {
                $response = $this->get_failed_response(NULL, "quiz_scholar not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objquiz_scholar, "quiz_scholar details..!");
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

        $objquiz_scholar = new quiz_scholar_object();
        $objquiz_scholar->id = 0;
        $objquiz_scholar->quiz_id = $request['quiz_id'];
        $objquiz_scholar->scholar_id = $request['scholar_id'];
        $objquiz_scholar->scholar_order = $request['scholar_order'];

        $this->load->model('quiz_scholar/quiz_scholar_model');
        $objquiz_scholar = $this->quiz_scholar_model->add_quiz_scholar($objquiz_scholar);
        if ($objquiz_scholar === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating quiz_scholar...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objquiz_scholar, "quiz_scholar created successfully...!");
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

        $this->load->model('quiz_scholar/quiz_scholar_model');
        $objquiz_scholarOriginal = $this->quiz_scholar_model->get_quiz_scholar($id);

        $objquiz_scholar = new quiz_scholar_object();
        $objquiz_scholar->id = $objquiz_scholarOriginal->id;
        $objquiz_scholar->quiz_id = $request['quiz_id'];
        $objquiz_scholar->scholar_id = $request['scholar_id'];
        $objquiz_scholar->scholar_order = $request['scholar_order'];
        $objquiz_scholar = $this->quiz_scholar_model->update_quiz_scholar($objquiz_scholar);
        if ($objquiz_scholar === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating quiz_scholar...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objquiz_scholar, "quiz_scholar updated successfully...!");
            $this->set_output($response);
        }
    }

     function quiz_scholar_delete_post() {
        $request = $this->get_request();
		$id =  $request['id'];
		$this->load->model('quiz_scholar/quiz_scholar_model');
            $deleted = $this->quiz_scholar_model->delete_quiz_scholar($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "quiz_scholar deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "quiz_scholar deletion failed...!");
                $this->set_output($response);
            }
    }

    function get_scholars_for_quiz_scholar_get() {

        $quiz_id = $this->input->get('quiz_id', true);

        $this->load->model('quiz_scholar/quiz_scholar_model');
        $objscholars = $this->quiz_scholar_model->get_all_scholars_for_quiz($quiz_id);
        if ($objscholars === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating scholar...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objscholars, "scholar List!");
            $this->set_output($response);
        }
    }

}
