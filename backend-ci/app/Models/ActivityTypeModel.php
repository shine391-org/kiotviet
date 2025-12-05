<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Activity type schema.
 *
 * @agent-model: activity_types
 * @agent-pattern: CI4 model
 */
class ActivityTypeModel extends Model
{
    protected $table = 'activity_types';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'activity_name',
        'billing_rate',
        'cost_rate',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
