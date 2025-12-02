<?php

namespace App\Models;

use CodeIgniter\Model;

/** GL entry schema. @agent-model: gl_entries */
class GLEntryModel extends Model
{
    protected $table = 'gl_entries';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'posting_date',
        'account_id',
        'debit',
        'credit',
        'party_type',
        'party_id',
        'reference_type',
        'reference_id',
        'remarks',
        'currency',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
