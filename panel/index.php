<?php
session_start();
$password = 'box26admin';

if ($_POST['pass'] ?? '' === $password) {
    $_SESSION['logged_in'] = true;
    header('Location: hijack.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>BOX_26 Panel</title>
    <style>
        body { background: #0a0e1a; color: #00ff9d; font-family: monospace; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login { background: #1a1f2f; padding: 40px; border-radius: 10px; border: 1px solid #00ff9d; }
        input { background: #0a0e1a; border: 1px solid #00ff9d; color: #00ff9d; padding: 10px; width: 200px; }
        button { background: #00ff9d; color: #0a0e1a; border: none; padding: 10px 20px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <div class="login">
        <h2>🔐 BOX_26 CONTROL PANEL</h2>
        <form method="POST">
            <input type="password" name="pass" placeholder="Enter password" autofocus><br><br>
            <button type="submit">ACCESS</button>
        </form>
    </div>
</body>
</html>
