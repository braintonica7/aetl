<?php

class Section extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    function index_get($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('section/section_model');
        $recordCount = $this->section_model->get_section_count();

        if ($id == NULL) {
            //give multiple records...
            $pageSize = $this->input->get('pagesize', true);
            $page = $this->input->get('page', true);
            $sortBy = $this->input->get('sortby', true);
            $sortOrder = $this->input->get('sortorder', true);
            $objFilter = $this->input->get('filter', true);
            $multipleIds = $this->input->get('mid', true);

            $arr = (array) $objFilter;
            if (!$arr)
                $objFilter = NULL;

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
            $sections = $this->section_model->get_paginated_section($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($sections) > 0) {
                $response = $this->get_success_response($sections, 'Section page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($sections);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($sections, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            $objSection = $this->section_model->get_section($id);
            if ($objSection == NULL) {
                $response = $this->get_failed_response(NULL, "Section not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objSection, "Section details..!");
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

        $objSection = new Section_object();
        $objSection->id = 0;
        $objSection->class_id = $request['class_id'];
        $objSection->section = $request['section'];

        $this->load->model('section/section_model');
        $objSection = $this->section_model->add_section($objSection);
        if ($objSection === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating section...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objSection, "Section created successfully...!");
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

        $this->load->model('section/section_model');
        $objSectionOriginal = $this->section_model->get_section($id);

        $objSection = new Section_object();
        $objSection->id = $objSectionOriginal['id'];
        $objSection->class_id = $request['class_id'];
        $objSection->section = $request['section'];
        $objSection = $this->section_model->update_section($objSection);
        if ($objSection === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating section...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objSection, "Section updated successfully...!");
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
            $this->load->model('section/section_model');
            $deleted = $this->section_model->delete_section($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "Section deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "Section deletion failed...!");
                $this->set_output($response);
            }
        }
    }

}
