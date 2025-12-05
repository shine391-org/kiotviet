<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table = 'permissions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['name','display_name','description','module','module_group','guard_name','created_at','updated_at','deleted_at'];
}
