<?php

namespace App\Models;

use CodeIgniter\Model;

/** Branch schema model. @agent-model: branches */
class BranchModel extends Model
{
    protected $table = 'branches';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'code','name','phone','address','ward','district','city','status','is_active',
        'created_at','updated_at','deleted_at'
    ];
    protected $useTimestamps = false;
}
