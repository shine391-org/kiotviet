<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

/**
 * Debug Controller - TEMPORARY for debugging customer debt data
 * DELETE THIS FILE AFTER DEBUGGING
 * 
 * @deprecated This controller should be removed after debugging is complete
 */
class DebugController extends BaseController
{
    use ResponseTrait;

    private const MAX_DEBUG_ROWS = 1000;

    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Check customer debt data
     * GET /api/debug/debt-check
     * 
     * WARNING: This endpoint exposes sensitive data. It is restricted to:
     * - Non-production environments only
     * - Authenticated users with admin role
     */
    public function debtCheck(): \CodeIgniter\HTTP\ResponseInterface
    {
        // Block in production environment
        if (ENVIRONMENT === 'production') {
            return $this->failForbidden('Debug endpoints are disabled in production');
        }

        // Require authentication
        $user = auth()->user();
        if (!$user) {
            return $this->failUnauthorized('Authentication required');
        }

        // Require admin role (check via model_has_roles)
        $hasAdminRole = $this->db->table('model_has_roles')
            ->where('model_id', $user->id)
            ->where('model_type', 'App\\Models\\User')
            ->whereIn('role_id', [1]) // super-admin role
            ->countAllResults() > 0;

        if (!$hasAdminRole) {
            return $this->failForbidden('Admin access required');
        }

        $result = [
            'table_exists' => $this->db->tableExists('customer_debt_transactions'),
            'total_records' => 0,
            'sample_data' => [],
            'customer_ids_with_data' => [],
            'test_query_for_2014' => [],
        ];

        if ($result['table_exists']) {
            $result['total_records'] = $this->db->table('customer_debt_transactions')
                ->countAllResults();

            $result['sample_data'] = $this->db->table('customer_debt_transactions')
                ->select('id, customer_id, code, type, value, deleted_at')
                ->limit(10)
                ->get()
                ->getResultArray();

            $result['customer_ids_with_data'] = $this->db->table('customer_debt_transactions')
                ->select('customer_id, COUNT(*) as cnt')
                ->where('deleted_at IS NULL')
                ->groupBy('customer_id')
                ->orderBy('cnt', 'DESC')
                ->limit(self::MAX_DEBUG_ROWS)
                ->get()
                ->getResultArray();

            // Test query for customer 2014
            $result['test_query_for_2014'] = $this->db->table('customer_debt_transactions')
                ->select('code, created_at, type, value, balance')
                ->where('customer_id', 2014)
                ->where('deleted_at IS NULL')
                ->get()
                ->getResultArray();
        }

        return $this->respond([
            'success' => true,
            'data' => $result,
        ]);
    }
}
