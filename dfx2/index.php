<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$servers = [];
$error = null;

try {

    $db = dfx2_db_handle();

    $serverTable = dfx2_safe_table(
        DFX2_SERVERS_TABLE
    );

    $playerTable = dfx2_safe_table(
        DFX2_PLAYERS_TABLE
    );

    $sql =
        "SELECT s.*,
        (
            SELECT COUNT(*)
            FROM {$playerTable} p
            WHERE p.server_id = s.id
              AND p.last_seen_at >= NOW() - INTERVAL " .
        (int)DFX2_HEARTBEAT_SECONDS .
        " SECOND
        ) AS live_player_rows
        FROM {$serverTable} s";

    $servers = $db
        ->query($sql)
        ->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {

    $error =
        'The DFX2 lobby database is not configured yet. ' .
        'Check includes/config.php and the DFX2 database connection.';
}


foreach ($servers as &$server) {

    $server['_online'] =
        dfx2_is_online($server);

    $server['_players'] =
        max(
            (int)(
                $server['current_players']
                ?? 0
            ),
            (int)(
                $server['live_player_rows']
                ?? 0
            )
        );

    $server['_clean_name'] =
        dfx2_clean_name(
            (string)(
                $server['server_name']
                ?? ''
            )
        );
}

unset($server);


/*
|--------------------------------------------------------------------------
| Summary counters
|--------------------------------------------------------------------------
*/

$totalServers = count($servers);
$playersOnline = 0;
$activeServers = 0;
$privateServers = 0;

foreach ($servers as $server) {

    if ((bool)$server['_online']) {

        $activeServers++;

        $playersOnline +=
            (int)$server['_players'];

        if (
            (int)(
                $server['passworded']
                ?? 0
            ) === 1
        ) {
            $privateServers++;
        }
    }
}


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$gameTypes = [];

foreach ($servers as $server) {

    $type =
        trim(
            (string)(
                $server['game_type']
                ?? ''
            )
        );

    if ($type !== '') {
        $gameTypes[$type] = $type;
    }
}

natcasesort($gameTypes);

$q =
    trim(
        (string)(
            $_GET['q']
            ?? ''
        )
    );

$source =
    trim(
        (string)(
            $_GET['source']
            ?? 'all'
        )
    );

$gameType =
    trim(
        (string)(
            $_GET['game_type']
            ?? 'all'
        )
    );

$population =
    trim(
        (string)(
            $_GET['population']
            ?? 'all'
        )
    );

$sort =
    trim(
        (string)(
            $_GET['sort']
            ?? 'players_desc'
        )
    );

$allowedPopulation = [
    'all',
    'players',
    'empty',
    'full',
    'offline',
];

$allowedSort = [
    'players_desc',
    'players_asc',
    'name_asc',
    'mission_asc',
    'type_asc',
];

if (
    !in_array(
        $population,
        $allowedPopulation,
        true
    )
) {
    $population = 'all';
}

if (
    !in_array(
        $sort,
        $allowedSort,
        true
    )
) {
    $sort = 'players_desc';
}

if (
    $source !== 'all'
    &&
    $source !== 'dfx2'
) {
    $source = 'all';
}


$filteredServers =
    array_values(
        array_filter(
            $servers,
            static function (
                array $server
            ) use (
                $q,
                $source,
                $gameType,
                $population
            ): bool {

                if (
                    $source !== 'all'
                    &&
                    $source !== 'dfx2'
                ) {
                    return false;
                }

                if (
                    $gameType !== 'all'
                    &&
                    strcasecmp(
                        (string)(
                            $server['game_type']
                            ?? ''
                        ),
                        $gameType
                    ) !== 0
                ) {
                    return false;
                }

                $players =
                    (int)(
                        $server['_players']
                        ?? 0
                    );

                $maxPlayers =
                    max(
                        1,
                        (int)(
                            $server['max_players']
                            ?? 32
                        )
                    );

                $online =
                    (bool)(
                        $server['_online']
                        ?? false
                    );

                if (
                    $population === 'players'
                    &&
                    (
                        !$online
                        ||
                        $players < 1
                    )
                ) {
                    return false;
                }

                if (
                    $population === 'empty'
                    &&
                    (
                        !$online
                        ||
                        $players !== 0
                    )
                ) {
                    return false;
                }

                if (
                    $population === 'full'
                    &&
                    (
                        !$online
                        ||
                        $players < $maxPlayers
                    )
                ) {
                    return false;
                }

                if (
                    $population === 'offline'
                    &&
                    $online
                ) {
                    return false;
                }

                if ($q !== '') {

                    $haystack =
                        strtolower(
                            implode(
                                ' ',
                                [
                                    $server['_clean_name']
                                        ?? '',
                                    $server['server_name']
                                        ?? '',
                                    $server['game_type']
                                        ?? '',
                                    $server['map_name']
                                        ?? '',
                                    $server['country_code']
                                        ?? '',
                                    $server['host_ip']
                                        ?? '',
                                    $server['mod_name']
                                        ?? '',
                                    $server['version']
                                        ?? '',
                                ]
                            )
                        );

                    if (
                        !str_contains(
                            $haystack,
                            strtolower($q)
                        )
                    ) {
                        return false;
                    }
                }

                return true;
            }
        )
    );


usort(
    $filteredServers,
    static function (
        array $a,
        array $b
    ) use ($sort): int {

        $aPlayers =
            (int)(
                $a['_players']
                ?? 0
            );

        $bPlayers =
            (int)(
                $b['_players']
                ?? 0
            );

        return match ($sort) {

            'players_asc' =>
                $aPlayers <=> $bPlayers,

            'name_asc' =>
                strcasecmp(
                    (string)(
                        $a['_clean_name']
                        ?? ''
                    ),
                    (string)(
                        $b['_clean_name']
                        ?? ''
                    )
                ),

            'mission_asc' =>
                strcasecmp(
                    (string)(
                        $a['map_name']
                        ?? ''
                    ),
                    (string)(
                        $b['map_name']
                        ?? ''
                    )
                ),

            'type_asc' =>
                strcasecmp(
                    (string)(
                        $a['game_type']
                        ?? ''
                    ),
                    (string)(
                        $b['game_type']
                        ?? ''
                    )
                ),

            default =>
                $bPlayers <=> $aPlayers
                ?:
                strcasecmp(
                    (string)(
                        $a['_clean_name']
                        ?? ''
                    ),
                    (string)(
                        $b['_clean_name']
                        ?? ''
                    )
                ),
        };
    }
);


function dfx2_selected(
    string $value,
    string $current
): string {
    return
        $value === $current
        ? ' selected'
        : '';
}
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
Delta Force Xtreme 2 Live Server List
</title>

<link
    rel="stylesheet"
    href="assets/lobby.css"
>

</head>

<body>

<div class="wrap">


<div class="topbar">

    <div class="brand">

        <h1>
            Delta Force Xtreme 2
        </h1>

        <p>
            Devilish Services Nova Lobby
        </p>

    </div>


    <div class="nav">

        <a href="/">
            Main Lobby
        </a>

        <a
            class="active"
            href="./"
        >
            DFX2 Lobby
        </a>

        <a href="about.php">
            About
        </a>

    </div>

</div>


<section class="hero">

    <div class="hero-brand">

        <img
            class="hero-logo"
            src="/images/DFX2.avif"
            alt="Delta Force Xtreme 2"
        >

        <div>

            <div class="hero-title">
                Live Server List
            </div>

            <div class="hero-sub">
                Delta Force Xtreme 2 multiplayer servers
            </div>

        </div>

    </div>


    <a
        class="refresh-top"
        href="./"
    >
        Refresh
    </a>

</section>


<section class="summary-grid">

    <div class="summary-card">
        <div class="summary-number">
            <?= $totalServers ?>
        </div>
        <div class="summary-label">
            Servers
        </div>
    </div>


    <div class="summary-card">
        <div class="summary-number">
            <?= $playersOnline ?>
        </div>
        <div class="summary-label">
            Players Online
        </div>
    </div>


    <div class="summary-card">
        <div class="summary-number">
            <?= $activeServers ?>
        </div>
        <div class="summary-label">
            Active Servers
        </div>
    </div>


    <div class="summary-card">
        <div class="summary-number">
            <?= $privateServers ?>
        </div>
        <div class="summary-label">
            Private
        </div>
    </div>

</section>


<form
    class="filters"
    method="get"
    action="./"
>


<div class="filter-field filter-search">

    <label for="q">
        Search
    </label>

    <input
        id="q"
        name="q"
        type="search"
        value="<?= dfx2_h($q) ?>"
        placeholder="Server, mission, IP, country..."
    >

</div>


<div class="filter-field">

    <label for="source">
        Source
    </label>

    <select
        id="source"
        name="source"
    >

        <option
            value="all"
            <?= dfx2_selected(
                'all',
                $source
            ) ?>
        >
            All sources
        </option>

        <option
            value="dfx2"
            <?= dfx2_selected(
                'dfx2',
                $source
            ) ?>
        >
            Devilish DFX2
        </option>

    </select>

</div>


<div class="filter-field">

    <label for="game_type">
        Game type
    </label>

    <select
        id="game_type"
        name="game_type"
    >

        <option value="all">
            All game types
        </option>

        <?php foreach ($gameTypes as $type): ?>

            <option
                value="<?= dfx2_h($type) ?>"
                <?= dfx2_selected(
                    $type,
                    $gameType
                ) ?>
            >
                <?= dfx2_h($type) ?>
            </option>

        <?php endforeach; ?>

    </select>

</div>


<div class="filter-field">

    <label for="population">
        Population
    </label>

    <select
        id="population"
        name="population"
    >

        <option
            value="all"
            <?= dfx2_selected(
                'all',
                $population
            ) ?>
        >
            All servers
        </option>

        <option
            value="players"
            <?= dfx2_selected(
                'players',
                $population
            ) ?>
        >
            Players online
        </option>

        <option
            value="empty"
            <?= dfx2_selected(
                'empty',
                $population
            ) ?>
        >
            Empty
        </option>

        <option
            value="full"
            <?= dfx2_selected(
                'full',
                $population
            ) ?>
        >
            Full
        </option>

        <option
            value="offline"
            <?= dfx2_selected(
                'offline',
                $population
            ) ?>
        >
            Offline / stale
        </option>

    </select>

</div>


<div class="filter-field">

    <label for="sort">
        Sort
    </label>

    <select
        id="sort"
        name="sort"
    >

        <option
            value="players_desc"
            <?= dfx2_selected(
                'players_desc',
                $sort
            ) ?>
        >
            Players: high to low
        </option>

        <option
            value="players_asc"
            <?= dfx2_selected(
                'players_asc',
                $sort
            ) ?>
        >
            Players: low to high
        </option>

        <option
            value="name_asc"
            <?= dfx2_selected(
                'name_asc',
                $sort
            ) ?>
        >
            Server name
        </option>

        <option
            value="mission_asc"
            <?= dfx2_selected(
                'mission_asc',
                $sort
            ) ?>
        >
            Mission
        </option>

        <option
            value="type_asc"
            <?= dfx2_selected(
                'type_asc',
                $sort
            ) ?>
        >
            Game type
        </option>

    </select>

</div>


<div class="filter-actions">

    <button
        class="apply"
        type="submit"
    >
        Apply
    </button>

    <a
        class="reset"
        href="./"
    >
        Reset
    </a>

</div>


</form>


<div class="list-heading">

    <div>

        <strong>
            DFX2 Live Servers
        </strong>

        <span>
            <?= count($filteredServers) ?>
            result<?= count($filteredServers) === 1
                ? ''
                : 's' ?>
        </span>

    </div>


    <div class="legend">

        <span>
            <i class="legend-dot playing"></i>
            Players
        </span>

        <span>
            <i class="legend-dot idle"></i>
            Online / empty
        </span>

        <span>
            <i class="legend-dot offline"></i>
            Offline
        </span>

    </div>

</div>


<div class="tablebox">


<?php if ($error): ?>

    <div class="message">

        <?= dfx2_h($error) ?>

        <div class="message-actions">

            <a
                class="button"
                href="about.php"
            >
                DFX2 setup information
            </a>

        </div>

    </div>


<?php elseif (!$filteredServers): ?>

    <div class="message">

        No DFX2 servers match the selected filters.

        <div class="message-actions">

            <a
                class="button"
                href="./"
            >
                Reset filters
            </a>

        </div>

    </div>


<?php else: ?>

<table>

<colgroup>
    <col class="col-game">
    <col class="col-cn">
    <col class="col-name">
    <col class="col-private">
    <col class="col-type">
    <col class="col-mission">
    <col class="col-time">
    <col class="col-players">
    <col class="col-info">
</colgroup>


<thead>

<tr>
    <th>DFX2</th>
    <th>CN</th>
    <th>Name</th>
    <th>Private</th>
    <th>Type</th>
    <th>Mission</th>
    <th>Time Left</th>
    <th>Players</th>
    <th>Info</th>
</tr>

</thead>


<tbody>


<?php foreach ($filteredServers as $server): ?>


<?php

$online =
    (bool)$server['_online'];

$players =
    (int)$server['_players'];

$dot =
    !$online
    ? 'offline'
    : (
        $players === 0
        ? 'idle'
        : 'playing'
    );

$status =
    !$online
    ? 'Offline'
    : (
        $players === 0
        ? 'Online - waiting'
        : 'Online'
    );

$timeLeft =
    trim(
        (string)(
            $server['time_left']
            ?? ''
        )
    );

if ($timeLeft === '') {
    $timeLeft = '--:--';
}

?>


<tr class="server-row">


<td
    data-label="DFX2"
    class="game-cell"
>

    <img
        class="game-thumb"
        src="/images/DFX2.avif"
        alt="DFX2"
    >

</td>


<td
    data-label="CN"
    class="country"
>

    <?= dfx2_h(
        dfx2_country(
            $server['country_code']
            ?? null
        )
    ) ?>

</td>


<td
    data-label="Name"
    class="name"
>

    <div class="server-name">

        <?= dfx2_nova_name(
            (string)(
                $server['server_name']
                ?? 'Unnamed Server'
            )
        ) ?>

    </div>


    <div class="statusline">

        <span
            class="dot <?= dfx2_h($dot) ?>"
        ></span>

        <span>
            <?= dfx2_h($status) ?>
        </span>

    </div>

</td>


<td
    data-label="Private"
    class="private <?= (int)(
        $server['passworded']
        ?? 0
    ) === 1
        ? 'yes'
        : '' ?>"
>

    <?= (int)(
        $server['passworded']
        ?? 0
    ) === 1
        ? 'Yes'
        : 'No' ?>

</td>


<td data-label="Type">

    <?= dfx2_h(
        (string)(
            $server['game_type']
            ?: 'Unknown'
        )
    ) ?>

</td>


<td data-label="Mission">

    <?= dfx2_h(
        (string)(
            $server['map_name']
            ?: 'Unknown'
        )
    ) ?>

</td>


<td
    data-label="Time Left"
    class="time-left"
>

    <?= dfx2_h($timeLeft) ?>

</td>


<td
    data-label="Players"
    class="players"
>

    <?= $players ?>
    of
    <?= (int)(
        $server['max_players']
        ?? 32
    ) ?>

</td>


<td
    data-label="Info"
    class="info-cell"
>

    <a
        class="view"
        href="server.php?id=<?= (int)$server['id'] ?>"
    >
        View
    </a>

</td>


</tr>


<?php endforeach; ?>


</tbody>

</table>


<?php endif; ?>


</div>


<div class="footer">
    Devilish Services • Delta Force Xtreme 2 Nova Lobby
</div>


</div>

</body>
</html>
