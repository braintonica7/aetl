<?php

class Employee_model extends CI_Model {
 
    public function get_employee($id) {
        $objEmployee = NULL;
        $sql = "select * from employee where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objEmployee = new Employee_object();
            $objEmployee->id = $row['id'];
            $objEmployee->name = $row['name'];
            $objEmployee->mobile_no = $row['mobile_no'];
            $objEmployee->faculty_type = $row['faculty_type'];
            $objEmployee->designation = $row['designation'];
            $objEmployee->is_active = $row['is_active'] == 1;
        }
        $statement = NULL;
        $pdo = NULL;
        return $objEmployee;
    }

    public function get_all_employees() {
        $records = array();

        $sql = "select * from employee";
        $sql = "select * from employee where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objEmployee = new Employee_object();
            $objEmployee->id = $row['id'];
            $objEmployee->name = $row['name'];
            $objEmployee->mobile_no = $row['mobile_no'];
            $objEmployee->faculty_type = $row['faculty_type'];
            $objEmployee->designation = $row['designation'];
            $objEmployee->is_active = $row['is_active'] == 1;

            $records[] = $objEmployee;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_employee($objEmployee) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from employee";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objEmployee->id = $row['mvalue'];
        else
            $objEmployee->id = 0;
        $objEmployee->id = $objEmployee->id + 1;
        $sql = "insert into employee values (?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objEmployee->id,
            $objEmployee->name,
            $objEmployee->mobile_no,
            $objEmployee->faculty_type,
            $objEmployee->designation,
            $objEmployee->is_active
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted) {
            //create record in the table user
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
            
            $CI = & get_instance();
            $CI->load->model('user/user_model');
            $objUser = $CI->user_model->add_user($objUser);
            
            return $objEmployee;
        }
        return FALSE;
    }

    public function update_employee($objEmployee) {
        $sql = "update employee set name = ?, mobile_no = ?, faculty_type = ?, designation = ?, is_active = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objEmployee->name,
            $objEmployee->mobile_no,
            $objEmployee->faculty_type,
            $objEmployee->designation,
            $objEmployee->is_active,
            $objEmployee->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objEmployee;
        return FALSE;
    }

    public function delete_employee($id) {
        $pdo = CDatabase::getPdo();
        
        $sql = "delete from user where reference_id = ? and role_id != 5";
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $stattement = NULL;
        
        $sql = "delete from employee where id = ?";
        
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_employee_count() {
        $count = 0;
        $sql = "select count(id) as cnt from employee";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_employee($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select* from employee order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select* from employee where $filterString order by $sortBy $sortType limit $offset, $limit";
        
        
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objEmployee = new Employee_object();
            $objEmployee->id = $row['id'];
            $objEmployee->name = $row['name'];
            $objEmployee->mobile_no = $row['mobile_no'];
            $objEmployee->faculty_type = $row['faculty_type'];
            $objEmployee->designation = $row['designation'];
            $objEmployee->is_active = $row['is_active'] == 1;
            $records[] = $objEmployee;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

