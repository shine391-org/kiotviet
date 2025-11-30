<?php

namespace App\Services\Assets;

use App\Repositories\Assets\AssetRepository;
use App\Validators\AssetValidator;
use RuntimeException;

/**
 * Asset lifecycle service.
 *
 * @agent-service: Asset
 * @agent-pattern: CRUD + status
 * @agent-reusable: MEDIUM
 */
class AssetService
{
    protected AssetRepository $assets;
    protected AssetValidator $validator;

    public function __construct(?AssetRepository $assets = null, ?AssetValidator $validator = null)
    {
        $this->assets = $assets ?? new AssetRepository();
        $this->validator = $validator ?? new AssetValidator();
    }

    /** @agent-use: GET /api/assets @agent-pattern: List */
    public function list(array $filters): array
    {
        return ['success' => true, 'data' => $this->assets->list($filters)];
    }

    /** @agent-use: GET /api/assets/{id} */
    public function show(int $id): array
    {
        return ['success' => true, 'data' => $this->require($id)];
    }

    /** @agent-use: POST /api/assets */
    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $number = $this->assets->nextNumber();
        $asset = $this->assets->create($data + ['asset_number' => $number]);
        return ['success' => true, 'data' => $asset];
    }

    /** @agent-use: POST /api/assets/{id}/capitalize */
    public function capitalize(int $id): array
    {
        $asset = $this->require($id);
        if ($asset['status'] === 'active') {
            return ['success' => true, 'data' => $asset];
        }
        $this->assets->update($id, ['status' => 'active']);
        return ['success' => true, 'data' => $this->require($id)];
    }

    /** @agent-use: POST /api/assets/{id}/dispose */
    public function dispose(int $id): array
    {
        $this->require($id);
        $this->assets->update($id, ['status' => 'disposed']);
        return ['success' => true, 'data' => $this->require($id)];
    }

    /** @agent-use: POST /api/assets/{id}/status */
    public function changeStatus(int $id, array $input): array
    {
        $this->require($id);
        $data = $this->validator->validateStatus($input);
        $this->assets->update($id, ['status' => $data['status']]);
        return ['success' => true, 'data' => $this->require($id)];
    }

    private function require(int $id): array
    {
        $row = $this->assets->find($id);
        if (! $row) {
            throw new RuntimeException('Asset not found');
        }
        return $row;
    }
}
