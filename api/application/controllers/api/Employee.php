<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Employee extends API_Controller {

    public function __constructor() {
        parent::__construct();
    }

    function index_get($id = NULL) {
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $this->load->model('employee/employee_model');
        $recordCount = $this->employee_model->get_employee_count();

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
            $employees = $this->employee_model->get_paginated_employee($offset, $pageSize, $sortBy, $sortOrder, $filterString);
            if (count($employees) > 0) {
                $response = $this->get_success_response($employees, 'Employee page...!');
                if ($filterString == '')
                    $response['total'] = $recordCount;
                else
                    $response['total'] = count($employees);
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($employees, 'Data not available...!');
                $response['total'] = 0;
                $this->set_output($response);
            }
        } else {
            //give a specific single record.
            $objEmployee = $this->employee_model->get_employee($id);
            if ($objEmployee == NULL) {
                $response = $this->get_failed_response(NULL, "Employee not found..!");
                $response['total'] = 0;
                $this->set_output($response);
            } else {
                $response = $this->get_success_response($objEmployee, "Employee details..!");
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

        $objEmployee = new Employee_object();
        $objEmployee->id = 0;
        $objEmployee->name = $request['name'];
        $objEmployee->mobile_no = $request['mobile_no'];
        $objEmployee->faculty_type = $request['faculty_type'];
        $objEmployee->designation = $request['designation'];
        $objEmployee->is_active = $request['is_active'];

        $this->load->model('employee/employee_model');
        $objEmployee = $this->employee_model->add_employee($objEmployee);
        if ($objEmployee === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while creating employee...!");
            $this->set_output($response);
        } else {
            $response = $this->get_success_response($objEmployee, "Employee created successfully...!");
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

        $this->load->model('employee/employee_model');
        $objEmployeeOriginal = $this->employee_model->get_employee($id);

        $objEmployee = new Employee_object();
        $objEmployee->id = $objEmployeeOriginal->id;
        $objEmployee->name = $request['name'];
        $objEmployee->mobile_no = $request['mobile_no'];
        $objEmployee->faculty_type = $request['faculty_type'];
        $objEmployee->designation = $request['designation'];
        $objEmployee->is_active = $request['is_active'];
        $objEmployee = $this->employee_model->update_employee($objEmployee);
        if ($objEmployee === FALSE) {
            $response = $this->get_failed_response(NULL, "Error while updating employee...!");
            $this->set_output($response);
        } else {
            // make updations in the user table            
            $this->load->model('user/user_model');
            $objUserOriginal = $this->user_model->get_user_from_username($objEmployeeOriginal->mobile_no);
            if ($objUserOriginal != NULL) {
                $objUser = new User_object();
                $objUser->id = $objUserOriginal->id;
                $objUser->username = $objEmployee->mobile_no;
                $objUser->password = $objEmployee->mobile_no;
                $objUser->display_name = $objEmployee->name;
                if ($objEmployee->faculty_type == 'Office Executive')
                    $objUser->role_id = 2;
                else if ($objEmployee->faculty_type == 'Principal')
                    $objUser->role_id = 3;
                else if ($objEmployee->faculty_type == 'Teacher')
                    $objUser->role_id = 4;   
                $objUser->allow_login = $objEmployee->is_active;
                if ($objEmployeeOriginal == NULL)
                    $objUser->last_login = NULL;
                else
                    $objUser->last_login = DateTime::createFromFormat("Y-m-d H:i:s", $objUserOriginal->last_login);
                $objUser->reference_id = $objEmployee->id;

                $this->user_model->update_user($objUser);
            }else{
                $objUser = new User_object();
                $objUser->id = 0;
                $objUser->username = $objEmployee->mobile_no;
                $objUser->password = $objEmployee->mobile_no;
                $objUser->display_name = $objEmployee->name;
                if ($objEmployee->faculty_type == 'Office Executive')
                    $objUser->role_id = 2;
                else if ($objEmployee->faculty_type == 'Principal')
                    $objUser->role_id = 3;
                else if ($objEmployee->faculty_type == 'Teacher')
                    $objUser->role_id = 4;   
                $objUser->allow_login = $objEmployee->is_active;
                $objUser->last_login = NULL;
                $objUser->reference_id = $objEmployee->id;
                $this->user_model->add_user($objUser);
            }
            $response = $this->get_success_response($objEmployee, "Employee updated successfully...!");
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
            $this->load->model('employee/employee_model');
            $deleted = $this->employee_model->delete_employee($id);
            if ($deleted) {
                $response = $this->get_success_response($id, "Employee deleted successfully...!");
                $this->set_output($response);
            } else {
                $response = $this->get_failed_response($id, "Employee deletion failed...!");
                $this->set_output($response);
            }
        }
    }
    
    function create_employee_users_from_employee_table_post(){
        // ✅ SECURE: Require JWT authentication with admin privileges
        $objUser = $this->require_jwt_auth(true); // true = admin required
        if (!$objUser) {
            return; // Error response already sent by require_jwt_auth()
        }

        $pdo = CDatabase::getPdo();
        $sql = "delete from user where role_id in (2,3,4)";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        $statement = NULL;
        
        $sql = "select * from employee order by id";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        
        $this->load->model('user/user_model');
        while ($row = $statement->fetch()){
            $objEmployee = new Employee_object();
            $objEmployee->id = $row['id'];
            $objEmployee->name = $row['name'];
            $objEmployee->mobile_no = $row['mobile_no'];
            $objEmployee->faculty_type = $row['faculty_type'];
            $objEmployee->is_active = $row['is_active'] == 1;
            
            
            $objUser = new User_object();
            $objUser->id = 0;
            $objUser->username = $objEmployee->mobile_no;
            $objUser->password = $objEmployee->mobile_no;
            $objUser->display_name = $objEmployee->name;
            if ($objEmployee->faculty_type === 'Teacher')
                $objUser->role_id = 4;  
            else if ($objEmployee->faculty_type === 'Principal')
                $objUser->role_id = 3;
            else if ($objEmployee->faculty_type === 'Office Executive')
                $objUser->role_id = 2;
            
            $objUser->allow_login = 1;
            $objUser->reference_id = $objEmployee->id;
            $objUser->last_login = NULL;
            
            //print_r($objUser);
                                   
            $this->user_model->add_user($objUser);
        }
        $statement = NULL;
        $pdo = NULL;
        echo "done";
    }

}
