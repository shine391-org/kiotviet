<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {
    private $table = 'users';

    public function get_all() {
        return $this->db->get($this->table)->result_array();
    }
    public function get_by_id($id) {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }
    public function get_by_username($username) {
        return $this->db
            ->select('id, username, password, full_name, email, phone, avatar, branch_id, status')
            ->where('username', $username)
            ->where('deleted_at IS NULL')
            ->get($this->table)
            ->row_array();
    }
    public function insert($data) {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }
    public function update($id, $data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        return $this->db->where('id', $id)->update($this->table, $data);
    }
    public function delete($id) {
        return $this->db->where('id', $id)->delete($this->table);
    }
    // Lấy danh sách các vai trò (role) của user
    public function get_user_roles($user_id) {
        $this->db->select('r.name');
        $this->db->from('roles r');
        $this->db->join('model_has_roles mr', 'mr.role_id = r.id');
        $this->db->where('mr.model_id', $user_id);
        return $this->db->get()->result_array();
    }
    public function revoke_all_roles($user_id) {
        $this->db->where('model_id', $user_id)->delete('model_has_roles');
    }
    public function assign_role($user_id, $role_id) {
        $this->db->insert('model_has_roles', ['model_id' => $user_id, 'role_id' => $role_id]);
    }
}