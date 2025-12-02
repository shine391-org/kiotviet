<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Maintenance work order schema.
 *
 * @agent-model: maintenance_work_orders
 * @agent-pattern: CI4 model
 */
class MaintenanceWorkOrderModel extends Model
{
    protected $table = 'maintenance_work_orders';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'work_order_number',
        'asset_id',
        'schedule_id',
        'status',
        'description',
        'planned_date',
        'completed_at',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
