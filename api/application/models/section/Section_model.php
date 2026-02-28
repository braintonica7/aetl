<?php

class Section_model extends CI_Model {

    public function get_section($id) {
        $sql = "select * from section where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objSection = new Section_object();
            $objSection->id = $row['id'];
            $objSection->class_id = $row['class_id'];
            $objSection->section = $row['section'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objSection;
    }

    public function get_all_sections() {
        $records = array();

        $sql = "select * from section";        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objSection = new Section_object();
            $objSection->id = $row['id'];
            $objSection->class_id = $row['class_id'];
            $objSection->section = $row['section'];

            $records[] = $objSection;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_section($objSection) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from section";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objSection->id = $row['mvalue'];
        else
            $objSection->id = 0;
        $objSection->id = $objSection->id + 1;
        $sql = "insert into section values (?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objSection->id,
            $objSection->class_id,
            $objSection->section
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objSection;
        return FALSE;
    }

    public function update_section($objSection) {
        $sql = "update section set class_id = ?, section = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objSection->class_id,
            $objSection->section,
            $objSection->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objSection;
        return FALSE;
    }

    public function delete_section($id) {
        $sql = "delete from section where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_section_count() {
        $count = 0;
        $sql = "select count(id) as cnt from section";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_section($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            //$sql = "select * from section order by $sortBy $sortType limit $offset, $limit";        
            $sql = "select section.* from section left join genere on section.class_id = genere.id left join class_group on genere.class_group_id = class_group.id order by class_group.id, genere.numeric_equivalent limit $offset, $limit"; 
        else
            $sql = "select * from section where $filterString order by $sortBy $sortType limit $offset, $limit";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objSection = new Section_object();
            $objSection->id = $row['id'];
            $objSection->class_id = $row['class_id'];
            $objSection->section = $row['section'];
            $records[] = $objSection;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

