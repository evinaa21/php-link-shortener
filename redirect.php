<?php
// This part tells the script where to find important files and how to show errors.
define('MAPPINGS_FILE', __DIR__ . '/data/mappings.txt');
define('HISTORY_FILE', __DIR__ . '/data/history.txt');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// This section makes sure the folder and files we need to store links are ready.
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    if (!mkdir($dataDir, 0777, true)) {
        error_log("Failed to create the data directory: " . $dataDir);
        http_response_code(500);
        die("Failed to create the data directory");
    }
    chmod($dataDir, 0777);
}

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

// Here, we get the words the user typed into the website form.
$keyword = $_GET['keyword'] ?? 'unknown';
$src = $_GET['src'] ?? 'unknown';
$creative = $_GET['creative'] ?? 'unknown';
$refresh = isset($_GET['refresh']) ? true : false;

// This bit checks if the user actually gave us any words and cleans them up.
if ($keyword === 'unknown' && $src === 'unknown' && $creative === 'unknown') {
    http_response_code(400);
    echo "Error: At least one parameter must be given";
    exit;
}

$keyword = substr(preg_replace('/[^\w\-]/', '', $keyword), 0, 64);
$src = substr(preg_replace('/[^\w\-]/', '', $src), 0, 64);
$creative = substr(preg_replace('/[^\w\-]/', '', $creative), 0, 64);

// Now, we look into our main file to see what links are already saved.
$all_current_mapping_lines = [];
$old_param_for_history = null;
$our_param_if_exists_no_refresh = null;
$found_existing_for_no_refresh = false;

if (file_exists(MAPPINGS_FILE)) {
    $lines = file(MAPPINGS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line_content) {
            $all_current_mapping_lines[] = $line_content;
            $parts = explode(" ", $line_content, 4);
            if (count($parts) < 4) {
                continue;
            }
            list($p, $k, $s, $c) = $parts;

            if ($k === $keyword && $s === $src && $c === $creative) {
                if ($refresh) {
                    $old_param_for_history = $p;
                } else {
                    $our_param_if_exists_no_refresh = $p;
                    $found_existing_for_no_refresh = true;
                }
            }
        }
    } else {
        error_log("Failed to read mappings file content: " . MAPPINGS_FILE);
        http_response_code(500);
        die("Error reading mappings data.");
    }
}

// This part figures out what the short code for the link should be.
if ($refresh) {
    $our_param = hash('md5', uniqid($keyword . $src . $creative, true));
} else {
    if ($found_existing_for_no_refresh) {
        $our_param = $our_param_if_exists_no_refresh;
    } else {
        $our_param = hash('md5', $keyword . $src . $creative);
    }
}

// Here, we get ready to update our main list of links.
$new_mappings_to_write = [];
$entry_for_current_input_processed = false;

foreach ($all_current_mapping_lines as $line_content) {
    $parts = explode(" ", $line_content, 4);
    if (count($parts) < 4) {
        $new_mappings_to_write[] = $line_content;
        continue;
    }
    list($_, $k, $s, $c) = $parts;

    if ($k === $keyword && $s === $src && $c === $creative) {
        $new_mappings_to_write[] = "$our_param $keyword $src $creative";
        $entry_for_current_input_processed = true;
    } else {
        $new_mappings_to_write[] = $line_content;
    }
}

if (!$entry_for_current_input_processed) {
    $new_mappings_to_write[] = "$our_param $keyword $src $creative";
}

// This is where we save the updated list of links back to our main file.
$fp_map = fopen(MAPPINGS_FILE, 'w');
if (!$fp_map) {
    error_log("Failed to open mappings file for writing: " . MAPPINGS_FILE);
    http_response_code(500);
    die("Failed to open mappings file for writing.");
}

if (flock($fp_map, LOCK_EX)) {
    foreach ($new_mappings_to_write as $map_line) {
        fwrite($fp_map, $map_line . "\n");
    }
    flock($fp_map, LOCK_UN);
} else {
    error_log("Could not lock mappings file for writing: " . MAPPINGS_FILE);
    fclose($fp_map);
    http_response_code(500);
    die("Failed to lock mappings file.");
}
fclose($fp_map);

// If a link was refreshed, we make a note of the old and new short codes here.
if ($refresh && $old_param_for_history !== null && $old_param_for_history !== $our_param) {
    $history_entry = "$old_param_for_history $our_param\n";
    if (file_put_contents(HISTORY_FILE, $history_entry, FILE_APPEND | LOCK_EX) === false) {
        error_log("Failed to write to history file: " . HISTORY_FILE);
    }
}
header("Location: https://example.com/targetpage?our_param=$our_param"); // New: Redirect to an example external site
exit;
?>