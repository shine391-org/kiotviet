<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Permission_model extends CI_Model {
    
    protected $table = 'permissions';
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Get all permissions grouped by module
     * @return array
     */
    public function get_all_grouped() {
        $this->db->select('id, name, display_name, module, description');
        $this->db->from($this->table);
        $this->db->where('deleted_at IS NULL');
        $this->db->order_by('module', 'ASC');
        $this->db->order_by('display_name', 'ASC');
        
        $query = $this->db->get();
        $permissions = $query->result_array();
        
        // Group by module
        $grouped = [];
        foreach ($permissions as $permission) {
            $module = $permission['module'] ?: 'Other';
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $permission;
        }
        
        return $grouped;
    }
    
    /**
     * Get all permissions (flat array)
     * @return array
     */
    public function get_all() {
        $this->db->select('id, name, display_name, module, description');
        $this->db->from($this->table);
        $this->db->where('deleted_at IS NULL');
        $this->db->order_by('display_name', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }
    
    /**
     * Get permission by ID
     * @param int $id
     * @return array|null
     */
    public function get_by_id($id) {
        $this->db->where('id', $id);
        $this->db->where('deleted_at IS NULL');
        $query = $this->db->get($this->table);
        return $query->row_array();
    }
}