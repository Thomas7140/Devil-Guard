<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function dfx2_h(mixed $value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function dfx2_safe_table(string $name): string
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        throw new RuntimeException('Invalid DFX2 database table name.');
    }

    return $name;
}

function dfx2_db_handle(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbFile = DFX2_DB_FILE;

    if (!is_file($dbFile)) {
        throw new RuntimeException(
            'DFX2 database connection file was not found: ' . $dbFile
        );
    }

    /*
     * Require inside this function so connection variables such as $pdo
     * or $db remain available in this local scope.
     */
    require_once $dbFile;

    if (function_exists('dfx2Db')) {
        $candidate = dfx2Db();

        if ($candidate instanceof PDO) {
            return $pdo = $candidate;
        }
    }

    if (function_exists('dfx2_db')) {
        $candidate = dfx2_db();

        if ($candidate instanceof PDO) {
            return $pdo = $candidate;
        }
    }

    if (isset($pdo) && $pdo instanceof PDO) {
        return $pdo;
    }

    if (isset($db) && $db instanceof PDO) {
        return $pdo = $db;
    }

    throw new RuntimeException(
        'The DFX2 connection file loaded, but it did not expose a PDO connection.'
    );
}

function dfx2_nova_name(string $value): string
{
    $colors = [
        '0' => '#FFFFFF',
        '1' => '#00FF00',
        '2' => '#FF4040',
        '3' => '#008000',
        '4' => '#FFFF00',
        '5' => '#00FFFF',
        '6' => '#A0A0A0',
        '7' => '#008080',
        '8' => '#B04040',
        '9' => '#80FFFF',
    ];

    $parts = preg_split(
        '/(~[0-9])/',
        $value,
        -1,
        PREG_SPLIT_DELIM_CAPTURE
    );

    if (!is_array($parts)) {
        return dfx2_h($value);
    }

    $color = '#FFFFFF';
    $html = '';

    foreach ($parts as $part) {

        if (preg_match('/^~([0-9])$/', $part, $m)) {
            $color = $colors[$m[1]];
            continue;
        }

        if ($part !== '') {
            $html .=
                '<span style="color:' .
                dfx2_h($color) .
                '">' .
                dfx2_h($part) .
                '</span>';
        }
    }

    return $html !== ''
        ? $html
        : dfx2_h($value);
}

function dfx2_clean_name(string $value): string
{
    return trim(
        (string)preg_replace(
            '/~[0-9]/',
            '',
            $value
        )
    );
}

function dfx2_heartbeat_age(?string $value): int
{
    if (!$value) {
        return 999999;
    }

    $time = strtotime($value);

    return $time === false
        ? 999999
        : max(0, time() - $time);
}

function dfx2_is_online(array $server): bool
{
    return
        ($server['status'] ?? '') === 'online'
        &&
        dfx2_heartbeat_age(
            $server['last_heartbeat_at'] ?? null
        ) <= DFX2_HEARTBEAT_SECONDS;
}

function dfx2_age_text(int $seconds): string
{
    if ($seconds < 5) {
        return 'just now';
    }

    if ($seconds < 60) {
        return $seconds . ' sec ago';
    }

    if ($seconds < 3600) {
        return floor($seconds / 60) . ' min ago';
    }

    if ($seconds < 86400) {
        return floor($seconds / 3600) . ' hr ago';
    }

    return floor($seconds / 86400) . ' days ago';
}

function dfx2_country(?string $code): string
{
    $code = strtoupper(
        trim((string)$code)
    );

    return preg_match('/^[A-Z]{2}$/', $code)
        ? $code
        : '--';
}
