<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Permissions Controller
 * Quản lý permissions trong hệ thống
 */
class Permissions extends MY_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('Permission_model');
    }
    
    /**
     * GET /api/permissions
     * Lấy tất cả permissions, grouped by module
     */
    public function index() {
        $this->requirePermission('roles.view');
        
        try {
            // Lấy permissions grouped theo module
            $grouped_permissions = $this->Permission_model->get_all_grouped();
            
            $this->_jsonResponse([
                'success' => true,
                'data' => $grouped_permissions
            ], 200);
            
        } catch (Exception $e) {
            $this->_jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/permissions/:id
     * Lấy permission theo ID
     */
    public function show($id) {
        $this->requirePermission('roles.view');
        
        try {
            $permission = $this->Permission_model->get_by_id($id);
            
            if (!$permission) {
                $this->_jsonResponse(['message' => 'Permission not found'], 404);
                return;
            }
            
            $this->_jsonResponse([
                'success' => true,
                'data' => $permission
            ], 200);
            
        } catch (Exception $e) {
            $this->_jsonResponse([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}