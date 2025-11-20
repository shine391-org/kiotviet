<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Password_history_model extends CI_Model {
    private $table = 'password_history';

    /**
     * Lưu mật khẩu vào lịch sử
     */
    public function add($user_id, $password_hash) {
        $data = [
            'user_id' => $user_id,
            'password_hash' => $password_hash
        ];
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Kiểm tra mật khẩu đã từng dùng chưa
     * @param int $user_id
     * @param string $password Mật khẩu plain text
     * @param int $limit Kiểm tra N mật khẩu gần nhất
     * @return bool
     */
    public function is_password_used($user_id, $password, $limit = 5) {
        $history = $this->db->where('user_id', $user_id)
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get($this->table)
            ->result_array();

        foreach ($history as $record) {
            if (password_verify($password, $record['password_hash'])) {
                return true; // Mật khẩu đã từng dùng
            }
        }
        return false;
    }

    /**
     * Lấy lịch sử mật khẩu của user
     */
    public function get_history($user_id, $limit = 10) {
        return $this->db->where('user_id', $user_id)
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get($this->table)
            ->result_array();
    }
}