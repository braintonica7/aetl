<?php

class Subscription_plan_model extends CI_Model {

    public function get_subscription_plan($id) {
        $objPlan = NULL;
        $sql = "SELECT * FROM subscription_plans WHERE id = ?";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($id));
        
        if ($row = $statement->fetch()) {
            $objPlan = new Subscription_plan_object();
            $objPlan->id = $row['id'];
            $objPlan->plan_key = $row['plan_key'];
            $objPlan->plan_name = $row['plan_name'];
            $objPlan->plan_description = $row['plan_description'];
            $objPlan->monthly_price_inr = $row['monthly_price_inr'];
            $objPlan->academic_session_price_inr = $row['academic_session_price_inr'];
            $objPlan->is_free = $row['is_free'] == 1;
            $objPlan->is_default = $row['is_default'] == 1;
            $objPlan->is_active = $row['is_active'] == 1;
            $objPlan->sort_order = $row['sort_order'];
            $objPlan->plan_color = $row['plan_color'];
            $objPlan->created_at = $row['created_at'];
            $objPlan->updated_at = $row['updated_at'];
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $objPlan;
    }

    public function get_subscription_plan_by_key($plan_key) {
        $objPlan = NULL;
        $sql = "SELECT * FROM subscription_plans WHERE plan_key = ? AND is_active = 1";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($plan_key));
        
        if ($row = $statement->fetch()) {
            $objPlan = new Subscription_plan_object();
            $objPlan->id = $row['id'];
            $objPlan->plan_key = $row['plan_key'];
            $objPlan->plan_name = $row['plan_name'];
            $objPlan->plan_description = $row['plan_description'];
            $objPlan->monthly_price_inr = $row['monthly_price_inr'];
            $objPlan->academic_session_price_inr = $row['academic_session_price_inr'];
            $objPlan->is_free = $row['is_free'] == 1;
            $objPlan->is_default = $row['is_default'] == 1;
            $objPlan->is_active = $row['is_active'] == 1;
            $objPlan->sort_order = $row['sort_order'];
            $objPlan->plan_color = $row['plan_color'];
            $objPlan->created_at = $row['created_at'];
            $objPlan->updated_at = $row['updated_at'];
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $objPlan;
    }

    public function get_all_active_plans() {
        $plans = array();
        $sql = "SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order ASC";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        
        while ($row = $statement->fetch()) {
            $objPlan = new Subscription_plan_object();
            $objPlan->id = $row['id'];
            $objPlan->plan_key = $row['plan_key'];
            $objPlan->plan_name = $row['plan_name'];
            $objPlan->plan_description = $row['plan_description'];
            $objPlan->monthly_price_inr = $row['monthly_price_inr'];
            $objPlan->academic_session_price_inr = $row['academic_session_price_inr'];
            $objPlan->is_free = $row['is_free'] == 1;
            $objPlan->is_default = $row['is_default'] == 1;
            $objPlan->is_active = $row['is_active'] == 1;
            $objPlan->sort_order = $row['sort_order'];
            $objPlan->plan_color = $row['plan_color'];
            $objPlan->created_at = $row['created_at'];
            $objPlan->updated_at = $row['updated_at'];
            $plans[] = $objPlan;
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $plans;
    }

    public function get_default_plan() {
        $objPlan = NULL;
        $sql = "SELECT * FROM subscription_plans WHERE is_default = 1 AND is_active = 1 LIMIT 1";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        
        if ($row = $statement->fetch()) {
            $objPlan = new Subscription_plan_object();
            $objPlan->id = $row['id'];
            $objPlan->plan_key = $row['plan_key'];
            $objPlan->plan_name = $row['plan_name'];
            $objPlan->plan_description = $row['plan_description'];
            $objPlan->monthly_price_inr = $row['monthly_price_inr'];
            $objPlan->academic_session_price_inr = $row['academic_session_price_inr'];
            $objPlan->is_free = $row['is_free'] == 1;
            $objPlan->is_default = $row['is_default'] == 1;
            $objPlan->is_active = $row['is_active'] == 1;
            $objPlan->sort_order = $row['sort_order'];
            $objPlan->plan_color = $row['plan_color'];
            $objPlan->created_at = $row['created_at'];
            $objPlan->updated_at = $row['updated_at'];
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $objPlan;
    }

    public function get_plan_features($plan_id) {
        $features = array();
        $sql = "SELECT spf.*, sfd.feature_key, sfd.feature_name, sfd.feature_description, sfd.feature_type, sfd.reset_cycle 
                FROM subscription_plan_features spf 
                JOIN subscription_feature_definitions sfd ON spf.feature_id = sfd.id 
                WHERE spf.plan_id = ? AND spf.is_enabled = 1 AND sfd.is_active = 1 
                ORDER BY sfd.sort_order ASC";
        $pdo = CDatabase::getPdo();
        $statement = $pdo->prepare($sql);
        $statement->execute(array($plan_id));
        
        while ($row = $statement->fetch()) {
            $feature = array(
                'feature_id' => $row['feature_id'],
                'feature_key' => $row['feature_key'],
                'feature_name' => $row['feature_name'],
                'feature_description' => $row['feature_description'],
                'feature_type' => $row['feature_type'],
                'reset_cycle' => $row['reset_cycle'],
                'feature_limit' => $row['feature_limit'],
                'is_enabled' => $row['is_enabled'] == 1
            );
            $features[] = $feature;
        }
        
        $statement = NULL;
        $pdo = NULL;
        return $features;
    }

    public function add_subscription_plan($objPlan) {
        try {
            $sql = "INSERT INTO subscription_plans (plan_key, plan_name, plan_description, monthly_price_inr, academic_session_price_inr, is_free, is_default, is_active, sort_order, plan_color) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            
            $result = $statement->execute(array(
                $objPlan->plan_key,
                $objPlan->plan_name,
                $objPlan->plan_description,
                $objPlan->monthly_price_inr,
                $objPlan->academic_session_price_inr,
                $objPlan->is_free ? 1 : 0,
                $objPlan->is_default ? 1 : 0,
                $objPlan->is_active ? 1 : 0,
                $objPlan->sort_order,
                $objPlan->plan_color
            ));
            
            if ($result) {
                $objPlan->id = $pdo->lastInsertId();
                $statement = NULL;
                $pdo = NULL;
                return $objPlan;
            }
            
            $statement = NULL;
            $pdo = NULL;
            return FALSE;
            
        } catch (Exception $e) {
            log_message('error', "Subscription plan creation error: " . $e->getMessage());
            return FALSE;
        }
    }

    public function update_subscription_plan($objPlan) {
        try {
            $sql = "UPDATE subscription_plans SET 
                    plan_name = ?, plan_description = ?, monthly_price_inr = ?, 
                    academic_session_price_inr = ?, is_free = ?, is_default = ?, 
                    is_active = ?, sort_order = ?, plan_color = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            
            $result = $statement->execute(array(
                $objPlan->plan_name,
                $objPlan->plan_description,
                $objPlan->monthly_price_inr,
                $objPlan->academic_session_price_inr,
                $objPlan->is_free ? 1 : 0,
                $objPlan->is_default ? 1 : 0,
                $objPlan->is_active ? 1 : 0,
                $objPlan->sort_order,
                $objPlan->plan_color,
                $objPlan->id
            ));
            
            $statement = NULL;
            $pdo = NULL;
            return $result;
            
        } catch (Exception $e) {
            log_message('error', "Subscription plan update error: " . $e->getMessage());
            return FALSE;
        }
    }

    public function delete_subscription_plan($plan_id) {
        try {
            // Soft delete by setting is_active = 0
            $sql = "UPDATE subscription_plans SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $pdo = CDatabase::getPdo();
            $statement = $pdo->prepare($sql);
            
            $result = $statement->execute(array($plan_id));
            
            $statement = NULL;
            $pdo = NULL;
            return $result;
            
        } catch (Exception $e) {
            log_message('error', "Subscription plan deletion error: " . $e->getMessage());
            return FALSE;
        }
    }
}