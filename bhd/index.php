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

function bhd_country_code(mixed $value): string
{
    if (is_array($value)) {
        foreach (
            [
                'country_code',
                'countrycode',
                'code',
                'iso2',
                'iso',
                'cc',
                'cn',
                'country',
                'name',
                'flag',
            ] as $key
        ) {
            if (
                array_key_exists($key, $value)
                &&
                $value[$key] !== null
                &&
                $value[$key] !== ''
            ) {
                $code = bhd_country_code($value[$key]);

                if ($code !== '') {
                    return $code;
                }
            }
        }

        return '';
    }

    $value = trim((string)$value);

    if ($value === '') {
        return '';
    }

    /*
     * Already a clean ISO-3166 alpha-2 code.
     */
    if (preg_match('/^[A-Za-z]{2}$/', $value)) {
        return strtoupper($value);
    }

    /*
     * Handle flag filenames / paths such as:
     *   flags/au.gif
     *   /images/flags/us.png
     *   country-de.svg
     */
    if (
        preg_match(
            '~(?:^|[/_\\.-])([A-Za-z]{2})(?:\.(?:gif|png|webp|svg|avif)|$)~i',
            $value,
            $match
        )
    ) {
        return strtoupper($match[1]);
    }

    /*
     * Common country-name values returned by older Nova/NovaHQ feeds.
     */
    $name =
        strtolower(
            preg_replace(
                '/\s+/',
                ' ',
                trim($value)
            )
            ?? $value
        );

    $map = [
        'australia' => 'AU',
        'austria' => 'AT',
        'belgium' => 'BE',
        'brazil' => 'BR',
        'canada' => 'CA',
        'chile' => 'CL',
        'china' => 'CN',
        'croatia' => 'HR',
        'czech republic' => 'CZ',
        'czechia' => 'CZ',
        'denmark' => 'DK',
        'finland' => 'FI',
        'france' => 'FR',
        'germany' => 'DE',
        'greece' => 'GR',
        'hungary' => 'HU',
        'ireland' => 'IE',
        'italy' => 'IT',
        'japan' => 'JP',
        'mexico' => 'MX',
        'netherlands' => 'NL',
        'new zealand' => 'NZ',
        'norway' => 'NO',
        'poland' => 'PL',
        'portugal' => 'PT',
        'romania' => 'RO',
        'russia' => 'RU',
        'russian federation' => 'RU',
        'south africa' => 'ZA',
        'spain' => 'ES',
        'sweden' => 'SE',
        'switzerland' => 'CH',
        'turkey' => 'TR',
        'united kingdom' => 'GB',
        'great britain' => 'GB',
        'england' => 'GB',
        'scotland' => 'GB',
        'wales' => 'GB',
        'united states' => 'US',
        'united states of america' => 'US',
        'usa' => 'US',
        'us' => 'US',
        'uk' => 'GB',
    ];

    return $map[$name] ?? '';
}

function bhd_country_from_row(array $row): string
{
    /*
     * Try all field names seen in Nova/NovaHQ-era feeds.
     */
    foreach (
        [
            'country_code',
            'countrycode',
            'country_iso',
            'countryiso',
            'iso2',
            'iso',
            'cn',
            'cc',
            'country',
            'country_name',
            'countryname',
            'flag',
            'country_flag',
            'countryflag',
            'flag_url',
            'flagurl',
            'location',
        ] as $key
    ) {
        $value =
            bhd_pick(
                $row,
                [$key],
                null
            );

        if ($value === null || $value === '') {
            continue;
        }

        $code = bhd_country_code($value);

        if ($code !== '') {
            return $code;
        }
    }

    return '';
}

function bhd_is_team_sabre(array $row): bool
{
    /*
     * Use explicit mod / expansion metadata only.
     * Do NOT infer Team Sabre from the server name.
     */
    $booleanFields = [
        'ts',
        'is_ts',
        'ists',
        'team_sabre',
        'teamsabre',
        'is_team_sabre',
        'isteamsabre',
    ];

    foreach ($booleanFields as $key) {
        $value = bhd_pick(
            $row,
            [$key],
            null
        );

        if ($value !== null) {
            return bhd_bool($value, false);
        }
    }

    $modFields = [
        'mod',
        'mod_name',
        'modname',
        'game_mod',
        'gamemod',
        'game',
        'game_name',
        'gamename',
        'expansion',
        'expansion_name',
        'expansionname',
        'product',
        'product_code',
        'productcode',
        'game_code',
        'gamecode',
        'version_name',
        'versionname',
        'mod_image',
        'modimage',
        'mod_icon',
        'modicon',
    ];

    foreach ($modFields as $key) {
        $value = bhd_pick(
            $row,
            [$key],
            null
        );

        if ($value === null || $value === '') {
            continue;
        }

        if (is_array($value)) {
            $value = implode(
                ' ',
                array_map(
                    static fn ($v): string =>
                        is_scalar($v)
                            ? (string)$v
                            : '',
                    $value
                )
            );
        }

        $token = strtolower(
            preg_replace(
                '/[^a-z0-9]+/i',
                '',
                (string)$value
            )
            ?? ''
        );

        if (
            in_array(
                $token,
                [
                    'bhdts',
                    'dfbhdts',
                    'teamsabre',
                    'teamssabre',
                    'exp1',
                    'modbhdts',
                    'blackhawkdownteamsabre',
                    'deltaforceblackhawkdownteamsabre',
                ],
                true
            )
        ) {
            return true;
        }

        $text = strtolower(
            trim((string)$value)
        );

        if (
            str_contains($text, 'team sabre')
            ||
            str_contains($text, 'bhdts')
            ||
            str_contains($text, 'dfbhdts')
            ||
            str_contains($text, 'mod-bhdts')
        ) {
            return true;
        }
    }

    return false;
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
            bhd_country_from_row($raw);

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

        $isTeamSabre =
            bhd_is_team_sabre($raw);

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
            'image_url' =>
                $isTeamSabre
                ? '/images/DFBHDTS.avif'
                : '/images/DFBHD.avif',
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
        'first_column' => 'TS',
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
