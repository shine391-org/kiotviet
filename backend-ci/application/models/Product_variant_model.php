<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Product Variant Model
 * Handles all variant-related database operations
 * 
 * MATCH SCHEMA: product_variants_v2
 * ✅ FIXED: Remove is_active - use only status field
 * 
 * @version 2.0
 * @date 2025-11-01
 */

class Product_variant_model extends CI_Model {
    
    private $table = 'product_variants_v2';
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get variant by ID (only active status)
     * ✅ FIXED: Use only status field
     */
    // Hàm get_by_id với alias bảng
    public function get_by_id($variant_id) {
        try {
            $query = $this->db
                ->where('id', $variant_id)
                ->where('status', 'active')  // variant table alias không cần vì chỉ 1 bảng
                ->get($this->table);
            
            $result = $query->row_array();
            
            if (!empty($result['attributes'])) {
                $result['attributes'] = json_decode($result['attributes'], true);
            }
            
            return $result;
            
        } catch (Exception $e) {
            log_message('error', 'Product_variant_model::get_by_id - ' . $e->getMessage());
            return null;
        }
    }    
    
    public function get_by_id_with_images($variant_id) {
        try {
            $variant = $this->db
                ->where('id', $variant_id)
                ->where('status', 'active')
                ->get('product_variants_v2')
                ->row_array();
    
            if (!$variant) {
                return null;
            }
    
            if (!empty($variant['attributes'])) {
                $variant['attributes'] = json_decode($variant['attributes'], true);
            }
    
            $images = $this->db
                ->select('id, image_url, file_name, is_primary, sort_order, deleted_at')
                ->where('variant_id', $variant_id)
                ->where('deleted_at IS NULL', null, false)
                ->order_by('sort_order', 'ASC')
                ->get('product_images')
                ->result_array();
    
            $variant['images'] = $images;
    
            return $variant;
        } catch (Exception $e) {
            log_message('error', 'Product_variant_model::get_by_id_with_images - ' . $e->getMessage());
            return null;
        }
    }    
    
    /**
     * Create variantget_variant_attribute_values
     */
    public function create($data) {
        try {
            // Encode attributes if array
            if (!empty($data['attributes']) && is_array($data['attributes'])) {
                $data['attributes'] = json_encode($data['attributes'], JSON_UNESCAPED_UNICODE);
            }

            // Gán variant_signature nếu có trong $data
            if (!empty($data['variant_signature'])) {
                $data['variant_signature'] = $data['variant_signature'];
            }
            
            // Loại bỏ các trường không hợp lệ và set mặc định
            unset($data['created_by'], $data['updated_by'], $data['is_active']);
            $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
            $data['status'] = $data['status'] ?? 'active';
            $data['min_stock'] = $data['min_stock'] ?? 0;
            $data['max_stock'] = $data['max_stock'] ?? 0;
            
            // Set created_at if not set
            if (empty($data['created_at'])) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            
            // Set status to active if not set
            if (empty($data['status'])) {
                $data['status'] = 'active';
            }

            // Đảm bảo 2 trường mới có giá trị, nếu không set mặc định
            if (!isset($data['min_stock'])) {
                $data['min_stock'] = 0;
            }
            if (!isset($data['max_stock'])) {
                $data['max_stock'] = 0;
            }
            
            if ($this->db->insert($this->table, $data)) {
                return $this->db->insert_id();
            } else {
                log_message('error', 'Product_variant_model::create - Insert failed');
                return false;
            }
            
        } catch (Exception $e) {
            log_message('error', 'Product_variant_model::create - ' . $e->getMessage());
            return false;
        }
    }
    
    public function update($variant_id, $data) {
        try {
            // Only allow these fields
            $allowed = ['variant_name', 'sku', 'barcode', 'price', 'cost_price', 
                       'stock_quantity', 'image_url', 'attributes', 'status', 'min_stock', 'max_stock', 'updated_at', 'variant_signature'];
            
            $update_data = array_intersect_key($data, array_flip($allowed));
            
            // ------------------ START FIX -----------------------
            if (!empty($update_data['attributes']) && is_array($update_data['attributes'])) {
                $update_data['attributes'] = json_encode($update_data['attributes'], JSON_UNESCAPED_UNICODE);
            }
            // ------------------ END FIX -------------------------
            
            if (empty($update_data)) {
                return false;
            }
            
            return $this->db->where('id', $variant_id)->update($this->table, $update_data);
            
        } catch (Exception $e) {
            log_message('error', 'Update error: ' . $e->getMessage());
            return false;
        }
    }


    /**
     * Soft delete variant
     * ✅ FIXED: Set status to 'inactive' (not 'deleted')
     */
    public function delete($variant_id) {
        try {
            // Start transaction
            $this->db->trans_start();
            // 1. Soft-delete variant
            $this->db->where('id', $variant_id);
            $this->db->update($this->table, [
                'status' => 'inactive',
                'deleted_at' => date('Y-m-d H:i:s')
            ]);
            
            // 2. Soft-delete attribute values liên quan
            $this->db->where('variant_id', $variant_id);
            $this->db->update('product_attribute_values', [
                'deleted_at' => date('Y-m-d H:i:s')
            ]);
            
            // Complete transaction
            $this->db->trans_complete();
            
            // Check transaction status
            if ($this->db->trans_status() === FALSE) {
                log_message('error', 'Transaction failed for delete variant');
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            log_message('error', 'Product_variant_model::delete - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if SKU exists
     * ✅ FIXED: Use only status field
     */
    public function check_sku_exists($sku, $exclude_id = null) {
        try {
            $this->db->where('sku', $sku)
                ->where('status', 'active');  // ✅ CHANGED: from is_active to status
            
            if (!empty($exclude_id)) {
                $this->db->where('id !=', $exclude_id);
            }
            
            $query = $this->db->get($this->table);
            return $query->num_rows() > 0;
            
        } catch (Exception $e) {
            log_message('error', 'Product_variant_model::check_sku_exists - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sum stock by product
     * ✅ FIXED: Use only status field
     */
    public function sum_stock_by_product($product_id) {
        $this->db->reset_query();
        $this->db->select_sum('pv.stock_quantity', 'total_stock');
        $this->db->from('product_variants_v2 pv');
        $this->db->where('pv.product_id', $product_id);
        $this->db->where('pv.status', 'active');
        $query = $this->db->get();
        $row = $query->row();
        return !empty($row->total_stock) ? (int)$row->total_stock : 0;
    }    
    
    /**
     * Count active variants by product
     * ✅ FIXED: Use only status field
     */
    public function count_active_by_product($product_id) {
        try {
            return $this->db
                ->where('product_id', $product_id)
                ->where('status', 'active')  // ✅ CHANGED: from is_active to status
                ->count_all_results($this->table);
            
        } catch (Exception $e) {
            log_message('error', 'Product_variant_model::count_active_by_product - ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * ✅ COMPLETE & PERFECT: Attach multiple images cho variant với full logic
     * 
     * @param int $variant_id - ID của variant
     * @param array $image_ids - Mảng các image IDs cần gắn
     * @return array - Response với counts chi tiết
     */
    public function attach_images($variantId, $imageIds = []) {
        try {
            $variantId = (int)$variantId;
            
            if ($variantId <= 0 || empty($imageIds) || !is_array($imageIds)) {
                return ['success' => false, 'message' => 'Invalid parameters'];
            }
            
            // ===== STEP 1: Get existing images for this variant (cả bị xóa mềm lẫn chưa xóa mềm) =====
            $existing = $this->db
                ->select('id, deleted_at')
                ->where('variant_id', $variantId)
                ->get('product_images')
                ->result_array();
            
            $existing_ids = array_column($existing, 'id');
            $soft_deleted_ids = [];
            foreach ($existing as $img) {
                if (!empty($img['deleted_at'])) {
                    $soft_deleted_ids[] = (int)$img['id'];
                }
            }
            
            // ===== STEP 2: Phân loại =====
            $to_attach = [];
            $to_restore = [];
            $duplicates = [];
    
            foreach ($imageIds as $img_id) {
                $img_id = (int)$img_id;
                
                if (in_array($img_id, $existing_ids)) {
                    if (in_array($img_id, $soft_deleted_ids)) {
                        $to_restore[] = $img_id;
                    } else {
                        $duplicates[] = $img_id;
                    }
                } else {
                    // Check trong DB: ảnh tồn tại, chưa bị xóa mềm, chưa gắn variant nào khác
                    $this->db->reset_query(); // clear trước với CI < 4
                    $check = $this->db
                        ->where('id', $img_id)
                        ->where('deleted_at IS NULL', null, false)
                        ->get('product_images')->row_array();
    
                    if (!$check) continue; // Không tìm thấy hoặc bị xóa mềm
    
                    // Có thể rule "ảnh đã gán cho product khác với variant_id null thì không attach cho variant"
                    // Nếu cần kiểm tra thêm product_id ở đây
    
                    $to_attach[] = $img_id;
                }
            }
    
            // ===== STEP 3: Thực hiện update =====
            $this->db->trans_start();
    
            $attached = 0;
            if (!empty($to_attach)) {
                $this->db->where_in('id', $to_attach)->update(
                    'product_images',
                    ['variant_id' => $variantId, 'deleted_at' => NULL, 'updated_at' => date('Y-m-d H:i:s')]
                );
                $attached = $this->db->affected_rows();
            }
    
            $restored = 0;
            if (!empty($to_restore)) {
                $this->db->where_in('id', $to_restore)->update(
                    'product_images',
                    ['deleted_at' => NULL, 'updated_at' => date('Y-m-d H:i:s')]
                );
                $restored = $this->db->affected_rows();
            }
            
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                return ['success' => false, 'message' => 'Transaction failed'];
            }
            
            // ===== STEP 4: Build response =====
            $total_success = $attached + $restored;
            $duplicate_count = count($duplicates);
            $parts = [];
            if ($attached > 0) $parts[] = "Gắn {$attached} ảnh mới";
            if ($restored > 0) $parts[] = "Khôi phục {$restored} ảnh";
            if ($duplicate_count > 0) $parts[] = "Bỏ qua {$duplicate_count} ảnh đã có";
            $message = $total_success > 0
                ? '✅ ' . implode('. ', $parts)
                : '⚠️ Không có ảnh mới được gắn';
            
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
    
    /**
     * Get variant statistics
     * ✅ FIXED: Use only status field
     */
    public function get_statistics($product_id = null) {
        try {
            $this->db->select('COUNT(id) as total_variants, SUM(stock_quantity) as total_stock, AVG(price) as avg_price');
            
            if (!empty($product_id)) {
                $this->db->where('product_id', $product_id);
            }
            
            $this->db->where('p.status', 'active');  // ✅ CHANGED: from is_active to status
            
            $query = $this->db->get($this->table);
            $result = $query->row_array();
            
            return [
                'total_variants' => (int)($result['total_variants'] ?? 0),
                'total_stock' => (int)($result['total_stock'] ?? 0),
                'avg_price' => (float)($result['avg_price'] ?? 0)
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Product_variant_model::get_statistics - ' . $e->getMessage());
            return [
                'total_variants' => 0,
                'total_stock' => 0,
                'avg_price' => 0
            ];
        }
    }
    /**
     * Lấy variant đã bị xóa mềm
     */
    public function get_deleted_variant($variant_id) {
        try {
            $query = $this->db
                ->where('id', $variant_id)
                ->where('status', 'inactive')
                ->where('deleted_at IS NOT NULL', null, false)
                ->get($this->table);

            return $query->row_array();
        } catch (Exception $e) {
            log_message('error', 'Product_variant_model::get_deleted_variant - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Khôi phục soft-delete cho variant và attribute values
     */
    public function restore_with_attributes($variant_id) {
        try {
            $this->db->trans_start();

            // Restore variant
            $this->db->where('id', $variant_id);
            $this->db->update($this->table, [
                'status' => 'active',
                'deleted_at' => NULL
            ]);

            // Restore attribute values liên quan
            $this->db->where('variant_id', $variant_id);
            $this->db->update('product_attribute_values', [
                'deleted_at' => NULL
            ]);

            $this->db->trans_complete();

            return $this->db->trans_status() !== FALSE;

        } catch (Exception $e) {
            log_message('error', 'Product_variant_model::restore_with_attributes - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy danh sách các variant đã bị xóa mềm theo product (nếu cần hiển thị danh sách đã xóa)
     */
    public function get_deleted_variants($product_id = null, $limit = 50, $offset = 0) {
        try {
            $this->db->where('status', 'inactive');
            $this->db->where('deleted_at IS NOT NULL', null, false);

            if (!empty($product_id)) {
                $this->db->where('product_id', $product_id);
            }

            $this->db->limit($limit, $offset);
            $this->db->order_by('deleted_at', 'DESC');

            $query = $this->db->get($this->table);
            return $query->result_array();

        } catch (Exception $e) {
            log_message('error', 'Product_variant_model::get_deleted_variants - ' . $e->getMessage());
            return [];
        }
    }

    public function get_by_product_id($product_id) {
        $this->db->reset_query();
        $this->db->from('product_variants_v2 pv');
        $this->db->where('pv.product_id', $product_id);
        $this->db->where('pv.status', 'active');
        $this->db->order_by('pv.created_at', 'ASC');
        $query = $this->db->get();
        $results = $query->result_array();
    
        foreach ($results as &$variant) {
            if (!empty($variant['attributes'])) {
                $variant['attributes'] = json_decode($variant['attributes'], true);
            }
        }
        return $results;
    }                
    

    /**
     * Xóa vĩnh viễn biến thể (hard delete)
     * Kiểm tra tồn kho, xóa attribute values, ảnh, biến thể
     * @param int $variant_id
     * @return array ['success' => bool, 'message' => string, 'product_id' => int|null]
     */
    public function hard_delete_variant($variant_id) {
        try {
            $variant = $this->db
                ->where('id', $variant_id)
                ->get('product_variants_v2')
                ->row_array();

            if (!$variant) {
                return ['success' => false, 'message' => 'Không tìm thấy biến thể', 'product_id' => null];
            }

            if ($variant['stock_quantity'] > 0) {
                return [
                    'success' => false,
                    'message' => "❌ Không thể xóa vĩnh viễn! Biến thể còn tồn kho ({$variant['stock_quantity']} cái).",
                    'product_id' => $variant['product_id']
                ];
            }

            $product_id = $variant['product_id'];

            $this->db->trans_start();

            // Xóa attribute values
            $this->db->where('variant_id', $variant_id)
                ->delete('product_attribute_values');

            // Unlink ảnh variant (có thể bổ sung xóa ảnh nếu cần)
            $this->db->where('variant_id', $variant_id)
                ->update('product_images', ['variant_id' => NULL, 'deleted_at' => date('Y-m-d H:i:s')]);

            // Xóa biến thể vĩnh viễn
            $this->db->where('id', $variant_id)
                ->delete('product_variants_v2');

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                return ['success' => false, 'message' => 'Xóa vĩnh viễn biến thể thất bại', 'product_id' => $product_id];
            }

            return ['success' => true, 'message' => 'Đã xóa vĩnh viễn biến thể', 'product_id' => $product_id];

        } catch (Exception $e) {
            log_message('error', 'Product_variant_model::hard_delete_variant - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi khi xóa vĩnh viễn biến thể: ' . $e->getMessage(), 'product_id' => null];
        }
    }

    // kiểm tra trùng attribute khi gán attribute cho variant trong sản phẩm cha
    public function check_variant_signature_exists($product_id, $exclude_variant_id, $variant_signature) {
        $this->db->from($this->table);
        $this->db->where('product_id', $product_id);
        $this->db->where('variant_signature', $variant_signature);
        if (!empty($exclude_variant_id)) {
            $this->db->where('id !=', $exclude_variant_id);
        }
        // Đếm cả soft deleted nên không filter deleted_at
        $count = $this->db->count_all_results();
        return $count > 0;
    }

    /**
     * Xây dựng variant signature từ danh sách option_id
     * Chuẩn hóa: sắp xếp các option_id theo thứ tự tăng dần, nối bằng dấu gạch ngang
     * Ví dụ: [5, 2, 10] -> "2-5-10"
     * 
     * @param array $option_ids Mảng các option_id của biến thể
     * @return string Chuỗi signature
     */
    public function build_variant_signature(array $option_ids) {
        if (empty($option_ids)) {
            return '';
        }

        // Tạo bản sao để tránh tác động đến mảng gốc
        $sorted_ids = $option_ids;
        sort($sorted_ids, SORT_NUMERIC);

        // Nối thành chuỗi cách nhau bằng dấu gạch ngang
        $signature = implode('-', $sorted_ids);

        return $signature;
    }

    
}
?>