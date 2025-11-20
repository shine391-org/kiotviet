<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Branch_model extends CI_Model {

    // Tên bảng dữ liệu
    private $table = 'branches';

    /**
     * Lấy danh sách chi nhánh với các tham số phân trang, lọc, sắp xếp
     * @param array $params Mảng các tham số lọc, phân trang
     * @return array Mảng chứa 'total' tổng bản ghi và 'data' danh sách dữ liệu
     */
    public function get_branches($params = []) {
        $search = isset($params['search']) ? $params['search'] : null;              // Tìm kiếm theo tên
        $status = isset($params['status']) ? $params['status'] : null;              // Lọc theo trạng thái
        $page = isset($params['page']) ? (int)$params['page'] : 1;                  // Trang hiện tại
        $limit = isset($params['limit']) ? (int)$params['limit'] : 10;              // Số bản ghi trên trang
        $sort_by = isset($params['sort_by']) ? $params['sort_by'] : 'id';           // Cột sắp xếp
        $sort_order = (isset($params['sort_order']) && strtolower($params['sort_order']) == 'desc') ? 'desc' : 'asc'; // Thứ tự sắp xếp

        $offset = ($page - 1) * $limit;  // Tính bắt đầu lấy bản ghi

        $this->db->start_cache();        // Bắt đầu cache query

        // Thêm điều kiện tìm kiếm theo tên
        if (!empty($search)) {
            $this->db->like('name', $search);
        }
        // Thêm điều kiện lọc theo trạng thái
        if (!empty($status)) {
            $this->db->where('status', $status);
        }

        $this->db->stop_cache();         // Dừng cache query, sẵn sàng cho count và phân trang

        // Lấy tổng số record phù hợp để phân trang
        $total = $this->db->count_all_results($this->table);

        // Thực hiện truy vấn lấy dữ liệu theo phân trang và sắp xếp
        $this->db->order_by($sort_by, $sort_order);
        $this->db->limit($limit, $offset);
        $query = $this->db->get($this->table);

        // Xóa cache query
        $this->db->flush_cache();

        // Trả về tổng bản ghi và dữ liệu dạng mảng
        return [
            'total' => $total,
            'data' => $query->result_array()
        ];
    }

    /**
     * Lấy chi nhánh theo id
     * @param int $id 
     * @return array|null
     */
    public function get_by_id($id) {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    /**
     * Thêm mới chi nhánh
     * @param array $data 
     * @return int ID của chi nhánh vừa thêm
     */
    public function insert($data) {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Cập nhật chi nhánh
     * @param int $id 
     * @param array $data 
     * @return bool
     */
    public function update($id, $data) {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    /**
     * Xóa chi nhánh
     * @param int $id 
     * @return bool
     */
    public function delete($id) {
        return $this->db->where('id', $id)->delete($this->table);
    }

    // Đặt chi nhánh mặc định
    public function set_default($branch_id) {
    // Bỏ mặc định tất cả chi nhánh
        $this->db->update('branches', ['is_default' => 0]);
    // Đặt chi nhánh mới làm mặc định
        $this->db->where('id', $branch_id);
        return $this->db->update('branches', ['is_default' => 1]);
    }

    // Kiểm tra xem chi nhánh có phải mặc định không
    public function is_default_branch($branch_id) {
        $branch = $this->db->get_where('branches', ['id' => $branch_id])->row_array();
        return $branch && $branch['is_default'] == 1;
    }

    // Đếm số chi nhánh hoạt động
    public function count_active_branches() {
        return $this->db->where('status', 'active')->count_all_results('branches');
    }
    /**
    * Đếm tổng số chi nhánh (bao gồm cả active và inactive)
    * @return int
    */
    public function count_all() {
        return $this->db->count_all_results('branches');
    }

    // Lấy chi nhánh mặc định
    public function get_default_branch() {
        return $this->db->get_where('branches', ['is_default' => 1])->row_array();
    }
}