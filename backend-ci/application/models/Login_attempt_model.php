<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login_attempt_model extends CI_Model {
    private $table = 'login_attempts';

    /**
     * Ghi lại lần đăng nhập
     */
    public function record($username, $success = false, $failure_reason = null) {
        $data = [
            'username' => $username,
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent(),
            'success' => $success ? 1 : 0,
            'failure_reason' => $failure_reason
        ];
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Đếm số lần đăng nhập sai trong khoảng thời gian
     * @param string $username
     * @param int $minutes Số phút gần đây
     * @return int
     */
    public function count_failed_attempts($username, $minutes = 15) {
        $time_limit = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));
        return $this->db->where('username', $username)
            ->where('success', 0)
            ->where('attempted_at >=', $time_limit)
            ->count_all_results($this->table);
    }

    /**
     * Lấy lịch sử đăng nhập theo username
     */
    public function get_history($username, $limit = 20) {
        return $this->db->where('username', $username)
            ->order_by('attempted_at', 'DESC')
            ->limit($limit)
            ->get($this->table)
            ->result_array();
    }
}