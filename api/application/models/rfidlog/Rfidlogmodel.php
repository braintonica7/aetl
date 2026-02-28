<?php

class RFIDLogModel extends CI_Model {

      public function get_latest_rfidlog() {
        $sql = "select * from rfidlog ORDER BY logid DESC LIMIT 1";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch()) {
            $objRfidlog = new RfidlogObject();
            $objRfidlog->logid = $row['logid'];
            $objRfidlog->series = $row['series'];
            $objRfidlog->logdate = DateTime::createFromFormat("Y-m-d", $row['logdate']);
            $objRfidlog->logtime = DateTime::createFromFormat("Y-m-d H:i:s", $row['logtime']);
            $objRfidlog->logtime = $row['logtime'];
            $objRfidlog->machineid = $row['machineid'];
            $objRfidlog->cardno = $row['cardno'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objRfidlog;
    }
  
    public function get_rfidlog($logid) {
        $sql = "select * from rfidlog where logid = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($logid));
        if ($row = $statement->fetch()) {
            $objRfidlog = new RfidlogObject();
            $objRfidlog->logid = $row['logid'];
            $objRfidlog->series = $row['series'];
            $objRfidlog->logdate = DateTime::createFromFormat("Y-m-d", $row['logdate']);
            $objRfidlog->logtime = DateTime::createFromFormat("Y-m-d H:i:s", $row['logtime']);
            $objRfidlog->machineid = $row['machineid'];
            $objRfidlog->cardno = $row['cardno'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objRfidlog;
    }

    public function add_rfidlog($objRfidlog) {
        $pdo = CDatabase::getPdo();

        $sql = "select max(logid) as mvalue from rfidlog";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $objRfidlog->logid = $row['mvalue'];
        else
            $objRfidlog->logid = 0;
        $objRfidlog->logid = $objRfidlog->logid + 1;
        $sql = "insert into rfidlog values (?,?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $statement->execute(array(
            $objRfidlog->logid,
            $objRfidlog->series,
            $objRfidlog->logdate->format('Y-m-d'),
            $objRfidlog->logtime->format('Y-m-d H:i:s'),
            $objRfidlog->machineid,
            $objRfidlog->cardno,
            $objRfidlog->apikey,
        ));
        $statement = NULL;
        $pdo = NULL;
    }

    public function update_rfidlog($objRfidlog) {
        $sql = "update rfidlog set logdate = ?, logtime = ?, machineid = ?, cardno = ? where logid = ? and series = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array(
            $objRfidlog->logdate->format('Y-m-d'),
            $objRfidlog->logtime->format('Y-m-d H:i:s'),
            $objRfidlog->machineid,
            $objRfidlog->cardno,
            $objRfidlog->logid,
            $objRfidlog->series
        ));
        $statement = NULL;
        $pdo = NULL;
    }

    public function delete_rfidlog($logid) {
        $sql = "delete from rfidlog where logid = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($logid));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_rfidlog_count() {
        $count = 0;
        $sql = "select count(logid) as cnt from rfidlog";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }

    public function get_paginated_rfidlog($limit, $offset) {
        $records = array();
        $sql = "select * from rfidlog limit $offset, $limit";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objRfidlog = new RfidlogObject();
            $objRfidlog->logid = $row['logid'];
            $temp = explode("-", $row['logdate']);
            $objRfidlog->logdate = DateTime::createFromFormat("Y-m-d", "$temp[0]-$temp[1]-$temp[2]");
            $temp = explode("-", $row['logtime']);
            $objRfidlog->logtime = DateTime::createFromFormat("Y-m-d", "$temp[0]-$temp[1]-$temp[2]");
            $objRfidlog->machineid = $row['machineid'];
            $objRfidlog->cardno = $row['cardno'];
            $records[] = $objRfidlog;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}
?>

