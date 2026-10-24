<?php
declare(strict_types=1);

require_once '/home/devilishservices/connections/dfx/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/lobby-ui.php';

$rows = [];
$error = null;

try {
    $db = dfx_db_handle();

    $sql = "SELECT s.*,
        (
            SELECT COUNT(*)
            FROM dfx_server_players p
            WHERE p.server_id = s.id
              AND p.last_seen_at >= NOW() - INTERVAL 120 SECOND
        ) AS live_player_rows
        FROM dfx_servers s";

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
            'source' => 'dfx',
            'name' => (string)($server['server_name'] ?? 'Unnamed Server'),
            'country' => (string)($server['country_code'] ?? ''),
            'private' => (int)($server['passworded'] ?? 0) === 1,
            'type' => (string)($server['game_type'] ?? 'Unknown'),
            'mission' => (string)($server['map_name'] ?? 'Unknown'),
            'time_left' => (string)($server['time_left'] ?? '--:--'),
            'players' => $players,
            'max_players' => (int)($server['max_players'] ?? 32),
            'online' => dfx_is_online($server),
            'view_url' => 'server.php?id=' . (int)$server['id'],
            'image_url' => 'dfx.webp',
        ];
    }

} catch (Throwable $e) {
    $error = 'Unable to load the DFX server list.';
}

lobby_render_index(
    [
        'theme' => 'dfx',
        'title' => 'Delta Force Xtreme',
        'lobby_label' => 'DFX',
        'hero_title' => 'Live Server List',
        'hero_subtitle' => 'Delta Force Xtreme 1.6.9.3 multiplayer servers',
        'hero_image' => 'dfx.webp',
        'hero_alt' => 'Delta Force Xtreme',
        'row_image' => 'dfx.webp',
        'first_column' => 'DFX',
        'live_heading' => 'DFX Live Servers',
        'source_options' => [
            'dfx' => 'Devilish DFX',
        ],
        'about_url' => 'about.php',
        'main_url' => '/',
        'lobby_url' => './',
        'default_max_players' => 32,
        'footer' => 'Devilish Services • Delta Force Xtreme Nova Lobby',
    ],
    $rows,
    $error
);
