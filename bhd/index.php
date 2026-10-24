<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/includes/lobby-ui.php';

/*
|--------------------------------------------------------------------------
| BHD -> Universal Lobby Adapter
|--------------------------------------------------------------------------
|
| The BHD parsers remain game-specific.
| The page markup is NOT game-specific anymore.
|
| parse_novahq() / parse_novaworld() feed normalized rows into the same
| lobby_render_index() function used by DFX and DFX2.
|
*/

function bhd_ci_map(array $row): array
{
    $mapped = [];

    foreach ($row as $key => $value) {
        $mapped[strtolower((string)$key)] = $value;
    }

    return $mapped;
}

function bhd_pick(
    array $row,
    array $keys,
    mixed $default = null
): mixed {
    $row = bhd_ci_map($row);

    foreach ($keys as $key) {
        $key = strtolower((string)$key);

        if (
            array_key_exists($key, $row)
            &&
            $row[$key] !== null
            &&
            $row[$key] !== ''
        ) {
            return $row[$key];
        }
    }

    return $default;
}

function bhd_bool(
    mixed $value,
    bool $default = false
): bool {
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return (int)$value !== 0;
    }

    $value =
        strtolower(
            trim((string)$value)
        );

    if ($value === '') {
        return $default;
    }

    if (
        in_array(
            $value,
            [
                '1',
                'true',
                'yes',
                'y',
                'on',
                'private',
                'locked',
            ],
            true
        )
    ) {
        return true;
    }

    if (
        in_array(
            $value,
            [
                '0',
                'false',
                'no',
                'n',
                'off',
                'public',
                'open',
            ],
            true
        )
    ) {
        return false;
    }

    return $default;
}

function bhd_online(array $row): bool
{
    $status =
        strtolower(
            trim(
                (string)bhd_pick(
                    $row,
                    [
                        'status',
                        'server_status',
                    ],
                    ''
                )
            )
        );

    if (
        in_array(
            $status,
            [
                'offline',
                'down',
                'stale',
                'dead',
            ],
            true
        )
    ) {
        return false;
    }

    $online =
        bhd_pick(
            $row,
            [
                'online',
                'is_online',
                'isonline',
            ],
            null
        );

    if ($online !== null) {
        return bhd_bool($online, true);
    }

    /*
     * Nova/NovaHQ parser results are live-list entries by default.
     */
    return true;
}

function bhd_source_rows(
    array $rawRows,
    string $source
): array {
    $rows = [];

    foreach ($rawRows as $raw) {
        if (!is_array($raw)) {
            continue;
        }

        $id =
            (string)bhd_pick(
                $raw,
                [
                    'id',
                    'server_id',
                    'serverid',
                    'nova_id',
                    'novakey',
                    'key',
                    'server_key',
                ],
                ''
            );

        $name =
            (string)bhd_pick(
                $raw,
                [
                    'name',
                    'server_name',
                    'servername',
                    'host_name',
                    'hostname',
                ],
                'Unnamed Server'
            );

        $country =
            (string)bhd_pick(
                $raw,
                [
                    'country_code',
                    'country',
                    'cn',
                    'cc',
                ],
                ''
            );

        $private =
            bhd_bool(
                bhd_pick(
                    $raw,
                    [
                        'private',
                        'passworded',
                        'password',
                        'locked',
                    ],
                    false
                )
            );

        $type =
            (string)bhd_pick(
                $raw,
                [
                    'game_type',
                    'gametype',
                    'type',
                    'game',
                ],
                'Unknown'
            );

        $mission =
            (string)bhd_pick(
                $raw,
                [
                    'mission',
                    'mission_name',
                    'map',
                    'map_name',
                    'mapname',
                ],
                'Unknown'
            );

        $timeLeft =
            (string)bhd_pick(
                $raw,
                [
                    'time_left',
                    'timeleft',
                    'time',
                    'remaining',
                ],
                '--:--'
            );

        $playersValue =
            bhd_pick(
                $raw,
                [
                    'players',
                    'player_count',
                    'current_players',
                    'currentplayers',
                    'numplayers',
                ],
                0
            );

        $maxPlayers =
            bhd_pick(
                $raw,
                [
                    'max_players',
                    'maxplayers',
                    'player_limit',
                    'playerlimit',
                    'max',
                ],
                50
            );

        [$players, $maxPlayers] =
            lobby_parse_player_counts(
                $playersValue,
                $maxPlayers,
                50
            );

        $viewUrl =
            'server.php?source=' .
            rawurlencode($source) .
            '&id=' .
            rawurlencode($id);

        $rows[] = [
            'id' => $id,
            'source' => $source,
            'name' => $name,
            'country' => $country,
            'private' => $private,
            'type' => $type,
            'mission' => $mission,
            'time_left' => $timeLeft,
            'players' => $players,
            'max_players' => $maxPlayers,
            'online' => bhd_online($raw),
            'view_url' => $viewUrl,
            'image_url' => '/images/DFBHD.avif',
        ];
    }

    return $rows;
}


$rows = [];
$errors = [];
$sourceOptions = [];


/*
|--------------------------------------------------------------------------
| NovaHQ
|--------------------------------------------------------------------------
*/

$novaHqEnabled =
    defined('ENABLE_NOVAHQ')
    ? (bool)ENABLE_NOVAHQ
    : function_exists('parse_novahq');

if (
    $novaHqEnabled
    &&
    function_exists('parse_novahq')
) {
    try {
        $raw =
            parse_novahq();

        if (!is_array($raw)) {
            $raw = [];
        }

        $rows =
            array_merge(
                $rows,
                bhd_source_rows(
                    $raw,
                    'novahq'
                )
            );

        $sourceOptions['novahq'] = 'NovaHQ';

    } catch (Throwable $e) {
        $errors[] =
            'NovaHQ: ' .
            $e->getMessage();
    }
}


/*
|--------------------------------------------------------------------------
| Nova / NovaWorld
|--------------------------------------------------------------------------
*/

$novaEnabled =
    defined('ENABLE_NOVAWORLD')
    ? (bool)ENABLE_NOVAWORLD
    : function_exists('parse_novaworld');

if (
    $novaEnabled
    &&
    function_exists('parse_novaworld')
) {
    try {
        $raw =
            parse_novaworld();

        if (!is_array($raw)) {
            $raw = [];
        }

        $rows =
            array_merge(
                $rows,
                bhd_source_rows(
                    $raw,
                    'nova'
                )
            );

        $sourceOptions['nova'] = 'Nova';

    } catch (Throwable $e) {
        $errors[] =
            'Nova: ' .
            $e->getMessage();
    }
}


$error = null;

if (
    !$rows
    &&
    $errors
) {
    $error =
        'Unable to load the BHD server list. ' .
        implode(' | ', $errors);
}


lobby_render_index(
    [
        'theme' => 'bhd',
        'title' => 'Delta Force: Black Hawk Down',
        'lobby_label' => 'BHD',
        'hero_title' => 'Live Server List',
        'hero_subtitle' => 'Delta Force: Black Hawk Down multiplayer servers',
        'hero_image' => '/images/DFBHD.avif',
        'hero_alt' => 'Delta Force: Black Hawk Down',
        'row_image' => '/images/DFBHD.avif',
        'first_column' => 'BHD',
        'live_heading' => 'BHD Live Servers',
        'source_options' => $sourceOptions,
        'about_url' => 'about.php',
        'main_url' => '/',
        'lobby_url' => './',
        'default_max_players' => 50,
        'footer' => 'Devilish Services • Delta Force: Black Hawk Down Nova Lobby',
    ],
    $rows,
    $error
);
