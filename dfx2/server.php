<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$id =
    filter_input(
        INPUT_GET,
        'id',
        FILTER_VALIDATE_INT
    );

$server = null;
$players = [];
$error = null;

try {

    $db = dfx2_db_handle();

    $serverTable =
        dfx2_safe_table(
            DFX2_SERVERS_TABLE
        );

    $playerTable =
        dfx2_safe_table(
            DFX2_PLAYERS_TABLE
        );

    if ($id && $id > 0) {

        $stmt =
            $db->prepare(
                "SELECT *
                 FROM {$serverTable}
                 WHERE id = ?
                 LIMIT 1"
            );

        $stmt->execute([$id]);

        $server =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            )
            ?: null;


        if ($server) {

            $stmt =
                $db->prepare(
                    "SELECT
                        player_name,
                        score,
                        kills,
                        deaths,
                        ping,
                        team,
                        joined_at,
                        last_seen_at
                     FROM {$playerTable}
                     WHERE
                        server_id = ?
                        AND last_seen_at >=
                            NOW() - INTERVAL " .
                    (int)DFX2_HEARTBEAT_SECONDS .
                    " SECOND
                     ORDER BY
                        score DESC,
                        kills DESC,
                        player_name ASC"
                );

            $stmt->execute([$id]);

            $players =
                $stmt->fetchAll(
                    PDO::FETCH_ASSOC
                );
        }
    }

} catch (Throwable $e) {

    $error =
        'Unable to load DFX2 server information.';
}


if (!$id || $id < 1) {
    http_response_code(400);
}
elseif (!$server && !$error) {
    http_response_code(404);
}


$online =
    $server
    ? dfx2_is_online($server)
    : false;

$age =
    $server
    ? dfx2_heartbeat_age(
        $server['last_heartbeat_at']
        ?? null
    )
    : 999999;

$playerCount =
    $server
    ? max(
        (int)(
            $server['current_players']
            ?? 0
        ),
        count($players)
    )
    : 0;
?>
<!doctype html>
<html lang="en">
<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>
<?= $server
    ? dfx2_h(
        dfx2_clean_name(
            (string)$server['server_name']
        )
    )
    : 'DFX2 Server' ?>
</title>

<link
    rel="stylesheet"
    href="/assets/lobby.css"
>

</head>

<body data-lobby-theme="dfx2">

<div class="wrap">


<div class="topbar">

    <div class="brand">

        <h1>
            Delta Force Xtreme 2
        </h1>

        <p>
            Server Information
        </p>

    </div>


    <div class="nav">

        <a href="/">
            Main Lobby
        </a>

        <a href="./">
            DFX2 Lobby
        </a>

        <a href="about.php">
            About
        </a>

    </div>

</div>


<div class="card">


<?php if ($error): ?>

    <div class="message">

        <h2>
            Unable to load server
        </h2>

        <p>
            <?= dfx2_h($error) ?>
        </p>

    </div>


<?php elseif (!$server): ?>

    <div class="message">

        <h2>
            Server not found
        </h2>

        <p>
            No DFX2 server exists with ID
            <?= (int)($id ?: 0) ?>.
        </p>

        <a
            class="button"
            href="./"
        >
            Return to lobby
        </a>

    </div>


<?php else: ?>


<?php

$dot =
    !$online
    ? 'offline'
    : (
        $playerCount === 0
        ? 'idle'
        : 'playing'
    );

$status =
    !$online
    ? 'Offline'
    : (
        $playerCount === 0
        ? 'Online - Waiting for players'
        : 'Online'
    );

?>


<div class="hero">

    <div class="hero-brand">

        <img
            class="hero-logo"
            src="/images/DFX2.avif"
            alt="Delta Force Xtreme 2"
        >

        <div>

            <div class="hero-sub">
                Delta Force Xtreme 2
            </div>

            <div class="hero-title">

                <?= dfx2_nova_name(
                    (string)$server['server_name']
                ) ?>

            </div>

            <div class="hero-sub">

                <?= dfx2_h(
                    $server['host_ip']
                ) ?>

                :

                <?= (int)$server['game_port'] ?>

            </div>

        </div>

    </div>


    <div class="statusline status-large">

        <span
            class="dot <?= dfx2_h($dot) ?>"
        ></span>

        <?= dfx2_h($status) ?>

    </div>

</div>


<div class="detail-grid">


<div class="detail">

    <div class="label">
        Game Type
    </div>

    <div class="value">

        <?= dfx2_h(
            $server['game_type']
            ?: 'Unknown'
        ) ?>

    </div>

</div>


<div class="detail">

    <div class="label">
        Mission
    </div>

    <div class="value">

        <?= dfx2_h(
            $server['map_name']
            ?: 'Unknown'
        ) ?>

    </div>

</div>


<div class="detail">

    <div class="label">
        Players
    </div>

    <div class="value">

        <?= $playerCount ?>
        /
        <?= (int)$server['max_players'] ?>

    </div>

</div>


<div class="detail">

    <div class="label">
        Version
    </div>

    <div class="value">

        <?= dfx2_h(
            $server['version']
            ?: 'Unknown'
        ) ?>

    </div>

</div>


<div class="detail">

    <div class="label">
        Private
    </div>

    <div class="value">

        <?= (int)$server['passworded'] === 1
            ? 'Yes'
            : 'No' ?>

    </div>

</div>


<div class="detail">

    <div class="label">
        Dedicated
    </div>

    <div class="value">

        <?= (int)$server['dedicated'] === 1
            ? 'Yes'
            : 'No' ?>

    </div>

</div>


<div class="detail">

    <div class="label">
        Mod
    </div>

    <div class="value">

        <?= dfx2_h(
            $server['mod_name']
            ?: 'Default'
        ) ?>

    </div>

</div>


<div class="detail">

    <div class="label">
        Heartbeat
    </div>

    <div class="value">

        <?= dfx2_h(
            dfx2_age_text($age)
        ) ?>

    </div>

</div>


</div>


<div class="section">

<h2>
    Players
</h2>


<?php if (!$players): ?>

    <div class="message">

        <?= $online
            ? 'The server is online but no detailed player records have been received yet.'
            : 'No active players are available for this server.' ?>

    </div>


<?php else: ?>


<table>

<thead>

<tr>
    <th>Player</th>
    <th>Team</th>
    <th>Score</th>
    <th>Kills</th>
    <th>Deaths</th>
    <th>Ping</th>
</tr>

</thead>


<tbody>


<?php foreach ($players as $player): ?>


<tr>

<td class="name">

    <?= dfx2_nova_name(
        (string)$player['player_name']
    ) ?>

</td>


<td>

    <?= dfx2_h(
        $player['team']
        ?: '--'
    ) ?>

</td>


<td>
    <?= (int)$player['score'] ?>
</td>


<td>
    <?= (int)$player['kills'] ?>
</td>


<td>
    <?= (int)$player['deaths'] ?>
</td>


<td>

    <?= $player['ping'] !== null
        ? (int)$player['ping'] . ' ms'
        : '--' ?>

</td>


</tr>


<?php endforeach; ?>


</tbody>

</table>


<?php endif; ?>


<p class="page-actions">

    <a
        class="button"
        href="./"
    >
        ← Back to DFX2 Lobby
    </a>

    <a
        class="button"
        href="server.php?id=<?= (int)$server['id'] ?>"
    >
        Refresh
    </a>

</p>


</div>


<?php endif; ?>


</div>

</div>

</body>
</html>
