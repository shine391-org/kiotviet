<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Activity_log_model extends CI_Model {
    private $table = 'activity_logs';

    /**
     * Ghi log hoạt động
     * @param array $data Dữ liệu log
     * @return int ID của log vừa tạo
     */
    public function log($data) {
        $log_data = [
            'user_id' => $data['user_id'] ?? null,
            'action' => $data['action'] ?? '',
            'module' => $data['module'] ?? '',
            'model_type' => $data['model_type'] ?? null,
            'model_id' => $data['model_id'] ?? null,
            'old_values' => isset($data['old_values']) ? json_encode($data['old_values']) : null,
            'new_values' => isset($data['new_values']) ? json_encode($data['new_values']) : null,
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent()
        ];
        $this->db->insert($this->table, $log_data);
        return $this->db->insert_id();
    }

    /**
     * Lấy logs theo user với phân trang
     */
    public function get_by_user($user_id, $limit = 50, $offset = 0) {
        return $this->db->where('user_id', $user_id)
            ->order_by('created_at', 'DESC')
            ->limit($limit, $offset)
            ->get($this->table)
            ->result_array();
    }

    /**
     * Lấy logs theo module và model
     */
    public function get_by_model($module, $model_type, $model_id) {
        return $this->db->where([
            'module' => $module,
            'model_type' => $model_type,
            'model_id' => $model_id
        ])->order_by('created_at', 'DESC')
          ->get($this->table)
          ->result_array();
    }
}