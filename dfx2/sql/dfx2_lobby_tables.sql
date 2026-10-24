-- Devilish Services - Delta Force Xtreme 2 web lobby tables
-- Import into the DFX2 database only if equivalent tables do not already exist.

CREATE TABLE IF NOT EXISTS `dfx2_servers` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `server_key` varchar(128) NOT NULL,
  `server_name` varchar(128) NOT NULL,
  `game_type` varchar(64) DEFAULT NULL,
  `map_name` varchar(128) DEFAULT NULL,
  `time_left` varchar(16) DEFAULT NULL,
  `host_ip` varchar(45) NOT NULL,
  `game_port` smallint(5) UNSIGNED NOT NULL,
  `query_port` smallint(5) UNSIGNED DEFAULT NULL,
  `max_players` smallint(5) UNSIGNED NOT NULL DEFAULT 32,
  `current_players` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `passworded` tinyint(1) NOT NULL DEFAULT 0,
  `dedicated` tinyint(1) NOT NULL DEFAULT 1,
  `version` varchar(64) DEFAULT NULL,
  `mod_name` varchar(128) DEFAULT NULL,
  `country_code` char(2) DEFAULT NULL,
  `status` enum('online','offline','starting','stopping') NOT NULL DEFAULT 'online',
  `website_url` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_heartbeat_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dfx2_server_key` (`server_key`),
  KEY `idx_dfx2_servers_status` (`status`),
  KEY `idx_dfx2_servers_heartbeat` (`last_heartbeat_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `dfx2_server_players` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `server_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `profile_id` bigint(20) UNSIGNED DEFAULT NULL,
  `player_name` varchar(64) NOT NULL,
  `score` int(11) NOT NULL DEFAULT 0,
  `kills` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deaths` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `ping` int(10) UNSIGNED DEFAULT NULL,
  `team` varchar(32) DEFAULT NULL,
  `joined_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_seen_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dfx2_players_server` (`server_id`),
  KEY `idx_dfx2_players_seen` (`last_seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
