<?php
// -------------------------------------------------------------
// Link Shortener Redirect Script
// -------------------------------------------------------------
// This script receives keyword, src, and creative parameters,
// generates a unique code (our_param), and stores the mapping.
// If "refresh" is set, it creates a new code for the same values.
// It also keeps a history of refreshed codes.
// -------------------------------------------------------------

// Define file paths for storing mappings and history
define('MAPPINGS_FILE', __DIR__ . '/data/mappings.txt');
define('HISTORY_FILE', __DIR__ . '/data/history.txt');

// Enable error reporting for debugging (for development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the data directory exists with proper permissions
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    // Create the data directory if it doesn't exist
    if (!mkdir($dataDir, 0777, true)) {
        error_log("Failed to create data directory: " . $dataDir);
        http_response_code(500);
        die("Failed to create data directory");
    }
    chmod($dataDir, 0777);
}

// Ensure mapping and history files exist with proper permissions
if (!file_exists(MAPPINGS_FILE)) {
    // Create the mappings file if it doesn't exist
    if (!touch(MAPPINGS_FILE)) {
        error_log("Failed to create mappings file: " . MAPPINGS_FILE);
        http_response_code(500);
        die("Failed to create mappings file");
    }
    chmod(MAPPINGS_FILE, 0666);
}

if (!file_exists(HISTORY_FILE)) {
    // Create the history file if it doesn't exist
    if (!touch(HISTORY_FILE)) {
        error_log("Failed to create history file: " . HISTORY_FILE);
        http_response_code(500);
        die("Failed to create history file");
    }
    chmod(HISTORY_FILE, 0666);
}

// Get URL parameters or set to 'unknown' if missing
$keyword = $_GET['keyword'] ?? 'unknown';
$src = $_GET['src'] ?? 'unknown';
$creative = $_GET['creative'] ?? 'unknown';
// "refresh" checkbox: if set, generate a new code even for same values
$refresh = isset($_GET['refresh']) ? true : false;

// If all parameters are missing, return an error
if ($keyword === 'unknown' && $src === 'unknown' && $creative === 'unknown') {
    http_response_code(400); // Bad Request
    echo "Error: At least one parameter must be given";
    exit;
}

// Sanitize input: allow only letters, numbers, underscores, dashes, max 64 chars
$keyword = substr(preg_replace('/[^\w\-]/', '', $keyword), 0, 64);
$src = substr(preg_replace('/[^\w\-]/', '', $src), 0, 64);
$creative = substr(preg_replace('/[^\w\-]/', '', $creative), 0, 64);

// Generate our_param: unique code for this combination
if ($refresh) {
    // If refresh is checked, generate a new unique code
    $our_param = hash('md5', uniqid($keyword . $src . $creative, true));
} else {
    // Otherwise, generate the same code for the same values
    $our_param = hash('md5', $keyword . $src . $creative);
}

// Open the mappings file for reading and writing (with locking)
$fp = fopen(MAPPINGS_FILE, 'a+');
if (!$fp) {
    error_log("Failed to open mappings file for writing: " . MAPPINGS_FILE);
    http_response_code(500);
    die("Failed to open mappings file");
}

flock($fp, LOCK_EX); // Lock the file for writing

// Read existing mappings into an array
$existing_mapping = [];
$found = false;
while (($line = fgets($fp)) !== false) {
    $line = trim($line);
    if (empty($line))
        continue;

    // Split the line into its parts
    list($existing_param, $existing_keyword, $existing_src, $existing_creative) = explode(" ", $line);
    // Check if this mapping already exists
    if (
        $existing_keyword === $keyword &&
        $existing_src === $src &&
        $existing_creative === $creative
    ) {
        // If found, reuse the existing code (unless refresh is set)
        $our_param = $existing_param;
        $found = true;
        break;
    }
    $existing_mapping[] = $line;
}

// Store the new mapping if it's a refresh or not found
if ($refresh || !$found) {
    // Remove any existing mapping for this combination
    $existing_mapping = array_filter($existing_mapping, function ($line) use ($keyword, $src, $creative) {
        list($_, $k, $s, $c) = explode(" ", $line);
        return !($k === $keyword && $s === $src && $c === $creative);
    });

    // Add the new mapping to the array
    $existing_mapping[] = "$our_param $keyword $src $creative";

    // Rewrite the mapping file with the updated mappings
    ftruncate($fp, 0);
    rewind($fp);
    foreach ($existing_mapping as $line) {
        fwrite($fp, $line . "\n");
    }
}

// Remove the lock and close the file
flock($fp, LOCK_UN);
fclose($fp);

// If refresh was used, log the old and new codes in history.txt
if ($refresh) {
    $historyFile = __DIR__ . '/data/history.txt';
    $old_param = null;
    foreach ($existing_mapping as $line) {
        list($existing_param, $existing_keyword, $existing_src, $existing_creative) = explode(" ", trim($line));
        if (
            $existing_keyword === $keyword &&
            $existing_src === $src &&
            $existing_creative === $creative
        ) {
            $old_param = $existing_param;
            break;
        }
    }
    // Only log if the code actually changed
    if ($old_param && $old_param !== $our_param) {
        file_put_contents($historyFile, "$old_param $our_param\n", FILE_APPEND | LOCK_EX);
    }
}

// Redirect to the retrieve page with the generated our_param code
header("Location: retrieve.php?our_param=$our_param");
exit;
?>