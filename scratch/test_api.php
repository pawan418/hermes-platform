<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'get_data';
ob_start();
include __DIR__ . '/../api.php';
$output = ob_get_clean();
$json = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "API TEST PASSED!\n";
    echo "Keys returned: " . implode(', ', array_keys($json)) . "\n";
    echo "Enterprise services count: " . count($json['enterprise']['services'] ?? []) . "\n";
    echo "Academy services count: " . count($json['academy']['services'] ?? []) . "\n";
    echo "AI services count: " . count($json['ai']['services'] ?? []) . "\n";
} else {
    echo "API TEST FAILED! Raw output:\n";
    echo $output . "\n";
}
