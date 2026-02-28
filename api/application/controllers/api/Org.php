<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Org extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    function index_get($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('org/org_model');
        $recordCount = $this->org_model->get_org_count();

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
            $orgs = $this->org_model->get_paginated_org($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($orgs) > 0) {
                $response = $this->get_success_response($orgs, 'Org page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($orgs);
                $this->set_output($response);
            } else {                
                $response = $this->get_success_response($orgs, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            $objOrg = $this->org_model->get_org($id);
            if ($objOrg == NULL) {
                $response = $this->get_failed_response(NULL, "Org not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objOrg, "Org details..!");
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

        $objOrg = new Org_object();
        $objOrg->id = 0;
        $objOrg->name = $request['name'];
        $objOrg->address = $request['address'];
        $objOrg->logo_url = $request['logo_url'];

        $this->load->model('org/org_model');
        $objOrg = $this->org_model->add_org($objOrg);
        if ($objOrg === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating org...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objOrg, "Org created successfully...!");
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

        $this->load->model('org/org_model');
        $objOrgOriginal = $this->org_model->get_org($id);

        $objOrg = new Org_object();
        $objOrg->id = $objOrgOriginal->id;
        $objOrg->name = $request['name'];
        $objOrg->address = $request['address'];
        $objOrg->logo_url = $request['logo_url'];
        $objOrg = $this->org_model->update_org($objOrg);
        if ($objOrg === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating org...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objOrg, "Org updated successfully...!");
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
            $this->load->model('org/org_model');
            $deleted = $this->org_model->delete_org($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "Org deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "Org deletion failed...!");
                $this->set_output($response);
            }
        }
    }

}
