<?php
/**
 * -------------------------------------------------------------
 * retrieve.php — Retrieve Original Parameters by our_param
 * -------------------------------------------------------------
 * This script receives an 'our_param' code (from the short link)
 * and looks up the original keyword, src, and creative values.
 * It returns the result as JSON.
 * -------------------------------------------------------------
 * Steps:
 * 1. Makes sure the data directory and mappings file exist.
 * 2. Gets the 'our_param' from the URL and sanitizes it.
 * 3. Searches the mappings file for a matching code.
 * 4. If found, returns the original parameters as JSON.
 * 5. If not found, returns a 404 error as JSON.
 */

// Define the path to the mappings file
define('MAPPING_FILE', __DIR__ . '/data/mappings.txt');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the data directory exists with proper permissions
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    // Create the data directory if it doesn't exist
    if (!mkdir($dataDir, 0777, true)) {
        error_log("Failed to create data directory: " . $dataDir);
        http_response_code(500);
        die(json_encode(["error" => "Internal server error"]));
    }
    chmod($dataDir, 0777);
}

// Ensure file exists with proper permissions
if (!file_exists(MAPPING_FILE)) {
    // Create the mappings file if it doesn't exist
    if (!touch(MAPPING_FILE)) {
        error_log("Failed to create mappings file: " . MAPPING_FILE);
        http_response_code(500);
        die(json_encode(["error" => "Internal server error"]));
    }
    chmod(MAPPING_FILE, 0666);
}

// Get the our_param from the URL (or empty string if missing)
$our_param = $_GET['our_param'] ?? '';

// Sanitize: only allow lowercase hex digits, max 32 chars
$our_param = substr(preg_replace('/[^\da-f]/', '', $our_param), 0, 32);

// If our_param is missing, return an error
if (empty($our_param)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing our_param"]);
    exit;
}

// Search for the matching our_param in the mappings file
$found = false;
$fp = fopen(MAPPING_FILE, 'r');
if ($fp === false) {
    http_response_code(500);
    echo json_encode(["error" => "Internal server error"]);
    exit;
}

while (($line = fgets($fp)) !== false) {
    $line = trim($line);
    // Skip blank lines or comment lines
    if ($line === '' || strpos($line, '//') === 0) {
        continue;
    }
    // Split the line into parts (by whitespace)
    $parts = preg_split('/\s+/', $line);
    if (count($parts) < 4) {
        // Invalid line format, skip it
        error_log("Invalid line format in mappings file: " . $line);
        continue;
    }
    list($stored_param, $keyword, $src, $creative) = $parts;
    // If the code matches, return the original parameters as JSON
    if ($stored_param === $our_param) {
        echo json_encode([
            "keyword" => $keyword,
            "src" => $src,
            "creative" => $creative
        ]);
        $found = true;
        break; // Stop searching after finding the match
    }
}
fclose($fp);

// If not found, return a 404 error as JSON
if (!$found) {
    error_log("our_param not found: " . $our_param);
    http_response_code(404);
    echo json_encode(["error" => "our_param not found"]);
}
exit;