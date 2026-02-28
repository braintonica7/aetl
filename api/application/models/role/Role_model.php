<?php

class Role_model extends CI_Model {

    public function get_role($id) {
        $sql = "select * from role where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objRole = new Role_object();
            $objRole->id = $row['id'];
            $objRole->role = $row['role'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objRole;
    }

    public function get_all_roles() {
        $records = array();

        $sql = "select * from role";        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objRole = new Role_object();
            $objRole->id = $row['id'];
            $objRole->role = $row['role'];

            $records[] = $objRole;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_role($objRole) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from role";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objRole->id = $row['mvalue'];
        else
            $objRole->id = 0;
        $objRole->id = $objRole->id + 1;
        $sql = "insert into role values (?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objRole->id,
            $objRole->role
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objRole;
        return FALSE;
    }

    public function update_role($objRole) {
        $sql = "update role set role = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objRole->role,
            $objRole->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objRole;
        return FALSE;
    }

    public function delete_role($id) {
        $sql = "delete from role where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_role_count() {
        $count = 0;
        $sql = "select count(id) as cnt from role";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    } 

    public function get_paginated_role($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        $sortBy = 'role';
        $sortType = 'asc';
        if ($filterString == NULL)
            $sql = "select * from role order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select * from role where $filterString order by $sortBy $sortType limit $offset, $limit";
        log_message('debug', "Role Model - get_paginated_role SQL : " . $sql);
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objRole = new Role_object();
            $objRole->id = $row['id'];
            $objRole->role = $row['role'];
            $records[] = $objRole;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

