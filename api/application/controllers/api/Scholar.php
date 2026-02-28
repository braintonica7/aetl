<?php

class Scholar extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    public function put_scholar_records_from_scholar_dump_to_scholar_table_post() {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        //INSERT INTO `user`(`id`, `username`, `password`, `display_name`, `role_id`, `reference_id`, `allow_login`, `token`, `last_login`) VALUES (1,'team','together@123','Super Administrator',1,0,1,null, null)        
        $pdo = CDatabase::getPdo();
        $sql = "delete from scholar where id >= 1";        
        $statement = $pdo->prepare($sql);
        $statement->execute();
        $statement = NULL;
        
        $sql = "delete from user where role_id = 5";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        $statement = NULL;
        
        
        $sql = "select * from scholar_dump";
        $statement = $pdo->prepare($sql);
        $statement->execute();

        $this->load->model('scholar/scholar_model');
        $this->load->model('user/user_model');
        
        while ($row = $statement->fetch()) {            
            $className = $row['class_name'];
            $section = $row['section'];
            $classId = 0;
            $sectionId = 0;

            $sql = "select * from genere where class_name = ?";
            $statementClass = $pdo->prepare($sql);
            $statementClass->execute(array($className));
            if ($classRow = $statementClass->fetch())
                $classId = $classRow['id'];
            $statementClass = NULL;            
            if ($classId != 0) {
                $sql = "select * from section where class_id = ? and section = ?";
                $statementSection = $pdo->prepare($sql);
                $statementSection->execute(array($classId,$section));
                if ($sectionRow = $statementSection->fetch())
                    $sectionId = $sectionRow['id'];
                $statementSection = NULL;

                if ($sectionId != 0) {
                    $objScholar = new Scholar_object();
                    $objScholar->id = 0;
                    $objScholar->scholar_no = $row['scholar_no'];
                    $objScholar->session = '2020-2021';
                    $objScholar->name = $row['scholar_name'];
                    $objScholar->dob = DateTime::createFromFormat("Y-m-d", $row['dob']);
                    $objScholar->gender = $row['gender'];
                    $objScholar->father = $row['father'];
                    $objScholar->mother = $row['mother'];
                    $objScholar->alert_mobile_no = $row['mobile_no'];
                    $objScholar->class_id = $classId;
                    $objScholar->section_id = $sectionId;
                    $objScholar->is_active = 1;
                    
                    $this->scholar_model->add_scholar($objScholar);

                    //To Do ->Insert new record in the user table for this scholar.
                    $objUser = new User_object();
                    $objUser->id = 0;
                    $objUser->username = $objScholar->scholar_no;
                    $objUser->password = $objScholar->alert_mobile_no;
                    $objUser->display_name = $objScholar->name;
                    $objUser->role_id = 5;
                    $objUser->allow_login = $objScholar->is_active;
                    $objUser->last_login = NULL;
                    $objUser->reference_id = $objScholar->id;

                    $this->user_model->add_user($objUser);
                }
            }else{
                echo "Scholar Not imported " . $row['scholar_no'] . PHP_EOL;
            }
        }
        echo "Done...";
    }

    public function generate_scholar_login_records_post() {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $pdo = CDatabase::getPdo();
        $sql = "delete from user where role_id = 5";
          $statement = $pdo->prepare($sql);
          $statement->execute();

          /*
          $sql = "insert into user values(1,'team','together@123','Super Administrator',1,0,1,null, null)";
          $statement = $pdo->prepare($sql);
          $statement->execute();
          */

        $sql = "select id, name, scholar_no, alert_mobile_no from scholar where scholar_no not in(select scholar_no from scholar group by scholar_no having count(scholar_no) > 1 order by scholar_no)";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        $CI = & get_instance();
        $CI->load->model('user/user_model');
        while ($row = $statement->fetch()) {
            $objUser = new User_object();
            $objUser->id = 0;
            $objUser->username = $row['scholar_no'];
            $objUser->password = $row['alert_mobile_no'];
            $objUser->display_name = $row['name'];
            $objUser->role_id = 5;
            $objUser->reference_id = $row['id'];
            $objUser->allow_login = 1;
            $objUser->last_login = NULL;
            //print_r($objUser); 
            $CI->user_model->add_user($objUser);
        }
        $statement = NULL;
        $pdo = NULL;
        echo "done";
    }

    function index_get($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('scholar/scholar_model');
        $recordCount = $this->scholar_model->get_scholar_count();

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
            $scholars = $this->scholar_model->get_paginated_scholar($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($scholars) > 0) {
                $response = $this->get_success_response($scholars, 'Scholar page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($scholars);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($scholars, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            $objScholar = $this->scholar_model->get_scholar($id);
            if ($objScholar == NULL) {
                $response = $this->get_failed_response(NULL, "Scholar not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objScholar, "Scholar details..!");
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

        $objScholar = new Scholar_object();
        $objScholar->id = 0;
        $objScholar->scholar_no = $request['scholar_no'];
        $objScholar->session = $request['session'];
        $objScholar->name = $request['name'];
        $objScholar->dob = DateTime::createFromFormat('Y-m-d', $request['dob']);
        $objScholar->gender = $request['gender'];
        $objScholar->father = $request['father'];
        $objScholar->mother = $request['mother'];
        $objScholar->alert_mobile_no = $request['alert_mobile_no'];
        $objScholar->class_id = $request['class_id'];
        $objScholar->section_id = $request['section_id'];
        $objScholar->is_active = $request['is_active'];

        $this->load->model('scholar/scholar_model');
        $objScholar = $this->scholar_model->add_scholar($objScholar);
        if ($objScholar === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating scholar...!");
            $this->set_output($response);
        } else {
            //To Do ->Insert new record in the user table for this scholar.
            $objUser = new User_object();
            $objUser->id = 0;
            $objUser->username = $objScholar->scholar_no;
            $objUser->password = $objScholar->alert_mobile_no;
            $objUser->display_name = $objScholar->name;
            $objUser->role_id = 5;
            $objUser->allow_login = $objScholar->is_active;
            $objUser->last_login = NULL;
            $objUser->reference_id = $objScholar->id;

            $this->load->model('user/user_model');
            $this->user_model->add_user($objUser);

            $response = $this->get_success_response($objScholar, "Scholar created successfully...!");
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

        $this->load->model('scholar/scholar_model');
        $objScholarOriginal = $this->scholar_model->get_scholar($id);

        $objScholar = new Scholar_object();
        $objScholar->id = $objScholarOriginal->id;
        $objScholar->scholar_no = $request['scholar_no'];
        $objScholar->session = $request['session'];
        $objScholar->name = $request['name'];
        $objScholar->dob = DateTime::createFromFormat('Y-m-d', $request['dob']);
        $objScholar->gender = $request['gender'];
        $objScholar->father = $request['father'];
        $objScholar->mother = $request['mother'];
        $objScholar->alert_mobile_no = $request['alert_mobile_no'];
        $objScholar->class_id = $request['class_id'];
        $objScholar->section_id = $request['section_id'];
        $objScholar->is_active = $request['is_active'];

        $objScholar = $this->scholar_model->update_scholar($objScholar);
        if ($objScholar === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating scholar...!");
            $this->set_output($response);
        } else {

            // make updations in the user table            
            $this->load->model('user/user_model');
            $objUserOriginal = $this->user_model->get_user_from_username($objScholarOriginal->scholar_no);
            if ($objUserOriginal != NULL) {
                $objUser = new User_object();
                $objUser->id = $objUserOriginal->id;
                $objUser->username = $objScholar->scholar_no;
                $objUser->password = $objScholar->alert_mobile_no;
                $objUser->display_name = $objScholar->name;
                $objUser->role_id = 5;
                $objUser->allow_login = $objScholar->is_active;
                if ($objUserOriginal->last_login == NULL)
                    $objUser->last_login = NULL;
                else
                    $objUser->last_login = DateTime::createFromFormat("Y-m-d H:i:s", $objUserOriginal->last_login);
                $objUser->reference_id = $objScholar->id;

                $this->user_model->update_user($objUser);
            } else {
                //create a new entry in the table user
                $objUser = new User_object();
                $objUser->id = 0;
                $objUser->username = $objScholar->scholar_no;
                $objUser->password = $objScholar->alert_mobile_no;
                $objUser->display_name = $objScholar->name;
                $objUser->role_id = 5;
                $objUser->allow_login = $objScholar->is_active;
                $objUser->last_login = NULL;
                $objUser->reference_id = $objScholar->id;

                $this->load->model('user/user_model');
                $this->user_model->add_user($objUser);
            }
            $response = $this->get_success_response($objScholar, "Scholar updated successfully...!");
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
            $this->load->model('scholar/scholar_model');
            $deleted = $this->scholar_model->delete_scholar($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "Scholar deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "Scholar deletion failed...!");
                $this->set_output($response);
            }
        }
    }
    
    function get_scholars_for_assignment_post(){
        $request = $this->get_request();
        $this->load->model('scholar/scholar_model');
        $submitStatus = $request['submit_status'];
        $classId = $request['class_id'];
        
        $scholars = NULL;
        if ($submitStatus === 'submitted'){
            $scholars = $this->scholar_model->get_scholars_who_submitted_assignment($request['assignment_id'], $classId);
        }else if ($submitStatus === 'not submitted'){
            $scholars = $this->scholar_model->get_scholars_who_didnt_submitted_assignment($request['assignment_id'],  $classId);
        }
        
        if ($scholars == NULL){
            $response = $this->get_failed_response(NULL,"No Data found...");
            $this->set_output($response);
        }else if (count($scholars) > 0){
            $response = $this->get_success_response($scholars,"List of scholars");
            $this->set_output($response);
        }        
    }

}
