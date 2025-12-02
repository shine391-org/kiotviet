<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Asset schema.
 *
 * @agent-model: assets
 * @agent-pattern: CI4 model
 */
class AssetModel extends Model
{
    protected $table = 'assets';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'asset_number',
        'asset_name',
        'category',
        'purchase_date',
        'cost',
        'location',
        'status',
        'salvage_value',
        'useful_life_months',
        'created_by',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
