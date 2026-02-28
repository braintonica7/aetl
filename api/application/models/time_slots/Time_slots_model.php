<?php

class Time_slots_model extends CI_Model {

    /**
     * Get all active time slots
     */
    public function get_all_time_slots() {
        $records = array();
        $sql = "SELECT * FROM time_slots WHERE is_active = 1 ORDER BY start_time";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        
        while ($row = $statement->fetch()) {
            $timeSlot = array(
                'id' => $row['id'],
                'label' => $row['label'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'is_active' => $row['is_active'] == 1,
                'created_at' => $row['created_at']
            );
            $records[] = $timeSlot;
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    /**
     * Get time slot by ID
     */
    public function get_time_slot($id) {
        $timeSlot = NULL;
        $sql = "SELECT * FROM time_slots WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        
        if ($row = $statement->fetch()) {
            $timeSlot = array(
                'id' => $row['id'],
                'label' => $row['label'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'is_active' => $row['is_active'] == 1,
                'created_at' => $row['created_at']
            );
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $timeSlot;
    }

    /**
     * Get time slots by IDs
     */
    public function get_time_slots_by_ids($ids) {
        if (empty($ids)) {
            return array();
        }
        
        $records = array();
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "SELECT * FROM time_slots WHERE id IN ($placeholders) AND is_active = 1 ORDER BY start_time";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute($ids);
        
        while ($row = $statement->fetch()) {
            $timeSlot = array(
                'id' => $row['id'],
                'label' => $row['label'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'is_active' => $row['is_active'] == 1,
                'created_at' => $row['created_at']
            );
            $records[] = $timeSlot;
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    /**
     * Add new time slot
     */
    public function add_time_slot($label, $start_time, $end_time, $is_active = 1) {
        $pdo = CDatabase::getPdo();

        // Get next ID
        $sql = "SELECT MAX(id) as mvalue FROM time_slots";
        $statement = $pdo->prepare($sql);
        $statement->execute();
        $id = 1;
        if ($row = $statement->fetch()) {
            $id = $row['mvalue'] + 1;
        }

        date_default_timezone_set("Asia/Calcutta");
        $dateTime = new DateTime();
        $created_at = $dateTime->format('Y-m-d H:i:s');

        $sql = "INSERT INTO time_slots (id, label, start_time, end_time, is_active, created_at) VALUES (?,?,?,?,?,?)";
        $statement = $pdo->prepare($sql);
        $inserted = $statement->execute(array($id, $label, $start_time, $end_time, $is_active, $created_at));

        $statement = NULL;
        $pdo = NULL;
        
        if ($inserted) {
            return array(
                'id' => $id,
                'label' => $label,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'is_active' => $is_active == 1,
                'created_at' => $created_at
            );
        }
        return FALSE;
    }

    /**
     * Update time slot
     */
    public function update_time_slot($id, $label, $start_time, $end_time, $is_active = 1) {
        $pdo = CDatabase::getPdo();
        $sql = "UPDATE time_slots SET label = ?, start_time = ?, end_time = ?, is_active = ? WHERE id = ?";
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array($label, $start_time, $end_time, $is_active, $id));
        $statement = NULL;
        $pdo = NULL;
        return $updated;
    }

    /**
     * Deactivate time slot
     */
    public function deactivate_time_slot($id) {
        $pdo = CDatabase::getPdo();
        $sql = "UPDATE time_slots SET is_active = 0 WHERE id = ?";
        $statement = $pdo->prepare($sql);
        $updated = $statement->execute(array($id));
        $statement = NULL;
        $pdo = NULL;
        return $updated;
    }

    /**
     * Get time slots by time range
     */
    public function get_time_slots_by_range($start_time, $end_time) {
        $records = array();
        $sql = "SELECT * FROM time_slots WHERE start_time >= ? AND end_time <= ? AND is_active = 1 ORDER BY start_time";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($start_time, $end_time));
        
        while ($row = $statement->fetch()) {
            $timeSlot = array(
                'id' => $row['id'],
                'label' => $row['label'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'is_active' => $row['is_active'] == 1,
                'created_at' => $row['created_at']
            );
            $records[] = $timeSlot;
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $records;
    }

    /**
     * Get time slots for a specific time preference (morning, afternoon, evening, night)
     */
    public function get_time_slots_by_preference($preference) {
        $start_time = '';
        $end_time = '';
        
        switch ($preference) {
            case 'morning':
                $start_time = '05:00:00';
                $end_time = '12:00:00';
                break;
            case 'afternoon':
                $start_time = '12:00:00';
                $end_time = '17:00:00';
                break;
            case 'evening':
                $start_time = '17:00:00';
                $end_time = '20:00:00';
                break;
            case 'night':
                $start_time = '20:00:00';
                $end_time = '23:59:59';
                break;
            default:
                return $this->get_all_time_slots();
        }
        
        return $this->get_time_slots_by_range($start_time, $end_time);
    }
}

?>