<?php

class Org_model extends CI_Model {

    public function get_org($id) {
        $objOrg = NULL;
        $sql = "select * from org where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objOrg = new Org_object();
            $objOrg->id = $row['id'];
            $objOrg->name = $row['name'];
            $objOrg->address = $row['address'];
            $objOrg->logo_url = $row['logo_url'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objOrg;
    }

    public function get_all_orgs() {
        $records = array();

        $sql = "select * from org";
        $sql = "select * from org where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objOrg = new Org_object();
            $objOrg->id = $row['id'];
            $objOrg->name = $row['name'];
            $objOrg->address = $row['address'];
            $objOrg->logo_url = $row['logo_url'];

            $records[] = $objOrg;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_org($objOrg) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from org";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objOrg->id = $row['mvalue'];
        else
            $objOrg->id = 0;
        $objOrg->id = $objOrg->id + 1;
        $sql = "insert into org values (?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objOrg->id,
            $objOrg->name,
            $objOrg->address,
            $objOrg->logo_url
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objOrg;
        return FALSE;
    }

    public function update_org($objOrg) {
        $sql = "update org set name = ?, address = ?, logo_url = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objOrg->name,
            $objOrg->address,
            $objOrg->logo_url,
            $objOrg->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objOrg;
        return FALSE;
    }

    public function delete_org($id) {
        $sql = "delete from org where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_org_count() {
        $count = 0;
        $sql = "select count(id) as cnt from org";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_org($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select* from org order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select* from org where $filterString order by $sortBy $sortType limit $offset, $limit";

        $sql = "select * from org limit $offset, $limit";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objOrg = new Org_object();
            $objOrg->id = $row['id'];
            $objOrg->name = $row['name'];
            $objOrg->address = $row['address'];
            $objOrg->logo_url = $row['logo_url'];
            $records[] = $objOrg;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

