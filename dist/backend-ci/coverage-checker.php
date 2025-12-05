<?php
/**
 * Coverage Checker Script
 * 
 * Parses PHPUnit Clover XML coverage report and enforces minimum threshold.
 * 
 * Usage: php coverage-checker.php <clover.xml> <threshold>
 * Example: php coverage-checker.php coverage/clover.xml 70
 * 
 * Exit codes:
 * 0 - Coverage meets or exceeds threshold
 * 1 - Coverage below threshold or error
 */

if ($argc < 3) {
    echo "Usage: php coverage-checker.php <clover.xml> <threshold>\n";
    echo "Example: php coverage-checker.php coverage/clover.xml 70\n";
    exit(1);
}

$cloverFile = $argv[1];
$threshold = (float) $argv[2];

if (!file_exists($cloverFile)) {
    echo "❌ Error: Clover file not found: $cloverFile\n";
    exit(1);
}

$xml = simplexml_load_file($cloverFile);
if (!$xml) {
    echo "❌ Error: Failed to parse Clover XML file\n";
    exit(1);
}

$metrics = $xml->project->metrics;
$elements = (int) $metrics['elements'];
$coveredElements = (int) $metrics['coveredelements'];

if ($elements === 0) {
    echo "⚠️  Warning: No elements found in coverage report\n";
    exit(1);
}

$coverage = ($coveredElements / $elements) * 100;

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 Code Coverage Report\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";
echo "Total Elements:   $elements\n";
echo "Covered Elements: $coveredElements\n";
echo "Coverage:         " . number_format($coverage, 2) . "%\n";
echo "Threshold:        " . number_format($threshold, 2) . "%\n";
echo "\n";

if ($coverage < $threshold) {
    echo "❌ FAILED: Coverage is below threshold!\n";
    echo "\n";
    echo "Required: " . number_format($threshold, 2) . "%\n";
    echo "Actual:   " . number_format($coverage, 2) . "%\n";
    echo "Gap:      " . number_format($threshold - $coverage, 2) . "%\n";
    echo "\n";
    echo "To improve coverage, run:\n";
    echo "  docker exec meomeo2-api-1 vendor/bin/phpunit --coverage-html coverage/\n";
    echo "  open coverage/index.html\n";
    echo "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    exit(1);
}

echo "✅ PASSED: Coverage meets threshold!\n";
echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
exit(0);
