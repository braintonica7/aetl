<?php

class Notification_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Create a new notification
     */
    public function create_notification($data) {
        try {
            $sql = "INSERT INTO notifications (title, body, type, target_type, target_user_id, deep_link_screen, deep_link_data, created_by, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $data['title'],
                $data['body'],
                $data['type'],
                $data['target_type'],
                $data['target_user_id'],
                $data['deep_link_screen'],
                $data['deep_link_data'],
                $data['created_by'],
                $data['status'],
                date('Y-m-d H:i:s')
            ];

            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $result = $statement->execute($params);
            $insert_id = $pdo->lastInsertId();
            $statement = NULL;
            
            return $result ? $insert_id : false;
        } catch (Exception $e) {
            log_message('error', 'Error creating notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update notification
     */
    public function update_notification($id, $data) {
        try {
            $fields = [];
            $params = [];
            
            foreach ($data as $key => $value) {
                $fields[] = "$key = ?";
                $params[] = $value;
            }
            
            $fields[] = "updated_at = ?";
            $params[] = date('Y-m-d H:i:s');
            $params[] = $id;
            
            $sql = "UPDATE notifications SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $result = $statement->execute($params);
            $statement = NULL;
            
            return $result;
        } catch (Exception $e) {
            log_message('error', 'Error updating notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create notification recipient record
     */
    public function create_notification_recipient($data) {
        try {
            $sql = "INSERT INTO notification_recipients (notification_id, user_id, fcm_token, delivery_status, delivery_response, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $params = [
                $data['notification_id'],
                $data['user_id'],
                $data['fcm_token'],
                $data['delivery_status'],
                $data['delivery_response'],
                date('Y-m-d H:i:s')
            ];

            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $result = $statement->execute($params);
            $insert_id = $pdo->lastInsertId();
            $statement = NULL;
            
            return $result ? $insert_id : false;
        } catch (Exception $e) {
            log_message('error', 'Error creating notification recipient: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get notification history with pagination
     */
    public function get_notification_history($page = 1, $limit = 20, $type = null) {
        try {
            $offset = ($page - 1) * $limit;

            // Build the base query
            $sql = "SELECT 
                        n.id,
                        n.title,
                        n.body,
                        n.type,
                        n.target_type,
                        n.target_user_id,
                        n.deep_link_screen,
                        n.status,
                        n.recipients_count,
                        n.sent_at,
                        n.created_at,
                        u.username as created_by_name,
                        tu.username as target_user_name
                    FROM notifications n
                    LEFT JOIN user u ON n.created_by = u.id
                    LEFT JOIN user tu ON n.target_user_id = tu.id";
            
            $where_conditions = [];
            $params = [];
            
            if ($type) {
                $where_conditions[] = "n.type = ?";
                $params[] = $type;
            }
            
            if (!empty($where_conditions)) {
                $sql .= " WHERE " . implode(' AND ', $where_conditions);
            }
            
            $sql .= " ORDER BY n.created_at DESC";

            // Get total count for pagination
            $count_sql = "SELECT COUNT(*) as total FROM notifications n";
            if (!empty($where_conditions)) {
                $count_sql .= " WHERE " . implode(' AND ', $where_conditions);
            }

            $pdo = CDatabase::getPdo();
            
            // Get total count
            $count_statement = $pdo->prepare($count_sql);
            $count_statement->execute($params);
            $total_count = $count_statement->fetch()['total'];
            $count_statement = NULL;

            // Get paginated results
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            
            $notifications = [];
            while ($row = $statement->fetch()) {
                $notification = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'body' => $row['body'],
                    'type' => $row['type'],
                    'target_type' => $row['target_type'],
                    'target_user_id' => $row['target_user_id'],
                    'deep_link_screen' => $row['deep_link_screen'],
                    'status' => $row['status'],
                    'recipients_count' => $row['recipients_count'],
                    'sent_at' => $row['sent_at'],
                    'created_at' => $row['created_at'],
                    'created_by_name' => $row['created_by_name'],
                    'target_user_name' => $row['target_user_name']
                ];
                
                // Get delivery statistics for this notification
                $notification['delivery_stats'] = $this->get_notification_delivery_stats($row['id']);
                $notifications[] = $notification;
            }
            $statement = NULL;

            return [
                'notifications' => $notifications,
                'pagination' => [
                    'total' => $total_count,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total_count / $limit)
                ]
            ];
        } catch (Exception $e) {
            log_message('error', 'Error getting notification history: ' . $e->getMessage());
            return [
                'notifications' => [],
                'pagination' => [
                    'total' => 0,
                    'page' => 1,
                    'limit' => $limit,
                    'total_pages' => 0
                ]
            ];
        }
    }

    /**
     * Get delivery statistics for a notification
     */
    public function get_notification_delivery_stats($notification_id) {
        try {
            $sql = "SELECT delivery_status, COUNT(*) as count 
                    FROM notification_recipients 
                    WHERE notification_id = ? 
                    GROUP BY delivery_status";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute([$notification_id]);
            
            $stats = [
                'sent' => 0,
                'delivered' => 0,
                'failed' => 0,
                'opened' => 0
            ];

            while ($row = $statement->fetch()) {
                $stats[$row['delivery_status']] = $row['count'];
            }
            $statement = NULL;

            return $stats;
        } catch (Exception $e) {
            log_message('error', 'Error getting delivery stats: ' . $e->getMessage());
            return [
                'sent' => 0,
                'delivered' => 0,
                'failed' => 0,
                'opened' => 0
            ];
        }
    }

    /**
     * Get notification by ID
     */
    public function get_notification($id) {
        try {
            $sql = "SELECT * FROM notifications WHERE id = ?";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $statement->execute([$id]);
            
            $notification = null;
            if ($row = $statement->fetch()) {
                $notification = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'body' => $row['body'],
                    'type' => $row['type'],
                    'target_type' => $row['target_type'],
                    'target_user_id' => $row['target_user_id'],
                    'deep_link_screen' => $row['deep_link_screen'],
                    'deep_link_data' => $row['deep_link_data'],
                    'created_by' => $row['created_by'],
                    'status' => $row['status'],
                    'recipients_count' => $row['recipients_count'],
                    'sent_at' => $row['sent_at'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                ];
            }
            $statement = NULL;
            
            return $notification;
        } catch (Exception $e) {
            log_message('error', 'Error getting notification: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user's notification recipients
     */
    public function get_user_notification_history($user_id, $page = 1, $limit = 20) {
        try {
            $offset = ($page - 1) * $limit;

            $sql = "SELECT 
                        n.id,
                        n.title,
                        n.body,
                        n.type,
                        n.deep_link_screen,
                        nr.delivery_status,
                        nr.delivered_at,
                        nr.opened_at,
                        n.sent_at
                    FROM notification_recipients nr
                    JOIN notifications n ON nr.notification_id = n.id
                    WHERE nr.user_id = ?
                    ORDER BY n.sent_at DESC";

            // Get total count
            $count_sql = "SELECT COUNT(*) as total 
                         FROM notification_recipients nr
                         JOIN notifications n ON nr.notification_id = n.id
                         WHERE nr.user_id = ?";

            $pdo = CDatabase::getPdo();
            
            // Get total count
            $count_statement = $pdo->prepare($count_sql);
            $count_statement->execute([$user_id]);
            $total_count = $count_statement->fetch()['total'];
            $count_statement = NULL;

            // Get paginated results
            $sql .= " LIMIT ? OFFSET ?";
            $statement = $pdo->prepare($sql);
            $statement->execute([$user_id, $limit, $offset]);
            
            $notifications = [];
            while ($row = $statement->fetch()) {
                $notifications[] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'body' => $row['body'],
                    'type' => $row['type'],
                    'deep_link_screen' => $row['deep_link_screen'],
                    'delivery_status' => $row['delivery_status'],
                    'delivered_at' => $row['delivered_at'],
                    'opened_at' => $row['opened_at'],
                    'sent_at' => $row['sent_at']
                ];
            }
            $statement = NULL;

            return [
                'notifications' => $notifications,
                'pagination' => [
                    'total' => $total_count,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total_count / $limit)
                ]
            ];
        } catch (Exception $e) {
            log_message('error', 'Error getting user notification history: ' . $e->getMessage());
            return [
                'notifications' => [],
                'pagination' => [
                    'total' => 0,
                    'page' => 1,
                    'limit' => $limit,
                    'total_pages' => 0
                ]
            ];
        }
    }

    /**
     * Mark notification as opened by user
     */
    public function mark_notification_opened($notification_id, $user_id) {
        try {
            $sql = "UPDATE notification_recipients 
                    SET delivery_status = 'opened', opened_at = ? 
                    WHERE notification_id = ? AND user_id = ?";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $result = $statement->execute([date('Y-m-d H:i:s'), $notification_id, $user_id]);
            $affected_rows = $statement->rowCount();
            $statement = NULL;

            return $affected_rows > 0;
        } catch (Exception $e) {
            log_message('error', 'Error marking notification as opened: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user notifications with filtering and pagination
     * For web dashboard NotificationsPanel
     */
    public function get_user_notifications($user_id, $page = 1, $limit = 20, $type = null, $unread_only = false) {
        try {
            $offset = ($page - 1) * $limit;

            // Build the base query
            $sql = "SELECT 
                        n.id,
                        n.type,
                        n.title,
                        n.body as message,
                        n.sent_at,
                        n.deep_link_screen,
                        n.deep_link_data,
                        nr.delivery_status,
                        nr.opened_at,
                        CASE 
                            WHEN nr.opened_at IS NOT NULL THEN 1 
                            ELSE 0 
                        END as is_read
                    FROM notifications n
                    INNER JOIN notification_recipients nr ON n.id = nr.notification_id
                    WHERE nr.user_id = ?
                        AND nr.delivery_status IN ('sent', 'delivered', 'opened')";
            
            $params = [$user_id];
            
            // Add type filter if specified
            if ($type) {
                $sql .= " AND n.type = ?";
                $params[] = $type;
            }
            
            // Add unread filter if specified
            if ($unread_only) {
                $sql .= " AND nr.opened_at IS NULL";
            }
            
            $sql .= " ORDER BY n.sent_at DESC";

            // Get total count for pagination
            $count_sql = "SELECT COUNT(*) as total 
                         FROM notifications n
                         INNER JOIN notification_recipients nr ON n.id = nr.notification_id
                         WHERE nr.user_id = ?
                             AND nr.delivery_status IN ('sent', 'delivered', 'opened')";
            
            $count_params = [$user_id];
            
            if ($type) {
                $count_sql .= " AND n.type = ?";
                $count_params[] = $type;
            }
            
            if ($unread_only) {
                $count_sql .= " AND nr.opened_at IS NULL";
            }

            $pdo = CDatabase::getPdo();
            
            // Get total count
            $count_statement = $pdo->prepare($count_sql);
            $count_statement->execute($count_params);
            $total_count = $count_statement->fetch()['total'];
            $count_statement = NULL;

            // Add LIMIT and OFFSET to SQL (don't add to params array)
            $sql .= " LIMIT ? OFFSET ?";
            
            // Cast limit and offset to integers for proper SQL syntax
            $limit = (int)$limit;
            $offset = (int)$offset;
            
            // Cast limit and offset to integers for proper SQL syntax
            $limit = (int)$limit;
            $offset = (int)$offset;
            
            $statement = $pdo->prepare($sql);
            
            // Bind parameters with proper types
            $paramIndex = 1;
            foreach ($params as $param) {
                $statement->bindValue($paramIndex++, $param);
            }
            $statement->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
            $statement->bindValue($paramIndex, $offset, PDO::PARAM_INT);
            
            $statement->execute();
            
            $notifications = [];
            while ($row = $statement->fetch()) {
                $notifications[] = [
                    'id' => $row['id'],
                    'type' => $row['type'],
                    'title' => $row['title'],
                    'message' => $row['message'],
                    'sent_at' => $row['sent_at'],
                    'deep_link_screen' => $row['deep_link_screen'],
                    'read' => (bool)$row['is_read']
                ];
            }
            $statement = NULL;

            return [
                'notifications' => $notifications,
                'pagination' => [
                    'total' => (int)$total_count,
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'total_pages' => ceil($total_count / $limit)
                ]
            ];
        } catch (Exception $e) {
            log_message('error', 'Error getting user notifications: ' . $e->getMessage());
            return [
                'notifications' => [],
                'pagination' => [
                    'total' => 0,
                    'page' => 1,
                    'limit' => $limit,
                    'total_pages' => 0
                ]
            ];
        }
    }

    /**
     * Get user notification statistics
     */
    public function get_user_notification_stats($user_id) {
        try {
            $pdo = CDatabase::getPdo();
            
            // Total count
            $total_sql = "SELECT COUNT(*) as total 
                         FROM notification_recipients 
                         WHERE user_id = ? 
                         AND delivery_status IN ('sent', 'delivered', 'opened')";
            $total_stmt = $pdo->prepare($total_sql);
            $total_stmt->execute([$user_id]);
            $total = $total_stmt->fetch()['total'];
            $total_stmt = NULL;
            
            // Unread count
            $unread_sql = "SELECT COUNT(*) as unread 
                          FROM notification_recipients 
                          WHERE user_id = ? 
                          AND opened_at IS NULL 
                          AND delivery_status IN ('sent', 'delivered')";
            $unread_stmt = $pdo->prepare($unread_sql);
            $unread_stmt->execute([$user_id]);
            $unread = $unread_stmt->fetch()['unread'];
            $unread_stmt = NULL;
            
            // Count by type
            $type_sql = "SELECT n.type, COUNT(*) as count 
                        FROM notifications n
                        INNER JOIN notification_recipients nr ON n.id = nr.notification_id
                        WHERE nr.user_id = ? 
                        AND nr.delivery_status IN ('sent', 'delivered', 'opened')
                        GROUP BY n.type";
            $type_stmt = $pdo->prepare($type_sql);
            $type_stmt->execute([$user_id]);
            
            $by_type = [];
            while ($row = $type_stmt->fetch()) {
                $by_type[$row['type']] = (int)$row['count'];
            }
            $type_stmt = NULL;
            
            return [
                'total' => (int)$total,
                'unread' => (int)$unread,
                'by_type' => $by_type
            ];
        } catch (Exception $e) {
            log_message('error', 'Error getting user notification stats: ' . $e->getMessage());
            return [
                'total' => 0,
                'unread' => 0,
                'by_type' => []
            ];
        }
    }

    /**
     * Mark all notifications as read for a user
     */
    public function mark_all_notifications_read($user_id) {
        try {
            $sql = "UPDATE notification_recipients 
                    SET delivery_status = 'opened', opened_at = ? 
                    WHERE user_id = ? 
                    AND opened_at IS NULL 
                    AND delivery_status IN ('sent', 'delivered')";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $result = $statement->execute([date('Y-m-d H:i:s'), $user_id]);
            $affected_rows = $statement->rowCount();
            $statement = NULL;

            return $affected_rows;
        } catch (Exception $e) {
            log_message('error', 'Error marking all notifications as read: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Soft delete notification for a user
     */
    public function delete_user_notification($notification_id, $user_id) {
        try {
            // For now, we'll just mark it as read and add a flag
            // In future, add deleted_by_user column to notification_recipients table
            $sql = "DELETE FROM notification_recipients 
                    WHERE notification_id = ? AND user_id = ?";
            
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            $result = $statement->execute([$notification_id, $user_id]);
            $affected_rows = $statement->rowCount();
            $statement = NULL;

            return $affected_rows > 0;
        } catch (Exception $e) {
            log_message('error', 'Error deleting user notification: ' . $e->getMessage());
            return false;
        }
    }
}
