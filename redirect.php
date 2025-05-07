<?php
//file for storing mapping

define('MAPPINGS_FILE', __DIR__ . '/data/mappings.txt');
define('HISTORY_FILE', __DIR__ . '/data/history.txt');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the data directory exists with proper permissions
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    if (!mkdir($dataDir, 0777, true)) {
        error_log("Failed to create data directory: " . $dataDir);
        http_response_code(500);
        die("Failed to create data directory");
    }
    chmod($dataDir, 0777);
}

// Ensure files exist with proper permissions
if (!file_exists(MAPPINGS_FILE)) {
    if (!touch(MAPPINGS_FILE)) {
        error_log("Failed to create mappings file: " . MAPPINGS_FILE);
        http_response_code(500);
        die("Failed to create mappings file");
    }
    chmod(MAPPINGS_FILE, 0666);
}

if (!file_exists(HISTORY_FILE)) {
    if (!touch(HISTORY_FILE)) {
        error_log("Failed to create history file: " . HISTORY_FILE);
        http_response_code(500);
        die("Failed to create history file");
    }
    chmod(HISTORY_FILE, 0666);
}

//url parameters
$keyword = $_GET['keyword'] ?? 'unknown';
$src = $_GET['src'] ?? 'unknown';
$creative = $_GET['creative'] ?? 'unknown';
$refresh = isset($_GET['refresh']) ? true : false;

// see if are given
// if not, set to unknown
if ($keyword === 'unknown' && $src === 'unknown' && $creative === 'unknown') {
    // If all parameters are missing, return an error
    // If one or two are missing, set them to "unknown"
    http_response_code(400); // Bad Request
    echo "Error: At least one parameter must be given";
    exit;
}

// ...after getting $_GET params...
$keyword = substr(preg_replace('/[^\w\-]/', '', $keyword), 0, 64);
$src = substr(preg_replace('/[^\w\-]/', '', $src), 0, 64);
$creative = substr(preg_replace('/[^\w\-]/', '', $creative), 0, 64);

//generate ourparam
if ($refresh) {
    $our_param = hash('md5', uniqid($keyword . $src . $creative, true));
} else {
    $our_param = hash('md5', $keyword . $src . $creative);
}

//lock file because of concurrency
$fp = fopen(MAPPINGS_FILE, 'a+');
if (!$fp) {
    error_log("Failed to open mappings file for writing: " . MAPPINGS_FILE);
    http_response_code(500);
    die("Failed to open mappings file");
}

flock($fp, LOCK_EX); // Lock the file for writing

// Read existing mappings
$existing_mapping = [];
$found = false;
while (($line = fgets($fp)) !== false) {
    $line = trim($line);
    if (empty($line))
        continue;

    list($existing_param, $existing_keyword, $existing_src, $existing_creative) = explode(" ", $line);
    if (
        $existing_keyword === $keyword &&
        $existing_src === $src &&
        $existing_creative === $creative
    ) {
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

    // Add the new mapping
    $existing_mapping[] = "$our_param $keyword $src $creative";

    // Write back all mappings
    ftruncate($fp, 0);
    rewind($fp);
    foreach ($existing_mapping as $line) {
        fwrite($fp, $line . "\n");
    }
}

// Release the file lock and close the file
flock($fp, LOCK_UN);
fclose($fp);

// ...after writing the new mapping...
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
    if ($old_param && $old_param !== $our_param) {
        file_put_contents($historyFile, "$old_param $our_param\n", FILE_APPEND | LOCK_EX);
    }
}

// Redirect to the predefined URL with our_param
header("Location: retrieve.php?our_param=$our_param");
exit;
?>