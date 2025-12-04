<?php

namespace Tests\Support\Assertions;

/**
 * Edge case assertion methods for boundary value and special condition testing
 * 
 * @agent-assertions: Edge case and boundary value validation
 * @agent-pattern: Comprehensive edge case testing
 * @agent-reusable: HIGH
 */
trait EdgeCaseAssertions
{
    /**
     * Assert that null values are properly handled
     * 
     * @param callable $operation Operation to test with null values
     * @param array $nullFields Array of field names that should be tested with null
     * @param string $expectedException Expected exception class (optional)
     * @param string $message Custom error message
     */
    public function assertNullValueHandling(callable $operation, array $nullFields, ?string $expectedException = null, string $message = ''): void
    {
        foreach ($nullFields as $field) {
            $testData = [$field => null];
            
            if ($expectedException) {
                $this->expectException($expectedException);
                $this->expectExceptionMessage("{$field} is required");
            }
            
            try {
                $result = $operation($testData);
                
                if ($expectedException) {
                    $this->fail("Expected exception {$expectedException} for null {$field} but operation succeeded");
                }
                
                // If no exception expected, verify the result handles null appropriately
                $this->assertNotNull($result, "Operation should return a result even with null {$field}");
                
            } catch (\Exception $e) {
                if (!$expectedException) {
                    $this->fail("Unexpected exception for null {$field}: " . $e->getMessage());
                }
                
                // Verify the exception message mentions the field
                $this->assertStringContainsString($field, $e->getMessage(), 
                    "Exception message should mention the field with null value");
            }
        }
    }
    
    /**
     * Assert that empty values are properly handled
     * 
     * @param callable $operation Operation to test with empty values
     * @param array $emptyFields Array of field names that should be tested with empty values
     * @param string $expectedException Expected exception class (optional)
     * @param string $message Custom error message
     */
    public function assertEmptyValueHandling(callable $operation, array $emptyFields, ?string $expectedException = null, string $message = ''): void
    {
        $emptyValues = ['', [], 0, '0'];
        
        foreach ($emptyFields as $field) {
            foreach ($emptyValues as $emptyValue) {
                $testData = [$field => $emptyValue];
                
                if ($expectedException) {
                    $this->expectException($expectedException);
                }
                
                try {
                    $result = $operation($testData);
                    
                    if ($expectedException) {
                        $this->fail("Expected exception {$expectedException} for empty {$field} but operation succeeded");
                    }
                    
                    // If no exception expected, verify the result handles empty appropriately
                    $this->assertNotNull($result, "Operation should return a result even with empty {$field}");
                    
                } catch (\Exception $e) {
                    if (!$expectedException) {
                        $this->fail("Unexpected exception for empty {$field}: " . $e->getMessage());
                    }
                }
            }
        }
    }
    
    /**
     * Assert that boundary values are properly handled
     * 
     * @param callable $operation Operation to test with boundary values
     * @param array $boundaryTests Array of boundary test cases
     * @param string $message Custom error message
     */
    public function assertBoundaryValueHandling(callable $operation, array $boundaryTests, string $message = ''): void
    {
        foreach ($boundaryTests as $test) {
            $field = $test['field'];
            $value = $test['value'];
            $shouldPass = $test['should_pass'] ?? true;
            $expectedException = $test['expected_exception'] ?? null;
            
            $testData = [$field => $value];
            
            if (!$shouldPass && $expectedException) {
                $this->expectException($expectedException);
            }
            
            try {
                $result = $operation($testData);
                
                if (!$shouldPass) {
                    $this->fail("Expected operation to fail with {$field} = {$value} but it succeeded");
                }
                
                // Verify the result is valid for boundary values
                $this->assertNotNull($result, "Operation should return a valid result for boundary value {$field} = {$value}");
                
            } catch (\Exception $e) {
                if ($shouldPass) {
                    $this->fail("Operation should pass with {$field} = {$value} but threw exception: " . $e->getMessage());
                }
                
                if ($expectedException && !($e instanceof $expectedException)) {
                    $this->fail("Expected exception {$expectedException} but got " . get_class($e));
                }
            }
        }
    }
    
    /**
     * Assert that special characters are properly handled
     * 
     * @param callable $operation Operation to test with special characters
     * @param array $specialCharFields Array of field names that should be tested with special characters
     * @param string $message Custom error message
     */
    public function assertSpecialCharacterHandling(callable $operation, array $specialCharFields, string $message = ''): void
    {
        $specialChars = [
            'normal' => 'Test String',
            'quotes' => 'Test "String" with quotes',
            'apostrophes' => "Test 'String' with apostrophes",
            'unicode' => 'Test ñáéíóú 中文',
            'html' => '<script>alert("xss")</script>',
            'sql' => "'; DROP TABLE users; --",
            'emoji' => 'Test 🚀🎉🔥',
            'newline' => "Test\nString\nWith\nNewlines",
            'tab' => "Test\tString\tWith\tTabs",
            'backslash' => 'Test\\String\\With\\Backslashes',
        ];
        
        foreach ($specialCharFields as $field) {
            foreach ($specialChars as $type => $value) {
                $testData = [$field => $value];
                
                try {
                    $result = $operation($testData);
                    
                    // Verify the result handles special characters appropriately
                    $this->assertNotNull($result, "Operation should return a result for {$field} with {$type} characters");
                    
                    // If the operation returns data, verify special characters are preserved or escaped appropriately
                    if (is_array($result) && isset($result['data']) && isset($result['data'][$field])) {
                        $actualValue = $result['data'][$field];
                        
                        // For HTML/SQL injection, verify it's escaped
                        if ($type === 'html' || $type === 'sql') {
                            $this->assertStringNotContainsString('<script>', $actualValue, 
                                "HTML should be escaped in {$field}");
                            $this->assertStringNotContainsString('DROP TABLE', $actualValue, 
                                "SQL injection should be prevented in {$field}");
                        }
                    }
                    
                } catch (\Exception $e) {
                    // Some special characters might cause validation errors, which is acceptable
                    $this->assertStringContainsString('validation', strtolower($e->getMessage()), 
                        "Exception for special characters should be validation-related: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Assert that large values are properly handled
     * 
     * @param callable $operation Operation to test with large values
     * @param array $largeValueTests Array of large value test cases
     * @param string $message Custom error message
     */
    public function assertLargeValueHandling(callable $operation, array $largeValueTests, string $message = ''): void
    {
        foreach ($largeValueTests as $test) {
            $field = $test['field'];
            $value = $test['value'];
            $shouldPass = $test['should_pass'] ?? true;
            $expectedException = $test['expected_exception'] ?? null;
            
            $testData = [$field => $value];
            
            if (!$shouldPass && $expectedException) {
                $this->expectException($expectedException);
            }
            
            try {
                $result = $operation($testData);
                
                if (!$shouldPass) {
                    $this->fail("Expected operation to fail with large {$field} but it succeeded");
                }
                
                // Verify the result handles large values appropriately
                $this->assertNotNull($result, "Operation should return a result for large {$field}");
                
            } catch (\Exception $e) {
                if ($shouldPass) {
                    $this->fail("Operation should pass with large {$field} but threw exception: " . $e->getMessage());
                }
                
                if ($expectedException && !($e instanceof $expectedException)) {
                    $this->fail("Expected exception {$expectedException} but got " . get_class($e));
                }
            }
        }
    }
    
    /**
     * Assert that concurrent operations are properly handled
     * 
     * @param callable $operation Operation to test concurrently
     * @param int $concurrency Number of concurrent operations
     * @param string $message Custom error message
     */
    public function assertConcurrentOperationHandling(callable $operation, int $concurrency = 5, string $message = ''): void
    {
        $results = [];
        $exceptions = [];
        
        // Simulate concurrent operations (simplified for testing)
        for ($i = 0; $i < $concurrency; $i++) {
            try {
                $result = $operation(['iteration' => $i]);
                $results[] = $result;
            } catch (\Exception $e) {
                $exceptions[] = $e;
            }
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
     * Assert that zero and negative values are properly handled
     * 
     * @param callable $operation Operation to test with zero/negative values
     * @param array $numericFields Array of numeric field names to test
     * @param string $message Custom error message
     */
    public function assertZeroNegativeValueHandling(callable $operation, array $numericFields, string $message = ''): void
    {
        $testValues = [0, -1, -0.01, -999.99];
        
        foreach ($numericFields as $field) {
            foreach ($testValues as $value) {
                $testData = [$field => $value];
                
                try {
                    $result = $operation($testData);
                    
                    // For zero values, operation might succeed
                    if ($value === 0) {
                        $this->assertNotNull($result, "Operation should handle zero {$field}");
                    } else {
                        // For negative values, operation should typically fail
                        $this->fail("Operation should fail with negative {$field} = {$value}");
                    }
                    
                } catch (\Exception $e) {
                    // Negative values should cause validation errors
                    if ($value < 0) {
                        $this->assertStringContainsString('must be positive', strtolower($e->getMessage()), 
                            "Negative {$field} should cause validation error");
                    }
                }
            }
        }
    }
    
    /**
     * Assert that maximum length constraints are properly enforced
     * 
     * @param callable $operation Operation to test with maximum length values
     * @param array $maxLengthTests Array of maximum length test cases
     * @param string $message Custom error message
     */
    public function assertMaximumLengthEnforcement(callable $operation, array $maxLengthTests, string $message = ''): void
    {
        foreach ($maxLengthTests as $test) {
            $field = $test['field'];
            $maxLength = $test['max_length'];
            
            // Test exactly at maximum length
            $exactValue = str_repeat('A', $maxLength);
            $testData = [$field => $exactValue];
            
            try {
                $result = $operation($testData);
                $this->assertNotNull($result, "Operation should succeed with {$field} at maximum length");
            } catch (\Exception $e) {
                $this->fail("Operation should succeed with {$field} at maximum length: " . $e->getMessage());
            }
            
            // Test exceeding maximum length
            $exceedValue = str_repeat('A', $maxLength + 1);
            $testData = [$field => $exceedValue];
            
            try {
                $result = $operation($testData);
                $this->fail("Operation should fail with {$field} exceeding maximum length");
            } catch (\Exception $e) {
                $this->assertStringContainsString('too long', strtolower($e->getMessage()), 
                    "Exceeding maximum length should cause validation error");
            }
        }
    }
    
    /**
     * Assert that invalid data types are properly handled
     * 
     * @param callable $operation Operation to test with invalid data types
     * @param array $typeTests Array of data type test cases
     * @param string $message Custom error message
     */
    public function assertInvalidDataTypeHandling(callable $operation, array $typeTests, string $message = ''): void
    {
        foreach ($typeTests as $test) {
            $field = $test['field'];
            $invalidValue = $test['invalid_value'];
            $expectedType = $test['expected_type'];
            
            $testData = [$field => $invalidValue];
            
            try {
                $result = $operation($testData);
                $this->fail("Operation should fail with invalid {$field} type: " . gettype($invalidValue));
            } catch (\Exception $e) {
                $this->assertStringContainsString($expectedType, strtolower($e->getMessage()), 
                    "Invalid type should cause validation error mentioning expected type");
            }
        }
    }
    
    
    /**
     * Assert that string length is within expected bounds
     *
     * @param mixed $value Value to check
     * @param int $min Minimum expected length
     * @param int $max Maximum expected length
     * @param string $message Custom error message
     */
    public function assertStringLength($value, int $min, int $max, string $message = ''): void
    {
        $this->assertIsString($value, "Value must be a string for length validation");
        
        $length = strlen($value);
        
        $defaultMessage = "String length {$length} should be between {$min} and {$max}";
        $this->assertGreaterThanOrEqual($min, $length, $message ?: $defaultMessage);
        $this->assertLessThanOrEqual($max, $length, $message ?: $defaultMessage);
    }
    
    /**
     * Assert that two strings are equal with proper type checking
     *
     * @param mixed $expected Expected string value
     * @param mixed $actual Actual string value
     * @param string $message Custom error message
     */
    public function assertStringEquals($expected, $actual, string $message = ''): void
    {
        $this->assertIsString($expected, "Expected value must be a string");
        $this->assertIsString($actual, "Actual value must be a string");
        
        $defaultMessage = "Expected string '{$expected}', got '{$actual}'";
        $this->assertEquals($expected, $actual, $message ?: $defaultMessage);
        
        // Additional check for string encoding consistency
        $expectedEncoding = mb_detect_encoding($expected);
        $actualEncoding = mb_detect_encoding($actual);
        
        $this->assertEquals($expectedEncoding, $actualEncoding,
            "String encodings should match: expected {$expectedEncoding}, got {$actualEncoding}");
    }
    
}