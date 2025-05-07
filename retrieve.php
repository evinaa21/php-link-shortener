<?php
define('MAPPING_FILE', __DIR__ . '/data/mappings.txt');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the data directory exists with proper permissions
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    if (!mkdir($dataDir, 0777, true)) {
        error_log("Failed to create data directory: " . $dataDir);
        http_response_code(500);
        die(json_encode(["error" => "Internal server error"]));
    }
    chmod($dataDir, 0777);
}

// Ensure file exists with proper permissions
if (!file_exists(MAPPING_FILE)) {
    if (!touch(MAPPING_FILE)) {
        error_log("Failed to create mappings file: " . MAPPING_FILE);
        http_response_code(500);
        die(json_encode(["error" => "Internal server error"]));
    }
    chmod(MAPPING_FILE, 0666);
}

// Get the our_param from the URL
$our_param = $_GET['our_param'] ?? '';

$our_param = substr(preg_replace('/[^\da-f]/', '', $our_param), 0, 32);

if (empty($our_param)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing our_param"]);
    exit;
}

// Search for the matching our_param
$found = false;
$fp = fopen(MAPPING_FILE, 'r');
if ($fp === false) {
    http_response_code(500);
    echo json_encode(["error" => "Internal server error"]);
    exit;
}

while (($line = fgets($fp)) !== false) {
    $line = trim($line);
    // skip blank lines or comments
    if ($line === '' || strpos($line, '//') === 0) {
        continue;
    }
    // split on any whitespace
    $parts = preg_split('/\s+/', $line);
    if (count($parts) < 4) {
        // Invalid line format, skip it
        error_log("Invalid line format in mappings file: " . $line);
        continue;
    }
    list($stored_param, $keyword, $src, $creative) = $parts;
    if ($stored_param === $our_param) {
        echo json_encode([
            "keyword" => $keyword,
            "src" => $src,
            "creative" => $creative
        ]);
        $found = true;
        break; // Exit as soon as we find the match
    }
}
fclose($fp);

// If not found, return a 404 error
if (!$found) {
    error_log("our_param not found: " . $our_param);
    http_response_code(404);
    echo json_encode(["error" => "our_param not found"]);
}
?>