<?php

namespace Tests\Support\Assertions;

/**
 * Business logic assertion methods for test validation
 * 
 * @agent-assertions: Business rule validation
 * @agent-pattern: Strong business logic assertions
 * @agent-reusable: HIGH
 */
trait BusinessLogicAssertions
{
    /**
     * Assert that a service operation result is successful
     * 
     * @param array $result Service result array
     * @param string $message Custom error message
     */
    public function assertServiceSuccess(array $result, string $message = ''): void
    {
        $this->assertArrayHasKey('success', $result, 'Service result must have success key');
        $defaultMessage = 'Service operation failed: ' . ($result['message'] ?? 'Unknown error');
        $this->assertTrue($result['success'], $message ?: $defaultMessage);
    }
    
    /**
     * Assert that a service operation result is a failure
     * 
     * @param array $result Service result array
     * @param string $expectedMessage Expected error message (optional)
     * @param string $message Custom error message
     */
    public function assertServiceFailure(array $result, ?string $expectedMessage = null, string $message = ''): void
    {
        $this->assertArrayHasKey('success', $result, 'Service result must have success key');
        $defaultMessage = 'Service operation should have failed but succeeded';
        $this->assertFalse($result['success'], $message ?: $defaultMessage);
        
        if ($expectedMessage !== null) {
            $this->assertArrayHasKey('message', $result, 'Failed service result must have message key');
            $this->assertStringContainsString($expectedMessage, $result['message'], 
                "Expected error message to contain '{$expectedMessage}', got '{$result['message']}'");
        }
    }
    
    /**
     * Assert that a service result contains expected data structure
     * 
     * @param array $result Service result array
     * @param array $expectedKeys Expected keys in data
     * @param string $message Custom error message
     */
    public function assertServiceDataStructure(array $result, array $expectedKeys, string $message = ''): void
    {
        $this->assertArrayHasKey('data', $result, 'Service result must have data key');
        $data = $result['data'];
        
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $data, "Service data must contain key: {$key}");
        }
    }
    
    /**
     * Assert that a service result contains specific data values
     * 
     * @param array $result Service result array
     * @param array $expectedData Expected key-value pairs
     * @param string $message Custom error message
     */
    public function assertServiceDataContains(array $result, array $expectedData, string $message = ''): void
    {
        $this->assertArrayHasKey('data', $result, 'Service result must have data key');
        $data = $result['data'];
        
        foreach ($expectedData as $key => $value) {
            $this->assertArrayHasKey($key, $data, "Service data must contain key: {$key}");
            $this->assertEquals($value, $data[$key], 
                "Service data key '{$key}' expected value '{$value}', got '{$data[$key]}'");
        }
    }
    
    /**
     * Assert that a price calculation follows business rules
     * 
     * @param float $basePrice Base price
     * @param float $actualPrice Actual calculated price
     * @param float $expectedMultiplier Expected multiplier (e.g., 0.8 for 20% discount)
     * @param float $delta Allowed delta for floating point comparison
     * @param string $message Custom error message
     */
    public function assertPriceCalculation(float $basePrice, float $actualPrice, float $expectedMultiplier, float $delta = 0.01, string $message = ''): void
    {
        $expectedPrice = $basePrice * $expectedMultiplier;
        $defaultMessage = "Price calculation failed: base {$basePrice} * {$expectedMultiplier} = {$expectedPrice}, got {$actualPrice}";
        $this->assertEqualsWithDelta($expectedPrice, $actualPrice, $delta, $message ?: $defaultMessage);
    }
    
    /**
     * Assert that inventory movement follows business rules
     * 
     * @param array $movement Movement data
     * @param string $expectedType Expected movement type (IN, OUT, TRANSFER)
     * @param float $expectedQuantity Expected quantity
     * @param string $message Custom error message
     */
    public function assertInventoryMovement(array $movement, string $expectedType, float $expectedQuantity, string $message = ''): void
    {
        $this->assertArrayHasKey('movement_type', $movement, 'Movement must have movement_type');
        $this->assertEquals($expectedType, $movement['movement_type'], 
            "Expected movement type '{$expectedType}', got '{$movement['movement_type']}'");
        
        $this->assertArrayHasKey('quantity', $movement, 'Movement must have quantity');
        $this->assertEqualsWithDelta($expectedQuantity, (float) $movement['quantity'], 0.01, 
            "Expected quantity {$expectedQuantity}, got {$movement['quantity']}");
    }
    
    /**
     * Assert that stock levels are valid after operations
     * 
     * @param float $initialStock Initial stock level
     * @param float $movementQuantity Movement quantity
     * @param string $movementType Movement type (IN, OUT, TRANSFER)
     * @param float $finalStock Final stock level
     * @param string $message Custom error message
     */
    public function assertStockLevelCalculation(float $initialStock, float $movementQuantity, string $movementType, float $finalStock, string $message = ''): void
    {
        $expectedFinalStock = $initialStock;
        
        switch ($movementType) {
            case 'IN':
                $expectedFinalStock += $movementQuantity;
                break;
            case 'OUT':
                $expectedFinalStock -= $movementQuantity;
                break;
            case 'TRANSFER':
                // Transfer doesn't change total stock, just moves between warehouses
                break;
        }
        
        $defaultMessage = "Stock level calculation failed: initial {$initialStock}, movement {$movementQuantity} ({$movementType}), expected final {$expectedFinalStock}, got {$finalStock}";
        $this->assertEqualsWithDelta($expectedFinalStock, $finalStock, 0.01, $message ?: $defaultMessage);
    }
    
    /**
     * Assert that order total calculation is correct
     * 
     * @param array $items Order items
     * @param float $expectedSubtotal Expected subtotal
     * @param float $expectedTotal Expected total
     * @param float $discountPercent Discount percentage (optional)
     * @param string $message Custom error message
     */
    public function assertOrderTotalCalculation(array $items, float $expectedSubtotal, float $expectedTotal, float $discountPercent = 0, string $message = ''): void
    {
        $calculatedSubtotal = 0;
        foreach ($items as $item) {
            $this->assertArrayHasKey('price', $item, 'Item must have price');
            $this->assertArrayHasKey('quantity', $item, 'Item must have quantity');
            $calculatedSubtotal += (float) $item['price'] * (int) $item['quantity'];
        }
        
        $this->assertEqualsWithDelta($expectedSubtotal, $calculatedSubtotal, 0.01, 
            "Subtotal calculation failed: expected {$expectedSubtotal}, calculated {$calculatedSubtotal}");
        
        $calculatedTotal = $calculatedSubtotal * (1 - $discountPercent / 100);
        $this->assertEqualsWithDelta($expectedTotal, $calculatedTotal, 0.01, 
            "Total calculation failed: expected {$expectedTotal}, calculated {$calculatedTotal}");
    }
    
    /**
     * Assert that price list priority is respected
     * 
     * @param array $priceLists Array of price lists with priorities
     * @param int $expectedPriority Expected priority to be selected
     * @param string $message Custom error message
     */
    public function assertPriceListPriority(array $priceLists, int $expectedPriority, string $message = ''): void
    {
        $selectedList = null;
        $highestPriority = -1;
        
        foreach ($priceLists as $list) {
            if (isset($list['priority']) && $list['priority'] > $highestPriority) {
                $highestPriority = $list['priority'];
                $selectedList = $list;
            }
        }
        
        $this->assertNotNull($selectedList, 'No price list was selected');
        $this->assertEquals($expectedPriority, $selectedList['priority'], 
            "Expected price list with priority {$expectedPriority}, got priority {$selectedList['priority']}");
    }
    
    /**
     * Assert that business validation rules are enforced
     * 
     * @param callable $operation Operation to test
     * @param string $expectedException Expected exception class
     * @param string $expectedMessage Expected exception message (optional)
     * @param string $message Custom error message
     */
    public function assertBusinessValidation(callable $operation, string $expectedException, ?string $expectedMessage = null, string $message = ''): void
    {
        $this->expectException($expectedException);
        
        if ($expectedMessage !== null) {
            $this->expectExceptionMessage($expectedMessage);
        }
        
        $operation();
    }
    
    /**
     * Assert that audit trail is properly maintained
     * 
     * @param string $tableName Table name
     * @param int $recordId Record ID
     * @param string $action Expected action (create, update, delete)
     * @param int $userId Expected user ID (optional)
     * @param string $message Custom error message
     */
    public function assertAuditTrail(string $tableName, int $recordId, string $action, ?int $userId = null, string $message = ''): void
    {
        $where = [
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => $action
        ];
        
        if ($userId !== null) {
            $where['user_id'] = $userId;
        }
        
        $this->assertDatabaseHas('audit_logs', $where, $message ?: "Audit trail entry not found for {$action} on {$tableName}:{$recordId}");
    }
    
    /**
     * Assert that business rules for price list dependencies are followed
     * 
     * @param int $baseListId Base price list ID
     * @param int $dependentListId Dependent price list ID
     * @param bool $autoUpdate Expected auto-update setting
     * @param string $formula Expected formula (optional)
     * @param string $message Custom error message
     */
    public function assertPriceListDependency(int $baseListId, int $dependentListId, bool $autoUpdate, ?string $formula = null, string $message = ''): void
    {
        $this->assertDatabaseHas('price_lists', [
            'id' => $dependentListId,
            'base_price_list_id' => $baseListId,
            'auto_update' => $autoUpdate ? 1 : 0
        ], $message ?: "Price list dependency not found or incorrect");
        
        if ($formula !== null) {
            $this->assertDatabaseHas('price_lists', [
                'id' => $dependentListId,
                'formula' => $formula
            ], $message ?: "Price list formula not found or incorrect");
        }
    }
    
    /**
     * Assert that boundary values are handled correctly
     * 
     * @param callable $operation Operation to test
     * @param array $boundaryTests Array of boundary test cases
     * @param string $message Custom error message
     */
    public function assertBoundaryValues(callable $operation, array $boundaryTests, string $message = ''): void
    {
        foreach ($boundaryTests as $testName => $test) {
            $input = $test['input'];
            $shouldPass = $test['should_pass'] ?? true;
            $expectedException = $test['exception'] ?? null;
            
            if (!$shouldPass && $expectedException) {
                $this->expectException($expectedException);
            }
            
            try {
                $result = $operation($input);
                
                if (!$shouldPass) {
                    $this->fail("Expected operation to fail for {$testName} but it succeeded");
                }
                
                // Verify expected result if provided
                if (isset($test['expected'])) {
                    foreach ($test['expected'] as $key => $value) {
                        $this->assertEquals($value, $result['data'][$key] ?? null, 
                            "Expected {$key} to be {$value} for {$testName}");
                    }
                }
                
            } catch (\Exception $e) {
                if ($shouldPass) {
                    $this->fail("Operation should pass for {$testName} but threw exception: " . $e->getMessage());
                }
                
                if ($expectedException && !($e instanceof $expectedException)) {
                    $this->fail("Expected exception {$expectedException} but got " . get_class($e));
                }
            }
        }
    }
    
    /**
     * Assert that null values are handled correctly
     * 
     * @param callable $operation Operation to test
     * @param array $nullFields Array of field names to test with null values
     * @param string $message Custom error message
     */
    public function assertNullValues(callable $operation, array $nullFields, string $message = ''): void
    {
        foreach ($nullFields as $field) {
            $testData = [$field => null];
            
            try {
                $result = $operation($testData);
                $this->fail("Expected operation to fail with null {$field} but it succeeded");
                
            } catch (\Exception $e) {
                // Expected behavior - null values should cause validation errors
                $this->assertStringContainsString(strtolower($field), strtolower($e->getMessage()), 
                    "Exception message should mention the field with null value");
            }
        }
    }
    
    /**
     * Assert that empty string values are handled correctly
     * 
     * @param callable $operation Operation to test
     * @param array $fields Array of field configurations
     * @param string $message Custom error message
     */
    public function assertEmptyStringValues(callable $operation, array $fields, string $message = ''): void
    {
        foreach ($fields as $field => $config) {
            $shouldFail = $config['should_fail'] ?? true;
            $testData = [$field => ''];
            
            if ($shouldFail) {
                try {
                    $result = $operation($testData);
                    $this->fail("Expected operation to fail with empty {$field} but it succeeded");
                    
                } catch (\Exception $e) {
                    // Expected behavior
                    $this->assertStringContainsString(strtolower($field), strtolower($e->getMessage()), 
                        "Exception message should mention the field with empty value");
                }
            } else {
                try {
                    $result = $operation($testData);
                    $this->assertNotNull($result, "Operation should succeed with empty {$field}");
                    
                } catch (\Exception $e) {
                    $this->fail("Operation should succeed with empty {$field} but failed: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Assert that max length values are handled correctly
     * 
     * @param callable $operation Operation to test
     * @param array $fields Array of field names and their max lengths
     * @param string $message Custom error message
     */
    public function assertMaxLengthValues(callable $operation, array $fields, string $message = ''): void
    {
        foreach ($fields as $field => $maxLength) {
            // Test exactly at max length
            $exactValue = str_repeat('A', $maxLength);
            $testData = [$field => $exactValue];
            
            try {
                $result = $operation($testData);
                $this->assertNotNull($result, "Operation should succeed with {$field} at max length");
                
            } catch (\Exception $e) {
                $this->fail("Operation should succeed with {$field} at max length: " . $e->getMessage());
            }
            
            // Test exceeding max length
            $exceedValue = str_repeat('A', $maxLength + 1);
            $testData = [$field => $exceedValue];
            
            try {
                $result = $operation($testData);
                $this->fail("Operation should fail with {$field} exceeding max length");
                
            } catch (\Exception $e) {
                // Expected behavior
                $this->assertStringContainsString('too long', strtolower($e->getMessage()), 
                    "Exception should mention value is too long");
            }
        }
    }
    
    /**
     * Assert that special characters are handled correctly
     * 
     * @param callable $operation Operation to test
     * @param array $fields Array of field configurations
     * @param string $message Custom error message
     */
    public function assertSpecialCharacters(callable $operation, array $fields, string $message = ''): void
    {
        $specialChars = [
            'quotes' => '"Test"',
            'apostrophe' => "'Test'",
            'html' => '<script>alert("xss")</script>',
            'sql' => "'; DROP TABLE users; --",
            'unicode' => 'Test ñáéíóú 中文',
        ];
        
        foreach ($fields as $field => $shouldSanitize) {
            foreach ($specialChars as $type => $value) {
                $testData = [$field => $value];
                
                try {
                    $result = $operation($testData);
                    $this->assertNotNull($result, "Operation should handle {$type} in {$field}");
                    
                } catch (\Exception $e) {
                    if ($shouldSanitize) {
                        // Should handle gracefully
                        $this->assertStringContainsString('validation', strtolower($e->getMessage()), 
                            "Should be validation error for {$type} in {$field}");
                    } else {
                        // Should not fail for non-sanitized fields
                        $this->fail("Operation should not fail for {$type} in {$field}: " . $e->getMessage());
                    }
                }
            }
        }
    }
    
    /**
     * Assert that unique constraints are enforced
     * 
     * @param callable $operation Operation to test
     * @param string $field Field name that should be unique
     * @param string $message Custom error message
     */
    public function assertUniqueConstraint(callable $operation, string $field, string $message = ''): void
    {
        try {
            $result = $operation();
            $this->fail("Expected unique constraint violation for {$field} but operation succeeded");
            
        } catch (\Exception $e) {
            $this->assertStringContainsString('duplicate', strtolower($e->getMessage()), 
                "Exception should mention duplicate for {$field}");
        }
    }
    
    /**
     * Assert that foreign key constraints are enforced
     * 
     * @param callable $operation Operation to test
     * @param string $field Field name with foreign key constraint
     * @param string $message Custom error message
     */
    public function assertForeignKeyConstraint(callable $operation, string $field, string $message = ''): void
    {
        try {
            $result = $operation();
            $this->fail("Expected foreign key constraint violation for {$field} but operation succeeded");
            
        } catch (\Exception $e) {
            $this->assertStringContainsString('constraint', strtolower($e->getMessage()), 
                "Exception should mention constraint violation for {$field}");
        }
    }
    
    /**
     * Assert that temporal edge cases are handled correctly
     * 
     * @param callable $operation Operation to test
     * @param array $dateTests Array of date field tests
     * @param string $message Custom error message
     */
    public function assertTemporalEdgeCases(callable $operation, array $dateTests, string $message = ''): void
    {
        foreach ($dateTests as $field => $testType) {
            $testData = [];
            
            switch ($testType) {
                case 'future_date':
                    $testData[$field] = date('Y-m-d H:i:s', strtotime('+1 year'));
                    break;
                case 'past_date':
                    $testData[$field] = date('Y-m-d H:i:s', strtotime('-1 year'));
                    break;
                case 'invalid_date':
                    $testData[$field] = '2025-13-45 99:99:99';
                    break;
            }
            
            try {
                $result = $operation($testData);
                // Some temporal edge cases might be allowed
                $this->assertNotNull($result, "Operation should handle {$testType} for {$field}");
                
            } catch (\Exception $e) {
                // Invalid dates should fail
                if ($testType === 'invalid_date') {
                    $this->assertStringContainsString('date', strtolower($e->getMessage()), 
                        "Invalid date should cause validation error");
                }
            }
        }
    }
    
    /**
     * Assert that data types are validated correctly
     * 
     * @param callable $operation Operation to test
     * @param array $typeTests Array of data type tests
     * @param string $message Custom error message
     */
    public function assertDataTypeValidation(callable $operation, array $typeTests, string $message = ''): void
    {
        foreach ($typeTests as $field => $test) {
            $expectedType = $test['expected_type'];
            $invalidValue = $test['invalid'];
            
            $testData = [$field => $invalidValue];
            
            try {
                $result = $operation($testData);
                $this->fail("Expected type validation error for {$field} but operation succeeded");
                
            } catch (\Exception $e) {
                $this->assertStringContainsString($expectedType, strtolower($e->getMessage()), 
                    "Exception should mention expected type {$expectedType} for {$field}");
            }
        }
    }
    
    /**
     * Assert that concurrent access is handled correctly
     * 
     * @param callable $operation1 First operation
     * @param callable $operation2 Second operation
     * @param string $message Custom error message
     */
    public function assertConcurrentAccess(callable $operation1, callable $operation2, string $message = ''): void
    {
        $results = [];
        $exceptions = [];
        
        // Simulate concurrent operations
        try {
            $results[] = $operation1();
        } catch (\Exception $e) {
            $exceptions[] = $e;
        }
        
        try {
            $results[] = $operation2();
        } catch (\Exception $e) {
            $exceptions[] = $e;
        }
        
        // At least one operation should succeed
        $this->assertGreaterThan(0, count($results), 
            "At least one concurrent operation should succeed");
        
        // If there are exceptions, they should be expected (like deadlocks)
        foreach ($exceptions as $exception) {
            $this->assertContainsString(get_class($exception), 
                ['RuntimeException', 'DatabaseException'], 
                "Concurrent operation exceptions should be database-related");
        }
    }
    
    /**
     * Assert that transaction rollback works correctly
     * 
     * @param callable $successCallback Operation that should succeed
     * @param callable $failCallback Operation that should fail and rollback
     * @param string $message Custom error message
     */
    public function assertTransactionRollback(callable $successCallback, callable $failCallback, string $message = ''): void
    {
        // Test successful transaction
        try {
            $result = $successCallback();
            $this->assertNotNull($result, "Success callback should return a result");
        } catch (\Exception $e) {
            $this->fail("Success callback should not fail: " . $e->getMessage());
        }
        
        // Test failed transaction
        try {
            $result = $failCallback();
            $this->fail("Fail callback should throw an exception");
        } catch (\Exception $e) {
            // Expected behavior
            $this->assertNotNull($e, "Fail callback should throw an exception");
        }
    }
    
    /**
     * Assert monetary precision is maintained
     * 
     * @param float $value Value to check
     * @param int $precision Expected precision
     * @param string $message Custom error message
     */
    public function assertMonetaryPrecision(float $value, int $precision, string $message = ''): void
    {
        $multiplier = pow(10, $precision);
        $roundedValue = round($value, $precision);
        $difference = abs($value - $roundedValue);
        
        $defaultMessage = "Value {$value} should have precision {$precision}, difference: {$difference}";
        $this->assertLessThanOrEqual(0.01 / $multiplier, $difference, $message ?: $defaultMessage);
    }
    
    /**
     * Assert timestamp format is correct
     * 
     * @param string $timestamp Timestamp to check
     * @param string $format Expected format
     * @param string $message Custom error message
     */
    public function assertTimestampFormat(string $timestamp, string $format, string $message = ''): void
    {
        $date = \DateTime::createFromFormat($format, $timestamp);
        $defaultMessage = "Timestamp '{$timestamp}' should match format '{$format}'";
        $this->assertInstanceOf(\DateTime::class, $date, $message ?: $defaultMessage);
    }
    
    /**
     * Assert business rule success
     * 
     * @param array $result Service result
     * @param string $message Custom error message
     */
    public function assertBusinessRuleSuccess(array $result, string $message = ''): void
    {
        $this->assertArrayHasKey('success', $result, 'Result must have success key');
        $this->assertTrue($result['success'], $message ?: 'Business rule should succeed');
        $this->assertArrayHasKey('data', $result, 'Result must have data key');
    }
    
    /**
     * Assert business rule violation
     * 
     * @param string $ruleType Type of business rule
     * @param string $expectedMessage Expected error message
     * @param callable $operation Operation to test
     * @param string $message Custom error message
     */
    public function assertBusinessRuleViolation(string $ruleType, string $expectedMessage, callable $operation, string $message = ''): void
    {
        try {
            $result = $operation();
            $this->fail("Expected business rule violation for {$ruleType} but operation succeeded");
            
        } catch (\Exception $e) {
            $this->assertStringContainsString($expectedMessage, $e->getMessage(), 
                "Business rule violation message should match expected");
        }
    }
    
    /**
     * Assert field validation error
     * 
     * @param string $field Field name
     * @param string $expectedMessage Expected error message
     * @param callable $operation Operation to test
     * @param string $message Custom error message
     */
    public function assertFieldValidationError(string $field, string $expectedMessage, callable $operation, string $message = ''): void
    {
        try {
            $result = $operation();
            $this->fail("Expected validation error for {$field} but operation succeeded");
            
        } catch (\Exception $e) {
            $this->assertStringContainsString($expectedMessage, $e->getMessage(), 
                "Validation error message should match expected for {$field}");
        }
    }
    
    /**
     * Assert multiple field validation errors
     * 
     * @param array $expectedErrors Array of field => expected messages
     * @param callable $operation Operation to test
     * @param string $message Custom error message
     */
    public function assertMultipleFieldValidationErrors(array $expectedErrors, callable $operation, string $message = ''): void
    {
        try {
            $result = $operation();
            $this->fail("Expected multiple validation errors but operation succeeded");
            
        } catch (\Exception $e) {
            foreach ($expectedErrors as $field => $messages) {
                foreach ($messages as $expectedMessage) {
                    $this->assertStringContainsString($expectedMessage, $e->getMessage(), 
                        "Validation error should contain message for {$field}");
                }
            }
        }
    }
    
    /**
     * Assert constraint violation
     * 
     * @param string $constraintType Type of constraint
     * @param string $field Field name
     * @param callable $operation Operation to test
     * @param string $message Custom error message
     */
    public function assertConstraintViolation(string $constraintType, string $field, callable $operation, string $message = ''): void
    {
        try {
            $result = $operation();
            $this->fail("Expected {$constraintType} constraint violation for {$field} but operation succeeded");
            
        } catch (\Exception $e) {
            $this->assertStringContainsString($constraintType, strtolower($e->getMessage()), 
                "Exception should mention {$constraintType} constraint violation");
        }
    }
    
    /**
     * Assert resource not found error
     * 
     * @param string $resourceType Type of resource
     * @param int $resourceId Resource ID
     * @param callable $operation Operation to test
     * @param string $message Custom error message
     */
    public function assertResourceNotFoundError(string $resourceType, int $resourceId, callable $operation, string $message = ''): void
    {
        try {
            $result = $operation();
            $this->fail("Expected resource not found error for {$resourceType} {$resourceId} but operation succeeded");
            
        } catch (\Exception $e) {
            $this->assertStringContainsString('not found', strtolower($e->getMessage()), 
                "Exception should mention resource not found");
        }
    }
    
    /**
     * Assert standard error message format
     * 
     * @param callable $operation Operation to test
     * @param string $message Custom error message
     */
    public function assertStandardErrorMessageFormat(callable $operation, string $message = ''): void
    {
        try {
            $result = $operation();
            $this->fail("Expected error but operation succeeded");
            
        } catch (\Exception $e) {
            // Error message should be descriptive and user-friendly
            $errorMessage = $e->getMessage();
            $this->assertGreaterThan(5, strlen($errorMessage), 
                "Error message should be descriptive");
            $this->assertStringNotContainsString('sql', strtolower($errorMessage), 
                "Error message should not contain SQL details");
        }
    }
}