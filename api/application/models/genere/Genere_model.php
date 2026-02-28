<?php

class Genere_model extends CI_Model {

    public function get_genere($id) {
        $objGenere = NULL;
        $sql = "select * from genere where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objGenere = new Genere_object();
            $objGenere->id = $row['id'];
            $objGenere->class_group_id = $row['class_group_id'];
            $objGenere->class_name = $row['class_name'];
            $objGenere->numeric_equivalent = $row['numeric_equivalent'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objGenere;
    }

    public function get_all_generes() {
        $records = array();

        $sql = "select * from genere";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objGenere = new Genere_object();
            $objGenere->id = $row['id'];
            $objGenere->class_group_id = $row['class_group_id'];
            $objGenere->class_name = $row['class_name'];
            $objGenere->numeric_equivalent = $row['numeric_equivalent'];

            $records[] = $objGenere;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_genere($objGenere) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from genere";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objGenere->id = $row['mvalue'];
        else
            $objGenere->id = 0;
        $objGenere->id = $objGenere->id + 1;
        $sql = "insert into genere values (?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objGenere->id,
            $objGenere->class_group_id,
            $objGenere->class_name,
            $objGenere->numeric_equivalent
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objGenere;
        return FALSE;
    }

    public function update_genere($objGenere) {
        $sql = "update genere set class_group_id = ?, class_name = ?, numeric_equivalent = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objGenere->class_group_id,
            $objGenere->class_name,
            $objGenere->numeric_equivalent,
            $objGenere->id            
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objGenere;
        return FALSE;
    }

    public function delete_genere($id) {
        $sql = "delete from genere where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_genere_count() {
        $count = 0;
        $sql = "select count(id) as cnt from genere";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_genere($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        $sortBy = 'numeric_equivalent';
        $sortType = 'asc';
        if ($filterString == NULL)
            $sql = "select * from genere order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select * from genere where $filterString order by $sortBy $sortType limit $offset, $limit";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objGenere = new Genere_object();
            $objGenere->id = $row['id'];
            $objGenere->class_group_id = $row['class_group_id'];
            $objGenere->class_name = $row['class_name'];
            $objGenere->numeric_equivalent = $row['numeric_equivalent'];
            $records[] = $objGenere;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

