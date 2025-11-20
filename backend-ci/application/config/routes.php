<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a URL
| normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

/*
| -------------------------------------------------------------------------
| ✅ AUTHENTICATION ROUTES (Auth Controller - NEW)
| -------------------------------------------------------------------------
| Handles user authentication, profile, and permissions
| All requests use JWT Bearer token (except login)
*/

// Public routes (no auth required)
$route['api/auth/login']['POST'] = 'api/Auth/login';
$route['api/auth/forgot-password']['POST'] = 'api/Auth/forgot_password';
$route['api/auth/reset-password']['POST'] = 'api/Auth/reset_password';

// Protected routes (JWT required)
$route['api/auth/me']['GET'] = 'api/Auth/me';
$route['api/auth/me/permissions']['GET'] = 'api/Auth/permissions';
$route['api/auth/logout']['POST'] = 'api/Auth/logout';
$route['api/auth/change-password']['POST'] = 'api/Auth/change_password';

// Refresh token (optional - if you want to implement token refresh)
$route['api/auth/refresh']['POST'] = 'api/Auth/refresh_token';

/*
| -------------------------------------------------------------------------
| USER MANAGEMENT ROUTES
| -------------------------------------------------------------------------
| CRUD operations for users, profile, sessions, and activity logs
*/

// User CRUD
$route['api/users']['GET'] = 'api/users/index';
$route['api/users']['POST'] = 'api/users/create';
$route['api/users/(:num)']['GET'] = 'api/users/show/$1';
$route['api/users/(:num)']['PUT'] = 'api/users/update/$1';
$route['api/users/(:num)']['DELETE'] = 'api/users/delete/$1';

// Change password for specific user (Admin only)
$route['api/users/(:num)/change-password']['PUT'] = 'api/users/change_password_admin/$1';
$route['api/users/(:num)/change-password']['POST'] = 'api/users/change_password_admin/$1';

// Get branches for user dropdown
$route['api/users/branches']['GET'] = 'api/users/branches';

// Profile Management (current user)
$route['api/users/profile']['GET'] = 'api/users/profile';
$route['api/users/profile']['PUT'] = 'api/users/update_profile';
$route['api/users/avatar']['POST'] = 'api/users/upload_avatar';

// Session Management
$route['api/users/sessions']['GET'] = 'api/users/sessions';
$route['api/users/sessions/logout-others']['POST'] = 'api/users/logout_other_sessions';
$route['api/users/sessions/(:num)']['DELETE'] = 'api/users/delete_session/$1';

// Activity Audit Logs
$route['api/users/(:num)/activities']['GET'] = 'api/users/activities/$1';
$route['api/users/activities']['GET'] = 'api/users/activities'; // Current user activities
$route['api/users/(:num)/login-history']['GET'] = 'api/users/login_history/$1';
$route['api/users/login-history']['GET'] = 'api/users/login_history'; // Current user login history

/*
| -------------------------------------------------------------------------
| ROLE & PERMISSION MANAGEMENT ROUTES
| -------------------------------------------------------------------------
| Manage roles and assign permissions to roles
*/

// Role CRUD
$route['api/roles']['GET'] = 'api/Roles/index';
$route['api/roles']['POST'] = 'api/Roles/create';
$route['api/roles/(:num)']['GET'] = 'api/Roles/show/$1';
$route['api/roles/(:num)']['PUT'] = 'api/Roles/update/$1';
$route['api/roles/(:num)']['DELETE'] = 'api/Roles/delete/$1';
$route['roles/edit/(:num)'] = 'api/roles/show/$1';

// Role Permissions Management
$route['api/roles/(:num)/permissions']['GET'] = 'api/Roles/permissions/$1';
$route['api/roles/(:num)/assign-permissions']['POST'] = 'api/Roles/assign_permissions/$1';
$route['api/roles/(:num)/assign-permissions']['PUT'] = 'api/Roles/assign_permissions/$1';

// Get all permissions (for dropdowns/checkboxes)
$route['api/permissions']['GET'] = 'api/Roles/getallpermissions';

// Assign role to user (optional - if you want direct user-role assignment)
$route['api/users/(:num)/assign-role']['POST'] = 'api/users/assign_role/$1';

//test routes
$route['api/users/test-permissions'] = 'api/users/test_permissions';


/*
| -------------------------------------------------------------------------
| BRANCH MANAGEMENT ROUTES
| -------------------------------------------------------------------------
| Manage branches (Chi nhánh)
*/

// Branch CRUD
$route['api/branches']['GET'] = 'api/branches/index';
$route['api/branches']['POST'] = 'api/branches/create';
$route['api/branches/(:num)']['GET'] = 'api/branches/view/$1';
$route['api/branches/(:num)']['PUT'] = 'api/branches/update/$1';
$route['api/branches/(:num)']['DELETE'] = 'api/branches/delete/$1';

// Branch Relations & Statistics
$route['api/branches/(:num)/employees']['GET'] = 'api/branches/employees/$1';
$route['api/branches/(:num)/statistics']['GET'] = 'api/branches/statistics/$1';
$route['api/branches/(:num)/inventory']['GET'] = 'api/branches/inventory/$1';

// Import/Export
$route['api/branches/import']['POST'] = 'api/branches/import';
$route['api/branches/export']['GET'] = 'api/branches/export';

// ========== PERMISSIONS ROUTES ==========
$route['api/permissions']['GET'] = 'api/permissions/index';
$route['api/permissions/(:num)']['GET'] = 'api/permissions/show/$1';

/*
| ============================================================================
| PRODUCT MANAGEMENT ROUTES
| ============================================================================
| Controller: application/controllers/api/Products.php
| Version: 2.0 - Fixed to match controller methods
| Date: 2025-10-27
*/
/*
| ============================================================================
| ✨ PRODUCT VARIANTS ROUTES (MUST BE BEFORE generic /api/products/:num routes)
| ============================================================================
| Important: More specific routes MUST come BEFORE generic routes!
*/

// Get variants by product - MUST use UPPERCASE GET/POST/PUT/DELETE

$route['api/products/(:num)/variants']['GET'] = 'api/variants/index_get/$1';
$route['api/products/(:num)/variants']['POST'] = 'api/variants/create_post/$1';
// GET: Lấy danh sách biến thể đã xóa mềm theo product_id
$route['api/variants/deleted']['GET'] = 'api/variants/deleted_get';

// PUT: Khôi phục (restore) biến thể đã xóa mềm
$route['api/variants/(:num)/restore']['PUT'] = 'api/variants/restore_put/$1';

// ✅ NEW: DELETE: Xóa vĩnh viễn biến thể (hard delete - không thể khôi phục)
$route['api/variants/(:num)/hard']['DELETE'] = 'api/variants/hard_delete_delete/$1';


// Get/Update/Delete single variant
$route['api/variants/(:num)']['GET'] = 'api/variants/detail_get/$1';
$route['api/variants/(:num)']['PUT'] = 'api/variants/update_put/$1';      // ← Match method name
$route['api/variants/(:num)']['DELETE'] = 'api/variants/delete_delete/$1';  // ← Match method name

// Get product with variants (alternative endpoint)
$route['api/products/(:num)/with-variants']['GET'] = 'api/products/get_with_variants/$1';

/*
| ============================================================================
| PRODUCT MANAGEMENT ROUTES - FIXED V3
| ============================================================================
*/

// List products
$route['api/products']['GET'] = 'api/products/index_get';

// Quick search (MUST BE BEFORE (:num))
$route['api/products/search']['GET'] = 'api/products/search_get';

// Get statistics
$route['api/products/statistics']['GET'] = 'api/products/statistics_get';

// ✅ GET: Get product images by product_id
$route['api/products/(:num)/images']['get'] = 'api/Products/get_images/$1';

// Get product detail
$route['api/products/(:num)']['GET'] = 'api/products/detail_get/$1';

// Create product
$route['api/products']['POST'] = 'api/products/create_post';

// Update product - FIX: Use correct method name
$route['api/products/(:num)']['PUT'] = 'api/products/update/$1';
$route['api/products/(:num)']['PATCH'] = 'api/products/update/$1';

// Delete product - FIX: Use correct method name
$route['api/products/(:num)']['DELETE'] = 'api/products/delete/$1';

// Route RESTful chuẩn (GET method)
$route['api/products/(:num)/detail-with-variants']['GET'] = 'api/products/get_detail_with_variants/$1';


// ============================================================
// PRODUCT CATEGORIES ROUTES (Add after Products routes)
// ============================================================

// Product Categories - List (GET)
$route['api/product-categories']['get'] = 'api/Product_categories/index';

// Product Categories - Statistics (GET) - Must be BEFORE :id route
$route['api/product-categories/statistics']['get'] = 'api/Product_categories/statistics';

// Product Categories - Detail (GET)
$route['api/product-categories/(:num)']['get'] = 'api/Product_categories/detail/$1';

// Product Categories - Create (POST)
$route['api/product-categories']['post'] = 'api/Product_categories/create';

// Product Categories - Update (PUT)
$route['api/product-categories/(:num)']['put'] = 'api/Product_categories/update/$1';

// Product Categories - Delete (DELETE)
$route['api/product-categories/(:num)']['delete'] = 'api/Product_categories/delete/$1';

// Hard delete product category
$route['api/product-categories/(:num)/hard']['delete'] = 'api/Product_categories/hard_delete_delete/$1';

// restore soft delete category
$route['api/product-categories/(:num)/restore']['put'] = 'api/Product_categories/restore_put/$1';

// GET - Get all products in a category tree
$route['api/product-categories/(:num)/products']['get'] = 'api/Product_categories/products/$1';

// thêm route GET /api/product-categories/:id/products-with-variants
$route['api/product-categories/(:num)/products-with-variants']['GET'] = 'api/Product_categories/products_with_variants/$1';



// ============================================================================
// PRODUCT IMAGES - UPLOAD & MANAGEMENT
// ============================================================================

// ✅ POST: Upload image
$route['api/products/upload']['post'] = 'api/Products/upload';

// ✅ PUT: Set primary image
$route['api/products/images/(:num)/set-primary']['put'] = 'api/Products/set_primary_image/$1';

// ✅ DELETE: Delete image (soft delete)
$route['api/products/images/(:num)']['delete'] = 'api/Products/delete_image/$1';

// ✅ POST: Upload multiple images for product (batch upload)
$route['api/products/upload-multiple']['post'] = 'api/Products/upload_multiple_post';

// upload variant images
$route['api/variants/(:num)/upload-multiple']['POST'] = 'api/variants/upload_multiple_post/$1';

//attach variant images
$route['api/variants/(:num)/images/attach-multiple']['POST'] = 'api/variants/attach_multiple_images/$1';

// ============================================================================
// 🆕 MEDIA LIBRARY ENDPOINTS (Phase 1B - NEW)
// ============================================================================

// Get media library - browse all images
$route['api/products/media/library']['GET'] = 'api/Products/get_media_library_get';

// Get media by date (month/year)
$route['api/products/media/by-date']['GET'] = 'api/Products/get_media_by_date_get';

// Search media by SKU
$route['api/products/media/search-sku']['GET'] = 'api/Products/search_media_by_sku_get';

// Attach multiple images to product - POST
$route['api/products/(:num)/images/attach-multiple']['POST'] = 'api/Products/attach_multiple_images/$1';

/*
| ============================================================================
| PRODUCT ATTRIBUTES ROUTES (CORRECTED - 2025-11-10)
| ============================================================================
| ✅ TÊN HÀM PHẢI KHỚP CHÍNH XÁC VỚI CONTROLLER
*/

// ============================================================
// ATTRIBUTES CRUD
// ============================================================
// Đường dẫn RESTful để xóa attribute khỏi product
$route['api/products/(:num)/attribute-values/(:num)']['DELETE'] = 'api/attributes/removeAttributeFromProduct/$1/$2';

$route['api/attributes/remove-from-variant/(:num)/(:num)']['DELETE'] = 'api/Attributes/remove_attribute_from_variant_delete/$1/$2';
$route['api/attributes/(:num)/generate-variants']['POST'] = 'api/Attributes/generate_variants_post/$1';
$route['api/attributes']['GET'] = 'api/Attributes/index_get';
$route['api/attributes/(:num)']['GET'] = 'api/Attributes/detail_get/$1';
$route['api/attributes']['POST'] = 'api/Attributes/create_post';
$route['api/attributes/(:num)']['PUT'] = 'api/Attributes/update_put/$1';
$route['api/attributes/(:num)']['DELETE'] = 'api/Attributes/delete_delete/$1';
// Lấy products/variants sử dụng attribute
$route['api/attributes/(:num)/products']['GET'] = 'api/Attributes/products_get/$1';

// ============================================================
// ATTRIBUTE OPTIONS
// ============================================================

$route['api/attributes/(:num)/options']['GET'] = 'api/Attributes/options_get/$1';

// ✅ FIX: create_option_post (ĐÚNG TÊN HÀM)
$route['api/attributes/(:num)/options']['POST'] = 'api/Attributes/create_option_post/$1';

// ✅ FIX: update_option_put (ĐÚNG TÊN HÀM)
$route['api/attributes/options/(:num)']['PUT'] = 'api/Attributes/update_option_put/$1';

// ✅ FIX: delete_option_delete (ĐÚNG TÊN HÀM)
$route['api/attributes/options/(:num)']['DELETE'] = 'api/Attributes/delete_option_delete/$1';
// Lấy products/variants sử dụng option
$route['api/attributes/options/(:num)/products']['GET'] = 'api/Attributes/products_by_option_get/$1';


// ============================================================
// ATTRIBUTE VALUES
// ============================================================
//Trả về danh sách option_id đã được dùng trong các variant của product
//Format: { attribute_id: [option_id1, option_id2, ...], ... }
$route['api/products/(:num)/used-attribute-options']['GET'] = 'api/products/used_attribute_options_get/$1';

$route['api/products/(:num)/attribute-values']['GET'] = 'api/Attributes/product_values_get/$1';
// ✅ NEW: Update attribute values for product (match frontend)
$route['api/products/(:num)/attribute-values']['post'] = 'api/attributes/update_product_values_post/$1';
$route['api/variants/(:num)/attribute-values']['GET'] = 'api/Attributes/variant_values_get/$1';

// ✅ FIX: sync_variant_values_post (ĐÚNG TÊN HÀM)
$route['api/variants/(:num)/attribute-values/sync']['POST'] = 'api/Attributes/sync_variant_values_post/$1';



/*
| -------------------------------------------------------------------------
| CUSTOMER MANAGEMENT ROUTES (Future - chưa implement)
| -------------------------------------------------------------------------
*/

// Customer CRUD
$route['api/customers']['GET'] = 'api/customers/index';
$route['api/customers']['POST'] = 'api/customers/create';
$route['api/customers/(:num)']['GET'] = 'api/customers/show/$1';
$route['api/customers/(:num)']['PUT'] = 'api/customers/update/$1';
$route['api/customers/(:num)']['DELETE'] = 'api/customers/delete/$1';

// Customer Groups
$route['api/customer-groups']['GET'] = 'api/customer_groups/index';
$route['api/customer-groups']['POST'] = 'api/customer_groups/create';

// Customer Purchase History
$route['api/customers/(:num)/orders']['GET'] = 'api/customers/orders/$1';
$route['api/customers/(:num)/debt']['GET'] = 'api/customers/debt/$1';

// Import/Export
$route['api/customers/import']['POST'] = 'api/customers/import';
$route['api/customers/export']['GET'] = 'api/customers/export';

/*
| -------------------------------------------------------------------------
| SALES ORDER MANAGEMENT ROUTES (Future - chưa implement)
| -------------------------------------------------------------------------
*/

// Sales Order CRUD
$route['api/orders']['GET'] = 'api/orders/index';
$route['api/orders']['POST'] = 'api/orders/create';
$route['api/orders/(:num)']['GET'] = 'api/orders/show/$1';
$route['api/orders/(:num)']['PUT'] = 'api/orders/update/$1';
$route['api/orders/(:num)']['DELETE'] = 'api/orders/delete/$1';

// Order Actions
$route['api/orders/(:num)/approve']['POST'] = 'api/orders/approve/$1';
$route['api/orders/(:num)/cancel']['POST'] = 'api/orders/cancel/$1';
$route['api/orders/(:num)/print']['GET'] = 'api/orders/print/$1';
$route['api/orders/(:num)/invoice']['GET'] = 'api/orders/generate_invoice/$1';

// Payment
$route['api/orders/(:num)/payments']['POST'] = 'api/orders/add_payment/$1';
$route['api/orders/(:num)/payments']['GET'] = 'api/orders/payments/$1';

/*
| -------------------------------------------------------------------------
| INVENTORY MANAGEMENT ROUTES (Future - chưa implement)
| -------------------------------------------------------------------------
*/

// Inventory Movements
$route['api/inventory/movements']['GET'] = 'api/inventory/movements';
$route['api/inventory/stock-in']['POST'] = 'api/inventory/stock_in';
$route['api/inventory/stock-out']['POST'] = 'api/inventory/stock_out';
$route['api/inventory/transfer']['POST'] = 'api/inventory/transfer';
$route['api/inventory/adjustment']['POST'] = 'api/inventory/adjustment';

// Stock Check
$route['api/inventory/products/(:num)']['GET'] = 'api/inventory/product_stock/$1';
$route['api/inventory/branches/(:num)']['GET'] = 'api/inventory/branch_stock/$1';

/*
| -------------------------------------------------------------------------
| PARTNER/VENDOR MANAGEMENT ROUTES (Future - chưa implement)
| -------------------------------------------------------------------------
*/

$route['api/partners']['GET'] = 'api/partners/index';
$route['api/partners']['POST'] = 'api/partners/create';
$route['api/partners/(:num)']['GET'] = 'api/partners/show/$1';
$route['api/partners/(:num)']['PUT'] = 'api/partners/update/$1';
$route['api/partners/(:num)']['DELETE'] = 'api/partners/delete/$1';

/*
| -------------------------------------------------------------------------
| REPORT & DASHBOARD ROUTES (Future - chưa implement)
| -------------------------------------------------------------------------
*/

// Dashboard
$route['api/dashboard/stats']['GET'] = 'api/dashboard/statistics';
$route['api/dashboard/revenue']['GET'] = 'api/dashboard/revenue';
$route['api/dashboard/top-products']['GET'] = 'api/dashboard/top_products';
$route['api/dashboard/recent-orders']['GET'] = 'api/dashboard/recent_orders';

// Reports
$route['api/reports/sales']['GET'] = 'api/reports/sales';
$route['api/reports/inventory']['GET'] = 'api/reports/inventory';
$route['api/reports/customers']['GET'] = 'api/reports/customers';
$route['api/reports/employees']['GET'] = 'api/reports/employees';

// Export Reports
$route['api/reports/sales/export']['GET'] = 'api/reports/export_sales';
$route['api/reports/inventory/export']['GET'] = 'api/reports/export_inventory';

/*
| -------------------------------------------------------------------------
| SETTINGS & CONFIGURATION ROUTES (Future - chưa implement)
| -------------------------------------------------------------------------
*/

$route['api/settings']['GET'] = 'api/settings/index';
$route['api/settings']['PUT'] = 'api/settings/update';
$route['api/settings/company']['GET'] = 'api/settings/company';
$route['api/settings/company']['PUT'] = 'api/settings/update_company';

/*
| -------------------------------------------------------------------------
| FILE UPLOAD ROUTES (Generic)
| -------------------------------------------------------------------------
*/

$route['api/upload/image']['POST'] = 'api/upload/image';
$route['api/upload/file']['POST'] = 'api/upload/file';
$route['api/upload/avatar']['POST'] = 'api/upload/avatar';

/*
| -------------------------------------------------------------------------
| UTILITY ROUTES
| -------------------------------------------------------------------------
*/

$route['api/health']['GET'] = 'api/utility/health_check';
$route['api/version']['GET'] = 'api/utility/version';

/*
| -------------------------------------------------------------------------
| CATCH-ALL ROUTE (Must be at the end)
| -------------------------------------------------------------------------
| Handle undefined routes with proper error message
*/

// API 404 handler
$route['api/(:any)'] = 'api/error/not_found';