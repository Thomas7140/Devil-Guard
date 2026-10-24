DFX Lobby Update - Devilish Services
====================================

This package mirrors the BHD-style lobby layout for Delta Force Xtreme using a DFX blue/steel/cyan theme.

Upload the contents of the DFX folder to:
  /home/devilishservices/public_html/sub-domains/nova-lobby.devilishservices.com/DFX/

Required shared database file:
  /home/devilishservices/connections/dfx/db.php

Included:
  DFX/index.php
  DFX/server.php
  DFX/about.php
  DFX/includes/functions.php
  DFX/assets/lobby.css

Database tables used:
  dfx_servers
  dfx_server_players

Lobby columns:
  DFX | CN | Name | Private | Type | Mission | Time Left | Players | Info

Time Left currently displays --:-- because the existing dfx_servers schema does not store that field yet.

Status:
  Solid green = online with players
  Slow flashing green = online with zero players
  Red = offline/stale heartbeat

After upload:
  php -l DFX/index.php
  php -l DFX/server.php
  php -l DFX/about.php
  php -l DFX/includes/functions.php

Web URLs:
  https://nova-lobby.devilishservices.com/DFX/
  https://nova-lobby.devilishservices.com/DFX/server.php?id=1
  https://nova-lobby.devilishservices.com/DFX/about.php
