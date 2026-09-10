<?php
// +------------------------------------------------------------------+
// |  devsapp — views/login.php                                        |
// |  Halaman login. Variabel tersedia dari AuthManager::renderLogin(). |
// |  $appName, $la (label), $lb (button text), $lp (placeholder)      |
// +------------------------------------------------------------------+
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
<meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
<title><?= $appName ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body {
    margin:0;
    background:#000;
    color:#00ff66;
    font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;
    display:grid;
    place-items:center;
    min-height:100vh;
}
.box {
    width:min(380px, 90vw);
    background:#0a0a0a;
    border:2px solid #00ff66;
    padding:24px;
    box-shadow:0 0 15px rgba(0,255,102,0.2);
    text-align:left;
}
.icon-logo {
    font-size:32px;
    color:#00ff66;
    margin-bottom:12px;
    text-align:center;
}
h2 {
    margin:0 0 6px;
    font-size:18px;
    text-align:center;
    text-transform:uppercase;
    color:#00ff66;
}
.subtitle {
    color:#888;
    font-size:12px;
    text-align:center;
    margin-bottom:16px;
}
input, button {
    width:100%;
    padding:10px;
    border:1px solid #00ff66;
    background:#000;
    color:#00ff66;
    font-family:inherit;
    box-sizing:border-box;
    margin-top:10px;
    font-size:13px;
}
button {
    background:#00ff66;
    color:#000;
    font-weight:bold;
    cursor:pointer;
    text-transform:uppercase;
    letter-spacing:1px;
}
button:hover {
    background:#ffb000;
    border-color:#ffb000;
    color:#000;
}
</style>
</head>
<body>
    <form class="box" method="post">
        <div class="icon-logo"><i class="fa-solid fa-code"></i></div>
        <h2>[ <?= $appName ?> ]</h2>
        <div class="subtitle"><?= $la ?></div>
        <input type="password" name="ap" placeholder="<?= $lp ?>" autofocus required>
        <button type="submit"><i class="fa-solid fa-key"></i> <?= $lb ?></button>
    </form>
</body>
</html>
