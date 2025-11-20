<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Library giúp ghi log hoạt động dễ dàng
 */
class ActivityLogger {
    protected $CI;

    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->model('Activity_log_model');
    }

    /**
     * Ghi log hoạt động
     * @param string $action Tên hành động (create, update, delete...)
     * @param string $module Tên module (user, branch, product...)
     * @param mixed $model_id ID của model
     * @param array $old_values Giá trị cũ (trước khi thay đổi)
     * @param array $new_values Giá trị mới (sau khi thay đổi)
     */
    public function log($action, $module, $model_id = null, $old_values = null, $new_values = null) {
        $user_id = isset($this->CI->current_user['id']) ? $this->CI->current_user['id'] : null;
        
        $log_data = [
            'user_id' => $user_id,
            'action' => $action,
            'module' => $module,
            'model_type' => $module,
            'model_id' => $model_id,
            'old_values' => $old_values,
            'new_values' => $new_values
        ];

        return $this->CI->Activity_log_model->log($log_data);
    }

    /**
     * Log tạo mới
     */
    public function created($module, $model_id, $data) {
        return $this->log('create', $module, $model_id, null, $data);
    }

    /**
     * Log cập nhật
     */
    public function updated($module, $model_id, $old_data, $new_data) {
        return $this->log('update', $module, $model_id, $old_data, $new_data);
    }

    /**
     * Log xóa
     */
    public function deleted($module, $model_id, $old_data) {
        return $this->log('delete', $module, $model_id, $old_data, null);
    }

    /**
     * Log login
     */
    public function login($user_id) {
        return $this->log('login', 'auth', $user_id);
    }

    /**
     * Log logout
     */
    public function logout($user_id) {
        return $this->log('logout', 'auth', $user_id);
    }
}