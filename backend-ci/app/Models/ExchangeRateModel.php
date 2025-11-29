<?php

namespace App\Models;

use CodeIgniter\Model;

/** Exchange rate schema. @agent-model: exchange_rates */
class ExchangeRateModel extends Model
{
    protected $table = 'exchange_rates';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'currency',
        'rate',
        'valid_from',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
