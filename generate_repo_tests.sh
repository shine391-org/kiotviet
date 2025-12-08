#!/bin/bash

# Get all Repository files
find backend-ci/app/Repositories -name "*.php" | while read repo_file; do
    # Extract class name
    class_name=$(basename "$repo_file" .php)
    # Extract namespace path
    ns_path=$(echo "$repo_file" | sed 's|backend-ci/app/Repositories/||' | sed 's|/|\\\\|g' | sed 's|\.php||')
    # Check if test exists
    test_file="backend-ci/tests/Repositories/${class_name}Test.php"
    if [ ! -f "$test_file" ]; then
        echo "Creating test for $class_name"
        cat > "$test_file" << EOF
<?php

namespace Tests\Repositories;

use App\Repositories\\$ns_path;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: $class_name
 */
class ${class_name}Test extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private $class_name \$repo;

    protected function setUp(): void
    {
        parent::setUp();
        \$this->setUpDatabase();
        \$this->repo = new $class_name(null, null, \$this->db);
    }

    protected function tearDown(): void
    {
        \$this->tearDownDatabase();
        parent::tearDown();
    }

    // TODO: Add comprehensive tests for all public methods
    // Follow TESTING-RULES.md: success cases, validation errors, edge cases (null, empty, boundaries), error handling
    // Use DevDatabaseTrait for database isolation
    // No artificial test passing
}
EOF
    fi
done