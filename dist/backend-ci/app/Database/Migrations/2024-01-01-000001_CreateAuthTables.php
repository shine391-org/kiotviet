<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuthTables extends Migration
{
    public function up()
    {
        // users
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'username' => ['type' => 'VARCHAR', 'constraint' => 100],
            'email' => ['type' => 'VARCHAR', 'constraint' => 255],
            'password' => ['type' => 'VARCHAR', 'constraint' => 255],
            'full_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'avatar' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['active','inactive','suspended'], 'default' => 'active'],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
            'last_login_ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'remember_token' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'two_factor_secret' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'two_factor_enabled' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'password_changed_at' => ['type' => 'DATETIME', 'null' => true],
            'failed_login_attempts' => ['type' => 'INT', 'default' => 0],
            'account_locked_until' => ['type' => 'DATETIME', 'null' => true],
            'timezone' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'Asia/Ho_Chi_Minh'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('username');
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('users', true);

        // roles
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'guard_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT', 'null' => true],
            'is_system' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('roles', true);

        // permissions
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'display_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'module' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'module_group' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'guard_name' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('permissions', true);

        // role_has_permissions
        $this->forge->addField([
            'permission_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'role_id' => ['type' => 'BIGINT', 'unsigned' => true],
        ]);
        $this->forge->addKey(['permission_id','role_id'], true);
        $this->forge->createTable('role_has_permissions', true);

        // model_has_roles
        $this->forge->addField([
            'role_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'model_type' => ['type' => 'VARCHAR', 'constraint' => 255],
            'model_id' => ['type' => 'BIGINT', 'unsigned' => true],
        ]);
        $this->forge->addKey(['role_id','model_id','model_type'], true);
        $this->forge->createTable('model_has_roles', true);

        // sessions
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'token' => ['type' => 'VARCHAR', 'constraint' => 500],
            'device_info' => ['type' => 'TEXT', 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'last_activity' => ['type' => 'DATETIME', 'null' => true],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token');
        $this->forge->createTable('sessions', true);

        // login_attempts
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'username' => ['type' => 'VARCHAR', 'constraint' => 100],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45],
            'user_agent' => ['type' => 'TEXT', 'null' => true],
            'success' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'failure_reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'attempted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('login_attempts', true);
    }

    public function down()
    {
        $this->forge->dropTable('login_attempts', true);
        $this->forge->dropTable('sessions', true);
        $this->forge->dropTable('model_has_roles', true);
        $this->forge->dropTable('role_has_permissions', true);
        $this->forge->dropTable('permissions', true);
        $this->forge->dropTable('roles', true);
        $this->forge->dropTable('users', true);
    }
}
