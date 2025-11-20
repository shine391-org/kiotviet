<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth_hook
{
    public function checkAuth()
    {
        $CI = &get_instance();
        $CI->load->library('JwtAuth');
        $user = $CI->jwtauth->validateToken();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            exit();
        }
        $CI->current_user = $user;
    }
}