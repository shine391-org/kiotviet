<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Product Model - Complete Version
 * Version: 2.0 - Merged with Phase 2 Enhancements
 * Date: 2025-10-27
 * 
 * Features:
 * - Original functions (backward compatible)
 * - Phase 2 enhancements (images, variants v2, attributes)
 * - Support both old and new table names
 */
class Product_model extends CI_Model {

    protected $table = 'products';
    protected $primaryKey = 'id';

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    // ========================================================================
    // ORIGINAL FUNCTIONS (KEEP FOR BACKWARD COMPATIBILITY)
    // ========================================================================

    /**
     * Get products with pagination and filters
     * Original function - keep for existing code
     */
    public function get_products($search = '', $category_id = null, $product_type = null, $status = null, $is_active = null, $brand = null, $limit = 20, $offset = 0, $sort_by = 'p.id', $order = 'DESC', $stock_status = null, $attributes = [])
{
    $subquery = '(SELECT product_id, SUM(stock_quantity) as total_stock FROM product_variants_v2 WHERE status = "active" GROUP BY product_id) AS variant_stock';

    // ===== Build WHERE động =====
    $where = ['p.deleted_at IS NULL'];
    if (!empty($search)) {
        $like = [];
        $like[] = "p.name LIKE " . $this->db->escape('%' . $search . '%');
        $like[] = "p.code LIKE " . $this->db->escape('%' . $search . '%');
        $like[] = "p.barcode LIKE " . $this->db->escape('%' . $search . '%');
        $like[] = "p.brand LIKE " . $this->db->escape('%' . $search . '%');
        $where[] = '(' . implode(' OR ', $like) . ')';
    }
    // ---- NÂNG CẤP FIX MULTI CATEGORY FILTER MANY-TO-MANY ----
    if (!empty($category_id)) {
        if (is_array($category_id)) {
            $id_list = array_map('intval', $category_id);
            $where[] = 'p.id IN (SELECT product_id FROM product_category_links WHERE category_id IN (' . implode(',', $id_list) . '))';
        } else {
            $where[] = 'p.id IN (SELECT product_id FROM product_category_links WHERE category_id = ' . (int)$category_id . ')';
        }
    }
    // -----------------------------------
    if (!empty($product_type))   $where[] = 'p.product_type = ' . $this->db->escape($product_type);
    if (!empty($status))         $where[] = 'p.status = ' . $this->db->escape($status);
    if ($is_active !== null && $is_active !== '') $where[] = 'p.is_active = ' . (int)$is_active;
    if (!empty($brand))          $where[] = 'p.brand = ' . $this->db->escape($brand);

    if (!empty($stock_status)) {
        if ($stock_status === 'in_stock') {
            $where[] = 'variant_stock.total_stock > 0';
        } elseif ($stock_status === 'out_of_stock') {
            $where[] = '(variant_stock.total_stock = 0 OR variant_stock.total_stock IS NULL)';
        }
    }

    $attribute_join = '';
    $attribute_where = '';
    if (!empty($attributes) && is_array($attributes)) {
        $attribute_join = 'INNER JOIN product_attribute_values pav ON p.id = pav.product_id';
        $attr_or = [];
        foreach ($attributes as $attr) {
            $conds = [];
            if (!empty($attr['attribute_id'])) $conds[] = 'pav.attribute_id = ' . (int)$attr['attribute_id'];
            if (!empty($attr['option_id'])) $conds[] = 'pav.option_id = ' . (int)$attr['option_id'];
            if (!empty($attr['value_text'])) $conds[] = 'pav.value_text LIKE ' . $this->db->escape('%' . $attr['value_text'] . '%');
            if (!empty($conds)) $attr_or[] = '(' . implode(' AND ', $conds) . ')';
        }
        if (!empty($attr_or)) $attribute_where = 'AND (' . implode(' OR ', $attr_or) . ')';
    }

    $where_sql = implode(' AND ', $where);

    // ===== Truy vấn tổng số bản ghi =====
    $sql_count = "
        SELECT COUNT(DISTINCT p.id) as total
        FROM products p
        LEFT JOIN $subquery ON variant_stock.product_id = p.id
        $attribute_join
        WHERE $where_sql $attribute_where
    ";
    $total_number = (int)$this->db->query($sql_count)->row()->total;

    // ===== Truy vấn dữ liệu trang với JOIN bảng liên kết category ----
    $sql_data = "
        SELECT p.*,
            variant_stock.total_stock,
            GROUP_CONCAT(pc.id) as category_ids,
            GROUP_CONCAT(pc.name) as category_names
        FROM products p
        LEFT JOIN $subquery ON variant_stock.product_id = p.id
        LEFT JOIN product_category_links pcl ON pcl.product_id = p.id
        LEFT JOIN product_categories pc ON pc.id = pcl.category_id
        $attribute_join
        WHERE $where_sql $attribute_where
        GROUP BY p.id
        ORDER BY $sort_by $order
        LIMIT $limit OFFSET $offset
    ";
    $data = $this->db->query($sql_data)->result_array();

    // ✅ NÂNG CẤP: Parse lại category_ids/category_names thành mảng cho mỗi row
    foreach ($data as &$row) {
        $row['category_ids'] = !empty($row['category_ids']) ? array_map('intval', explode(',', $row['category_ids'])) : [];
        $row['category_names'] = !empty($row['category_names']) ? explode(',', $row['category_names']) : [];
    }

    return [
        'total' => $total_number,
        'data' => $data
    ];
}

    /**
     * Get product by ID - Original version
     */
    public function get_by_id($id) {
        $this->db->select('
            p.*,
            u1.username as created_by_name,
            u2.username as updated_by_name
        ');
        $this->db->from($this->table . ' p');
        // Thêm join lấy được tất cả category thông qua bảng liên kết
        $this->db->join('product_category_links pcl', 'pcl.product_id = p.id', 'left');
        $this->db->join('product_categories pc', 'pc.id = pcl.category_id', 'left');
        // Gom tên các danh mục thành một chuỗi
        $this->db->select('GROUP_CONCAT(DISTINCT pc.name) as category_names', false);
        $this->db->join('users u1', 'u1.id = p.created_by', 'left');
        $this->db->join('users u2', 'u2.id = p.updated_by', 'left');
        $this->db->where('p.id', $id);
        $this->db->where('p.deleted_at', NULL);
        // Nhóm theo product id để GROUP_CONCAT hoạt động
        $this->db->group_by('p.id');
    
        $query = $this->db->get();
        $product = $query->row_array();
    
        if ($product) {
            $product['primary_image'] = $this->get_primary_image($product['id']);
            // Chuyển chuỗi category_names thành mảng
            $product['category_names'] = $product['category_names'] ? explode(',', $product['category_names']) : [];
        }
    
        return $product;
    }    

    /**
     * Create product
     * ✅ FIXED: Handle has_variants logic
     */
    /**
     * Create product
     * Version hỗ trợ category_id array và attribute_values đưa vào bảng phụ.
     */
    public function create($data) {
        // ====== Bóc tách field đặc thù ======
        $category_ids = [];
        if (isset($data['category_id'])) {
            if (is_array($data['category_id'])) {
                $category_ids = array_map('intval', $data['category_id']);
            } else {
                $category_ids = [intval($data['category_id'])];
            }
            unset($data['category_id']);
        }

        $attribute_values = [];
        if (isset($data['attribute_values']) && is_array($data['attribute_values'])) {
            $attribute_values = $data['attribute_values'];
            unset($data['attribute_values']);
        }

        // Normalize has_variants (y nguyên)
        if (isset($data['has_variants'])) {
            $data['has_variants'] = (int)$data['has_variants'];
        } else {
            $data['has_variants'] = 0; // Default: simple product
        }
        if ($data['has_variants'] == 1) {
            $data['selling_price'] = null;
            $data['purchase_price'] = null;
            $data['stock_quantity'] = null;
            log_message('info', 'create: Product has variants - cleared price/stock fields');
        }

        // ====== Begin Transaction (model cũng dùng trans nếu gọi riêng lẻ) ======
        // $this->db->trans_start();  (không cần vì controller đã trans_start)

        // Insert vào products
        $this->db->insert($this->table, $data);
        $product_id = $this->db->insert_id();

        // Insert vào bảng phụ product_categories
        if ($product_id && !empty($category_ids)) {
            foreach ($category_ids as $cat_id) {
                $this->db->insert('product_category_links', [
                    'product_id' => $product_id,
                    'category_id' => $cat_id,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }

        // (Nếu có xử lý attribute_values thì xử lý ở đây tùy ý bạn...)

        // $this->db->trans_complete();  (không cần vì controller đã trans_complete)

        return $product_id;
    }

    /**
     * Update product
     * ✅ FIXED: Handle has_variants logic
     */
    public function update($id, $data) {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        if (!is_array($data) || empty($data)) {
            return false;
        }
    
        // Bóc tách danh mục và unset khỏi $data để tránh update vào bảng products
        $category_ids = [];
        if (isset($data['category_id'])) {
            if (is_array($data['category_id'])) {
                $category_ids = array_map('intval', $data['category_id']);
            } else {
                $category_ids = [intval($data['category_id'])];
            }
            unset($data['category_id']);
        }
    
        $old_product = $this->get_by_id($id);
        if (!$old_product) return false;
    
        // Lấy danh sách category_id cũ trước khi update bảng liên kết
        $old_category_ids = array_column($this->get_product_categories($id), 'id');
    
        foreach (['is_active', 'is_featured', 'is_available_online', 'has_variants'] as $bool_field) {
            if (isset($data[$bool_field])) {
                if (is_bool($data[$bool_field])) {
                    $data[$bool_field] = $data[$bool_field] ? 1 : 0;
                } elseif ($data[$bool_field] === 'true') {
                    $data[$bool_field] = 1;
                } elseif ($data[$bool_field] === 'false') {
                    $data[$bool_field] = 0;
                } else {
                    $data[$bool_field] = (int)$data[$bool_field];
                }
            }
        }
    
        if (isset($data['has_variants']) && $data['has_variants'] == 1) {
            $data['selling_price'] = null;
            $data['purchase_price'] = null;
            $data['stock_quantity'] = null;
            log_message('info', "update: Product ID={$id} changed to has_variants=1 - cleared price/stock");
        }
    
        $this->db->where('id', $id);
        $result = $this->db->update($this->table, $data);
    
        // Update bảng liên kết category many-to-many
        $this->db->where('product_id', $id)->delete('product_category_links');
        // ✅ Insert lại nếu còn category
        foreach ($category_ids as $cat_id) {
            $this->db->insert('product_category_links', [
                'product_id' => $id,
                'category_id' => $cat_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    
        if ($result) {
            $new_product = $this->get_by_id($id);
            $changed_fields = [];
            foreach ($data as $field => $new_value) {
                $old_value = isset($old_product[$field]) ? $old_product[$field] : null;
                if ((string)$old_value !== (string)$new_value) {
                    $changed_fields[$field] = [
                        'old' => $old_value,
                        'new' => $new_value
                    ];
                }
            }
            // So sánh old <-> new category_ids (luôn so sánh là mảng)
            $old_ids = array_map('intval', $old_category_ids);
            $new_ids = array_map('intval', $category_ids);
            sort($old_ids);
            sort($new_ids);
            if ($old_ids !== $new_ids) {
                $changed_fields['category_ids'] = [
                    'old' => $old_ids,
                    'old_names' => $this->get_category_names_by_ids($old_ids),
                    'new' => $new_ids,
                    'new_names' => $this->get_category_names_by_ids($new_ids)
                ];
            }
            log_message('error', '[DEBUG CATEGORY_IDS CHANGE] OLD: ' . json_encode($old_ids) . ' | NEW: ' . json_encode($new_ids) . ' | CHANGED: ' . json_encode($changed_fields));
            return [
                'success' => true,
                'changed_fields' => $changed_fields,
                'product' => $new_product
            ];
        }
        return false;
    }    

    /**
     * Soft delete product
     */
    public function delete($id) {
        $data = [
            'deleted_at' => date('Y-m-d H:i:s'),
            'status' => 'discontinued'
        ];
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Get product by code
     */
    public function get_by_code($code) {
        $this->db->where('code', $code);
        $this->db->where('deleted_at', NULL);
        $query = $this->db->get($this->table);
        return $query->row_array();
    }

    /**
     * Generate unique slug
     */
    public function generate_slug($name, $id = null) {
        $this->load->helper('url');
        $slug = url_title($name, 'dash', TRUE);
        
        $this->db->where('slug', $slug);
        if ($id) {
            $this->db->where('id !=', $id);
        }
        $this->db->where('deleted_at', NULL);
        $count = $this->db->count_all_results($this->table);
        
        if ($count > 0) {
            $slug = $slug . '-' . ($count + 1);
        }
        
        return $slug;
    }

    /**
     * Quick search for autocomplete
     */
    public function quick_search($keyword, $limit = 10) {
        $this->db->select('id, code, name, image, selling_price, stock_quantity');
        $this->db->from($this->table);
        $this->db->group_start();
        $this->db->like('name', $keyword);
        $this->db->or_like('code', $keyword);
        $this->db->or_like('barcode', $keyword);
        $this->db->group_end();
        $this->db->where('deleted_at', NULL);
        $this->db->where('is_active', 1);
        $this->db->limit($limit);
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get variants (original table name)
     */
    public function get_variants($product_id) {
        $this->db->select('*');
        $this->db->from('product_variants');
        $this->db->where('product_id', $product_id);
        $this->db->where('status', 'active');
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get stock by branches (original table name)
     */
    public function get_stock_by_branches($product_id) {
        $this->db->select('psb.*, b.name as branch_name');
        $this->db->from('product_branch_stock psb');
        $this->db->join('branches b', 'b.id = psb.branch_id', 'left');
        $this->db->where('psb.product_id', $product_id);
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Update stock quantity
     */
    public function update_stock($product_id, $quantity, $branch_id = null) {
        if ($branch_id) {
            // Update specific branch stock
            $this->db->set('stock_quantity', 'stock_quantity + ' . (int)$quantity, FALSE);
            $this->db->where('product_id', $product_id);
            $this->db->where('branch_id', $branch_id);
            return $this->db->update('product_branch_stock');
        } else {
            // Update main product stock
            $this->db->set('stock_quantity', 'stock_quantity + ' . (int)$quantity, FALSE);
            $this->db->where('id', $product_id);
            return $this->db->update($this->table);
        }
    }

    /**
     * Get product statistics
     */
    public function get_statistics() {
        $stats = [];
        
        // Total products
        $this->db->where('deleted_at', NULL);
        $stats['total'] = $this->db->count_all_results($this->table);
        
        // Active products
        $this->db->where('is_active', 1);
        $this->db->where('deleted_at', NULL);
        $stats['active'] = $this->db->count_all_results($this->table);
        
        // Out of stock
        $this->db->where('stock_quantity', 0);
        $this->db->where('deleted_at', NULL);
        $stats['out_of_stock'] = $this->db->count_all_results($this->table);
        
        // Low stock (< 10)
        $this->db->where('stock_quantity >', 0);
        $this->db->where('stock_quantity <', 10);
        $this->db->where('deleted_at', NULL);
        $stats['low_stock'] = $this->db->count_all_results($this->table);
        
        return $stats;
    }

    // ========================================================================
    // PHASE 2 ENHANCEMENTS - NEW FUNCTIONS
    // ========================================================================

    /**
     * Get primary image from product_images table
     */
    public function get_primary_image($product_id) {
        $this->db->select('id, image_url, image_path');
        $this->db->from('product_images');
        $this->db->where('product_id', $product_id);
        $this->db->where('is_primary', 1);
        $this->db->where('deleted_at', NULL);
        $this->db->limit(1);
        
        $query = $this->db->get();
        return $query->row_array();
    }

    /**
     * Get all product images
     */
    public function get_product_images($product_id) {
        $this->db->select('id, image_url, image_path, is_primary, sort_order');
        $this->db->from('product_images');
        $this->db->where('product_id', $product_id);
        $this->db->where('deleted_at', NULL);
        $this->db->order_by('is_primary', 'DESC');
        $this->db->order_by('sort_order', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get product attributes
     */
    public function get_product_attributes($product_id) {
        $this->db->select('pav.*, pa.name as attribute_name, pa.attribute_key, pa.type');
        $this->db->from('product_attribute_values pav');
        $this->db->join('product_attributes pa', 'pa.id = pav.attribute_id', 'left');
        $this->db->where('pav.product_id', $product_id);
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get stock for all branches (NEW table name)
     */
    public function get_all_branch_stock($product_id) {
        $this->db->select('psb.*, b.name as branch_name');
        $this->db->from('product_stock_by_branch psb');
        $this->db->join('branches b', 'b.id = psb.branch_id', 'left');
        $this->db->where('psb.product_id', $product_id);
        $this->db->order_by('b.name', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get product variants v2 (NEW table name)
     */
    public function get_product_variants_v2($product_id) {
        $this->db->select('*');
        $this->db->from('product_variants_v2');
        $this->db->where('product_id', $product_id);
        $this->db->where('deleted_at', NULL);
        $this->db->where('status', 'active');
        $this->db->order_by('id', 'ASC');
        
        $query = $this->db->get();
        $variants = $query->result_array();

        // Parse attributes JSON
        foreach ($variants as &$variant) {
            if (!empty($variant['attributes'])) {
                $variant['attributes'] = json_decode($variant['attributes'], true);
            }
        }

        return $variants;
    }

    /**
     * Get branch stock (NEW table name)
     */
    public function get_branch_stock_v2($product_id, $branch_id) {
        $this->db->select('*');
        $this->db->from('product_stock_by_branch');
        $this->db->where('product_id', $product_id);
        $this->db->where('branch_id', $branch_id);
        
        $query = $this->db->get();
        return $query->row_array();
    }

    /**
     * Get unique brands
     */
    public function get_brands() {
        $this->db->select('brand');
        $this->db->from($this->table);
        $this->db->where('brand IS NOT NULL');
        $this->db->where('brand !=', '');
        $this->db->where('deleted_at', NULL);
        $this->db->group_by('brand');
        $this->db->order_by('brand', 'ASC');
        
        $query = $this->db->get();
        return array_column($query->result_array(), 'brand');
    }

    /**
     * Enhanced get_by_id with images, variants, attributes
     * New function for Phase 2
     */
    public function get_by_id_full($id) {
        $this->db->select('
            p.*,
            u1.username as created_by_name,
            u2.username as updated_by_name
        ');
        $this->db->from($this->table . ' p');
        $this->db->join('users u1', 'u1.id = p.created_by', 'left');
        $this->db->join('users u2', 'u2.id = p.updated_by', 'left');
        $this->db->where('p.id', $id);
        $this->db->where('p.deleted_at', NULL);
    
        $query = $this->db->get();
        $product = $query->row_array();
    
        if ($product) {
            // Các thông tin bổ sung
            $product['primary_image'] = $this->get_primary_image($id);
            $product['all_images'] = $this->get_product_images($id);
            $product['variants_v2'] = $this->get_product_variants_v2($id);
            $product['variants'] = $this->get_variants($id);
            $product['stock_by_branches'] = $this->get_all_branch_stock($id);
            $product['stock_by_branches_old'] = $this->get_stock_by_branches($id);
            $product['attributes'] = $this->get_product_attributes($id);
            // Lấy danh sách categories nhiều-nhiều
            $product['categories'] = $this->get_product_categories($id);
            $product['category_ids'] = array_column($product['categories'], 'id');
            $product['category_names'] = array_column($product['categories'], 'name');
        }
        return $product;
    }    

    /**
     * Count products with filters (for pagination)
     */
    public function count_products_filtered($filters = []) {
        $this->db->from($this->table . ' p');
        $this->db->where('p.deleted_at', NULL);

        // Search
        if (!empty($filters['search'])) {
            $search = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('p.name', $search);
            $this->db->or_like('p.code', $search);
            $this->db->or_like('p.barcode', $search);
            $this->db->group_end();
        }

        // Filters
        if (!empty($filters['category_id'])) {
            $this->db->join('product_category_links pcl', 'pcl.product_id = p.id');
            if (is_array($filters['category_id'])) {
                $this->db->where_in('pcl.category_id', $filters['category_id']);
            } else {
                $this->db->where('pcl.category_id', $filters['category_id']);
            }
        }        

        if (!empty($filters['brand'])) {
            $this->db->where('p.brand', $filters['brand']);
        }

        if (!empty($filters['product_type'])) {
            $this->db->where('p.product_type', $filters['product_type']);
        }

        if (!empty($filters['status'])) {
            $this->db->where('p.status', $filters['status']);
        }

        if (isset($filters['is_active'])) {
            $this->db->where('p.is_active', $filters['is_active']);
        }

        // Stock filter
        if (!empty($filters['stock_status'])) {
            switch ($filters['stock_status']) {
                case 'in_stock':
                    $this->db->where('p.stock_quantity >', 10);
                    break;
                case 'low_stock':
                    $this->db->where('p.stock_quantity >', 0);
                    $this->db->where('p.stock_quantity <=', 10);
                    break;
                case 'out_of_stock':
                    $this->db->where('p.stock_quantity', 0);
                    break;
            }
        }

        return $this->db->count_all_results();
    }

    /**
     * Get products with enhanced data (Phase 2)
     */
    public function get_products_enhanced($filters = [], $limit = 20, $offset = 0) {
        $this->db->select('
            p.id,
            p.product_type,
            p.code,
            p.barcode,
            p.name,
            p.slug,
            p.description,
            p.image,
            p.images,
            p.purchase_price,
            p.selling_price,
            p.wholesale_price,
            p.stock_quantity,
            p.unit,
            p.weight,
            p.dimensions,
            p.brand,
            p.warehouse_location,
            p.commission_percent,
            p.commission_amount,
            p.alert_stock,
            p.expiry_days,
            p.is_active,
            p.status,
            p.created_at,
            p.updated_at,
            GROUP_CONCAT(pc.id) as category_ids,
            GROUP_CONCAT(pc.name) as category_names,
            (SELECT COUNT(*) FROM product_variants_v2 pv WHERE pv.product_id = p.id AND pv.deleted_at IS NULL) as variants_count,
            (SELECT COUNT(*) FROM product_images pi WHERE pi.product_id = p.id AND pi.deleted_at IS NULL) as images_count
        ');
        $this->db->from($this->table . ' p');
        $this->db->join('product_category_links pcl', 'pcl.product_id = p.id', 'left');
        $this->db->join('product_categories pc', 'pc.id = pcl.category_id', 'left');
        $this->db->where('p.deleted_at', NULL);
    
        // === Apply filters (best practice many-to-many) ===
        if (!empty($filters['search'])) {
            $search = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('p.name', $search);
            $this->db->or_like('p.code', $search);
            $this->db->or_like('p.barcode', $search);
            $this->db->group_end();
        }
    
        if (!empty($filters['category_id'])) {
            // Nhiều danh mục: lọc qua bảng liên kết
            if (is_array($filters['category_id'])) {
                $this->db->where_in('pcl.category_id', $filters['category_id']);
            } else {
                $this->db->where('pcl.category_id', $filters['category_id']);
            }
        }
    
        if (!empty($filters['brand'])) {
            $this->db->where('p.brand', $filters['brand']);
        }
    
        if (!empty($filters['product_type'])) {
            $this->db->where('p.product_type', $filters['product_type']);
        }
    
        if (!empty($filters['status'])) {
            $this->db->where('p.status', $filters['status']);
        }
    
        if (isset($filters['is_active'])) {
            $this->db->where('p.is_active', $filters['is_active']);
        }
    
        if (!empty($filters['stock_status'])) {
            switch ($filters['stock_status']) {
                case 'in_stock':
                    $this->db->where('p.stock_quantity >', 10);
                    break;
                case 'low_stock':
                    $this->db->where('p.stock_quantity >', 0);
                    $this->db->where('p.stock_quantity <=', 10);
                    break;
                case 'out_of_stock':
                    $this->db->where('p.stock_quantity', 0);
                    break;
            }
        }
    
        // Sorting
        $sort_field = !empty($filters['sort_field']) ? $filters['sort_field'] : 'p.id';
        $sort_order = !empty($filters['sort_order']) ? $filters['sort_order'] : 'DESC';
        $this->db->order_by($sort_field, $sort_order);
    
        // Group by để GROUP_CONCAT đúng từng product.id (many-to-many)
        $this->db->group_by('p.id');
    
        // Pagination
        $this->db->limit($limit, $offset);
    
        $query = $this->db->get();
        $products = $query->result_array();
    
        // Parse lại mảng category_ids/category_names cho từng product
        foreach ($products as &$product) {
            $product['category_ids'] = !empty($product['category_ids']) ? array_map('intval', explode(',', $product['category_ids'])) : [];
            $product['category_names'] = !empty($product['category_names']) ? explode(',', $product['category_names']) : [];
    
            $product['primary_image'] = $this->get_primary_image($product['id']);
            if (!empty($filters['branch_id'])) {
                $product['branch_stock'] = $this->get_branch_stock_v2($product['id'], $filters['branch_id']);
            }
        }
        return $products;
    }    

    /**
     * Check if barcode exists
     * @param string $barcode
     * @param int|null $exclude_id
     * @return bool
     */
    public function check_barcode_exists($barcode, $exclude_id = null) {
        $this->db->where('barcode', $barcode);
        $this->db->where('deleted_at', NULL);
        
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        
        $count = $this->db->count_all_results($this->table);
        return ($count > 0);
    }

    /**
     * Check if slug exists
     * @param string $slug
     * @param int|null $exclude_id
     * @return bool
     */
    public function check_slug_exists($slug, $exclude_id = null) {
        $this->db->where('slug', $slug);
        $this->db->where('deleted_at', NULL);
        
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        
        $count = $this->db->count_all_results($this->table);
        return ($count > 0);
    }

    /**
     * Get next product code (auto-generate)
     * @param string $prefix (default: 'SP')
     * @return string
     */
    public function get_next_code($prefix = 'SP') {
        $this->db->select('code');
        $this->db->from($this->table);
        $this->db->like('code', $prefix, 'after');
        $this->db->where('deleted_at', NULL);
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        
        $query = $this->db->get();
        $last = $query->row_array();
        
        if ($last && !empty($last['code'])) {
            // Extract number from code (e.g., SP000123 -> 123)
            preg_match('/\d+$/', $last['code'], $matches);
            if (!empty($matches[0])) {
                $last_number = intval($matches[0]);
                $new_number = $last_number + 1;
                return $prefix . str_pad($new_number, 6, '0', STR_PAD_LEFT);
            }
        }
        
        // Default first code
        return $prefix . '000001';
    }

    /**
     * Get product with relationships for detail view
     * @param int $id
     * @return array|null
     */
    public function get_product_detail($id) {
        $product = $this->get_by_id_full($id);
        
        if (!$product) {
            return null;
        }
        
        // Additional processing if needed
        return $product;
    }

    /**
     * Restore soft deleted product
     * @param int $id
     * @return bool
     */
    public function restore($id) {
        $data = [
            'deleted_at' => NULL,
            'status' => 'active'
        ];
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Hard delete product (permanent)
     * @param int $id
     * @return bool
     */
    public function hard_delete($id) {
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    /**
     * Bulk update products
     * @param array $ids
     * @param array $data
     * @return bool
     */
    public function bulk_update($ids, $data) {
        if (empty($ids) || empty($data)) {
            return false;
        }
        
        $this->db->where_in('id', $ids);
        return $this->db->update($this->table, $data);
    }

    /**
     * Bulk delete products (soft delete)
     * @param array $ids
     * @return bool
     */
    public function bulk_delete($ids) {
        if (empty($ids)) {
            return false;
        }
        
        $data = [
            'deleted_at' => date('Y-m-d H:i:s'),
            'status' => 'discontinued'
        ];
        
        $this->db->where_in('id', $ids);
        return $this->db->update($this->table, $data);
    }

    /**
     * Get low stock products
     * @param int $threshold (default: 10)
     * @param int $limit
     * @return array
     */
    public function get_low_stock_products($threshold = 10, $limit = 50) {
        $this->db->select('id, code, name, image, stock_quantity, alert_stock, unit');
        $this->db->from($this->table);
        $this->db->where('stock_quantity >', 0);
        $this->db->where('stock_quantity <=', $threshold);
        $this->db->where('deleted_at', NULL);
        $this->db->where('is_active', 1);
        $this->db->order_by('stock_quantity', 'ASC');
        $this->db->limit($limit);
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get out of stock products
     * @param int $limit
     * @return array
     */
    public function get_out_of_stock_products($limit = 50) {
        $this->db->select('id, code, name, image, stock_quantity, unit');
        $this->db->from($this->table);
        $this->db->where('stock_quantity', 0);
        $this->db->where('deleted_at', NULL);
        $this->db->where('is_active', 1);
        $this->db->order_by('updated_at', 'DESC');
        $this->db->limit($limit);
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get best selling products
     * @param int $limit
     * @param string $date_from
     * @param string $date_to
     * @return array
     */
    public function get_best_selling($limit = 10, $date_from = null, $date_to = null) {
        // This requires order_items table - implement when orders module is ready
        // For now, return empty or most viewed products
        return [];
    }

    /**
     * Import products from array
     * @param array $products
     * @param int $created_by
     * @return array ['success' => count, 'errors' => []]
     */
    public function bulk_import($products, $created_by) {
        $success = 0;
        $errors = [];
        
        foreach ($products as $index => $product_data) {
            try {
                // Validate required fields
                if (empty($product_data['name'])) {
                    $errors[] = "Row " . ($index + 1) . ": Name is required";
                    continue;
                }
                
                // Generate code if not provided
                if (empty($product_data['code'])) {
                    $product_data['code'] = $this->get_next_code();
                }
                
                // Generate slug
                if (empty($product_data['slug'])) {
                    $product_data['slug'] = $this->generate_slug($product_data['name']);
                }
                
                // Add timestamps
                $product_data['created_by'] = $created_by;
                $product_data['created_at'] = date('Y-m-d H:i:s');
                
                // Insert
                $this->db->insert($this->table, $product_data);
                $success++;
                
            } catch (Exception $e) {
                $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
            }
        }
        
        return [
            'success' => $success,
            'errors' => $errors
        ];
    }
    /**
     * Get category info by ID
     */
    public function get_category_info($category_id) {
        if (empty($category_id)) {
            return null;
        }
        
        $this->db->select('
            id,
            parent_id,
            name,
            slug,
            description,
            image,
            sort_order,
            status
        ');
        $this->db->from('product_categories');
        $this->db->where('id', $category_id);
        $this->db->where('deleted_at', NULL);
        
        $query = $this->db->get();
        $category = $query->row_array();
        
        if ($category && $category['parent_id']) {
            $this->db->select('id, name, slug');
            $this->db->from('product_categories');
            $this->db->where('id', $category['parent_id']);
            $this->db->where('deleted_at', NULL);
            
            $parent_query = $this->db->get();
            $category['parent'] = $parent_query->row_array();
        }
        
        return $category;
    }

    /**
     * Get all categories (for dropdown)
     */
    public function get_all_categories() {
        $this->db->select('id, parent_id, name, slug');
        $this->db->from('product_categories');
        $this->db->where('deleted_at', NULL);
        $this->db->where('status', 'active');
        $this->db->order_by('sort_order', 'ASC');
        $this->db->order_by('name', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get product by slug
     */
    public function get_by_slug($slug) {
        $this->db->select('
            p.*,
            GROUP_CONCAT(pc.name) as category_names
        ');
        $this->db->from($this->table . ' p');
        // Join bảng liên kết nhiều-nhiều category
        $this->db->join('product_category_links pcl', 'pcl.product_id = p.id', 'left');
        $this->db->join('product_categories pc', 'pc.id = pcl.category_id', 'left');
        $this->db->where('p.slug', $slug);
        $this->db->where('p.deleted_at', NULL);
        $this->db->group_by('p.id');
    
        $query = $this->db->get();
        $product = $query->row_array();
    
        // Chuyển chuỗi tên danh mục thành mảng
        if ($product) {
            $product['category_names'] = $product['category_names'] ? explode(',', $product['category_names']) : [];
        }
    
        return $product;
    }    

    /**
     * Update product stock after sale
     */
    public function reduce_stock($product_id, $quantity) {
        $this->db->set('stock_quantity', 'stock_quantity - ' . (int)$quantity, FALSE);
        $this->db->where('id', $product_id);
        $this->db->where('stock_quantity >=', $quantity); // Ensure enough stock
        
        return $this->db->update($this->table);
    }

    /**
     * Increase stock (for returns)
     */
    public function increase_stock($product_id, $quantity) {
        $this->db->set('stock_quantity', 'stock_quantity + ' . (int)$quantity, FALSE);
        $this->db->where('id', $product_id);
        
        return $this->db->update($this->table);
    }
    /**
     * Check if product code exists
     * @param string $code - Product code to check
     * @param int|null $exclude_id - Product ID to exclude (for update)
     * @return bool - True if exists, False if not
     */
    public function check_code_exists($code, $exclude_id = null) {
        if (empty($code)) {
            return false;
        }
        
        $this->db->where('code', $code);
        $this->db->where('deleted_at', NULL);
        
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        
        $count = $this->db->count_all_results($this->table);
        return ($count > 0);
    }

    // ========================================================================
// 🆕 PHASE 1A: IMAGE MANAGEMENT HELPERS - NEW SECTION
// ========================================================================

    /**
     * Format image filename with SKU
     * Format: SKU_timestamp_random.ext
     * 
     * @param string $product_code - Product SKU/code
     * @param string $file_extension - jpg|png|gif|webp
     * @return string - Formatted filename
     */
    public function format_image_filename($product_code, $file_extension) {
        if (empty($product_code) || empty($file_extension)) {
            return null;
        }
        
        // Clean SKU: Remove special chars, spaces
        $clean_sku = preg_replace('/[^a-zA-Z0-9_-]/', '', strtoupper($product_code));
        
        // Generate unique suffix
        $timestamp = time();
        $random = substr(md5(uniqid()), 0, 6);
        
        // Return: SKU_timestamp_random.ext
        return "{$clean_sku}_{$timestamp}_{$random}.{$file_extension}";
    }


    /**
     * Sync products.images field with product_images table
     * Rebuilds JSON array from product_images records
     * 
     * @param int $product_id - Product ID
     * @return bool - Success/failure
     */
    /**
     * ✅ FIXED: Sync product.images JSON array
     * Rebuilds products.images from product_images table
     */
    public function sync_product_images($product_id) {
        try {
            $product_id = (int)$product_id;
            
            if ($product_id <= 0) {
                log_message('error', "sync_product_images: Invalid product ID {$product_id}");
                return false;
            }
            
            // ✅ FIX: Get images with proper NULL check
            $query = $this->db
                ->select('id, image_path, image_url, is_primary, sort_order')
                ->where('product_id', $product_id)
                ->where('deleted_at IS NULL', NULL, FALSE)  // ✅ Proper NULL check
                ->order_by('is_primary', 'DESC')
                ->order_by('sort_order', 'ASC')
                ->order_by('id', 'ASC')
                ->get('product_images');
            
            // ✅ Check query success
            if (!$query) {
                log_message('error', "sync_product_images: Query failed for product {$product_id}");
                return false;
            }
            
            $images = $query->result_array();
            
            // ✅ Build images array
            $image_data = [];
            foreach ($images as $img) {
                $image_data[] = [
                    'id' => (int)$img['id'],
                    'image_path' => (string)$img['image_path'],
                    'image_url' => (string)$img['image_url'],
                    'is_primary' => (int)$img['is_primary'],
                    'sort_order' => isset($img['sort_order']) ? (int)$img['sort_order'] : 0
                ];
            }

            // ✅ FIX: Ensure JSON encoding with error check
            $images_json = json_encode($image_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', 'sync_product_images JSON encode error: ' . json_last_error_msg());
                $images_json = '[]';  // Fallback to empty array
            }

            // ✅ Update products table with JSON string
            $update_data = [
                'images' => $images_json,
                'image_count' => count($image_data),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->where('id', $product_id);
            $update_result = $this->db->update('products', $update_data);
            
            if (!$update_result) {
                log_message('error', "sync_product_images: Update failed for product {$product_id}");
                return false;
            }

            log_message('info', "sync_product_images: Product {$product_id} synced with " . count($image_data) . " images");
            return true;

        } catch (Exception $e) {
            log_message('error', 'sync_product_images Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Insert a new product image into product_images table
     * ✅ FIXED: Support variant_id
     * 
     * @param array $data [
     *   product_id (required),
     *   variant_id (optional) - Nếu có thì ảnh thuộc variant,
     *   image_url (required),
     *   image_path (optional),
     *   is_primary (optional, default 0),
     *   sort_order (optional, default 0),
     *   file_name (optional),
     *   created_at (optional, default NOW())
     * ]
     * @return int|bool  Inserted ID or false
     */
    public function create_product_image($data) {
        if (!is_array($data) || empty($data['product_id']) || empty($data['image_url'])) {
            return false;
        }

        $insert = [
            'product_id' => $data['product_id'],
            'variant_id' => isset($data['variant_id']) ? $data['variant_id'] : null, // ✅ NEW
            'image_url' => $data['image_url'],
            'image_path' => isset($data['image_path']) ? $data['image_path'] : null,
            'is_primary' => isset($data['is_primary']) ? $data['is_primary'] : 0,
            'sort_order' => isset($data['sort_order']) ? $data['sort_order'] : 0,
            'file_name' => isset($data['file_name']) ? $data['file_name'] : null,
            'created_at' => isset($data['created_at']) ? $data['created_at'] : date('Y-m-d H:i:s'),
        ];

        $this->db->insert('product_images', $insert);
        if ($this->db->affected_rows() > 0) {
            return $this->db->insert_id();
        }
        return false;
    }

    /**
     * Update primary image and sync
     * Sets is_primary=1 for target, is_primary=0 for others
     * Also updates products.image field
     * 
     * @param int $image_id - product_images.id
     * @param int $product_id - products.id
     * @return bool - Success/failure
     */
    public function set_primary_image($image_id, $product_id) {
        try {
            $image_id = (int)$image_id;
            $product_id = (int)$product_id;

            // Get target image
            $image = $this->db
                ->where('id', $image_id)
                ->where('product_id', $product_id)
                ->get('product_images')
                ->row_array();

            if (!$image) {
                log_message('error', "set_primary_image: Image {$image_id} not found");
                return false;
            }

            // Reset all is_primary to 0 for this product
            $this->db->update('product_images', 
                ['is_primary' => 0],
                ['product_id' => $product_id, 'deleted_at' => NULL]
            );

            // Set target as primary
            $this->db->update('product_images',
                ['is_primary' => 1, 'updated_at' => date('Y-m-d H:i:s')],
                ['id' => $image_id]
            );

            // Update products.image with URL
            $this->db->update('products',
                ['image' => $image['image_url'], 'updated_at' => date('Y-m-d H:i:s')],
                ['id' => $product_id]
            );

            // Sync images array
            $this->sync_product_images($product_id);

            log_message('info', "set_primary_image: Image {$image_id} set as primary for product {$product_id}");
            return true;

        } catch (Exception $e) {
            log_message('error', 'set_primary_image Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all images of a variant
     * ✅ NEW: Support variant images
     * 
     * @param int $variant_id
     * @return array
     */
    public function get_variant_images($variant_id) {
        $this->db->select('id, image_url, image_path, is_primary, sort_order, file_name');
        $this->db->from('product_images');
        $this->db->where('variant_id', $variant_id);
        $this->db->where('deleted_at', NULL);
        $this->db->order_by('is_primary', 'DESC');
        $this->db->order_by('sort_order', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Soft delete image - set deleted_at
     * Also removes from products.images array
     * 
     * @param int $image_id - product_images.id
     * @return bool - Success/failure
     */
    public function soft_delete_image($image_id) {
        try {
            $image_id = (int)$image_id;

            // Get image to find product_id
            $image = $this->db
                ->where('id', $image_id)
                ->get('product_images')
                ->row_array();

            if (!$image) {
                log_message('error', "soft_delete_image: Image {$image_id} not found");
                return false;
            }

            // Soft delete
            $this->db->update('product_images',
                ['deleted_at' => date('Y-m-d H:i:s')],
                ['id' => $image_id]
            );

            // If was primary, clear products.image
            if ($image['is_primary'] == 1) {
                $this->db->update('products',
                    ['image' => NULL, 'updated_at' => date('Y-m-d H:i:s')],
                    ['id' => (int)$image['product_id']]
                );
            }

            // Sync images array (remove from JSON)
            $this->sync_product_images((int)$image['product_id']);

            log_message('info', "soft_delete_image: Image {$image_id} soft deleted");
            return true;

        } catch (Exception $e) {
            log_message('error', 'soft_delete_image Error: ' . $e->getMessage());
            return false;
        }
    }

    // ========================================================================
    // 🆕 PHASE 2: HARD DELETE IMAGE (NEW METHOD)
    // ========================================================================

    /**
     * Hard delete image (permanent removal)
     * Deletes row completely + removes file from disk
     * Used in: MediaLibraryBrowser - Hard delete from library
     *
     * @param int $image_id - Product image ID
     * @return bool
     */
    /**
     * ✅ FIXED: Hard delete with detailed logging
     */
    public function hard_delete_image($image_id) {
        try {
            $image_id = (int)$image_id;
            
            if ($image_id <= 0) {
                log_message('error', "hard_delete_image: Invalid image ID {$image_id}");
                return false;
            }

            // Get image details (no deleted_at filter - allow deleting soft-deleted)
            $image = $this->db
                ->where('id', $image_id)
                ->get('product_images')
                ->row_array();

            if (!$image) {
                log_message('error', "hard_delete_image: Image {$image_id} NOT FOUND in database");
                return false;
            }

            $product_id = (int)$image['product_id'];
            $image_path = $image['image_path'];
            $is_primary = (int)$image['is_primary'];

            log_message('info', "hard_delete_image START: ID={$image_id}, product_id={$product_id}, path={$image_path}");

            // ✅ Step 1: Delete file from disk - DON'T return false if file not found
            $file_deleted = false;
            if (!empty($image_path)) {
                $possible_paths = [
                    FCPATH . 'uploads/products/' . basename($image_path),
                    FCPATH . ltrim($image_path, '/'),
                    FCPATH . 'uploads/' . basename($image_path),
                ];
                
                log_message('info', "Checking file paths: " . json_encode($possible_paths));
                
                foreach ($possible_paths as $file_path) {
                    log_message('info', "Checking path: {$file_path}, exists=" . (file_exists($file_path) ? 'YES' : 'NO'));
                    
                    if (file_exists($file_path)) {
                        if (@unlink($file_path)) {
                            log_message('info', "✅ File deleted successfully: {$file_path}");
                            $file_deleted = true;
                            break;
                        } else {
                            log_message('error', "❌ Failed to delete file: {$file_path}");
                        }
                    }
                }
                
                if (!$file_deleted) {
                    log_message('error', "⚠️ File not found in any path: {$image_path} - CONTINUING ANYWAY");
                    // ✅ IMPORTANT: Don't return false - continue to delete from DB
                }
            }

            // ✅ Step 2: Delete row permanently from database
            log_message('info', "Deleting from database: image_id={$image_id}");
            
            $this->db->where('id', $image_id);
            $delete_result = $this->db->delete('product_images');

            if (!$delete_result) {
                log_message('error', "❌ DATABASE DELETE FAILED for image {$image_id}");
                log_message('error', "DB Error: " . $this->db->error()['message']);
                return false;
            }

            $affected_rows = $this->db->affected_rows();
            log_message('info', "✅ Database delete successful, affected_rows={$affected_rows}");

            // ✅ Step 3: If was primary, clear products.image
            if ($is_primary == 1) {
                log_message('info', "Clearing primary image for product {$product_id}");
                $this->db->update('products', [
                    'image' => NULL,
                    'updated_at' => date('Y-m-d H:i:s')
                ], ['id' => $product_id]);
            }

            // ✅ Step 4: Rebuild products.images JSON
            try {
                log_message('info', "Syncing product images for product {$product_id}");
                $this->sync_product_images($product_id);
            } catch (Exception $e) {
                log_message('error', "sync_product_images failed: " . $e->getMessage() . " - CONTINUING ANYWAY");
                // ✅ Don't return false - sync is not critical
            }

            log_message('info', "✅✅✅ hard_delete_image COMPLETED: Image {$image_id} permanently deleted");
            return true;

        } catch (Exception $e) {
            log_message('error', "❌❌❌ hard_delete_image EXCEPTION: " . $e->getMessage());
            log_message('error', "Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Get media library - browse all product images
     * 
     * @param int $limit - Items per page
     * @param int $offset - Pagination offset
     * @param array $filters - Optional filters ['product_id', 'brand', 'year', 'month']
     * @return array - ['total' => int, 'data' => array]
     */
    /**
     * ✅ FIXED: Get media library - Show ALL images including soft-deleted
     * Used in: MediaLibraryBrowser component
     * 
     * @param int $limit - Items per page (default: 12)
     * @param int $offset - Pagination offset (default: 0)
     * @param array $filters - Filters: product_id, sku, year, month, brand
     * @return array { success, message, data, total }
     */
    public function get_media_library($limit = 12, $offset = 0, $filters = []) {
        try {
            $limit = max(1, (int)$limit);
            $offset = max(0, (int)$offset);
            
            // ✅ STEP 1: Get total count (including soft-deleted)
            $countQuery = $this->db->select('COUNT(*) as cnt')
                ->from('product_images pi')
                ->join('products p', 'p.id = pi.product_id', 'LEFT');
            
            // Apply filters to count
            $countQuery = $this->_apply_media_filters($countQuery, $filters);
            
            // ✅ FIX: REMOVE deleted_at filter - show all images
            // $countQuery = $countQuery->where('pi.deleted_at', null); // ❌ REMOVED
            
            $countResult = $countQuery->get();
            
            $totalCount = 0;
            if ($countResult->num_rows() > 0) {
                $totalCount = (int)$countResult->row()->cnt;
            }
            
            // ✅ Check empty result
            if ($totalCount === 0) {
                log_message('info', 'get_media_library: No images found');
                return [
                    'success' => true,
                    'message' => 'Thư viện ảnh trống',
                    'data' => [],
                    'total' => 0
                ];
            }
            
            // ✅ STEP 2: Get paginated results (including soft-deleted)
            $query = $this->db->select('
                pi.id, 
                pi.product_id, 
                pi.image_url, 
                pi.image_path, 
                pi.file_name, 
                pi.is_primary, 
                pi.created_at,
                pi.deleted_at,
                p.code as product_code, 
                p.name as product_name
            ')
                ->from('product_images pi')
                ->join('products p', 'p.id = pi.product_id', 'LEFT');
            
            // Apply filters
            $query = $this->_apply_media_filters($query, $filters);
            
            // ✅ FIX: REMOVE deleted_at filter - show all images
            // $query = $query->where('pi.deleted_at', null); // ❌ REMOVED
            
            $query = $query
                ->order_by('pi.created_at', 'DESC')  // ✅ Newest first
                ->order_by('pi.id', 'DESC')
                ->limit($limit, $offset)
                ->get();
            
            $images = $query->result_array();
            
            log_message('info', "get_media_library: Loaded {$limit} images (total: {$totalCount}, including soft-deleted)");
            
            return [
                'success' => true,
                'message' => 'Media library retrieved successfully',
                'data' => $images,
                'total' => $totalCount
            ];
            
        } catch (Exception $e) {
            log_message('error', 'get_media_library Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => [],
                'total' => 0
            ];
        }
    }
    
    /**
     * ✅ Helper: Apply filters to query
     * @param object $query - CodeIgniter query builder
     * @param array $filters - Filters to apply
     * @return object - Modified query builder
     */
    private function _apply_media_filters($query, $filters = []) {
        if (empty($filters)) {
            return $query;
        }
        
        // Filter by product_id
        if (isset($filters['product_id']) && $filters['product_id'] > 0) {
            $query = $query->where('pi.product_id', (int)$filters['product_id']);
        }
        
        // Filter by SKU (product code)
        if (isset($filters['sku']) && !empty($filters['sku'])) {
            $sku = $this->db->escape_like_str($filters['sku']);
            $query = $query->where("p.code LIKE '%{$sku}%'", NULL, FALSE);
        }
        
        // Filter by brand
        if (isset($filters['brand']) && !empty($filters['brand'])) {
            $query = $query->where('p.brand', $filters['brand']);
        }
        
        // ✅ FIX: Filter by year & month using where() instead of whereRaw()
        if (isset($filters['year']) && isset($filters['month'])) {
            $year = (int)$filters['year'];
            $month = (int)$filters['month'];
            
            if ($year >= 2000 && $month >= 1 && $month <= 12) {
                // ✅ Use where() with raw SQL string
                $query = $query->where("YEAR(pi.created_at) = {$year}", NULL, FALSE)
                            ->where("MONTH(pi.created_at) = {$month}", NULL, FALSE);
            }
        }
        
        return $query;
    }
  
    /**
     * Search media library by SKU (product code)
     * 
     * @param string $sku - Product code/SKU
     * @param int $limit - Max results
     * @return array - Image records
     */
    public function search_media_by_sku($sku, $limit = 20) {
        try {
            if (empty($sku)) {
                return [];
            }

            $sku = trim($sku);

            $images = $this->db
                ->select('pi.id, pi.product_id, pi.image_url, pi.image_path, pi.is_primary, pi.created_at, p.code, p.name')
                ->from('product_images pi')
                ->join('products p', 'p.id = pi.product_id', 'left')
                ->where('pi.deleted_at', NULL)
                ->group_start()
                    ->like('p.code', $sku)
                    ->or_like('p.name', $sku)
                ->group_end()
                ->order_by('pi.is_primary DESC, pi.created_at DESC')
                ->limit($limit)
                ->get()
                ->result_array();

            log_message('info', "search_media_by_sku: Found " . count($images) . " images for SKU: {$sku}");
            return $images;

        } catch (Exception $e) {
            log_message('error', 'search_media_by_sku Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get images by date range (month/year)
     * @param int $year - 4-digit year
     * @param int $month - 1-12 month
     * @param int $limit - Items per page
     * @param int $offset - Pagination offset
     * @return array
     */
    public function get_media_by_date($year, $month, $limit = 50, $offset = 0) {
        try {
            $year = (int)$year;
            $month = (int)$month;
            $limit = (int)$limit;
            $offset = (int)$offset;
            
            if ($year < 2000 || $month < 1 || $month > 12) {
                return [
                    'success' => false,
                    'message' => 'Invalid year or month',
                    'data' => [],
                    'total' => 0
                ];
            }
            
            // ✅ Build query builder object
            $this->db->select('pi.id, pi.product_id, pi.image_url, pi.image_path, pi.file_name, pi.is_primary, pi.created_at, p.code as product_code');
            $this->db->from('product_images pi');
            $this->db->join('products p', 'p.id = pi.product_id', 'LEFT');
            
            // ✅ Use where() with raw SQL (CodeIgniter 3 compatible)
            $this->db->where("YEAR(pi.created_at) = $year", NULL, FALSE);
            $this->db->where("MONTH(pi.created_at) = $month", NULL, FALSE);
            $this->db->where('pi.deleted_at IS NULL', NULL, FALSE);
            $this->db->order_by('pi.created_at', 'DESC');
            
            // ✅ Get total count BEFORE limit
            $countQuery = clone $this->db;
            $totalCount = $countQuery->count_all_results();
            
            // ✅ Apply limit and offset
            $this->db->limit($limit, $offset);
            $query = $this->db->get();
            
            if ($query->num_rows() === 0) {
                return [
                    'success' => true,
                    'message' => "No images for {$month}/{$year}",
                    'data' => [],
                    'total' => 0,
                    'pagination' => [
                        'total' => 0,
                        'limit' => $limit,
                        'offset' => $offset,
                        'pages' => 0
                    ]
                ];
            }
            
            return [
                'success' => true,
                'message' => "Media for {$month}/{$year} retrieved successfully",
                'data' => $query->result_array(),
                'total' => $totalCount,
                'pagination' => [
                    'total' => $totalCount,
                    'limit' => $limit,
                    'offset' => $offset,
                    'pages' => ceil($totalCount / $limit)
                ]
            ];
            
        } catch (Exception $e) {
            log_message('error', 'get_media_by_date Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'data' => [],
                'total' => 0
            ];
        }
    }

    /**
     * ✅ COMPLETE & PERFECT: Attach multiple images với logic hoàn hảo
     * 
     * @param int $product_id - ID của product
     * @param array $image_ids - Mảng các image IDs cần gắn
     * @return array - Response với counts chi tiết
     */
    public function attach_multiple_images($product_id, $image_ids = []) {
        try {
            $product_id = (int)$product_id;
            
            if ($product_id <= 0 || empty($image_ids) || !is_array($image_ids)) {
                return ['success' => false, 'message' => 'Invalid parameters'];
            }
            
            log_message('info', "🔍 MODEL START: product={$product_id}, images=" . json_encode($image_ids));
            
            // ===== STEP 1: Get existing images =====
            $existing = $this->db
                ->select('id, deleted_at')
                ->where('product_id', $product_id)
                ->get('product_images')
                ->result_array();
            
            $existing_ids = array_column($existing, 'id');
            $soft_deleted_ids = [];
            
            foreach ($existing as $img) {
                if (!empty($img['deleted_at'])) {
                    $soft_deleted_ids[] = (int)$img['id'];
                }
            }
            
            log_message('info', "📊 Existing: total=" . count($existing_ids) . ", soft_deleted=" . count($soft_deleted_ids));
            log_message('info', "📊 Existing IDs: " . json_encode($existing_ids));
            
            // ===== STEP 2: Classify =====
            $to_attach = [];
            $to_restore = [];
            $duplicates = [];
            
            foreach ($image_ids as $img_id) {
                $img_id = (int)$img_id;
                
                // Check if image ALREADY in THIS product
                if (in_array($img_id, $existing_ids)) {
                    if (in_array($img_id, $soft_deleted_ids)) {
                        $to_restore[] = $img_id;
                        log_message('info', "♻️ {$img_id}: RESTORE");
                    } else {
                        $duplicates[] = $img_id;
                        log_message('info', "⚠️ {$img_id}: DUPLICATE");
                    }
                } else {
                    // NOT in product yet - Check if exists in DB
                    $check = $this->db->where('id', $img_id)->get('product_images')->row_array();
                    
                    if (!$check) {
                        log_message('error', "❌ {$img_id}: NOT FOUND in database");
                        continue;
                    }
                    
                    // Check if belongs to ANOTHER product
                    if (!empty($check['product_id']) && (int)$check['product_id'] !== $product_id) {
                        log_message('error', "❌ {$img_id}: Belongs to product {$check['product_id']}");
                        continue;
                    }
                    
                    // Image exists and available → Can attach
                    $to_attach[] = $img_id;
                    log_message('info', "✅ {$img_id}: ATTACH (current_product_id=" . ($check['product_id'] ?? 'NULL') . ")");
                }
            }
            
            log_message('info', "📋 Classification: attach=" . count($to_attach) . ", restore=" . count($to_restore) . ", duplicate=" . count($duplicates));
            
            // ===== STEP 3: Execute =====
            $this->db->trans_start();
            
            $attached = 0;
            if (!empty($to_attach)) {
                $this->db->where_in('id', $to_attach)->update('product_images', [
                    'product_id' => $product_id,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $attached = $this->db->affected_rows();
            }
            
            $restored = 0;
            if (!empty($to_restore)) {
                $this->db->where_in('id', $to_restore)->update('product_images', [
                    'deleted_at' => NULL,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $restored = $this->db->affected_rows();
            }
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                return ['success' => false, 'message' => 'Transaction failed'];
            }
            
            // ===== STEP 4: Sync product images JSON =====
            try {
                $this->sync_product_images($product_id);
                log_message('info', "✅ Product images JSON synced");
            } catch (Exception $e) {
                log_message('error', "⚠️ sync_product_images failed: " . $e->getMessage());
            }
            
            // ===== STEP 5: Build response =====
            $total_success = $attached + $restored;
            $duplicate_count = count($duplicates);
            
            $parts = [];
            if ($attached > 0) $parts[] = "Gắn {$attached} ảnh mới";
            if ($restored > 0) $parts[] = "Khôi phục {$restored} ảnh";
            if ($duplicate_count > 0) $parts[] = "Bỏ qua {$duplicate_count} ảnh đã có";
            
            $message = $total_success > 0 
                ? '✅ ' . implode('. ', $parts)
                : '⚠️ Không có ảnh mới được gắn';
            
            log_message('info', "✅ FINAL: attached={$attached}, restored={$restored}, duplicate={$duplicate_count}");
            
            return [
                'success' => true,
                'message' => $message,
                'attached_count' => $attached,
                'restored_count' => $restored,
                'duplicate_count' => $duplicate_count,
                'total_success' => $total_success,
            ];
            
        } catch (Exception $e) {
            log_message('error', '❌ MODEL Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function get_product_categories($product_id) {
        $this->db->select('pc.id, pc.name, pc.slug');
        $this->db->from('product_category_links pcl');
        $this->db->join('product_categories pc', 'pc.id = pcl.category_id', 'left');
        $this->db->where('pcl.product_id', $product_id);
        $query = $this->db->get();
        return $query->result_array();
    }
    
    // Function lấy danh sách tên category theo id (helper trong model hoặc dưới function)
    private function get_category_names_by_ids($ids) {
        if (!is_array($ids) || empty($ids)) return [];
        $this->db->select('id, name');
        $this->db->from('product_categories');
        $this->db->where_in('id', $ids);
        $query = $this->db->get();
        $map = [];
        foreach ($query->result_array() as $row) {
            $map[(int)$row['id']] = $row['name'];
        }
        $names = [];
        foreach ($ids as $id) {
            if (isset($map[(int)$id])) $names[] = $map[(int)$id];
        }
        return $names;
    }


} // ← END OF CLASS