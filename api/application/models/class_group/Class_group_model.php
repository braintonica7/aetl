<?php

class Class_group_model extends CI_Model {

    public function get_class_group($id) {
        $sql = "select * from class_group where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objClass_group = new Class_group_object();
            $objClass_group->id = $row['id'];
            $objClass_group->class_group = $row['class_group'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objClass_group;
    }

    public function get_all_class_groups() {
        $records = array();

        $sql = "select * from class_group";        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objClass_group = new Class_group_object();
            $objClass_group->id = $row['id'];
            $objClass_group->class_group = $row['class_group'];

            $records[] = $objClass_group;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_class_group($objClass_group) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from class_group";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objClass_group->id = $row['mvalue'];
        else
            $objClass_group->id = 0;
        $objClass_group->id = $objClass_group->id + 1;
        $sql = "insert into class_group values (?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objClass_group->id,
            $objClass_group->class_group
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objClass_group;
        return FALSE;
    }

    public function update_class_group($objClass_group) {
        $sql = "update class_group set class_group = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objClass_group->class_group,
            $objClass_group->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objClass_group;
        return FALSE;
    }

    public function delete_class_group($id) {
        $sql = "delete from class_group where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_class_group_count() {
        $count = 0;
        $sql = "select count(id) as cnt from class_group";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_class_group($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select * from class_group order by $sortBy $sortType limit $offset, $limit";        
        else
            $sql = "select * from class_group where $filterString order by $sortBy $sortType limit $offset, $limit";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objClass_group = new Class_group_object();
            $objClass_group->id = $row['id'];
            $objClass_group->class_group = $row['class_group'];
            $records[] = $objClass_group;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

