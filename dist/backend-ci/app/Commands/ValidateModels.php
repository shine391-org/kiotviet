<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Validate Models Command
 * 
 * Checks for environment-specific issues in models
 * Usage: php spark validate:models
 */
class ValidateModels extends BaseCommand
{
    protected $group = 'Development';
    protected $name = 'validate:models';
    protected $description = 'Validate models for environment-specific issues';
    protected $usage = 'validate:models';
    protected $arguments = [];
    protected $options = [];

    public function run(array $params)
    {
        CLI::write('🔍 Validating models...', 'white');

        $errors = [];
        
        // Check for test prefixes in development
        if (ENVIRONMENT === 'development') {
            $modelFiles = glob(APPPATH . 'Models/*.php');
            
            foreach ($modelFiles as $file) {
                $content = file_get_contents($file);
                
                // Check for db_ prefix in table names
                if (preg_match("/protected\s+\$table\s*=\s*['\"]db_/", $content)) {
                    $modelName = basename($file, '.php');
                    $errors[] = "❌ {$modelName}: Test prefix 'db_' detected in development environment";
                }
            }
        }

        // Check specific models
        $modelsToCheck = [
            'ProductModel',
            'OrderModel', 
            'OrderItemModel',
            'ProductCategoryLinkModel',
            'WebhookEventModel',
            'WebhookSubscriptionModel'
        ];

        foreach ($modelsToCheck as $modelClass) {
            $modelFile = APPPATH . 'Models/' . $modelClass . '.php';
            if (file_exists($modelFile)) {
                $content = file_get_contents($modelFile);
                
                // Validate table name format
                if (preg_match("/protected\s+\$table\s*=\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
                    $tableName = $matches[1];
                    
                    if (strpos($tableName, 'db_') === 0 && ENVIRONMENT === 'development') {
                        $errors[] = "❌ {$modelClass}: Using test table prefix '{$tableName}' in development";
                    }
                }
            }
        }

        // Report results
        if (empty($errors)) {
            CLI::write('✅ All models validated successfully!', 'green');
            CLI::write('   - No test prefixes found in development', 'green');
            CLI::write('   - All table names are environment-appropriate', 'green');
            return 0;
        } else {
            CLI::write('❌ Validation failed!', 'red');
            CLI::write('Found the following issues:', 'red');
            
            foreach ($errors as $error) {
                CLI::write("   {$error}", 'red');
            }
            
            CLI::write("\n💡 To fix these issues:", 'yellow');
            CLI::write("   1. Remove 'db_' prefixes from table names in development", 'yellow');
            CLI::write("   2. Use framework DBPrefix configuration instead", 'yellow');
            CLI::write("   3. Run tests in proper test environment", 'yellow');
            
            return 1;
        }
    }
}