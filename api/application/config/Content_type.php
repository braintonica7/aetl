<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Content_type extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    function index_get($id = NULL) {
        $this->load->model('content_type/content_type_model');
        $recordCount = $this->content_type_model->get_content_type_count();

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
            $content_types = $this->content_type_model->get_paginated_content_type($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($content_types) > 0) {
                $response = $this->get_success_response($content_types, 'Content_type page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($content_types);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($content_types, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            $objContent_type = $this->content_type_model->get_content_type($id);
            if ($objContent_type == NULL) {
                $response = $this->get_failed_response(NULL, "Content_type not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objContent_type, "Content_type details..!");
                $this->set_output($response);
            }
        }
    }

    function index_post() {
        $request = $this->get_request();

        $objContent_type = new Content_type_object();
        $objContent_type->id = 0;
        $objContent_type->content_type_name = $request['content_type_name'];
        $objContent_type->is_active = $request['is_active'];
        $objContent_type->auto_approve = $request['auto_approve'];

        $this->load->model('content_type/content_type_model');
        $objContent_type = $this->content_type_model->add_content_type($objContent_type);
        if ($objContent_type === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating content_type...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objContent_type, "Content_type created successfully...!");
            $this->set_output($response);
        }
    }

    function index_put($id) {
        $request = $this->get_request();

        $this->load->model('content_type/content_type_model');
        $objContent_typeOriginal = $this->content_type_model->get_content_type($id);

        $objContent_type = new Content_type_object();
        $objContent_type->id = $objContent_typeOriginal->id;
        $objContent_type->content_type_name = $request['content_type_name'];
        $objContent_type->is_active = $request['is_active'];
        $objContent_type->auto_approve = $request['auto_approve'];
        $objContent_type = $this->content_type_model->update_content_type($objContent_type);
        if ($objContent_type === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating content_type...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objContent_type, "Content_type updated successfully...!");
            $this->set_output($response);
        }
    }

    function index_delete($id = NULL) {
        if ($id != NULL) {
            $this->load->model('content_type/content_type_model');
            $deleted = $this->content_type_model->delete_content_type($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "Content_type deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "Content_type deletion failed...!");
                $this->set_output($response);
            }
        }
    }

}
