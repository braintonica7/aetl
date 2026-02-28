<?php

class Estimate extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    function prepare_get(){
        $this->load->model('scan/scan_model');
        $data = $this->scan_model->get_all_Scans();
        foreach ($data as $item) {
            $doc = $item->document;
            $xmlDoc = simplexml_load_string($doc);
            $item->insurance_company = (string) $xmlDoc->AdminInfo->InsuranceCompany->Party->OrgInfo->CompanyName;
            $item->repair_facility = (string) $xmlDoc->AdminInfo->RepairFacility->Party->OrgInfo->CompanyName;
            $this->scan_model->update_Scan($item);
        }
        $resData = new stdClass();
        $resData->total = count($data);
        $response = $this->get_success_response($resData, "Chapter details..!");
        $this->set_output($response);
    }

    function pending_get(){
        $this->load->model('scan/scan_model');
        $data = $this->scan_model->get_all_pending_scans();
        $response = $this->get_success_response($data, "Success");
        $this->set_output($response);
    }

    function submit_po_post(){
        $this->load->model('scan/scan_model');
        $request = $this->get_request();
        $document_id = $request['document_id'];
        if($document_id == NULL){
            $response = $this->get_failed_response(NULL, "Document Not Found");
            $this->set_output($response);
            return;
        }
        $data = $this->scan_model->get_scan_by_doc_id($document_id);

        if($data == NULL){
            $response = $this->get_failed_response(NULL, "Document Data Not Found");
            $this->set_output($response);
            return;
        }
        $data->status = "SUBMITTED";
        $data->error = "";
        $this->scan_model->update_scan_status($data);
        $response = $this->get_success_response(null, "Success");
        $this->set_output($response);
    }

    function submit_processed_post(){
        $this->load->model('scan/scan_model');
        $request = $this->get_request();
        $document_id = $request['document_id'];
        $status = $request['status'];
        $error = $request['error'];

        if($document_id == NULL){
            $response = $this->get_failed_response(NULL, "Document Not Found");
            $this->set_output($response);
            return;
        }
        $data = $this->scan_model->get_scan_by_doc_id($document_id);

        if($data == NULL){
            $response = $this->get_failed_response(NULL, "Document Not Found");
            $this->set_output($response);
            return;
        }
        if($status == "SUCCESS")
            $data->status = "PO CREATED";
        else
            $data->status = "ERROR";

        $data->error = $error;
        $this->scan_model->update_scan_status($data);
        $response = $this->get_success_response(null, "Success");
        $this->set_output($response);
    }

    function index_get($id = NULL) {
        $this->load->model('scan/scan_model');
        $recordCount = $this->scan_model->get_scan_count();

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
                $page = 0;

            if (empty($sortBy))
                $sortBy = "id";

            if (empty($sortOrder))
                $sortOrder = 'desc';

            if (empty($objFilter))
                $objFilter = NULL;
            else{

                if(!empty($objFilter['limit']))
                    $pageSize = $objFilter['limit'];

                if(!empty($objFilter['offset'])){
                    $page = $objFilter['offset'];
                    if($page != 0){
                        $page = ($page/$pageSize);
                    }
                }
            }

            $filterString = "";
           
            $totalNoOfPages = intdiv($recordCount, $pageSize);
            $remainder = $recordCount % $pageSize;
            if ($remainder > 0)
                $totalNoOfPages++;

            $scans = $this->scan_model->get_paginated_scan($page, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($scans) > 0) {
                $response = $this->get_success_response($scans, 'Chapter page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($scans);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($scans, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            $objChapter = $this->scan_model->get_scan($id);
            if ($objChapter == NULL) {
                $response = $this->get_failed_response(NULL, "Chapter not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objChapter, "Chapter details..!");
                $this->set_output($response);
            }
        }
    }
}
