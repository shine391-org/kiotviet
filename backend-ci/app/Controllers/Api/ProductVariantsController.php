<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ProductVariantV2Model;
use CodeIgniter\API\ResponseTrait;

class ProductVariantsController extends BaseController
{
    use ResponseTrait;

    protected ProductVariantV2Model $variants;

    public function __construct()
    {
        $this->variants = new ProductVariantV2Model();
    }

    public function show($id)
    {
        $variant = $this->variants->find($id);
        if (!$variant) {
            return $this->failNotFound('Variant not found');
        }
        return $this->respond(['success' => true, 'data' => $variant]);
    }

    /**
     * POST /api/variants/{id}/upload-multiple
     * Lưu file vào writable/uploads/variants và trả về URLs.
     */
    public function uploadMultiple($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid variant id');
        }

        $files = $this->request->getFiles()['files'] ?? [];
        if (empty($files)) {
            return $this->failValidationErrors('No files uploaded');
        }

        $uploadPath = WRITEPATH . 'uploads/variants';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0775, true);
        }

        $urls = [];
        foreach ($files as $file) {
            if (!$file->isValid()) {
                continue;
            }
            $newName = $file->getRandomName();
            $file->move($uploadPath, $newName);
            $urls[] = '/uploads/variants/' . $newName;
        }

        return $this->respond([
            'success' => true,
            'data' => [
                'variant_id' => $id,
                'files' => $urls,
            ],
        ]);
    }
}
