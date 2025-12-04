<?php

namespace Tests\Support\Assertions;

/**
 * Error message assertion methods for validation error testing
 * 
 * @agent-assertions: Error message validation
 * @agent-pattern: Comprehensive error message testing
 * @agent-reusable: HIGH
 */
trait ErrorMessageAssertions
{
    /**
     * Assert that an operation throws an exception with specific message
     * 
     * @param callable $operation Operation to test
     * @param string $expectedException Expected exception class
     * @param string $expectedMessage Expected error message (or part of it)
     * @param string $message Custom error message
     */
    public function assertExceptionWithMessage(callable $operation, string $expectedException, string $expectedMessage, string $message = ''): void
    {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);
        
        try {
            $operation();
        } catch (\Exception $e) {
            if (!$e instanceof $expectedException) {
                $this->fail("Expected exception {$expectedException} but got " . get_class($e));
            }
            
            if (strpos($e->getMessage(), $expectedMessage) === false) {
                $this->fail("Expected message '{$expectedMessage}' but got '{$e->getMessage()}'");
            }
            
            throw $e;
        }
    }
    
    /**
     * Assert that a service result contains an error message
     * 
     * @param array $result Service result array
     * @param string $expectedMessage Expected error message (or part of it)
     * @param string $message Custom error message
     */
    public function assertServiceErrorMessage(array $result, string $expectedMessage, string $message = ''): void
    {
        $this->assertArrayHasKey('success', $result, 'Service result must have success key');
        $this->assertFalse($result['success'], 'Service result should indicate failure');
        $this->assertArrayHasKey('message', $result, 'Failed service result must have message key');
        
        $actualMessage = $result['message'];
        $defaultMessage = "Expected error message to contain '{$expectedMessage}', got '{$actualMessage}'";
        $this->assertStringContainsString($expectedMessage, $actualMessage, $message ?: $defaultMessage);
    }
    
    /**
     * Assert that validation errors contain specific field errors
     * 
     * @param array $result Service result array
     * @param array $expectedFieldErrors Expected field => error message pairs
     * @param string $message Custom error message
     */
    public function assertValidationErrors(array $result, array $expectedFieldErrors, string $message = ''): void
    {
        $this->assertArrayHasKey('success', $result, 'Service result must have success key');
        $this->assertFalse($result['success'], 'Service result should indicate failure');
        $this->assertArrayHasKey('errors', $result, 'Failed service result must have errors key');
        
        $errors = $result['errors'];
        
        foreach ($expectedFieldErrors as $field => $expectedError) {
            $this->assertArrayHasKey($field, $errors, "Validation errors should contain field: {$field}");
            
            $actualError = is_array($errors[$field]) ? implode(' ', $errors[$field]) : $errors[$field];
            $defaultMessage = "Field {$field} expected error '{$expectedError}', got '{$actualError}'";
            $this->assertStringContainsString($expectedError, $actualError, $message ?: $defaultMessage);
        }
    }
    
    /**
     * Assert that error messages are user-friendly
     * 
     * @param array $result Service result array
     * @param string $message Custom error message
     */
    public function assertUserFriendlyErrorMessage(array $result, string $message = ''): void
    {
        $this->assertArrayHasKey('success', $result, 'Service result must have success key');
        $this->assertFalse($result['success'], 'Service result should indicate failure');
        $this->assertArrayHasKey('message', $result, 'Failed service result must have message key');
        
        $errorMessage = $result['message'];
        
        // Check for technical jargon that shouldn't be exposed to users
        $technicalTerms = [
            'SQL', 'database error', 'exception', 'stack trace', 'fatal error',
            'mysqli', 'pdo', 'query failed', 'syntax error', 'undefined index'
        ];
        
        foreach ($technicalTerms as $term) {
            $this->assertStringNotContainsStringIgnoringCase($term, $errorMessage, 
                "Error message should not contain technical term: {$term}");
        }
        
        // Check that message is meaningful (not just "Error occurred")
        $this->assertGreaterThan(10, strlen($errorMessage), 
            "Error message should be meaningful and descriptive");
    }
    
    /**
     * Assert that error messages are properly localized
     * 
     * @param array $result Service result array
     * @param string $expectedLanguage Expected language code (e.g., 'en', 'vi')
     * @param string $message Custom error message
     */
    public function assertLocalizedErrorMessage(array $result, string $expectedLanguage, string $message = ''): void
    {
        $this->assertArrayHasKey('success', $result, 'Service result must have success key');
        $this->assertFalse($result['success'], 'Service result should indicate failure');
        $this->assertArrayHasKey('message', $result, 'Failed service result must have message key');
        
        $errorMessage = $result['message'];
        
        // This is a simplified check - in real implementation, you might check
        // for specific language patterns or use translation services
        if ($expectedLanguage === 'vi') {
            $vietnamesePatterns = ['không', 'lỗi', 'bắt buộc', 'hợp lệ', 'tồn tại'];
            $foundVietnamese = false;
            
            foreach ($vietnamesePatterns as $pattern) {
                if (strpos(strtolower($errorMessage), $pattern) !== false) {
                    $foundVietnamese = true;
                    break;
                }
            }
            
            $this->assertTrue($foundVietnamese, 
                "Error message should be in Vietnamese: {$errorMessage}");
        } elseif ($expectedLanguage === 'en') {
            $englishPatterns = ['required', 'invalid', 'exists', 'not found', 'must be'];
            $foundEnglish = false;
            
            foreach ($englishPatterns as $pattern) {
                if (strpos(strtolower($errorMessage), $pattern) !== false) {
                    $foundEnglish = true;
                    break;
                }
            }
            
            $this->assertTrue($foundEnglish, 
                "Error message should be in English: {$errorMessage}");
        }
    }
    
    /**
     * Assert that error messages provide actionable information
     * 
     * @param array $result Service result array
     * @param string $message Custom error message
     */
    public function assertActionableErrorMessage(array $result, string $message = ''): void
    {
        $this->assertArrayHasKey('success', $result, 'Service result must have success key');
        $this->assertFalse($result['success'], 'Service result should indicate failure');
        $this->assertArrayHasKey('message', $result, 'Failed service result must have message key');
        
        $errorMessage = $result['message'];
        
        // Check for actionable patterns
        $actionablePatterns = [
            'must be', 'required', 'please', 'should', 'need to', 'provide',
            'enter', 'select', 'choose', 'specify', 'ensure'
        ];
        
        $foundActionable = false;
        foreach ($actionablePatterns as $pattern) {
            if (strpos(strtolower($errorMessage), $pattern) !== false) {
                $foundActionable = true;
                break;
            }
        }
        
        $this->assertTrue($foundActionable, 
            "Error message should provide actionable guidance: {$errorMessage}");
    }
    
    /**
     * Assert that error messages are consistent across similar operations
     * 
     * @param array $results Array of service results from similar operations
     * @param string $expectedConsistentMessage Expected consistent message pattern
     * @param string $message Custom error message
     */
    public function assertConsistentErrorMessages(array $results, string $expectedConsistentMessage, string $message = ''): void
    {
        foreach ($results as $index => $result) {
            $this->assertArrayHasKey('success', $result, "Result {$index} must have success key");
            $this->assertFalse($result['success'], "Result {$index} should indicate failure");
            $this->assertArrayHasKey('message', $result, "Result {$index} must have message key");
            
            $actualMessage = $result['message'];
            $defaultMessage = "Result {$index} expected consistent message pattern, got: {$actualMessage}";
            $this->assertStringContainsString($expectedConsistentMessage, $actualMessage, $message ?: $defaultMessage);
        }
    }
    
    /**
     * Assert that error messages include field context
     * 
     * @param array $result Service result array
     * @param array $expectedFields Expected fields mentioned in error
     * @param string $message Custom error message
     */
    public function assertErrorFieldContext(array $result, array $expectedFields, string $message = ''): void
    {
        $this->assertArrayHasKey('success', $result, 'Service result must have success key');
        $this->assertFalse($result['success'], 'Service result should indicate failure');
        $this->assertArrayHasKey('message', $result, 'Failed service result must have message key');
        
        $errorMessage = strtolower($result['message']);
        
        foreach ($expectedFields as $field) {
            $defaultMessage = "Error message should mention field '{$field}': {$result['message']}";
            $this->assertStringContainsString(strtolower($field), $errorMessage, $message ?: $defaultMessage);
        }
    }
    
    /**
     * Assert that error messages respect data privacy
     * 
     * @param array $result Service result array
     * @param array $sensitiveFields Sensitive fields that should not be exposed
     * @param string $message Custom error message
     */
    public function assertErrorDataPrivacy(array $result, array $sensitiveFields, string $message = ''): void
    {
        $this->assertArrayHasKey('success', $result, 'Service result must have success key');
        $this->assertFalse($result['success'], 'Service result should indicate failure');
        $this->assertArrayHasKey('message', $result, 'Failed service result must have message key');
        
        $errorMessage = strtolower($result['message']);
        
        foreach ($sensitiveFields as $field) {
            $this->assertStringNotContainsString($field, $errorMessage, 
                "Error message should not expose sensitive field: {$field}");
        }
        
        // Check for common sensitive data patterns
        $sensitivePatterns = [
            '/\d{4}[-\s]?\d{4}[-\s]?\d{4}[-\s]?\d{4}/', // Credit card
            '/\b\d{3}-\d{2}-\d{4}\b/', // SSN
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', // Email
            '/password/i',
            '/token/i',
            '/secret/i',
            '/key/i'
        ];
        
        foreach ($sensitivePatterns as $pattern) {
            $this->assertDoesNotMatchRegularExpression($pattern, $errorMessage, 
                "Error message should not contain sensitive data pattern");
        }
    }
    
    /**
     * Assert that error messages are properly formatted
     * 
     * @param array $result Service result array
     * @param string $message Custom error message
     */
    public function assertErrorFormatting(array $result, string $message = ''): void
    {
        $this->assertArrayHasKey('success', $result, 'Service result must have success key');
        $this->assertFalse($result['success'], 'Service result should indicate failure');
        $this->assertArrayHasKey('message', $result, 'Failed service result must have message key');
        
        $errorMessage = $result['message'];
        
        // Check that message starts with capital letter
        $this->assertMatchesRegularExpression('/^[A-Z]/', $errorMessage, 
            "Error message should start with capital letter");
        
        // Check that message ends with appropriate punctuation
        $this->assertMatchesRegularExpression('/[.!?]$/', $errorMessage, 
            "Error message should end with punctuation");
        
        // Check that message doesn't have excessive whitespace
        $this->assertStringNotContainsString('  ', $errorMessage, 
            "Error message should not have double spaces");
        
        // Check that message doesn't have leading/trailing whitespace
        $this->assertEquals(trim($errorMessage), $errorMessage, 
            "Error message should not have leading/trailing whitespace");
    }
    
    /**
     * Assert that error messages include error codes for technical reference
     * 
     * @param array $result Service result array
     * @param string $expectedErrorCodePattern Expected error code pattern
     * @param string $message Custom error message
     */
    public function assertErrorCodeIncluded(array $result, string $expectedErrorCodePattern = '', string $message = ''): void
    {
        $this->assertArrayHasKey('success', $result, 'Service result must have success key');
        $this->assertFalse($result['success'], 'Service result should indicate failure');
        
        // Check for error_code in result
        if (isset($result['error_code'])) {
            $errorCode = $result['error_code'];
            
            if (!empty($expectedErrorCodePattern)) {
                $this->assertMatchesRegularExpression($expectedErrorCodePattern, $errorCode, 
                    "Error code should match expected pattern");
            }
            
            // Check that error code follows standard format
            $this->assertMatchesRegularExpression('/^[A-Z_]+[0-9]*$/', $errorCode, 
                "Error code should follow format: MODULE_ERROR123");
        } else {
            // If no error_code field, check if message includes reference
            $this->assertArrayHasKey('message', $result, 'Failed service result must have message key');
            $errorMessage = $result['message'];
            
            // Look for reference patterns like (ERR001), [REF123], etc.
            $referencePattern = '/\(([A-Z_]+[0-9]*)\)|\[([A-Z_]+[0-9]*)\]/';
            $this->assertMatchesRegularExpression($referencePattern, $errorMessage, 
                "Error message should include error reference code");
        }
    }
    
    
}