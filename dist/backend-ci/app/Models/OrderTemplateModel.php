<?php

namespace App\Models;

use CodeIgniter\Model;

/** Order template schema. @agent-model: order_templates */
class OrderTemplateModel extends Model
{
    protected $table = 'order_templates';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['name', 'customer_id', 'frequency', 'is_active', 'notes', 'created_at', 'updated_at'];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
