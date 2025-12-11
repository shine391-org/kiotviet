<?php

namespace App\Controllers;

use App\Services\PurchaseReturnService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * PurchaseReturnController - API endpoints for purchase returns (Trả hàng nhập)
 * @agent-layer: backend-controller
 */
class PurchaseReturnController extends ResourceController
{
    protected $format = 'json';
    protected PurchaseReturnService $service;

    public function __construct()
    {
        $this->service = new PurchaseReturnService();
    }

    /**
     * GET /api/purchase-returns
     * List purchase returns with filters and pagination
     */
    public function index(): ResponseInterface
    {
        $filters = [
            'branch_id' => $this->request->getGet('branch_id'),
            'status' => $this->request->getGet('status'),
            'supplier_id' => $this->request->getGet('supplier_id'),
            'created_by' => $this->request->getGet('created_by'),
            'returned_by' => $this->request->getGet('returned_by'),
            'date_from' => $this->request->getGet('date_from'),
            'date_to' => $this->request->getGet('date_to'),
            'search' => $this->request->getGet('search'),
            'page' => $this->request->getGet('page') ?? 1,
            'limit' => $this->request->getGet('limit') ?? 15,
        ];

        // Handle status as array
        if (is_string($filters['status']) && str_contains($filters['status'], ',')) {
            $filters['status'] = explode(',', $filters['status']);
        }

        $result = $this->service->list(array_filter($filters));

        return $this->respond($result);
    }

    /**
     * GET /api/purchase-returns/:id
     * Get single purchase return detail
     */
    public function show($id = null): ResponseInterface
    {
        $return = $this->service->findById((int)$id);

        if (!$return) {
            return $this->failNotFound('Purchase return not found');
        }

        return $this->respond(['data' => $return]);
    }

    /**
     * POST /api/purchase-returns
     * Create new purchase return
     */
    public function create(): ResponseInterface
    {
        $data = $this->request->getJSON(true);

        if (!$data) {
            return $this->fail('Invalid request data');
        }

        $items = $data['items'] ?? [];
        unset($data['items']);

        // Require authentication - no silent fallback
        $userId = auth()->id();
        if (!$userId) {
            return $this->failUnauthorized('Authentication required');
        }

        try {
            $result = $this->service->create($data, $items, $userId);
            return $this->respondCreated(['data' => $result, 'message' => 'Purchase return created successfully']);
        } catch (\InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * PUT /api/purchase-returns/:id
     * Update purchase return
     */
    public function update($id = null): ResponseInterface
    {
        // Require authentication
        $userId = auth()->id();
        if (!$userId) {
            return $this->failUnauthorized('Authentication required');
        }

        $data = $this->request->getJSON(true);

        if (!$data) {
            return $this->fail('Invalid request data');
        }

        $items = $data['items'] ?? [];
        unset($data['items']);

        try {
            $result = $this->service->update((int)$id, $data, $items);
            if (!$result) {
                return $this->failNotFound('Purchase return not found');
            }
            return $this->respond(['data' => $result, 'message' => 'Purchase return updated successfully']);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * DELETE /api/purchase-returns/:id
     * Delete purchase return
     */
    public function delete($id = null): ResponseInterface
    {
        // Require authentication
        $userId = auth()->id();
        if (!$userId) {
            return $this->failUnauthorized('Authentication required');
        }

        try {
            $deleted = $this->service->delete((int)$id);
            if (!$deleted) {
                return $this->failNotFound('Purchase return not found');
            }
            return $this->respondDeleted(['message' => 'Purchase return deleted successfully']);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * POST /api/purchase-returns/:id/status
     * Update purchase return status
     */
    public function updateStatus($id = null): ResponseInterface
    {
        $userId = auth()->id();
        if (!$userId) {
            return $this->failUnauthorized('Authentication required');
        }

        $data = $this->request->getJSON(true);
        $status = $data['status'] ?? null;

        if (!$status || !in_array($status, ['draft', 'returned', 'cancelled'])) {
            return $this->fail('Invalid status');
        }

        try {
            $updated = $this->service->updateStatus((int)$id, $status, (int)$userId);
            if (!$updated) {
                return $this->failNotFound('Purchase return not found');
            }
            return $this->respond(['message' => 'Status updated successfully']);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * GET /api/purchase-returns/export
     * Export purchase returns to Excel
     */
    public function export(): ResponseInterface
    {
        $filters = [
            'branch_id' => $this->request->getGet('branch_id'),
            'status' => $this->request->getGet('status'),
            'supplier_id' => $this->request->getGet('supplier_id'),
            'date_from' => $this->request->getGet('date_from'),
            'date_to' => $this->request->getGet('date_to'),
            'search' => $this->request->getGet('search'),
        ];

        // Handle status as array
        if (is_string($filters['status']) && str_contains($filters['status'], ',')) {
            $filters['status'] = explode(',', $filters['status']);
        }

        try {
            $filename = $this->service->export(array_filter($filters));
            $basename = basename($filename);

            // Register cleanup to delete temp file after response is sent
            register_shutdown_function(function () use ($filename) {
                if (file_exists($filename)) {
                    @unlink($filename);
                }
            });

            // Use CodeIgniter's download method for streaming
            // Pass file path as first arg and display name as second arg
            return $this->response->download($filename, $basename);
        } catch (\Exception $e) {
            return $this->fail('Export failed: ' . $e->getMessage());
        }
    }
}
