<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Subject extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    function index_get($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(false); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('subject/subject_model');
        $recordCount = $this->subject_model->get_subject_count();

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
                    if ($key == 'my_subjects_only')
                    {
                        $objUser = $this->get_logged_user();
                        
                        $employeeId = $objUser->employee->id;
                        if ($objUser != NULL){
                            $filterString = " id in (select subject_id from teacher_subject where employee_id = $employeeId)";
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
            $subjects = $this->subject_model->get_paginated_subject($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($subjects) > 0) {
                $response = $this->get_success_response($subjects, 'Subject page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($subjects);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($subjects, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            $objSubject = $this->subject_model->get_subject($id);
            if ($objSubject == NULL) {
                $response = $this->get_failed_response(NULL, "Subject not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objSubject, "Subject details..!");
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

        $objSubject = new Subject_object();
        $objSubject->id = 0;
        $objSubject->subject = $request['subject'];

        $this->load->model('subject/subject_model');
        $objSubject = $this->subject_model->add_subject($objSubject);
        if ($objSubject === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating subject...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objSubject, "Subject created successfully...!");
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

        $this->load->model('subject/subject_model');
        $objSubjectOriginal = $this->subject_model->get_subject($id);

        $objSubject = new Subject_object();
        $objSubject->id = $objSubjectOriginal->id;
        $objSubject->subject = $request['subject'];
        $objSubject = $this->subject_model->update_subject($objSubject);
        if ($objSubject === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating subject...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objSubject, "Subject updated successfully...!");
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
            $this->load->model('subject/subject_model');
            $deleted = $this->subject_model->delete_subject($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "Subject deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "Subject deletion failed...!");
                $this->set_output($response);
            }
        }
    }

    public function get_subjects_for_logged_user_get() {
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
                $this->load->model('subject/subject_model');
                $subjects = $this->subject_model->get_subjects_for_class($classId);

                if (count($subjects) > 0) {
                    $response = $this->get_success_response($subjects, "List of subjects");
                    $this->set_output($response);
                } else {
                    $response = $this->get_failed_response(NULL, "No Subjects found...!");
                    $this->set_output($response);
                }
            }
        }
    }
    
    public function get_subjects_for_logged_faculty_get() {
        $objUser = $this->get_logged_user();
        if ($objUser == NULL) {
            $response = $this->get_failed_response(NULL, "No user logged id....");
            $this->set_output($response);
        } else {
            if ($objUser->role_id == 4) {
                $employeeId = $objUser->employee->id;
                $this->load->model('subject/subject_model');
                $subjects = $this->subject_model->get_subjects_for_faculty($employeeId);

                if (count($subjects) > 0) {
                    $response = $this->get_success_response($subjects, "List of subjects");
                    $this->set_output($response);
                } else {
                    $response = $this->get_failed_response(NULL, "No Subjects found...!");
                    $this->set_output($response); 
                }
            }
        }
    }

}
