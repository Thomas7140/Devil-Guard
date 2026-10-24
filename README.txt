Devilish Services Nova Lobby - 3 Game Update
================================================

This package adds/updates:

1. Main root lobby:
   https://nova-lobby.devilishservices.com/

   Panels:
   - BHD  -> /bhd/
   - DFX  -> /DFX/
   - DFX2 -> /dfx2/

   Root panel images:
   - /images/DFBHD.avif
   - /images/DFX.avif
   - /images/DFX2.avif

2. DFX lobby
   The existing DFX BHD-style lobby remains in /DFX/.

3. DFX2 lobby
   A new BHD-style lobby is provided in /dfx2/.

4. BHD Main Lobby button
   The package intentionally does NOT replace your existing working
   bhd/index.php.

   Run this once after uploading the package:

       cd /home/devilishservices/public_html/sub-domains/nova-lobby.devilishservices.com
       php tools/patch-bhd-main-lobby.php

   The script:
   - locates bhd/index.php
   - makes a timestamped backup
   - adds a fixed "Main Lobby" button linking to /
   - refuses to patch it twice

DFX2 DATABASE
=============

Default connection file:

    /home/devilishservices/connections/dfx2/db.php

Default tables:

    dfx2_servers
    dfx2_server_players

Edit:

    dfx2/includes/config.php

if your path or table names are different.

A compatible schema is included at:

    dfx2/sql/dfx2_lobby_tables.sql

An example PDO connection file is included at:

    dfx2/db.example.php

Do not upload db.example.php into the shared connections directory
without replacing the placeholder username/password.

IMAGES
======

This package references the image files already on your web server:

    images/DFBHD.avif
    images/DFX.avif
    images/DFX2.avif

Make sure the capitalization matches exactly.

TEST URLS
=========

Main:
    https://nova-lobby.devilishservices.com/

BHD:
    https://nova-lobby.devilishservices.com/bhd/

DFX:
    https://nova-lobby.devilishservices.com/DFX/

DFX2:
    https://nova-lobby.devilishservices.com/dfx2/


BHD DFX-STYLE HEADER
====================

To make the BHD header match the simple DFX-style navigation, run:

    cd /home/devilishservices/public_html/sub-domains/nova-lobby.devilishservices.com
    php tools/patch-bhd-dfx-style-header.php

The patcher backs up:

    bhd/partials.php

before replacing only render_header().

The new BHD header becomes:

    Delta Force: Black Hawk Down
    Devilish Services Nova Lobby

    Main Lobby | BHD Lobby | About

This removes the old visual header wording such as:

    DEVILISH SERVICES
    NOVA BHD LOBBY
    Delta Force: Black Hawk Down Multiplayer Network
    LIVE NETWORK
    Servers | Gametypes | System

from the shared page header while leaving the BHD server browser and
the rest of the BHD page content unchanged.
