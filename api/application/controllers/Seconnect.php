<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Seconnect extends API_Controller {

     public function __constructor()
    {
        parent::__construct();
    }

    public function ping_get() {
        echo gmdate("Y-m-d\TH:i:s\Z");
     }

    public function index_get()
    {
        echo gmdate("Y-m-d\TH:i:s\Z");
    }
    function index_post()
    {
        $this->load->model('scan/scan_model');
        $request = $this->get_request();
        $objScan = new Scan_object();
        $objScan->id = 0;
        $objScan->data = json_encode($request);
        $document = $this->input->raw_input_stream;
        $objScan->document = $document;
        if(is_null($document)){
            $objScan = $this->scan_model->add_Scan($objScan);
        }else{
            $xmlDoc = simplexml_load_string($document);
            $doc_id = (string) $xmlDoc->DocumentInfo->DocumentID;
            if($doc_id){
                $obj = $this->scan_model->get_scan_by_doc_id($doc_id);
                if(is_null($obj))
                    $objScan = $this->scan_model->add_Scan($objScan);
                else
                    $objScan = $obj;
            }
            else 
                $objScan = $this->scan_model->add_Scan($objScan);
        }
        if (!is_null($request) &&  count($request) > 0) {
            $data =  $document;
            // First Save the Full XML  IN DB //
            $objScan->document = $data;
            $objScan = $this->scan_model->update_Scan($objScan);
            //Now get the data and fill Properties //

            $xmlDoc = simplexml_load_string($data);
            //echo 'The Document';
            //print_r($xmlDoc);
            $objScan->doc_id = (string) $xmlDoc->DocumentInfo->DocumentID;
            $objScan->doc_status = (string) $xmlDoc->DocumentInfo->DocumentStatus;
            $objScan->repair_order_id = (string) $xmlDoc->DocumentInfo->ReferenceInfo->RepairOrderID;
            $objScan->vin_no = (string) $xmlDoc->VehicleInfo->VINInfo->VIN->VINNum;
            $objScan->v_production_date = (string) $xmlDoc->VehicleInfo->VehicleDesc->ProductionDate;
            $objScan->v_model_year = (string) $xmlDoc->VehicleInfo->VehicleDesc->ModelYear;
            $objScan->v_model_description = (string) $xmlDoc->VehicleInfo->VehicleDesc->MakeDesc;
            $objScan->v_model_name = (string) $xmlDoc->VehicleInfo->VehicleDesc->ModelName;
            $objScan->v_model_name = (string) $xmlDoc->VehicleInfo->VehicleDesc->ModelName;
            $objScan->insurance_company = (string) $xmlDoc->AdminInfo->InsuranceCompany->Party->OrgInfo->CompanyName;
            $objScan->repair_facility = (string) $xmlDoc->AdminInfo->RepairFacility->Party->OrgInfo->CompanyName;
            $this->scan_model->update_Scan($objScan);
        }
        echo 'Success';
    }

}
