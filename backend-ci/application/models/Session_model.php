<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Session_model extends CI_Model {
    private $table = 'sessions';

    /**
     * Tạo session mới
     */
    public function create($user_id, $token, $device_info = null, $expires_in_hours = 24) {
        $data = [
            'user_id' => $user_id,
            'token' => $token,
            'device_info' => $device_info,
            'ip_address' => $this->input->ip_address(),
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$expires_in_hours} hours"))
        ];
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Kiểm tra token còn hợp lệ
     */
    public function validate_token($token) {
        $session = $this->db->where('token', $token)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get($this->table)
            ->row_array();
        
        if ($session) {
            // Cập nhật last_activity
            $this->db->where('id', $session['id'])
                ->update($this->table, ['last_activity' => date('Y-m-d H:i:s')]);
        }
        return $session;
    }

    /**
     * Lấy tất cả session của user
     */
    public function get_user_sessions($user_id) {
        return $this->db->where('user_id', $user_id)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->order_by('last_activity', 'DESC')
            ->get($this->table)
            ->result_array();
    }

    /**
     * Xóa session (logout)
     */
    public function delete_session($token) {
        return $this->db->where('token', $token)->delete($this->table);
    }

    /**
     * Xóa tất cả session của user trừ session hiện tại
     */
    public function delete_other_sessions($user_id, $current_token) {
        return $this->db->where('user_id', $user_id)
            ->where('token !=', $current_token)
            ->delete($this->table);
    }

    /**
     * Xóa session đã hết hạn
     */
    public function clean_expired_sessions() {
        return $this->db->where('expires_at <', date('Y-m-d H:i:s'))
            ->delete($this->table);
    }
}