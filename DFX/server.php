<?php
declare(strict_types=1);
require_once '/home/devilishservices/connections/dfx/db.php';
require_once __DIR__.'/includes/functions.php';

$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
$server=null;
$players=[];
$error=null;

try {
    $db=dfx_db_handle();
    if ($id && $id>0) {
        $stmt=$db->prepare('SELECT * FROM dfx_servers WHERE id=? LIMIT 1');
        $stmt->execute([$id]);
        $server=$stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($server) {
            $stmt=$db->prepare('SELECT player_name,score,kills,deaths,ping,team,joined_at,last_seen_at
                FROM dfx_server_players
                WHERE server_id=? AND last_seen_at>=NOW()-INTERVAL 120 SECOND
                ORDER BY score DESC,kills DESC,player_name ASC');
            $stmt->execute([$id]);
            $players=$stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Throwable $e) {
    $error='Unable to load server information.';
}

if (!$id || $id<1) http_response_code(400);
elseif (!$server && !$error) http_response_code(404);

$online=$server ? dfx_is_online($server) : false;
$age=$server ? dfx_heartbeat_age($server['last_heartbeat_at']??null) : 999999;
$playerCount=$server ? max((int)($server['current_players']??0),count($players)) : 0;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $server ? dfx_h(dfx_clean_name((string)$server['server_name'])) : 'DFX Server' ?></title>
<link rel="stylesheet" href="/assets/lobby.css">
</head>
<body data-lobby-theme="dfx">
<div class="wrap">
<div class="topbar">
  <div class="brand"><h1>Delta Force Xtreme</h1><p>Server Information</p></div>
  <div class="nav"><a href="/">Main Lobby</a><a href="./">DFX Lobby</a><a href="about.php">About</a></div>
</div>

<div class="card">
<?php if ($error): ?>
  <div class="message"><h2>Unable to load server</h2><p><?= dfx_h($error) ?></p></div>
<?php elseif (!$server): ?>
  <div class="message"><h2>Server not found</h2><p>No DFX server exists with ID <?= (int)($id?:0) ?>.</p><a class="button" href="./">Return to lobby</a></div>
<?php else:
  $dot=!$online?'offline':($playerCount===0?'idle':'playing');
  $status=!$online?'Offline':($playerCount===0?'Online - Waiting for players':'Online');
?>
<div class="hero">
  <div><div class="hero-sub">Delta Force Xtreme</div><div class="hero-title"><?= dfx_nova_name((string)$server['server_name']) ?></div><div class="hero-sub"><?= dfx_h($server['host_ip']) ?>:<?= (int)$server['game_port'] ?></div></div>
  <div class="statusline"><span class="dot <?= dfx_h($dot) ?>"></span><?= dfx_h($status) ?></div>
</div>

<div class="detail-grid">
  <div class="detail"><div class="label">Game Type</div><div class="value"><?= dfx_h($server['game_type']?:'Unknown') ?></div></div>
  <div class="detail"><div class="label">Mission</div><div class="value"><?= dfx_h($server['map_name']?:'Unknown') ?></div></div>
  <div class="detail"><div class="label">Players</div><div class="value"><?= $playerCount ?> / <?= (int)$server['max_players'] ?></div></div>
  <div class="detail"><div class="label">Version</div><div class="value"><?= dfx_h($server['version']?:'Unknown') ?></div></div>
  <div class="detail"><div class="label">Private</div><div class="value"><?= (int)$server['passworded']===1?'Yes':'No' ?></div></div>
  <div class="detail"><div class="label">Dedicated</div><div class="value"><?= (int)$server['dedicated']===1?'Yes':'No' ?></div></div>
  <div class="detail"><div class="label">Mod</div><div class="value"><?= dfx_h($server['mod_name']?:'Default') ?></div></div>
  <div class="detail"><div class="label">Heartbeat</div><div class="value"><?= dfx_h(dfx_age_text($age)) ?></div></div>
</div>

<div class="section">
<h2>Players</h2>
<?php if (!$players): ?>
  <div class="message"><?= $online ? 'The server is online but no detailed player records have been received yet.' : 'No active players are available for this server.' ?></div>
<?php else: ?>
<table>
<thead><tr><th>Player</th><th>Team</th><th>Score</th><th>Kills</th><th>Deaths</th><th>Ping</th></tr></thead>
<tbody>
<?php foreach ($players as $player): ?>
<tr>
  <td class="name"><?= dfx_nova_name((string)$player['player_name']) ?></td>
  <td><?= dfx_h($player['team']?:'--') ?></td>
  <td><?= (int)$player['score'] ?></td>
  <td><?= (int)$player['kills'] ?></td>
  <td><?= (int)$player['deaths'] ?></td>
  <td><?= $player['ping']!==null ? (int)$player['ping'].' ms' : '--' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
<p><a class="button" href="./">← Back to DFX Lobby</a> <a class="button" href="server.php?id=<?= (int)$server['id'] ?>">Refresh</a></p>
</div>
<?php endif; ?>
</div>
</div>
</body>
</html>
