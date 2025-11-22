<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Quick manual check for media attach on real DB (no mocks).
 *
 * @agent-command: Debug attach images
 */
class TestAttach extends BaseCommand
{
    protected $group = 'custom';
    protected $name = 'test:attach';
    protected $description = 'Attach images to a hardcoded variant and dump DB rows.';

    public function run(array $params)
    {
        $variantId = 2133;
        $mediaIds = [2, 3, 5];

        try {
            $service = service('productVariantService');
            $result = $service->attachImages($variantId, $mediaIds);

            if (!is_array($result) || ($result['success'] ?? false) !== true) {
                throw new \RuntimeException('Service returned invalid result: ' . json_encode($result));
            }

            $db = \Config\Database::connect();
            $rows = $db->table('product_images')
                ->where('variant_id', $variantId)
                ->get()
                ->getResultArray();

            CLI::write('=== Service result ===');
            CLI::write(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            CLI::write('=== DB rows (product_images where variant_id=2133) ===');
            CLI::write('Count: ' . count($rows));
            CLI::write(json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            CLI::error('Attach verification failed: ' . $e->getMessage());
            CLI::error($e->getTraceAsString());
            exit(1);
        }
    }
}
