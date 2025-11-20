<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rate Limiter - Giới hạn số lần thử đăng nhập
 */
class RateLimiter {
    
    protected $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('Login_attempt_model');
    }
    
    /**
     * 🆕 METHOD MỚI - Check if allowed (inverse of is_rate_limited)
     * @param string $action - Action name (e.g. 'login')
     * @param string $key - Identifier (username, IP, etc.)
     * @param int $max_attempts - Max attempts allowed
     * @param int $within_minutes - Time window in minutes
     * @return bool - TRUE if allowed, FALSE if rate limited
     */
    public function check($action, $key, $max_attempts = 5, $within_minutes = 15) {
        // Return TRUE if NOT rate limited (allowed)
        return !$this->is_rate_limited($key, $max_attempts, $within_minutes);
    }
    
    /**
     * Kiểm tra có vượt quá giới hạn không
     * @param string $key Khóa định danh (username, ip...)
     * @param int $max_attempts Số lần tối đa
     * @param int $within_minutes Trong khoảng thời gian (phút)
     * @return bool true nếu vượt quá giới hạn
     */
    public function is_rate_limited($key, $max_attempts = 5, $within_minutes = 15) {
        $failed_count = $this->CI->Login_attempt_model->count_failed_attempts($key, $within_minutes);
        return $failed_count >= $max_attempts;
    }
    
    /**
     * Lấy thời gian còn lại phải đợi (giây)
     */
    public function get_retry_after($key, $within_minutes = 15) {
        return $within_minutes * 60; // Trả về số giây
    }
    
    /**
     * Ghi nhận thất bại
     */
    public function record_failure($username, $reason = null) {
        return $this->CI->Login_attempt_model->record($username, false, $reason);
    }
    
    /**
     * Ghi nhận thành công
     */
    public function record_success($username) {
        return $this->CI->Login_attempt_model->record($username, true);
    }
    
    /**
     * 🆕 XÓA RATE LIMIT CHO USER (để unlock)
     * @param string $key - Username or identifier
     */
    public function clear($key) {
        // Delete all login attempts for this key
        return $this->CI->Login_attempt_model->clear_attempts($key);
    }
}