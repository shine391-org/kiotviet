<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Validate username
 */
if (!function_exists('validate_username')) {
    function validate_username($username) {
        return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
    }
}

/**
 * Validate password mạnh
 */
if (!function_exists('validate_strong_password')) {
    function validate_strong_password($password) {
        // Tối thiểu 8 ký tự, có chữ hoa, chữ thường, số
        return strlen($password) >= 8 
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password);
    }
}

/**
 * Validate số điện thoại Việt Nam
 */
if (!function_exists('validate_vietnam_phone')) {
    function validate_vietnam_phone($phone) {
        // Loại bỏ khoảng trắng, dấu gạch ngang
        $phone = preg_replace('/[\s\-]/', '', $phone);
        // Kiểm tra format: 10-11 số, bắt đầu bằng 0
        return preg_match('/^0[0-9]{9,10}$/', $phone);
    }
}

/**
 * Validate email
 */
if (!function_exists('validate_email_format')) {
    function validate_email_format($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

/**
 * Sanitize string đầu vào
 */
if (!function_exists('sanitize_input')) {
    function sanitize_input($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
}