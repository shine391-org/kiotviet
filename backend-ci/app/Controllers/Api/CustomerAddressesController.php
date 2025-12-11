<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

/**
 * Customer Addresses API Controller
 * Handles CRUD operations for customer shipping addresses.
 * 
 * @agent-controller: CustomerAddresses
 * @agent-pattern: Thin controller with inline logic
 */
class CustomerAddressesController extends BaseController
{
    use ResponseTrait;

    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * List addresses for a customer.
     * GET /api/customers/{customerId}/addresses
     */
    public function index($customerId = null): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->wrap(function () use ($customerId) {
            if (! $customerId) {
                return $this->failValidationErrors('Customer ID is required');
            }

            $rows = $this->db->table('customer_addresses')
                ->where('customer_id', (int) $customerId)
                ->where('deleted_at', null)
                ->orderBy('is_default', 'DESC')
                ->orderBy('created_at', 'DESC')
                ->get()
                ->getResultArray();

            return $this->respond([
                'success' => true,
                'data' => $rows,
            ]);
        });
    }

    /**
     * Create a new address for a customer.
     * POST /api/customers/{customerId}/addresses
     */
    public function create($customerId = null): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->wrap(function () use ($customerId) {
            if (! $customerId) {
                return $this->failValidationErrors('Customer ID is required');
            }

            $input = $this->safeInput();
            
            $data = [
                'customer_id' => (int) $customerId,
                'name' => trim($input['name'] ?? '') ?: null,
                'recipient_name' => trim($input['recipient_name'] ?? '') ?: null,
                'phone' => trim($input['phone'] ?? '') ?: null,
                'address' => trim($input['address'] ?? '') ?: null,
                'province' => trim($input['province'] ?? '') ?: null,
                'district' => trim($input['district'] ?? '') ?: null,
                'ward' => trim($input['ward'] ?? '') ?: null,
                'is_default' => (int) ($input['is_default'] ?? 0),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            // Use transaction for atomic default address handling
            $this->db->transStart();

            try {
                // If this is set as default, unset others
                if ($data['is_default']) {
                    $this->db->table('customer_addresses')
                        ->where('customer_id', (int) $customerId)
                        ->update(['is_default' => 0]);
                }

                $this->db->table('customer_addresses')->insert($data);
                $data['id'] = $this->db->insertID();

                $this->db->transComplete();

                if ($this->db->transStatus() === false) {
                    throw new \RuntimeException('Failed to create address');
                }

                return $this->respondCreated([
                    'success' => true,
                    'data' => $data,
                    'message' => 'Đã thêm địa chỉ thành công',
                ]);
            } catch (\Throwable $e) {
                $this->db->transRollback();
                throw $e;
            }
        });
    }

    /**
     * Update an address.
     * PUT /api/customers/{customerId}/addresses/{id}
     */
    public function update($customerId = null, $id = null): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->wrap(function () use ($customerId, $id) {
            if (! $customerId || ! $id) {
                return $this->failValidationErrors('Customer ID and Address ID are required');
            }

            $existing = $this->db->table('customer_addresses')
                ->where('id', (int) $id)
                ->where('customer_id', (int) $customerId)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if (! $existing) {
                return $this->failNotFound('Address not found');
            }

            $input = $this->safeInput();

            $data = [
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if (array_key_exists('name', $input)) {
                $data['name'] = trim($input['name']) ?: null;
            }
            if (array_key_exists('recipient_name', $input)) {
                $data['recipient_name'] = trim($input['recipient_name']) ?: null;
            }
            if (array_key_exists('phone', $input)) {
                $data['phone'] = trim($input['phone']) ?: null;
            }
            if (array_key_exists('address', $input)) {
                $data['address'] = trim($input['address']) ?: null;
            }
            if (array_key_exists('province', $input)) {
                $data['province'] = trim($input['province']) ?: null;
            }
            if (array_key_exists('district', $input)) {
                $data['district'] = trim($input['district']) ?: null;
            }
            if (array_key_exists('ward', $input)) {
                $data['ward'] = trim($input['ward']) ?: null;
            }

            // Use transaction for atomic default address handling
            $this->db->transStart();

            try {
                if (array_key_exists('is_default', $input)) {
                    $data['is_default'] = (int) $input['is_default'];
                    if ($data['is_default']) {
                        $this->db->table('customer_addresses')
                            ->where('customer_id', (int) $customerId)
                            ->where('id !=', (int) $id)
                            ->update(['is_default' => 0]);
                    }
                }

                $this->db->table('customer_addresses')
                    ->where('id', (int) $id)
                    ->update($data);

                $this->db->transComplete();

                if ($this->db->transStatus() === false) {
                    throw new \RuntimeException('Failed to update address');
                }

                return $this->respond([
                    'success' => true,
                    'message' => 'Đã cập nhật địa chỉ',
                ]);
            } catch (\Throwable $e) {
                $this->db->transRollback();
                throw $e;
            }
        });
    }

    /**
     * Delete an address.
     * DELETE /api/customers/{customerId}/addresses/{id}
     */
    public function delete($customerId = null, $id = null): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->wrap(function () use ($customerId, $id) {
            if (! $customerId || ! $id) {
                return $this->failValidationErrors('Customer ID and Address ID are required');
            }

            $existing = $this->db->table('customer_addresses')
                ->where('id', (int) $id)
                ->where('customer_id', (int) $customerId)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if (! $existing) {
                return $this->failNotFound('Address not found');
            }

            $this->db->table('customer_addresses')
                ->where('id', (int) $id)
                ->update([
                    'deleted_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return $this->respond([
                'success' => true,
                'message' => 'Đã xóa địa chỉ',
            ]);
        });
    }

    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\DomainException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (\RuntimeException $e) {
            log_message('error', 'CustomerAddressesController error: ' . $e->getMessage());
            return $this->failServerError('A server error occurred');
        } catch (\Throwable $e) {
            log_message('error', 'CustomerAddressesController error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->failServerError('An internal server error occurred');
        }
    }

    private function safeInput(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                return $json;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }
}
