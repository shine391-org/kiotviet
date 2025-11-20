<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH.'third_party/firebase_php_jwt/JWTExceptionWithPayloadInterface.php');
require_once(APPPATH.'third_party/firebase_php_jwt/JWT.php');
require_once(APPPATH.'third_party/firebase_php_jwt/Key.php');
require_once(APPPATH.'third_party/firebase_php_jwt/ExpiredException.php');
require_once(APPPATH.'third_party/firebase_php_jwt/BeforeValidException.php');
require_once(APPPATH.'third_party/firebase_php_jwt/SignatureInvalidException.php');

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class JwtAuth {
    
    protected $CI;
    protected $key;
    protected $currentUser;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->key = 'ffb4430639648be05bdcc19a454586c%';
        $this->CI->load->model('Session_model');
        $this->CI->load->model('Role_permission_model');
    }
    
    public function generateToken($user, $device_info = null, $expire_hours = 24) {
        if (!isset($user['user_id'])) {
            throw new Exception('User ID is required for token generation');
        }
        
        $iat = time();
        $exp = $iat + ($expire_hours * 3600);
        
        $payload = [
            'iat' => $iat,
            'exp' => $exp,
            'data' => [
                'id' => $user['user_id'],
                'username' => $user['username'],
                'role' => $user['role'] ?? '',
                'branch_id' => $user['branch_id'] ?? null
            ]
        ];
        
        $token = JWT::encode($payload, $this->key, 'HS256');
        $this->CI->Session_model->create($user['user_id'], $token, $device_info, $expire_hours);
        
        return $token;
    }
    
    public function validateToken() {
        $headers = $this->CI->input->get_request_header('Authorization');
        if (!$headers) {
            return false;
        }
        
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            $token = $matches[1];
        } else {
            $token = $headers;
        }
        
        try {
            $decoded = JWT::decode($token, new Key($this->key, 'HS256'));
            $session = $this->CI->Session_model->validate_token($token);
            if (!$session) {
                return false;
            }
            $this->currentUser = (array) $decoded->data;
            return $this->currentUser;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getTokenFromHeader() {
        $headers = $this->CI->input->get_request_header('Authorization');
        if (!$headers) {
            return false;
        }
        
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
        
        return $headers;
    }
    
    public function revokeToken($token = null) {
        if ($token === null) {
            $token = $this->getTokenFromHeader();
        }
        
        if (!$token) {
            return false;
        }
        
        return $this->CI->Session_model->delete_session($token);
    }
    
    /**
     * ✅ FIX: Sử dụng $this->validateToken() CHÍNH XÁC
     */
    public function requirePermission($permission) {
        // ✅ GỌI $this->validateToken() - ĐÚNG CONTEXT!
        $user = $this->validateToken();
        
        if (!$user) {
            $this->_jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            exit;
        }
        
        // Super admin bypass
        if (isset($user['role']) && $user['role'] === 'super-admin') {
            return true;
        }
        
        // ✅ SỬ DỤNG $this->CI-> để truy cập model
        $permissions = $this->CI->Role_permission_model->getUserPermissions($user['id']);
        
        $has_permission = false;
        foreach ($permissions as $perm) {
            if (isset($perm['name']) && $perm['name'] === $permission) {
                $has_permission = true;
                break;
            }
        }
        
        if (!$has_permission) {
            $this->_jsonResponse([
                'success' => false,
                'message' => 'Bạn không có quyền thực hiện hành động này'
            ], 403);
            exit;
        }
        
        return true;
    }
    
    /**
     * Get current user from validated token
     */
    public function getCurrentUser() {
        if ($this->currentUser) {
            return $this->currentUser;
        }
        return $this->validateToken();
    }
    
    /**
     * JSON Response helper
     */
    protected function _jsonResponse($data, $status = 200) {
        $this->CI->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;
    }
}
