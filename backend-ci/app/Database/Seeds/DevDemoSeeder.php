<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Database\Seeds\DevSeeder;

/**
 * DevDemoSeeder - tự động chạy tất cả seeders trong namespace Demo cho môi trường dev.
 *
 * @agent-seeder: Dev demo data
 * @agent-pattern: Auto-scan Demo seeders
 * @agent-reusable: HIGH
 */
class DevDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (ENVIRONMENT !== 'development') {
            echo "DevDemoSeeder skipped (ENV=" . ENVIRONMENT . ")\n";
            return;
        }

        // Base dev seed (roles/users/master data) if available
        if (class_exists(DevSeeder::class)) {
            echo "Seeding base dev data: " . DevSeeder::class . "\n";
            $this->call(DevSeeder::class);
        }

        $dir = APPPATH . 'Database/Seeds/Demo';
        $namespace = 'App\\Database\\Seeds\\Demo\\';

        if (! is_dir($dir)) {
            echo "No Demo seeders found (" . $dir . ")\n";
            return;
        }

        foreach (glob($dir . '/*.php') as $file) {
            $class = $namespace . pathinfo($file, PATHINFO_FILENAME);

            // Ensure class is loaded
            if (! class_exists($class)) {
                require_once $file;
            }

            if (! class_exists($class)) {
                echo "Skipping {$file}: class {$class} not found\n";
                continue;
            }

            echo "Seeding demo: {$class}\n";
            $this->call($class);
        }
    }
}
