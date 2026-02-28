<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Client
 *
 * @author Jawahar
 */
class Client extends API_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function populate_table_post() {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();
        $this->load->model('client/client_model');
        foreach ($request as $rec) {

            $objClient = new Client_object();
            $objClient->id = 0;
            $objClient->uuid = CUtility::get_UUID(openssl_random_pseudo_bytes(16));
            $objClient->name = $rec['name'];
            $objClient->address = $rec['address'];
            $objClient->city = $rec['city'];
            $objClient->state = $rec['state'];
            $objClient->contact_person = $rec['contact_person'];
            $objClient->contact_title = $rec['contact_title'];
            $objClient->contact_no = $rec['contact_no'];
            $objClient = $this->client_model->add_client($objClient);
        }
        echo "Done...";
    }

    function index_get($uuid = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('client/client_model');
        $recordCount = $this->client_model->get_client_count();
        
        //$header = "x-count-total: $recordCount";
        //header($header);

        if ($uuid == NULL) {
            //give multiple records...
            $pageSize = $this->input->get('pagesize', true);
            $page = $this->input->get('page', true);
            $sortBy = $this->input->get('sortby', true);
            $sortOrder = $this->input->get('sortorder', true);
            
            if (empty($pageSize))
                $pageSize = 10;
            
            if (empty($page))
                $page = 1;
            
            if (empty($sortBy))
                $sortBy = "id";
            
            if (empty($sortOrder))
                $sortOrder = 'desc';
            
            

            $totalNoOfPages = intdiv($recordCount, $pageSize);
            $remainder = $recordCount % $pageSize;
            if ($remainder > 0)
                $totalNoOfPages++;

            $offset = ($page - 1) * $pageSize;
            $clients = $this->client_model->get_paginated_client($offset, $pageSize, $sortBy, $sortOrder);
            if (count($clients) > 0) {
                $response = $this->get_success_response($clients, 'Client page...!');
                $response['total'] = $recordCount;
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($clients, 'Data not available...!');
                $this->set_output($response);
            }
        } else {
            //give a specific single record.            
            $objClient = $this->client_model->get_client_from_uuid($uuid);
            if ($objClient == NULL) {
                $response = $this->get_failed_response(NULL, "Client not found..!");
                $response['total'] = 1;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objClient, "Client details..!");
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
        $objClient = new Client_object();
        $objClient->id = 0;
        $objClient->uuid = CUtility::get_UUID(openssl_random_pseudo_bytes(16));
        $objClient->name = $request['name'];
        $objClient->address = $request['address'];
        $objClient->city = $request['city'];
        $objClient->state = $request['state'];
        $objClient->contact_person = $request['contact_person'];
        $objClient->contact_title = $request['contact_title'];
        $objClient->contact_no = $request['contact_no'];

        //These properties have already been set in the constructor
        /*
          $objClient->created;
          $objClient->updated;
         */

        $this->load->model('client/client_model');
        $objClient = $this->client_model->add_client($objClient);

        if ($objClient === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating client...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response(NULL, "Client created successfully...!");
            $this->set_output($response);
        }
    }

    function index_put($uuid) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $request = $this->get_request();

        $this->load->model('client/client_model');
        $objOriginalClient = $this->client_model->get_client_from_uuid($request['uuid']);

        $objClient = new Client_object();
        $objClient->id = $objOriginalClient->id;
        $objClient->uuid = $objOriginalClient->uuid;
        $objClient->name = request['name'];
        $objClient->address = request['address'];
        $objClient->city = request['city'];
        $objClient->state = request['state'];
        $objClient->contact_person = request['contact_person'];
        $objClient->contact_title = request['contact_title'];
        $objClient->contact_no = request['contact_no'];
        $objClient->created = $objOriginalClient->created;
        //These properties have already been set in the constructor
        /*
          $objClient->updated;
         */

        $objClient = $this->client_model->update_client($objClient);
        if ($objClient === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating client...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objClient, "Client updated successfully...!");
            $this->set_output($response);
        }
    }

    function index_delete($uuid = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        if ($uuid != NULL) {
            $this->load->model('client/client_model');
            $deleted = $this->client_model->delete_client_from_uuid($uuid);
            if ($deleted) {
                $response = $this->get_success_response($uuid, "Client deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response(NULL, "Error while deleting client...!");
                $this->set_output($response);
            }
        }
    }    
}
