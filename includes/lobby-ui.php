<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Devilish Services - Universal Nova Lobby UI
|--------------------------------------------------------------------------
|
| This is the ONE shared lobby page template.
|
| BHD, DFX and DFX2 all normalize their game-specific server data into
| the same array shape and pass it to lobby_render_index().
|
| Do not copy the DFX HTML into each game folder. Change the layout here
| once and every lobby follows it.
|
*/

function lobby_h(mixed $value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function lobby_nova_name(string $value): string
{
    $colors = [
        '0' => '#FFFFFF',
        '1' => '#00FF00',
        '2' => '#FF0000',
        '3' => '#008000',
        '4' => '#FFFF00',
        '5' => '#00FFFF',
        '6' => '#808080',
        '7' => '#008080',
        '8' => '#800000',
        '9' => '#80FFFF',
    ];

    $parts = preg_split(
        '/(~[0-9])/',
        $value,
        -1,
        PREG_SPLIT_DELIM_CAPTURE
    );

    if (!is_array($parts)) {
        return lobby_h($value);
    }

    $color = '#FFFFFF';
    $html = '';

    foreach ($parts as $part) {
        if (preg_match('/^~([0-9])$/', $part, $match)) {
            $color = $colors[$match[1]] ?? '#FFFFFF';
            continue;
        }

        if ($part !== '') {
            $html .=
                '<span style="color:' .
                lobby_h($color) .
                '">' .
                lobby_h($part) .
                '</span>';
        }
    }

    return $html !== ''
        ? $html
        : lobby_h($value);
}

function lobby_clean_name(string $value): string
{
    return trim(
        (string)preg_replace(
            '/~[0-9]/',
            '',
            $value
        )
    );
}

function lobby_country(?string $code): string
{
    $code = strtoupper(
        trim((string)$code)
    );

    return preg_match('/^[A-Z]{2}$/', $code)
        ? $code
        : '--';
}

function lobby_flag_markup(string $country): string
{
    $country = lobby_country($country);

    if ($country === '--') {
        return '<span class="country-code country-code-missing">--</span>';
    }

    $code = lobby_h($country);
    $filename = strtolower($country) . '.svg';
    $src = '/flags/' . rawurlencode($filename);

    return
        '<span class="country-flag-wrap">' .
            '<img' .
                ' class="country-flag"' .
                ' src="' . lobby_h($src) . '"' .
                ' alt="' . $code . '"' .
                ' title="' . $code . '"' .
                ' onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'inline\';"' .
            '>' .
            '<span class="country-code country-code-fallback">' .
                $code .
            '</span>' .
        '</span>';
}

function lobby_int(mixed $value, int $default = 0): int
{
    if (is_int($value)) {
        return $value;
    }

    if (is_numeric($value)) {
        return (int)$value;
    }

    if (is_string($value)) {
        if (preg_match('/-?\d+/', $value, $match)) {
            return (int)$match[0];
        }
    }

    return $default;
}

/**
 * Parse values such as:
 *   "27 of 50"
 *   "27/50"
 *   "27"
 *
 * @return array{0:int,1:int}
 */
function lobby_parse_player_counts(
    mixed $players,
    mixed $maxPlayers = null,
    int $defaultMax = 32
): array {
    $current = 0;
    $max = lobby_int($maxPlayers, $defaultMax);

    if (is_string($players)) {
        if (
            preg_match(
                '/(\d+)\s*(?:of|\/)\s*(\d+)/i',
                $players,
                $match
            )
        ) {
            $current = (int)$match[1];
            $max = (int)$match[2];

            return [
                max(0, $current),
                max(1, $max),
            ];
        }
    }

    $current = lobby_int($players, 0);

    return [
        max(0, $current),
        max(1, $max),
    ];
}

function lobby_selected(
    string $value,
    string $current
): string {
    return $value === $current
        ? ' selected'
        : '';
}

/**
 * Normalized server row shape used by the shared template.
 *
 * Required/recognized keys:
 * id, source, name, country, private, type, mission, time_left,
 * players, max_players, online, view_url, image_url
 *
 * @param array<int,array<string,mixed>> $servers
 * @return array<int,array<string,mixed>>
 */
function lobby_normalize_rows(
    array $servers,
    array $config
): array {
    $rows = [];

    foreach ($servers as $server) {
        if (!is_array($server)) {
            continue;
        }

        [$players, $maxPlayers] =
            lobby_parse_player_counts(
                $server['players'] ?? 0,
                $server['max_players'] ?? ($config['default_max_players'] ?? 32),
                (int)($config['default_max_players'] ?? 32)
            );

        $name = trim(
            (string)($server['name'] ?? 'Unnamed Server')
        );

        if ($name === '') {
            $name = 'Unnamed Server';
        }

        $rows[] = [
            'id' => (string)($server['id'] ?? ''),
            'source' => strtolower(
                trim(
                    (string)($server['source'] ?? 'default')
                )
            ),
            'name' => $name,
            '_clean_name' => lobby_clean_name($name),
            'country' => lobby_country(
                isset($server['country'])
                    ? (string)$server['country']
                    : null
            ),
            'private' => (bool)($server['private'] ?? false),
            'type' => trim((string)($server['type'] ?? 'Unknown')) ?: 'Unknown',
            'mission' => trim((string)($server['mission'] ?? 'Unknown')) ?: 'Unknown',
            'time_left' => trim((string)($server['time_left'] ?? '')) ?: '--:--',
            'players' => $players,
            'max_players' => $maxPlayers,
            'online' => (bool)($server['online'] ?? true),
            'view_url' => (string)($server['view_url'] ?? '#'),
            'image_url' => (string)(
                $server['image_url']
                ?? ($config['row_image'] ?? $config['hero_image'] ?? '')
            ),
        ];
    }

    return $rows;
}

/**
 * Apply the same filters/sort for every game lobby.
 *
 * @param array<int,array<string,mixed>> $servers
 * @return array{
 *   rows:array<int,array<string,mixed>>,
 *   total:int,
 *   players_online:int,
 *   active:int,
 *   private:int,
 *   game_types:array<int,string>,
 *   q:string,
 *   source:string,
 *   game_type:string,
 *   population:string,
 *   sort:string
 * }
 */
function lobby_prepare_index(
    array $servers,
    array $config
): array {
    $servers = lobby_normalize_rows(
        $servers,
        $config
    );

    $totalServers = count($servers);
    $playersOnline = 0;
    $activeServers = 0;
    $privateServers = 0;

    foreach ($servers as $server) {
        if ((bool)$server['online']) {
            $activeServers++;
            $playersOnline += (int)$server['players'];

            if ((bool)$server['private']) {
                $privateServers++;
            }
        }
    }

    $gameTypes = [];

    foreach ($servers as $server) {
        $type = trim((string)$server['type']);

        if ($type !== '') {
            $gameTypes[$type] = $type;
        }
    }

    natcasesort($gameTypes);
    $gameTypes = array_values($gameTypes);

    $q = trim((string)($_GET['q'] ?? ''));
    $source = strtolower(
        trim((string)($_GET['source'] ?? 'all'))
    );
    $gameType = trim((string)($_GET['game_type'] ?? 'all'));
    $population = trim((string)($_GET['population'] ?? 'all'));
    $sort = trim((string)($_GET['sort'] ?? 'players_desc'));

    $sourceOptions =
        is_array($config['source_options'] ?? null)
        ? $config['source_options']
        : [];

    $allowedSources = ['all'];

    foreach ($sourceOptions as $value => $label) {
        $allowedSources[] = strtolower((string)$value);
    }

    if (!in_array($source, $allowedSources, true)) {
        $source = 'all';
    }

    $allowedPopulation = [
        'all',
        'players',
        'empty',
        'full',
        'offline',
    ];

    if (!in_array($population, $allowedPopulation, true)) {
        $population = 'all';
    }

    $allowedSort = [
        'players_desc',
        'players_asc',
        'name_asc',
        'mission_asc',
        'type_asc',
    ];

    if (!in_array($sort, $allowedSort, true)) {
        $sort = 'players_desc';
    }

    $filtered =
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
                        strtolower((string)$server['source']) !== $source
                    ) {
                        return false;
                    }

                    if (
                        $gameType !== 'all'
                        &&
                        strcasecmp(
                            (string)$server['type'],
                            $gameType
                        ) !== 0
                    ) {
                        return false;
                    }

                    $players = (int)$server['players'];
                    $maxPlayers = max(1, (int)$server['max_players']);
                    $online = (bool)$server['online'];

                    if (
                        $population === 'players'
                        &&
                        (!$online || $players < 1)
                    ) {
                        return false;
                    }

                    if (
                        $population === 'empty'
                        &&
                        (!$online || $players !== 0)
                    ) {
                        return false;
                    }

                    if (
                        $population === 'full'
                        &&
                        (!$online || $players < $maxPlayers)
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
                                        (string)$server['_clean_name'],
                                        (string)$server['name'],
                                        (string)$server['type'],
                                        (string)$server['mission'],
                                        (string)$server['country'],
                                        (string)$server['source'],
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
        $filtered,
        static function (
            array $a,
            array $b
        ) use ($sort): int {

            $aPlayers = (int)$a['players'];
            $bPlayers = (int)$b['players'];

            return match ($sort) {
                'players_asc' =>
                    $aPlayers <=> $bPlayers,

                'name_asc' =>
                    strcasecmp(
                        (string)$a['_clean_name'],
                        (string)$b['_clean_name']
                    ),

                'mission_asc' =>
                    strcasecmp(
                        (string)$a['mission'],
                        (string)$b['mission']
                    ),

                'type_asc' =>
                    strcasecmp(
                        (string)$a['type'],
                        (string)$b['type']
                    ),

                default =>
                    $bPlayers <=> $aPlayers
                    ?:
                    strcasecmp(
                        (string)$a['_clean_name'],
                        (string)$b['_clean_name']
                    ),
            };
        }
    );

    return [
        'rows' => $filtered,
        'total' => $totalServers,
        'players_online' => $playersOnline,
        'active' => $activeServers,
        'private' => $privateServers,
        'game_types' => $gameTypes,
        'q' => $q,
        'source' => $source,
        'game_type' => $gameType,
        'population' => $population,
        'sort' => $sort,
    ];
}

/**
 * The universal DFX-format lobby page.
 *
 * @param array<string,mixed> $config
 * @param array<int,array<string,mixed>> $servers
 */
function lobby_render_index(
    array $config,
    array $servers,
    ?string $error = null
): void {
    $state = lobby_prepare_index(
        $servers,
        $config
    );

    $rows = $state['rows'];
    $sourceOptions =
        is_array($config['source_options'] ?? null)
        ? $config['source_options']
        : [];

    $theme = (string)($config['theme'] ?? 'dfx');
    $title = (string)($config['title'] ?? 'Delta Force');
    $lobbyLabel = (string)($config['lobby_label'] ?? 'Lobby');
    $heroTitle = (string)($config['hero_title'] ?? 'Live Server List');
    $heroSub = (string)($config['hero_subtitle'] ?? 'Multiplayer servers');
    $heroImage = (string)($config['hero_image'] ?? '');
    $heroAlt = (string)($config['hero_alt'] ?? $title);
    $firstColumn = (string)($config['first_column'] ?? 'Game');
    $showStatusColumn = (bool)($config['show_status_column'] ?? true);
    $statusInName = (bool)($config['status_in_name'] ?? false);
    $liveHeading = (string)($config['live_heading'] ?? ($lobbyLabel . ' Live Servers'));
    $footer = (string)($config['footer'] ?? ('Devilish Services • ' . $title . ' Nova Lobby'));
    $aboutUrl = (string)($config['about_url'] ?? 'about.php');
    $mainUrl = (string)($config['main_url'] ?? '/');
    $lobbyUrl = (string)($config['lobby_url'] ?? './');

    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>
<title><?=lobby_h($title)?> Live Server List</title>
<link
    rel="stylesheet"
    href="/assets/lobby.css"
>
</head>

<body data-lobby-theme="<?=lobby_h($theme)?>">

<div class="wrap">

    <div class="topbar">

        <div class="brand">
            <h1><?=lobby_h($title)?></h1>
            <p>Devilish Services Nova Lobby</p>
        </div>

        <div class="nav">

            <a href="<?=lobby_h($mainUrl)?>">
                Main Lobby
            </a>

            <a
                class="active"
                href="<?=lobby_h($lobbyUrl)?>"
            >
                <?=lobby_h($lobbyLabel)?> Lobby
            </a>

            <a href="<?=lobby_h($aboutUrl)?>">
                About
            </a>

        </div>

    </div>


    <section class="hero">

        <div class="hero-brand">

            <?php if ($heroImage !== ''): ?>

                <img
                    class="hero-logo"
                    src="<?=lobby_h($heroImage)?>"
                    alt="<?=lobby_h($heroAlt)?>"
                >

            <?php endif; ?>

            <div>

                <div class="hero-title">
                    <?=lobby_h($heroTitle)?>
                </div>

                <div class="hero-sub">
                    <?=lobby_h($heroSub)?>
                </div>

            </div>

        </div>

        <a
            class="refresh-top"
            href="<?=lobby_h($lobbyUrl)?>"
        >
            Refresh
        </a>

    </section>


    <section class="summary-grid">

        <div class="summary-card">
            <div class="summary-number">
                <?=(int)$state['total']?>
            </div>
            <div class="summary-label">
                Servers
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-number">
                <?=(int)$state['players_online']?>
            </div>
            <div class="summary-label">
                Players Online
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-number">
                <?=(int)$state['active']?>
            </div>
            <div class="summary-label">
                Active Servers
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-number">
                <?=(int)$state['private']?>
            </div>
            <div class="summary-label">
                Private
            </div>
        </div>

    </section>


    <form
        class="filters"
        method="get"
        action="<?=lobby_h($lobbyUrl)?>"
    >

        <div class="filter-field filter-search">

            <label for="q">
                Search
            </label>

            <input
                id="q"
                name="q"
                type="search"
                value="<?=lobby_h($state['q'])?>"
                placeholder="Server, mission, country..."
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
                    <?=lobby_selected('all', (string)$state['source'])?>
                >
                    All sources
                </option>

                <?php foreach ($sourceOptions as $value => $label): ?>

                    <option
                        value="<?=lobby_h((string)$value)?>"
                        <?=lobby_selected(
                            strtolower((string)$value),
                            (string)$state['source']
                        )?>
                    >
                        <?=lobby_h((string)$label)?>
                    </option>

                <?php endforeach; ?>

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

                <?php foreach ($state['game_types'] as $type): ?>

                    <option
                        value="<?=lobby_h($type)?>"
                        <?=lobby_selected(
                            $type,
                            (string)$state['game_type']
                        )?>
                    >
                        <?=lobby_h($type)?>
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
                    <?=lobby_selected('all', (string)$state['population'])?>
                >
                    All servers
                </option>

                <option
                    value="players"
                    <?=lobby_selected('players', (string)$state['population'])?>
                >
                    Players online
                </option>

                <option
                    value="empty"
                    <?=lobby_selected('empty', (string)$state['population'])?>
                >
                    Empty
                </option>

                <option
                    value="full"
                    <?=lobby_selected('full', (string)$state['population'])?>
                >
                    Full
                </option>

                <option
                    value="offline"
                    <?=lobby_selected('offline', (string)$state['population'])?>
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
                    <?=lobby_selected('players_desc', (string)$state['sort'])?>
                >
                    Players: high to low
                </option>

                <option
                    value="players_asc"
                    <?=lobby_selected('players_asc', (string)$state['sort'])?>
                >
                    Players: low to high
                </option>

                <option
                    value="name_asc"
                    <?=lobby_selected('name_asc', (string)$state['sort'])?>
                >
                    Server name
                </option>

                <option
                    value="mission_asc"
                    <?=lobby_selected('mission_asc', (string)$state['sort'])?>
                >
                    Mission
                </option>

                <option
                    value="type_asc"
                    <?=lobby_selected('type_asc', (string)$state['sort'])?>
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
                href="<?=lobby_h($lobbyUrl)?>"
            >
                Reset
            </a>

        </div>

    </form>


    <div class="list-heading">

        <div>

            <strong>
                <?=lobby_h($liveHeading)?>
            </strong>

            <span>
                <?=count($rows)?>
                result<?=count($rows) === 1 ? '' : 's'?>
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
                <?=lobby_h($error)?>
            </div>

        <?php elseif (!$rows): ?>

            <div class="message">

                No <?=lobby_h($lobbyLabel)?> servers match the selected filters.

                <div class="message-actions">

                    <a
                        class="button"
                        href="<?=lobby_h($lobbyUrl)?>"
                    >
                        Reset filters
                    </a>

                </div>

            </div>

        <?php else: ?>

            <table>

                <colgroup>
                    <col class="col-game">
                    <?php if ($showStatusColumn): ?>
                        <col class="col-status">
                    <?php endif; ?>
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
                    <th><?=lobby_h($firstColumn)?></th>
                    <?php if ($showStatusColumn): ?>
                        <th
                            class="status-head"
                            aria-label="Status"
                        ></th>
                    <?php endif; ?>
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

                <?php foreach ($rows as $server): ?>

                    <?php

                    $online = (bool)$server['online'];
                    $players = (int)$server['players'];

                    $dot =
                        !$online
                        ? 'offline'
                        : (
                            $players === 0
                            ? 'idle'
                            : 'playing'
                        );

                    $statusText =
                        !$online
                        ? 'Offline'
                        : (
                            $players === 0
                            ? 'Online - empty'
                            : 'Online'
                        );

                    ?>

                    <tr class="server-row">

                        <td
                            data-label="<?=lobby_h($firstColumn)?>"
                            class="game-cell"
                        >

                            <?php if ((string)$server['image_url'] !== ''): ?>

                                <img
                                    class="game-thumb"
                                    src="<?=lobby_h($server['image_url'])?>"
                                    alt="<?=lobby_h($firstColumn)?>"
                                >

                            <?php endif; ?>

                        </td>


                        <?php if ($showStatusColumn): ?>

                            <td
                                data-label="Status"
                                class="status-cell"
                                title="<?=lobby_h($statusText)?>"
                                aria-label="<?=lobby_h($statusText)?>"
                            >

                                <span
                                    class="dot <?=lobby_h($dot)?>"
                                    aria-hidden="true"
                                ></span>

                            </td>

                        <?php endif; ?>


                        <td
                            data-label="CN"
                            class="country"
                        >
                            <?=lobby_flag_markup((string)$server['country'])?>
                        </td>


                        <td
                            data-label="Name"
                            class="name"
                        >

                            <div class="server-name<?= $statusInName ? ' with-status' : '' ?>">

                                <?php if ($statusInName): ?>
                                    <span
                                        class="dot <?=lobby_h($dot)?>"
                                        title="<?=lobby_h($statusText)?>"
                                        aria-label="<?=lobby_h($statusText)?>"
                                    ></span>
                                <?php endif; ?>

                                <span class="server-name-text">
                                    <?=lobby_nova_name((string)$server['name'])?>
                                </span>

                            </div>

                        </td>


                        <td
                            data-label="Private"
                            class="private <?=((bool)$server['private']) ? 'yes' : ''?>"
                        >
                            <?=((bool)$server['private']) ? 'Yes' : 'No'?>
                        </td>


                        <td data-label="Type">
                            <?=lobby_h($server['type'])?>
                        </td>


                        <td data-label="Mission">
                            <?=lobby_h($server['mission'])?>
                        </td>


                        <td
                            data-label="Time Left"
                            class="time-left"
                        >
                            <?=lobby_h($server['time_left'])?>
                        </td>


                        <td
                            data-label="Players"
                            class="players"
                        >
                            <?=(int)$server['players']?>
                            of
                            <?=(int)$server['max_players']?>
                        </td>


                        <td
                            data-label="Info"
                            class="info-cell"
                        >

                            <a
                                class="view"
                                href="<?=lobby_h($server['view_url'])?>"
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
        <?=lobby_h($footer)?>
    </div>

</div>




</body>
</html>
<?php
}
