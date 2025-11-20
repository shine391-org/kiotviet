<?php

namespace App\Models;

use CodeIgniter\Model;

class ModelHasRolesModel extends Model
{
    protected $table = 'model_has_roles';
    protected $primaryKey = null;
    protected $returnType = 'array';
    protected $allowedFields = ['role_id','model_type','model_id'];
    public $incrementing = false;
    protected $useAutoIncrement = false;
}
