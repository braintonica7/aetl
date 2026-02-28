<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Account extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    function index_get($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('account/account_model');
        $recordCount = $this->account_model->get_account_count();

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
            $accounts = $this->account_model->get_paginated_account($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($accounts) > 0) {
                $response = $this->get_success_response($accounts, 'Account page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($accounts);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($accounts, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            $objAccount = $this->account_model->get_account($id);
            if ($objAccount == NULL) {
                $response = $this->get_failed_response(NULL, "Account not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objAccount, "Account details..!");
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

        $objAccount = new Account_object();
        $objAccount->id = 0;
        $objAccount->username = $request['username'];
        $objAccount->password = $request['password'];
        $objAccount->token = $request['token'];
        $objAccount->display_name = $request['display_name'];
        $objAccount->plan = $request['plan'];
        $objAccount->is_active = $request['is_active'];

        $this->load->model('account/account_model');
        $objAccount = $this->account_model->add_account($objAccount);
        if ($objAccount === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating account...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objAccount, "Account created successfully...!");
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

        $this->load->model('account/account_model');
        $objAccountOriginal = $this->account_model->get_account($id);

        $objAccount = new Account_object();
        $objAccount->id = $objAccountOriginal->id;
        $objAccount->username = $request['username'];
        $objAccount->password = $request['password'];
        $objAccount->token = $request['token'];
        $objAccount->display_name = $request['display_name'];
        $objAccount->plan = $request['plan'];
        $objAccount->is_active = $request['is_active'];
        $objAccount = $this->account_model->update_account($objAccount);
        if ($objAccount === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating account...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objAccount, "Account updated successfully...!");
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
            $this->load->model('account/account_model');
            $deleted = $this->account_model->delete_account($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "Account deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "Account deletion failed...!");
                $this->set_output($response);
            }
        }
    }
    
    function get_account_token_post(){
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();
        $accountId = $request['accountId'];
        
        $this->load->model('account/account_model');
        $objAccount = $this->account_model->get_account($accountId);
        if ($objAccount == NULL){                                    
            $response = $this->get_failed_response(NULL, "Account not found...");
            $this->set_output($response);
        }else{
            $objAccount->username = "";
            $objAccount->password = "";                        
            $objAccount->plan = "";            
            $response = $this->get_success_response($objAccount, "Account not found...");
            $this->set_output($response);
        }
    }

}
