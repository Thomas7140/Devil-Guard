<?php
declare(strict_types=1);
require_once '/home/devilishservices/connections/dfx/db.php';
require_once __DIR__.'/includes/functions.php';

$servers=[];
$error=null;
try {
    $db=dfx_db_handle();
    $sql="SELECT s.*,
        (SELECT COUNT(*) FROM dfx_server_players p
         WHERE p.server_id=s.id
         AND p.last_seen_at>=NOW()-INTERVAL 120 SECOND) AS live_player_rows
        FROM dfx_servers s
        ORDER BY CASE WHEN s.status='online'
          AND s.last_heartbeat_at>=NOW()-INTERVAL 120 SECOND THEN 0 ELSE 1 END,
          s.current_players DESC,s.server_name ASC";
    $servers=$db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error='Unable to load the DFX server list.';
}

$onlineServers=0;
$totalPlayers=0;
foreach ($servers as &$server) {
    $server['_online']=dfx_is_online($server);
    $server['_players']=max(
        (int)($server['current_players']??0),
        (int)($server['live_player_rows']??0)
    );
    if ($server['_online']) {
        $onlineServers++;
        $totalPlayers+=(int)$server['_players'];
    }
}
unset($server);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Delta Force Xtreme Live Server List</title>
<link rel="stylesheet" href="assets/lobby.css">
</head>
<body>
<div class="wrap">
<div class="topbar">
  <div class="brand"><h1>Delta Force Xtreme</h1><p>Devilish Services Nova Lobby</p></div>
  <div class="nav"><a href="/">Main Lobby</a><a href="./">DFX Lobby</a><a href="about.php">About</a></div>
</div>

<div class="hero">
  <div><div class="hero-title">Live Server List</div><div class="hero-sub">Delta Force Xtreme 1.6.9.3 multiplayer servers</div></div>
  <div class="stats">
    <div class="stat">Servers: <strong><?= $onlineServers ?></strong></div>
    <div class="stat">Players: <strong><?= $totalPlayers ?></strong></div>
  </div>
</div>

<div class="tools">
  <input id="serverSearch" class="search" type="search" placeholder="Search server, type or mission...">
  <a class="refresh" href="./">Refresh</a>
</div>

<div class="tablebox">
<?php if ($error): ?>
  <div class="message"><?= dfx_h($error) ?></div>
<?php elseif (!$servers): ?>
  <div class="message">No DFX servers have been registered yet.</div>
<?php else: ?>
<table>
<thead>
<tr><th>DFX</th><th>CN</th><th>Name</th><th>Private</th><th>Type</th><th>Mission</th><th>Time Left</th><th>Players</th><th>Info</th></tr>
</thead>
<tbody>
<?php foreach ($servers as $server):
    $online=(bool)$server['_online'];
    $players=(int)$server['_players'];
    $dot=!$online?'offline':($players===0?'idle':'playing');
    $status=!$online?'Offline':($players===0?'Waiting for players':'Online');
    $search=strtolower(implode(' ',[
        $server['server_name']??'',
        $server['game_type']??'',
        $server['map_name']??'',
        $server['country_code']??''
    ]));
?>
<tr class="server-row" data-search="<?= dfx_h($search) ?>">
  <td><span class="dfx-badge">DFX</span></td>
  <td><?= dfx_h(dfx_country($server['country_code']??null)) ?></td>
  <td class="name"><?= dfx_nova_name((string)($server['server_name']??'Unnamed Server')) ?>
    <div class="statusline"><span class="dot <?= dfx_h($dot) ?>"></span><?= dfx_h($status) ?></div>
  </td>
  <td class="private <?= (int)($server['passworded']??0)===1?'yes':'' ?>"><?= (int)($server['passworded']??0)===1?'Yes':'No' ?></td>
  <td><?= dfx_h($server['game_type']?:'Unknown') ?></td>
  <td><?= dfx_h($server['map_name']?:'Unknown') ?></td>
  <td class="muted">--:--</td>
  <td class="players"><?= $players ?> of <?= (int)($server['max_players']??32) ?></td>
  <td><a class="view" href="server.php?id=<?= (int)$server['id'] ?>">View</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
<div class="footer">Devilish Services • Delta Force Xtreme Nova Lobby</div>
</div>
<script>
const q=document.getElementById('serverSearch');
const rows=[...document.querySelectorAll('.server-row')];
if(q){q.addEventListener('input',()=>{const v=q.value.trim().toLowerCase();rows.forEach(r=>r.style.display=(r.dataset.search||'').includes(v)?'':'none');});}
</script>
</body>
</html>
