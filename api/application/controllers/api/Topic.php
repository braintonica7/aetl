<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Topic extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    function index_get($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('topic/topic_model');
        $recordCount = $this->topic_model->get_topic_count();

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
                    if ($key == 'my_topics_only')
                    {
                        $objUser = $this->get_logged_user();
                        
                        $employeeId = $objUser->employee->id;
                        if ($objUser != NULL){
                            $filterString = " id in (select topic_id from teacher_topic where employee_id = $employeeId)";
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
            $topics = $this->topic_model->get_paginated_topic($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($topics) > 0) {
                $response = $this->get_success_response($topics, 'Topic page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($topics);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($topics, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            $objTopic = $this->topic_model->get_topic($id);
            if ($objTopic == NULL) {
                $response = $this->get_failed_response(NULL, "Topic not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objTopic, "Topic details..!");
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

        $objTopic = new Topic_object();
        $objTopic->id = 0;
        $objTopic->topic_name = $request['topic_name'];
        $objTopic->chapter_id = $request['chapter_id'];
        $objTopic->subject_id = $request['subject_id'];

        $this->load->model('topic/topic_model');
        $objTopic = $this->topic_model->add_topic($objTopic);
        if ($objTopic === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating topic...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objTopic, "Topic created successfully...!");
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

        $this->load->model('topic/topic_model');
        $objTopicOriginal = $this->topic_model->get_topic($id);

        $objTopic = new Topic_object();
        $objTopic->id = $objTopicOriginal->id;
        $objTopic->topic_name = $request['topic_name'];
        $objTopic->chapter_id = $request['chapter_id'];
        $objTopic->subject_id = $request['subject_id'];
        $objTopic = $this->topic_model->update_topic($objTopic);
        if ($objTopic === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating topic...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objTopic, "Topic updated successfully...!");
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
            $this->load->model('topic/topic_model');
            $deleted = $this->topic_model->delete_topic($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "Topic deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "Topic deletion failed...!");
                $this->set_output($response);
            }
        }
    }

    public function get_topics_for_logged_user_get() {
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
                $this->load->model('topic/topic_model');
                $topics = $this->topic_model->get_topics_for_class($classId);

                if (count($topics) > 0) {
                    $response = $this->get_success_response($topics, "List of topics");
                    $this->set_output($response);
                } else {
                    $response = $this->get_failed_response(NULL, "No Topics found...!");
                    $this->set_output($response);
                }
            }
        }
    }
    
    public function get_topics_for_logged_faculty_get() {
        $objUser = $this->get_logged_user();
        if ($objUser == NULL) {
            $response = $this->get_failed_response(NULL, "No user logged id....");
            $this->set_output($response);
        } else {
            if ($objUser->role_id == 4) {
                $employeeId = $objUser->employee->id;
                $this->load->model('topic/topic_model');
                $topics = $this->topic_model->get_topics_for_faculty($employeeId);

                if (count($topics) > 0) {
                    $response = $this->get_success_response($topics, "List of topics");
                    $this->set_output($response);
                } else {
                    $response = $this->get_failed_response(NULL, "No Topics found...!");
                    $this->set_output($response); 
                }
            }
        }
    }

}
