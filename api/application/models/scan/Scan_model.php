<?php

class Scan_model extends CI_Model {

    public function get_scan_by_doc_id($id) {
        $objScan = NULL;
        $sql = "select * from scan where doc_id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objScan = new Scan_object();
            $objScan->id = $row['id'];
            $objScan->doc_id = $row['doc_id'];
            $objScan->doc_status = $row['doc_status'];
            $objScan->repair_order_id = $row['repair_order_id'];
            $objScan->vin_no = $row['vin_no'];
            $objScan->v_production_date = $row['v_production_date'];
            $objScan->v_model_year = $row['v_model_year'];
            $objScan->v_model_description = $row['v_model_description'];
            $objScan->v_model_name = $row['v_model_name'];
            $objScan->insurance_company = $row['insurance_company'];
            $objScan->repair_facility = $row['repair_facility'];
            $objScan->document = $row['document'];
            $objScan->data = $row['data'];
            $objScan->status = $row['status'];
            $objScan->error = $row['error'];
            $objScan->create_date = $row['create_date'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objScan;
    }

    public function get_Scan($id) {
        $objScan = NULL;
        $sql = "select * from scan where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objScan = new Scan_object();
            $objScan->id = $row['id'];
            $objScan->doc_id = $row['doc_id'];
            $objScan->doc_status = $row['doc_status'];
            $objScan->repair_order_id = $row['repair_order_id'];
            $objScan->vin_no = $row['vin_no'];
            $objScan->v_production_date = $row['v_production_date'];
            $objScan->v_model_year = $row['v_model_year'];
            $objScan->v_model_description = $row['v_model_description'];
            $objScan->v_model_name = $row['v_model_name'];
            $objScan->insurance_company = $row['insurance_company'];
            $objScan->repair_facility = $row['repair_facility'];
            $objScan->document = $row['document'];
            $objScan->data = $row['data'];
            $objScan->status = $row['status'];
            $objScan->error = $row['error'];
            $objScan->create_date = $row['create_date'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objScan;
    }

    public function get_all_Scans() {
        $records = array();

        $sql = "select * from scan";
       // $sql = "select * from scan where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objScan = new Scan_object();
            $objScan->id = $row['id'];
            $objScan->doc_id = $row['doc_id'];
            $objScan->doc_status = $row['doc_status'];
            $objScan->repair_order_id = $row['repair_order_id'];
            $objScan->vin_no = $row['vin_no'];
            $objScan->v_production_date = $row['v_production_date'];
            $objScan->v_model_year = $row['v_model_year'];
            $objScan->v_model_description = $row['v_model_description'];
            $objScan->v_model_name = $row['v_model_name'];
            $objScan->insurance_company = $row['insurance_company'];
            $objScan->repair_facility = $row['repair_facility'];
            $objScan->document = $row['document'];
            $objScan->data = $row['data'];
            $objScan->status = $row['status'];
            $objScan->error = $row['error'];
            $objScan->create_date = $row['create_date'];
            $records[] = $objScan;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function get_all_pending_scans() {
        $records = array();
        $sql = "select * from scan where status = 'SUBMITTED'";
       // $sql = "select * from scan where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objScan = new Scan_object();
            $objScan->id = $row['id'];
            $objScan->doc_id = $row['doc_id'];
            $objScan->doc_status = $row['doc_status'];
            $objScan->repair_order_id = $row['repair_order_id'];
            $objScan->vin_no = $row['vin_no'];
            $objScan->v_production_date = $row['v_production_date'];
            $objScan->v_model_year = $row['v_model_year'];
            $objScan->v_model_description = $row['v_model_description'];
            $objScan->v_model_name = $row['v_model_name'];
            $objScan->insurance_company = $row['insurance_company'];
            $objScan->repair_facility = $row['repair_facility'];
            $objScan->document = $row['document'];
            $objScan->data = $row['data'];
            $objScan->status = $row['status'];
            $objScan->error = $row['error'];
            $objScan->create_date = $row['create_date'];
            $records[] = $objScan;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_Scan($objScan) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(id) as mvalue from scan";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objScan->id = $row['mvalue'];
        else
            $objScan->id = 0;
        $objScan->id = $objScan->id + 1;
        $sql = "insert into scan (id, doc_id,doc_status, repair_order_id,vin_no, v_production_date, v_model_year,v_model_description, v_model_name, document,insurance_company,repair_facility, data ) values (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objScan->id,
            $objScan->doc_id,
            $objScan->doc_status,
            $objScan->repair_order_id,
            $objScan->vin_no,
            $objScan->v_production_date,
            $objScan->v_model_year,
            $objScan->v_model_description,
            $objScan->v_model_name,
            $objScan->insurance_company,
            $objScan->repair_facility,
            $objScan->document,
            $objScan->data

        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objScan;
        return FALSE;
    }

    public function update_scan_status($objScan) {
        $sql = "update scan set status = ?, error = ?, update_date=? where id = ?";
        //echo $sql;
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $dateTime = new DateTime();
        $updated = $statement->execute(array(
            $objScan->status,
            $objScan->error,
            $dateTime->format('Y-m-d H:i:s.v'),
            $objScan->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objScan;
        return FALSE;
    }

    public function update_Scan($objScan) {
        $sql = "update scan set doc_id = ?, doc_status = ?, repair_order_id = ?, vin_no = ?, v_production_date=?, v_model_year=?, v_model_description=?, v_model_name=?,insurance_company=?, repair_facility=? , document=?, update_date=? where id = ?";
        //echo $sql;
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $dateTime = new DateTime();
        $updated = $statement->execute(array(
            $objScan->doc_id,
            $objScan->doc_status,
            $objScan->repair_order_id,
            $objScan->vin_no,
            $objScan->v_production_date,
            $objScan->v_model_year,
            $objScan->v_model_description,
            $objScan->v_model_name,
            $objScan->insurance_company,
            $objScan->repair_facility,
            $objScan->document,
            $dateTime->format('Y-m-d H:i:s.v'),
            $objScan->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objScan;
        return FALSE;
    }

    public function delete_Scan($id) {
        $sql = "delete from scan where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_Scan_count() {
        $count = 0;
        $sql = "select count(id) as cnt from scan";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_Scan($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        //echo "<br>IN Model sortBy = $sortBy";

        if ($filterString == NULL)
            $sql = "select* from scan order by $sortBy $sortType limit $offset, $limit";
        else
            $sql = "select* from scan where $filterString order by $sortBy $sortType limit $offset, $limit";

        //$sql = "select * from scan limit $offset, $limit";

       // print_r($sql);
      //  echo "<br>";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objScan = new Scan_object();
            $objScan->id = $row['id'];
            $objScan->doc_id = $row['doc_id'];
            $objScan->doc_status = $row['doc_status'];
            $objScan->repair_order_id = $row['repair_order_id'];
            $objScan->vin_no = $row['vin_no'];
            $objScan->v_production_date = $row['v_production_date'];
            $objScan->v_model_year = $row['v_model_year'];
            $objScan->v_model_description = $row['v_model_description'];
            $objScan->v_model_name = $row['v_model_name'];
            $objScan->insurance_company = $row['insurance_company'];
            $objScan->repair_facility = $row['repair_facility'];
            $objScan->document = $row['document'];
            $objScan->data = $row['data'];
            $objScan->status = $row['status'];
            $objScan->error = $row['error'];
            $objScan->create_date = $row['create_date'];
            $records[] = $objScan;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

   

}
?>

