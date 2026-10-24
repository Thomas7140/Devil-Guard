<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/lobby-ui.php';

$rows = [];
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

    $servers =
        $db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);

    foreach ($servers as $server) {
        $players =
            max(
                (int)($server['current_players'] ?? 0),
                (int)($server['live_player_rows'] ?? 0)
            );

        $rows[] = [
            'id' => (string)$server['id'],
            'source' => 'dfx2',
            'name' => (string)($server['server_name'] ?? 'Unnamed Server'),
            'country' => (string)($server['country_code'] ?? ''),
            'private' => (int)($server['passworded'] ?? 0) === 1,
            'type' => (string)($server['game_type'] ?? 'Unknown'),
            'mission' => (string)($server['map_name'] ?? 'Unknown'),
            'time_left' => (string)($server['time_left'] ?? '--:--'),
            'players' => $players,
            'max_players' => (int)($server['max_players'] ?? 32),
            'online' => dfx2_is_online($server),
            'view_url' => 'server.php?id=' . (int)$server['id'],
            'image_url' => '/images/DFX2.avif',
        ];
    }

} catch (Throwable $e) {
    $error =
        'The DFX2 lobby database is not configured yet. ' .
        'Check includes/config.php and the DFX2 database connection.';
}

lobby_render_index(
    [
        'theme' => 'dfx2',
        'title' => 'Delta Force Xtreme 2',
        'lobby_label' => 'DFX2',
        'hero_title' => 'Live Server List',
        'hero_subtitle' => 'Delta Force Xtreme 2 multiplayer servers',
        'hero_image' => '/images/DFX2.avif',
        'hero_alt' => 'Delta Force Xtreme 2',
        'row_image' => '/images/DFX2.avif',
        'first_column' => 'DFX2',
        'live_heading' => 'DFX2 Live Servers',
        'source_options' => [
            'dfx2' => 'Devilish DFX2',
        ],
        'about_url' => 'about.php',
        'main_url' => '/',
        'lobby_url' => './',
        'default_max_players' => 32,
        'footer' => 'Devilish Services • Delta Force Xtreme 2 Nova Lobby',
    ],
    $rows,
    $error
);
