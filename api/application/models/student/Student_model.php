<?php

class Student_model extends CI_Model {

public function search_by_applicationid($applicationid) {
        $records = array();

        $sql = "select * from student where applicationid = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($applicationid));

        while ($row = $statement->fetch()) {
            $objStudent = new Student_object();
            $objStudent->applicationid = $row['applicationid'];
            $objStudent->student_name = $row['student_name'];
            $objStudent->father_name = $row['father_name'];
            $objStudent->card_no = $row['card_no'];
            $objStudent->id = $row['id'];

            $records[] = $objStudent;
        }

        $statement = NULL;
        $pdo = NULL;
        
        return $records;
    }

public function search_by_cardno($cardno) {
        $records = array();

        $sql = "select * from student where card_no = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($cardno));

        while ($row = $statement->fetch()) {
            $objStudent = new Student_object();
            $objStudent->applicationid = $row['applicationid'];
            $objStudent->student_name = $row['student_name'];
            $objStudent->father_name = $row['father_name'];
            $objStudent->card_no = $row['card_no'];
            $objStudent->id = $row['id'];

            $records[] = $objStudent;
        }

        $statement = NULL;
        $pdo = NULL;
        
        return $records;
    }

    public function get_student($id) {
        $sql = "select * from student where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        if ($row = $statement->fetch()) {
            $objStudent = new Student_object();
            $objStudent->applicationid = $row['applicationid'];
            $objStudent->student_name = $row['student_name'];
            $objStudent->father_name = $row['father_name'];
            $objStudent->card_no = $row['card_no'];
            $objStudent->id = $row['id'];
        }
        $statement = NULL;
        $pdo = NULL;
        return $objStudent;
    }

    public function get_all_students() {
        $records = array();

        $sql = "select * from student";        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objStudent = new Student_object();
            $objStudent->applicationid = $row['applicationid'];
            $objStudent->student_name = $row['student_name'];
            $objStudent->father_name = $row['father_name'];
            $objStudent->card_no = $row['card_no'];
            $objStudent->id = $row['id'];

            $records[] = $objStudent;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    public function add_student($objStudent) {
        $pdo = CDatabase::getPdo();

        // $sql = "select max(id) as mvalue from student";
        // $statement = $pdo->prepare($sql);
        // $statement->execute();
        // if ($row = $statement->fetch())
        //     $objStudent->id = $row['mvalue'];
        // else
        //     $objStudent->id = 0;
        
        // $objStudent->id = $objStudent->id + 1;
        $sql = "insert into student(applicationid,student_name,father_name,card_no) values (?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objStudent['applicationid'],
            $objStudent['student_name'],
            $objStudent['father_name'],
            $objStudent['card_no']
            // ,
            // $objStudent->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objStudent;
        return FALSE;
    }

public function add_attendance($objAttendance) {
        $pdo = CDatabase::getPdo();

        // $sql = "select max(id) as mvalue from student";
        // $statement = $pdo->prepare($sql);
        // $statement->execute();
        // if ($row = $statement->fetch())
        //     $objStudent->id = $row['mvalue'];
        // else
        //     $objStudent->id = 0;
        
        // $objStudent->id = $objStudent->id + 1;
        $sql = "insert into attendance(student_id,month_number,year,exam,attendance_date,attendance_value) values (?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array(
            $objAttendance['student_id'],
            $objAttendance['month_number'],
            $objAttendance['year'],
            $objAttendance['exam'],
            $objAttendance['attendance_date'],
            $objAttendance['attendance_value']
            // ,
            // $objStudent->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($inserted)
            return $objAttendance;
        return FALSE;
    }
    public function update_student($objStudent) {
        $sql = "update student set applicationid = ?, student_name = ?, father_name = ?, card_no = ? where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array(
            $objStudent->applicationid,
            $objStudent->student_name,
            $objStudent->father_name,
            $objStudent->card_no,
            $objStudent->id
        ));
        $statement = NULL;
        $pdo = NULL;
        if ($updated)
            return $objStudent;
        return FALSE;
    }

    
    public function delete_attendance($month,$year) {
        $sql = "delete from attendance where month_number = $month and  year = $year"; 
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        $statement = NULL;
        $pdo = NULL;
    }
      public function delete_day_attendance($student_id,$date) {
        
        $sql = "delete from attendance where student_id = $student_id and  attendance_date = '$date'"; 
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        $statement = NULL;
        $pdo = NULL;
    }

    public function delete_student($id) {
        $sql = "delete from student where id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
    }

    public function get_student_count() {
        $count = 0;
        $sql = "select count(id) as cnt from student";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        if ($row = $statement->fetch())
            $count = $row['cnt'];
        $statement = NULL;
        $pdo = NULL;
        return $count;
    }
public function get_attendance($month, $year) {
    $records = array();
    $sql = "CALL GetAttendancePivotV1($month, $year)";
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
  
    try {
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        
        $statement->execute();

        // Fetch the result as an associative array
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                 
                if (isset($row['student_name'])) {
                    // Rename the column
                            $row['Applicant Name'] = $row['student_name'];
                        unset($row['student_name']);
                }
                if (isset($row['father_name'])) {
                    // Rename the column
                            $row['Father Name'] = $row['father_name'];
                        unset($row['father_name']);
                }
                if (isset($row['applicationid'])) {
                    // Rename the column
                            $row['Application Id'] = $row['applicationid'];
                        unset($row['applicationid']);
                }
                $row['Institute Name'] = 'MT EDUCARE LAKSHYA INSTITUTE,AJMAR';
                 $row['MACHINE ID'] = '202201021';
                   if (isset($row['exam'])) {
                    // Rename the column
                            $row['Exam'] = $row['exam'];
                        unset($row['exam']);
                }
                   if (isset($row['card_no'])) {
                    // Rename the column
                            $row['RFID CARD NO'] = $row['card_no'];
                        unset($row['card_no']);
                }
                

            for ($day = 1; $day <=31; $day++) {
                    $oldColumnName = "day_" . $day;
                        $newColumnName ="  " . $day; //(string)$day;

        // Check if the old column exists and rename it
                        if (isset($row[$oldColumnName])) {
                            
                                $row[$newColumnName] = $row[$oldColumnName];
                                unset($row[$oldColumnName]);
                }
                else
                {
                    unset($row[$oldColumnName]);
                }
    }
            $records[] = $row;
        }

        // Output the result as JSON
        header('Content-Type: application/json');
      //  echo json_encode($records);

        return $records;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    } finally {
        // Close the connection
        $pdo = null;
    }
}
public function get_attendance_V12($month, $year) {
    $records = array();
    $sql = "CALL GetAttendancePivotV1($month, $year)";
  
    try {
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        
        $statement->execute();

        // Fetch the result as an associative array
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                 $row['Institute Name'] = 'MT EDUCARE LAKSHYA INSTITUTE,AJMAR';
                 $row['MACHINE ID'] = '202201021';
                if (isset($row['student_name'])) {
                    // Rename the column
                            $row['Applicant Name'] = $row['student_name'];
                        unset($row['student_name']);
                }
                if (isset($row['father_name'])) {
                    // Rename the column
                            $row['Father Name'] = $row['father_name'];
                        unset($row['father_name']);
                }
                if (isset($row['applicationid'])) {
                    // Rename the column
                            $row['Application Id'] = $row['applicationid'];
                        unset($row['applicationid']);
                }
                   if (isset($row['exam'])) {
                    // Rename the column
                            $row['Exam'] = $row['exam'];
                        unset($row['exam']);
                }
                   if (isset($row['card_no'])) {
                    // Rename the column
                            $row['RFID CARD NO'] = $row['card_no'];
                        unset($row['card_no']);
                }
                

            for ($day = 1; $day <= 31; $day++) {
                    $oldColumnName = "day_" . $day;
                        $newColumnName ="_day_" . $day; //(string)$day;

        // Check if the old column exists and rename it
                        if (isset($row[$oldColumnName])) {
                            
                                $row[$newColumnName] = $row[$oldColumnName];
                                unset($row[$oldColumnName]);
                }
    }
            $records[] = $row;
        }

        // Output the result as JSON
        header('Content-Type: application/json');
      //  echo json_encode($records);

        return $records;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    } finally {
        // Close the connection
        $pdo = null;
    }
}
public function get_attendance_v1($month, $year) {
    $records = array();
    $sql = "CALL GetAttendancePivot($month, $year)";
  
    try {
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        
        $statement->execute();

        // Fetch the result as an associative array
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $records[] = $row;
        }

        // Output the result as JSON
        header('Content-Type: application/json');
      //  echo json_encode($records);

        return $records;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    } finally {
        // Close the connection
        $pdo = null;
    }
}


    public function get_paginated_students($offset, $limit, $sortBy, $sortType, $filterString = NULL) {
        $records = array();
        $sql = "";
        if ($filterString == NULL)
            $sql = "select * from student order by $sortBy $sortType limit $offset, $limit";        
        else
            $sql = "select * from student where $filterString order by $sortBy $sortType limit $offset, $limit";
        
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        while ($row = $statement->fetch()) {
            $objStudent = new Student_object();
            $objStudent->applicationid = $row['applicationid'];
            $objStudent->student_name = $row['student_name'];
            $objStudent->father_name = $row['father_name'];
            $objStudent->card_no = $row['card_no'];
            $objStudent->id = $row['id'];
            $records[] = $objStudent;
        }
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

}

?>
