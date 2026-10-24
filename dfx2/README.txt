DFX2 Lobby
==========

This folder provides a Delta Force Xtreme 2 web lobby using the same
general BHD-style layout as the DFX/BHD lobbies.

Main page:
  /dfx2/

Server page:
  /dfx2/server.php?id=1

About:
  /dfx2/about.php

Image:
  /images/DFX2.avif

Database configuration:
  includes/config.php

Default database connection file:
  /home/devilishservices/connections/dfx2/db.php

Default tables:
  dfx2_servers
  dfx2_server_players

If the database is not configured, the web page stays available and
shows a setup message rather than a PHP fatal error.

A compatible SQL schema is in:
  sql/dfx2_lobby_tables.sql
