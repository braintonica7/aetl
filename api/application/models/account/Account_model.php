<?php

class Account_model extends CI_Model {

    public function get_account($id) {
        $objAccount = NULL;
        $sql = "select * from account where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objAccount = new Account_object();
            $objAccount->id = $row['id'];
            //$objAccount->username = $row['username'];
            //$objAccount->password = $row['password'];
            $objAccount->token = $row['token'];
            $objAccount->display_name = $row['display_name'];
            //$objAccount->plan = $row['plan'];
            $objAccount->is_active = $row['is_active'] == 1;
        }
        $statement = NULL;
        $pdo = NULL;
        return $objAccount;
    }

    public function get_all_accounts() {
        $records = array();

        $sql = "select * from account";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objAccount = new Account_object();
            $objAccount->id = $row['id'];
            //$objAccount->username = $row['username'];
            //$objAccount->password = $row['password'];
            $objAccount->token = $row['token'];
            $objAccount->display_name = $row['display_name'];
            //$objAccount->plan = $row['plan'];
            $objAccount->is_active = $row['is_active'] == 1;

            $records[] = $objAccount;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_account($objAccount) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from account";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objAccount->id = $row['mvalue'];
        else
            $objAccount->id = 0;
        $objAccount->id = $objAccount->id + 1;
        $sql = "insert into account values (?,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objAccount->id,
            //$objAccount->username,
            //$objAccount->password,
            $objAccount->token,
            $objAccount->display_name,
            //$objAccount->plan,
            $objAccount->is_active
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted) {
            $objAccount->is_active = $objAccount->is_active == 1;
            return $objAccount;
        }
        return FALSE;
    }

    public function update_account($objAccount) {
        $sql = "update account set username = ?, password = ?, token = ?, display_name = ?, plan = ?, is_active = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objAccount->username,
            $objAccount->password,
            $objAccount->token,
            $objAccount->display_name,
            $objAccount->plan,
            $objAccount->is_active,
            $objAccount->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated) {
            $objAccount->is_active = $objAccount->is_active == 1;
            return $objAccount;
        }
        return FALSE;
    }

    public function delete_account($id) {
        $sql = "delete from account where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_account_count() {
        $count = 0;
        $sql = "select count(id) as cnt from account";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_account($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select* from account order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select* from account where $filterString order by $sortBy $sortType limit $offset, $limit";

        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objAccount = new Account_object();
            $objAccount->id = $row['id'];
            //$objAccount->username = $row['username'];
            //$objAccount->password = $row['password'];
            $objAccount->token = $row['token'];
            $objAccount->display_name = $row['display_name'];
            //$objAccount->plan = $row['plan'];
            $objAccount->is_active = $row['is_active'] == 1;
            $records[] = $objAccount;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

