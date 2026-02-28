<?php

class ClassGroup extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    function index_get($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('class_group/class_group_model');
        $recordCount = $this->class_group_model->get_class_group_count();

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
            else{
                $multipleIds = trim($multipleIds);
                if (CUtility::endsWith($multipleIds, ",")){
                    $multipleIds = substr($multipleIds,0, strlen($multipleIds) - 1);
                }
            }
            //$multipleIds = preg_replace("/[\n\r]/","",$multipleIds);

            $arr = (array) $objFilter;
            if (!$arr)
                $objFilter = NULL;

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
                    $filterString = "id in (". $multipleIds . ")";
                else
                    $filterString .= " and id in (" . $multipleIds . ")";
            }

            $totalNoOfPages = intdiv($recordCount, $pageSize);
            $remainder = $recordCount % $pageSize;
            if ($remainder > 0)
                $totalNoOfPages++;

            $offset = ($page - 1) * $pageSize;
            $class_groups = $this->class_group_model->get_paginated_class_group($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($class_groups) > 0) {
                $response = $this->get_success_response($class_groups, 'Class Group page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($class_groups);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($class_groups, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            $objClass_group = $this->class_group_model->get_class_group($id);
            if ($objClass_group == NULL) {
                $response = $this->get_failed_response(NULL, "Class Group not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objClass_group, "Class Group details..!");
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

        $objClass_group = new Class_group_object();
        $objClass_group->id = 0;
        $objClass_group->class_group = $request['class_group'];

        $this->load->model('class_group/class_group_model');
        $objClass_group = $this->class_group_model->add_class_group($objClass_group);
        if ($objClass_group === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating Class Group...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objClass_group, "Class Group created successfully...!");
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

        $this->load->model('class_group/class_group_model');
        $objClass_groupOriginal = $this->class_group_model->get_class_group($id);

        $objClass_group = new Class_group_object();
        $objClass_group->id = $objClass_groupOriginal['id'];
        $objClass_group->class_group = $request['class_group'];
        $objClass_group = $this->class_group_model->update_class_group($objClass_group);
        if ($objClass_group === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating class_group...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objClass_group, "Class Group updated successfully...!");
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
            $this->load->model('class_group/class_group_model');
            $deleted = $this->class_group_model->delete_class_group($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "Class Group deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "Class Group deletion failed...!");
                $this->set_output($response);
            }
        }
    }

}
