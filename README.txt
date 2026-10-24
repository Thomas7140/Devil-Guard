DFX Lobby - BHD-Style Layout
================================

This package updates the Devilish Services DFX web lobby so it follows
the same overall layout and controls used by the BHD lobby, while using
DFX blue / steel / cyan styling and the DFX database.

Target:
  https://nova-lobby.devilishservices.com/DFX/

Server path:
  /home/devilishservices/public_html/sub-domains/nova-lobby.devilishservices.com/DFX/

IMPORTANT
---------
Keep the existing file:

  DFX/dfx.webp

The lobby uses that image for the DFX header and the first column of the
server list.

Updated lobby features
----------------------
- Servers summary counter
- Players Online summary counter
- Active Servers summary counter
- Private server summary counter
- Search filter
- Source filter
- Game type filter
- Population filter
- Sort filter
- Apply button
- Reset link
- BHD-style full server table
- DFX / CN / Name / Private / Type / Mission / Time Left / Players / Info
- No horizontal scrolling server list on smaller screens
- DFX Nova ~0 through ~9 colour rendering
- Slow flashing green dot for online empty servers
- Solid green dot for servers with players
- Red dot for stale/offline servers
- DFX server details page
- DFX About page

Database
--------
The lobby reads:

  /home/devilishservices/connections/dfx/db.php

and uses:

  dfx_servers
  dfx_server_players

A server is active when status='online' and last_heartbeat_at is no more
than 120 seconds old.

Time Left
---------
The current dfx_servers schema does not require a time_left column.
The lobby displays --:-- when no time_left value exists. If a time_left
column is added later and populated by the native DFX protocol, the lobby
will use it automatically.

Install
-------
Upload/overwrite:

  DFX/index.php
  DFX/server.php
  DFX/about.php
  DFX/includes/functions.php
  DFX/assets/lobby.css

Do not delete your existing DFX/dfx.webp.

Test:
  https://nova-lobby.devilishservices.com/DFX/
  https://nova-lobby.devilishservices.com/DFX/server.php?id=1
