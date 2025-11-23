<?php

namespace App\Commands;

use App\Services\ProductMedia\ProductMediaService;
use App\Services\Products\ProductService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class VerifyImageManager extends BaseCommand
{
    protected $group = 'custom';
    protected $name = 'verify:image_manager';
    protected $description = 'Manual end-to-end verification for Image Manager on real DB (no mocks).';

    public function run(array $params): int
    {
        $productId = 1984; // hardcoded test product
        $db = Database::connect();
        $productService = service('productService');
        $mediaService = service('productMediaService');

        CLI::write('[STEP 1] Verify media listing vs DB count', 'yellow');
        $dbCount = $db->table('product_images')->where('deleted_at', null)->countAllResults();
        $list = $mediaService->library(['limit' => 5, 'offset' => 0, 'entity_id' => $productId]);
        $listCount = $list['pagination']['total'] ?? -1;
        $isAttachedFlags = array_column($list['data'], 'is_attached', 'id');
        CLI::write("DB total: {$dbCount} | Service total: {$listCount}");
        CLI::write('Sample is_attached flags: ' . json_encode(array_slice($isAttachedFlags, 0, 5, true)));
        if ($dbCount === $listCount) {
            CLI::write('PASS: Listing count matches DB', 'green');
        } else {
            CLI::write('FAIL: Listing count mismatch', 'red');
        }

        // prepare image ids
        $unassigned = $db->table('product_images')
            ->select('id')
            ->where('deleted_at', null)
            ->where('variant_id', null)
            ->where('product_id !=', $productId)
            ->get(5)
            ->getResultArray();
        if (count($unassigned) < 2) {
            CLI::write('FAIL: Need at least 2 available images (variant_id is null)', 'red');
            return 1;
        }
        $newId1 = (int) $unassigned[0]['id'];
        $newId2 = (int) $unassigned[1]['id'];

        $existing = $db->table('product_images')
            ->select('id')
            ->where('deleted_at', null)
            ->where('product_id', $productId)
            ->get(1)
            ->getRowArray();
        if (! $existing) {
            CLI::write('FAIL: No existing image attached to product for duplicate test', 'red');
            return 1;
        }
        $existingId = (int) $existing['id'];

        CLI::write('[STEP 2] Attach tests', 'yellow');

        // Action 1: attach new
        $before = $this->countImages($db, $productId);
        $res1 = $productService->attachImages($productId, [$newId1]);
        $after = $this->countImages($db, $productId);
        CLI::write("Action1 result: " . json_encode($res1, JSON_UNESCAPED_UNICODE));
        if ($after === $before + 1 && $this->exists($db, $productId, $newId1)) {
            CLI::write('PASS: Found new record in DB', 'green');
        } else {
            CLI::write('FAIL: New record not saved', 'red');
        }

        // Action 2: attach same again
        $beforeDup = $this->countImages($db, $productId);
        $res2 = $productService->attachImages($productId, [$newId1]);
        $afterDup = $this->countImages($db, $productId);
        CLI::write("Action2 result: " . json_encode($res2, JSON_UNESCAPED_UNICODE));
        if ($afterDup === $beforeDup) {
            CLI::write('PASS: No duplicate record created', 'green');
        } else {
            CLI::write('FAIL: Duplicate detected', 'red');
        }

        // Action 3: mix existing + new
        $beforeMix = $this->countImages($db, $productId);
        $res3 = $productService->attachImages($productId, [$existingId, $newId2]);
        $afterMix = $this->countImages($db, $productId);
        CLI::write("Action3 result: " . json_encode($res3, JSON_UNESCAPED_UNICODE));
        $added = $afterMix - $beforeMix;
        $expectedMsg = ($res3['attached_count'] ?? 0) >= 1 && ($res3['skipped_count'] ?? 0) >= 1;
        if ($added === 1 && $this->exists($db, $productId, $newId2) && $expectedMsg) {
            CLI::write('PASS: Mix attach added only new image and reported skip', 'green');
        } else {
            CLI::write('FAIL: Mix attach did not behave as expected', 'red');
        }

        // Cleanup
        CLI::write('[CLEANUP] Detach test images', 'yellow');
        $db->table('product_images')->whereIn('id', [$newId1, $newId2])->update([
            'product_id' => null,
            'variant_id' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        CLI::write('Cleanup done. Final count for product ' . $productId . ': ' . $this->countImages($db, $productId));

        return 0;
    }

    private function countImages($db, int $productId): int
    {
        return (int) $db->table('product_images')->where('deleted_at', null)->where('product_id', $productId)->countAllResults();
    }

    private function exists($db, int $productId, int $imageId): bool
    {
        return (bool) $db->table('product_images')->where('id', $imageId)->where('product_id', $productId)->where('deleted_at', null)->countAllResults();
    }
}
