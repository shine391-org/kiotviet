<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'username','email','password','full_name','phone','avatar','branch_id','status',
        'last_login_at','last_login_ip','remember_token','two_factor_secret','two_factor_enabled',
        'password_changed_at','failed_login_attempts','account_locked_until','timezone','created_at','updated_at','deleted_at'
    ];
    protected $useTimestamps = false;
}
