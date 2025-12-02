<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Config\Services;
use Config\Database;

/**
 * Kiểm tra nhanh các route API cần có (so với FE) bằng cách so khớp cấu hình router,
 * không gọi HTTP nên không phá dữ liệu.
 */
class ApiRoutesTest extends TestCase
{
    /** @test */
    public function expected_routes_are_registered()
    {
        // Chuẩn bị MySQL test connection
        $db = Database::connect('tests');
        $db->query("CREATE TABLE IF NOT EXISTS products (id INT AUTO_INCREMENT PRIMARY KEY)");

        // Reset services and manually load routes from Config/Routes.php
        Services::reset(true);
        
        // Get a fresh routes collection and load our route definitions
        $routes = Services::routes();
        require APPPATH . 'Config/Routes.php';

        // Dùng placeholder, không cần truy vấn DB
        // CI lưu route đã compile với regex ([0-9]+)
        $num = '([0-9]+)';

        $expect = [
            'get' => [
                'api/products',
                "api/products/{$num}",
                "api/products/{$num}/detail-with-variants",
                "api/products/{$num}/variants",
                "api/products/{$num}/images",
                "api/products/{$num}/used-attribute-options",
                "api/products/{$num}/attribute-values",
                'api/products/export',
                "api/products/{$num}/analytics",
                'api/products/media/library',
                'api/products/media/by-date',
                'api/products/media/search-sku',
                'api/product-categories',
                "api/product-categories/{$num}",
                'api/variants',
                'api/variants/deleted',
                "api/variants/{$num}",
                "api/variants/{$num}/attribute-values",
                'api/attributes',
                "api/attributes/{$num}",
                "api/attributes/{$num}/options",
                "api/attributes/options/{$num}/products",
                'api/roles',
                'api/permissions',
                'api/branches',
                'api/branches/export',
                'api/users',
                "api/users/{$num}",
                'api/users/me',
                'api/users/roles',
                'api/users/branches',
            ],
            'post' => [
                'api/auth/login',
                'api/products',
                'api/products/check-code',
                'api/products/upload',
                'api/products/upload-multiple',
                "api/products/{$num}/images/attach-multiple",
                "api/products/{$num}/attribute-values",
                'api/products/import',
                "api/products/{$num}/variants",
                "api/variants/{$num}/upload-multiple",
                "api/variants/{$num}/images/attach-multiple",
                "api/variants/{$num}/attribute-values/sync",
                'api/attribute-values',
                'api/attributes',
                "api/attributes/{$num}/options",
                'api/roles/create',
                'api/branches',
                'api/users/create',
            ],
            'put' => [
                "api/products/{$num}",
                "api/products/images/{$num}/set-primary",
                "api/variants/{$num}",
                "api/variants/{$num}/restore",
                "api/users/update/{$num}",
                "api/users/{$num}/change-password",
                "api/attributes/{$num}",
                "api/attributes/options/{$num}",
            ],
            'delete' => [
                "api/products/{$num}",
                "api/products/images/{$num}",
                "api/products/{$num}/attribute-values/{$num}",
                "api/variants/{$num}",
                "api/variants/{$num}/hard",
                "api/attributes/remove-from-variant/{$num}/{$num}",
                "api/attributes/{$num}",
                "api/attributes/options/{$num}",
                "api/attribute-values/{$num}",
                "api/users/delete/{$num}",
            ],
        ];

        foreach ($expect as $method => $list) {
            $registered = $routes->getRoutes(strtoupper($method));
            foreach ($list as $pattern) {
                // Check both pattern as-is and with newlines/regex variations
                $found = isset($registered[$pattern]) ||
                         isset($registered[str_replace('/', '\/', $pattern)]) ||
                         array_key_exists($pattern, $registered);

                if (!$found && str_contains($pattern, '([0-9]+)')) {
                    // Try alternative regex patterns CodeIgniter might use
                    $altPatterns = [
                        str_replace('([0-9]+)', '(\d+)', $pattern),
                        str_replace('([0-9]+)', '[^/]+', $pattern),
                    ];
                    foreach ($altPatterns as $alt) {
                        if (isset($registered[$alt])) {
                            $found = true;
                            break;
                        }
                    }
                }

                $this->assertTrue($found, "Thiếu route {$method} {$pattern}");
            }
        }
    }
}
