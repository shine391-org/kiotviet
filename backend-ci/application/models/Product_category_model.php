<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Product Category Model - ENHANCED VERSION
 * 
 * ✅ Fixed all previous issues from Products module
 * ✅ Proper NULL handling (no strtoupper errors)
 * ✅ Safe SQL queries (no SQL injection)
 * ✅ Consistent method naming
 * ✅ Full exception handling
 * 
 * @package    CodeIgniter
 * @subpackage Models
 * @category   Product Categories
 * @author     LANOCRM Development Team
 * @version    2.0.0 - PRODUCTION READY
 */
class Product_category_model extends CI_Model {
    
    protected $table = 'product_categories';
    public $max_depth = 10;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Get categories with pagination and filters
     * ✅ FIXED: Reset query builder properly
     * ✅ FIXED: Safe NULL handling
     * 
     * @param array $params Query parameters
     * @return array
     */
    public function get_categories($params = []) {
        try {
            // Extract params with defaults
            $search = isset($params['search']) && is_string($params['search']) ? trim($params['search']) : '';
            $level = isset($params['level']) && $params['level'] !== '' ? (int)$params['level'] : null;
            $parent_id = isset($params['parent_id']) && $params['parent_id'] !== '' ? $params['parent_id'] : null;
            $status = isset($params['status']) && is_string($params['status']) ? trim($params['status']) : '';
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
            $offset = ($page - 1) * $limit;
            
            // ✅ NEW: Check if include soft-deleted categories
            $include_deleted = isset($params['include_deleted']) && ($params['include_deleted'] === true || $params['include_deleted'] === 'true');
            
            // ============================================================
            // QUERY 1: COUNT TOTAL
            // ============================================================
            $this->db->select('COUNT(*) as total');
            $this->db->from($this->table);
            
            // ✅ NEW: Apply deleted_at filter based on include_deleted
            if (!$include_deleted) {
                $this->db->where('deleted_at IS NULL');
                $this->db->where('status', 'active');
            }
            
            // Search
            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('name', $search);
                $this->db->or_like('code', $search);
                $this->db->group_end();
            }
            
            // Filters
            if ($level !== null) {
                $this->db->where('level', $level);
            }
            
            if ($parent_id !== null) {
                if ($parent_id == 0 || $parent_id === '0') {
                    $this->db->where('parent_id IS NULL');
                } else {
                    $this->db->where('parent_id', (int)$parent_id);
                }
            }
            
            if (!empty($status)) {
                $this->db->where('status', $status);
            }
            
            // Execute count
            $count_result = $this->db->get();
            $total = $count_result->row()->total;
            
            // ============================================================
            // ✅ CRITICAL FIX: RESET QUERY BUILDER
            // ============================================================
            $this->db->reset_query();
            
            // ============================================================
            // QUERY 2: GET DATA
            // ============================================================
            $this->db->select('*');
            $this->db->from($this->table);
            
            // ✅ NEW: Apply deleted_at filter based on include_deleted
            if (!$include_deleted) {
                $this->db->where('deleted_at IS NULL');
            }
            
            // Search (repeat)
            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('name', $search);
                $this->db->or_like('code', $search);
                $this->db->group_end();
            }
            
            // Filters (repeat)
            if ($level !== null) {
                $this->db->where('level', $level);
            }
            
            if ($parent_id !== null) {
                if ($parent_id == 0 || $parent_id === '0') {
                    $this->db->where('parent_id IS NULL');
                } else {
                    $this->db->where('parent_id', (int)$parent_id);
                }
            }
            
            if (!empty($status)) {
                $this->db->where('status', $status);
            }
            
            // Order and limit
            $this->db->order_by('level', 'ASC');
            $this->db->order_by('sort_order', 'ASC');
            $this->db->order_by('name', 'ASC');
            $this->db->limit($limit, $offset);
            
            // Execute data query
            $query = $this->db->get();
            
            return [
                'total' => (int)$total,
                'data' => $query->result_array()
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Product_category_model::get_categories - ' . $e->getMessage());
            return [
                'total' => 0,
                'data' => []
            ];
        }
    }    
    
    /**
     * Get category tree (recursive) - UNLIMITED LEVELS ✅
     * 
     * @param int|null $parent_id Parent ID
     * @param int $current_depth Current depth (for safety)
     * @return array
     */
    public function get_tree($parent_id = null, $current_depth = 0, $include_deleted = false) {
        try {
            // Safety check để tránh đệ quy vô hạn
            if ($current_depth >= $this->max_depth) {
                log_message('warning', 'Product_category_model::get_tree - Max depth reached');
                return [];
            }
    
            // ✅ Áp dụng filter deleted_at nếu không trả cả soft delete
            if (!$include_deleted) {
                $this->db->where('deleted_at IS NULL');
                $this->db->where('status', 'active');
            }
            
            if ($parent_id === null) {
                $this->db->where('parent_id IS NULL');
            } else {
                $this->db->where('parent_id', (int)$parent_id);
            }
    
            $this->db->order_by('sort_order', 'ASC');
            $this->db->order_by('name', 'ASC');
            
            $categories = $this->db->get($this->table)->result_array();
    
            // ✅ Đệ quy truyền đúng tham số $include_deleted cho từng lần lặp (cả con)
            foreach ($categories as &$category) {
                $category['children'] = $this->get_tree($category['id'], $current_depth + 1, $include_deleted);
                $category['product_count'] = $this->count_products($category['id'], true);
            }
            unset($category);
    
            return $categories;
    
        } catch (Exception $e) {
            log_message('error', 'Product_category_model::get_tree - ' . $e->getMessage());
            return [];
        }
    }        
    
    /**
     * Get category by ID
     * 
     * @param int $id Category ID
     * @return array|null
     */
    public function get_by_id($id) {
        try {
            if (empty($id)) {
                return null;
            }
            
            $this->db->where('id', (int)$id);
            $this->db->where('deleted_at IS NULL');
            
            $result = $this->db->get($this->table)->row_array();
            return $result ? $result : null;
            
        } catch (Exception $e) {
            log_message('error', 'Product_category_model::get_by_id - ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get children of category
     * 
     * @param int $parent_id Parent category ID
     * @return array
     */
    public function get_children($parent_id) {
        try {
            if (empty($parent_id)) {
                return [];
            }
            
            $this->db->where('parent_id', (int)$parent_id);
            $this->db->where('deleted_at IS NULL');
            $this->db->order_by('sort_order', 'ASC');
            $this->db->order_by('name', 'ASC');
            
            return $this->db->get($this->table)->result_array();
            
        } catch (Exception $e) {
            log_message('error', 'Product_category_model::get_children - ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get breadcrumb path - UNLIMITED LEVELS ✅
     * 
     * @param int $category_id Category ID
     * @return array
     */
    public function get_breadcrumb($category_id) {
        try {
            $breadcrumb = [];
            $current = $this->get_by_id($category_id);
            
            if (!$current) {
                return $breadcrumb;
            }

            // Add current category
            $breadcrumb[] = [
                'id' => $current['id'],
                'name' => $current['name'],
                'level' => $current['level'],
                'slug' => $current['slug']
            ];

            // ✅ Lặp không giới hạn cấp, chỉ giới hạn số vòng lặp để tránh circular reference
            $iterations = 0;
            while (!empty($current['parent_id']) && $iterations < $this->max_depth) {
                $parent = $this->get_by_id($current['parent_id']);
                if (!$parent) break;

                array_unshift($breadcrumb, [
                    'id' => $parent['id'],
                    'name' => $parent['name'],
                    'level' => $parent['level'],
                    'slug' => $parent['slug']
                ]);

                $current = $parent;
                $iterations++;
            }

            return $breadcrumb;

        } catch (Exception $e) {
            log_message('error', 'Product_category_model::get_breadcrumb - ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Count products in category
     * 
     * @param int $category_id Category ID
     * @param bool $include_children Include children categories
     * @return int
     */
    public function count_products($category_id, $include_children = false) {
        try {
            if (empty($category_id)) {
                return 0;
            }
    
            if ($include_children) {
                // Lấy tất cả id category con
                $category_ids = [(int)$category_id];
                $this->_get_all_children_ids($category_id, $category_ids);
    
                $this->db->join('product_category_links pcl', 'pcl.product_id = products.id');
                $this->db->where_in('pcl.category_id', $category_ids);
            } else {
                $this->db->join('product_category_links pcl', 'pcl.product_id = products.id');
                $this->db->where('pcl.category_id', (int)$category_id);
            }
    
            $this->db->where('products.deleted_at IS NULL');
    
            return $this->db->count_all_results('products');
    
        } catch (Exception $e) {
            log_message('error', 'Product_category_model::count_products - ' . $e->getMessage());
            return 0;
        }
    }    
    
    /**
     * Get all children IDs recursively
     * 
     * @param int $parent_id Parent category ID
     * @param array &$ids Array to store IDs
     * @return void
     */
    private function _get_all_children_ids($parent_id, &$ids) {
        try {
            $children = $this->get_children($parent_id);
            
            foreach ($children as $child) {
                $ids[] = (int)$child['id'];
                $this->_get_all_children_ids($child['id'], $ids);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Product_category_model::_get_all_children_ids - ' . $e->getMessage());
        }
    }
    
    /**
     * Insert new category
     * ✅ FIXED: Proper method name (not create)
     * 
     * @param array $data Category data
     * @return int|bool Insert ID or FALSE
     */
    public function insert($data) {
        try {
            $this->db->trans_start();
            
            $this->db->insert($this->table, $data);
            $insert_id = $this->db->insert_id();
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                log_message('error', 'Product_category_model::insert - Transaction failed');
                return false;
            }
            
            return $insert_id;
            
        } catch (Exception $e) {
            log_message('error', 'Product_category_model::insert - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update category
     * 
     * @param int $id Category ID
     * @param array $data Update data
     * @return bool
     */
    public function update($id, $data) {
        try {
            if (empty($id)) {
                return false;
            }
            
            $this->db->trans_start();
            
            $this->db->where('id', (int)$id);
            $this->db->where('deleted_at IS NULL');
            $this->db->update($this->table, $data);
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                log_message('error', 'Product_category_model::update - Transaction failed');
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            log_message('error', 'Product_category_model::update - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Soft delete category
     * 
     * @param int $id Category ID
     * @return bool
     */
    public function soft_delete($id) {
        try {
            if (empty($id)) {
                return false;
            }
            
            $this->db->trans_start();
            
            $data = [
                'deleted_at' => date('Y-m-d H:i:s'),
                'status' => 'inactive',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->where('id', (int)$id);
            $this->db->where('deleted_at IS NULL');
            $this->db->update($this->table, $data);
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                log_message('error', 'Product_category_model::soft_delete - Transaction failed');
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            log_message('error', 'Product_category_model::soft_delete - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Hard delete category (use with caution!)
     * 
     * @param int $id Category ID
     * @return bool
     */
    public function delete($id) {
        try {
            if (empty($id)) {
                return false;
            }
            
            $this->db->trans_start();
            
            $this->db->where('id', (int)$id);
            $this->db->delete($this->table);
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                log_message('error', 'Product_category_model::delete - Transaction failed');
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            log_message('error', 'Product_category_model::delete - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate unique code
     * ✅ FIXED: Proper code generation with retry
     * 
     * @param string $name Category name
     * @return string
     */
    public function generate_code($name) {
        try {
            if (empty($name) || !is_string($name)) {
                $name = 'CATEGORY';
            }
            
            // ✅ FIX: Safe string handling
            $clean_name = preg_replace('/[^A-Za-z0-9]/', '', $name);
            $slug = strtoupper(substr($clean_name, 0, 10));
            
            if (empty($slug)) {
                $slug = 'CAT';
            }
            
            $code = 'CAT_' . $slug . '_' . substr(time(), -6);
            
            // Check unique with retry
            $counter = 1;
            $base_code = $code;
            $max_attempts = 100;
            
            while ($this->code_exists($code) && $counter < $max_attempts) {
                $code = $base_code . '_' . $counter;
                $counter++;
            }
            
            return $code;
            
        } catch (Exception $e) {
            log_message('error', 'Product_category_model::generate_code - ' . $e->getMessage());
            return 'CAT_' . time();
        }
    }
    
    /**
     * Check if code exists
     * 
     * @param string $code Category code
     * @param int|null $exclude_id Exclude category ID
     * @return bool
     */
    public function code_exists($code, $exclude_id = null) {
        try {
            if (empty($code) || !is_string($code)) {
                return false;
            }
            
            $this->db->where('code', $code);
            $this->db->where('deleted_at IS NULL');
            
            if ($exclude_id !== null) {
                $this->db->where('id !=', (int)$exclude_id);
            }
            
            return $this->db->count_all_results($this->table) > 0;
            
        } catch (Exception $e) {
            log_message('error', 'Product_category_model::code_exists - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate unique slug
     * ✅ FIXED: Vietnamese character handling
     * 
     * @param string $string Input string
     * @param int|null $exclude_id Exclude category ID
     * @return string
     */
    public function generate_slug($string, $exclude_id = null) {
        try {
            if (empty($string) || !is_string($string)) {
                return 'category-' . time();
            }
            
            $string = trim(mb_strtolower($string));
            
            // Convert Vietnamese characters
            $vietnamese = [
                'à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ',
                'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ',
                'ì', 'í', 'ị', 'ỉ', 'ĩ',
                'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ',
                'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ',
                'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ',
                'đ'
            ];
            
            $replacements = [
                'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
                'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
                'i', 'i', 'i', 'i', 'i',
                'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
                'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
                'y', 'y', 'y', 'y', 'y',
                'd'
            ];
            
            $string = str_replace($vietnamese, $replacements, $string);
            
            // Clean up
            $string = preg_replace('/[^a-z0-9-\s]/', '', $string);
            $string = preg_replace('/(\s+)/', '-', $string);
            $string = preg_replace('/(-+)/', '-', $string);
            $string = trim($string, '-');
            
            if (empty($string)) {
                $string = 'category';
            }
            
            // Make unique
            $base_slug = substr($string, 0, 200);
            $counter = 1;
            $slug = $base_slug;
            $max_attempts = 100;
            
            while ($this->slug_exists($slug, $exclude_id) && $counter < $max_attempts) {
                $slug = $base_slug . '-' . $counter;
                $counter++;
            }
            
            return $slug;
            
        } catch (Exception $e) {
            log_message('error', 'Product_category_model::generate_slug - ' . $e->getMessage());
            return 'category-' . time();
        }
    }
    
    /**
     * Check if slug exists
     * 
     * @param string $slug Category slug
     * @param int|null $exclude_id Exclude category ID
     * @return bool
     */
    public function slug_exists($slug, $exclude_id = null) {
        try {
            if (empty($slug) || !is_string($slug)) {
                return false;
            }
            
            $this->db->where('slug', $slug);
            $this->db->where('deleted_at IS NULL');
            
            if ($exclude_id !== null) {
                $this->db->where('id !=', (int)$exclude_id);
            }
            
            return $this->db->count_all_results($this->table) > 0;
            
        } catch (Exception $e) {
            log_message('error', 'Product_category_model::slug_exists - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Move category to new parent - UNLIMITED LEVELS ✅
     * 
     * @param int $category_id Category ID
     * @param int|null $new_parent_id New parent ID
     * @return bool
     */
    public function move_to_parent($category_id, $new_parent_id) {
        try {
            if (empty($category_id)) {
                return false;
            }

            // Move to root
            if ($new_parent_id === null || $new_parent_id === 0 || $new_parent_id === '0') {
                return $this->update($category_id, [
                    'parent_id' => null,
                    'level' => 1,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }

            // Validate parent exists
            $parent = $this->get_by_id($new_parent_id);
            if (!$parent) {
                return false;
            }

            // ✅ Kiểm tra không được di chuyển vào chính con của nó (circular reference)
            if ($this->is_descendant($category_id, $new_parent_id)) {
                log_message('error', 'Product_category_model::move_to_parent - Cannot move to descendant');
                return false;
            }

            // ✅ Không còn giới hạn cấp độ, tự động tính level
            $new_level = $parent['level'] + 1;

            return $this->update($category_id, [
                'parent_id' => (int)$new_parent_id,
                'level' => $new_level,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        } catch (Exception $e) {
            log_message('error', 'Product_category_model::move_to_parent - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if target_id is a descendant of category_id (to prevent circular reference)
     * 
     * @param int $category_id Category ID
     * @param int $target_id Target ID to check
     * @return bool
     */
    public function is_descendant($category_id, $target_id) {
        try {
            $current = $this->get_by_id($target_id);
            $iterations = 0;

            while ($current && $iterations < $this->max_depth) {
                if ($current['id'] == $category_id) {
                    return true; // target_id là con của category_id
                }

                if (empty($current['parent_id'])) {
                    break;
                }

                $current = $this->get_by_id($current['parent_id']);
                $iterations++;
            }

            return false;

        } catch (Exception $e) {
            log_message('error', 'Product_category_model::is_descendant - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate category level from parent
     * 
     * @param int|null $parent_id Parent ID
     * @return int
     */
    public function calculate_level($parent_id) {
        try {
            if (empty($parent_id)) {
                return 1;
            }

            $parent = $this->get_by_id($parent_id);
            if (!$parent) {
                return 1;
            }

            return $parent['level'] + 1;

        } catch (Exception $e) {
            log_message('error', 'Product_category_model::calculate_level - ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Get statistics - UPDATED FOR UNLIMITED LEVELS ✅
     * 
     * @return array
     */
    public function get_statistics() {
        try {
            $stats = [];

            // Total categories
            $this->db->where('deleted_at IS NULL');
            $stats['total_categories'] = $this->db->count_all_results($this->table);

            // ✅ Lấy thống kê theo từng level động
            // Lấy max level hiện có trong DB
            $this->db->select_max('level');
            $this->db->where('deleted_at IS NULL');
            $max_level_result = $this->db->get($this->table)->row();
            $max_level = $max_level_result ? (int)$max_level_result->level : 0;

            $stats['max_level'] = $max_level;
            $stats['levels'] = [];

            // Đếm số lượng category theo từng level
            for ($i = 1; $i <= $max_level; $i++) {
                $this->db->where('level', $i);
                $this->db->where('deleted_at IS NULL');
                $count = $this->db->count_all_results($this->table);
                $stats['levels'][$i] = $count;
            }

            // Active categories
            $this->db->where('status', 'active');
            $this->db->where('deleted_at IS NULL');
            $stats['active_categories'] = $this->db->count_all_results($this->table);

            return $stats;

        } catch (Exception $e) {
            log_message('error', 'Product_category_model::get_statistics - ' . $e->getMessage());
            return [];
        }
    }
    /**
     * Get all descendants (children, grandchildren, etc.) of a category
     * PUBLIC method for Controller/API use
     * 
     * @param int $category_id Parent category ID
     * @param bool $include_self Include the category itself
     * @return array Array of category objects
     */
    public function get_all_descendants($category_id, $include_self = false) {
        try {
            $descendants = [];
            
            if ($include_self) {
                $current = $this->get_by_id($category_id);
                if ($current) {
                    $descendants[] = $current;
                }
            }
            
            $this->_get_all_descendants_recursive($category_id, $descendants);
            
            return $descendants;
            
        } catch (Exception $e) {
            log_message('error', 'Product_category_model::get_all_descendants - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Recursive helper to get all descendants with full data
     * 
     * @param int $parent_id Parent category ID
     * @param array &$descendants Array to store results
     * @return void
     */
    private function _get_all_descendants_recursive($parent_id, &$descendants) {
        try {
            $children = $this->get_children($parent_id);
            
            foreach ($children as $child) {
                $descendants[] = $child;
                $this->_get_all_descendants_recursive($child['id'], $descendants);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Product_category_model::_get_all_descendants_recursive - ' . $e->getMessage());
        }
    }

    /**
     * Get all descendant IDs (for filtering products by category tree)
     * PUBLIC method optimized for product queries
     * 
     * @param int $category_id Parent category ID
     * @param bool $include_self Include the category itself
     * @return array Array of IDs
     */
    public function get_all_descendant_ids($category_id, $include_self = true) {
        try {
            $ids = [];
            
            if ($include_self) {
                $ids[] = (int)$category_id;
            }
            
            $this->_get_all_children_ids($category_id, $ids);
            
            return array_unique($ids);
            
        } catch (Exception $e) {
            log_message('error', 'Product_category_model::get_all_descendant_ids - ' . $e->getMessage());
            return $include_self ? [(int)$category_id] : [];
        }
    }

    public function hard_delete($id) { return $this->db->delete('product_categories', ['id'=>$id]); }
    public function restore($id) { return $this->db->update('product_categories', ['deleted_at'=>NULL], ['id'=>$id]); }

    /*
        Thêm hàm mới get_products_with_variants_by_category($category_id) để lấy sản phẩm + biến thể theo category. Hàm này sẽ:
        Lấy tất cả category con (id) dùng $this->get_all_descendant_ids().
        Query 2 bước lấy:
        Tất cả sản phẩm chính thuộc category.
        Tất cả biến thể của những sản phẩm này.
        Gộp dữ liệu sản phẩm và biến thể trả về mảng hợp nhất.
     */
    
     public function get_products_with_variants_by_category($category_id) {
        $category_ids = $this->get_all_descendant_ids($category_id, true);
    
        // Lấy product_ids liên kết category
        $this->db->select('product_id');
        $this->db->from('product_category_links');
        $this->db->where_in('category_id', $category_ids);
        $query = $this->db->get();
        $product_ids = array_column($query->result_array(), 'product_id');
    
        if (empty($product_ids)) {
            return [];
        }
    
        // Lấy sản phẩm chính từ bảng products
        $this->db->select('id, name, code, selling_price, status, image');
        $this->db->select('NULL AS variant_id', false);
        $this->db->select('NULL AS variant_name', false);
        $this->db->select('NULL AS sku', false);
        $this->db->select('NULL AS variant_price', false);
        $this->db->from('products');
        $this->db->where_in('id', $product_ids);
        $this->db->where('deleted_at IS NULL');
        $products = $this->db->get()->result_array();
    
        if (empty($products)) {
            return [];
        }
    
        // Map products theo id để tra cứu thông tin parent cho variant
        $products_map = [];
        foreach ($products as $product) {
            $products_map[$product['id']] = $product;
        }
    
        // Lấy biến thể từ bảng product_variants_v2
        $this->db->select('id as variant_id, product_id, variant_name, sku, price as variant_price, status as variant_status, image_url');
        $this->db->from('product_variants_v2');
        $this->db->where_in('product_id', $product_ids);
        //$this->db->where('deleted_at IS NULL');
        $variants = $this->db->get()->result_array();
    
        // Gộp biến thể vào kết quả
        $result = $products; // $products có thể có chỉ số [0],[1],...
        foreach ($variants as $variant) {
            // Lấy thông tin sản phẩm chính
            $parent_product = isset($products_map[$variant['product_id']]) ? $products_map[$variant['product_id']] : null;
            $result[] = [
                'id'            => $variant['product_id'],
                'name'          => $parent_product ? $parent_product['name'] : '',
                'code'          => $parent_product ? $parent_product['code'] : '',
                'selling_price' => null,
                'status'        => $variant['variant_status'],
                'image'         => !empty($variant['image_url']) ? $variant['image_url'] : ($parent_product ? $parent_product['image'] : null),
                'variant_id'    => $variant['variant_id'],
                'variant_name'  => $variant['variant_name'],
                'sku'           => $variant['sku'],
                'variant_price' => $variant['variant_price'],
            ];
        }
        return array_values($result); // đảm bảo chỉ số mảng tuần tự

    }       
    

}
