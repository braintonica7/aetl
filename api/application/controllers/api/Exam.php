<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Exam extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    function index_get($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(false); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('exam/exam_model');
        $recordCount = $this->exam_model->get_exam_count();

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
                    if ($key == 'my_exams_only')
                    {
                        $objUser = $this->get_logged_user();
                        
                        $employeeId = $objUser->employee->id;
                        if ($objUser != NULL){
                            $filterString = " id in (select exam_id from teacher_exam where employee_id = $employeeId)";
                        }                        
                        break;
                    }
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
                if (CUtility::endsWith($filterString, " and "))                        
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
            $exams = $this->exam_model->get_paginated_exam($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($exams) > 0) {
                $response = $this->get_success_response($exams, 'Exam page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($exams);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($exams, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            $objExam = $this->exam_model->get_exam($id);
            if ($objExam == NULL) {
                $response = $this->get_failed_response(NULL, "Exam not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objExam, "Exam details..!");
                $this->set_output($response);
            }
        }
    }

    function get_subjects_for_exam_get() {
         $objUser = $this->require_jwt_auth(false); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }
        
         $request = $this->get_request();
         $examId = $request['exam_id'];
        $this->load->model('exam/exam_model');
        $subjects = $this->exam_model->get_subjects_for_exam($examId);
        if (count($subjects) > 0) {
            $response = $this->get_success_response($subjects, "List of subjects for exam");
            $this->set_output($response);
        } else {
            $response = $this->get_failed_response(NULL, "No subjects found...!");
            $this->set_output($response);
        }
    }
    function index_post() {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();

        $objExam = new Exam_object();
        $objExam->id = 0;
        $objExam->exam_name = $request['exam_name'];
        $objExam->max_score = $request['max_score'];

        $this->load->model('exam/exam_model');
        $objExam = $this->exam_model->add_exam($objExam);
        if ($objExam === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating exam...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objExam, "Exam created successfully...!");
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

        $this->load->model('exam/exam_model');
        $objExamOriginal = $this->exam_model->get_exam($id);

        $objExam = new Exam_object();
        $objExam->id = $objExamOriginal->id;
        $objExam->exam_name = $request['exam_name'];
        $objExam->max_score = $request['max_score'];
        $objExam = $this->exam_model->update_exam($objExam);
        if ($objExam === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating exam...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objExam, "Exam updated successfully...!");
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
            $this->load->model('exam/exam_model');
            $deleted = $this->exam_model->delete_exam($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "Exam deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "Exam deletion failed...!");
                $this->set_output($response);
            }
        }
    }

    public function get_exams_for_logged_user_get() {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $objUser = $this->get_logged_user();
        if ($objUser == NULL) {
            $response = $this->get_failed_response(NULL, "No user logged id....");
            $this->set_output($response);
        } else {
            if ($objUser->role_id == 5) {
                $classId = $objUser->scholar->class_id;
                $this->load->model('exam/exam_model');
                $exams = $this->exam_model->get_exams_for_class($classId);

                if (count($exams) > 0) {
                    $response = $this->get_success_response($exams, "List of exams");
                    $this->set_output($response);
                } else {
                    $response = $this->get_failed_response(NULL, "No Exams found...!");
                    $this->set_output($response);
                }
            }
        }
    }
    
    public function get_exams_for_logged_faculty_get() {
        // ✅ SECURE: Require JWT authentication for user access
        $objUser = $this->require_jwt_auth(false); // false = regular user access
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $objUser = $this->get_logged_user();
        if ($objUser == NULL) {
            $response = $this->get_failed_response(NULL, "No user logged id....");
            $this->set_output($response);
        } else {
            if ($objUser->role_id == 4) {
                $employeeId = $objUser->employee->id;
                $this->load->model('exam/exam_model');
                $exams = $this->exam_model->get_exams_for_faculty($employeeId);

                if (count($exams) > 0) {
                    $response = $this->get_success_response($exams, "List of exams");
                    $this->set_output($response);
                } else {
                    $response = $this->get_failed_response(NULL, "No Exams found...!");
                    $this->set_output($response); 
                }
            }
        }
    }

}
