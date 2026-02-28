<?php

class Role extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    function index_get($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('role/role_model');
        $recordCount = $this->role_model->get_role_count();

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
            $roles = $this->role_model->get_paginated_role($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($roles) > 0) {
                $response = $this->get_success_response($roles, 'Role page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($roles);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($roles, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            $objRole = $this->role_model->get_role($id);
            if ($objRole == NULL) {
                $response = $this->get_failed_response(NULL, "Role not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objRole, "Role details..!");
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

        $objRole = new Role_object();
        $objRole->id = 0;
        $objRole->role = $request['role'];

        $this->load->model('role/role_model');
        $objRole = $this->role_model->add_role($objRole);
        if ($objRole === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating role...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objRole, "Role created successfully...!");
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

        $this->load->model('role/role_model');
        $objRoleOriginal = $this->role_model->get_role($id);

        $objRole = new Role_object();
        $objRole->id = $objRoleOriginal['id'];
        $objRole->role = $request['role'];
        $objRole = $this->role_model->update_role($objRole);
        if ($objRole === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating role...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objRole, "Role updated successfully...!");
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
            $this->load->model('role/role_model');
            $deleted = $this->role_model->delete_role($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "Role deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "Role deletion failed...!");
                $this->set_output($response);
            }
        }
    }

}
