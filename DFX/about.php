<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>About the DFX Lobby</title>
<link rel="stylesheet" href="/assets/lobby.css">
</head>
<body data-lobby-theme="dfx">
<div class="wrap">
<div class="topbar">
  <div class="brand"><h1>Delta Force Xtreme</h1><p>Devilish Services Nova Lobby</p></div>
  <div class="nav"><a href="/">Main Lobby</a><a href="./">DFX Lobby</a><a href="about.php">About</a></div>
</div>
<div class="card">
<div class="hero"><div><div class="hero-title">About this Lobby</div><div class="hero-sub">Community replacement services for Delta Force Xtreme</div></div></div>
<div class="about">
<p>This lobby provides a modern web view of Delta Force Xtreme multiplayer servers registered with the Devilish Services Nova replacement system.</p>
<h2>Server status</h2>
<p>A server is treated as online when its database status is online and its most recent heartbeat is no more than 120 seconds old. Online servers with no players use a slow flashing green indicator. Servers with players use a solid green indicator, and stale or offline servers use red.</p>
<h2>Server names</h2>
<p>NovaLogic tilde colour codes (~0 through ~9) are rendered as colours while preserving the visible server name.</p>
<h2>Current development status</h2>
<p>The web lobby reads from the DFX database. Native DFX hosting registration over the replacement UDP lobby is a separate protocol component and must populate dfx_servers for real hosted games to appear automatically.</p>
<p><a class="button" href="./">← Back to DFX Lobby</a></p>
</div>
</div>
</div>
</body>
</html>
