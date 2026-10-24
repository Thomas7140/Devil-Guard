Devilish Services Nova Lobby - Shared Lobby UI
================================================

GOAL
====
All game lobbies now use the Delta Force Xtreme lobby as the master
format and layout.

The active shared layout file is:

    /assets/lobby.css

That single file controls:

- page width and spacing
- top navigation
- live server hero
- four summary cards
- Search / Source / Game type / Population / Sort filters
- Apply / Reset controls
- Live Servers heading
- server table
- status dot column
- View buttons
- server detail pages
- About pages
- responsive/mobile layout
- no horizontal scrolling server list

THEMES
======
Each lobby selects a theme on the BODY element.

BHD:
    <body data-lobby-theme="bhd">

DFX:
    <body data-lobby-theme="dfx">

DFX2:
    <body data-lobby-theme="dfx2">

All theme colors are also stored in:

    /assets/lobby.css

Current themes:

    BHD   = military green
    DFX   = blue / steel / cyan
    DFX2  = orange / brown

This means future layout changes should normally be made ONLY in:

    /assets/lobby.css

and they will apply to every lobby.

LOBBIES
=======

Main:
    https://nova-lobby.devilishservices.com/

BHD:
    https://nova-lobby.devilishservices.com/bhd/

DFX:
    https://nova-lobby.devilishservices.com/DFX/

DFX2:
    https://nova-lobby.devilishservices.com/dfx2/

ROOT PANEL IMAGES
=================

BHD:
    /images/DFBHD.avif

DFX:
    /images/DFX.avif

DFX2:
    /images/DFX2.avif

DFX LOBBY IMAGE
===============

The DFX lobby itself continues to use:

    /DFX/dfx.webp

for its server/header image.

BHD INSTALL / UPDATE
====================

The BHD site has older PHP markup and live Nova/NovaHQ logic that should
not be replaced blindly.

After uploading this package over the nova-lobby web root, run:

    cd /home/devilishservices/public_html/sub-domains/nova-lobby.devilishservices.com
    php tools/patch-bhd-match-dfx-layout.php

The patcher:

- backs up bhd/index.php
- backs up bhd/partials.php
- preserves BHD server fetching
- preserves Nova/NovaHQ data
- preserves tilde colors
- preserves BHD View/Join behavior
- removes the old BHD intro/branding panels
- changes the header to:
      Delta Force: Black Hawk Down
      Devilish Services Nova Lobby
      Main Lobby | BHD Lobby | About
- loads the shared /assets/lobby.css file
- selects the BHD theme with data-lobby-theme="bhd"

The older BHD patch scripts are no longer required.

DFX
===

DFX pages now load:

    /assets/lobby.css

with:

    data-lobby-theme="dfx"

Files:
    DFX/index.php
    DFX/server.php
    DFX/about.php

The DFX server list status indicator is a dot-only column immediately
before CN.

DFX2
====

DFX2 pages now load:

    /assets/lobby.css

with:

    data-lobby-theme="dfx2"

Files:
    dfx2/index.php
    dfx2/server.php
    dfx2/about.php

The DFX2 server list also uses the dot-only status column immediately
before CN so its table matches the DFX format.

DFX2 DATABASE
=============

Default connection file:

    /home/devilishservices/connections/dfx2/db.php

Default tables:

    dfx2_servers
    dfx2_server_players

Configuration:

    dfx2/includes/config.php

Compatible SQL schema:

    dfx2/sql/dfx2_lobby_tables.sql

Example database connection:

    dfx2/db.example.php

Do not use db.example.php without replacing the placeholder database
credentials.

CENTRAL COLOR EDITING
=====================

Open:

    /assets/lobby.css

Near the top you will see three sections:

    body[data-lobby-theme="bhd"]
    body[data-lobby-theme="dfx"]
    body[data-lobby-theme="dfx2"]

Change only those variables when you want to alter the color scheme
without changing the shared layout.

IMPORTANT
=========

Do not add separate lobby.css files back into DFX, DFX2 or BHD unless
there is a specific game-only exception.

The intended setup is one shared layout file:

    /assets/lobby.css
